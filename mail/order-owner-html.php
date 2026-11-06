<?php

use yii\helpers\Html;

/** @var app\models\Order $order */
/** @var array<int, string> $images */

$e = static fn ($value) => Html::encode((string) $value);
$method = $order->delivery_method === 'pickup'
    ? 'Ridicare din magazin'
    : 'Livrare la domiciliu';
?>
<table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="background:#f7f4f0;font-family:Arial,sans-serif;color:#292625">
    <tr><td align="center" style="padding:24px 12px">
        <table role="presentation" cellspacing="0" cellpadding="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e8e1d9">
            <tr><td style="padding:25px 30px;border-bottom:1px solid #e8e1d9;font:20px Georgia,serif">ANNA ACCESORII NUNTĂ</td></tr>
            <tr><td style="padding:28px 30px">
                <h1 style="font:normal 27px Georgia,serif;margin:0 0 8px">Comandă nouă #<?= $e($order->order_number) ?></h1>
                <p style="font-size:13px;color:#736960;margin:0 0 24px">Data: <?= $e(date('d.m.Y H:i', (int) $order->created_at)) ?> · Necesită confirmare telefonică</p>

                <p style="line-height:1.7;margin:0 0 22px">
                    <strong>Client:</strong> <?= $e($order->full_name) ?><br>
                    <strong>Telefon:</strong> <a href="tel:<?= $e($order->phone_number) ?>" style="color:#704d40"><?= $e($order->phone_number) ?></a><br>
                    <strong>Email:</strong> <?= $e($order->email) ?><br>
                    <strong>Livrare:</strong> <?= $e($method) ?>
                    <?php if ($order->delivery_address): ?><br><strong>Adresă:</strong> <?= $e($order->delivery_address) ?><?php endif; ?>
                    <?php if ($order->additional_information): ?><br><strong>Note:</strong> <?= nl2br($e($order->additional_information)) ?><?php endif; ?>
                </p>

                <h2 style="font:normal 20px Georgia,serif;margin:0 0 8px">Produse comandate</h2>
                <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="border-top:1px solid #e8e1d9">
                    <?php foreach ($order->items as $index => $item): ?>
                        <tr>
                            <td width="88" valign="top" style="padding:16px 12px 16px 0;border-bottom:1px solid #eee9e3">
                                <?php if (isset($images[$index])): ?>
                                    <img src="<?= $e($images[$index]) ?>" alt="<?= $e($item->product_title) ?>" width="76" height="76" style="display:block;border:0;width:76px;height:76px">
                                <?php else: ?>
                                    <span style="display:block;width:76px;font-size:12px;line-height:1.4;color:#80766e">Imagine indisponibilă</span>
                                <?php endif; ?>
                            </td>
                            <td valign="top" style="padding:16px 0;border-bottom:1px solid #eee9e3;line-height:1.55">
                                <strong style="font-size:15px"><?= $e($item->product_title) ?></strong><br>
                                <span style="font-size:13px;color:#6e655e">Cantitate: <?= (int) $item->quantity ?></span><br>
                                <span style="font-size:13px;color:#6e655e">Preț unitar: <?= $e($item->unit_price) ?> MDL</span><br>
                                <strong style="font-size:13px">Total produs: <?= $e($item->line_total) ?> MDL</strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <p style="text-align:right;font-size:17px;margin:22px 0 0"><strong>Total comandă: <?= $e($order->total) ?> MDL</strong></p>
            </td></tr>
            <tr><td style="padding:18px 30px;background:#f7f4f0;color:#736960;font-size:12px">Anna Accesorii Nuntă · Notificare comandă nouă</td></tr>
        </table>
    </td></tr>
</table>
