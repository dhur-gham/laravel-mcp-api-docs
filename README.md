# Laravel MCP API Docs

Exposes your app’s OpenAPI spec to Laravel MCP via tools and a resource so the AI uses the canonical API contract (no guessing endpoints or payloads).

## Requirements

- PHP 8.4+
- Laravel 11 or 12
- [laravel/mcp](https://github.com/laravel/mcp) ^0.5.7

## Installation

```bash
composer require dhur-gham/laravel-mcp-api-docs
```

Publish the config (optional; defaults work out of the box):

```bash
php artisan vendor:publish --tag="laravel-mcp-api-docs-config"
```

## Configuration

After publishing, edit `config/mcp-api-docs.php`:

| Key | Env | Description |
|-----|-----|-------------|
| `enabled` | `MCP_API_DOCS_ENABLED` | Enable/disable the MCP API Docs server (default: `true`). |
| `path` | `MCP_API_DOCS_PATH` | Web route path for the MCP server (default: `/mcp/api-docs`). |
| `middleware` | `MCP_API_DOCS_MIDDLEWARE` | Comma-separated middleware, e.g. `auth:sanctum`. |
| `policy_ability` | `MCP_API_DOCS_POLICY_ABILITY` | Gate ability (e.g. `useMcpApiDocs`) required after auth; user must be allowed by policy. Omit or null to skip. |
| `openapi.file` | `MCP_API_DOCS_OPENAPI_FILE` | Absolute path to a local OpenAPI JSON file. |
| `openapi.url` | `MCP_API_DOCS_OPENAPI_URL` | URL to fetch OpenAPI JSON from. |
| `docs_folder` | `MCP_API_DOCS_DOCS_FOLDER` | Path to folder of feature docs (e.g. `DocsForMcp`); each `.md` file = one feature. Omit or null = docs tools are not registered. |

If neither `openapi.file` nor `openapi.url` is set, the package looks for `openapi.json` in `public/`, project root, `storage/app/`, and `storage/app/api-docs/`.

### Policy (optional)

To require the authenticated user to be allowed by a Laravel policy (not just a valid token), set `policy_ability` (e.g. `MCP_API_DOCS_POLICY_ABILITY=useMcpApiDocs`). Then define the ability in a policy and register it:

```php
// app/Policies/UserPolicy.php
public function useMcpApiDocs(User $user): bool
{
    return $user->hasPermission('use_mcp'); // your logic
}
```

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    User::class => UserPolicy::class,
];
```

Middleware order: first `auth:sanctum` (token → user), then `can:useMcpApiDocs` (policy check).

## MCP Behaviour

Read-only API info for AI agents to implement against (no real HTTP requests).

- **Tools**
  - `list_tags()` – list tags and their endpoints (method, path, operationId, summary).
  - `search_endpoints(query)` – discover endpoints by keyword (path, summary, operationId, tags).
  - `get_endpoint(method, path)` – full request/response schema for one endpoint.
  - `get_endpoints(tag)` or `get_endpoints(paths: [{method, path}, ...])` – bulk schema for up to 25 endpoints (by tag or explicit list).
  - `list_docs()` – list feature doc names from the DocsForMcp folder (when `docs_folder` is set). Returns `name` (filename without `.md`) and `summary` (first heading).
  - `get_doc(name)` – return the markdown content of a feature doc by name.
- **Resources**
  - `api://openapi/catalog` – servers + flat list of all operations (method, path, summary, operationId, tags) for fast scan.
  - `api://openapi` – full OpenAPI spec.

## Usage in AI IDEs (Cursor, etc.)

Point your IDE’s MCP config at your app’s MCP route. With **auth:sanctum** and a personal access token:

**Cursor** – add a server in `~/.cursor/mcp.json` (or project `.cursor/mcp.json`):

```json
{
  "mcpServers": {
    "your-app-api-docs": {
      "transport": "streamable-http",
      "url": "http://127.0.0.1:8000/mcp/api-docs",
      "headers": {
        "Authorization": "Bearer YOUR_SANCTUM_TOKEN"
      }
    }
  }
}
```

Replace `YOUR_SANCTUM_TOKEN` with a Laravel Sanctum personal access token (e.g. from `users` → create token). Use your real app URL if not local (e.g. `https://api.example.com/mcp/api-docs`). The server name (`your-app-api-docs`) is only a label.

## License

MIT.
