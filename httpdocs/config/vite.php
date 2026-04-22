<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fix Laravel Vite Manifest Path for Plesk
    |--------------------------------------------------------------------------
    |
    | Laravel by default looks for "public/public/build/manifest.json" on
    | some server structures (like Plesk). We fix it here to the correct
    | location "public/build/manifest.json".
    |
    */

    'manifest' => base_path('public/build/manifest.json'),

    'hot' => base_path('public/hot'),

    'build_path' => 'public/build',

    'entrypoints' => [
        'resources/css/app.css',
        'resources/js/app.js',
    ],
];