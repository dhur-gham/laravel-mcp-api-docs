<?php

use DhurGham\LaravelMcpApiDocs\Support\McpArgs;
use Laravel\Mcp\Request;

it('gets argument from Request constructor', function () {
    $request = new Request(['query' => 'user', 'foo' => 'bar']);

    expect(McpArgs::get($request, 'query', ''))->toBe('user')
        ->and(McpArgs::get($request, 'foo', ''))->toBe('bar')
        ->and(McpArgs::get($request, 'missing', 'default'))->toBe('default');
});
