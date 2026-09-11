<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | openai
    | gemini
    | claude
    | azure
    | openrouter
    | ollama
    |
    */

    'provider'=>env(
        'AI_PROVIDER',
        'openai'
    ),


    'models'=>[


        'openai'=>[
            'model'=>'gpt-5-mini',
            'key'=>env('OPENAI_API_KEY'),
            'url'=>'https://api.openai.com/v1/chat/completions'
        ],


        'gemini'=>[
            'model'=>'gemini-2.5-flash',
            'key'=>env('GEMINI_API_KEY'),
            'url'=>'https://generativelanguage.googleapis.com/v1beta/models'
        ],


        'claude'=>[
            'model'=>'claude-3-5-sonnet',
            'key'=>env('CLAUDE_API_KEY'),
            'url'=>'https://api.anthropic.com/v1/messages'
        ],


        'azure'=>[
            'model'=>env('AZURE_MODEL'),
            'key'=>env('AZURE_KEY'),
            'url'=>env('AZURE_URL')
        ],


        'openrouter'=>[
            'model'=>'meta-llama/llama-3.1-8b-instruct',
            'key'=>env('OPENROUTER_KEY'),
            'url'=>'https://openrouter.ai/api/v1/chat/completions'
        ],


        'ollama'=>[
            'model'=>'llama3',
            'url'=>'http://localhost:11434/api/chat'
        ]

    ]

];