<?php

namespace App\Services;


class WritingRubricService
{

    /**
     * Writing assessment criteria
     *
     * Used for:
     * - IB Language
     * - IGCSE English
     * - Essay responses
     * - Extended responses
     */
    public function criteria(): array
    {

        return [

            'content' => [
                'max_marks' => 5,
                'description' =>
                    'Quality, relevance and development of ideas.'
            ],


            'organisation' => [
                'max_marks' => 5,
                'description' =>
                    'Logical structure, paragraphing and coherence.'
            ],


            'vocabulary' => [
                'max_marks' => 5,
                'description' =>
                    'Range, accuracy and appropriateness of vocabulary.'
            ],


            'grammar' => [
                'max_marks' => 5,
                'description' =>
                    'Sentence structure, grammar accuracy and control.'
            ],


            'spelling' => [
                'max_marks' => 5,
                'description' =>
                    'Spelling accuracy and written conventions.'
            ]

        ];

    }



    /**
     * Total writing rubric marks
     */
    public function totalMarks(): int
    {

        return collect($this->criteria())
            ->sum('max_marks');

    }



    /**
     * Generate empty rubric structure
     * for AI response validation
     */
    public function emptyRubric(): array
    {

        $rubric=[];


        foreach($this->criteria() as $key=>$item)
        {

            $rubric[$key]=[

                'awarded'=>0,

                'max_marks'=>$item['max_marks'],

                'feedback'=>null

            ];

        }


        return $rubric;

    }



    /**
     * Calculate obtained writing marks
     */
    public function calculate(array $rubric): int
    {

        return collect($rubric)
            ->sum(function($item){

                return (int)($item['awarded'] ?? 0);

            });

    }



}