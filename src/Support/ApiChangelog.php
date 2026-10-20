<?php

namespace DhurGham\LaravelMcpApiDocs\Support;

/**
 * Reads the API changelog file produced at release time (oasdiff output grouped by release).
 *
 * Format: {"releases": [{"version", "date", "note"?, "changes": [{"method", "path", "level", "text"}]}]}
 * Releases are newest first.
 */
class ApiChangelog
{
    public static function isEnabled(): bool
    {
        $file = config('mcp-api-docs.changelog_file');

        return is_string($file) && $file !== '' && is_file($file);
    }

    public static function releases(): array
    {
        if (! self::isEnabled()) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents(config('mcp-api-docs.changelog_file')), true);

        return is_array($decoded['releases'] ?? null) ? $decoded['releases'] : [];
    }

    /**
     * Entries newer than $since (exclusive). Null returns all.
     *
     * Entries are newest first, so everything above the matching label is newer. This works for any
     * label ("#42", "v1.5.0", a commit hash). An unknown label falls back to a version comparison.
     */
    public static function since(?string $since): array
    {
        $releases = self::releases();
        if ($since === null || $since === '') {
            return $releases;
        }

        $normalize = fn ($v) => ltrim((string) $v, 'vV#');
        foreach ($releases as $i => $release) {
            if ($normalize($release['version'] ?? '') === $normalize($since)) {
                return array_slice($releases, 0, $i);
            }
        }

        return array_values(array_filter(
            $releases,
            fn ($r) => version_compare($normalize($r['version'] ?? ''), $normalize($since), '>')
        ));
    }

    /**
     * Changes for one endpoint, flattened with their release version/date, newest first.
     */
    public static function forEndpoint(string $method, string $path, int $limit = 10): array
    {
        $out = [];
        foreach (self::releases() as $release) {
            foreach ((array) ($release['changes'] ?? []) as $change) {
                if (strtoupper((string) ($change['method'] ?? '')) === strtoupper($method) && ($change['path'] ?? null) === $path) {
                    $out[] = [
                        'version' => $release['version'] ?? null,
                        'date' => $release['date'] ?? null,
                        'level' => $change['level'] ?? null,
                        'text' => $change['text'] ?? null,
                    ];
                }
            }
        }

        return array_slice($out, 0, $limit);
    }
}
