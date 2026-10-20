<?php

use DhurGham\LaravelMcpApiDocs\LaravelMcpApiDocsServiceProvider;

it('merges config', function () {
    expect(config('mcp-api-docs.enabled'))->toBeTrue()
        ->and(config('mcp-api-docs.path'))->toBeString()
        ->and(config('mcp-api-docs.middleware'))->toBeArray();
});

it('publishes config with tag', function () {
    $result = $this->artisan('vendor:publish', ['--tag' => 'laravel-mcp-api-docs-config', '--force' => true]);

    $result->assertSuccessful();
});

it('publishes the API changelog workflow with tag', function () {
    $target = base_path('.github/workflows/api-changelog.yml');
    @unlink($target);

    $this->artisan('vendor:publish', ['--tag' => 'laravel-mcp-api-docs-changelog-workflow', '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents($target))->toContain('/update-mcp-changelog')
        ->and(base_path('.github/scripts/api-changelog.sh'))->toBeFile();
    @unlink($target);
    @unlink(base_path('.github/scripts/api-changelog.sh'));
});

it('adds can:ability to middleware when policy_ability is set', function () {
    config()->set('mcp-api-docs.middleware', ['auth:sanctum']);
    config()->set('mcp-api-docs.policy_ability', 'useMcpApiDocs');

    $middleware = LaravelMcpApiDocsServiceProvider::resolveMiddleware();

    expect($middleware)->toContain('auth:sanctum')
        ->and($middleware)->toContain('can:useMcpApiDocs');
});

it('does not add can: when policy_ability is null', function () {
    config()->set('mcp-api-docs.middleware', ['auth:sanctum']);
    config()->set('mcp-api-docs.policy_ability', null);

    $middleware = LaravelMcpApiDocsServiceProvider::resolveMiddleware();

    expect($middleware)->toBe(['auth:sanctum']);
});
