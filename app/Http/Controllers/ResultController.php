<?php

namespace App\Http\Controllers;

use App\Models\{
    Assessment,
    QuestionResult,
    UsageRecord
};

use App\Services\{
    PaperMarker,
    AuditService,
    PdfReportService
};

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;


class ResultController extends Controller
{

    public function show(Assessment $assessment)
    {
        $this->access($assessment);

        $assessment->load([
            'results'=>function($q){

                $q->orderByRaw(
                    "CAST(question_number AS INTEGER)"
                )
                ->orderBy('question_part');

            },

            'student',
            'communications',
            'assignedTeacher'
        ]);

        return view(
            'results.show',
            compact('assessment')
        );
    }


    public function update(
        Request $request,
        QuestionResult $result,
        AuditService $audit
    ){

        $this->access($result->assessment);

        abort_unless(
            $request->user()->hasFeature('manual_mark_editing'),
            403
        );


        $data=$request->validate([
            'teacher_marks'=>'required|numeric|min:0',
            'teacher_comment'=>'nullable|string|max:2000'
        ]);


        abort_if(
            (float)$data['teacher_marks'] >
            (float)$result->max_marks,
            422,
            'Final marks cannot exceed maximum marks.'
        );


        $before=$result->teacher_marks;


        $result->update(
            $data + [
                'status'=>'reviewed'
            ]
        );


        $this->recalculate(
            $result->assessment->fresh('results')
        );


        $audit->log(
            $result,
            'result.teacher_edited',
            [
                'before'=>$before,
                'after'=>$data['teacher_marks']
            ]
        );


        return back()->with(
            'success',
            'Teacher mark and feedback saved.'
        );
    }





    public function recheckQuestion(
        QuestionResult $result,
        PaperMarker $marker,
        AuditService $audit
    ){

        $this->access($result->assessment);


        abort_unless(
            request()->user()->hasFeature('question_recheck'),
            403
        );



        $owner=request()
            ->user()
            ->organisationOwnerId();



        $limit=request()
            ->user()
            ->limit(
                'recheck_limit',
                100
            );


        $used=UsageRecord::where('user_id',$owner)
            ->where(
                'type',
                'question_recheck'
            )
            ->where(
                'created_at',
                '>=',
                now()->startOfMonth()
            )
            ->sum('quantity');



        abort_if(
            $limit>0 && $used >= $limit,
            422,
            'Question recheck limit reached for this billing cycle.'
        );




        $payload=$marker->mark(
            $result->assessment
        );



        /*
        |--------------------------------------------------------------------------
        | Match question + sub-question
        |--------------------------------------------------------------------------
        */


        $fresh=collect(
            $payload['questions'] ?? []
        )
        ->first(
            function($q) use($result){

                return

                ($q['question_number'] ?? null)
                ==
                $result->question_number

                &&

                strtolower(
                    $q['question_part'] ?? ''
                )
                ==
                strtolower(
                    $result->question_part ?? ''
                );

            }
        );



        abort_unless(
            $fresh,
            422,
            'The question could not be matched during recheck.'
        );




        $result->update([

            'ai_marks'=>
                $fresh['ai_marks']
                ??
                $fresh['marks']
                ??
                0,


            'confidence'=>
                $fresh['confidence']
                ??
                'medium',


            'feedback'=>
                $fresh['feedback']
                ??
                null,


            'criteria'=>
                $fresh['criteria']
                ??
                null,


            'status'=>'rechecked',


            'recheck_count'=>
                $result->recheck_count + 1,


            'last_rechecked_at'=>now()

        ]);




        UsageRecord::create([

            'user_id'=>
                request()
                ->user()
                ->organisationOwnerId(),


            'type'=>'question_recheck',


            'reference_type'=>QuestionResult::class,


            'reference_id'=>$result->id

        ]);



        $audit->log(
            $result,
            'result.rechecked'
        );



        $this->recalculate(
            $result->assessment->fresh('results')
        );



        $label =
            $result->question_part
            ?
            $result->question_number .
            '(' .
            $result->question_part .
            ')'
            :
            $result->question_number;



        return back()->with(
            'success',
            $label .
            ' rechecked. Teacher mark, if already set, remains unchanged.'
        );

    }





