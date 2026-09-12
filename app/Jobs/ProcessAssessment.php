<?php

namespace App\Jobs;


use App\Models\{
    Assessment,
    UsageRecord,
    QuestionResult
};


use App\Services\{
    PaperMarker,
    AuditService
};


use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class ProcessAssessment implements ShouldQueue
{


    use Queueable;



    public int $tries = 2;


    public int $timeout = 180;





    public function __construct(
        public int $assessmentId
    )
    {
    }







    public function handle(
        PaperMarker $marker,
        AuditService $audit
    ): void
    {



        $assessment = Assessment::findOrFail(
            $this->assessmentId
        );





        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Processing
        |--------------------------------------------------------------------------
        */


        if(
            in_array(
                $assessment->status,
                [
                    'processing',
                    'completed'
                ]
            )
        )
        {

            return;

        }







        /*
        |--------------------------------------------------------------------------
        | Start Processing
        |--------------------------------------------------------------------------
        */


        $assessment->update([


            'status'=>'processing',


            'processing_started_at'=>now(),


            'error_message'=>null


        ]);








        $audit->log(

            $assessment,

            'AI_PROCESSING_STARTED',

            [
                'assessment_id'=>$assessment->id
            ]

        );








        try{


            /*
            |--------------------------------------------------------------------------
            | AI MARKING
            |--------------------------------------------------------------------------
            */


            $payload=$marker->mark(
                $assessment
            );








            if(

                !isset($payload['questions'])

                ||

                !is_array($payload['questions'])

            )
            {


                throw new \Exception(
                    'AI returned invalid marking response.'
                );


            }










            DB::transaction(function()
            use(
                $assessment,
                $payload
            ){




                /*
                Remove old results
                */

                $assessment
                ->results()
                ->delete();







                /*
                Save Question Results
                */


                foreach(
                    $payload['questions']
                    as $question
                )
                {




                    QuestionResult::create([



                        'assessment_id'=>
                            $assessment->id,



                        'question_number'=>
                            $question['question_number']
                            ??
                            null,


                        'question_part'=>
                            $question['question_part']
                            ??
                            null,


                        'parent_question_number'=>
                            $question['parent_question_number']
                            ??
                            ($question['question_number'] ?? null),


                        'topic'=>

                            $question['topic']
                            ??
                            null,



                        'max_marks'=>
                            $question['max_marks']
                            ??
                            0,



                        'ai_marks'=>
                            $question['ai_marks']
                            ??
                            $question['marks']
                            ??
                            0,



                        'teacher_marks'=>null,



                        'confidence'=>
                            $question['confidence']
                            ??
                            'medium',



                        'feedback'=>
                            $question['feedback']
                            ??
                            null,



                        'criteria'=>
                            $question['criteria']
                            ??
                            [],



                        'status'=>
                            $question['status']
                            ??
                            'ai_checked',



                        'recheck_count'=>0,


                    ]);



                }




            });









            /*
            |--------------------------------------------------------------------------
            | Complete Assessment
            |--------------------------------------------------------------------------
            */


            $assessment->update([



                'status'=>
                    'review_required',



                'percentage'=>
                    $payload['percentage']
                    ??
                    0,



                'grade_awarded'=>
                    $payload['grade']
                    ??
                    null,



                'ai_summary'=>
                    $payload['summary']
                    ??
                    [],



                'processing_completed_at'=>now()



            ]);









            /*
            |--------------------------------------------------------------------------
            | Usage Tracking
            |--------------------------------------------------------------------------
            */


            UsageRecord::create([


                'user_id'=>
                    $assessment->user_id,


                'type'=>
                    'paper_processed',



                'reference_type'=>
                    Assessment::class,



                'reference_id'=>
                    $assessment->id



            ]);









            $audit->log(


                $assessment,


                'AI_PROCESSING_COMPLETED',



                [

                    'percentage'=>
                    $assessment->percentage


                ]


            );






        }

        catch(\Throwable $e)

        {


            Log::error(

                'AI Processing Failed',

                [

                    'assessment_id'=>
                    $assessment->id,


                    'error'=>
                    $e->getMessage()


                ]

            );







            $assessment->update([


                'status'=>'failed',


                'processing_completed_at'=>now(),


                'error_message'=>
                    $e->getMessage()


            ]);








            $audit->log(


                $assessment,


                'AI_PROCESSING_FAILED',



                [

                    'error'=>
                    $e->getMessage()


                ]

            );





            throw $e;


        }



    }



}