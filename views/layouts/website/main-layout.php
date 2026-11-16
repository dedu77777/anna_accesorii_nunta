
<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\WebsiteAsset;
use app\components\SiteRefresh;
use app\widgets\Alert;
use yii\helpers\Html;
use yii\helpers\Url;

WebsiteAsset::register($this);
$catalogPage = Yii::$app->controller->id === 'website'
    && in_array(Yii::$app->controller->action->id, ['home', 'produse'], true);
$siteVersion = $catalogPage ? SiteRefresh::version() : null;

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <?= $this->render('head') ?>
    <?php $this->head() ?>
</head>

<body class="website-layout"<?php if ($catalogPage): ?> data-site-version="<?= $siteVersion ?>" data-site-version-url="<?= Html::encode(Url::to(['/website/site-version'])) ?>"<?php endif; ?>>
<div class="anna-splash" aria-hidden="true"><span class="anna-splash-lockup"><span class="anna-splash-brand">ANNA ACCESORII NUNTĂ</span><span class="anna-splash-line"></span></span></div>
<?php $this->beginBody() ?>

<?= $this->render('header') ?>

<main id="main" class="website-main">
    <?= Alert::widget() ?>
    <?= $content ?>
</main>

<?= $this->render('footer') ?>

<?php if (!Yii::$app->session->get('cookie_notice_accepted', false)): ?>
<aside class="anna-cookie-banner" aria-labelledby="anna-cookie-title">
    <div class="anna-cookie-banner__content">
        <div>
            <h2 id="anna-cookie-title">Despre cookie-uri</h2>
            <p>Folosim doar cookie-urile necesare pentru coșul de cumpărături și siguranța formularelor. Alegerea ta rămâne valabilă numai în această sesiune.</p>
            <?= Html::a('Politica de confidențialitate', ['/checkout/privacy'], ['class' => 'anna-cookie-banner__link']) ?>
        </div>
        <?= Html::beginForm(['/website/accept-cookies'], 'post', ['class' => 'anna-cookie-banner__form']) ?>
            <?= Html::hiddenInput('return', Yii::$app->request->url) ?>
            <?= Html::submitButton('Am înțeles', ['class' => 'anna-cookie-banner__button']) ?>
        <?= Html::endForm() ?>
    </div>
</aside>
<?php endif; ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
