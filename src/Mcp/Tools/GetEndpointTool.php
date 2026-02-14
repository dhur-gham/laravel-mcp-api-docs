<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Tools;

use DhurGham\LaravelMcpApiDocs\Support\McpArgs;
use DhurGham\LaravelMcpApiDocs\Support\OpenApiLoader;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetEndpointTool extends Tool
{
    protected string $name = 'get_endpoint';

    protected string $description = 'Return request/response schema + examples for a method/path from OpenAPI.';

    public function handle(Request $request): Response
    {
        $method = strtoupper(trim((string) McpArgs::get($request, 'method', 'GET')));
        $path = trim((string) McpArgs::get($request, 'path', ''));

        $spec = OpenApiLoader::load();
        $paths = $spec['paths'] ?? [];

        $op = null;

        if (isset($paths[$path]) && is_array($paths[$path])) {
            $op = $paths[$path][strtolower($method)] ?? null;
        }

        if (! is_array($op)) {
            return Response::error("Endpoint not found in OpenAPI: {$method} {$path}");
        }

        $result = [
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

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
