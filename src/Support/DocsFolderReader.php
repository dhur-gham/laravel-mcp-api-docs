<?php

namespace DhurGham\LaravelMcpApiDocs\Support;

use Illuminate\Support\Str;

class DocsFolderReader
{
    public static function isEnabled(): bool
    {
        $path = config('mcp-api-docs.docs_folder');
        if ($path === null || $path === '') {
            return false;
        }
        $dir = self::resolvePath($path);

        return is_dir($dir);
    }

    public static function resolvePath(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }
        $path = trim($path);
        if ($path === '' || Str::startsWith($path, '/') || preg_match('#^[A-Za-z]:\\\\#', $path)) {
            return $path;
        }

        return rtrim(base_path($path), DIRECTORY_SEPARATOR);
    }

    public static function listDocs(): array
    {
        $path = config('mcp-api-docs.docs_folder');
        if ($path === null || $path === '') {
            return [];
        }
        $dir = self::resolvePath($path);
        if (! is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*.md') ?: [] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $firstLine = '';
            if (is_readable($file)) {
                $first = fgets($h = fopen($file, 'r'));
                if ($first !== false) {
                    $firstLine = trim(preg_replace('/^#+\s*/', '', $first));
                }
                if (isset($h)) {
                    fclose($h);
                }
            }
            $out[] = ['name' => $name, 'summary' => $firstLine ?: null];
        }
        usort($out, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $out;
    }

    public static function getDoc(string $name): ?string
    {
        $path = config('mcp-api-docs.docs_folder');
        if ($path === null || $path === '') {
            return null;
        }
        $dir = self::resolvePath($path);
        if (! is_dir($dir)) {
            return null;
        }
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
        if ($safe !== $name) {
            return null;
        }
        $file = $dir.DIRECTORY_SEPARATOR.$name.'.md';
        if (! is_file($file) || ! is_readable($file)) {
            return null;
        }
        $content = file_get_contents($file);

        return $content !== false ? $content : null;
    }
}
