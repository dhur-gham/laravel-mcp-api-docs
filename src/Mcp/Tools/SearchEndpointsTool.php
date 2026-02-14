<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Tools;

use DhurGham\LaravelMcpApiDocs\Support\McpArgs;
use DhurGham\LaravelMcpApiDocs\Support\OpenApiLoader;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchEndpointsTool extends Tool
{
    protected string $name = 'search_endpoints';

    protected string $description = 'Search OpenAPI paths/operations by keyword (path, summary, operationId, tags).';

    public function handle(Request $request): Response
    {
        $q = trim((string) McpArgs::get($request, 'query', ''));
        $qLower = mb_strtolower($q);

        $spec = OpenApiLoader::load();
        $paths = $spec['paths'] ?? [];

        $results = [];

        foreach ($paths as $path => $methods) {
            if (! is_array($methods)) {
                continue;
            }

            foreach ($methods as $method => $op) {
                if (! is_array($op)) {
                    continue;
                }

                $haystack = mb_strtolower(
                    $path.' '.
                    (string) ($op['summary'] ?? '').' '.
                    (string) ($op['operationId'] ?? '').' '.
                    implode(' ', (array) ($op['tags'] ?? []))
                );

                if ($qLower === '' || str_contains($haystack, $qLower)) {
                    $results[] = [
                        'method' => strtoupper((string) $method),
                        'path' => (string) $path,
                        'summary' => $op['summary'] ?? null,
                        'operationId' => $op['operationId'] ?? null,
                        'tags' => array_values((array) ($op['tags'] ?? [])),
                    ];
                }
            }
        }

        $results = array_slice($results, 0, 50);

        return Response::text(json_encode([
            'query' => $q,
            'count' => count($results),
            'results' => $results,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
