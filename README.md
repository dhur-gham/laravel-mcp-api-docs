# Laravel MCP API Docs

Exposes your app’s OpenAPI spec to Laravel MCP via tools and a resource so the AI uses the canonical API contract (no guessing endpoints or payloads).

## Requirements

- PHP 8.3+
- Laravel 12
- [laravel/mcp](https://github.com/laravel/mcp) ^0.7.0 up to ^1.0

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
  - `get_changelog(since?)` – API changes per merged PR, newest first (when the [API changelog](#api-changelog-optional) is set up). `get_endpoint` also returns a `changes` list for that endpoint.
- **Resources**
  - `api://openapi/catalog` – servers + flat list of all operations (method, path, summary, operationId, tags) for fast scan.
  - `api://openapi` – full OpenAPI spec.

## API changelog (optional)

Tells API consumers what changed on each endpoint. Every pull request that changes your API gets one entry in `api-changelog.json`, written by CI and reviewed in the PR like any other file. Nobody writes entries by hand: CI compares `openapi.json` before and after the PR with [oasdiff](https://github.com/oasdiff/oasdiff) and labels each change `breaking`, `deprecation` or `non-breaking`.

It is off until you set it up. Updating the package alone changes nothing.

### Quick start

```bash
php artisan scramble:export --path=openapi.json   # 1. commit your OpenAPI spec (any tool works)
php artisan mcp-api-docs:install-changelog        # 2. answer the questions, it writes the workflow
git add openapi.json .github/workflows/api-changelog.yml
git commit -m "Add API changelog" && git push      # 3. merge this to your default branch
```

```env
# 4. tell the package where the changelog lives
MCP_API_DOCS_CHANGELOG_FILE=/path/to/app/api-changelog.json
```

That's it. Open a PR that changes the API and the bot adds its entry. The details for each step follow.

### Setup

1. **Commit `openapi.json`** at your repo root (for example `php artisan scramble:export --path=openapi.json`).
2. **Add the workflow.** The installer asks how `openapi.json` is kept up to date and writes `.github/workflows/api-changelog.yml` with the matching settings:

   ```bash
   php artisan mcp-api-docs:install-changelog
   ```

   - **I commit openapi.json myself:** use any tool; the bot reads the committed file.
   - **Scramble:** the bot regenerates `openapi.json` on every push, so a forgotten export is fixed for you. You also pick the database your migrations run on (MySQL/MariaDB, PostgreSQL or SQLite); CI migrates a throwaway copy of it to export the spec, adding about a minute per run. Export once on your default branch first, so the first PR's entry does not include older, unexported changes.

   Without questions: `php artisan mcp-api-docs:install-changelog --export=scramble --db=mysql` (or `--export=manual`). Run it again to switch modes. Prefer copying the file as is: `php artisan vendor:publish --tag="laravel-mcp-api-docs-changelog-workflow"`.

   Merge the workflow to your default branch; the `/update-mcp-changelog` command only works from there.
3. **Point the package at the file:**

   ```env
   MCP_API_DOCS_CHANGELOG_FILE=/path/to/app/api-changelog.json
   ```

### What happens on a pull request

| When | What the bot does |
|---|---|
| PR opened or pushed (not draft) | Commits the `#<PR number>` entry to `api-changelog.json` on the PR branch and comments the changes, flagging breaking ones. |
| More commits on the same PR | Rebuilds the same entry from the PR's net change. A reverted change removes it. |
| Draft PR | Nothing, until you mark it ready for review. |
| PR has no API change | Nothing. No entry, no comment. |
| PR conflicts because another PR merged first | Comment `/update-mcp-changelog`. The bot merges your default branch, puts this entry on top, and the PR becomes mergeable. |
| Merge | Nothing more. The entry is already in the PR, and the bot never pushes to your default branch. |

Pull after the bot commits, like with a code-style bot.

### What consumers get

- `get_endpoint(method, path)` includes a `changes` list for that endpoint: entry label, date, level and text.
- `get_changelog(since?)` lists entries newest first. Pass a label such as `"#41"` to get only newer ones.

### Good to know

- Only changes visible in `openapi.json` are detected. A behaviour change with the same schema is not.
- Without auto-export, if a PR changes the API but `openapi.json` is not regenerated, no entry is created.
- Only repo owners, members and collaborators can run `/update-mcp-changelog`. Pull requests from forks are skipped.
- Bot commits made with the default `GITHUB_TOKEN` do not trigger your other workflows. Add a `CHANGELOG_TOKEN` secret (a fine-grained token with contents and pull-requests write) if your tests must run on them.
- `bin/api-changelog.sh` does the diff and can be run by hand (needs oasdiff and jq):

  ```bash
  bin/api-changelog.sh old-openapi.json openapi.json "#42" api-changelog.json "Add coupons to orders"
  bin/api-changelog.sh --markdown old-openapi.json openapi.json   # print the changes as a table
  ```

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
