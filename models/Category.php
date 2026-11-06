<?php

namespace app\models;

use yii\db\ActiveRecord;

class Category extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%category}}';
    }

    public function rules(): array
    {
        return [
            [['title'], 'required'],
            [['title'], 'trim'],
            [['title', 'image'], 'string', 'max' => 255],
        ];
    }
}
