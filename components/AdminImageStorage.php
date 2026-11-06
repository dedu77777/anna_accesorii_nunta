<?php

declare(strict_types=1);

namespace app\components;

use RuntimeException;
use Yii;
use yii\web\UploadedFile;

final class AdminImageStorage
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function isValidImage(UploadedFile $file): bool
    {
        if ($file->error !== UPLOAD_ERR_OK || $file->size < 1 || $file->size > self::MAX_BYTES) {
            return false;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->tempName);
        return isset(self::TYPES[$mime]) && @getimagesize($file->tempName) !== false;
    }

    /** @param UploadedFile[] $files
     *  @return string[] Paths relative to web/images.
     */
    public function storeProducts(array $files): array
    {
        $paths = [];
        try {
            foreach ($files as $file) {
                $paths[] = $this->store($file, 'products/admin');
            }
            return $paths;
        } catch (\Throwable $error) {
            foreach ($paths as $path) {
                $this->deleteManagedProduct($path);
            }
            throw $error;
        }
    }

    public function storeCategory(UploadedFile $file): string
    {
        return basename($this->store($file, 'categories', 'admin-'));
    }

    public function deleteManagedProduct(string $path): void
    {
        if (preg_match('~^products/admin/[a-f0-9]{32}\.(?:jpg|png|webp)$~D', $path)) {
            $this->deleteAt('products/admin', basename($path));
        }
    }

    public function deleteManagedCategory(string $name): void
    {
        if (preg_match('~^admin-[a-f0-9]{32}\.(?:jpg|png|webp)$~D', $name)) {
            $this->deleteAt('categories', $name);
        }
    }

    private function store(UploadedFile $file, string $folder, string $prefix = ''): string
    {
        if (!self::isValidImage($file)) {
            throw new RuntimeException('Imagine invalidă.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->tempName);
        $directory = Yii::getAlias('@webroot/images/' . $folder);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Directorul imaginilor nu poate fi creat.');
        }
        $name = $prefix . bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        if (!$file->saveAs($directory . DIRECTORY_SEPARATOR . $name)) {
            throw new RuntimeException('Imaginea nu poate fi salvată.');
        }
        return $folder . '/' . $name;
    }

    private function deleteAt(string $folder, string $name): void
    {
        $directory = realpath(Yii::getAlias('@webroot/images/' . $folder));
        if ($directory === false) {
            return;
        }
        $path = realpath($directory . DIRECTORY_SEPARATOR . $name);
        if ($path !== false && dirname($path) === $directory && is_file($path)) {
            @unlink($path);
        }
    }
}
