<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Tools;

use DhurGham\LaravelMcpApiDocs\Support\DocsFolderReader;
use DhurGham\LaravelMcpApiDocs\Support\McpArgs;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetDocTool extends Tool
{
    protected string $name = 'get_doc';

    protected string $description = 'Return markdown content for a feature doc by name (from DocsForMcp). Use list_docs to see available names.';

    public function handle(Request $request): Response
    {
        if (! DocsFolderReader::isEnabled()) {
            return Response::error('Docs folder not configured or missing.');
        }
        $name = trim((string) McpArgs::get($request, 'name', ''));
        if ($name === '') {
            return Response::error('Argument "name" is required (feature doc name, e.g. from list_docs).');
        }
        $content = DocsFolderReader::getDoc($name);
        if ($content === null) {
            return Response::error("Doc not found: {$name}");
        }

        return Response::text($content);
    }
}
