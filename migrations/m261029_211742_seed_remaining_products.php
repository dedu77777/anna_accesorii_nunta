<?php

use yii\db\Migration;
use yii\db\Query;

class m261029_211742_seed_remaining_products extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $now = time();

        // INSERT 3 CANDLE PRODUCTS AND 3 TOWEL PRODUCTS

        $this->batchInsert(
            '{{%products}}',
            [
                'category_id',
                'title',
                'subtitle',
                'description',
                'price',
                'image',
                'created_at',
                'updated_at',
            ],
            [
                // CANDLE 1
                [
                    1,
                    'Lumânări din ceară naturală de albine – pereche 40 cm',
                    'Handmade · 2 lumânări · 40 cm',
                    'Pereche de lumânări pentru cununie, realizate manual din ceară naturală de albine. Cu o înălțime de 40 cm, sunt potrivite pentru ceremonia religioasă. Prețul este pentru pereche.',
                    450.00,
                    'products/candles/candle-1-1.webp',
                    $now,
                    $now,
                ],

                // CANDLE 2
                [
                    1,
                    'Set de 4 lumânări din ceară naturală de albine',
                    'Handmade · 2 perechi · 40 cm și 26 cm',
                    'Set realizat din ceară naturală de albine, alcătuit din două perechi de lumânări: o pereche de 40 cm și o pereche de 26 cm. Potrivite pentru ceremonia de cununie. Prețul este pentru întregul set de 4 lumânări.',
                    650.00,
                    'products/candles/candle-2-1.webp',
                    $now,
                    $now,
                ],

                // CANDLE 3
                [
                    1,
                    'Lumânări handmade din ceară de albine – pereche 40 cm',
                    'Handmade · 2 lumânări · 40 cm',
                    'Pereche de lumânări pentru cununie, lucrate manual din ceară naturală de albine. Au o înălțime de 40 cm și completează ceremonia printr-un aspect simplu și elegant. Prețul este pentru pereche.',
                    500.00,
                    'products/candles/candle-3-1.webp',
                    $now,
                    $now,
                ],

                // TOWEL 1
                [
                    2,
                    'Prosoape pentru nași din bumbac natural',
                    'Țesute manual · Bumbac natural · Pereche',
                    'Pereche de prosoape tradiționale pentru nași, țesute manual din bumbac natural. Modelul este completat de detalii decorative, potrivite pentru ceremonia de nuntă. Prețul este pentru pereche.',
                    1500.00,
                    'products/prosoape/prosoape-1-1.png',
                    $now,
                    $now,
                ],

                // TOWEL 2
                [
                    2,
                    'Prosoape pentru nași din in natural – model tradițional',
                    'Țesute manual · In natural · Pereche',
                    'Pereche de prosoape pentru nași, realizate manual din in natural. Țesătura tradițională și detaliile decorative oferă un aspect elegant, potrivit obiceiurilor de nuntă. Prețul este pentru pereche.',
                    2500.00,
                    'products/prosoape/prosoape-2-1.png',
                    $now,
                    $now,
                ],

                // TOWEL 3
                [
                    2,
                    'Prosoape pentru nași din in natural – model elegant',
                    'Țesute manual · In natural · Pereche',
                    'Pereche de prosoape tradiționale pentru nași, țesute manual din in natural și finisate cu elemente decorative. Potrivite pentru ceremonia de nuntă și păstrarea tradițiilor. Prețul este pentru pereche.',
                    2700.00,
                    'products/prosoape/prosoape-3-1.png',
                    $now,
                    $now,
                ],
            ]
        );

        // GET INSERTED PRODUCT IDS USING THEIR COVER IMAGES

        $images = [
            'products/candles/candle-1-1.webp',
            'products/candles/candle-2-1.webp',
            'products/candles/candle-3-1.webp',
            'products/prosoape/prosoape-1-1.png',
            'products/prosoape/prosoape-2-1.png',
            'products/prosoape/prosoape-3-1.png',
        ];

        $ids = (new Query())
            ->select(['id', 'image'])
            ->from('{{%products}}')
            ->where(['image' => $images])
            ->indexBy('image')
            ->all($this->db);

        // VERIFY THAT ALL SIX PRODUCTS WERE INSERTED

        if (count($ids) !== 6) {
            throw new \RuntimeException(
                'Could not find all six inserted products.'
            );
        }

        // INSERT 18 GALLERY IMAGES

        $this->batchInsert(
            '{{%product_images}}',
            ['product_id', 'image', 'sort_order'],
            [
                // CANDLE 1
                [$ids['products/candles/candle-1-1.webp']['id'], 'products/candles/candle-1-1.webp', 1],
                [$ids['products/candles/candle-1-1.webp']['id'], 'products/candles/candle-1-2.webp', 2],
                [$ids['products/candles/candle-1-1.webp']['id'], 'products/candles/candle-1-3.webp', 3],

                // CANDLE 2
                [$ids['products/candles/candle-2-1.webp']['id'], 'products/candles/candle-2-1.webp', 1],
                [$ids['products/candles/candle-2-1.webp']['id'], 'products/candles/candle-2-2.webp', 2],
                [$ids['products/candles/candle-2-1.webp']['id'], 'products/candles/candle-2-3.webp', 3],

                // CANDLE 3
                [$ids['products/candles/candle-3-1.webp']['id'], 'products/candles/candle-3-1.webp', 1],
                [$ids['products/candles/candle-3-1.webp']['id'], 'products/candles/candle-3-2.webp', 2],
                [$ids['products/candles/candle-3-1.webp']['id'], 'products/candles/candle-3-3.webp', 3],

                // TOWEL 1
                [$ids['products/prosoape/prosoape-1-1.png']['id'], 'products/prosoape/prosoape-1-1.png', 1],
                [$ids['products/prosoape/prosoape-1-1.png']['id'], 'products/prosoape/prosoape-1-2.png', 2],
                [$ids['products/prosoape/prosoape-1-1.png']['id'], 'products/prosoape/prosoape-1-3.png', 3],

                // TOWEL 2
                [$ids['products/prosoape/prosoape-2-1.png']['id'], 'products/prosoape/prosoape-2-1.png', 1],
                [$ids['products/prosoape/prosoape-2-1.png']['id'], 'products/prosoape/prosoape-2-2.png', 2],
                [$ids['products/prosoape/prosoape-2-1.png']['id'], 'products/prosoape/prosoape-2-3.png', 3],

                // TOWEL 3
                [$ids['products/prosoape/prosoape-3-1.png']['id'], 'products/prosoape/prosoape-3-1.png', 1],
                [$ids['products/prosoape/prosoape-3-1.png']['id'], 'products/prosoape/prosoape-3-2.png', 2],
                [$ids['products/prosoape/prosoape-3-1.png']['id'], 'products/prosoape/prosoape-3-3.png', 3],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $images = [
            'products/candles/candle-1-1.webp',
            'products/candles/candle-2-1.webp',
            'products/candles/candle-3-1.webp',
            'products/prosoape/prosoape-1-1.png',
            'products/prosoape/prosoape-2-1.png',
            'products/prosoape/prosoape-3-1.png',
        ];

        // Related gallery images are removed by ON DELETE CASCADE
        $this->delete('{{%products}}', ['image' => $images]);

        return true;
    }
}
