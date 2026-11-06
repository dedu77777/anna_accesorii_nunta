<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_images}}`.
 */
class m261029_192449_create_product_images_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%product_images}}', [
            'id' => $this->primaryKey(),

            'product_id' => $this->integer()->notNull(),

            'image' => $this->string(255)->notNull(),

            'sort_order' => $this->integer()->notNull()->defaultValue(0),

            'created_at' => $this->timestamp()
                ->notNull()
                ->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx-product_images-product_id',
            '{{%product_images}}',
            'product_id'
        );

        /* PRODUCT FOREIGN KEY */

        $this->addForeignKey(
            'fk-product_images-product_id',
            '{{%product_images}}',
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
        $this->dropForeignKey(
            'fk-product_images-product_id',
            '{{%product_images}}'
        );

        $this->dropTable('{{%product_images}}');
    }
}
