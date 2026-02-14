<?php

use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointsTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    config()->set('mcp-api-docs.openapi.file', __DIR__.'/../fixtures/openapi.json');
});

it('returns endpoints by tag', function () {
    $request = new Request(['tag' => 'User']);
    $tool = new GetEndpointsTool;

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
    $body = json_decode($response->content()->__toString(), true);
    expect($body['endpoints'])->toHaveCount(1)
        ->and($body['endpoints'][0]['path'])->toBe('/user')
        ->and($body['endpoints'][0]['operationId'])->toBe('user.getUser');
});

it('returns endpoints by paths array', function () {
    $request = new Request(['paths' => [['method' => 'GET', 'path' => '/user'], ['method' => 'GET', 'path' => '/orders']]]);
    $tool = new GetEndpointsTool;

    $response = $tool->handle($request);

    $body = json_decode($response->content()->__toString(), true);
    expect($body['endpoints'])->toHaveCount(2);
});