    public function finalise(
        Assessment $assessment,
        AuditService $audit
    ){

        $this->access($assessment);


        $this->recalculate(
            $assessment->load('results')
        );



        $assessment->update([

            'status'=>'finalised',

            'finalised_at'=>now(),

            'public_report_token'=>
                $assessment->public_report_token
                ?:
                Str::random(48),


            'public_report_expires_at'=>
                now()->addDays(
                    config(
                        'exam-marker.parent_link_days',
                        30
                    )
                ),


            'report_version'=>
                $assessment->report_version+1

        ]);



        $audit->log(
            $assessment,
            'assessment.finalised'
        );


        return back()->with(
            'success',
            'Result finalised and locked as the current approved result.'
        );

    }





    public function report(Assessment $assessment)
    {
        $this->access($assessment);


        abort_unless(
            request()->user()->hasFeature('result_report_download'),
            403
        );


        $assessment->load(
            'results',
            'student',
            'user'
        );


        return view(
            'reports.result',
            compact('assessment')
        );
    }






    public function downloadReport(
        Assessment $assessment,
        PdfReportService $pdf
    ){

        $this->access($assessment);


        abort_unless(
            request()->user()->hasFeature('result_report_download'),
            403
        );


        $assessment->load(
            'results',
            'student'
        );


        UsageRecord::create([

            'user_id'=>
                request()->user()->organisationOwnerId(),

            'type'=>'result_report_download',

            'reference_type'=>Assessment::class,

            'reference_id'=>$assessment->id

        ]);



        return response(
            $pdf->make($assessment),
            200,
            [
                'Content-Type'=>'application/pdf',

                'Content-Disposition'=>
                'attachment; filename="result-report-'.$assessment->id.'.pdf"'
            ]
        );

    }






    public function correctedPaper(
        Assessment $assessment
    ){

        $this->access($assessment);


        abort_unless(
            request()->user()->hasFeature('corrected_paper_download'),
            403
        );


        $assessment->load(
            'results',
            'student'
        );


        $tmp=tempnam(
            sys_get_temp_dir(),
            'corrected-'
        ).'.zip';



        $zip=new ZipArchive;


        if(
            $zip->open(
                $tmp,
                ZipArchive::CREATE |
                ZipArchive::OVERWRITE
            )
            !== true
        ){

            abort(
                500,
                'Unable to build corrected-paper package.'
            );

        }


        $contents=Storage::get(
            $assessment->written_answer_path
        );


        $zip->addFromString(
            'original-answer.'.
            pathinfo(
                $assessment->written_answer_path,
                PATHINFO_EXTENSION
            ),
            $contents
        );



        $summary=view(
            'reports.result',
            compact('assessment')
        )->render();



        $zip->addFromString(
            'correction-summary.html',
            $summary
        );


        $zip->close();




        UsageRecord::create([

            'user_id'=>
                request()->user()->organisationOwnerId(),

            'type'=>'corrected_paper_download',

            'reference_type'=>Assessment::class,

            'reference_id'=>$assessment->id

        ]);



        return response()
            ->download(
                $tmp,
                'corrected-paper-'.$assessment->id.'.zip'
            )
            ->deleteFileAfterSend(true);

    }







    private function recalculate(
        Assessment $a
    ){

        $obtained=$a->results->sum(
            fn($r)=>
            (float)(
                $r->teacher_marks
                ??
                $r->ai_marks
            )
        );


        $maximum=$a->results->sum(
            fn($r)=>
            (float)$r->max_marks
        )
        ?:
        $a->total_marks;



        $pct=$maximum
            ?
            round(
                $obtained/$maximum*100,
                2
            )
            :
            0;



        $a->update([

            'percentage'=>$pct,


            'grade_awarded'=>

            $pct>=90?'A+':

            (
                $pct>=80?'A':
                (
                    $pct>=70?'B':
                    (
                        $pct>=60?'C':
                        (
                            $pct>=50?'D':
                            'Needs Improvement'
                        )
                    )
                )
            )

        ]);

    }





    private function access(
        Assessment $a
    ){

        abort_unless(
            request()
            ->user()
            ->canAccessAssessment($a),
            403
        );

    }

}