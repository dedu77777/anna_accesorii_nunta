<?php

namespace app\models;

use yii\db\ActiveRecord;

class CartItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%cart_item}}';
    }

    public function rules(): array
    {
        return [
            [['cart_id', 'product_id', 'quantity'], 'required'],
            [['cart_id', 'product_id', 'quantity'], 'integer'],
            ['quantity', 'integer', 'min' => 1, 'max' => 10],
            [
                ['cart_id', 'product_id'],
                'unique',
                'targetAttribute' => ['cart_id', 'product_id'],
            ],
        ];
    }

    // CART RELATION
    public function getCart()
    {
        return $this->hasOne(
            Cart::class,
            ['id' => 'cart_id']
        );
    }

    // PRODUCT RELATION
    public function getProduct()
    {
        return $this->hasOne(
            Products::class,
            ['id' => 'product_id']
        );
    }
}
