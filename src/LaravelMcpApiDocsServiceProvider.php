<?php

namespace DhurGham\LaravelMcpApiDocs;

use DhurGham\LaravelMcpApiDocs\Http\Middleware\CatchMcpAuthExceptions;
use DhurGham\LaravelMcpApiDocs\Mcp\ApiDocsServer;
use Laravel\Mcp\Facades\Mcp;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMcpApiDocsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-mcp-api-docs');
    }

    public function register(): mixed
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mcp-api-docs.php', 'mcp-api-docs');

        return parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__.'/../config/mcp-api-docs.php' => config_path('mcp-api-docs.php')],
                'laravel-mcp-api-docs-config'
            );
        }
    }

    public function bootingPackage(): void
    {
        if (! config('mcp-api-docs.enabled')) {
            return;
        }

        if (! class_exists(Mcp::class)) {
            return;
        }

        $path = (string) config('mcp-api-docs.path', '/mcp/api-docs');

        $middleware = [CatchMcpAuthExceptions::class, ...self::resolveMiddleware()];

        Mcp::web($path, ApiDocsServer::class)
            ->middleware($middleware);
    }

    /** @return array<int, string> */
    public static function resolveMiddleware(): array
    {
        $middleware = (array) config('mcp-api-docs.middleware', []);
        $ability = config('mcp-api-docs.policy_ability');
        if (is_string($ability) && $ability !== '') {
            $middleware[] = 'can:'.$ability;
        }

        return $middleware;
    }
}
