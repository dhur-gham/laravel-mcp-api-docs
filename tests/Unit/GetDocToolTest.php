<?php

use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetDocTool;
use Laravel\Mcp\Request;

beforeEach(function () {
    $this->docsDir = sys_get_temp_dir().'/mcp-doc-get-'.uniqid();
    mkdir($this->docsDir, 0755, true);
    config()->set('mcp-api-docs.docs_folder', $this->docsDir);
});

afterEach(function () {
    if (isset($this->docsDir) && is_dir($this->docsDir)) {
        array_map('unlink', glob($this->docsDir.'/*.md') ?: []);
        rmdir($this->docsDir);
    }
});

it('returns doc content by name', function () {
    file_put_contents($this->docsDir.'/auth.md', "# Auth\n\nLogin flow.");
    $request = new Request(['name' => 'auth']);
    $tool = new GetDocTool;
    $response = $tool->handle($request);
    expect($response->content()->__toString())->toContain('Auth', 'Login flow');
});

it('returns error when doc not found', function () {
    $request = new Request(['name' => 'nonexistent']);
    $tool = new GetDocTool;
    $response = $tool->handle($request);
    expect($response->content()->__toString())->toContain('not found');
});
