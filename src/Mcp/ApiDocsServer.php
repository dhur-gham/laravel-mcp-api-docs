<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp;

use DhurGham\LaravelMcpApiDocs\Mcp\Resources\OpenApiCatalogResource;
use DhurGham\LaravelMcpApiDocs\Mcp\Resources\OpenApiResource;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointsTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\ListTagsTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\SearchEndpointsTool;
use Laravel\Mcp\Server;

class ApiDocsServer extends Server
{
    protected string $name = 'API Docs MCP';

    protected string $version = '0.1.0';

    protected string $instructions = <<<'TXT'
You are connected to the application's canonical API contract. Use it to implement clients or integrations—no real HTTP calls; API info only.

Use:
- list_tags() to see tags and their endpoints (method, path, operationId, summary)
- search_endpoints(query) to discover endpoints by keyword
- get_endpoint(method, path) for one endpoint's full request/response schema
- get_endpoints(tag) or get_endpoints(paths: [{method, path}, ...]) for bulk schema (max 25)
- resource api://openapi/catalog for servers + flat operation list (fast scan)
- resource api://openapi for the full OpenAPI spec
TXT;

    protected array $tools = [
        ListTagsTool::class,
        SearchEndpointsTool::class,
        GetEndpointTool::class,
        GetEndpointsTool::class,
    ];

    protected array $resources = [
        OpenApiResource::class,
        OpenApiCatalogResource::class,
    ];

    protected array $prompts = [];
}
