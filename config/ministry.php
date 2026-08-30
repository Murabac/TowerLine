<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Build approval letter signatory
    |--------------------------------------------------------------------------
    |
    | Defaults used on first install. Ministry admins can change these in
    | Settings → Ministry settings. Env values apply only when no DB row exists.
    |
    */

    'approval_letter' => [
        'director_name' => env('MINISTRY_APPROVAL_DIRECTOR_NAME', ''),
        'director_title_so' => env('MINISTRY_APPROVAL_DIRECTOR_TITLE_SO', 'Agaasimaha Waaxda Isgaadhsiinta'),
        'director_title_en' => env('MINISTRY_APPROVAL_DIRECTOR_TITLE_EN', 'Director of Communication Department'),
    ],

];
