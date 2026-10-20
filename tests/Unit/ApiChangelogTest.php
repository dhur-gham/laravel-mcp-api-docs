<?php

use DhurGham\LaravelMcpApiDocs\Mcp\ApiDocsServer;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetChangelogTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointTool;
use DhurGham\LaravelMcpApiDocs\Support\ApiChangelog;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Transport\FakeTransporter;

beforeEach(function () {
    config()->set('mcp-api-docs.openapi.file', __DIR__.'/../fixtures/openapi.json');
    config()->set('mcp-api-docs.changelog_file', __DIR__.'/../fixtures/api-changelog.json');
});

it('filters changes for one endpoint, newest first', function () {
    $changes = ApiChangelog::forEndpoint('get', '/user');

    expect($changes)->toHaveCount(2)
        ->and($changes[0]['version'])->toBe('1.2.0')
        ->and($changes[1]['level'])->toBe('deprecation');
});

it('returns only releases newer than since', function () {
    expect(ApiChangelog::since('v1.1.0'))->toHaveCount(1)
        ->and(ApiChangelog::since('1.2.0'))->toHaveCount(0)
        ->and(ApiChangelog::since(null))->toHaveCount(2);
});

it('filters since by PR labels using list order', function () {
    $file = sys_get_temp_dir().'/api-changelog-pr-'.uniqid().'.json';
    file_put_contents($file, json_encode(['releases' => [
        ['version' => '#42', 'changes' => []],
        ['version' => '#41', 'changes' => []],
        ['version' => '#38', 'changes' => []],
    ]]));
    config()->set('mcp-api-docs.changelog_file', $file);

    expect(array_column(ApiChangelog::since('#38'), 'version'))->toBe(['#42', '#41'])
        ->and(array_column(ApiChangelog::since('41'), 'version'))->toBe(['#42']);
    @unlink($file);
});

it('get_changelog returns releases', function () {
    $response = (new GetChangelogTool)->handle(new Request(['since' => '1.1.0']));
    $body = json_decode($response->content()->__toString(), true);

    expect($body['releases'])->toHaveCount(1)
        ->and($body['releases'][0]['changes'][1]['level'])->toBe('breaking');
});

it('get_changelog errors when not configured', function () {
    config()->set('mcp-api-docs.changelog_file', null);
    $response = (new GetChangelogTool)->handle(new Request([]));

    expect($response->content()->__toString())->toContain('not configured');
});

it('get_endpoint includes the endpoint changes', function () {
    $response = (new GetEndpointTool)->handle(new Request(['method' => 'GET', 'path' => '/user']));
    $body = json_decode($response->content()->__toString(), true);

    expect($body['changes'])->toHaveCount(2);
});

it('get_endpoint omits changes when changelog is disabled', function () {
    config()->set('mcp-api-docs.changelog_file', null);
    $response = (new GetEndpointTool)->handle(new Request(['method' => 'GET', 'path' => '/user']));
    $body = json_decode($response->content()->__toString(), true);

    expect($body)->not->toHaveKey('changes');
});

it('server registers get_changelog only when enabled', function () {
    config()->set('mcp-api-docs.docs_folder', null);
    $names = fn () => (new ApiDocsServer(new FakeTransporter))->createContext()->tools()->map(fn ($t) => $t->name())->values()->all();

    expect($names())->toContain('get_changelog');

    config()->set('mcp-api-docs.changelog_file', null);
    expect($names())->not->toContain('get_changelog');
});

it('reads a per-PR changelog written by the stores CI', function () {
    config()->set('mcp-api-docs.changelog_file', __DIR__.'/../fixtures/api-changelog-pr.json');

    expect(array_column(ApiChangelog::since('#38'), 'version'))->toBe(['#42', '#41'])
        ->and(array_column(ApiChangelog::forEndpoint('POST', '/orders'), 'version'))->toBe(['#41', '#38'])
        ->and(ApiChangelog::forEndpoint('POST', '/orders')[1]['level'])->toBe('breaking');
});
