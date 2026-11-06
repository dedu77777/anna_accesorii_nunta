<?php

/** @var yii\web\View $this */
/** @var app\models\Products[] $products */
/** @var array $categoryNames */
/** @var yii\data\Pagination $pages */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Produse';
?>
<div class="admin-page-head">
    <div>
        <h1>Produse</h1>
        <p>Gestionează produsele disponibile în magazin.</p>
    </div>
    <?= Html::a('Adaugă produs', ['/admin/add-product-anna-accesorii'], ['class' => 'admin-button admin-button--primary']) ?>
</div>
<section class="admin-section" aria-label="Lista produselor">
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
                    <?php foreach ($products as $rowIndex => $product): ?>
                        <?php $primary = $product->images[0]->image ?? $product->image; ?>
                        <?php $position = $pages->offset + $rowIndex; ?>
                        <tr>
                            <td><?php if ($primary): ?><?= Html::img(Url::to('@web/images/' . ltrim($primary, '/')), ['alt' => '', 'class' => 'admin-thumbnail', 'loading' => 'lazy']) ?><?php else: ?><span class="admin-no-image">Fără imagine</span><?php endif; ?></td>
                            <td><?= Html::encode($product->title) ?></td>
                            <td><?= Html::encode($categoryNames[$product->category_id] ?? '—') ?></td>
                            <td><?= Html::encode(number_format((float) $product->price, 2, ',', ' ')) ?> lei</td>
                            <td class="admin-table__actions">
                                <?= Html::beginForm(['/admin/move-product-anna-accesorii', 'id' => $product->id], 'post', ['class' => 'admin-move-form']) ?>
                                    <?= Html::hiddenInput('direction', 'up') ?>
                                    <?= Html::hiddenInput('page', $pages->page + 1) ?>
                                    <?= Html::submitButton('↑ Mută sus', ['class' => 'admin-button admin-button--move', 'disabled' => $position === 0]) ?>
                                <?= Html::endForm() ?>
                                <?= Html::beginForm(['/admin/move-product-anna-accesorii', 'id' => $product->id], 'post', ['class' => 'admin-move-form']) ?>
                                    <?= Html::hiddenInput('direction', 'down') ?>
                                    <?= Html::hiddenInput('page', $pages->page + 1) ?>
                                    <?= Html::submitButton('↓ Mută jos', ['class' => 'admin-button admin-button--move', 'disabled' => $position >= $pages->totalCount - 1]) ?>
                                <?= Html::endForm() ?>
                                <?= Html::a('Editează', ['/admin/edit-product-anna-accesorii', 'id' => $product->id], ['class' => 'admin-button']) ?>
                                <details class="admin-delete-confirm">
                                    <summary class="admin-button admin-button--danger">Șterge</summary>
                                    <p>Ștergi produsul „<?= Html::encode($product->title) ?>”?</p>
                                    <?= Html::beginForm(['/admin/delete-product-anna-accesorii', 'id' => $product->id], 'post') ?>
                                    <?= Html::submitButton('Confirmă ștergerea', ['class' => 'admin-button admin-button--danger']) ?>
                                    <?= Html::endForm() ?>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?><p class="admin-empty">Nu există produse.</p><?php endif; ?>
</section>
<?php if ($pages->pageCount > 1): ?>
    <?= LinkPager::widget(['pagination' => $pages, 'options' => ['class' => 'admin-pagination'], 'maxButtonCount' => 5]) ?>
<?php endif; ?>
