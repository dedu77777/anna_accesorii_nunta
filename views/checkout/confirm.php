<?php

use yii\helpers\Html;

$this->title = 'Comandă înregistrată | Anna Accesorii Nuntă';
$this->params['robots'] = 'noindex, nofollow';
$this->registerCssFile('@web/css/checkout.css', ['depends' => [\app\assets\WebsiteAsset::class]]);
?>
<section class="checkout-confirm"><p class="checkout-eyebrow">Comandă înregistrată</p><h1>Vă mulțumim pentru comandă</h1><p>Comanda dumneavoastră a fost înregistrată. Vă vom contacta telefonic pentru confirmarea detaliilor înainte de procesare.</p><dl><div><dt>Numărul comenzii</dt><dd>#<?= Html::encode($order->order_number) ?></dd></div><div><dt>Total produse</dt><dd><?= number_format((float) $order->total, 2, ',', ' ') ?> MDL</dd></div><div><dt>Livrare</dt><dd><?= $order->delivery_method === 'pickup' ? 'Ridicare din magazin' : 'Livrare la domiciliu' ?></dd></div><div><dt>Status</dt><dd>În așteptarea confirmării telefonice</dd></div></dl><p>Costul livrării, dacă se aplică, va fi confirmat separat.</p><?= Html::a('Înapoi la pagina principală', ['/website/home'], ['class' => 'checkout-submit']) ?></section>
