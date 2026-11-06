<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class Products extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%products}}';
    }

    public function rules(): array
    {
        return [

            [
                ['category_id', 'title', 'price'],
                'required'
            ],

            [
                ['category_id', 'created_at', 'updated_at'],
                'integer'
            ],

            [
                ['title', 'subtitle'],
                'string',
                'max' => 255
            ],

            [
                ['description'],
                'string'
            ],

            [
                ['image'],
                'string',
                'max' => 255
            ],

            [
                ['price'],
                'number',
                'min' => 0
            ],

            [
                ['category_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Category::class,
                'targetAttribute' => ['category_id' => 'id']
            ],
        ];
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => [
                        'created_at',
                        'updated_at'
                    ],
                    self::EVENT_BEFORE_UPDATE => [
                        'updated_at'
                    ],
                ],
            ],
        ];
    }

    /* PRODUCT IMAGES RELATIONSHIP */

    public function getImages(): ActiveQuery
    {
        return $this->hasMany(
            ProductImages::class,
            ['product_id' => 'id']
        )->orderBy([
            'sort_order' => SORT_ASC,
            'id' => SORT_ASC,
        ]);
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }
}
