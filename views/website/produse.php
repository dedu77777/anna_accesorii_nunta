<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\SiteSeo;

/**
 * @var \yii\web\View $this
 * @var \app\models\Category[] $categories
 * @var \app\models\Products[] $products
 * @var \yii\data\Pagination $pagination
 * @var int $selectedCategory
 * @var string $search
 */

/* ==========================================
   PAGE SEO
========================================== */

$totalProducts = (int) $pagination->totalCount;
$totalPages = (int) $pagination->getPageCount();
$currentPage = (int) $pagination->getPage() + 1;

$categoryName = null;
foreach ($categories as $category) {
    if ((int) $category->id === $selectedCategory) {
        $categoryName = $category->title;
        break;
    }
}

$pageLabel = $currentPage > 1 ? ' | Pagina ' . $currentPage : '';
$this->title = $categoryName !== null
    ? $categoryName . $pageLabel . ' | Anna Accesorii Nuntă'
    : 'Accesorii pentru Nuntă' . $pageLabel . ' | Produse și Colecții | Anna';

$this->params['metaDescription'] = $categoryName !== null
    ? 'Explorează produsele din categoria ' . $categoryName . ' la Anna Accesorii Nuntă.'
    : 'Explorează colecția de accesorii pentru nuntă Anna Accesorii Nuntă: lumânări, prosoape și buchete.';

if ($search !== '' || $selectedCategory > 0) {
    $this->params['robots'] = 'noindex, follow';
}

$canonicalQuery = [];
if ($search === '' && $selectedCategory > 0) {
    $canonicalQuery['category'] = $selectedCategory;
}
if ($search === '' && $currentPage > 1) {
    $canonicalQuery['page'] = $currentPage;
}
$this->params['canonicalUrl'] = SiteSeo::url('/produse', $canonicalQuery);

/* ==========================================
   CATEGORY AND PAGE URL
========================================== */

$productPageUrl = static function (
    int $category,
    int $page = 1
 ) use ($search): string {
    $params = ['/website/produse'];

    if ($search !== '') {
        $params['q'] = $search;
    }

    if ($category > 0) {
        $params['category'] = $category;
    }

    if ($page > 1) {
        $params['page'] = $page;
    }

    return Url::to($params);
};

?>

<!-- ==========================================
     PRODUCTS PAGE
========================================== -->

