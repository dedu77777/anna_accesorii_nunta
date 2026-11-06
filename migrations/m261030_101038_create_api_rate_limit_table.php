<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%api_rate_limit}}`.
 */
class m261030_101038_create_api_rate_limit_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%api_rate_limit}}', [
            'id' => $this->primaryKey(),
            'rate_key' => $this->string(64)->notNull(),
            'request_count' => $this->integer()->notNull()->defaultValue(0),
            'expires_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx_api_rate_limit_key',
            '{{%api_rate_limit}}',
            'rate_key',
            true
        );

        $this->createIndex(
            'idx_api_rate_limit_expires',
            '{{%api_rate_limit}}',
            'expires_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%api_rate_limit}}');
    }
}
