<?php

use app\models\Category;
use app\models\Products;
use app\components\SiteSeo;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Category[] $categories */
/** @var Products[] $products */

// SEO settings
$this->title = 'Accesorii Nuntă Moldova | Anna Accesorii Nuntă';

$this->params['metaDescription'] =
    'Descoperă accesorii pentru nuntă: lumânări pentru cununie, '
    . 'prosoape pentru nași și buchete pentru miri și nași. '
    . 'Explorează colecția Anna Accesorii Nuntă.';
$this->params['canonicalUrl'] = SiteSeo::url();
$this->params['structuredData'] = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => SiteSeo::url('/#organization'),
            'name' => 'Anna Accesorii Nuntă',
            'url' => SiteSeo::url(),
            'sameAs' => ['https://www.instagram.com/anna_accesorii_nunta/'],
        ],
        [
            '@type' => 'WebSite',
            'name' => 'Anna Accesorii Nuntă',
            'url' => SiteSeo::url(),
            'publisher' => ['@id' => SiteSeo::url('/#organization')],
        ],
    ],
];

?>

<!-- HOMEPAGE SEARCH -->

<section class="home-search" aria-label="Căutare produse">

    <div class="home-search-container">

        <form
            class="home-search-form"
            action="<?= Url::to(['/website/produse']) ?>"
            method="get"
            data-search-url="<?= Html::encode(Url::to(['/website/cauta-produse'])) ?>"
            role="search">

            <label for="home-search-input" class="home-search-label">
                Ce cauți pentru ziua ta specială?
            </label>

            <div class="home-search-field">

                <input
                    type="search"
                    id="home-search-input"
                    name="q"
                    maxlength="100"
                    placeholder="Caută lumânări, prosoape, buchete..."
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-controls="home-search-suggestions"
                    aria-expanded="false"
                    required>

                <button
                    type="submit"
                    class="home-search-button"
                    aria-label="Caută produse">

                    <svg
                        width="21"
                        height="21"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true">

                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />

                    </svg>

                    Caută

                </button>

            </div>

            <div id="home-search-suggestions" class="home-search-suggestions" role="listbox" aria-label="Produse sugerate" hidden></div>
            <p class="anna-sr-only" role="status" aria-live="polite" data-search-status></p>

        </form>

    </div>

</section>

<!-- CATEGORIES SECTION -->

<section
    class="home-categories"
    aria-labelledby="categories-title">

    <div class="anna-container">

        <div class="home-categories-heading">

            <h1 id="categories-title">
                Accesorii pentru nuntă în Moldova
            </h1>

            <?= Html::a(
                'Vezi toate categoriile',
                ['/website/produse'],
                ['class' => 'home-section-link']
            ) ?>

        </div>

        <!-- Categories loaded from MySQL -->

        <div class="home-categories-grid">

            <?php foreach ($categories as $category): ?>

                <?php
                // Generate the category URL
                $categoryUrl = Url::to([
                    '/website/produse',
                    'category' => $category->id,
                ]);
                ?>

                <a
                    href="<?= Html::encode($categoryUrl) ?>"
                    class="home-category">

                    <div class="home-category-visual">

                        <div class="home-category-circle">

                            <?php if (!empty($category->image)): ?>

                                <?php
                                // Category images are stored in web/images/categories
                                $categoryImage = Yii::getAlias('@web')
                                    . '/images/categories/'
                                    . rawurlencode(basename($category->image));
                                ?>

                                <img
                                    src="<?= Html::encode($categoryImage) ?>"
                                    alt="<?= Html::encode($category->title) ?>"
                                    width="240"
                                    height="240"
                                    loading="lazy">

                            <?php else: ?>

                                <!-- Fallback icon when no category image exists -->

                                <svg
                                    class="home-category-placeholder"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="48"
                                    height="48"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true">

                                    <rect
                                        x="3"
                                        y="3"
                                        width="18"
                                        height="18"
                                        rx="2" />

                                    <circle
                                        cx="8.5"
                                        cy="8.5"
                                        r="1.5" />

                                    <path d="m21 15-5-5L5 21" />

                                </svg>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="home-category-info">

                        <h3>
                            <?= Html::encode($category->title) ?>
                        </h3>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<!-- FEATURED PRODUCTS SECTION -->

