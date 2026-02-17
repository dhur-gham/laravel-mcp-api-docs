<?php

use DhurGham\LaravelMcpApiDocs\Mcp\Tools\ListDocsTool;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

beforeEach(function () {
    $this->docsDir = sys_get_temp_dir().'/mcp-docs-test-'.uniqid();
    mkdir($this->docsDir, 0755, true);
    config()->set('mcp-api-docs.docs_folder', $this->docsDir);
});

afterEach(function () {
    if (isset($this->docsDir) && is_dir($this->docsDir)) {
        array_map('unlink', glob($this->docsDir.'/*.md') ?: []);
        rmdir($this->docsDir);
    }
});

it('returns error when docs folder not configured', function () {
    config()->set('mcp-api-docs.docs_folder', null);
    $tool = new ListDocsTool;
    $response = $tool->handle(new Request([]));
    expect($response->content()->__toString())->toContain('not configured');
});

it('returns docs list with name and summary', function () {
    file_put_contents($this->docsDir.'/auth.md', "# Auth\n\nLogin flow.");
    file_put_contents($this->docsDir.'/payments.md', "# Payments\n\nStripe.");
    $tool = new ListDocsTool;
    $response = $tool->handle(new Request([]));
    $body = json_decode($response->content()->__toString(), true);
    expect($body)->toHaveKey('docs');
    $names = array_column($body['docs'], 'name');
    expect($names)->toContain('auth', 'payments');
});
