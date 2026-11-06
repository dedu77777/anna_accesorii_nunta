<?php

namespace app\controllers;

use app\components\ApiRateLimiter;
use app\components\SiteRefresh;
use app\components\SiteSeo;
use app\models\Cart;
use app\models\CartItem;
use app\models\Category;
use app\models\Products;

use Yii;
use yii\filters\VerbFilter;
use yii\db\Expression;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\helpers\Url;
use yii\web\TooManyRequestsHttpException;

class WebsiteController extends Controller
{
    public $layout = 'website/main-layout';


    /* ==========================================
   CONTROLLER BEHAVIORS
========================================== */

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // HTTP METHOD PROTECTION
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                //Home Page
                'home' => ['GET'],
                'site-version' => ['GET'],
                'sitemap' => ['GET'],
                'despre-noi' => ['GET'],
                'produse' => ['GET'],
                'cauta-produse' => ['GET'],
                'detalii-produs' => ['GET'],

                // CART OPERATIONS
                'adauga-in-cos' => ['POST'],
                'cos' => ['GET'],
                'actualizeaza-cos' => ['PUT'],
                'sterge-din-cos' => ['DELETE'],

                //CheckOut
                'plaseaza-comanda' => ['POST'],

            ],
        ];

        return $behaviors;
    }


    public function actionSiteVersion(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->headers->set('Cache-Control', 'no-store, private');
        return ['version' => SiteRefresh::version()];
    }

    public function actionSitemap(): string
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        Yii::$app->response->headers->set('Cache-Control', 'public, max-age=0, must-revalidate');

        $urls = [SiteSeo::url(), SiteSeo::url('/produse'), SiteSeo::url('/despre-noi')];
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as $url) {
            $xml[] = '<url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>';
        }

        $products = Products::find()
            ->select(['id', 'updated_at'])
            ->orderBy(['id' => SORT_ASC])
            ->asArray();

        foreach ($products->each(200) as $product) {
            $url = SiteSeo::url('/produse/' . (int) $product['id']);
            $entry = '<url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';

            if ((int) $product['updated_at'] > 0) {
                $entry .= '<lastmod>' . gmdate('Y-m-d', (int) $product['updated_at']) . '</lastmod>';
            }

            $xml[] = $entry . '</url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml);
    }

    public function actionHome(): string
    {
        $categories = Category::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();

        $products = Products::find()->with('images')->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->limit(8)->all();

        return $this->render('home', ['categories' => $categories, 'products' => $products]);
    }

    public function actionDespreNoi(): string
    {
        return $this->render('despre-noi');
    }

    public function actionProduse(): string
    {
        $categories = Category::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        $search = $this->getSearchTerm();

        $selectedCategory = (int) Yii::$app->request->get('category', 0);

        if (
            $selectedCategory > 0 &&
            !Category::find()->where(['id' => $selectedCategory])->exists()
        ) {
            throw new \yii\web\NotFoundHttpException(
                'Categoria nu a fost găsită.'
            );
        }

        $query = Products::find();

        if ($search !== '') {
            $query->andWhere(['or', ['like', 'title', $search], ['like', 'subtitle', $search]]);
        }

        // FILTER BY CATEGORY
        if ($selectedCategory > 0) {
            $query->andWhere([
                'category_id' => $selectedCategory,
            ]);
        }

        // PAGINATION: 6 PRODUCTS PER PAGE
        $pagination = new \yii\data\Pagination([
            'totalCount' => (clone $query)->count(),
            'pageSize' => 6,
            'pageSizeParam' => false,
            'forcePageParam' => false,
            'params' => [
                'category' => $selectedCategory ?: null,
                'q' => $search ?: null,
                'page' => Yii::$app->request->get('page', 1),
            ],
        ]);

        // FETCH PRODUCTS FROM MYSQL
        $products = $query
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();


        return $this->render('produse', ['categories' => $categories, 'products' => $products, 'pagination' => $pagination, 'selectedCategory' => $selectedCategory, 'search' => $search]);
    }

    public function actionCautaProduse(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $search = $this->getSearchTerm();

        if (mb_strlen($search) < 2) {
            return ['products' => []];
        }

        $products = Products::find()
            ->select(['id', 'title', 'image'])
            ->where(['or', ['like', 'title', $search], ['like', 'subtitle', $search]])
            ->with(['images' => static function ($query): void {
                $query->select(['id', 'product_id', 'image', 'sort_order']);
            }])
            ->orderBy(new Expression(
                'CASE WHEN LOCATE(:searchTerm, title) > 0 THEN 0 ELSE 1 END',
                [':searchTerm' => $search]
            ))
            ->addOrderBy(['id' => SORT_ASC])
            ->limit(8)
            ->all();

        $results = [];

        foreach ($products as $product) {
            $image = $product->images[0]->image ?? $product->image;

            $results[] = [
                'id' => (int) $product->id,
                'title' => $product->title,
                'imageUrl' => $image ? Url::to('@web/images/' . ltrim($image, '/')) : null,
                'url' => Url::to(['/website/detalii-produs', 'id' => $product->id]),
            ];
        }

        return ['products' => $results];
    }

    private function getSearchTerm(): string
    {
        $searchInput = Yii::$app->request->get('q', '');

        if (!is_string($searchInput)) {
            throw new BadRequestHttpException('Căutarea nu este validă.');
        }

        $search = trim($searchInput);

        if (mb_strlen($search) > 100) {
            throw new BadRequestHttpException('Căutarea este prea lungă.');
        }

        return $search;
    }

    public function actionDetaliiProdus(int $id): string
    {
        $product = Products::find()->with(['images', 'category'])->where(['id' => $id])->one();

        if ($product == null) {
            throw new \yii\web\NotFoundHttpException(
                'Produsul nu a fost găsit.'
            );
        }

        return $this->render('detalii-produs', ['product' => $product]);
    }

    private function getCurrentCart(bool $create = false): ?Cart
    {
        $session = Yii::$app->session;

        if (!$session->getIsActive()) {
            $session->open();
        }

        $sessionId = $session->getId();

        $cart = Cart::findOne([
            'session_id' => $sessionId,
        ]);

        if ($cart !== null || !$create) {
            return $cart;
        }

        $cart = new Cart();
        $cart->session_id = $sessionId;

        if (!$cart->save()) {
            throw new \RuntimeException(
                'Coșul nu a putut fi creat.'
            );
        }

        return $cart;
    }

    public function actionAdaugaInCos(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;


        /* ==========================================
   ADD TO CART RATE LIMIT
========================================== */

        try {
            ApiRateLimiter::check(
                'adauga-in-cos',
                10,
                60
            );
        } catch (TooManyRequestsHttpException $exception) {
            Yii::$app->response->statusCode = 429;

            return [
                'success' => false,
                'message' => 'Prea multe solicitări. Încearcă din nou peste câteva momente.',
            ];
        }


        $request = Yii::$app->request;

        $productId = filter_var(
            $request->post('product_id'),
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $request->post('quantity'),
            FILTER_VALIDATE_INT
        );

        // VALIDATE INPUT
        if (
            $productId === false ||
            $productId === null ||
            $productId < 1 ||
            $quantity === false ||
            $quantity === null ||
            $quantity < 1 ||
            $quantity > 10
        ) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Produsul sau cantitatea este invalidă.',
            ];
        }

        $product = Products::findOne($productId);

        if ($product === null) {
            Yii::$app->response->statusCode = 404;

            return [
                'success' => false,
                'message' => 'Produsul nu a fost găsit.',
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // FIND OR CREATE VISITOR CART
            $cart = $this->getCurrentCart(true);

            // LOCK CART ROW FOR CONCURRENT REQUESTS
            Yii::$app->db->createCommand(
                'SELECT id FROM {{%cart}} WHERE id = :id FOR UPDATE',
                [':id' => $cart->id]
            )->queryOne();

            // FIND EXISTING CART ITEM
            $cartItem = CartItem::findOne([
                'cart_id' => $cart->id,
                'product_id' => $productId,
            ]);

            $existingQuantity = $cartItem !== null
                ? (int) $cartItem->quantity
                : 0;

            $newQuantity = $existingQuantity + $quantity;

            // MAXIMUM 10 UNITS PER PRODUCT
            if ($newQuantity > 10) {
                $transaction->rollBack();

                Yii::$app->response->statusCode = 422;

                return [
                    'success' => false,
                    'message' => 'Poți adăuga maximum 10 bucăți din acest produs în coș.',
                ];
            }

            // CREATE CART ITEM IF NECESSARY
            if ($cartItem === null) {
                $cartItem = new CartItem();
                $cartItem->cart_id = $cart->id;
                $cartItem->product_id = $productId;
            }

            // SAVE NEW QUANTITY
            $cartItem->quantity = $newQuantity;

            if (!$cartItem->save()) {
                throw new \RuntimeException(
                    'Produsul nu a putut fi adăugat în coș.'
                );
            }

            // UPDATE CART TIMESTAMP
            $cart->updated_at = time();

            if (!$cart->save()) {
                throw new \RuntimeException(
                    'Coșul nu a putut fi actualizat.'
                );
            }

            // TOTAL NUMBER OF ITEMS
            $cartCount = (int) CartItem::find()
                ->where(['cart_id' => $cart->id])
                ->sum('quantity');

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Produsul a fost adăugat în coș.',
                'cartCount' => $cartCount,
                'productQuantity' => $newQuantity,
            ];
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            Yii::error($e, __METHOD__);

            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => 'A apărut o eroare. Încearcă din nou.',
            ];
        }
    }

    public function actionCos(): string
    {
        $cart = $this->getCurrentCart();

        // FETCH CART ITEMS WITH PRODUCTS
        $items = $cart !== null
            ? CartItem::find()
            ->with('product')
            ->where(['cart_id' => $cart->id])
            ->orderBy(['id' => SORT_ASC])
            ->all()
            : [];

        // CALCULATE TOTAL AND CART COUNT
        $total = 0;
        $cartCount = 0;

        foreach ($items as $item) {
            if ($item->product === null) {
                continue;
            }

            $quantity = (int) $item->quantity;
            $price = (float) $item->product->price;

            $total += $price * $quantity;
            $cartCount += $quantity;
        }

        // RENDER CART PAGE
        return $this->render('cos', [
            'items' => $items,
            'total' => round($total, 2),
            'cartCount' => $cartCount,
        ]);
    }


    /* ==========================================
   UPDATE CART ITEM QUANTITY - PUT
========================================== */

    public function actionActualizeazaCos(): array
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            ApiRateLimiter::check('actualizeaza-cos', 20, 60);
        } catch (TooManyRequestsHttpException $exception) {
            Yii::$app->response->statusCode = 429;

            return [
                'success' => false,
                'message' => 'Prea multe solicitări. Încearcă din nou peste câteva momente.',
            ];
        }

        // VALIDATE INPUT
        $cartItemId = filter_var(
            Yii::$app->request->getBodyParam('cart_item_id'),
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            Yii::$app->request->getBodyParam('quantity'),
            FILTER_VALIDATE_INT
        );

        if (
            $cartItemId === false ||
            $cartItemId === null ||
            $cartItemId < 1 ||
            $quantity === false ||
            $quantity === null ||
            $quantity < 1 ||
            $quantity > 10
        ) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Cantitatea trebuie să fie între 1 și 10.',
            ];
        }

        // GET CURRENT VISITOR CART
        $cart = $this->getCurrentCart();

        if ($cart === null) {
            Yii::$app->response->statusCode = 404;

            return [
                'success' => false,
                'message' => 'Coșul nu a fost găsit.',
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // LOCK CART TO SERIALIZE CART MODIFICATIONS
            Yii::$app->db->createCommand(
                'SELECT id FROM {{%cart}} WHERE id = :id FOR UPDATE',
                [':id' => $cart->id]
            )->queryOne();

            // FIND ITEM BELONGING TO THIS VISITOR
            $cartItem = CartItem::findOne([
                'id' => $cartItemId,
                'cart_id' => $cart->id,
            ]);

            if ($cartItem === null) {
                $transaction->rollBack();

                Yii::$app->response->statusCode = 404;

                return [
                    'success' => false,
                    'message' => 'Produsul nu există în coș.',
                ];
            }

            // UPDATE QUANTITY
            $cartItem->quantity = $quantity;

            if (!$cartItem->save()) {
                throw new \RuntimeException(
                    'Cantitatea nu a putut fi actualizată.'
                );
            }

            // UPDATE CART TIMESTAMP
            $cart->updated_at = time();

            if (!$cart->save()) {
                throw new \RuntimeException(
                    'Coșul nu a putut fi actualizat.'
                );
            }

            // CALCULATE UPDATED TOTALS
            $cartItems = CartItem::find()
                ->with('product')
                ->where(['cart_id' => $cart->id])
                ->all();

            $cartCount = 0;
            $total = 0;
            $subtotal = 0;

            foreach ($cartItems as $item) {
                if ($item->product === null) {
                    continue;
                }

                $itemQuantity = (int) $item->quantity;
                $itemSubtotal = (float) $item->product->price * $itemQuantity;

                $cartCount += $itemQuantity;
                $total += $itemSubtotal;

                if ((int) $item->id === $cartItemId) {
                    $subtotal = $itemSubtotal;
                }
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Cantitatea a fost actualizată.',
                'quantity' => $quantity,
                'subtotal' => round($subtotal, 2),
                'total' => round($total, 2),
                'cartCount' => $cartCount,
            ];
        } catch (\Throwable $exception) {
            if ($transaction->getIsActive()) {
                $transaction->rollBack();
            }

            Yii::error($exception, __METHOD__);

            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => 'A apărut o eroare. Încearcă din nou.',
            ];
        }
    }


    /* ==========================================
   DELETE CART ITEM - DELETE
========================================== */

    public function actionStergeDinCos(): array
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            ApiRateLimiter::check('sterge-din-cos', 10, 60);
        } catch (TooManyRequestsHttpException $exception) {
            Yii::$app->response->statusCode = 429;

            return [
                'success' => false,
                'message' => 'Prea multe solicitări. Încearcă din nou peste câteva momente.',
            ];
        }

        // VALIDATE CART ITEM ID
        $cartItemId = filter_var(
            Yii::$app->request->getBodyParam('cart_item_id'),
            FILTER_VALIDATE_INT
        );

        if (
            $cartItemId === false ||
            $cartItemId === null ||
            $cartItemId < 1
        ) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Produs invalid.',
            ];
        }

        // GET CURRENT VISITOR CART
        $cart = $this->getCurrentCart();

        if ($cart === null) {
            Yii::$app->response->statusCode = 404;

            return [
                'success' => false,
                'message' => 'Coșul nu a fost găsit.',
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // LOCK CART TO SERIALIZE CART MODIFICATIONS
            Yii::$app->db->createCommand(
                'SELECT id FROM {{%cart}} WHERE id = :id FOR UPDATE',
                [':id' => $cart->id]
            )->queryOne();

            // FIND ITEM BELONGING TO THIS VISITOR
            $cartItem = CartItem::findOne([
                'id' => $cartItemId,
                'cart_id' => $cart->id,
            ]);

            if ($cartItem === null) {
                $transaction->rollBack();

                Yii::$app->response->statusCode = 404;

                return [
                    'success' => false,
                    'message' => 'Produsul nu există în coș.',
                ];
            }

            // DELETE CART ITEM
            if ($cartItem->delete() === false) {
                throw new \RuntimeException(
                    'Produsul nu a putut fi eliminat.'
                );
            }

            // UPDATE CART TIMESTAMP
            $cart->updated_at = time();

            if (!$cart->save()) {
                throw new \RuntimeException(
                    'Coșul nu a putut fi actualizat.'
                );
            }

            // RECALCULATE CART TOTALS
            $cartItems = CartItem::find()
                ->with('product')
                ->where(['cart_id' => $cart->id])
                ->all();

            $cartCount = 0;
            $total = 0;

            foreach ($cartItems as $item) {
                if ($item->product === null) {
                    continue;
                }

                $cartCount += (int) $item->quantity;

                $total +=
                    (float) $item->product->price *
                    (int) $item->quantity;
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Produsul a fost eliminat din coș.',
                'cartCount' => $cartCount,
                'total' => round($total, 2),
            ];
        } catch (\Throwable $exception) {
            if ($transaction->getIsActive()) {
                $transaction->rollBack();
            }

            Yii::error($exception, __METHOD__);

            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => 'A apărut o eroare. Încearcă din nou.',
            ];
        }
    }
}
