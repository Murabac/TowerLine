<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    /*
    | Do not wrap this in realpath(). If the folder is missing (fresh unzip on
    | Bluehost), realpath() returns false and Blade throws "Please provide a
    | valid cache path."
    */
    'compiled' => env('VIEW_COMPILED_PATH', storage_path('framework/views')),

];
