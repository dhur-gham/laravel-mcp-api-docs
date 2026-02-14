<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Tools;

use DhurGham\LaravelMcpApiDocs\Support\McpArgs;
use DhurGham\LaravelMcpApiDocs\Support\OpenApiLoader;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetEndpointsTool extends Tool
{
    protected string $name = 'get_endpoints';

    protected string $description = 'Bulk fetch full schema for endpoints: pass tag (e.g. "User") to get all ops in that tag, or paths (array of {method, path}). Returns same shape as get_endpoint per item. Max 25. No real HTTP calls—API info only.';

    private const MAX = 25;

    public function handle(Request $request): Response
    {
        $tag = trim((string) McpArgs::get($request, 'tag', ''));
        $pathsArg = McpArgs::get($request, 'paths', null);

        $spec = OpenApiLoader::load();
        $paths = $spec['paths'] ?? [];

        $toFetch = [];

        if ($pathsArg !== null && is_array($pathsArg)) {
            foreach ($pathsArg as $item) {
                $m = isset($item['method']) ? strtoupper(trim((string) $item['method'])) : 'GET';
                $p = isset($item['path']) ? trim((string) $item['path']) : '';
                if ($p !== '') {
                    $toFetch[] = ['method' => $m, 'path' => $p];
                }
            }
        } elseif ($tag !== '') {
            $tagLower = mb_strtolower($tag);
            foreach ($paths as $path => $methods) {
                if (! is_array($methods)) {
                    continue;
                }
                foreach ($methods as $method => $op) {
                    if (! is_array($op)) {
                        continue;
                    }
                    $tags = array_map('mb_strtolower', (array) ($op['tags'] ?? []));
                    if (in_array($tagLower, $tags, true)) {
                        $toFetch[] = ['method' => strtoupper((string) $method), 'path' => (string) $path];
                    }
                }
            }
        }

        $seen = [];
        $toFetch = array_values(array_filter($toFetch, function ($item) use (&$seen) {
            $key = $item['method'].' '.$item['path'];
            if (isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;

            return true;
        }));
        $toFetch = array_slice($toFetch, 0, self::MAX);

        $endpoints = [];
        foreach ($toFetch as $item) {
            $method = $item['method'];
            $path = $item['path'];
            $op = $paths[$path][strtolower($method)] ?? null;
            if (! is_array($op)) {
                continue;
            }
            $endpoints[] = [
                'method' => $method,
                'path' => $path,
                'summary' => $op['summary'] ?? null,
                'description' => $op['description'] ?? null,
                'operationId' => $op['operationId'] ?? null,
                'tags' => array_values((array) ($op['tags'] ?? [])),
                'security' => $op['security'] ?? null,
                'parameters' => $op['parameters'] ?? [],
                'requestBody' => $op['requestBody'] ?? null,
                'responses' => $op['responses'] ?? [],
            ];
        }

        return Response::text(json_encode(['endpoints' => $endpoints], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
