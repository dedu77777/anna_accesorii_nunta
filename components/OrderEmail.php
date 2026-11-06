<?php

declare(strict_types=1);

namespace app\components;

use app\models\Order;
use Yii;

final class OrderEmail
{
    /** Create a compact JPEG preview for an owner notification. */
    private static function ownerThumbnail(?string $path): ?string
    {
        if (
            !$path
            || !preg_match('~\Aproducts/[a-zA-Z0-9/_-]+\.(?:png|jpe?g|webp)\z~D', $path)
            || !function_exists('imagecreatefromstring')
        ) {
            return null;
        }

        $file = dirname(__DIR__) . '/web/images/' . $path;
        if (!is_file($file) || !is_readable($file) || filesize($file) > 12 * 1024 * 1024) {
            return null;
        }

        $size = @getimagesize($file);
        if (
            !$size
            || $size[0] < 1 || $size[1] < 1
            || $size[0] * $size[1] > 20000000
            || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
        ) {
            return null;
        }

        $source = @imagecreatefromstring((string) @file_get_contents($file));
        if (!$source) {
            return null;
        }

        $preview = imagecreatetruecolor(144, 144);
        $background = imagecolorallocate($preview, 255, 255, 255);
        imagefill($preview, 0, 0, $background);
        $scale = min(144 / $size[0], 144 / $size[1]);
        $width = max(1, (int) round($size[0] * $scale));
        $height = max(1, (int) round($size[1] * $scale));
        imagecopyresampled($preview, $source,
            (int) floor((144 - $width) / 2), (int) floor((144 - $height) / 2),
            0, 0, $width, $height, $size[0], $size[1]);

        ob_start();
        imagejpeg($preview, null, 82);
        $jpeg = ob_get_clean();

        return is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }

    /**
     * Generate a public HTTPS URL for a product image.
     */
    public static function imageUrl(?string $path): ?string
    {
        $base = rtrim((string) getenv('PUBLIC_SITE_URL'), '/');

        if (
            !preg_match('~\Ahttps://[a-z0-9.-]+(?::443)?\z~iD', $base)
            || !$path
            || !preg_match(
                '~\Aproducts/[a-zA-Z0-9/_-]+\.(?:png|jpe?g|webp)\z~D',
                $path
            )
            || !is_file(dirname(__DIR__) . '/web/images/' . $path)
        ) {
            return null;
        }

        return $base . '/images/' . implode(
            '/',
            array_map('rawurlencode', explode('/', $path))
        );
    }

    /**
     * Send an order notification.
     *
     * Development:
     * Uses Yii2 file transport when SMTP is not configured.
     *
     * Production:
     * Requires a configured SMTP transport.
     */
    public static function send(Order $order, string $kind): bool
    {
        if (!in_array($kind, ['customer', 'owner'], true)) {
            throw new \InvalidArgumentException('Invalid email kind.');
        }

        // Require real SMTP in production.
        if (YII_ENV === 'prod' && !getenv('SMTP_DSN')) {
            throw new \RuntimeException(
                'SMTP transport must be configured in production.'
            );
        }

        $from = (string) getenv('ORDER_FROM_EMAIL');
        $owner = (string) getenv('ORDER_OWNER_EMAIL');

        if (
            !filter_var($from, FILTER_VALIDATE_EMAIL)
            || !filter_var($owner, FILTER_VALIDATE_EMAIL)
        ) {
            throw new \RuntimeException(
                'Order email addresses are not configured.'
            );
        }

        $order->populateRelation('items', $order->items);

        $isCustomer = $kind === 'customer';

        $recipient = $isCustomer
            ? $order->email
            : $owner;

        $subject = $isCustomer
            ? 'Confirmarea primirii comenzii #' . $order->order_number
            : 'Comandă nouă #' . $order->order_number
                . ' — Necesită confirmare telefonică';

        $images = [];
        $inlineImages = [];
        if (!$isCustomer) {
            foreach ($order->items as $index => $item) {
                $thumbnail = self::ownerThumbnail($item->product_image);
                if ($thumbnail !== null) {
                    $name = 'order-product-' . $index . '@anna-accesorii-nunta';
                    $images[$index] = 'cid:' . $name;
                    $inlineImages[$name] = $thumbnail;
                } else {
                    $images[$index] = self::imageUrl($item->product_image);
                }
            }
        }

        $message = Yii::$app->mailer->compose(
            [
                'html' => $isCustomer
                    ? 'order-customer-html'
                    : 'order-owner-html',

                'text' => $isCustomer
                    ? 'order-customer-text'
                    : 'order-owner-text',
            ],
            [
                'order' => $order,
                'images' => $images,
            ]
        );

        foreach ($inlineImages as $name => $thumbnail) {
            $message->embedContent($thumbnail, [
                'fileName' => $name,
                'contentType' => 'image/jpeg',
            ]);
        }

        return $message
            ->setFrom($from)
            ->setTo($recipient)
            ->setSubject($subject)
            ->send();
    }
}
