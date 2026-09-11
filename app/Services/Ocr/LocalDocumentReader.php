<?php

namespace App\Services\Ocr;


use App\Contracts\DocumentReader;
use App\Models\Assessment;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use Smalot\PdfParser\Parser;



class LocalDocumentReader implements DocumentReader
{


    public function read(Assessment $assessment): array
    {


        $assessment->loadMissing('attachments');


        $insertText=[];



        foreach(
            $assessment->attachments
            ->where('type','insert')
            as $attachment
        )
        {

            $text=$this->extract(
                $attachment->path
            );


            if($text)
            {
                $insertText[]=$text;
            }

        }





        return [


            'qp_text'=>
                $this->extract(
                    $assessment->question_paper_path
                ),



            'ms_text'=>
                $assessment->mark_scheme_path
                ?
                $this->extract(
                    $assessment->mark_scheme_path
                )
                :
                null,



            'wa_text'=>
                $this->extract(
                    $assessment->written_answer_path
                ),



            'insert_text'=>
                trim(
                    implode(
                        "\n\n",
                        $insertText
                    )
                ),



            'source'=>
                'local-document-reader'


        ];


    }









    private function extract(?string $path): string
    {


        if(!$path)
        {
            return '';
        }




        if(!Storage::exists($path))
        {

            Log::warning(
                'Document not found',
                [
                    'path'=>$path
                ]
            );


            return '';

        }





        $absolute =
            Storage::path($path);





        $extension =
            strtolower(
                pathinfo(
                    $absolute,
                    PATHINFO_EXTENSION
                )
            );






        try
        {


            return match($extension)
            {


                'pdf'=>
                    $this->extractPdf(
                        $absolute
                    ),



                'docx'=>
                    $this->extractDocx(
                        $absolute
                    ),



                'doc'=>
                    $this->extractDoc(
                        $absolute
                    ),



                default=>
                    trim(
                        Storage::get($path)
                    )

            };


        }
        catch(\Throwable $e)
        {


            Log::error(
                'Document extraction error',
                [

                    'file'=>$absolute,

                    'message'=>$e->getMessage()

                ]
            );


            return '';

        }



    }









    /*
    |--------------------------------------------------------------------------
    | PDF Extraction
    |--------------------------------------------------------------------------
    */

    private function extractPdf(string $file): string
    {


        /*
        First try PHP PDF Parser
        */


        try
        {


            $parser =
                new Parser();



            $pdf =
                $parser->parseFile(
                    $file
                );



            $text =
                trim(
                    $pdf->getText()
                );



            if($text !== '')
            {

                return $text;

            }


        }
        catch(\Throwable $e)
        {


            Log::warning(
                'PDF parser failed',
                [
                    'error'=>$e->getMessage()
                ]
            );


        }





        /*
        Scanned PDF OCR fallback
        */


        return $this->ocrPdf(
            $file
        );


    }









    /*
    |--------------------------------------------------------------------------
    | OCR fallback
    |--------------------------------------------------------------------------
    */

    private function ocrPdf(string $file): string
    {


        /*
        Enable on server with:

        sudo apt install tesseract-ocr
        sudo apt install poppler-utils

        */


        try
        {


            $directory =
                dirname($file)
                .'/ocr_'
                .time();



            $command =
                'pdftoppm -png '
                .
                escapeshellarg($file)
                .
                ' '
                .
                escapeshellarg($directory);



            shell_exec($command);




            $images =
                glob(
                    $directory.'*.png'
                );




            $text='';



            foreach($images as $image)
            {


                $text .= "\n".
                    shell_exec(
                        'tesseract '
                        .
                        escapeshellarg($image)
                        .
                        ' stdout'
                    );



                unlink($image);


            }




            return trim($text);



        }
        catch(\Throwable $e)
        {


            Log::warning(
                'OCR unavailable',
                [
                    'error'=>$e->getMessage()
                ]
            );


            return '';

        }


    }









    /*
    |--------------------------------------------------------------------------
    | DOC extraction
    |--------------------------------------------------------------------------
    */

    private function extractDoc(string $file): string
    {


        return trim(
            $this->command(
                [
                    'antiword',
                    $file
                ]
            )
        );


    }









    /*
    |--------------------------------------------------------------------------
    | DOCX extraction
    |--------------------------------------------------------------------------
    */

    private function extractDocx(string $file): string
    {


        if(class_exists(\ZipArchive::class))
        {


            $zip =
                new \ZipArchive();



            if(
                $zip->open($file)
                ===
                true
            )
            {


                $xml =
                    $zip->getFromName(
                        'word/document.xml'
                    )
                    ??
                    '';



                $zip->close();



                return $this->xmlToText(
                    $xml
                );


            }


        }



        return '';

    }









    private function command(array $args): string
    {


        $cmd =
            implode(
                ' ',
                array_map(
                    'escapeshellarg',
                    $args
                )
            )
            .
            ' 2>/dev/null';



        return trim(
            (string)
            shell_exec($cmd)
        );


    }









    private function xmlToText(string $xml): string
    {


        if(!$xml)
        {
            return '';
        }



        $xml =
            str_replace(
                [
                    '</w:p>',
                    '</w:tr>',
                    '<w:tab/>'
                ],
                [
                    "\n",
                    "\n",
                    "\t"
                ],
                $xml
            );




        return trim(
            html_entity_decode(
                strip_tags($xml),
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            )
        );


    }



}