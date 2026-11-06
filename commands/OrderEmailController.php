<?php

namespace app\commands;

use app\components\OrderEmail;
use app\models\Order;
use Yii;
use yii\console\Controller;

class OrderEmailController extends Controller
{
    public function actionProcess(int $limit = 20): int
    {
        $db = Yii::$app->db;
        $limit = max(1, min(100, $limit));
        $processed = 0;
        while ($processed < $limit) {
            $tx = $db->beginTransaction();
            try {
                $row = $db->createCommand('SELECT id, order_id, kind FROM {{%order_email_outbox}}
                    WHERE (status=\'pending\' OR (status=\'failed\' AND locked_at < :retry)
                        OR (status=\'processing\' AND locked_at < :stale))
                    AND attempts < 5 ORDER BY id LIMIT 1 FOR UPDATE',
                    [':stale' => time() - 600, ':retry' => time() - 60])->queryOne();
                if (!$row) { $tx->commit(); break; }
                $db->createCommand()->update('{{%order_email_outbox}}', [
                    'status' => 'processing', 'attempts' => new \yii\db\Expression('attempts + 1'),
                    'locked_at' => time(),
                ], ['id' => $row['id']])->execute();
                $tx->commit();
            } catch (\Throwable $e) {
                if ($tx->getIsActive()) $tx->rollBack();
                Yii::error($e, __METHOD__);
                return 1;
            }
            try {
                $order = Order::findOne($row['order_id']);
                if (!$order || !OrderEmail::send($order, $row['kind'])) throw new \RuntimeException('Mail transport did not confirm send.');
                $db->createCommand()->update('{{%order_email_outbox}}', [
                    'status' => 'sent', 'sent_at' => time(), 'locked_at' => null,
                ], ['id' => $row['id'], 'status' => 'processing'])->execute();
            } catch (\Throwable $e) {
                Yii::warning('Order email ' . $row['id'] . ' failed: ' . get_class($e), __METHOD__);
                $db->createCommand()->update('{{%order_email_outbox}}', [
                    'status' => 'failed', 'locked_at' => time(),
                ], ['id' => $row['id'], 'status' => 'processing'])->execute();
            }
            $processed++;
        }
        $this->stdout("Processed {$processed} notification(s).\n");
        return 0;
    }
}
