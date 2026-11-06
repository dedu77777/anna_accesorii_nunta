<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

class ProductImages extends ActiveRecord
{
    /* DATABASE TABLE */

    public static function tableName(): string
    {
        return '{{%product_images}}';
    }

    /* VALIDATION RULES */

    public function rules(): array
    {
        return [
            [['product_id', 'image'], 'required'],

            [['product_id', 'sort_order'], 'integer'],

            [['image'], 'string', 'max' => 255],

            [['sort_order'], 'default', 'value' => 0],

            [
                ['product_id'],
                'exist',
                'targetClass' => Products::class,
                'targetAttribute' => ['product_id' => 'id'],
            ],
        ];
    }

    /* PRODUCT RELATIONSHIP */

    public function getProduct(): ActiveQuery
    {
        return $this->hasOne(
            Products::class,
            ['id' => 'product_id']
        );
    }
}
