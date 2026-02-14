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
