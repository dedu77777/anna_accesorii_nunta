<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\controllers\SiteController;
use Yii;
use yii\web\NotFoundHttpException;

final class LoginTest extends \Codeception\Test\Unit
{
    public function testPublicDemoLoginIsUnavailable(): void
    {
        $controller = new SiteController('site', Yii::$app, Yii::$app->mailer);
        $this->expectException(NotFoundHttpException::class);
        $controller->actionLogin();
    }
}
