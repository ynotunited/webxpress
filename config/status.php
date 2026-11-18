<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Status site
    |--------------------------------------------------------------------------
    |
    | The static status page in status/ is deployed separately (Cloudflare
    | Pages, own domain) so it keeps answering when this application stops
    | answering. This config simply points the status:publish command at the
    | data files that page renders, so that command (and nothing else) is the
    | way maintenance windows, incidents and monitor states get written.
    |
    | STATUS_PATH can point elsewhere if the status site ever moves repos —
    | but keep in mind the point of the arrangement is that the status page and
    | this app do NOT share a web server.
    |
    */

    'path' => env('STATUS_PATH', base_path('status/data')),

];