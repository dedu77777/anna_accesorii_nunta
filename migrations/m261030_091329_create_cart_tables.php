<?php

use yii\db\Migration;

class m261030_091329_create_cart_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%cart}}', [
            'id' => $this->primaryKey(),
            'session_id' => $this->string(128)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // ONE CART PER SESSION
        $this->createIndex(
            'idx-cart-session_id',
            '{{%cart}}',
            'session_id',
            true
        );

        // CART ITEMS TABLE
        $this->createTable('{{%cart_item}}', [
            'id' => $this->primaryKey(),
            'cart_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'quantity' => $this->integer()->notNull()->defaultValue(1),
        ]);

        // ONE ROW PER PRODUCT IN EACH CART
        $this->createIndex(
            'idx-cart_item-cart_product',
            '{{%cart_item}}',
            ['cart_id', 'product_id'],
            true
        );

        // PRODUCT LOOKUP INDEX
        $this->createIndex(
            'idx-cart_item-product_id',
            '{{%cart_item}}',
            'product_id'
        );

        // CART FOREIGN KEY
        $this->addForeignKey(
            'fk-cart_item-cart_id',
            '{{%cart_item}}',
            'cart_id',
            '{{%cart}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // PRODUCT FOREIGN KEY
        $this->addForeignKey(
            'fk-cart_item-product_id',
            '{{%cart_item}}',
            'product_id',
            '{{%products}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%cart_item}}');

        // REMOVE CART TABLE
        $this->dropTable('{{%cart}}');
    }
}
