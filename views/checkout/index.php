
<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\assets\WebsiteAsset;

/**
 * @var yii\web\View $this
 * @var app\models\CheckoutForm $form
 * @var app\models\CartItem[] $items
 */

$this->title = 'Plasează comanda | Anna Accesorii Nuntă';
$this->params['robots'] = 'noindex, nofollow';

$this->registerCssFile('@web/css/checkout.css', [
    'depends' => [WebsiteAsset::class],
]);

$total = 0.0;
$productCount = 0;
?>

<main class="checkout-page">
    <div class="checkout-container">

        <nav class="checkout-breadcrumb" aria-label="Navigare">
            <?= Html::a('Coș', ['/website/cos']) ?>
            <span aria-hidden="true">›</span>
            <span>Plasează comanda</span>
        </nav>

        <div class="checkout-grid">

            <!-- ORDER SUMMARY -->
            <aside class="checkout-summary">

                <div class="checkout-summary-header">
                    <h2>Rezumat comandă</h2>

                    <?= Html::a(
                        'Editează coș',
                        ['/website/cos'],
                        ['class' => 'checkout-edit-cart']
                    ) ?>
                </div>

                <div class="checkout-summary-products">

                    <?php foreach ($items as $item): ?>

                        <?php
                        $product = $item->product;

                        if (!$product) {
                            continue;
                        }

                        $quantity = (int) $item->quantity;
                        $price = (float) $product->price;
                        $lineTotal = $price * $quantity;

                        $total += $lineTotal;
                        $productCount += $quantity;

                        $imagePath = $product->image
                            ? Url::to('@web/images/' . ltrim($product->image, '/'))
                            : null;
                        ?>

                        <article class="checkout-product">

                            <div class="checkout-product-image">
                                <?php if ($imagePath): ?>
                                    <img
                                        src="<?= Html::encode($imagePath) ?>"
                                        alt="<?= Html::encode($product->title) ?>"
                                        width="96"
                                        height="104"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="checkout-image-placeholder">
                                        ANNA
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="checkout-product-details">

                                <h3>
                                    <?= Html::encode($product->title) ?>
                                </h3>

                                <?php if (!empty($product->subtitle)): ?>
                                    <p class="checkout-product-subtitle">
                                        <?= Html::encode($product->subtitle) ?>
                                    </p>
                                <?php endif; ?>

                                <p class="checkout-product-quantity">
                                    Cantitate: <?= $quantity ?>
                                </p>

                                <strong class="checkout-product-price">
                                    <?= number_format($lineTotal, 2, ',', ' ') ?> MDL
                                </strong>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <div class="checkout-summary-totals">

                    <div class="checkout-summary-row">
                        <span>Produse (<?= $productCount ?>)</span>
                        <span><?= number_format($total, 2, ',', ' ') ?> MDL</span>
                    </div>

                    <div class="checkout-summary-row">
                        <span>Livrare</span>
                        <span>Se confirmă telefonic</span>
                    </div>

                    <div class="checkout-summary-total">
                        <strong>Total produse</strong>
                        <strong><?= number_format($total, 2, ',', ' ') ?> MDL</strong>
                    </div>

                    <p class="checkout-total-note">
                        Eventualele costuri de livrare vor fi comunicate înainte de confirmarea comenzii.
                    </p>

                </div>

                <div class="checkout-benefits">

                    <div class="checkout-benefit">
                        <span class="checkout-benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h11v11H3z"/>
                                <path d="M14 10h4l3 4v3h-7z"/>
                                <circle cx="7" cy="18" r="2"/>
                                <circle cx="18" cy="18" r="2"/>
                            </svg>
                        </span>
                        <div>
                            <strong>Livrare la domiciliu</strong>
                            <p>Detaliile livrării se stabilesc la confirmarea comenzii.</p>
                        </div>
                    </div>

                    <div class="checkout-benefit">
                        <span class="checkout-benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.7L7 10a16 16 0 0 0 7 7l1.6-2a2 2 0 0 1 1.7-.6l3 .5a2 2 0 0 1 1.7 2z"/>
                            </svg>
                        </span>
                        <div>
                            <strong>Vei fi contactată</strong>
                            <p>Te sunăm pentru confirmarea comenzii.</p>
                        </div>
                    </div>

                    <div class="checkout-benefit">
                        <span class="checkout-benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </span>
                        <div>
                            <strong>Fără plată online</strong>
                            <p>Plata și modalitatea de achitare se confirmă telefonic.</p>
                        </div>
                    </div>

                </div>

            </aside>

            <!-- CHECKOUT FORM -->
            <section class="checkout-panel">

                <form
                    id="checkout-form"
                    action="<?= Html::encode(Url::to(['/checkout/submit'])) ?>"
                    method="post"
                    data-checkout-form
                >

                    <?= Html::hiddenInput(
                        Yii::$app->request->csrfParam,
                        Yii::$app->request->getCsrfToken()
                    ) ?>

                    <?= Html::hiddenInput(
                        'idempotency_key',
                        $form->idempotency_key
                    ) ?>

                    <?php if ($form->hasErrors()): ?>
                        <div class="checkout-alert" role="alert">
                            Verifică informațiile introduse și încearcă din nou.
                        </div>
                    <?php endif; ?>

                    <!-- SECTION 1 -->
                    <fieldset class="checkout-section">

                        <legend class="checkout-section-title">
                            1. Detalii personale
                        </legend>

                        <p class="checkout-section-description">
                            Completează informațiile pentru comandă.
                        </p>

                        <div class="checkout-field">
                            <label for="full_name">
                                Nume complet <span class="required">*</span>
                            </label>

                            <?= Html::textInput(
                                'full_name',
                                $form->full_name,
                                [
                                    'id' => 'full_name',
                                    'class' => 'checkout-input',
                                    'placeholder' => 'Ex. Popescu Ana Maria',
                                    'autocomplete' => 'name',
                                    'maxlength' => 120,
                                    'required' => true,
                                ]
                            ) ?>

                            <?= Html::error($form, 'full_name', [
                                'class' => 'checkout-field-error',
                            ]) ?>
                        </div>

                        <div class="checkout-two-columns">

                            <div class="checkout-field">
                                <label for="email">
                                    Email <span class="required">*</span>
                                </label>

                                <?= Html::input(
                                    'email',
                                    'email',
                                    $form->email,
                                    [
                                        'id' => 'email',
                                        'class' => 'checkout-input',
                                        'placeholder' => 'Ex. ana@email.md',
                                        'autocomplete' => 'email',
                                        'maxlength' => 254,
                                        'required' => true,
                                    ]
                                ) ?>

                                <?= Html::error($form, 'email', [
                                    'class' => 'checkout-field-error',
                                ]) ?>
                            </div>

                            <div class="checkout-field">
                                <label for="phone_number">
                                    Număr de telefon <span class="required">*</span>
                                </label>

                                <?= Html::input(
                                    'tel',
                                    'phone_number',
                                    $form->phone_number,
                                    [
                                        'id' => 'phone_number',
                                        'class' => 'checkout-input',
                                        'placeholder' => 'Ex. +373 69 123 456',
                                        'autocomplete' => 'tel',
                                        'inputmode' => 'tel',
                                        'maxlength' => 24,
                                        'required' => true,
                                    ]
                                ) ?>

                                <?= Html::error($form, 'phone_number', [
                                    'class' => 'checkout-field-error',
                                ]) ?>
                            </div>

                        </div>

                    </fieldset>

                    <!-- SECTION 2 -->
                    <fieldset class="checkout-section">

                        <legend class="checkout-section-title">
                            2. Livrare
                        </legend>

                        <p class="checkout-section-description">
                            Alege cum dorești să primești comanda.
                        </p>

                        <div class="checkout-delivery-options">

                            <label class="checkout-delivery-option">
                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="pickup"
                                    <?= $form->delivery_method === 'pickup'
                                        ? 'checked'
                                        : '' ?>
                                    required
                                >
                                <span>Ridicare din magazin</span>
                            </label>

                            <label class="checkout-delivery-option">
                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="home_delivery"
                                    <?= $form->delivery_method === 'home_delivery'
                                        ? 'checked'
                                        : '' ?>
                                    required
                                >
                                <span>Livrare la domiciliu</span>
                            </label>

                        </div>

                        <?= Html::error($form, 'delivery_method', [
                            'class' => 'checkout-field-error',
                        ]) ?>

                        <div
                            class="checkout-field checkout-address"
                            data-delivery-address
                            <?= $form->delivery_method === 'home_delivery'
                                ? ''
                                : 'hidden' ?>
                        >
                            <label for="delivery_address">
                                Adresă completă <span class="required">*</span>
                            </label>

                            <?= Html::textarea(
                                'delivery_address',
                                $form->delivery_address,
                                [
                                    'id' => 'delivery_address',
                                    'class' => 'checkout-input checkout-textarea',
                                    'placeholder' => 'Ex. Str. Alba Iulia 123, ap. 5, Chișinău',
                                    'autocomplete' => 'street-address',
                                    'rows' => 2,
                                    'maxlength' => 500,
                                    'required' => $form->delivery_method === 'home_delivery',
                                ]
                            ) ?>

                            <p class="checkout-field-hint">
                                Include strada, numărul, blocul, apartamentul și alte detalii relevante.
                            </p>

                            <?= Html::error($form, 'delivery_address', [
                                'class' => 'checkout-field-error',
                            ]) ?>
                        </div>

                    </fieldset>

                    <!-- SECTION 3 -->
                    <fieldset class="checkout-section checkout-section-last">

                        <legend class="checkout-section-title">
                            3. Informații suplimentare
                            <span class="checkout-optional">(opțional)</span>
                        </legend>

                        <div class="checkout-field">
                            <label for="additional_information">
                                Observații pentru comandă
                            </label>

                            <?= Html::textarea(
                                'additional_information',
                                $form->additional_information,
                                [
                                    'id' => 'additional_information',
                                    'class' => 'checkout-input checkout-textarea',
                                    'placeholder' => 'Ex. Prefer să fiu sunată înainte de livrare',
                                    'rows' => 3,
                                    'maxlength' => 2000,
                                ]
                            ) ?>

                            <?= Html::error($form, 'additional_information', [
                                'class' => 'checkout-field-error',
                            ]) ?>
                        </div>

                    </fieldset>

                    <!-- PRIVACY -->
                    <div class="checkout-privacy">

                        <label class="checkout-checkbox-label">

                            <input
                                type="checkbox"
                                name="privacy_accepted"
                                value="1"
                                <?= $form->privacy_accepted === '1'
                                    ? 'checked'
                                    : '' ?>
                                required
                            >

                            <span>
                                Accept
                                <?= Html::a(
                                    'Politica de confidențialitate',
                                    ['/checkout/privacy'],
                                    [
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer',
                                    ]
                                ) ?>
                                pentru prelucrarea datelor necesare comenzii.
                                <span class="required">*</span>
                            </span>

                        </label>

                        <?= Html::error($form, 'privacy_accepted', [
                            'class' => 'checkout-field-error',
                        ]) ?>

                    </div>

                    <!-- SUBMIT -->
                    <button
                        type="submit"
                        class="checkout-submit"
                        data-submit-button
                    >
                        <span>Plasează comanda</span>
                        <span class="checkout-submit-arrow" aria-hidden="true">›</span>
                    </button>

                    <p class="checkout-security-note">
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <rect x="5" y="10" width="14" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        Comanda va fi confirmată telefonic înainte de procesare.
                    </p>

                </form>

            </section>

        </div>

    </div>
