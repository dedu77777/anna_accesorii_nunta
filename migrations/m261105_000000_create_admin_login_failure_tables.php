<?php

use yii\db\Migration;

class m261105_000000_create_admin_login_failure_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%admin_login_lock}}', [
            'scope_key' => $this->string(64)->notNull(),
        ], 'ENGINE=InnoDB');
        $this->addPrimaryKey('pk-admin-login-lock', '{{%admin_login_lock}}', 'scope_key');

        $this->createTable('{{%admin_login_failure}}', [
            'id' => $this->primaryKey(),
            'scope_key' => $this->string(64)->notNull(),
            'failed_at' => $this->integer()->notNull(),
        ], 'ENGINE=InnoDB');
        $this->createIndex('idx-admin-login-failure-scope-time', '{{%admin_login_failure}}', ['scope_key', 'failed_at']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%admin_login_failure}}');
        $this->dropTable('{{%admin_login_lock}}');
    }
}
