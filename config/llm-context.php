<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | The default path where the LLM context files will be saved.
    |
    */
    'output_path' => storage_path('app/llm-context'),

    /*
    |--------------------------------------------------------------------------
    | Include in Context
    |--------------------------------------------------------------------------
    |
    | Specify which elements should be included in the context generation.
    |
    */
    'include' => [
        'migrations' => true,
        'models' => true,
        'relationships' => true,
        'routes' => false,  // Future feature
        'controllers' => false,  // Future feature
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Path
    |--------------------------------------------------------------------------
    |
    | The path where your models are located.
    |
    */
    'model_path' => app_path('Models'),
];
