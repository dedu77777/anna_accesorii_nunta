<?php

namespace app\models;

use yii\db\ActiveRecord;

class OrderItem extends ActiveRecord
{
    public static function tableName(): string { return '{{%order_items}}'; }
}
