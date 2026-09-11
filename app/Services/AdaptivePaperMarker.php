<?php

namespace App\Services;

use App\Contracts\DocumentReader;
use App\Models\Assessment;
use Illuminate\Support\Str;


class AdaptivePaperMarker implements PaperMarker
{


    public function __construct(
        private DocumentReader $reader
    )
    {
    }





    public function mark(Assessment $assessment): array
    {


        $docs = $this->reader->read($assessment);



        $qpText = $docs['qp_text'] ?? '';

        $waText = $docs['wa_text'] ?? '';

        $msText = $docs['ms_text'] ?? '';




        if(trim($qpText)==='')
        {
            throw new \Exception(
                'Unable to extract Question Paper text.'
            );
        }




        if(trim($waText)==='')
        {
            throw new \Exception(
                'Unable to extract Written Answer text.'
            );
        }





        /*
        |--------------------------------------------------------------------------
        | Detect Questions
        |--------------------------------------------------------------------------
        */


        $questions = $this->parseQuestions(
            $qpText,
            (float)$assessment->total_marks
        );



        $labels = array_column(
            $questions,
            'label'
        );




        /*
        |--------------------------------------------------------------------------
        | Split Student Answers
        |--------------------------------------------------------------------------
        */


        $answers = $this->splitByQuestions(
            $waText,
            $labels
        );



        /*
        |--------------------------------------------------------------------------
        | Split Mark Scheme
        |--------------------------------------------------------------------------
        */


        $schemes = $this->splitByQuestions(
            $msText,
            $labels
        );





        $rows=[];


        $obtained=0;


        $maxTotal=0;



        $strengths=[];


        $weak=[];





        foreach($questions as $question)
        {


            $label=$question['label'];



            $max=(float)$question['marks'];



            $answer = trim(
                $answers[$label] ?? ''
            );



            $scheme = trim(
                $schemes[$label] ?? ''
            );





            $result=$this->score(
                $answer,
                $scheme,
                $max,
                $question['text']
            );





            $obtained += $result['marks'];


            $maxTotal += $max;





            if($result['ratio']>=0.75)
            {

                $strengths[] =
                $label.': Strong response';

            }
            elseif($result['ratio']<0.45)
            {

                $weak[] =
                $label.': Requires improvement';

            }






            $rows[]=[


                'question_number'=>$label,


                'topic'=>$this->inferTopic(
                    $question['text'],
                    $assessment->subject
                ),



                'max_marks'=>$max,



                'ai_marks'=>$result['marks'],



                'confidence'=>$result['confidence'],



                'feedback'=>$result['feedback'],



                'criteria'=>$result['criteria'],



                'status'=>
                $result['confidence']==='low'
                ?
                'review_required'
                :
                'ai_checked'


            ];



        }





        $percentage =
        $maxTotal>0
        ?
        round(
            ($obtained/$maxTotal)*100,
            2
        )
        :
        0;





        return [


            'questions'=>$rows,


            'percentage'=>$percentage,


            'grade'=>$this->grade(
                $percentage
            ),



            'summary'=>[



                'strengths'=>
                array_slice(
                    array_unique($strengths),
                    0,
                    5
                )
                ?:
                [
                    'Responses detected successfully.'
                ],



                'weaknesses'=>
                array_slice(
                    array_unique($weak),
                    0,
                    5
                )
                ?:
                [
                    'No major weaknesses detected.'
                ],



                'notice'=>
                trim($msText)
                ?
                'Official mark scheme used.'
                :
                'No official mark scheme uploaded. AI marking is provisional.',



                'document_reader'=>
                $docs['source']
                ??
                'unknown',



                'insert_used'=>
                !empty($docs['insert_text']),



                'engine'=>
                'ExamMarker Adaptive AI V7'


            ]


        ];



    }

        private function score(
        string $answer,
        string $scheme,
        float $max,
        string $question
    ):array
    {


        $words=$this->tokens($answer);



        if(!$words)
        {

            return [

                'marks'=>0,

                'ratio'=>0,

                'confidence'=>'low',

                'feedback'=>
                'No readable answer detected.',


                'criteria'=>[

                    [
                        'label'=>'Answer detected',
                        'awarded'=>false
                    ]

                ]

            ];

        }




        /*
        |--------------------------------------------------------------------------
        | Use Mark Scheme if available
        |--------------------------------------------------------------------------
        */


        $reference =
        trim($scheme)
        ?
        $scheme
        :
        $question;




        $keyTokens =
        $this->importantTokens(
            $reference
        );



        $answerTokens =
        $this->expandTokens(
            $words
        );





        $hits=count(
            array_intersect(
                $keyTokens,
                $answerTokens
            )
        );





        $coverage =
        $keyTokens
        ?
        min(
            1,
            $hits/count($keyTokens)
        )
        :
        0.5;





        $completeness =
        min(
            1,
            count($words)/20
        );





        $ratio =
        ($coverage*0.7)
        +
        ($completeness*0.3);






        $marks =
        round(
            ($max*$ratio)*2
        )
        /
        2;



        $marks =
        min(
            $max,
            max(0,$marks)
        );





        $confidence =
        (
            $coverage>=0.7
            &&
            count($words)>=8
        )
        ?
        'high'
        :
        (
            $coverage>=0.4
            ?
            'medium'
            :
            'low'
        );







        return [


            'marks'=>$marks,


            'ratio'=>
            $max
            ?
            $marks/$max
            :
            0,



            'confidence'=>$confidence,



            'feedback'=>

            $ratio>=0.8

            ?

            'Strong answer with expected points detected.'

            :

            (

                $ratio>=0.5

                ?

                'Partially correct answer. Some points missing.'

                :

                'Limited evidence found. Teacher review required.'

            ),





            'criteria'=>[


                [

                    'label'=>'Relevant concepts',

                    'awarded'=>
                    $coverage>=0.4

                ],



                [

                    'label'=>'Complete explanation',

                    'awarded'=>
                    $completeness>=0.5

                ],



                [

                    'label'=>'Mark scheme alignment',

                    'awarded'=>
                    $coverage>=0.7

                ]


            ]



        ];

    }