<section
    class="home-products"
    aria-labelledby="home-products-title">

    <div class="home-products-container">

        <div class="home-products-heading">

            <div>

                <h2 id="home-products-title">
                    Produsele noastre
                </h2>

                <p>
                    Descoperă accesoriile pentru nuntă și cununie.
                </p>

            </div>

            <?= Html::a(
                'Vezi toate produsele',
                ['/website/produse'],
                ['class' => 'home-section-link']
            ) ?>

        </div>

        <!-- Products loaded from MySQL -->

        <?php if (!empty($products)): ?>

            <div class="home-products-grid">

                <?php foreach ($products as $product): ?>

                    <?php
                    // URL to the individual product details page
                    $productUrl = Url::to([
                        '/website/detalii-produs',
                        'id' => $product->id,
                    ]);

                    // Product image stored in web/images/products
                    $imageUrl = Url::to(
                        '@web/images/' . ltrim($product->image, '/')
                    );

                    // Format product price in Moldovan lei
                    $formattedPrice = number_format(
                        (float) $product->price,
                        0,
                        ',',
                        ' '
                    );
                    ?>

                    <article class="home-product-card">

                        <!-- Clickable product image -->

                        <a
                            href="<?= Html::encode($productUrl) ?>"
                            class="home-product-image"
                            aria-label="<?= Html::encode(
                                            'Vezi detalii pentru ' . $product->title
                                        ) ?>">

                            <?php if (!empty($product->image)): ?>

                                <img
                                    src="<?= Html::encode($imageUrl) ?>"
                                    alt="<?= Html::encode($product->title) ?>"
                                    width="600"
                                    height="600"
                                    loading="lazy">

                            <?php else: ?>

                                <!-- Image placeholder -->

                                <svg
                                    class="home-product-placeholder"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="48"
                                    height="48"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true">

                                    <rect
                                        x="3"
                                        y="3"
                                        width="18"
                                        height="18"
                                        rx="2" />

                                    <circle
                                        cx="8.5"
                                        cy="8.5"
                                        r="1.5" />

                                    <path d="m21 15-5-5L5 21" />

                                </svg>

                            <?php endif; ?>

                        </a>

                        <!-- Product information -->

                        <div class="home-product-content">

                            <h3>

                                <a
                                    href="<?= Html::encode($productUrl) ?>"
                                    class="home-product-title-link">

                                    <?= Html::encode($product->title) ?>

                                </a>

                            </h3>

                            <p class="home-product-description">
                                <?= Html::encode($product->subtitle) ?>
                            </p>

                            <!-- Price and details button -->


                            <!-- PRODUCT PRICE AND CART -->

                            <div class="home-product-bottom">

                                <p class="home-product-price">
                                    <?= number_format((float) $product->price, 0, ',', ' ') ?> MDL
                                </p>

                                <!-- ADD TO CART FORM -->

                                <form
                                    class="home-cart-form"
                                    data-product-title="<?= Html::encode($product->title) ?>"
                                    action="<?= Url::to(['/website/adauga-in-cos']) ?>"
                                    method="post">

                                    <?= Html::hiddenInput(
                                        Yii::$app->request->csrfParam,
                                        Yii::$app->request->getCsrfToken()
                                    ) ?>

                                    <?= Html::hiddenInput('product_id', $product->id) ?>

                                    <!-- QUANTITY SELECTOR -->

                                    <div class="home-quantity-row">

                                        <div class="home-quantity-control">

                                            <button
                                                type="button"
                                                class="home-quantity-minus"
                                                aria-label="Scade cantitatea">
                                                −
                                            </button>

                                            <input
                                                type="number"
                                                name="quantity"
                                                value="1"
                                                min="1"
                                                max="10"
                                                required
                                                aria-label="Cantitate">

                                            <button
                                                type="button"
                                                class="home-quantity-plus"
                                                aria-label="Crește cantitatea">
                                                +
                                            </button>

                                        </div>

                                        <span class="home-quantity-limit">
                                            Max. 10
                                        </span>

                                    </div>

                                    <!-- ADD TO CART BUTTON -->

                                    <button
                                        type="submit"
                                        class="home-add-cart-button">

                                        <svg
                                            width="17"
                                            height="17"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true">
                                            <path d="M6 7h12l1 14H5L6 7Z" />
                                            <path d="M9 9V6a3 3 0 0 1 6 0v3" />
                                        </svg>

                                        Adaugă în coș

                                    </button>

                                </form>

                                <!-- PRODUCT DETAILS LINK -->

                                <a
                                    href="<?= Html::encode($productUrl) ?>"
                                    class="home-product-button">
                                    Vezi detalii
                                </a>

                            </div>


                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <!-- Empty state when there are no products -->

            <p class="home-products-empty">
                Momentan nu sunt produse disponibile.
            </p>

        <?php endif; ?>

    </div>

</section>


<!-- FREQUENTLY ASKED QUESTIONS -->

<section
    class="home-faq"
    aria-labelledby="home-faq-title">

    <div class="home-faq-container">

        <div class="home-faq-heading">

            <h2 id="home-faq-title">
                Întrebări frecvente
            </h2>

            <p>
                Informații utile despre produse și comenzi.
            </p>

        </div>

        <?php
        // FAQ content displayed on the homepage
        $faqItems = [
            [
                'question' => 'Cum pot plasa o comandă?',
                'answer' => 'Alege produsele dorite, adaugă-le în coș și completează datele necesare pentru comandă. Pentru întrebări suplimentare, ne poți contacta direct.',
            ],
            [
                'question' => 'Pot solicita accesorii personalizate?',
                'answer' => 'Pentru produse personalizate sau cerințe speciale, contactează-ne și îți vom comunica ce opțiuni sunt disponibile.',
            ],
            [
                'question' => 'Cum verific disponibilitatea unui produs?',
                'answer' => 'Disponibilitatea produselor poate varia. Pentru confirmarea unui anumit model sau a cantității dorite, te rugăm să ne contactezi înainte de comandă.',
            ],
            [
                'question' => 'Cum se realizează livrarea sau ridicarea comenzii?',
                'answer' => 'Modalitățile de livrare sau ridicare și eventualele costuri vor fi confirmate în momentul procesării comenzii.',
            ],
            [
                'question' => 'Cu cât timp înainte de nuntă ar trebui să comand?',
                'answer' => 'Îți recomandăm să alegi accesoriile din timp, mai ales dacă ai nevoie de mai multe produse sau de personalizare. Termenul exact de pregătire se confirmă în funcție de comandă.',
            ],
        ];
        ?>

        <div class="home-faq-list">

            <?php foreach ($faqItems as $faq): ?>

                <details class="home-faq-item">

                    <summary class="home-faq-question">

                        <span>
                            <?= Html::encode($faq['question']) ?>
                        </span>

                        <span
                            class="home-faq-icon"
                            aria-hidden="true">
                        </span>

                    </summary>

                    <div class="home-faq-answer">

                        <p>
                            <?= Html::encode($faq['answer']) ?>
                        </p>

                    </div>

                </details>

            <?php endforeach; ?>

        </div>

    </div>

</section>
