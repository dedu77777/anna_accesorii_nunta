<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class AdminUser extends ActiveRecord implements IdentityInterface
{
    public static function tableName()
    {
        return '{{%admin_user}}';
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public static function findByEmail(string $email)
    {
        return static::findOne([
            'email' => strtolower(trim($email)),
        ]);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword(
            $password,
            $this->hashed_password
        );
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }
}
