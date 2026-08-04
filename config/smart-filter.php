<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed Fields
    |--------------------------------------------------------------------------
    |
    | A global whitelist of fields that may be filtered. When empty, all fields
    | are permitted (model-level $filterable takes precedence).
    |
    | Example: ['name', 'email', 'status', 'created_at']
    |
    */
    'allowed_fields' => [],

    /*
    |--------------------------------------------------------------------------
    | Ignored Fields
    |--------------------------------------------------------------------------
    |
    | Fields listed here are silently dropped from all filter requests.
    | Use this to protect sensitive columns (e.g. 'password', 'remember_token').
    |
    */
    'ignored_fields' => [
        'password',
        'remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Aliases
    |--------------------------------------------------------------------------
    |
    | Map request parameter names to real database column names.
    | Useful when your API surface doesn't match your schema.
    |
    | Example: ['city' => 'address_city', 'phone' => 'contact_phone']
    |
    */
    'aliases' => [],

    /*
    |--------------------------------------------------------------------------
    | Searchable Fields
    |--------------------------------------------------------------------------
    |
    | Default fields searched when ?search=term is used. These are used as
    | the fallback when the model does not define its own $searchable array.
    |
    */
    'searchable_fields' => [],

    /*
    |--------------------------------------------------------------------------
    | Sort Parameter
    |--------------------------------------------------------------------------
    |
    | The query string key used for sorting. Defaults to "sort".
    | Supports comma-separated columns: ?sort=-created_at,name
    | Prefix with "-" for descending order.
    |
    */
    'sort_param' => 'sort',

    /*
    |--------------------------------------------------------------------------
    | Search Parameter
    |--------------------------------------------------------------------------
    |
    | The query string key used for full-text search. Defaults to "search".
    |
    */
    'search_param' => 'search',

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, requests with fields not in the allowed list or in the
    | ignored list will throw an InvalidFilterFieldException instead of
    | being silently dropped.
    |
    | Recommended: false in production, true during development.
    |
    */
    'strict' => false,

    /*
    |--------------------------------------------------------------------------
    | Custom Operators
    |--------------------------------------------------------------------------
    |
    | Register additional operator classes here. Each class must implement
    | Pradeepdev\SmartFilter\Contracts\OperatorContract.
    |
    | Example: [App\Filters\StartsWithOperator::class]
    |
    */
    'operators' => [],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, SmartFilter will log the parsed FilterCollection and the
    | final SQL query to the Laravel log channel.
    |
    */
    'debug' => env('SMART_FILTER_DEBUG', false),

];
