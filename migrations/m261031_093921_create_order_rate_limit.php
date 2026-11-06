<?php

use yii\db\Migration;

class m261031_093921_create_order_rate_limit extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->createTable('{{%order_rate_limit_lock}}', [
            'scope_key' => $this->char(64)->notNull(),
            'PRIMARY KEY(scope_key)',
        ], 'ENGINE=InnoDB');

        $this->createTable('{{%order_rate_limit_event}}', [
            'id' => $this->bigPrimaryKey(),
            'scope_key' => $this->char(64)->notNull(),
            'order_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], 'ENGINE=InnoDB');

        $this->createIndex(
            'idx_order_rate_scope_time',
            '{{%order_rate_limit_event}}',
            ['scope_key', 'created_at']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%order_rate_limit_event}}');
        $this->dropTable('{{%order_rate_limit_lock}}');
    }
}
