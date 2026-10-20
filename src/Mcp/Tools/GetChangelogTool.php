<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Tools;

use DhurGham\LaravelMcpApiDocs\Support\ApiChangelog;
use DhurGham\LaravelMcpApiDocs\Support\McpArgs;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetChangelogTool extends Tool
{
    protected string $name = 'get_changelog';

    protected string $description = 'API changelog, one entry per merged PR or release (newest first). Optional since (an entry label such as "#41" or "v1.4.0") returns only newer entries. Each change has method, path, level (breaking|deprecation|non-breaking), text.';

    public function handle(Request $request): Response
    {
        if (! ApiChangelog::isEnabled()) {
            return Response::error('API changelog not configured or missing (config mcp-api-docs.changelog_file).');
        }
        $since = McpArgs::get($request, 'since');

        return Response::text(json_encode(
            ['releases' => ApiChangelog::since(is_string($since) ? $since : null)],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }
}
