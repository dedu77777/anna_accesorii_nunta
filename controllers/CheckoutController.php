<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\ApiRateLimiter;
use app\models\Cart;
use app\models\CartItem;
use app\models\CheckoutForm;
use app\models\Order;
use Yii;
use yii\db\Transaction;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

class CheckoutController extends Controller
{
    public $layout = 'website/main-layout';

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'submit' => ['POST'],
                    'confirm' => ['GET'],
                    'privacy' => ['GET'],
                ],
            ],
        ];
    }

    private function sessionId(): string
    {
        if (!Yii::$app->session->getIsActive()) {
            Yii::$app->session->open();
        }

        return Yii::$app->session->getId();
    }

    private function sessionHash(): string
    {
        return hash_hmac(
            'sha256',
            $this->sessionId(),
            Yii::$app->request->cookieValidationKey
        );
    }

    private function cart(): ?Cart
    {
        return Cart::findOne([
            'session_id' => $this->sessionId(),
        ]);
    }

    public function actionIndex(): Response|string
    {
        $cart = $this->cart();

        $items = $cart
            ? CartItem::find()
            ->with('product.images')
            ->where(['cart_id' => $cart->id])
            ->all()
            : [];

        if (!$items) {
            return $this->redirect(['/website/cos']);
        }

        $form = new CheckoutForm();
        $form->idempotency_key = bin2hex(random_bytes(32));

        Yii::$app->session->set(
            'checkout_key',
            $form->idempotency_key
        );

        return $this->render('index', [
            'form' => $form,
            'items' => $items,
        ]);
    }

    public function actionSubmit(): Response|string
    {
        $request = Yii::$app->request;

        if (strlen($request->getRawBody()) > 16384) {
            Yii::$app->response->statusCode = 413;
            return 'Cererea este prea mare.';
        }

        $input = $request->post();

        $allowed = [
            $request->csrfParam,
            'full_name',
            'email',
            'phone_number',
            'delivery_method',
            'delivery_address',
            'additional_information',
            'privacy_accepted',
            'idempotency_key',
        ];

        if (
            array_diff(array_keys($input), $allowed)
            || array_filter($input, 'is_array')
        ) {
            Yii::$app->response->statusCode = 422;
            return 'Datele trimise nu sunt valide.';
        }

        $form = new CheckoutForm();
        $form->load($input, '');

        // Prevent duplicate orders from previously completed requests.
        $key = (string) $form->idempotency_key;
        $sessionHash = $this->sessionHash();

        $idempotency = hash_hmac(
            'sha256',
            $this->sessionId() . '|' . $key,
            $request->cookieValidationKey
        );

        if (preg_match('/\A[a-f0-9]{64}\z/D', $key)) {
            $existing = Order::findOne([
                'idempotency_key' => $idempotency,
                'session_hash' => $sessionHash,
            ]);

            if ($existing) {
                return $this->redirect([
                    '/checkout/confirm',
                    'number' => $existing->order_number,
                ]);
            }
        }

        if (!$form->validate()) {
            return $this->renderForm($form);
        }

        if (!hash_equals(
            (string) Yii::$app->session->get('checkout_key', ''),
            $key
        )) {
            Yii::$app->response->statusCode = 422;
            return 'Formularul a expirat. Deschide din nou pagina comenzii.';
        }

        try {
            ApiRateLimiter::check('plaseaza-comanda', 20, 60);
        } catch (TooManyRequestsHttpException $e) {
            Yii::$app->response->statusCode = 429;
            return 'Prea multe solicitări. Încearcă din nou mai târziu.';
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $cart = $this->cart();

            if (!$cart) {
                return $this->abortTransaction(
                    $transaction,
                    $form,
                    'Coșul este gol.'
                );
            }

            $db->createCommand(
                'SELECT id FROM {{%cart}}
                 WHERE id = :id FOR UPDATE',
                [':id' => $cart->id]
            )->queryOne();

            $items = CartItem::find()
                ->with('product.images')
                ->where(['cart_id' => $cart->id])
                ->all();

            if (!$items) {
                return $this->abortTransaction(
                    $transaction,
                    $form,
                    'Coșul este gol.'
                );
            }

            $snapshots = [];
            $totalCents = 0;

            foreach ($items as $item) {
                $product = $item->product;
                $quantity = (int) $item->quantity;

                if (!$product || $quantity < 1 || $quantity > 10) {
                    return $this->abortTransaction(
                        $transaction,
                        $form,
                        'Un produs din coș nu mai este disponibil.'
                    );
                }

                $price = (string) $product->price;

                if (!preg_match('/\A\d{1,10}\.\d{2}\z/D', $price)) {
                    return $this->abortTransaction(
                        $transaction,
                        $form,
                        'Prețul unui produs nu este valid.'
                    );
                }

                [$whole, $fraction] = explode('.', $price, 2);

                $cents = (int) $whole * 100 + (int) $fraction;
                $line = $cents * $quantity;

                $totalCents += $line;

                $image = $product->images[0]->image ?? $product->image;

                $image = is_string($image)
                    && preg_match(
                        '~\Aproducts/[a-zA-Z0-9/_-]+\.(?:png|jpe?g|webp)\z~D',
                        $image
                    )
                    ? $image
                    : null;

                $snapshots[] = [
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'unit_price' => self::money($cents),
                    'quantity' => $quantity,
                    'line_total' => self::money($line),
                    'product_image' => $image,
                ];
            }

            // Check the rolling 30-minute successful-order limit.
            $now = time();

            $retryAfter = ApiRateLimiter::checkOrderLimit(
                (string) $form->email,
                (string) $form->phone_number,
                $now
            );

            if ($retryAfter > 0) {
                $transaction->rollBack();

                Yii::$app->response->statusCode = 429;

                Yii::$app->response->headers->set(
                    'Retry-After',
                    (string) $retryAfter
                );

                $form->addError(
                    'full_name',
                    'Ai atins limita de 3 comenzi în 30 de minute. '
                        . 'Încearcă mai târziu.'
                );

                return $this->renderForm($form);
            }

            $number = strtoupper(bin2hex(random_bytes(8)));

            $db->createCommand()->insert('{{%orders}}', [
                'order_number' => $number,
                'session_hash' => $sessionHash,
                'idempotency_key' => $idempotency,
                'full_name' => trim((string) $form->full_name),
                'email' => mb_strtolower(trim((string) $form->email)),
                'phone_number' => preg_replace(
                    '/[^0-9+]/',
                    '',
                    (string) $form->phone_number
                ),
                'delivery_method' => $form->delivery_method,
                'delivery_address' =>
                $form->delivery_method === 'home_delivery'
                    ? trim((string) $form->delivery_address)
                    : null,
                'additional_information' =>
                trim((string) $form->additional_information) ?: null,
                'subtotal' => self::money($totalCents),
                'total' => self::money($totalCents),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            $orderId = (int) $db->getLastInsertID();

            foreach ($snapshots as $snapshot) {
                $db->createCommand()->insert(
                    '{{%order_items}}',
                    ['order_id' => $orderId] + $snapshot
                )->execute();
            }

            foreach (['customer', 'owner'] as $kind) {
                $db->createCommand()->insert(
                    '{{%order_email_outbox}}',
                    [
                        'order_id' => $orderId,
                        'kind' => $kind,
                        'status' => 'pending',
                        'attempts' => 0,
                        'created_at' => $now,
                    ]
                )->execute();
            }

            CartItem::deleteAll(['cart_id' => $cart->id]);

            // Record quota usage in the same transaction.
            ApiRateLimiter::recordOrder(
                $orderId,
                (string) $form->email,
                (string) $form->phone_number,
                $now
            );

            $transaction->commit();

            Yii::$app->session->remove('checkout_key');

            return $this->redirect([
                '/checkout/confirm',
                'number' => $number,
            ]);
        } catch (\Throwable $e) {
            if ($transaction->getIsActive()) {
                $transaction->rollBack();
            }

            Yii::error($e, __METHOD__);

            $existing = Order::findOne([
                'idempotency_key' => $idempotency,
                'session_hash' => $sessionHash,
            ]);

            if ($existing) {
                return $this->redirect([
                    '/checkout/confirm',
                    'number' => $existing->order_number,
                ]);
            }

            Yii::$app->response->statusCode = 500;

            $form->addError(
                'full_name',
                'Comanda nu a putut fi salvată. Încearcă din nou.'
            );

            return $this->renderForm($form);
        }
    }

    private static function money(int $cents): string
    {
        return intdiv($cents, 100) . '.'
            . str_pad(
                (string) ($cents % 100),
                2,
                '0',
                STR_PAD_LEFT
            );
    }

    private function abortTransaction(
        Transaction $transaction,
        CheckoutForm $form,
        string $message
    ): string {
        $transaction->rollBack();

        Yii::$app->response->statusCode = 422;
        $form->addError('full_name', $message);

        return $this->renderForm($form);
    }

    private function renderForm(CheckoutForm $form): string
    {
        $cart = $this->cart();

        $items = $cart
            ? CartItem::find()
            ->with('product.images')
            ->where(['cart_id' => $cart->id])
            ->all()
            : [];

        return $this->render('index', [
            'form' => $form,
            'items' => $items,
        ]);
    }

    public function actionConfirm(string $number): Response|string
    {
        if (!preg_match('/\A[A-F0-9]{16}\z/D', $number)) {
            throw new NotFoundHttpException();
        }

        $order = Order::findOne([
            'order_number' => $number,
            'session_hash' => $this->sessionHash(),
        ]);

        if (!$order) {
            throw new NotFoundHttpException();
        }

        Yii::$app->response->headers->set(
            'Cache-Control',
            'private, no-store'
        );

        return $this->render('confirm', [
            'order' => $order,
        ]);
    }

    public function actionPrivacy(): string
    {
        return $this->render('privacy');
    }
}
