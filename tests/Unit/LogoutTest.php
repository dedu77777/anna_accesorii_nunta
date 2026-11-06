<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\models\User;

final class LogoutTest extends \Codeception\Test\Unit
{
    public function testFormerDemoIdentityCannotBeRestored(): void
    {
        self::assertNull(User::findIdentity('100'));
    }
}
