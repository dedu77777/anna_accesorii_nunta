<?php

/** @var yii\web\View $this */
/** @var app\models\AdminLoginForm $model */

use app\assets\AdminLoginAsset;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->context->layout = false;
$this->title = 'Autentificare administrator';
AdminLoginAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= Html::encode(Yii::getAlias('@web/favicon-32x32.png?v=2')) ?>">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<main class="admin-login">
    <section class="admin-login__panel" aria-labelledby="admin-login-title">
        <h1 class="admin-login__title" id="admin-login-title">Autentificare administrator</h1>

        <?php $form = ActiveForm::begin([
            'id' => 'admin-login-form',
            'action' => ['/admin/admin-anna-accesorii'],
            'method' => 'post',
            'options' => ['class' => 'admin-login__form'],
        ]); ?>
            <?php if ($model->hasErrors()): ?>
                <?= $form->errorSummary($model, [
                    'class' => 'error-summary',
                    'role' => 'alert',
                ]) ?>
            <?php endif; ?>

            <?= $form->field($model, 'email', [
                'options' => ['class' => 'mb-3'],
            ])->textInput([
                'type' => 'email',
                'autocomplete' => 'username',
                'autofocus' => true,
            ])->label('Email') ?>

            <?= $form->field($model, 'password', [
                'options' => ['class' => 'mb-3'],
            ])->passwordInput([
                'autocomplete' => 'current-password',
            ])->label('Parolă') ?>

            <?= Html::submitButton('Autentificare', [
                'class' => 'admin-login__submit',
            ]) ?>
        <?php ActiveForm::end(); ?>
    </section>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
