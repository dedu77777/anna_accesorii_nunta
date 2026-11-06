<?php

declare(strict_types=1);

namespace app\components;

use RuntimeException;
use Yii;

final class SiteRefresh
{
    private static function filePath(): string
    {
        return getenv('SITE_REFRESH_FILE') ?: Yii::getAlias('@runtime/site-refresh.version');
    }

    public static function version(): int
    {
        $path = self::filePath();
        if (!is_file($path)) {
            return 0;
        }

        $file = @fopen($path, 'rb');
        if ($file === false) {
            return 0;
        }

        try {
            if (!flock($file, LOCK_SH)) {
                return 0;
            }
            return max(0, (int) trim((string) stream_get_contents($file)));
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    public static function publish(): int
    {
        $file = @fopen(self::filePath(), 'c+');
        if ($file === false) {
            throw new RuntimeException('Fișierul de actualizare nu poate fi deschis.');
        }

        try {
            if (!flock($file, LOCK_EX)) {
                throw new RuntimeException('Actualizarea site-ului nu poate fi blocată.');
            }
            $version = max(0, (int) trim((string) stream_get_contents($file))) + 1;
            rewind($file);
            if (!ftruncate($file, 0) || fwrite($file, (string) $version) === false || !fflush($file)) {
                throw new RuntimeException('Actualizarea site-ului nu poate fi salvată.');
            }
            return $version;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
