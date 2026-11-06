<?php

namespace app\models;

use yii\db\ActiveRecord;

class Order extends ActiveRecord
{
    public static function tableName(): string { return '{{%orders}}'; }
    public function getItems() { return $this->hasMany(OrderItem::class, ['order_id' => 'id']); }
}
