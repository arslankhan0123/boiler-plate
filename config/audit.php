<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Audit / activity logging
    |--------------------------------------------------------------------------
    |
    | Central audit_trails + activity_logs are written in the BACKGROUND by the
    | RecordAudit job. Set `enabled=false` to turn capture off entirely (e.g. in
    | tests that don't assert on it).
    |
    */

    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | The audit job runs on its own queue so it can be supervised independently
    | (a dedicated Horizon supervisor in production). `connection` defaults to
    | the app's queue connection so it works out of the box on the `database`
    | driver in local dev; set AUDIT_QUEUE_CONNECTION=redis in production and run
    | Horizon to process the `audit` queue. `null` connection => the app default.
    |
    */

    'queue' => [
        'connection' => env('AUDIT_QUEUE_CONNECTION', 'central_database'),
        'name' => env('AUDIT_QUEUE', 'audit'),
    ],

    /*
    |--------------------------------------------------------------------------
    | GeoIP (country-of-request)
    |--------------------------------------------------------------------------
    |
    | The activity log resolves `request_country` from the caller's IP using a
    | self-hosted MaxMind GeoLite2-Country database. Drop the `.mmdb` file at the
    | configured path and set a (free) MaxMind license to download it. When the
    | file or the geoip2 package is absent, resolution returns null gracefully —
    | logging still works, `request_country` is simply left empty.
    |
    */

    'geoip' => [
        'enabled' => env('AUDIT_GEOIP_ENABLED', true),
        'database' => env('AUDIT_GEOIP_DATABASE', storage_path('app/geoip/GeoLite2-Country.mmdb')),
    ],

];
