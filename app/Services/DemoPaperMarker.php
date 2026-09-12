<?php

namespace App\Services;

use App\Models\Assessment;


class DemoPaperMarker implements PaperMarker
{

    public function mark(Assessment $a): array
    {


        $questions = [

            [
                'question_number'=>'1',
                'question_part'=>'a',
                'parent_question_number'=>'1',
                'topic'=>'Algebra',
                'max_marks'=>2,
                'ai_marks'=>2,
                'confidence'=>'high',
                'feedback'=>'Correct algebraic method and final answer identified.',
                'criteria'=>[
                    [
                        'label'=>'Correct method',
                        'awarded'=>true
                    ],
                    [
                        'label'=>'Correct answer',
                        'awarded'=>true
                    ]
                ],
                'status'=>'ai_checked'
            ],



            [
                'question_number'=>'1',
                'question_part'=>'b',
                'parent_question_number'=>'1',
                'topic'=>'Algebra',
                'max_marks'=>3,
                'ai_marks'=>2,
                'confidence'=>'medium',
                'feedback'=>'Method is correct but one step requires improvement.',
                'criteria'=>[
                    [
                        'label'=>'Working shown',
                        'awarded'=>true
                    ],
                    [
                        'label'=>'Final answer',
                        'awarded'=>false
                    ]
                ],
                'status'=>'review_required'
            ],




            [
                'question_number'=>'1',
                'question_part'=>'c',
                'parent_question_number'=>'1',
                'topic'=>'Algebra',
                'max_marks'=>2,
                'ai_marks'=>1,
                'confidence'=>'medium',
                'feedback'=>'Partial solution identified.',
                'criteria'=>[
                    [
                        'label'=>'Relevant approach',
                        'awarded'=>true
                    ]
                ],
                'status'=>'review_required'
            ],




            [
                'question_number'=>'2',
                'question_part'=>'a',
                'parent_question_number'=>'2',
                'topic'=>'Calculus',
                'max_marks'=>4,
                'ai_marks'=>4,
                'confidence'=>'high',
                'feedback'=>'Correct differentiation steps.',
                'criteria'=>[
                    [
                        'label'=>'Correct derivative',
                        'awarded'=>true
                    ]
                ],
                'status'=>'ai_checked'
            ],




            [
                'question_number'=>'2',
                'question_part'=>'b',
                'parent_question_number'=>'2',
                'topic'=>'Calculus',
                'max_marks'=>4,
                'ai_marks'=>3,
                'confidence'=>'high',
                'feedback'=>'Minor calculation error found.',
                'criteria'=>[
                    [
                        'label'=>'Correct process',
                        'awarded'=>true
                    ]
                ],
                'status'=>'ai_checked'
            ]

        ];





        $obtained =
            array_sum(
                array_column(
                    $questions,
                    'ai_marks'
                )
            );



        $maximum =
            array_sum(
                array_column(
                    $questions,
                    'max_marks'
                )
            );



        $percentage =
            round(
                $obtained / max(1,$maximum) * 100,
                2
            );





        return [

            'questions'=>$questions,


            'percentage'=>$percentage,


            'grade'=>

                $percentage >= 90 ? 'A+' :

                (
                    $percentage >= 80 ? 'A' :

                    (
                        $percentage >= 70 ? 'B' :

                        (
                            $percentage >= 60 ? 'C' :

                            (
                                $percentage >= 50 ? 'D' :

                                'Needs Improvement'
                            )
                        )
                    )
                ),



            'summary'=>[

                'strengths'=>[
                    'Good method selection',
                    'Most answers show logical steps'
                ],


                'weaknesses'=>[
                    'Review incomplete working',
                    'Improve final explanations'
                ],


                'notice'=>
                'Demo sub-question marking data generated for testing.'

            ]

        ];

    }

}