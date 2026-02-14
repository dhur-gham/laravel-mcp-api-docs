<?php

namespace DhurGham\LaravelMcpApiDocs\Support;

use Laravel\Mcp\Request;

class McpArgs
{
    public static function get(Request $request, string $key, mixed $default = null): mixed
    {
        // 1) Try common MCP request APIs across versions.
        foreach (['get', 'input', 'argument', 'arguments', 'param', 'params', 'data', 'json'] as $method) {
            if (method_exists($request, $method)) {
                try {
                    // Some versions accept a key, some return the full array.
                    $value = null;

                    try {
                        $value = $request->{$method}($key);
                        if ($value !== null) {
                            return $value;
                        }
                    } catch (\Throwable) {
                        // ignore; fall back to full payload
                    }

                    $value = $request->{$method}();

                    if (is_array($value) && array_key_exists($key, $value)) {
                        return $value[$key];
                    }
                } catch (\Throwable) {
                    // ignore and try next
                }
            }
        }

        // 2) Try common public properties (arguments/params/data/metadata).
        foreach (['arguments', 'params', 'data', 'meta', 'metadata'] as $prop) {
            if (isset($request->{$prop}) && is_array($request->{$prop}) && array_key_exists($key, $request->{$prop})) {
                return $request->{$prop}[$key];
            }
        }

        // 3) Try toArray() as a last resort, and look for nested locations too.
        try {
            $http = request(); // Illuminate\Http\Request

            // Inspector puts "Add Pair" values into params._meta.*
            $v = $http->input("params._meta.$key");
            if ($v !== null && $v !== '') {
                return $v;
            }

            // Normal tool arguments should be in params.arguments (usually an object)
            $args = $http->input('params.arguments');

            if (is_array($args)) {
                // If it's associative: {query: "..."}
                if (array_key_exists($key, $args)) {
                    return $args[$key];
                }

                // If it's a list of pairs: [["query","user"]] or [{"key":"query","value":"user"}]
                foreach ($args as $item) {
                    if (is_array($item)) {
                        if (($item['key'] ?? null) === $key) {
                            return $item['value'] ?? $default;
                        }
                        if (count($item) === 2 && ($item[0] ?? null) === $key) {
                            return $item[1] ?? $default;
                        }
                    }
                }
            }

            // Fallback direct
            $v = $http->input("params.$key");
            if ($v !== null && $v !== '') {
                return $v;
            }
        } catch (\Throwable) {
            // ignore
        }

        return $default;
    }
}
