<?php

use yii\db\Migration;

class m261104_115843_add_sort_order_to_category extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->addColumn(
            '{{%category}}',
            'sort_order',
            $this->integer()->notNull()->defaultValue(0)
        );

        $this->createIndex(
            'idx-category-sort-order',
            '{{%category}}',
            'sort_order'
        );

        $this->execute(
            'UPDATE {{%category}} SET sort_order = id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex(
            'idx-category-sort-order',
            '{{%category}}'
        );

        $this->dropColumn(
            '{{%category}}',
            'sort_order'
        );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261104_115843_add_sort_order_to_category cannot be reverted.\n";

        return false;
    }
    */
}