    /*
    |--------------------------------------------------------------------------
    | Question Parser
    |--------------------------------------------------------------------------
    */

    private function parseQuestions(
        string $text,
        float $totalMarks
    ):array
    {


        $text=str_replace(
            "\r",
            "",
            trim($text)
        );



        $questions=[];



        preg_match_all(
            '/^\s*(\d+)\s*(?:\(([a-z])\))?\s+(.+)$/mi',
            $text,
            $matches,
            PREG_SET_ORDER
        );





        foreach($matches as $row)
        {


            $label=$row[1];


            if(!empty($row[2]))
            {
                $label.=$row[2];
            }





            $questions[]=[

                'label'=>$label,

                'text'=>trim($row[3]),

                'marks'=>null

            ];

        }







        /*
        Fallback if question detection fails
        */


        if(!$questions)
        {


            $count=max(
                1,
                ceil($totalMarks/5)
            );



            for($i=1;$i<=$count;$i++)
            {


                $questions[]=[

                    'label'=>(string)$i,

                    'text'=>"Question ".$i,

                    'marks'=>
                    $totalMarks/$count

                ];


            }


        }








        foreach($questions as &$q)
        {


            if(!$q['marks'])
            {

                $q['marks']=
                $totalMarks/count($questions);

            }


        }



        return $questions;


    }








    /*
    |--------------------------------------------------------------------------
    | Split Answer / Mark Scheme
    |--------------------------------------------------------------------------
    */

    private function splitByQuestions(
        string $text,
        array $labels
    ):array
    {


        $result=array_fill_keys(
            $labels,
            ''
        );



        foreach($labels as $label)
        {


            preg_match(

                '/'.$label.'\s*(.*?)(?=\n\d+[a-z]?\s|\z)/is',

                $text,

                $match

            );



            if(isset($match[1]))
            {

                $result[$label]=trim(
                    $match[1]
                );

            }


        }



        return $result;


    }








    private function tokens(
        string $text
    ):array
    {


        preg_match_all(
            '/[\pL\pN]+/u',
            Str::lower($text),
            $matches
        );



        return array_values(
            array_filter(
                $matches[0],
                fn($x)=>strlen($x)>2
            )
        );


    }








    private function importantTokens(
        string $text
    ):array
    {


        $stop=[

            'the',
            'and',
            'for',
            'with',
            'that',
            'this',
            'marks',
            'answer',
            'describe',
            'explain',
            'state'

        ];



        return array_values(
            array_filter(
                $this->tokens($text),
                fn($w)=>
                !in_array($w,$stop)
            )
        );


    }








    private function expandTokens(
        array $tokens
    ):array
    {


        $map=[


            'force'=>[
                'force',
                'push',
                'pull',
                'newton'
            ],


            'energy'=>[
                'energy',
                'power',
                'work',
                'joule'
            ],


            'temperature'=>[
                'temperature',
                'heat',
                'thermal',
                'kinetic'
            ],


            'acceleration'=>[
                'acceleration',
                'speed',
                'velocity',
                'rate'
            ],


            'reaction'=>[
                'reaction',
                'particle',
                'collision',
                'rate'
            ],


        ];




        $output=$tokens;



        foreach($tokens as $token)
        {


            if(isset($map[$token]))
            {

                $output=array_merge(
                    $output,
                    $map[$token]
                );

            }


        }



        return array_unique($output);


    }








    private function inferTopic(
        string $text,
        string $subject
    ):string
    {


        $text=Str::lower($text);



        $topics=[

            'force',
            'energy',
            'electricity',
            'waves',
            'mechanics',
            'biology',
            'chemistry',
            'calculus',
            'algebra',
            'statistics'

        ];




        foreach($topics as $topic)
        {


            if(str_contains($text,$topic))
            {

                return Str::title($topic);

            }


        }



        return $subject;


    }








    private function grade(
        float $percentage
    ):string
    {


        return match(true)

        {


            $percentage>=90=>'9',

            $percentage>=80=>'8',

            $percentage>=70=>'7',

            $percentage>=60=>'6',

            $percentage>=50=>'5',

            $percentage>=40=>'4',

            default=>'U'


        };


    }



}