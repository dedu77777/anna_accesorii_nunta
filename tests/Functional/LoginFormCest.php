<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\tests\Support\FunctionalTester;

final class LoginFormCest
{
    public function demoLoginIsUnavailable(FunctionalTester $I): void
    {
        $I->amOnRoute('site/login');
        $I->seeResponseCodeIs(404);
    }
}
