<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp;

use DhurGham\LaravelMcpApiDocs\Mcp\Resources\OpenApiResource;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\SearchEndpointsTool;
use Laravel\Mcp\Server;

class ApiDocsServer extends Server
{
    protected string $name = 'API Docs MCP';

    protected string $version = '0.1.0';

    protected string $instructions = <<<'TXT'
You are connected to the application's canonical API contract.
Do not guess endpoints, payloads, or response shapes.

Use:
- search_endpoints(query) to discover endpoints
- get_endpoint(method, path) to retrieve exact request/response schema and examples
- resource api://openapi for the complete OpenAPI spec
TXT;

    protected array $tools = [
        SearchEndpointsTool::class,
        GetEndpointTool::class,
    ];

    protected array $resources = [
        OpenApiResource::class,
    ];

    protected array $prompts = [];
}
