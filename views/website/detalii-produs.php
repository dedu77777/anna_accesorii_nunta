<?php

use app\models\Products;
use app\components\SiteSeo;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var \yii\web\View $this
 * @var Products $product
 */

// PRODUCT DATA

$productTitle = $product->title;
$productDescription = $product->description;
$productSubtitle = $product->subtitle ?? '';

$productPrice = number_format(
    (float) $product->price,
    0,
    ',',
    ' '
);

// PRODUCT GALLERY

$galleryImages = $product->images;

// Use the cover image if the gallery is empty
if (empty($galleryImages) && !empty($product->image)) {
    $galleryImages = [
        (object) ['image' => $product->image],
    ];
}

// SEO

$productUrl = SiteSeo::url('/produse/' . (int) $product->id);
$primaryImage = $galleryImages[0]->image ?? null;
$productImageUrl = SiteSeo::productImage($primaryImage);
$plainDescription = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $productDescription)) ?? '');
$summary = $plainDescription !== '' ? $plainDescription : trim($productSubtitle);

$this->title = $productTitle . ' | Anna Accesorii Nuntă';
$this->params['metaDescription'] = mb_substr($summary !== '' ? $summary : $productTitle, 0, 160);
$this->params['canonicalUrl'] = $productUrl;
$this->params['ogType'] = 'product';

if ($productImageUrl !== null) {
    $this->params['ogImage'] = $productImageUrl;
}

$productSchema = [
    '@type' => 'Product',
    '@id' => $productUrl . '#product',
    'name' => $productTitle,
    'url' => $productUrl,
    'offers' => [
        '@type' => 'Offer',
        'url' => $productUrl,
        'price' => number_format((float) $product->price, 2, '.', ''),
        'priceCurrency' => 'MDL',
    ],
];

if ($summary !== '') {
    $productSchema['description'] = $summary;
}

if ($productImageUrl !== null) {
    $productSchema['image'] = $productImageUrl;
}

$this->params['structuredData'] = [
    '@context' => 'https://schema.org',
    '@graph' => [
        $productSchema,
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Acasă', 'item' => SiteSeo::url()],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Produse', 'item' => SiteSeo::url('/produse')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $productTitle, 'item' => $productUrl],
            ],
        ],
    ],
];

?>

<!-- PRODUCT DETAILS PAGE -->

