<?php

return [

    'enabled' => env('MCP_API_DOCS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | MCP Route Path
    |--------------------------------------------------------------------------
    */
    'path' => env('MCP_API_DOCS_PATH', '/mcp/api-docs'),

    /*
    |--------------------------------------------------------------------------
    | Middleware (comma separated in env)
    |--------------------------------------------------------------------------
    | Example: auth:sanctum
    */
    'middleware' => array_values(array_filter(array_map('trim', explode(',', env('MCP_API_DOCS_MIDDLEWARE', 'auth:sanctum'))))),

    /*
    |--------------------------------------------------------------------------
    | Policy ability (Gate)
    |--------------------------------------------------------------------------
    | After auth, require the authenticated user to pass this ability.
    | Register in AuthServiceProvider or a Policy (e.g. UserPolicy::useMcpApiDocs).
    | Set to null to skip policy check (only auth required).
    */
    'policy_ability' => env('MCP_API_DOCS_POLICY_ABILITY', null),

    /*
    |--------------------------------------------------------------------------
    | OpenAPI Source
    |--------------------------------------------------------------------------
    */
    'openapi' => [
        'file' => env('MCP_API_DOCS_OPENAPI_FILE', null),
        'url' => env('MCP_API_DOCS_OPENAPI_URL', null),
    ],

];
