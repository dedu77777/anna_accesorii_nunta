
<?php

use yii\db\Migration;
use yii\db\Query;

class m261029_200000_seed_lumanari_products extends Migration
{
    public function safeUp()
    {
        $now = time();

        /* INSERT PRODUCTS */

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
                [
                    3,
                    'Buchete pentru miri și nași – model clasic',
                    'Handmade · Set de 4 buchete',
                    'Set elegant de patru accesorii florale pentru miri și nași, realizate manual cu funde delicate din satin ivoire, perle decorative și detalii din lemn. Designul clasic adaugă o notă rafinată ținutelor de ceremonie. Prețul afișat este pentru întregul set de 4 bucăți.',
                    300.00,
                    'products/buchete/buchete-1-1.png',
                    $now,
                    $now,
                ],
                [
                    3,
                    'Buchete pentru miri și nași – model auriu',
                    'Handmade · Set de 4 buchete',
                    'Set de patru accesorii florale pentru miri și nași, lucrate manual și decorate cu perle, panglici ivoire și elemente aurii. Combinația de nuanțe calde și detalii strălucitoare oferă un aspect elegant, potrivit unei ceremonii de nuntă. Prețul afișat este pentru întregul set de 4 bucăți.',
                    300.00,
                    'products/buchete/buchete-2-1.png',
                    $now,
                    $now,
                ],
                [
                    3,
                    'Buchete pentru miri și nași – model argintiu',
                    'Handmade · Set de 4 buchete',
                    'Set rafinat de patru accesorii florale pentru miri și nași, realizate manual din panglici ivoire și ornamente cu perle decorative. Accentele argintii completează designul delicat și oferă un aspect armonios pentru ziua nunții. Prețul afișat este pentru întregul set de 4 bucăți.',
                    300.00,
                    'products/buchete/buchete-3-1.png',
                    $now,
                    $now,
                ],
            ]
        );

        /* GET INSERTED PRODUCT IDS */

        $product1 = (new Query())
            ->select('id')
            ->from('{{%products}}')
            ->where([
                'image' => 'products/buchete/buchete-1-1.png',
            ])
            ->orderBy(['id' => SORT_DESC])
            ->scalar($this->db);

        $product2 = (new Query())
            ->select('id')
            ->from('{{%products}}')
            ->where([
                'image' => 'products/buchete/buchete-2-1.png',
            ])
            ->orderBy(['id' => SORT_DESC])
            ->scalar($this->db);

        $product3 = (new Query())
            ->select('id')
            ->from('{{%products}}')
            ->where([
                'image' => 'products/buchete/buchete-3-1.png',
            ])
            ->orderBy(['id' => SORT_DESC])
            ->scalar($this->db);

        if (!$product1 || !$product2 || !$product3) {
            throw new \RuntimeException(
                'Cannot find the inserted bouquet products.'
            );
        }

        /* INSERT PRODUCT IMAGES */

        $this->batchInsert(
            '{{%product_images}}',
            [
                'product_id',
                'image',
                'sort_order',
            ],
            [
                [
                    $product1,
                    'products/buchete/buchete-1-1.png',
                    1,
                ],
                [
                    $product1,
                    'products/buchete/buchete-1-2.png',
                    2,
                ],
                [
                    $product1,
                    'products/buchete/buchete-1-3.png',
                    3,
                ],
                [
                    $product2,
                    'products/buchete/buchete-2-1.png',
                    1,
                ],
                [
                    $product2,
                    'products/buchete/buchete-2-2.png',
                    2,
                ],
                [
                    $product2,
                    'products/buchete/buchete-2-3.png',
                    3,
                ],
                [
                    $product3,
                    'products/buchete/buchete-3-1.png',
                    1,
                ],
                [
                    $product3,
                    'products/buchete/buchete-3-2.png',
                    2,
                ],
                [
                    $product3,
                    'products/buchete/buchete-3-3.png',
                    3,
                ],
            ]
        );
    }

    public function safeDown()
    {
        echo "This seed migration cannot be safely reverted automatically.\n";

        return false;
    }
}
