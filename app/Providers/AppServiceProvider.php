<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;


use App\Models\SystemSetting;
use App\Models\AiSetting;


use App\Services\{
    PaperMarker,
    AdaptivePaperMarker,
    AIPaperMarker
};


use App\Contracts\DocumentReader;


use App\Services\Ocr\{
    DemoDocumentReader,
    ProductionDocumentReader,
    LocalDocumentReader
};



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

                $mode=config(
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
        | AI Paper Marker Provider
        |--------------------------------------------------------------------------
        |
        | Admin can change provider from:
        |
        | Platform Admin
        | → AI Provider Settings
        |
        */


        $this->app->bind(
            PaperMarker::class,
            function(){



                $setting = AiSetting::first();



                /*
                |--------------------------------------------------------------------------
                | Default
                |--------------------------------------------------------------------------
                */

                if(!$setting)
                {
                    return app(AdaptivePaperMarker::class);
                }





                /*
                |--------------------------------------------------------------------------
                | AI Disabled
                |--------------------------------------------------------------------------
                */

                if($setting->status !== 'active')
                {
                    return app(AdaptivePaperMarker::class);
                }






                return match($setting->provider)
                {


                    'openai',
                    'gemini',
                    'claude',
                    'azure',
                    'openrouter'
                        =>
                        app(AIPaperMarker::class),




                    'ollama'
                        =>
                        app(AIPaperMarker::class),




                    default
                        =>
                        app(AdaptivePaperMarker::class),



                };



            }
        );



    }







    public function boot(): void
    {



        /*
        |--------------------------------------------------------------------------
        | Global Website Settings
        |--------------------------------------------------------------------------
        */


        View::composer('*',function($view){


            $settings =
            SystemSetting::pluck('value','key');



            $view->with(
                'systemSettings',
                $settings
            );


        });



    }


}