<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Assessment extends Model
{


    protected $fillable = [

        'user_id',

        'student_id',

        'title',

        'subject',

        'grade',

        'exam_board',

        'assessment_date',

        'total_marks',

        'status',

        'question_paper_path',

        'mark_scheme_path',

        'written_answer_path',

        'ai_summary',

        'percentage',

        'grade_awarded',

        'public_report_token',

        'public_report_expires_at',

        'finalised_at',

        'assigned_teacher_id',

        'processing_started_at',

        'processing_completed_at',

        'error_message',

        'report_version'

    ];





    protected function casts(): array
    {


        return [

            'assessment_date'=>'date',


            'ai_summary'=>'array',


            'percentage'=>'decimal:2',


            'total_marks'=>'decimal:2',


            'public_report_expires_at'=>'datetime',


            'finalised_at'=>'datetime',


            'processing_started_at'=>'datetime',


            'processing_completed_at'=>'datetime',


            'report_version'=>'integer'

        ];

    }





    /*
    |--------------------------------------------------------------------------
    | Owner User
    |--------------------------------------------------------------------------
    */

    public function user()
    {

        return $this->belongsTo(
            User::class
        );

    }




    /*
    |--------------------------------------------------------------------------
    | Student
    |--------------------------------------------------------------------------
    */

    public function student()
    {

        return $this->belongsTo(
            Student::class
        );

    }





    /*
    |--------------------------------------------------------------------------
    | Assigned Teacher
    |--------------------------------------------------------------------------
    */

    public function assignedTeacher()
    {

        return $this->belongsTo(
            User::class,
            'assigned_teacher_id'
        );

    }





    /*
    |--------------------------------------------------------------------------
    | Question Results
    |--------------------------------------------------------------------------
    */

    public function results()
    {

        return $this->hasMany(
            QuestionResult::class
        );

    }





    /*
    |--------------------------------------------------------------------------
    | Parent Communication
    |--------------------------------------------------------------------------
    */

    public function communications()
    {

        return $this->hasMany(
            ParentCommunication::class
        );

    }





    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    */

    public function attachments()
    {

        return $this->hasMany(
            AssessmentAttachment::class
        );

    }





    /*
    |--------------------------------------------------------------------------
    | Insert Attachments
    |--------------------------------------------------------------------------
    */

    public function inserts()
    {

        return $this->hasMany(
            AssessmentAttachment::class
        )
        ->where(
            'type',
            'insert'
        );

    }





    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    public function isCompleted(): bool
    {

        return $this->status === 'completed';

    }



    public function hasResults(): bool
    {

        return $this->results()->exists();

    }


}