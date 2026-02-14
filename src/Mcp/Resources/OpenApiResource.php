<?php

namespace DhurGham\LaravelMcpApiDocs\Mcp\Resources;

use DhurGham\LaravelMcpApiDocs\Support\OpenApiLoader;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class OpenApiResource extends Resource
{
    protected string $uri = 'api://openapi';

    protected string $name = 'OpenAPI Spec';

    protected string $mimeType = 'application/json';

    public function handle(Request $request): Response
    {
        $spec = OpenApiLoader::load();

        return Response::text(
            json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
