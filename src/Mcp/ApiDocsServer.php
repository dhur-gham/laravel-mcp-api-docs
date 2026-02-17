<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp;

use DhurGham\LaravelMcpApiDocs\Mcp\Resources\OpenApiCatalogResource;
use DhurGham\LaravelMcpApiDocs\Mcp\Resources\OpenApiResource;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetDocTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointsTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\ListDocsTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\ListTagsTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\SearchEndpointsTool;
use DhurGham\LaravelMcpApiDocs\Support\DocsFolderReader;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\ServerContext;

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
- list_docs() to see feature doc names (DocsForMcp folder); get_doc(name) to read one
TXT;

    protected array $tools = [
        ListTagsTool::class,
        SearchEndpointsTool::class,
        GetEndpointTool::class,
        GetEndpointsTool::class,
        ListDocsTool::class,
        GetDocTool::class,
    ];

    public function createContext(): ServerContext
    {
        $tools = $this->tools;
        $instructions = $this->instructions;
        if (! DocsFolderReader::isEnabled()) {
            $tools = array_values(array_filter($tools, fn ($t) => $t !== ListDocsTool::class && $t !== GetDocTool::class));
            $instructions = preg_replace("/\n- list_docs\\(\\).*?\n/", "\n", $this->instructions);
        }

        return new ServerContext(
            supportedProtocolVersions: $this->supportedProtocolVersion,
            serverCapabilities: $this->capabilities,
            serverName: $this->name,
            serverVersion: $this->version,
            instructions: $instructions,
            maxPaginationLength: $this->maxPaginationLength,
            defaultPaginationLength: $this->defaultPaginationLength,
            tools: $tools,
            resources: $this->resources,
            prompts: $this->prompts,
        );
    }

    protected array $resources = [
        OpenApiResource::class,
        OpenApiCatalogResource::class,
    ];

    protected array $prompts = [];
}
