
<?php

/** @var app\models\Order $order */

$method = $order->delivery_method === 'pickup'
    ? 'Ridicare din magazin'
    : 'Livrare la domiciliu';

?>
Comandă nouă #<?= $order->order_number ?>

Data: <?= date('d.m.Y H:i', (int) $order->created_at) ?>


Nume, Prenume: <?= $order->full_name ?>

Nr. telefon: <?= $order->phone_number ?>

Email: <?= $order->email ?>


Livrare: <?= $method ?>

<?php if ($order->delivery_address): ?>
Adresă: <?= $order->delivery_address ?>

<?php endif; ?>
<?php if ($order->additional_information): ?>
Note: <?= $order->additional_information ?>

<?php endif; ?>
Produse:

<?php foreach ($order->items as $item): ?>
- <?= $item->product_title ?> — <?= (int) $item->quantity ?> × <?= $item->unit_price ?> MDL = <?= $item->line_total ?> MDL
<?php endforeach; ?>

Total produse: <?= $order->total ?> MDL
