<?php
$method = $order->delivery_method === 'pickup' ? 'Ridicare din magazin' : 'Livrare la domiciliu';
?>
ANNA ACCESORII NUNTĂ
Comanda dumneavoastră a fost înregistrată. Vă vom contacta telefonic pentru confirmarea detaliilor înainte de procesare.

Comanda #<?= $order->order_number ?>
Data: <?= date('d.m.Y H:i', (int) $order->created_at) ?>
Status: În așteptarea confirmării telefonice

<?php foreach ($order->items as $item): ?>
<?= $item->product_title ?> — <?= (int) $item->quantity ?> × <?= $item->unit_price ?> MDL = <?= $item->line_total ?> MDL
<?php endforeach; ?>
Subtotal produse: <?= $order->subtotal ?> MDL
Total produse: <?= $order->total ?> MDL
Livrare: <?= $method ?>
<?php if ($order->delivery_address): ?>Adresă: <?= $order->delivery_address ?><?php endif; ?>
<?php if ($order->additional_information): ?>Informații suplimentare: <?= $order->additional_information ?><?php endif; ?>
Costul livrării, dacă se aplică, va fi confirmat separat.

Anna Accesorii Nuntă · Vă mulțumim pentru comandă.
