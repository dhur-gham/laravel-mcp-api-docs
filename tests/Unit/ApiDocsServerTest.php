<?php

use DhurGham\LaravelMcpApiDocs\Mcp\ApiDocsServer;
use Laravel\Mcp\Server\Transport\FakeTransporter;

beforeEach(function () {
    $this->server = new ApiDocsServer(new FakeTransporter);
});

it('excludes docs tools when docs_folder is not set', function () {
    config()->set('mcp-api-docs.docs_folder', null);

    $context = $this->server->createContext();
    $names = $context->tools()->map(fn ($t) => $t->name())->values()->all();

    expect($names)->toHaveCount(4)
        ->and($names)->not->toContain('list_docs', 'get_doc');
});

it('includes docs tools when docs_folder is set and exists', function () {
    $dir = sys_get_temp_dir().'/mcp-server-docs-'.uniqid();
    mkdir($dir, 0755, true);
    config()->set('mcp-api-docs.docs_folder', $dir);

    $context = $this->server->createContext();
    $names = $context->tools()->map(fn ($t) => $t->name())->values()->all();

    expect($names)->toContain('list_docs', 'get_doc')->and($names)->toHaveCount(6);
    rmdir($dir);
});
