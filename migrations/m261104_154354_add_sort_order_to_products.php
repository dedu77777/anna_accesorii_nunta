<?php

use yii\db\Migration;

class m261104_154354_add_sort_order_to_products extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->addColumn(
            '{{%products}}',
            'sort_order',
            $this->integer()->notNull()->defaultValue(0)
        );

        $this->createIndex(
            'idx-products-sort-order',
            '{{%products}}',
            'sort_order'
        );

        $this->execute(
            'UPDATE {{%products}} SET sort_order = id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex(
            'idx-products-sort-order',
            '{{%products}}'
        );

        $this->dropColumn(
            '{{%products}}',
            'sort_order'
        );
    }
}
