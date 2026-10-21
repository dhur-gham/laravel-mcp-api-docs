<?php

namespace DhurGham\LaravelMcpApiDocs\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenApiLoader
{
    public static function load(?string $bearerToken = null): array
    {
        $file = config('mcp-api-docs.openapi.file');
        $url = config('mcp-api-docs.openapi.url');

        if (is_string($file) && $file !== '' && is_file($file)) {
            return self::loadFile($file);
        }

        // An explicitly configured URL wins over the guessed local fallbacks.
        if (is_string($url) && $url !== '') {
            $auth = $bearerToken ?? (app()->bound('request') ? request()->header('Authorization') : null);
            $spec = self::loadUrl($url, $auth);

            if ($spec !== null) {
                return $spec;
            }
        }

        // Common fallbacks:
        foreach ([
            public_path('openapi.json'),
            base_path('openapi.json'),
            storage_path('app/openapi.json'),
            storage_path('app/api-docs/openapi.json'),
        ] as $candidate) {
            if (is_file($candidate)) {
                return self::loadFile($candidate);
            }
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'OpenAPI not found',
                'version' => '0.0.0',
            ],
            'x_error' => 'No OpenAPI JSON found. Set MCP_API_DOCS_OPENAPI_FILE or MCP_API_DOCS_OPENAPI_URL.',
            'paths' => new \stdClass,
        ];
    }

    private static function loadFile(string $path): array
    {
        $key = 'mcp-api-docs:file:'.md5($path.'|'.filemtime($path));

        return self::remember($key, fn () => self::decode((string) file_get_contents($path)));
    }

    private static function loadUrl(string $url, ?string $auth): ?array
    {
        $key = 'mcp-api-docs:url:'.md5($url.'|'.$auth);

        return self::remember($key, function () use ($url, $auth) {
            $client = Http::timeout(10);
            if ($auth !== null && $auth !== '') {
                $client = $client->withHeaders(['Authorization' => $auth]);
            }
            $resp = $client->get($url);

            return $resp->successful() ? self::decode($resp->body()) : null;
        });
    }

    /**
     * Cache successful loads only; null (failed fetch) and decode errors are never cached.
     */
    private static function remember(string $key, \Closure $resolver): ?array
    {
        $ttl = (int) config('mcp-api-docs.openapi.cache_ttl', 60);
        if ($ttl <= 0) {
            return $resolver();
        }

        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $spec = $resolver();
        if ($spec !== null && ! isset($spec['x_error'])) {
            Cache::put($key, $spec, $ttl);
        }

        return $spec;
    }

    private static function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Invalid OpenAPI JSON',
                'version' => '0.0.0',
            ],
            'x_error' => 'OpenAPI JSON could not be decoded.',
            'paths' => new \stdClass,
        ];
    }
}
