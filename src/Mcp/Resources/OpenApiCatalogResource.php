<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Resources;

use DhurGham\LaravelMcpApiDocs\Support\OpenApiLoader;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class OpenApiCatalogResource extends Resource
{
    protected string $uri = 'api://openapi/catalog';

    protected string $name = 'OpenAPI Catalog';

    protected string $mimeType = 'application/json';

    public function handle(Request $request): Response
    {
        $spec = OpenApiLoader::load();
        $paths = $spec['paths'] ?? [];
        $servers = $spec['servers'] ?? [];

        $operations = [];
        foreach ($paths as $path => $methods) {
            if (! is_array($methods)) {
                continue;
            }
            foreach ($methods as $method => $op) {
                if (! is_array($op)) {
                    continue;
                }
                $operations[] = [
                    'method' => strtoupper((string) $method),
                    'path' => (string) $path,
                    'summary' => $op['summary'] ?? null,
                    'operationId' => $op['operationId'] ?? null,
                    'tags' => array_values((array) ($op['tags'] ?? [])),
                ];
            }
        }

        $catalog = [
            'servers' => $servers,
            'operations' => $operations,
        ];

        return Response::text(
            json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
