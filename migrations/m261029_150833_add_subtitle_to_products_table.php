<?php

use yii\db\Migration;

class m261029_150833_add_subtitle_to_products_table extends Migration
{

    public function safeUp()
    {
        /* ADD PRODUCT SUBTITLE */

        $this->addColumn(
            '{{%products}}',
            'subtitle',
            $this->string(255)->null()->after('title')
        );
    }

    public function safeDown()
    {
        /* REMOVE PRODUCT SUBTITLE */

        $this->dropColumn(
            '{{%products}}',
            'subtitle'
        );
    }
}
