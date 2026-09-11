<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;

use App\Models\SystemSetting;

use App\Services\AuditService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;




class SystemConfigurationController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Configuration Page
    |--------------------------------------------------------------------------
    */


    public function index()
    {


        $settings = SystemSetting::pluck(
            'value',
            'key'
        );


        return view(
            'admin.config.index',
            compact('settings')
        );


    }





    /*
    |--------------------------------------------------------------------------
    | Update Configuration
    |--------------------------------------------------------------------------
    */


    public function update(
        Request $request,
        AuditService $audit
    )
    {


        $data = $request->validate([



            /*
            |--------------------------------------------------------------------------
            | Website
            |--------------------------------------------------------------------------
            */

            'app_name'
            =>
            'nullable|string|max:100',


            'site_title'
            =>
            'nullable|string|max:150',


            'meta_description'
            =>
            'nullable|string',


            'meta_keywords'
            =>
            'nullable|string',





            /*
            |--------------------------------------------------------------------------
            | AI
            |--------------------------------------------------------------------------
            */


            'ai_enabled'
            =>
            'nullable',


            'ai_confidence'
            =>
            'nullable|numeric|min:0|max:100',





            /*
            |--------------------------------------------------------------------------
            | Communication
            |--------------------------------------------------------------------------
            */


            'email_enabled'
            =>
            'nullable',


            'sms_enabled'
            =>
            'nullable',





            /*
            |--------------------------------------------------------------------------
            | Security
            |--------------------------------------------------------------------------
            */


            'maintenance_mode'
            =>
            'nullable',





            /*
            |--------------------------------------------------------------------------
            | Files
            |--------------------------------------------------------------------------
            */


            'site_logo'
            =>
            'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',


            'favicon'
            =>
            'nullable|image|mimes:png,jpg,jpeg,ico,svg|max:1024',


        ]);






        /*
        |--------------------------------------------------------------------------
        | SITE LOGO
        |--------------------------------------------------------------------------
        */


        if($request->hasFile('site_logo'))
        {


            // delete old logo

            $this->deleteFile(
                'site_logo'
            );



            $path =
            $request
            ->file('site_logo')
            ->store(
                'settings',
                'public'
            );



            SystemSetting::updateOrCreate(

                [
                    'key'=>'site_logo'
                ],

                [
                    'value'=>$path
                ]

            );


        }


        elseif(
            $request->boolean('remove_logo')
        )
        {


            $this->deleteFile(
                'site_logo'
            );



            SystemSetting::updateOrCreate(

                [
                    'key'=>'site_logo'
                ],

                [
                    'value'=>null
                ]

            );


        }







        /*
        |--------------------------------------------------------------------------
        | FAVICON
        |--------------------------------------------------------------------------
        */


        if($request->hasFile('favicon'))
        {


            // delete old favicon

            $this->deleteFile(
                'favicon'
            );



            $path =
            $request
            ->file('favicon')
            ->store(
                'settings',
                'public'
            );



            SystemSetting::updateOrCreate(

                [
                    'key'=>'favicon'
                ],

                [
                    'value'=>$path
                ]

            );


        }


        elseif(
            $request->boolean('remove_favicon')
        )
        {


            $this->deleteFile(
                'favicon'
            );



            SystemSetting::updateOrCreate(

                [
                    'key'=>'favicon'
                ],

                [
                    'value'=>null
                ]

            );


        }









        /*
        |--------------------------------------------------------------------------
        | Save Text Settings
        |--------------------------------------------------------------------------
        */


        unset(
            $data['site_logo'],
            $data['favicon']
        );





        foreach($data as $key=>$value)
        {


            SystemSetting::updateOrCreate(

                [
                    'key'=>$key
                ],

                [
                    'value'=>$value
                ]

            );


        }








        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */


        $audit->log(

            null,

            'SYSTEM_CONFIGURATION_UPDATED',

            $data

        );








        return back()

        ->with(

            'success',

            'System configuration updated successfully.'

        );


    }







    /*
    |--------------------------------------------------------------------------
    | Delete Old File
    |--------------------------------------------------------------------------
    */


    private function deleteFile(
        string $key
    )
    {


        $old =
        SystemSetting::where(
            'key',
            $key
        )
        ->value('value');




        if(
            $old &&
            Storage::disk('public')
            ->exists($old)
        )
        {


            Storage::disk('public')
            ->delete($old);


        }



    }




}