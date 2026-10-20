<?php

beforeEach(function () {
    $this->target = base_path('.github/workflows/api-changelog.yml');
    @unlink($this->target);
});

afterEach(function () {
    @unlink($this->target);
});

it('writes the workflow for manual openapi.json', function () {
    $this->artisan('mcp-api-docs:install-changelog', ['--export' => 'manual'])->assertSuccessful();

    expect(file_get_contents($this->target))->toContain('OPENAPI_EXPORT: ""')
        ->toContain('/update-mcp-changelog');
});

it('writes the workflow for Scramble export with the chosen database', function () {
    $this->artisan('mcp-api-docs:install-changelog', ['--export' => 'scramble', '--db' => 'pgsql'])->assertSuccessful();

    expect(file_get_contents($this->target))->toContain('OPENAPI_EXPORT: "scramble"')
        ->toContain('EXPORT_DB: pgsql')
        ->toContain('EXPORT_PHP: "'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'"');
});

it('asks before overwriting and keeps the file when declined', function () {
    @mkdir(dirname($this->target), 0755, true);
    file_put_contents($this->target, 'mine');

    $this->artisan('mcp-api-docs:install-changelog', ['--export' => 'manual'])
        ->expectsConfirmation('The workflow already exists. Overwrite it?', 'no')
        ->assertSuccessful();

    expect(file_get_contents($this->target))->toBe('mine');
});

it('asks how openapi.json is kept up to date', function () {
    $this->artisan('mcp-api-docs:install-changelog')
        ->expectsChoice('How is openapi.json kept up to date?', 'scramble', [
            'manual' => 'I commit openapi.json myself (any tool)',
            'scramble' => 'Scramble: the bot regenerates openapi.json on every push',
        ])
        ->expectsChoice('Which database do your migrations run on?', 'sqlite', [
            'mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite',
        ])
        ->assertSuccessful();

    expect(file_get_contents($this->target))->toContain('OPENAPI_EXPORT: "scramble"')->toContain('EXPORT_DB: sqlite');
});

it('rejects an unknown export mode', function () {
    $this->artisan('mcp-api-docs:install-changelog', ['--export' => 'swagger'])->assertFailed();
});
