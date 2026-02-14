<?php

use DhurGham\LaravelMcpApiDocs\Mcp\Tools\SearchEndpointsTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    config()->set('mcp-api-docs.openapi.file', __DIR__.'/../fixtures/openapi.json');
});

it('returns matching endpoints for query', function () {
    $request = new Request(['query' => 'user']);
    $tool = new SearchEndpointsTool;

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
    $body = json_decode($response->content()->__toString(), true);
    expect($body['query'])->toBe('user')
        ->and($body['count'])->toBe(1)
        ->and($body['results'][0]['path'])->toBe('/user')
        ->and($body['results'][0]['method'])->toBe('GET');
});

it('returns all endpoints for empty query', function () {
    $request = new Request(['query' => '']);
    $tool = new SearchEndpointsTool;

    $response = $tool->handle($request);

    $body = json_decode($response->content()->__toString(), true);
    expect($body['count'])->toBe(2)
        ->and(count($body['results']))->toBe(2);
});

it('returns empty results when no match', function () {
    $request = new Request(['query' => 'nonexistent']);
    $tool = new SearchEndpointsTool;

    $response = $tool->handle($request);

    $body = json_decode($response->content()->__toString(), true);
    expect($body['count'])->toBe(0)->and($body['results'])->toBeEmpty();
});
