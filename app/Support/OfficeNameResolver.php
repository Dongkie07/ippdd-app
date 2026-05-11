<?php

namespace App\Support;

class OfficeNameResolver
{
    public static function normalize(?string $name): string
    {
        $name = strtoupper(trim((string) $name));
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = str_replace(['–', '—'], '-', $name);

        return $name;
    }

    public static function canonicalName(?string $name): string
    {
        $normalized = self::normalize($name);
        $aliases = config('office_aliases.aliases', []);

        return $aliases[$normalized] ?? $normalized;
    }

    public static function key(?string $name): string
    {
        $canonical = self::canonicalName($name);
        $key = preg_replace('/[^A-Z0-9]+/', '_', $canonical) ?? $canonical;
        $key = trim($key, '_');

        return $key ?: 'UNKNOWN_OFFICE';
    }

    public static function shouldSkip(?string $name): bool
    {
        $canonical = self::canonicalName($name);

        foreach (config('office_aliases.skip_patterns', []) as $pattern) {
            if (str_contains($canonical, self::normalize($pattern))) {
                return true;
            }
        }

        return false;
    }
}
