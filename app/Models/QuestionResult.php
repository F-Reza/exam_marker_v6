<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class QuestionResult extends Model
{

    use HasFactory;


    protected $table = 'question_results';


    protected $fillable = [

        'assessment_id',

        'question_number',

        'question_part',
        'parent_question_number',

        'topic',

        'max_marks',

        'ai_marks',

        'teacher_marks',

        'confidence',

        'feedback',

        'teacher_comment',

        'criteria',

        'status',

        'recheck_count',

        'last_rechecked_at'

    ];



    protected function casts(): array
    {

        return [

            'criteria'=>'array',

            'last_rechecked_at'=>'datetime',

            'max_marks'=>'decimal:2',

            'ai_marks'=>'decimal:2',

            'teacher_marks'=>'decimal:2',
            'question_part'=>'string',

        ];

    }



    public function assessment()
    {

        return $this->belongsTo(
            Assessment::class
        );

    }



    public function finalMarks(): float
    {

        return (float)(
            $this->teacher_marks
            ??
            $this->ai_marks
            ??
            0
        );

    }


}