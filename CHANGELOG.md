# Changelog

All notable changes to `dhur-gham/laravel-mcp-api-docs` will be documented in this file.

## Unreleased

### Added

* Per-endpoint API changelog: `bin/api-changelog.sh` builds `api-changelog.json` from an oasdiff diff of two specs; new `get_changelog(since?)` tool and a `changes` list in `get_endpoint` (config `changelog_file`)

### Changed

* Require `laravel/mcp` ^0.7.0 (0.x breaking bump) and fix README requirements (PHP 8.3+, laravel/mcp ^0.7.0)
* `OpenApiLoader` now checks the configured `openapi.url` before the local fallback files
* Loaded OpenAPI spec is cached (`openapi.cache_ttl`, default 60s, 0 disables)
* Server version reports `1.4.0`; instructions are built from parts instead of a regex
* `minimum-stability` set to `stable`

### Fixed

* `DocsFolderReader::listDocs` no longer calls `fgets` on an unchecked `fopen` handle

### Removed

* Leftover Spatie skeleton `configure.php`

## v1.4 - 2026-02-17

### What's Changed

* Add support for feature documentation in MCP API Docs by @dhur-gham in https://github.com/dhur-gham/laravel-mcp-api-docs/pull/3

**Full Changelog**: https://github.com/dhur-gham/laravel-mcp-api-docs/compare/1.3...1.4

## v1.3 - 2026-02-17

### What's Changed

* Enhance OpenApiLoader to support optional bearer token for authorization by @dhur-gham in https://github.com/dhur-gham/laravel-mcp-api-docs/pull/2

**Full Changelog**: https://github.com/dhur-gham/laravel-mcp-api-docs/compare/1.2...1.3

## v1.2 - 2026-02-14

- Introduced CatchMcpAuthExceptions middleware to enhance error handling.
- Updated middleware registration to include the new middleware alongside existing ones.

**Full Changelog**: https://github.com/dhur-gham/laravel-mcp-api-docs/compare/1.1...1.2

## v1.1 - 2026-02-14

### What's Changed

* Add list_tags, get_endpoints (bulk), and api://openapi/catalog for faster AI implementation by @dhur-gham in https://github.com/dhur-gham/laravel-mcp-api-docs/pull/1

**Full Changelog**: https://github.com/dhur-gham/laravel-mcp-api-docs/compare/1.0...1.1

## v1.0 - 2026-02-14

### releasing V1 of the package 🚀

**Full Changelog**: https://github.com/dhur-gham/laravel-mcp-api-docs/commits/1.0
