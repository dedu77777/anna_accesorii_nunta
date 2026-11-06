<?php
/** @var yii\web\View $this */
/** @var app\models\ProductForm $form */
/** @var app\models\Products $product */
/** @var app\models\Category[] $categories */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$isNew = $product->isNewRecord;
$this->title = $isNew ? 'Adaugă produs' : 'Editează produs';
$categoryOptions = [];
foreach ($categories as $category) {
    $categoryOptions[$category->id] = $category->title;
}
?>
<div class="admin-page-head">
    <div><h1><?= Html::encode($this->title) ?></h1><p><?= $isNew ? 'Completează informațiile produsului.' : 'Actualizează informațiile produsului.' ?></p></div>
</div>
<div class="admin-form-panel">
    <?php $activeForm = ActiveForm::begin([
        'action' => $isNew ? ['/admin/add-product-anna-accesorii'] : ['/admin/edit-product-anna-accesorii', 'id' => $product->id],
        'method' => 'post',
        'options' => ['class' => 'admin-form', 'enctype' => 'multipart/form-data', 'novalidate' => true],
    ]); ?>
        <?= $activeForm->errorSummary($form, ['class' => 'admin-form-summary', 'role' => 'alert']) ?>
        <?= $activeForm->field($form, 'title')->textInput(['maxlength' => 255]) ?>
        <?= $activeForm->field($form, 'subtitle')->textInput(['maxlength' => 255]) ?>
        <?= $activeForm->field($form, 'description')->textarea(['rows' => 5]) ?>
        <?= $activeForm->field($form, 'price')->textInput(['inputmode' => 'decimal']) ?>
        <?= $activeForm->field($form, 'category_id')->dropDownList($categoryOptions, ['prompt' => 'Selectează categoria']) ?>

        <?php if (!$isNew): ?>
            <fieldset class="admin-existing-images">
                <legend>Imagini actuale</legend>
                <div class="admin-image-gallery">
                    <?php foreach ($product->images as $image): ?>
                        <label class="admin-image-choice">
                            <?= Html::img(Url::to('@web/images/' . ltrim($image->image, '/')), ['alt' => 'Imagine produs', 'class' => 'admin-thumbnail admin-thumbnail--large']) ?>
                            <span><?= Html::checkbox('ProductForm[removeImageIds][]', in_array((int) $image->id, array_map('intval', (array) $form->removeImageIds), true), ['value' => $image->id]) ?> Elimină</span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (!$product->images && $product->image): ?>
                        <label class="admin-image-choice">
                            <?= Html::img(Url::to('@web/images/' . ltrim($product->image, '/')), ['alt' => 'Imagine produs', 'class' => 'admin-thumbnail admin-thumbnail--large']) ?>
                            <span><?= Html::checkbox('ProductForm[removeLegacyImage]', (bool) $form->removeLegacyImage, ['value' => '1']) ?> Elimină</span>
                        </label>
                    <?php endif; ?>
                    <?php if (!$product->images && !$product->image): ?><span class="admin-no-image">Fără imagini</span><?php endif; ?>
                </div>
                <?= Html::error($form, 'removeImageIds', ['class' => 'admin-upload-error']) ?>
            </fieldset>
        <?php endif; ?>

        <?= $activeForm->field($form, 'images')->fileInput(['name' => 'ProductForm[images][]', 'value' => '', 'multiple' => true, 'accept' => 'image/jpeg,image/png,image/webp'])->hint('JPG, PNG sau WebP; maximum 8 MB fiecare. Produsul poate avea între 1 și 3 imagini. Prima imagine este principală.') ?>
        <div class="admin-form-actions">
            <?= Html::submitButton('Salvează produsul', ['class' => 'admin-button admin-button--primary']) ?>
            <?= Html::a('Anulează', ['/admin/products-anna-accesorii'], ['class' => 'admin-button']) ?>
        </div>
    <?php ActiveForm::end(); ?>
</div>
