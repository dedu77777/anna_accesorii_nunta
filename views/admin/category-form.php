<?php
/** @var yii\web\View $this */
/** @var app\models\CategoryForm $form */
/** @var app\models\Category $category */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$isNew = $category->isNewRecord;
$this->title = $isNew ? 'Adaugă categorie' : 'Editează categorie';
?>
<div class="admin-page-head">
    <div><h1><?= Html::encode($this->title) ?></h1><p><?= $isNew ? 'Completează informațiile categoriei.' : 'Actualizează categoria.' ?></p></div>
</div>
<div class="admin-form-panel">
    <?php $activeForm = ActiveForm::begin([
        'action' => $isNew ? ['/admin/add-category-anna-accesorii'] : ['/admin/edit-category-anna-accesorii', 'id' => $category->id],
        'method' => 'post',
        'options' => ['class' => 'admin-form', 'enctype' => 'multipart/form-data', 'novalidate' => true],
    ]); ?>
        <?= $activeForm->errorSummary($form, ['class' => 'admin-form-summary', 'role' => 'alert']) ?>
        <?= $activeForm->field($form, 'title')->textInput(['maxlength' => 255]) ?>
        <?php if (!$isNew && $category->image): ?>
            <div class="admin-upload-field"><span class="admin-label">Imagine actuală</span><?= Html::img(Url::to('@web/images/categories/' . rawurlencode(basename($category->image))), ['alt' => 'Imagine categorie', 'class' => 'admin-thumbnail admin-thumbnail--large']) ?></div>
        <?php endif; ?>
        <?= $activeForm->field($form, 'image')->fileInput(['value' => '', 'accept' => 'image/jpeg,image/png,image/webp'])->hint($isNew ? 'JPG, PNG sau WebP; maximum 8 MB.' : 'Lasă câmpul gol pentru a păstra imaginea actuală.') ?>
        <div class="admin-form-actions">
            <?= Html::submitButton('Salvează categoria', ['class' => 'admin-button admin-button--primary']) ?>
            <?= Html::a('Anulează', ['/admin/categories-anna-accesorii'], ['class' => 'admin-button']) ?>
        </div>
    <?php ActiveForm::end(); ?>
</div>
