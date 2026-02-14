<?php

namespace DhurGham\LaravelMcpApiDocs\Support;

use Illuminate\Support\Facades\Http;

class OpenApiLoader
{
    public static function load(): array
    {
        $file = config('mcp-api-docs.openapi.file');
        $url = config('mcp-api-docs.openapi.url');

        if (is_string($file) && $file !== '' && is_file($file)) {
            return self::decode((string) file_get_contents($file));
        }

        // Common fallbacks:
        foreach ([
            public_path('openapi.json'),
            base_path('openapi.json'),
            storage_path('app/openapi.json'),
            storage_path('app/api-docs/openapi.json'),
        ] as $candidate) {
            if (is_file($candidate)) {
                return self::decode((string) file_get_contents($candidate));
            }
        }

        if (is_string($url) && $url !== '') {
            $resp = Http::timeout(10)->get($url);

            if ($resp->successful()) {
                return self::decode($resp->body());
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
