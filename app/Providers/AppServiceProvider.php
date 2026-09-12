<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;


use App\Models\SystemSetting;
use App\Models\AiSetting;


use App\Services\PaperMarker;
use App\Services\AdaptivePaperMarker;
use App\Services\AIPaperMarker;


use App\Contracts\DocumentReader;


use App\Services\Ocr\DemoDocumentReader;
use App\Services\Ocr\ProductionDocumentReader;
use App\Services\Ocr\LocalDocumentReader;



class AppServiceProvider extends ServiceProvider
{


    public function register(): void
    {


        /*
        |--------------------------------------------------------------------------
        | Document Reader / OCR Engine
        |--------------------------------------------------------------------------
        */


        $this->app->bind(
            DocumentReader::class,
            function(){

                $mode = config(
                    'exam-marker.ocr',
                    'local'
                );


                return match($mode)
                {

                    'production'
                        => new ProductionDocumentReader(),


                    'demo'
                        => new DemoDocumentReader(),


                    default
                        => new LocalDocumentReader(),

                };


            }
        );







        /*
        |--------------------------------------------------------------------------
        | Paper Marker Engine
        |--------------------------------------------------------------------------
        |
        | Active AI setting  -> AIPaperMarker
        | No AI setting      -> AdaptivePaperMarker
        | Disabled AI        -> AdaptivePaperMarker
        |
        |--------------------------------------------------------------------------
        */


        $this->app->bind(
            PaperMarker::class,
            function(){


                $setting = AiSetting::first();



                /*
                |--------------------------------------------------------------------------
                | No AI configuration
                |--------------------------------------------------------------------------
                */


                if(!$setting)
                {

                    return app(
                        AdaptivePaperMarker::class
                    );

                }






                /*
                |--------------------------------------------------------------------------
                | AI disabled
                |--------------------------------------------------------------------------
                */


                if($setting->status !== 'active')
                {

                    return app(
                        AdaptivePaperMarker::class
                    );

                }







                /*
                |--------------------------------------------------------------------------
                | Active AI Provider
                |--------------------------------------------------------------------------
                */


                return match($setting->provider)
                {


                    'openai',
                    'gemini',
                    'claude',
                    'azure',
                    'openrouter',
                    'ollama'
                        =>
                        app(
                            AIPaperMarker::class
                        ),




                    default
                        =>
                        app(
                            AdaptivePaperMarker::class
                        ),


                };


            }
        );


    }










    public function boot(): void
    {


        View::composer(
            '*',
            function($view){


                $settings =
                    SystemSetting::pluck(
                        'value',
                        'key'
                    );


                $view->with(
                    'systemSettings',
                    $settings
                );


            }
        );


    }


}