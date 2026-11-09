<?php
/** @var yii\web\View $this */
/** @var string $content */
use app\assets\AdminDashboardAsset;
use yii\helpers\Html;
use yii\helpers\Url;

AdminDashboardAsset::register($this);
$action = Yii::$app->controller->action->id;
$section = str_contains($action, 'product') ? 'products' : (str_contains($action, 'categor') ? 'categories' : 'dashboard');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= Html::encode(Yii::getAlias('@web/favicon-32x32.png?v=2')) ?>">
    <title><?= Html::encode($this->title) ?> — Anna Accesorii</title>
    <?php $this->head() ?>
</head>
<body class="admin-shell">
<?php $this->beginBody() ?>
<div class="admin-layout">
    <details class="admin-mobile-nav">
        <summary>Meniu administrare</summary>
        <div class="admin-mobile-nav__content">
            <?= Html::a('Panou principal', ['/admin/dashboard-anna-accesorii']) ?>
            <?= Html::a('Produse', ['/admin/products-anna-accesorii']) ?>
            <?= Html::a('Categorii', ['/admin/categories-anna-accesorii']) ?>
            <?= Html::a('Adaugă produs', ['/admin/add-product-anna-accesorii'], ['class' => 'admin-button']) ?>
            <?= Html::a('Adaugă categorie', ['/admin/add-category-anna-accesorii'], ['class' => 'admin-button']) ?>
            <?= Html::a('Înapoi Acasă →', ['/website/home']) ?>
            <?= Html::beginForm(['/admin/logout'], 'post') ?><?= Html::submitButton('Deconectare', ['class' => 'admin-logout']) ?><?= Html::endForm() ?>
        </div>
    </details>
    <aside class="admin-sidebar" aria-label="Administrare">
        <div class="admin-brand"><strong>Anna Accesorii</strong><span>Administrare</span></div>
        <nav class="admin-sidebar__nav" aria-label="Navigare administrare">
            <?= Html::a('Panou principal', ['/admin/dashboard-anna-accesorii'], ['class' => $section === 'dashboard' ? 'is-active' : '', 'aria-current' => $section === 'dashboard' ? 'page' : null]) ?>
            <?= Html::a('Produse', ['/admin/products-anna-accesorii'], ['class' => $section === 'products' ? 'is-active' : '', 'aria-current' => $section === 'products' ? 'page' : null]) ?>
            <?= Html::a('Categorii', ['/admin/categories-anna-accesorii'], ['class' => $section === 'categories' ? 'is-active' : '', 'aria-current' => $section === 'categories' ? 'page' : null]) ?>
        </nav>
        <div class="admin-sidebar__quick">
            <?= Html::a('Adaugă produs', ['/admin/add-product-anna-accesorii'], ['class' => 'admin-button']) ?>
            <?= Html::a('Adaugă categorie', ['/admin/add-category-anna-accesorii'], ['class' => 'admin-button']) ?>
        </div>
        <div class="admin-sidebar__bottom">
            <?= Html::a('Înapoi Acasă →', ['/website/home'], ['class' => 'admin-sidebar__home']) ?>
            <?= Html::beginForm(['/admin/logout'], 'post', ['class' => 'admin-sidebar__logout']) ?>
                <?= Html::submitButton('Deconectare', ['class' => 'admin-logout']) ?>
            <?= Html::endForm() ?>
        </div>
    </aside>
    <main class="admin-main" id="main-content">
        <?php foreach (['success', 'error'] as $type): ?>
            <?php if (Yii::$app->session->hasFlash($type)): ?>
                <div class="admin-flash admin-flash--<?= $type ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>"><?= Html::encode(Yii::$app->session->getFlash($type)) ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
