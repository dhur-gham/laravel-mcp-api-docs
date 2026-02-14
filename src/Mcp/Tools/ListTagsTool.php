<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Tools;

use DhurGham\LaravelMcpApiDocs\Support\OpenApiLoader;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListTagsTool extends Tool
{
    protected string $name = 'list_tags';

    protected string $description = 'List OpenAPI tags and their endpoints (method, path, operationId, summary). Use to see API areas before calling get_endpoint or get_endpoints.';

    public function handle(Request $request): Response
    {
        $spec = OpenApiLoader::load();
        $paths = $spec['paths'] ?? [];

        $byTag = [];

        foreach ($paths as $path => $methods) {
            if (! is_array($methods)) {
                continue;
            }
            foreach ($methods as $method => $op) {
                if (! is_array($op)) {
                    continue;
                }
                $tags = (array) ($op['tags'] ?? []);
                if ($tags === []) {
                    $tags = ['default'];
                }
                $entry = [
                    'method' => strtoupper((string) $method),
                    'path' => (string) $path,
                    'operationId' => $op['operationId'] ?? null,
                    'summary' => $op['summary'] ?? null,
                ];
                foreach ($tags as $tag) {
                    $tag = (string) $tag;
                    if (! isset($byTag[$tag])) {
                        $byTag[$tag] = [];
                    }
                    $byTag[$tag][] = $entry;
                }
            }
        }

        $tags = [];
        foreach ($byTag as $name => $endpoints) {
            $tags[] = ['tag' => $name, 'endpoints' => $endpoints];
        }

        return Response::text(json_encode(['tags' => $tags], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
