<?php

declare(strict_types=1);

namespace app\tests\Acceptance;

use app\tests\Support\AcceptanceTester;
use yii\helpers\Url;

final class LoginCest
{
    public function demoLoginIsUnavailable(AcceptanceTester $I): void
    {
        $I->amOnPage(Url::toRoute('/site/login'));
        $I->seeResponseCodeIs(404);
    }
}
