<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Date-Time Format
    |--------------------------------------------------------------------------
    |
    | The single, project-wide format used to render timestamps in every API
    | response (via the format_datetime() helper). Keeping it here means the
    | whole project stays consistent and the shape can be changed in one place.
    |
    | Example output: 04-June-2026 15:01:33
    |
    */

    'format' => env('DATETIME_FORMAT', 'd-F-Y H:i:s'),

];
