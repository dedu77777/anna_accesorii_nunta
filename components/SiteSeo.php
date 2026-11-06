<?php

namespace app\components;

final class SiteSeo
{
    private const ORIGIN = 'https://annaaccesoriinunta.md';

    public static function url(string $path = '/', array $query = []): string
    {
        $url = self::ORIGIN . '/' . ltrim($path, '/');

        return $query === []
            ? $url
            : $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public static function productImage(?string $path): ?string
    {
        if (!$path || !preg_match('~\Aproducts/[a-zA-Z0-9/_-]+\.(?:png|jpe?g|webp)\z~D', $path)) {
            return null;
        }

        if (!is_file(dirname(__DIR__) . '/web/images/' . $path)) {
            return null;
        }

        return self::url('/images/' . implode('/', array_map('rawurlencode', explode('/', $path))));
    }
}
