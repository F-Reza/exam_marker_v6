<?php

return [
    'currency' => env('EXAM_MARKER_CURRENCY','INR'),

    'async'=>
        (bool) env(
            'EXAM_MARKER_ASYNC',
            false
        ),


    'ocr'=>
        env(
            'EXAM_MARKER_OCR',
            'local'
        ),


    'ai'=>
        env(
            'EXAM_MARKER_AI',
            'demo'
        ),



    'parent_link_days'=>
        (int) env(
            'EXAM_MARKER_PARENT_LINK_DAYS',
            30
        ),



    'demo_sms'=>
        (bool) env(
            'EXAM_MARKER_DEMO_SMS',
            true
        ),



    /*
    OCR Settings
    */


    'word_tools' => [
        'antiword' => env('ANTIWORD_PATH', 'antiword'),
        'libreoffice' => env('LIBREOFFICE_PATH', 'soffice'),
    ],

    'ocr_tools'=>[
        'pdftoppm' => env('PDFTOPPM_PATH', 'pdftoppm'),


        'pdftotext'=>
            env(
                'PDFTOTEXT_PATH',
                'pdftotext'
            ),


        'tesseract'=>
            env(
                'TESSERACT_PATH',
                'tesseract'
            ),


    ],


];
