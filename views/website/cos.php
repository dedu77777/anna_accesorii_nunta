<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\CartItem[] $items */
/** @var float $total */
/** @var int $cartCount */

$this->title = 'Coșul meu | Anna Accesorii Nuntă';
$this->params['robots'] = 'noindex, follow';
$this->registerCssFile('@web/css/cart.css', ['depends' => [\app\assets\WebsiteAsset::class]]);
?>

<section class="cart-page"
    data-update-url="<?= Html::encode(Url::to(['/website/actualizeaza-cos'])) ?>"
    data-delete-url="<?= Html::encode(Url::to(['/website/sterge-din-cos'])) ?>"
    data-csrf-param="<?= Html::encode(Yii::$app->request->csrfParam) ?>"
    data-csrf-token="<?= Html::encode(Yii::$app->request->getCsrfToken()) ?>">
    <div class="cart-container">
        <header class="cart-heading">
            <div>
                <h1>Coșul meu</h1>
                <p>Verifică produsele selectate înainte de comandă.</p>
            </div>
            <?= Html::a('Continuă cumpărăturile', ['/website/produse'], ['class' => 'cart-continue']) ?>
        </header>

        <section class="cart-empty" <?= $cartCount > 0 ? 'hidden' : '' ?>>
            <div class="cart-empty-icon" aria-hidden="true">
                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 7h12l1 14H5L6 7Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>
                </svg>
            </div>
            <h2>Coșul tău este gol</h2>
            <p>Nu ai adăugat încă produse în coș.</p>
            <?= Html::a('Descoperă produsele', ['/website/produse'], ['class' => 'cart-primary-button']) ?>
        </section>

        <div class="cart-layout" <?= $cartCount === 0 ? 'hidden' : '' ?>>
            <section class="cart-items" aria-label="Produse în coș">
                <div class="cart-items-heading">
                    <h2>Produse selectate</h2>
                    <span><span data-cart-count><?= (int) $cartCount ?></span> <span data-cart-count-label><?= $cartCount === 1 ? 'produs' : 'produse' ?></span></span>
                </div>

                <?php foreach ($items as $item): ?>
                    <?php
                    $product = $item->product;
                    if ($product === null) continue;
                    $quantity = (int) $item->quantity;
                    $price = (float) $product->price;
                    $productUrl = Url::to(['/website/detalii-produs', 'id' => $product->id]);
                    $imageUrl = !empty($product->image)
                        ? Url::to('@web/images/' . ltrim($product->image, '/'))
                        : null;
                    ?>
                    <article class="cart-item" data-cart-item-id="<?= (int) $item->id ?>">
                        <a href="<?= Html::encode($productUrl) ?>" class="cart-item-image">
                            <?php if ($imageUrl !== null): ?>
                                <img src="<?= Html::encode($imageUrl) ?>" alt="<?= Html::encode($product->title) ?>" loading="lazy">
                            <?php else: ?>
                                <span class="cart-image-placeholder">Fără imagine</span>
                            <?php endif; ?>
                        </a>
                        <div class="cart-item-info">
                            <div class="cart-item-top">
                                <h3><?= Html::a(Html::encode($product->title), $productUrl, ['encode' => false]) ?></h3>
                                <button type="button" class="cart-remove-button" data-remove-item aria-label="Elimină produsul din coș" title="Elimină produsul din coș">
                                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </div>
                            <?php if (!empty($product->subtitle)): ?>
                                <p class="cart-item-subtitle"><?= Html::encode($product->subtitle) ?></p>
                            <?php endif; ?>
                            <p class="cart-item-unit-price"><?= number_format($price, 2, ',', ' ') ?> MDL / buc.</p>
                            <div class="cart-item-bottom">
                                <div class="cart-quantity" role="group" aria-label="Cantitate pentru <?= Html::encode($product->title) ?>">
                                    <button type="button" data-quantity-minus aria-label="Scade cantitatea">−</button>
                                    <span data-item-quantity aria-live="polite"><?= $quantity ?></span>
                                    <button type="button" data-quantity-plus aria-label="Crește cantitatea">+</button>
                                </div>
                                <strong class="cart-item-subtotal" data-item-subtotal><?= number_format($price * $quantity, 2, ',', ' ') ?> MDL</strong>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="cart-summary">
                <h2>Sumarul comenzii</h2>
                <div class="cart-summary-row"><span>Produse</span><span data-cart-count><?= (int) $cartCount ?></span></div>
                <div class="cart-summary-row"><span>Subtotal</span><span data-cart-total><?= number_format($total, 2, ',', ' ') ?> MDL</span></div>
                <p class="cart-delivery-note">Detaliile și costul livrării vor fi confirmate la procesarea comenzii.</p>
                <div class="cart-summary-total"><span>Total produse</span><strong data-cart-total><?= number_format($total, 2, ',', ' ') ?> MDL</strong></div>
                <?= Html::a('Continuă spre comandă', ['/checkout/index'], ['class' => 'cart-checkout-button']) ?>
            </aside>
        </div>
    </div>
</section>
