<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use Illuminate\Http\Request;


class AdminAISettingController extends Controller
{


public function index()
{

    $setting = AiSetting::first();


    if(!$setting)
    {

        $setting = AiSetting::create([

            'provider'=>'adaptive',

            'model'=>'adaptive-local-v1',

            'status'=>'active'

        ]);

    }



    return view(
        'admin.ai-settings',
        compact('setting')
    );


}





public function update(Request $request)
{


    $data = $request->validate([


        'provider'=>
        'required|in:adaptive,openai,gemini,claude,azure,openrouter,ollama',


        'model'=>
        'nullable|string|max:150',


        'api_key'=>
        'nullable|string',


        'status'=>
        'nullable|in:active,inactive'


    ]);




    $setting = AiSetting::first();



    if(!$setting)
    {

        $setting = new AiSetting();

    }




    /*
    |--------------------------------------------------------------------------
    | Keep old API key
    |--------------------------------------------------------------------------
    */

    if($setting->exists && $setting->provider!==$data['provider'] && empty($data['api_key'])) { $data['api_key']=null; }
    elseif(empty($data['api_key']))
    {

        unset($data['api_key']);

    }



    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    */

    $data['status'] = $data['status'] ?? 'active';



    if(empty($data['model']))
    {

        $data['model']=$data['provider']==='adaptive'?'adaptive-local-v1':config('ai.models.'.$data['provider'].'.model');

    }




    $setting->fill($data);

    $setting->save();




    return back()
    ->with(
        'success',
        'AI Provider Updated Successfully'
    );


}


}