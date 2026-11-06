<?php

use yii\db\Migration;

class m261031_100000_create_checkout_tables extends Migration
{
    public function safeUp()
    {
        $options = 'ENGINE=InnoDB';
        $this->createTable('{{%orders}}', [
            'id' => $this->primaryKey(),
            'order_number' => $this->string(32)->notNull()->unique(),
            'session_hash' => $this->string(64)->notNull(),
            'idempotency_key' => $this->string(64)->notNull()->unique(),
            'full_name' => $this->string(120)->notNull(),
            'email' => $this->string(254)->notNull(),
            'phone_number' => $this->string(24)->notNull(),
            'delivery_method' => $this->string(20)->notNull(),
            'delivery_address' => $this->string(500),
            'additional_information' => $this->text(),
            'subtotal' => $this->decimal(12, 2)->notNull(),
            'total' => $this->decimal(12, 2)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $options);
        $this->createIndex('idx-orders-session', '{{%orders}}', 'session_hash');
        $this->createTable('{{%order_items}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'product_title' => $this->string(255)->notNull(),
            'unit_price' => $this->decimal(12, 2)->notNull(),
            'quantity' => $this->integer()->notNull(),
            'line_total' => $this->decimal(12, 2)->notNull(),
            'product_image' => $this->string(255),
        ], $options);
        $this->createIndex('idx-order-items-order', '{{%order_items}}', 'order_id');
        $this->addForeignKey('fk-order-items-order', '{{%order_items}}', 'order_id', '{{%orders}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-order-items-product', '{{%order_items}}', 'product_id', '{{%products}}', 'id', 'RESTRICT');
        $this->createTable('{{%order_cooldown}}', [
            'scope_key' => $this->string(64)->notNull(),
            'expires_at' => $this->integer()->notNull(),
            'order_id' => $this->integer()->notNull(),
        ], $options);
        $this->addPrimaryKey('pk-order-cooldown', '{{%order_cooldown}}', 'scope_key');
        $this->addForeignKey('fk-order-cooldown-order', '{{%order_cooldown}}', 'order_id', '{{%orders}}', 'id', 'CASCADE');
        $this->createTable('{{%order_email_outbox}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'kind' => $this->string(20)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'locked_at' => $this->integer(),
            'sent_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
        ], $options);
        $this->createIndex('ux-order-email-kind', '{{%order_email_outbox}}', ['order_id', 'kind'], true);
        $this->createIndex('idx-order-email-work', '{{%order_email_outbox}}', ['status', 'attempts']);
        $this->addForeignKey('fk-order-email-order', '{{%order_email_outbox}}', 'order_id', '{{%orders}}', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%order_email_outbox}}');
        $this->dropTable('{{%order_cooldown}}');
        $this->dropTable('{{%order_items}}');
        $this->dropTable('{{%orders}}');
    }
}
