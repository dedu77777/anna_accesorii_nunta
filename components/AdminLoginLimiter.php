<?php

namespace app\components;

use app\models\AdminLoginForm;
use Yii;

final class AdminLoginLimiter
{
    /** Null means blocked; otherwise return whether the credentials validated. */
    public static function attempt(AdminLoginForm $form): ?bool
    {
        $request = Yii::$app->request;
        $secret = $request->cookieValidationKey;
        $email = mb_strtolower(trim($form->email));
        $ip = $request->userIP ?? 'unknown';
        $scopes = [
            hash_hmac('sha256', 'admin-email|' . $email, $secret) => 5,
            hash_hmac('sha256', 'admin-ip|' . $ip, $secret) => 20,
        ];
        ksort($scopes, SORT_STRING);

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            foreach ($scopes as $scope => $limit) {
                $db->createCommand(
                    'INSERT INTO {{%admin_login_lock}} (scope_key) VALUES (:scope)
                     ON DUPLICATE KEY UPDATE scope_key = scope_key',
                    [':scope' => $scope]
                )->execute();
                $db->createCommand(
                    'SELECT scope_key FROM {{%admin_login_lock}} WHERE scope_key = :scope FOR UPDATE',
                    [':scope' => $scope]
                )->queryOne();
            }

            $now = time();
            $retryAfter = 0;
            foreach ($scopes as $scope => $limit) {
                $times = $db->createCommand(
                    'SELECT failed_at FROM {{%admin_login_failure}}
                     WHERE scope_key = :scope AND failed_at > :cutoff
                     ORDER BY failed_at ASC FOR UPDATE',
                    [':scope' => $scope, ':cutoff' => $now - 3600]
                )->queryColumn();
                if (count($times) >= $limit) {
                    $retryAfter = max($retryAfter, (int) $times[count($times) - $limit] + 3600 - $now);
                }
            }

            if ($retryAfter > 0) {
                $transaction->rollBack();
                Yii::$app->response->headers->set('Retry-After', (string) $retryAfter);
                return null;
            }

            $valid = $form->validate();
            if (!$valid) {
                foreach ($scopes as $scope => $limit) {
                    $db->createCommand()->insert('{{%admin_login_failure}}', [
                        'scope_key' => $scope,
                        'failed_at' => $now,
                    ])->execute();
                }
            }
            $transaction->commit();
            if (random_int(1, 100) === 1) {
                try {
                    $db->createCommand(
                        'DELETE FROM {{%admin_login_failure}} WHERE failed_at <= :cutoff LIMIT 100',
                        [':cutoff' => time() - 3600]
                    )->execute();
                } catch (\Throwable $error) {
                    Yii::warning('Admin login failure cleanup failed: ' . $error->getMessage(), __METHOD__);
                }
            }
            return $valid;
        } catch (\Throwable $error) {
            if ($transaction->getIsActive()) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }
}
