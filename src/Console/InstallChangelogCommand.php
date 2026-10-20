<?php

namespace DhurGham\LaravelMcpApiDocs\Console;

use Dedoc\Scramble\Scramble;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class InstallChangelogCommand extends Command
{
    protected $signature = 'mcp-api-docs:install-changelog
        {--export= : How openapi.json is kept up to date: manual or scramble}
        {--db= : Database your migrations run on when exporting: mysql, pgsql or sqlite}
        {--force : Overwrite an existing workflow}';

    protected $description = 'Add the GitHub Actions workflow that keeps the per-PR API changelog';

    private const EXPORTS = [
        'manual' => 'I commit openapi.json myself (any tool)',
        'scramble' => 'Scramble: the bot regenerates openapi.json on every push',
    ];

    private const DATABASES = ['mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite'];

    public function handle(): int
    {
        $target = base_path('.github/workflows/api-changelog.yml');
        if (is_file($target) && ! $this->option('force')
            && ! confirm('The workflow already exists. Overwrite it?', default: false)) {
            $this->components->info('Kept the existing workflow.');

            return self::SUCCESS;
        }

        $scrambleInstalled = class_exists(Scramble::class);
        $export = $this->option('export') ?? select(
            label: 'How is openapi.json kept up to date?',
            options: self::EXPORTS,
            default: $scrambleInstalled ? 'scramble' : 'manual',
        );
        if (! array_key_exists($export, self::EXPORTS)) {
            $this->components->error('--export must be manual or scramble.');

            return self::FAILURE;
        }

        $db = 'mysql';
        if ($export === 'scramble') {
            if (! $scrambleInstalled) {
                $this->components->warn('dedoc/scramble is not installed. Run: composer require dedoc/scramble');
            }
            $default = str_replace('mariadb', 'mysql', (string) config('database.default'));
            $db = $this->option('db') ?? select(
                label: 'Which database do your migrations run on?',
                options: self::DATABASES,
                default: array_key_exists($default, self::DATABASES) ? $default : 'mysql',
            );
            if (! array_key_exists($db, self::DATABASES)) {
                $this->components->error('--db must be mysql, pgsql or sqlite.');

                return self::FAILURE;
            }
        }

        $workflow = (string) file_get_contents(__DIR__.'/../../stubs/api-changelog.yml');
        $workflow = preg_replace('/^  OPENAPI_EXPORT: .*$/m', '  OPENAPI_EXPORT: "'.($export === 'scramble' ? 'scramble' : '').'"', $workflow);
        $workflow = preg_replace('/^  EXPORT_DB: .*$/m', '  EXPORT_DB: '.$db, $workflow);
        $workflow = preg_replace('/^  EXPORT_PHP: .*$/m', '  EXPORT_PHP: "'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'"', $workflow);

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        file_put_contents($target, $workflow);

        $script = base_path('.github/scripts/api-changelog.sh');
        if (! is_dir(dirname($script))) {
            mkdir(dirname($script), 0755, true);
        }
        copy(__DIR__.'/../../bin/api-changelog.sh', $script);

        $this->components->info('Created .github/workflows/api-changelog.yml and .github/scripts/api-changelog.sh ('.($export === 'scramble' ? "Scramble export, {$db}" : 'manual openapi.json').').');
        $this->components->bulletList([
            $export === 'scramble'
                ? 'Export once and commit openapi.json: php artisan scramble:export --path=openapi.json'
                : 'Commit openapi.json at the repo root and keep it updated in every API PR.',
            'Merge the workflow to your default branch (needed for /update-mcp-changelog).',
            'Set MCP_API_DOCS_CHANGELOG_FILE to the path of api-changelog.json.',
        ]);

        return self::SUCCESS;
    }
}