</main>

<?php

$js = <<<'JS'
(() => {
    'use strict';

    const form = document.querySelector('[data-checkout-form]');

    if (!form) {
        return;
    }

    const deliveryInputs = form.querySelectorAll(
        'input[name="delivery_method"]'
    );

    const addressContainer = form.querySelector(
        '[data-delivery-address]'
    );

    const addressInput = form.querySelector(
        '#delivery_address'
    );

    const submitButton = form.querySelector(
        '[data-submit-button]'
    );

    let isSubmitting = false;

    const syncDelivery = () => {
        const selected = form.querySelector(
            'input[name="delivery_method"]:checked'
        );

        const requiresAddress =
            selected !== null &&
            selected.value === 'home_delivery';

        if (addressContainer) {
            addressContainer.hidden = !requiresAddress;
        }

        if (addressInput) {
            addressInput.required = requiresAddress;
            addressInput.disabled = !requiresAddress;
        }
    };

    deliveryInputs.forEach(input => {
        input.addEventListener('change', syncDelivery);
    });

    syncDelivery();

    form.addEventListener('submit', event => {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        if (!form.checkValidity()) {
            return;
        }

        isSubmitting = true;

        if (submitButton) {
            submitButton.classList.add('is-loading');
            submitButton.setAttribute('aria-disabled', 'true');
        }
    });

    window.addEventListener('pageshow', () => {
        isSubmitting = false;

        if (submitButton) {
            submitButton.classList.remove('is-loading');
            submitButton.removeAttribute('aria-disabled');
        }
    });
})();
JS;

$this->registerJs($js);
?>
