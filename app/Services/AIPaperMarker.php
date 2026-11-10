<?php

namespace App\Services;


use App\Models\Assessment;
use App\Contracts\DocumentReader;
use Illuminate\Support\Facades\Http;


class AIPaperMarker implements PaperMarker
{


    public function __construct(
        private DocumentReader $reader,
        private WritingRubricService $writingRubric
    )
    {
    }





    public function mark(
        Assessment $assessment
    ): array
    {


        $docs = $this->reader->read($assessment);



        $prompt = $this->buildPrompt(
            $assessment,
            $docs
        );



        $response = $this->callAI(
            $prompt
        );



        return $this->parseResponse(
            $response
        );


    }







    private function buildPrompt(
        Assessment $assessment,
        array $docs
    ): string
    {


        $writingCriteria = json_encode(
            $this->writingRubric->criteria(),
            JSON_PRETTY_PRINT
        );



        return <<<PROMPT


You are an expert Cambridge IGCSE / IB examiner.



Subject:

{$assessment->subject}



Total Marks:

{$assessment->total_marks}



EXAMINER RULES:


1. Use ONLY the supplied mark scheme.

2. Do NOT divide marks equally.

3. Use exact marks from the mark scheme.

4. Mark every sub-question separately.

5. Provide examiner-quality feedback.

6. For writing questions use the supplied rubric.

7. Return ONLY valid JSON.



QUESTION PAPER:


{$docs['qp_text']}



MARK SCHEME:


{$docs['ms_text']}




WRITING RUBRIC:


{$writingCriteria}




STUDENT ANSWER:


{$docs['wa_text']}




RETURN JSON FORMAT:



{
"questions":[

{

"question_number":"1",

"question_part":"a",

"parent_question_number":"1",

"type":"normal",

"max_marks":2,

"ai_marks":1,

"confidence":"high",

"feedback":"Student identified the correct concept but explanation is incomplete.",

"criteria":[]

},


{

"question_number":"11",

"question_part":null,

"parent_question_number":"11",

"type":"writing",

"max_marks":25,

"ai_marks":20,


"writing_rubric":{


"content":{

"awarded":4,

"max_marks":5,

"feedback":"Ideas are relevant but need further development."

},


"organisation":{

"awarded":4,

"max_marks":5,

"feedback":"Clear paragraph structure and logical sequence."

},


"vocabulary":{

"awarded":4,

"max_marks":5,

"feedback":"Good vocabulary range with minor limitations."

},


"grammar":{

"awarded":3,

"max_marks":5,

"feedback":"Some grammatical errors affect accuracy."

},


"spelling":{

"awarded":5,

"max_marks":5,

"feedback":"Spelling and punctuation are accurate."

}


}


}

],



"percentage":80,


"grade":"7",



"summary":{

"strengths":[

""

],


"weaknesses":[

""

]

}


}



PROMPT;


    }









    private function callAI(
        string $prompt
    ): string
    {


        $provider = config(
            'ai.provider'
        );



        $config = config(
            "ai.models.$provider"
        );




        switch($provider)
        {


            case 'openai':

                return $this->openAI(
                    $config,
                    $prompt
                );



            case 'gemini':

                return $this->gemini(
                    $config,
                    $prompt
                );



            case 'claude':

                return $this->claude(
                    $config,
                    $prompt
                );



            case 'openrouter':

                return $this->openAI(
                    $config,
                    $prompt
                );



            case 'ollama':

                return $this->ollama(
                    $config,
                    $prompt
                );



            default:

                throw new \Exception(
                    "AI provider not supported"
                );


        }


    }









    private function openAI(
        array $config,
        string $prompt
    ): string
    {


        $r = Http::withToken(
            $config['key']
        )
        ->post(
            $config['url'],
            [

                'model'=>$config['model'],

                'messages'=>[

                    [
                        'role'=>'user',
                        'content'=>$prompt
                    ]

                ]

            ]
        );



        return $r->json(
            'choices.0.message.content'
        );


    }









    private function gemini(
        array $config,
        string $prompt
    ): string
    {


        $url =
            $config['url']
            .'/'
            .$config['model']
            .':generateContent?key='
            .$config['key'];



        $r = Http::post(
            $url,
            [

                'contents'=>[

                    [

                        'parts'=>[

                            [
                                'text'=>$prompt
                            ]

                        ]

                    ]

                ]

            ]
        );



        return $r->json(
            'candidates.0.content.parts.0.text'
        );


    }









    private function claude(
        array $config,
        string $prompt
    ): string
    {


        $r = Http::withHeaders([

            'x-api-key'=>$config['key'],

            'anthropic-version'=>'2023-06-01'


        ])
        ->post(
            $config['url'],
            [

                'model'=>$config['model'],

                'max_tokens'=>4000,


                'messages'=>[

                    [

                        'role'=>'user',

                        'content'=>$prompt

                    ]

                ]


            ]
        );



        return $r->json(
            'content.0.text'
        );


    }









    private function ollama(
        array $config,
        string $prompt
    ): string
    {


        $r = Http::post(
            $config['url'],
            [

                'model'=>$config['model'],


                'messages'=>[

                    [

                        'role'=>'user',

                        'content'=>$prompt

                    ]

                ],


                'stream'=>false


            ]
        );



        return $r->json(
            'message.content'
        );


    }









    private function parseResponse(
        string $json
    ): array
    {


        $json = str_replace(
            [
                '```json',
                '```'
            ],
            '',
            $json
        );



        $json = trim($json);



        $data = json_decode(
            $json,
            true
        );



        if(json_last_error() !== JSON_ERROR_NONE)
        {

            throw new \Exception(
                'Invalid AI JSON response: '
                .json_last_error_msg()
            );

        }



        return $data;


    }



}