<?php

namespace app\models;

use yii\db\ActiveRecord;

class Cart extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%cart}}';
    }

    public function rules(): array
    {
        return [
            [['session_id'], 'required'],
            [['session_id'], 'string', 'max' => 128],
            [['session_id'], 'unique'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    // CART ITEMS RELATION
    public function getItems()
    {
        return $this->hasMany(
            CartItem::class,
            ['cart_id' => 'id']
        );
    }

    // SET TIMESTAMPS
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = time();

        if ($insert) {
            $this->created_at = $now;
        }

        $this->updated_at = $now;

        return true;
    }
}
