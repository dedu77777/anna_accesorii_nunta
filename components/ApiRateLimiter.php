<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\TooManyRequestsHttpException;

/* ==========================================
   API RATE LIMITER
========================================== */

class ApiRateLimiter extends Component
{
    /**
     * Limit API requests by IP address.
     */
    public static function check(
        string $action,
        int $limit = 10,
        int $window = 60
    ): void {
        if ($limit < 1 || $window < 1) {
            throw new \InvalidArgumentException(
                'Invalid rate limit configuration.'
            );
        }

        // CLIENT IP ADDRESS
        $ip = Yii::$app->request->userIP ?? 'unknown';

        // CURRENT TIME WINDOW
        $now = time();
        $windowStart = intdiv($now, $window) * $window;
        $expiresAt = $windowStart + $window;

        // UNIQUE KEY PER ACTION, IP AND WINDOW
        $key = hash(
            'sha256',
            $action . '|' . $ip . '|' . $windowStart
        );

        /* ==========================================
           ATOMIC REQUEST COUNTER
        ========================================== */

        Yii::$app->db->createCommand(
            'INSERT INTO {{%api_rate_limit}}
                (rate_key, request_count, expires_at)
             VALUES
                (:rate_key, 1, :expires_at)
             ON DUPLICATE KEY UPDATE
                request_count = request_count + 1',
            [
                ':rate_key' => $key,
                ':expires_at' => $expiresAt,
            ]
        )->execute();

        // GET CURRENT REQUEST COUNT
        $count = (int) Yii::$app->db->createCommand(
            'SELECT request_count
             FROM {{%api_rate_limit}}
             WHERE rate_key = :rate_key',
            [
                ':rate_key' => $key,
            ]
        )->queryScalar();

        /* ==========================================
           RATE LIMIT VALIDATION
        ========================================== */

        if ($count > $limit) {
            $retryAfter = max(1, $expiresAt - time());

            Yii::$app->response->headers->set(
                'Retry-After',
                (string) $retryAfter
            );

            throw new TooManyRequestsHttpException(
                'Prea multe solicitări. Încearcă din nou peste câteva momente.'
            );
        }

        /* ==========================================
           AUTOMATIC DATABASE CLEANUP
        ========================================== */

        // APPROXIMATELY 1% OF ALLOWED REQUESTS
        if (random_int(1, 100) === 1) {
            try {
                Yii::$app->db->createCommand(
                    'DELETE FROM {{%api_rate_limit}}
                     WHERE expires_at < :now
                     LIMIT 100',
                    [
                        ':now' => time(),
                    ]
                )->execute();
            } catch (\Throwable $exception) {
                Yii::warning(
                    'Rate limit cleanup failed: ' . $exception->getMessage(),
                    __METHOD__
                );
            }
        }
    }


    /**
     * Check successful orders in a rolling 30-minute window.
     * Must run inside the checkout database transaction.
     * Returns the number of seconds to wait, or 0 if allowed.
     */
    public static function checkOrderLimit(
        string $email,
        string $phone,
        int $now
    ): int {
        $db = Yii::$app->db;

        if (!$db->getTransaction()?->getIsActive()) {
            throw new \LogicException('Transaction required.');
        }

        $scopes = self::orderScopes($email, $phone);
        sort($scopes, SORT_STRING);

        foreach ($scopes as $scope) {
            $db->createCommand(
                'INSERT INTO {{%order_rate_limit_lock}} (scope_key)
             VALUES (:scope)
             ON DUPLICATE KEY UPDATE scope_key = scope_key',
                [':scope' => $scope]
            )->execute();

            $db->createCommand(
                'SELECT scope_key FROM {{%order_rate_limit_lock}}
             WHERE scope_key = :scope FOR UPDATE',
                [':scope' => $scope]
            )->queryOne();
        }

        $retryAfter = 0;

        foreach ($scopes as $scope) {
            $times = $db->createCommand(
                'SELECT created_at FROM {{%order_rate_limit_event}}
             WHERE scope_key = :scope AND created_at > :cutoff
             ORDER BY created_at ASC FOR UPDATE',
                [
                    ':scope' => $scope,
                    ':cutoff' => $now - 1800,
                ]
            )->queryColumn();

            if (count($times) >= 3) {
                $oldestRelevant = (int) $times[count($times) - 3];

                $retryAfter = max(
                    $retryAfter,
                    $oldestRelevant + 1801 - $now
                );
            }
        }

        return max(0, $retryAfter);
    }

    /**
     * Record a successfully created order.
     * Call inside the same transaction before commit.
     */
    public static function recordOrder(
        int $orderId,
        string $email,
        string $phone,
        int $now
    ): void {
        if (!Yii::$app->db->getTransaction()?->getIsActive()) {
            throw new \LogicException('Transaction required.');
        }

        foreach (self::orderScopes($email, $phone) as $scope) {
            Yii::$app->db->createCommand()->insert(
                '{{%order_rate_limit_event}}',
                [
                    'scope_key' => $scope,
                    'order_id' => $orderId,
                    'created_at' => $now,
                ]
            )->execute();
        }
    }

    private static function orderScopes(
        string $email,
        string $phone
    ): array {
        $secret = Yii::$app->request->cookieValidationKey;

        $email = mb_strtolower(trim($email));
        $phone = preg_replace('/\D/', '', $phone);
        return [
            hash_hmac('sha256', 'email|' . $email, $secret),
            hash_hmac('sha256', 'phone|' . $phone, $secret),
        ];
    }
}
