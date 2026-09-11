<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

public function up(): void
{

Schema::table('system_settings', function(Blueprint $table){

    $table->string('site_title')
        ->default('Exam Marker')
        ->after('id');

    $table->string('site_logo')
        ->nullable();

    $table->string('favicon')
        ->nullable();

    $table->string('meta_title')
        ->nullable();

    $table->text('meta_description')
        ->nullable();

    $table->text('meta_keywords')
        ->nullable();

    $table->string('language')
        ->default('en');

    $table->string('timezone')
        ->default('UTC');

});

}


public function down(): void
{

Schema::table('system_settings', function(Blueprint $table){

$table->dropColumn([
'site_title',
'site_logo',
'favicon',
'meta_title',
'meta_description',
'meta_keywords',
'language',
'timezone'
]);

});


}

};