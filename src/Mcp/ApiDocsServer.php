<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp;

use DhurGham\LaravelMcpApiDocs\Mcp\Resources\OpenApiCatalogResource;
use DhurGham\LaravelMcpApiDocs\Mcp\Resources\OpenApiResource;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetChangelogTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetDocTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointsTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\GetEndpointTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\ListDocsTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\ListTagsTool;
use DhurGham\LaravelMcpApiDocs\Mcp\Tools\SearchEndpointsTool;
use DhurGham\LaravelMcpApiDocs\Support\ApiChangelog;
use DhurGham\LaravelMcpApiDocs\Support\DocsFolderReader;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\ServerContext;

class ApiDocsServer extends Server
{
    protected string $name = 'API Docs MCP';

    protected string $version = '2.0.0';

    protected string $instructions = '';

    private const INSTRUCTIONS_INTRO = "You are connected to the application's canonical API contract. Use it to implement clients or integrations—no real HTTP calls; API info only.";

    private const INSTRUCTIONS_API = [
        '- list_tags() to see tags and their endpoints (method, path, operationId, summary)',
        '- search_endpoints(query) to discover endpoints by keyword',
        "- get_endpoint(method, path) for one endpoint's full request/response schema",
        '- get_endpoints(tag) or get_endpoints(paths: [{method, path}, ...]) for bulk schema (max 25)',
        '- resource api://openapi/catalog for servers + flat operation list (fast scan)',
        '- resource api://openapi for the full OpenAPI spec',
    ];

    private const INSTRUCTIONS_DOCS = '- list_docs() to see feature doc names (DocsForMcp folder); get_doc(name) to read one';

    private const INSTRUCTIONS_CHANGELOG = "- get_changelog(since?) for what changed per release (get_endpoint also lists that endpoint's changes)";

    private const ALL_TOOLS = [
        ListTagsTool::class,
        SearchEndpointsTool::class,
        GetEndpointTool::class,
        GetEndpointsTool::class,
        ListDocsTool::class,
        GetDocTool::class,
        GetChangelogTool::class,
    ];

    protected array $tools = self::ALL_TOOLS;

    public function createContext(): ServerContext
    {
        $tools = self::ALL_TOOLS;
        $lines = self::INSTRUCTIONS_API;
        if (DocsFolderReader::isEnabled()) {
            $lines[] = self::INSTRUCTIONS_DOCS;
        } else {
            $tools = array_diff($tools, [ListDocsTool::class, GetDocTool::class]);
        }
        if (ApiChangelog::isEnabled()) {
            $lines[] = self::INSTRUCTIONS_CHANGELOG;
        } else {
            $tools = array_diff($tools, [GetChangelogTool::class]);
        }
        // Let the parent build the context so it tracks laravel/mcp's ServerContext signature.
        $this->tools = array_values($tools);
        $this->instructions = self::INSTRUCTIONS_INTRO."\n\nUse:\n".implode("\n", $lines);

        return parent::createContext();
    }

    protected array $resources = [
        OpenApiResource::class,
        OpenApiCatalogResource::class,
    ];

    protected array $prompts = [];
}