<section class="product-detail">

    <div class="product-detail-container">

        <!-- BACK TO PRODUCTS -->

        <?= Html::a(
            $product->category !== null
                ? 'Vezi ' . $product->category->title
                : 'Înapoi la produse',
            $product->category !== null
                ? ['/website/produse', 'category' => $product->category_id]
                : ['/website/produse'],
            ['class' => 'product-detail-back']
        ) ?>

        <!-- PRODUCT CONTENT -->

        <div class="product-detail-layout">

            <!-- PRODUCT IMAGE GALLERY -->

            <div
                class="product-detail-gallery"
                aria-label="Fotografii ale produsului">

                <?php if (!empty($galleryImages)): ?>

                    <?php
                    // First image displayed as the main image
                    $firstImage = $galleryImages[0];

                    $mainImageUrl = Url::to(
                        '@web/images/' . ltrim($firstImage->image, '/')
                    );
                    ?>

                    <!-- MAIN IMAGE -->

                    <?= Html::img(
                        $mainImageUrl,
                        [
                            'alt' => $productTitle,
                            'class' => 'product-detail-image',
                            'id' => 'product-main-image',
                            'fetchpriority' => 'high',
                            'width' => 800,
                            'height' => 900,
                        ]
                    ) ?>

                    <!-- GALLERY THUMBNAILS -->

                    <div class="product-detail-thumbnails">

                        <?php foreach ($galleryImages as $index => $image): ?>

                            <?php
                            // Image URL from MySQL
                            $imageUrl = Url::to(
                                '@web/images/' . ltrim($image->image, '/')
                            );
                            ?>

                            <button
                                type="button"
                                class="product-detail-thumbnail <?= $index === 0 ? 'active' : '' ?>"
                                data-image="<?= Html::encode($imageUrl) ?>"
                                aria-label="Afișează fotografia <?= $index + 1 ?>"
                                aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">

                                <?= Html::img(
                                    $imageUrl,
                                    [
                                        'alt' => 'Fotografia ' . ($index + 1)
                                            . ' — ' . $productTitle,
                                        'loading' => $index === 0
                                            ? 'eager'
                                            : 'lazy',
                                        'width' => 120,
                                        'height' => 120,
                                    ]
                                ) ?>

                            </button>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="product-detail-no-image">
                        Imagine indisponibilă
                    </div>

                <?php endif; ?>

            </div>

            <!-- PRODUCT INFORMATION -->

            <div class="product-detail-info">

                <!-- PRODUCT TITLE -->

                <h1 class="product-detail-title">
                    <?= Html::encode($productTitle) ?>
                </h1>

                <!-- PRODUCT SUBTITLE -->

                <?php if (!empty($productSubtitle)): ?>

                    <p class="product-detail-subtitle">
                        <?= Html::encode($productSubtitle) ?>
                    </p>

                <?php endif; ?>

                <!-- PRODUCT PRICE -->

                <p class="product-detail-price">
                    <?= $productPrice ?> MDL
                </p>

                <div class="product-detail-line"></div>

                <!-- PRODUCT DESCRIPTION -->

                <h2 class="product-detail-heading">
                    Descriere
                </h2>

                <p class="product-detail-description">
                    <?= Html::encode($productDescription) ?>
                </p>

                <div class="product-detail-line"></div>

                <!-- ADD TO CART FORM -->

                <form
                    id="product-detail-cart-form"
                    data-product-title="<?= Html::encode($product->title) ?>"
                    action="<?= Url::to(['/website/adauga-in-cos']) ?>"
                    method="post">

                    <!-- CSRF PROTECTION -->

                    <?= Html::hiddenInput(
                        Yii::$app->request->csrfParam,
                        Yii::$app->request->getCsrfToken()
                    ) ?>

                    <!-- PRODUCT ID -->

                    <?= Html::hiddenInput(
                        'product_id',
                        $product->id
                    ) ?>

                    <!-- QUANTITY -->

                    <div class="product-detail-quantity">

                        <label for="product-quantity">
                            Cantitate
                        </label>

                        <div class="product-detail-quantity-row">

                            <div class="product-detail-quantity-control">

                                <button
                                    type="button"
                                    id="product-quantity-minus"
                                    aria-label="Scade cantitatea">
                                    −
                                </button>

                                <input
                                    id="product-quantity"
                                    type="number"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="10"
                                    required>

                                <button
                                    type="button"
                                    id="product-quantity-plus"
                                    aria-label="Crește cantitatea">
                                    +
                                </button>

                            </div>

                            <span class="product-detail-quantity-limit">
                                Max. 10
                            </span>

                        </div>

                    </div>

                    <!-- ADD TO CART BUTTON -->

                    <button
                        type="submit"
                        id="product-detail-add-cart"
                        class="product-detail-cart">
                        Adaugă în coș
                    </button>

                </form>

            </div>

        </div>

    </div>

</section>

<?php

// PRODUCT GALLERY

$js = <<<'JS'
(() => {
    // PRODUCT IMAGE GALLERY

    const mainImage = document.getElementById('product-main-image');
    const thumbnails = document.querySelectorAll(
        '.product-detail-thumbnail'
    );

    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            if (!mainImage) {
                return;
            }

            // Change the main product image
            mainImage.src = thumbnail.dataset.image;

            // Update active thumbnail
            thumbnails.forEach((item) => {
                item.classList.remove('active');
                item.setAttribute('aria-pressed', 'false');
            });

            thumbnail.classList.add('active');
            thumbnail.setAttribute('aria-pressed', 'true');
        });
    });

})();
JS;

$this->registerJs($js);
?>
