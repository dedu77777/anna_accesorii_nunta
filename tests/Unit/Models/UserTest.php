<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\User;

final class UserTest extends \Codeception\Test\Unit
{
    public function testFindUserById()
    {
        verify(User::findIdentity(100))->empty();
        verify(User::findIdentity(999))->empty();
    }

    public function testFindUserByAccessToken()
    {
        verify(User::findIdentityByAccessToken('100-token'))->empty();
        verify(User::findIdentityByAccessToken('non-existing'))->empty();
    }

    public function testFindUserByUsername()
    {
        verify(User::findByUsername('admin'))->empty();
        verify(User::findByUsername('not-admin'))->empty();
    }

    public function testValidateUser()
    {
        verify(User::findByUsername('demo'))->empty();
    }
}
