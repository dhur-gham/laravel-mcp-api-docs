<?php

use DhurGham\LaravelMcpApiDocs\Mcp\Tools\ListTagsTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    config()->set('mcp-api-docs.openapi.file', __DIR__.'/../fixtures/openapi.json');
});

it('returns tags with endpoints', function () {
    $request = new Request([]);
    $tool = new ListTagsTool;

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
    $body = json_decode($response->content()->__toString(), true);
    expect($body)->toHaveKey('tags');
    $tags = collect($body['tags'])->keyBy('tag');
    expect($tags->has('User'))->toBeTrue()
        ->and($tags->has('Orders'))->toBeTrue()
        ->and($tags->get('User')['endpoints'][0]['path'])->toBe('/user');
});
