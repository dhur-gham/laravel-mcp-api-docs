<?php

use DhurGham\LaravelMcpApiDocs\Support\OpenApiLoader;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('mcp-api-docs.openapi.file', null);
    config()->set('mcp-api-docs.openapi.url', null);
});

it('loads from config file when set', function () {
    $path = __DIR__.'/../fixtures/openapi.json';
    config()->set('mcp-api-docs.openapi.file', $path);

    $spec = OpenApiLoader::load();

    expect($spec)->toHaveKeys(['openapi', 'info', 'paths'])
        ->and($spec['paths']['/user']['get']['operationId'])->toBe('user.getUser');
});

it('returns error structure when no source and no fallback file', function () {
    $spec = OpenApiLoader::load();

    expect($spec['x_error'] ?? null)->toContain('No OpenAPI JSON found')
        ->and($spec['paths'])->toBeInstanceOf(\stdClass::class);
});

it('decodes invalid json to error structure', function () {
    $path = sys_get_temp_dir().'/invalid-openapi-'.uniqid().'.json';
    file_put_contents($path, 'not json');
    config()->set('mcp-api-docs.openapi.file', $path);

    $spec = OpenApiLoader::load();

    expect($spec['x_error'] ?? null)->toContain('could not be decoded');
    @unlink($path);
});

it('loads from url when configured and file not set', function () {
    Http::fake(['*' => Http::response(file_get_contents(__DIR__.'/../fixtures/openapi.json'))]);
    config()->set('mcp-api-docs.openapi.url', 'https://api.example.com/openapi.json');

    $spec = OpenApiLoader::load();

    expect($spec['paths']['/user'] ?? null)->toBeArray();
});

it('falls back to error when url returns unsuccessful', function () {
    Http::fake(['*' => Http::response('', 500)]);
    config()->set('mcp-api-docs.openapi.url', 'https://api.example.com/openapi.json');

    $spec = OpenApiLoader::load();

    expect($spec['x_error'] ?? null)->toContain('No OpenAPI JSON found');
});

it('sends bearer token when loading from url and token provided', function () {
    $json = file_get_contents(__DIR__.'/../fixtures/openapi.json');
    Http::fake([
        '*' => function ($request) use ($json) {
            if ($request->hasHeader('Authorization') && $request->header('Authorization')[0] === 'Bearer secret-token') {
                return Http::response($json, 200);
            }
            return Http::response('Unauthorized', 401);
        },
    ]);
    config()->set('mcp-api-docs.openapi.url', 'https://staging.example.com/docs/api.json');

    $spec = OpenApiLoader::load('Bearer secret-token');

    expect($spec['paths']['/user'] ?? null)->toBeArray();
});

it('returns error when url requires auth and no token given', function () {
    Http::fake(['*' => Http::response('Unauthorized', 401)]);
    config()->set('mcp-api-docs.openapi.url', 'https://staging.example.com/docs/api.json');

    $spec = OpenApiLoader::load();

    expect($spec['x_error'] ?? null)->toContain('No OpenAPI JSON found');
});

it('forwards request authorization when loading from url and no token passed', function () {
    $json = file_get_contents(__DIR__.'/../fixtures/openapi.json');
    Http::fake([
        '*' => function ($request) use ($json) {
            if ($request->hasHeader('Authorization')) {
                return Http::response($json, 200);
            }
            return Http::response('Unauthorized', 401);
        },
    ]);
    config()->set('mcp-api-docs.openapi.url', 'https://staging.example.com/docs/api.json');
    $req = \Illuminate\Http\Request::create('/');
    $req->headers->set('Authorization', 'Bearer user-token');
    app()->instance('request', $req);

    $spec = OpenApiLoader::load();

    expect($spec['paths']['/user'] ?? null)->toBeArray();
});
