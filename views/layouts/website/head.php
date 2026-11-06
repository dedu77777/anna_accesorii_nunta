
<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Json;
use app\components\SiteSeo;

// BRAND
$brandName = 'Anna Accesorii Nuntă';

// PAGE TITLE
$pageTitle = trim((string) $this->title);

$title = $pageTitle !== ''
    ? $pageTitle
    : 'Anna Accesorii Nuntă | Accesorii pentru nunți';

// META DESCRIPTION
$description = $this->params['metaDescription']
    ?? 'Descoperă accesorii elegante pentru nunți și '
    . 'evenimente speciale la Anna Accesorii Nuntă. '
    . 'Explorează colecțiile noastre în Chișinău, Moldova.';

// CANONICAL URL
$canonicalUrl = $this->params['canonicalUrl'] ?? null;

// SOCIAL SHARING
$ogImage = $this->params['ogImage']
    ?? SiteSeo::url('/images/anna-shop-1.jpg');

$ogType = $this->params['ogType'] ?? 'website';

// ROBOTS
$robots = $this->params['robots']
    ?? 'index, follow, max-image-preview:large';

if (!YII_ENV_PROD) {
    $robots = 'noindex, nofollow';
}

// LOCALE
$locale = 'ro_MD';

// STRUCTURED DATA
$schema = $this->params['structuredData'] ?? null;
?>

<meta charset="UTF-8">
<script>
// SHOW THE INTRODUCTION ON FIRST ENTRY AND ON REFRESH.
try {
    var navigation = performance.getEntriesByType('navigation')[0];
    var isRefresh = navigation && navigation.type === 'reload';
    var hasVisited = sessionStorage.getItem('annaSplashSeen');
    var skipAutomaticRefresh = sessionStorage.getItem('annaSkipSplashOnce') === '1';
    sessionStorage.removeItem('annaSkipSplashOnce');
    if ((!window.matchMedia || !window.matchMedia('(prefers-reduced-motion: reduce)').matches) &&
        !skipAutomaticRefresh && (isRefresh || !hasVisited)) {
        sessionStorage.setItem('annaSplashSeen', '1');
        window.annaSplashStartedAt = performance.now();
        document.documentElement.classList.add('anna-splash-active');
    }
} catch (error) {
    // If storage is unavailable, leave the website immediately usable.
}
</script>
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= Html::encode($title) ?></title>

<meta
    name="description"
    content="<?= Html::encode($description) ?>"
>

<meta
    name="robots"
    content="<?= Html::encode($robots) ?>"
>

<?php if ($canonicalUrl !== null): ?>
<link rel="canonical" href="<?= Html::encode($canonicalUrl) ?>">
<?php endif; ?>

<!-- OPEN GRAPH -->

<meta property="og:type"
      content="<?= Html::encode($ogType) ?>">

<meta property="og:locale"
      content="<?= Html::encode($locale) ?>">

<meta property="og:site_name"
      content="<?= Html::encode($brandName) ?>">

<meta property="og:title"
      content="<?= Html::encode($title) ?>">

<meta property="og:description"
      content="<?= Html::encode($description) ?>">

<?php if ($canonicalUrl !== null): ?>
<meta property="og:url" content="<?= Html::encode($canonicalUrl) ?>">
<?php endif; ?>

<meta property="og:image"
      content="<?= Html::encode($ogImage) ?>">

<!-- TWITTER / X -->

<meta name="twitter:card" content="summary_large_image">

<meta name="twitter:title"
      content="<?= Html::encode($title) ?>">

<meta name="twitter:description"
      content="<?= Html::encode($description) ?>">

<meta name="twitter:image"
      content="<?= Html::encode($ogImage) ?>">

<!-- THEME -->

<meta name="theme-color" content="#faf8f5">

<!-- STRUCTURED DATA -->

<?php if ($schema !== null): ?>
<script type="application/ld+json">
<?= Json::htmlEncode($schema) ?>
</script>
<?php endif; ?>
