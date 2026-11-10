<?php
namespace App\Jobs;

use App\Models\{Assessment,QuestionResult,UsageRecord};
use App\Services\{AuditService,LifecycleNotifier,MarkingPayloadValidator,PaperMarker};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\{DB,Log};
use Illuminate\Support\Str;
use Throwable;

class ProcessAssessment implements ShouldQueue
{
    use Queueable;
    public int $tries=2;
    public int $timeout=180;
    public bool $failOnTimeout=true;
    public function __construct(public int $assessmentId,public ?string $processingToken=null) {}
    public function middleware(): array { return [(new WithoutOverlapping('assessment:'.$this->assessmentId))->shared()->releaseAfter(10)->expireAfter(240)]; }
    public function handle(PaperMarker $marker,AuditService $audit): void
    {
        $claim=(string)Str::uuid();
        $claimed=$this->attempt()->where(function($query){
            $query->whereIn('status',['queued','failed'])->orWhere(fn($stale)=>$stale->where('status','processing')->where('processing_started_at','<=',now()->subSeconds(185)));
        })->update(['status'=>'processing','processing_claim_token'=>$claim,'processing_started_at'=>now(),'processing_completed_at'=>null,'error_message'=>null]);
        if(!$claimed) return;
        $assessment=Assessment::findOrFail($this->assessmentId);
        try {
            $audit->log($assessment,'AI_PROCESSING_STARTED');
            $payload=app(MarkingPayloadValidator::class)->validate($assessment,$marker->mark($assessment));
            $saved=DB::transaction(function()use($payload,$claim,$audit){
                $current=$this->attempt()->where('processing_claim_token',$claim)->where('status','processing')->lockForUpdate()->first();
                if(!$current || $current->finalised_at) return false;
                $current->results()->delete();
                foreach($payload['questions'] as $question) QuestionResult::create([
                    'assessment_id'=>$current->id,'question_number'=>$question['question_number'],'question_part'=>$question['question_part']??null,
                    'parent_question_number'=>$question['parent_question_number']??$question['question_number'],'type'=>$question['type']??'normal',
                    'topic'=>$question['topic']??null,'max_marks'=>$question['max_marks'],'ai_marks'=>$question['ai_marks'],'teacher_marks'=>null,
                    'confidence'=>$question['confidence'],'feedback'=>$question['feedback']??null,'criteria'=>$question['criteria']??[],
                    'writing_rubric'=>$question['writing_rubric']??null,'status'=>$question['status']??'ai_checked','recheck_count'=>0,
                ]);
                $current->update(['status'=>'review_required','percentage'=>$payload['percentage'],'grade_awarded'=>$payload['grade'],
                    'ai_summary'=>$payload['summary']??[],'processing_completed_at'=>now(),'error_message'=>null]);
                UsageRecord::firstOrCreate(['user_id'=>$current->user_id,'type'=>'paper_processed','reference_type'=>Assessment::class,'reference_id'=>$current->id]);
                $audit->log($current,'AI_PROCESSING_COMPLETED',['percentage'=>$current->percentage]);
                return true;
            });
            if($saved) foreach(collect([$assessment->user,$assessment->assignedTeacher])->filter()->unique('id') as $recipient)
                app(LifecycleNotifier::class)->send($recipient,'processing','Paper checking completed','Your paper is ready for review.',route('results.show',$assessment));
        } catch(Throwable $error){
            Log::error('Paper processing failed',['assessment_id'=>$assessment->id,'error'=>$error->getMessage()]);
            $changed=$this->attempt()->where('processing_claim_token',$claim)->where('status','processing')->update([
                'status'=>'failed','processing_completed_at'=>now(),'error_message'=>$this->message($error)]);
            if($changed){
                $audit->log($assessment,'AI_PROCESSING_FAILED',['error'=>$this->message($error)]);
                app(LifecycleNotifier::class)->send($assessment->user,'processing','Paper checking needs attention',$this->message($error),route('assessments.processing',$assessment));
            }
            throw $error;
        }
    }
    public function failed(?Throwable $exception): void { $this->recordFailure($exception??new \RuntimeException('Checking exceeded its time limit. Please retry this paper.')); }
    public function recordFailure(Throwable $error): void
    {
        $this->attempt()->whereIn('status',['queued','processing'])->update(['status'=>'failed','processing_completed_at'=>now(),'error_message'=>$this->message($error)]);
    }
    private function attempt(): \Illuminate\Database\Eloquent\Builder { return Assessment::whereKey($this->assessmentId)->where('processing_token',$this->processingToken); }
    private function message(Throwable $error): string
    {
        return $error instanceof \RuntimeException && !$error instanceof \Illuminate\Database\QueryException
            ? $error->getMessage() : 'Checking could not complete. Please retry or contact support with paper #'.$this->assessmentId.'.';
    }
}