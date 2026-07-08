<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

final class StaffCustomerModel
{
    /**
     * QR発行用に、新しい顧客を1件作成する。
     *
     * customer_id は AUTO_INCREMENT に任せて連番で採番する（現在の最大IDの次番号）。
     * QRトークンのハッシュ化は開発中は見送り、識別しやすい平文プレースホルダを入れる。
     * 発行された顧客は、そのまま customer_id で客側画面を利用できる。
     *
     * @return array{customer_id:int, store_id:string, people_count:int}
     */
    public function issueCustomer(string $storeId, int $peopleCount): array
    {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            // customer_id は指定しない（AUTO_INCREMENTで自動連番）。
            $insert = $pdo->prepare(
                'INSERT INTO customers (store_id, qr_token_hash, people_count, billing_status)
                 VALUES (:store_id, :placeholder, :people_count, \'UNPAID\')'
            );
            $insert->bindValue(':store_id', $storeId, PDO::PARAM_STR);
            $insert->bindValue(':placeholder', 'pending', PDO::PARAM_STR);
            $insert->bindValue(':people_count', $peopleCount, PDO::PARAM_INT);
            $insert->execute();

            $customerId = (int)$pdo->lastInsertId();

            // ハッシュ化は未実装のため、開発中に識別しやすい平文トークンを入れておく。
            $update = $pdo->prepare('UPDATE customers SET qr_token_hash = :token WHERE customer_id = :customer_id');
            $update->bindValue(':token', 'dev_qr_token_' . $customerId, PDO::PARAM_STR);
            $update->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $update->execute();

            $pdo->commit();

            return [
                'customer_id' => $customerId,
                'store_id' => $storeId,
                'people_count' => $peopleCount,
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function customersForStore(string $storeId): array
    {
        $sql = <<<SQL
            SELECT
                c.customer_id,
                c.store_id,
                c.people_count,
                c.billing_status,
                GROUP_CONCAT(
                    DISTINCT s.table_number
                    ORDER BY s.table_number ASC
                    SEPARATOR ', '
                ) AS table_numbers
            FROM customers AS c
            LEFT JOIN sessions AS s
                ON s.customer_id = c.customer_id
               AND s.store_id = c.store_id
               AND s.session_status = 'ACTIVE'
            WHERE c.store_id = :store_id
            GROUP BY
                c.customer_id,
                c.store_id,
                c.people_count,
                c.billing_status
            ORDER BY c.customer_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $customers = [];

        foreach ($statement->fetchAll() as $row) {
            $tableNumbers = trim((string)($row['table_numbers'] ?? ''));

            $customers[] = [
                'customer_id' => (int)$row['customer_id'],
                'customer_no' => (string)$row['customer_id'],
                'table_no' => $tableNumbers !== '' ? $tableNumbers : 'なし',
                'people' => (int)$row['people_count'],
                'billing_status' => (string)$row['billing_status'],
            ];
        }

        return $customers;
    }

    public function customerDetail(string $storeId, int $customerId): array
    {
        $customer = $this->findCustomer($storeId, $customerId);

        if ($customer === null) {
            throw new RuntimeException('顧客情報が見つかりません。');
        }

        $plan = $this->activePlanForCustomer($customerId);
        $sessions = $this->activeSessionsForCustomer($storeId, $customerId);

        return [
            'customer_id' => (int)$customer['customer_id'],
            'people_count' => (int)$customer['people_count'],
            'plan_label' => $this->planLabel($plan),
            'table_numbers' => $this->tableNumberLabels($sessions),
            'has_active_session' => $sessions !== [],
            'billing_status_label' => $this->billingStatusLabel((string)$customer['billing_status']),
        ];
    }

    private function findCustomer(string $storeId, int $customerId): ?array
    {
        $sql = <<<SQL
            SELECT
                customer_id,
                store_id,
                people_count,
                billing_status,
                created_at,
                updated_at
            FROM customers
            WHERE customer_id = :customer_id
              AND store_id = :store_id
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $customer = $statement->fetch();

        return $customer === false ? null : $customer;
    }

    private function activePlanForCustomer(int $customerId): ?array
    {
        $sql = <<<SQL
            SELECT
                cp.customer_plan_id,
                cp.customer_id,
                cp.plan_id,
                cp.started_at,
                cp.ended_at,
                cp.unit_price,
                p.plan_type_id,
                pt.plan_type_name
            FROM customer_plans AS cp
            INNER JOIN plans AS p
                ON cp.plan_id = p.plan_id
            LEFT JOIN plan_types AS pt
                ON p.plan_type_id = pt.plan_type_id
            WHERE cp.customer_id = :customer_id
              AND cp.ended_at IS NULL
            ORDER BY cp.started_at DESC, cp.customer_plan_id DESC
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->execute();

        $plan = $statement->fetch();

        return $plan === false ? null : $plan;
    }

    private function activeSessionsForCustomer(string $storeId, int $customerId): array
    {
        $sql = <<<SQL
            SELECT
                session_id,
                table_number,
                session_status,
                started_at
            FROM sessions
            WHERE customer_id = :customer_id
              AND store_id = :store_id
              AND session_status = 'ACTIVE'
            ORDER BY
                table_number ASC,
                started_at ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function planLabel(?array $plan): string
    {
        if ($plan === null) {
            return 'なし';
        }

        $planTypeName = trim((string)($plan['plan_type_name'] ?? ''));

        if ($planTypeName !== '') {
            return $planTypeName;
        }

        return 'あり';
    }

    private function tableNumberLabels(array $sessions): array
    {
        $labels = [];

        foreach ($sessions as $session) {
            $tableNumber = trim((string)$session['table_number']);

            if ($tableNumber !== '') {
                $labels[] = $tableNumber . '番';
            }
        }

        return array_values(array_unique($labels));
    }

    private function billingStatusLabel(string $status): string
    {
        return match ($status) {
            'UNPAID' => '未会計',
            'PAYMENT_PENDING' => '会計待ち',
            'PAID' => '会計済み',
            'CANCELLED', 'CANCELED' => 'キャンセル',
            default => '不明',
        };
    }
}
