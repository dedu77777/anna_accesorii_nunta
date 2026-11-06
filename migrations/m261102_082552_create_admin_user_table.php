<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%admin_user}}`.
 */
class m261102_082552_create_admin_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%admin_user}}', [
            'id' => $this->primaryKey(),

            'email' => $this->string(128)->notNull(),

            'hashed_password' => $this->string(255)->notNull(),

            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx-admin_user-email-unique',
            '{{%admin_user}}',
            'email',
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%admin_user}}');
    }
}
