<?php
/** @var yii\web\View $this */
/** @var app\models\Category[] $categories */
/** @var yii\data\Pagination $pages */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Categorii';
?>
<div class="admin-page-head">
    <div><h1>Categorii</h1><p>Organizează produsele magazinului.</p></div>
    <?= Html::a('Adaugă categorie', ['/admin/add-category-anna-accesorii'], ['class' => 'admin-button admin-button--primary']) ?>
</div>
<section class="admin-section" aria-label="Lista categoriilor">
    <?php if ($categories): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Imagine</th><th>Categorie</th><th>Acțiuni</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $rowIndex => $category): ?>
                    <?php $position = $pages->offset + $rowIndex; ?>
                    <tr>
                        <td><?php if ($category->image): ?><?= Html::img(Url::to('@web/images/categories/' . rawurlencode(basename($category->image))), ['alt' => '', 'class' => 'admin-thumbnail', 'loading' => 'lazy']) ?><?php else: ?><span class="admin-no-image">Fără imagine</span><?php endif; ?></td>
                        <td><?= Html::encode($category->title) ?></td>
                        <td class="admin-table__actions">
                            <?= Html::beginForm(['/admin/move-category-anna-accesorii', 'id' => $category->id], 'post', ['class' => 'admin-move-form']) ?>
                                <?= Html::hiddenInput('direction', 'up') ?>
                                <?= Html::hiddenInput('page', $pages->page + 1) ?>
                                <?= Html::submitButton('↑ Mută sus', ['class' => 'admin-button admin-button--move', 'disabled' => $position === 0]) ?>
                            <?= Html::endForm() ?>
                            <?= Html::beginForm(['/admin/move-category-anna-accesorii', 'id' => $category->id], 'post', ['class' => 'admin-move-form']) ?>
                                <?= Html::hiddenInput('direction', 'down') ?>
                                <?= Html::hiddenInput('page', $pages->page + 1) ?>
                                <?= Html::submitButton('↓ Mută jos', ['class' => 'admin-button admin-button--move', 'disabled' => $position >= $pages->totalCount - 1]) ?>
                            <?= Html::endForm() ?>
                            <?= Html::a('Editează', ['/admin/edit-category-anna-accesorii', 'id' => $category->id], ['class' => 'admin-button']) ?>
                            <details class="admin-delete-confirm">
                                <summary class="admin-button admin-button--danger">Șterge</summary>
                                <p>Ștergi categoria „<?= Html::encode($category->title) ?>”?</p>
                                <?= Html::beginForm(['/admin/delete-category-anna-accesorii', 'id' => $category->id], 'post') ?>
                                    <?= Html::submitButton('Confirmă ștergerea', ['class' => 'admin-button admin-button--danger']) ?>
                                <?= Html::endForm() ?>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?><p class="admin-empty">Nu există categorii.</p><?php endif; ?>
</section>
<?php if ($pages->pageCount > 1): ?>
    <?= LinkPager::widget(['pagination' => $pages, 'options' => ['class' => 'admin-pagination'], 'maxButtonCount' => 5]) ?>
<?php endif; ?>
