<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Tools;

use DhurGham\LaravelMcpApiDocs\Support\DocsFolderReader;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListDocsTool extends Tool
{
    protected string $name = 'list_docs';

    protected string $description = 'List feature doc names from DocsForMcp folder (filename without .md). Use get_doc(name) to read content.';

    public function handle(Request $request): Response
    {
        if (! DocsFolderReader::isEnabled()) {
            return Response::error('Docs folder not configured or missing (config mcp-api-docs.docs_folder).');
        }
        $docs = DocsFolderReader::listDocs();

        return Response::text(json_encode(['docs' => $docs], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
