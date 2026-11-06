<?php

/** @var yii\web\View $this */
/** @var app\models\Products[] $products */
/** @var app\models\Category[] $categories */
/** @var array $categoryNames */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Panou de administrare';
?>
<div class="admin-page-head">
    <div>
        <h1>Panou de administrare</h1>
        <p>Gestionează produsele și categoriile magazinului.</p>
    </div>
    <div class="admin-dashboard-actions">
        <?= Html::beginForm(['/admin/publish-site-refresh'], 'post', ['class' => 'admin-refresh-form']) ?>
            <?= Html::submitButton('Actualizează site-ul', ['class' => 'admin-button admin-button--primary admin-refresh-button', 'title' => 'Actualizează paginile de produse deschise de vizitatori']) ?>
        <?= Html::endForm() ?>
        <div class="admin-page-actions">
            <?= Html::a('Adaugă produs', ['/admin/add-product-anna-accesorii'], ['class' => 'admin-button']) ?>
            <?= Html::a('Adaugă categorie', ['/admin/add-category-anna-accesorii'], ['class' => 'admin-button']) ?>
        </div>
    </div>
</div>
<section class="admin-section" aria-labelledby="products-title">
    <div class="admin-section__head">
        <h2 id="products-title">Produse</h2><?= Html::a('Vezi toate produsele', ['/admin/products-anna-accesorii'], ['class' => 'admin-button']) ?>
    </div>
    <?php if ($products): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Imagine</th>
                        <th>Produs</th>
                        <th>Categorie</th>
                        <th>Preț</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?><tr>
                            <td><?php $primary = $product->images[0]->image ?? $product->image; ?><?php if ($primary): ?><?= Html::img(Url::to('@web/images/' . ltrim($primary, '/')), ['alt' => '', 'class' => 'admin-thumbnail', 'loading' => 'lazy']) ?><?php else: ?><span class="admin-no-image">Fără imagine</span><?php endif; ?></td>
                            <td><?= Html::encode($product->title) ?></td>
                            <td><?= Html::encode($categoryNames[$product->category_id] ?? '—') ?></td>
                            <td><?= Html::encode(number_format((float) $product->price, 2, ',', ' ')) ?> lei</td>
                            <td><?= Html::a('Editează', ['/admin/edit-product-anna-accesorii', 'id' => $product->id], ['class' => 'admin-button']) ?></td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?><p class="admin-empty">Nu există produse.</p><?php endif; ?>
</section>
<section class="admin-section" aria-labelledby="categories-title">
    <div class="admin-section__head">
        <h2 id="categories-title">Categorii</h2><?= Html::a('Vezi toate categoriile', ['/admin/categories-anna-accesorii'], ['class' => 'admin-button']) ?>
    </div>
    <?php if ($categories): ?><div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Imagine</th>
                        <th>Categorie</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($categories as $category): ?><tr>
                            <td><?php if ($category->image): ?><?= Html::img(Url::to('@web/images/categories/' . rawurlencode(basename($category->image))), ['alt' => '', 'class' => 'admin-thumbnail', 'loading' => 'lazy']) ?><?php else: ?><span class="admin-no-image">Fără imagine</span><?php endif; ?></td>
                            <td><?= Html::encode($category->title) ?></td>
                            <td><?= Html::a('Editează', ['/admin/edit-category-anna-accesorii', 'id' => $category->id], ['class' => 'admin-button']) ?></td>
                        </tr><?php endforeach; ?></tbody>
            </table>
        </div>
    <?php else: ?><p class="admin-empty">Nu există categorii.</p><?php endif; ?>
</section>
