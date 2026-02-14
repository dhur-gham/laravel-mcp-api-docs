<?php

use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    config()->set('mcp-api-docs.openapi.file', __DIR__.'/../fixtures/openapi.json');
});

it('returns endpoint when found', function () {
    $request = new Request(['method' => 'GET', 'path' => '/user']);
    $tool = new GetEndpointTool;

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
    $body = json_decode($response->content()->__toString(), true);
    expect($body['method'])->toBe('GET')
        ->and($body['path'])->toBe('/user')
        ->and($body['operationId'])->toBe('user.getUser')
        ->and($body['summary'])->toBe('Get current user');
});

it('returns error when endpoint not found', function () {
    $request = new Request(['method' => 'POST', 'path' => '/user']);
    $tool = new GetEndpointTool;

    $response = $tool->handle($request);

    expect($response->content()->__toString())->toContain('Endpoint not found');
});
