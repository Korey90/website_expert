<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ziggy route exclusions
    |--------------------------------------------------------------------------
    |
    | The default `@routes` directive (used in resources/views/app.blade.php)
    | dumps every named route into the HTML of every Inertia page, including
    | public marketing/landing pages. Filament's own panel never renders that
    | blade view/Ziggy payload, so excluding its route names here only shrinks
    | the public payload — it does not affect Filament's own navigation.
    |
    */
    'except' => [
        'filament.*',
        'admin.*',
    ],

];
