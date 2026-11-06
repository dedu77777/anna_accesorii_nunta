<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%products}}`.
 */
class m261029_144819_create_products_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%products}}', [
            'id' => $this->primaryKey(),

            'category_id' => $this->integer()->notNull(),

            'title' => $this->string(255)->notNull(),

            'description' => $this->text()->null(),

            'price' => $this->decimal(10, 2)->notNull(),

            'image' => $this->string(255)->null(),

            'created_at' => $this->integer()->notNull(),

            'updated_at' => $this->integer()->notNull(),
        ]);

        /* CATEGORY INDEX */

        $this->createIndex(
            'idx-products-category_id',
            '{{%products}}',
            'category_id'
        );

        /* CATEGORY FOREIGN KEY */

        $this->addForeignKey(
            'fk-products-category_id',
            '{{%products}}',
            'category_id',
            '{{%category}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        /* SEARCH INDEX */

        $this->createIndex(
            'idx-products-title',
            '{{%products}}',
            'title'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        /* REMOVE FOREIGN KEY */

        $this->dropForeignKey(
            'fk-products-category_id',
            '{{%products}}'
        );

        /* REMOVE PRODUCT TABLE */

        $this->dropTable('{{%products}}');
    }
}