<section class="products-page" aria-labelledby="products-page-title">

    <div class="products-container">

        <!-- PAGE HEADING -->

        <div class="products-heading">

            <h1 id="products-page-title">
                <?= Html::encode($categoryName ?? 'Colecțiile noastre') ?>
            </h1>

            <p>
                Descoperă accesoriile pentru nuntă și cununie.
            </p>

        </div>

        <!-- CATEGORY FILTERS -->

        <nav class="products-categories" aria-label="Categorii produse">

            <?= Html::a(
                'Toate',
                $productPageUrl(0),
                [
                    'class' => 'products-category-link'
                        . ($selectedCategory === 0 ? ' active' : ''),
                    'aria-current' =>
                    $selectedCategory === 0 ? 'page' : null,
                ]
            ) ?>

            <?php foreach ($categories as $category): ?>

                <?= Html::a(
                    $category->title,
                    $productPageUrl((int) $category->id),
                    [
                        'class' => 'products-category-link'
                            . ($selectedCategory === (int) $category->id
                                ? ' active'
                                : ''),
                        'aria-current' =>
                        $selectedCategory === (int) $category->id
                            ? 'page'
                            : null,
                    ]
                ) ?>

            <?php endforeach; ?>

        </nav>

        <!-- PRODUCTS COUNT -->

        <div class="products-toolbar">

            <p>
                <?= $totalProducts ?>
                <?= $totalProducts === 1 ? 'produs' : 'produse' ?>
            </p>

        </div>

        <!-- PRODUCTS GRID -->

        <?php if (!empty($products)): ?>

            <div class="products-grid">

                <?php foreach ($products as $product): ?>

                    <?php
                    // PRODUCT DETAILS URL
                    $productUrl = Url::to([
                        '/website/detalii-produs',
                        'id' => $product->id,
                    ]);
                    ?>

                    <article class="products-card">

                        <!-- CLICKABLE PRODUCT IMAGE -->

                        <a
                            href="<?= Html::encode($productUrl) ?>"
                            class="products-card-image"
                            aria-label="<?= Html::encode('Vezi ' . $product->title) ?>">

                            <?= Html::img(
                                Url::to('@web/images/' . $product->image),
                                [
                                    'alt' => $product->title,
                                    'loading' => 'lazy',
                                    'class' => 'products-card-photo',
                                    'width' => 600,
                                    'height' => 600,
                                ]
                            ) ?>

                        </a>

                        <!-- PRODUCT INFORMATION -->

                        <div class="products-card-content">

                            <h2>
                                <?= Html::encode($product->title) ?>
                            </h2>

                            <!-- PRODUCT SUBTITLE -->

                            <p class="products-card-description">
                                <?= Html::encode($product->subtitle ?? '') ?>
                            </p>

                            <!-- PRICE AND DETAILS -->


                            <!-- PRICE AND CART -->

                            <div class="products-card-bottom">

                                <!-- PRODUCT PRICE -->

                                <p class="products-card-price">
                                    <?= number_format(
                                        (float) $product->price,
                                        0,
                                        ',',
                                        ' '
                                    ) ?> MDL
                                </p>

                                <!-- ADD TO CART FORM -->

                                <form
                                    class="products-cart-form"
                                    data-product-title="<?= Html::encode($product->title) ?>"
                                    action="<?= Url::to(['/website/adauga-in-cos']) ?>"
                                    method="post">

                                    <?= Html::hiddenInput(
                                        Yii::$app->request->csrfParam,
                                        Yii::$app->request->getCsrfToken()
                                    ) ?>

                                    <?= Html::hiddenInput('product_id', $product->id) ?>

                                    <!-- QUANTITY SELECTOR -->

                                    <div class="products-quantity-row">

                                        <div class="products-quantity-control">

                                            <button
                                                type="button"
                                                class="products-quantity-minus"
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
                                                class="products-quantity-plus"
                                                aria-label="Crește cantitatea">
                                                +
                                            </button>

                                        </div>

                                        <span class="products-quantity-limit">
                                            Max. 10
                                        </span>

                                    </div>

                                    <!-- ADD TO CART BUTTON -->

                                    <button
                                        type="submit"
                                        class="products-add-cart-button">

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

                                <!-- PRODUCT DETAILS -->

                                <?= Html::a(
                                    'Vezi detalii',
                                    $productUrl,
                                    ['class' => 'products-card-button']
                                ) ?>

                            </div>


                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <!-- EMPTY CATEGORY -->

            <div class="products-empty">

                <p>
                    <?= $search !== '' ? 'Nu am găsit produse pentru această căutare.' : 'Momentan nu există produse în această categorie.' ?>
                </p>

            </div>

        <?php endif; ?>

        <!-- PAGINATION -->

        <?php if ($totalPages > 1): ?>

            <nav
                class="products-pagination"
                aria-label="Paginare produse">

                <!-- PREVIOUS PAGE -->

                <?php if ($currentPage > 1): ?>

                    <?= Html::a(
                        '‹',
                        $productPageUrl(
                            $selectedCategory,
                            $currentPage - 1
                        ),
                        [
                            'class' => 'products-page-link products-page-arrow',
                            'aria-label' => 'Pagina anterioară',
                        ]
                    ) ?>

                <?php endif; ?>

                <!-- PAGE NUMBERS -->

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                    <?= Html::a(
                        (string) $i,
                        $productPageUrl($selectedCategory, $i),
                        [
                            'class' => 'products-page-link'
                                . ($currentPage === $i ? ' active' : ''),
                            'aria-current' =>
                            $currentPage === $i ? 'page' : null,
                        ]
                    ) ?>

                <?php endfor; ?>

                <!-- NEXT PAGE -->

                <?php if ($currentPage < $totalPages): ?>

                    <?= Html::a(
                        '›',
                        $productPageUrl(
                            $selectedCategory,
                            $currentPage + 1
                        ),
                        [
                            'class' => 'products-page-link products-page-arrow',
                            'aria-label' => 'Pagina următoare',
                        ]
                    ) ?>

                <?php endif; ?>

            </nav>

        <?php endif; ?>

    </div>

</section>
