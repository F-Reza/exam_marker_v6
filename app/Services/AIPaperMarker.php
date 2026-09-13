<?php

namespace App\Services;


use App\Models\Assessment;
use App\Models\AiSetting;
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


        $rubric = json_encode(
            $this->writingRubric->criteria(),
            JSON_PRETTY_PRINT
        );



        return <<<PROMPT


You are an expert Cambridge IGCSE and IB examiner.


Subject:
{$assessment->subject}


Total Marks:
{$assessment->total_marks}



STRICT RULES:

1. Use only the supplied mark scheme.

2. Award exact marks.

3. Mark every question separately.

4. Mark every sub-question separately.

5. Give examiner feedback.

6. For essay/writing questions use the writing rubric.

7. Return ONLY JSON.



QUESTION PAPER:

{$docs['qp_text']}



MARK SCHEME:

{$docs['ms_text']}



WRITING RUBRIC:

$rubric



STUDENT ANSWER:

{$docs['wa_text']}



JSON FORMAT:

{
"questions":[
{
"question_number":"1",
"question_part":"a",
"parent_question_number":"1",
"max_marks":5,
"ai_marks":4,
"confidence":"high",
"feedback":"Good explanation but missing final justification.",
"criteria":[]
}
],

"percentage":80,

"grade":"7",

"summary":{
"strengths":[""],
"weaknesses":[""]
}

}



PROMPT;


    }









    private function callAI(
        string $prompt
    ): string
    {


        $setting = AiSetting::first();



        if(!$setting)
        {
            throw new \Exception(
                "AI Provider not configured."
            );
        }



        if($setting->status !== 'active')
        {
            throw new \Exception(
                "AI Provider is disabled."
            );
        }




        $config=[

            'key'=>$setting->api_key,

            'model'=>$setting->model,

            'url'=>match($setting->provider)
            {

                'openai'
                =>
                'https://api.openai.com/v1/chat/completions',


                default
                =>
                config(
                    'ai.models.'.$setting->provider.'.url'
                )

            }

        ];





        return match($setting->provider)
        {


            'openai',
            'openrouter'
            =>
            $this->openAI(
                $config,
                $prompt
            ),



            'gemini'
            =>
            $this->gemini(
                $config,
                $prompt
            ),



            'claude'
            =>
            $this->claude(
                $config,
                $prompt
            ),



            'ollama'
            =>
            $this->ollama(
                $config,
                $prompt
            ),



            default
            =>
            throw new \Exception(
                "Unsupported AI provider."
            )


        };


    }









    private function openAI(
        array $config,
        string $prompt
    ): string
    {


        $response = Http::withToken(
            $config['key']
        )
        ->timeout(120)
        ->post(

            $config['url'],

            [

                'model'=>$config['model'],


                'temperature'=>0.2,


                'response_format'=>[
                    'type'=>'json_object'
                ],



                'messages'=>[


                    [
                        'role'=>'system',
                        'content'=>
                        'You are an expert examiner. Return only JSON.'
                    ],


                    [
                        'role'=>'user',
                        'content'=>$prompt
                    ]

                ]

            ]

        );





        if(!$response->successful())
        {

            throw new \Exception(

                "OpenAI Error: ".
                $response->body()

            );

        }




        $data=$response->json();



        $content =
            $data['choices'][0]['message']['content']
            ??
            null;




        if(!$content)
        {

            throw new \Exception(

                "Invalid OpenAI response: ".
                json_encode($data)

            );

        }



        return $content;


    }









    private function gemini(
        array $config,
        string $prompt
    ): string
    {


        $url =
        $config['url']
        .'/'.
        $config['model']
        .
        ':generateContent?key='
        .
        $config['key'];



        $response =
        Http::post(

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



        $text =
        $response->json(
            'candidates.0.content.parts.0.text'
        );



        if(!$text)
        {
            throw new \Exception(
                "Gemini invalid response ".$response->body()
            );
        }



        return $text;


    }









    private function claude(
        array $config,
        string $prompt
    ): string
    {


        $response =
        Http::withHeaders([

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



        $text =
        $response->json(
            'content.0.text'
        );



        if(!$text)
        {
            throw new \Exception(
                "Claude invalid response ".$response->body()
            );
        }



        return $text;


    }









    private function ollama(
        array $config,
        string $prompt
    ): string
    {


        $response =
        Http::post(

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



        $text =
        $response->json(
            'message.content'
        );



        if(!$text)
        {
            throw new \Exception(
                "Ollama invalid response ".$response->body()
            );
        }



        return $text;


    }









    private function parseResponse(
        string $json
    ): array
    {


        $json =
        str_replace(

            [
                '```json',
                '```'
            ],

            '',

            $json

        );



        $data =
        json_decode(

            trim($json),

            true

        );




        if(json_last_error() !== JSON_ERROR_NONE)
        {

            throw new \Exception(

                "AI JSON Error: ".
                json_last_error_msg()

            );

        }



        if(!isset($data['questions']))
        {

            throw new \Exception(
                "AI response missing questions."
            );

        }



        return $data;


    }


}