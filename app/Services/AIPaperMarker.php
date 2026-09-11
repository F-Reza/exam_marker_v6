<?php

namespace App\Services;


use App\Models\Assessment;
use App\Contracts\DocumentReader;
use Illuminate\Support\Facades\Http;



class AIPaperMarker implements PaperMarker
{


public function __construct(
    private DocumentReader $reader
)
{}




public function mark(
    Assessment $assessment
):array
{


$docs=$this->reader->read($assessment);



$prompt=$this->buildPrompt(
    $assessment,
    $docs
);



$response=$this->callAI(
    $prompt
);



return $this->parseResponse(
    $response
);


}






private function buildPrompt(
Assessment $assessment,
array $docs
):string
{


return <<<PROMPT


You are an expert IB/IGCSE examiner.


Subject:
{$assessment->subject}


Total Marks:
{$assessment->total_marks}



Question Paper:

{$docs['qp_text']}



Mark Scheme:

{$docs['ms_text']}



Student Answer:

{$docs['wa_text']}



Mark each question.


Return ONLY JSON:


{
"questions":[
{
"question_number":"1",
"max_marks":5,
"marks":4,
"confidence":"high",
"feedback":"..."
}
],

"percentage":80,

"grade":"7",

"summary":{
"strengths":[],
"weaknesses":[]
}

}



PROMPT;


}






private function callAI(
string $prompt
):string
{


$provider=config(
'ai.provider'
);


$config=config(
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
):string
{


$r=Http::withToken(
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



return $r
->json(
'choices.0.message.content'
);


}







private function gemini(
array $config,
string $prompt
):string
{


$url=$config['url']
.'/'
.$config['model']
.':generateContent?key='
.$config['key'];



$r=Http::post(
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
):string
{


$r=Http::withHeaders([

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
):string
{


$r=Http::post(
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
):array
{


$json=str_replace(
['```json','```'],
'',
$json
);



return json_decode(
trim($json),
true
);


}



}