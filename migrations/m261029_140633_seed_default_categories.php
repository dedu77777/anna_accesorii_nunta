<?php

use yii\db\Migration;

class m261029_140633_seed_default_categories extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->batchInsert(
            '{{%category}}',
            ['image', 'title'],
            [
                [
                    'lumanari_cateogorie.jpeg',
                    'Lumânări',
                ],
                [
                    'prosoape_categorii.jpg',
                    'Prosoape pentru nași',
                ],
                [
                    'buchete_categoreii.jpg',
                    'Buchete pentru miri și nași',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m261029_140633_seed_default_categories cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261029_140633_seed_default_categories cannot be reverted.\n";

        return false;
    }
    */
}
