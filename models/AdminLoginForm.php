<?php

namespace app\models;

use Yii;
use yii\base\Model;

class AdminLoginForm extends Model
{
    private const DUMMY_HASH = '$2y$13$kW1ZrnLbk19z0bhpuDQ8.ueQz4uugkrEkezm134.k3.SS4N.3r4nK';

    public string $email = '';
    public string $password = '';

    private ?AdminUser $_admin = null;

    public function rules(): array
    {
        return [
            [['email', 'password'], 'required'],
            ['email', 'email'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword($attribute, $params): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $admin = $this->getAdmin();

        $passwordMatches = Yii::$app->security->validatePassword(
            $this->password,
            $admin?->hashed_password ?? self::DUMMY_HASH
        );
        if (!$admin || !$passwordMatches) {
            $this->addError(
                $attribute,
                'Email sau parolă incorectă.'
            );
        }
    }

    public function getAdmin(): ?AdminUser
    {
        if ($this->_admin === null) {
            $this->_admin = AdminUser::findByEmail($this->email);
        }

        return $this->_admin;
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        return Yii::$app->user->login($this->getAdmin(), 0);
    }
}
