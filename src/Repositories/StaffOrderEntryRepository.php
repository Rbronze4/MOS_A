<?php
declare(strict_types=1);

/**
 * スタッフ注文開始時に使用するDBアクセスをまとめるRepository。
 */
final class StaffOrderEntryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findCustomerForUpdate(string $storeId, int $customerId): ?array
    {
        $sql = <<<'SQL'
            SELECT customer_id, store_id, billing_status
            FROM customers
            WHERE customer_id = :customer_id
              AND store_id = :store_id
            LIMIT 1
            FOR UPDATE
        SQL;
        $statement = $this->pdo->prepare($sql);
        $statement->execute([':customer_id' => $customerId, ':store_id' => $storeId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function findCustomer(string $storeId, int $customerId): ?array
    {
        $sql = 'SELECT customer_id, store_id, billing_status FROM customers WHERE customer_id = :customer_id AND store_id = :store_id LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([':customer_id' => $customerId, ':store_id' => $storeId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function activePlansForStore(string $storeId): array
    {
        $sql = <<<'SQL'
            SELECT
                p.plan_id,
                p.plan_type_id,
                pt.plan_type_name,
                p.price,
                p.time_limit_minutes,
                p.is_active
            FROM plans AS p
            INNER JOIN plan_types AS pt ON pt.plan_type_id = p.plan_type_id
            WHERE p.store_id = :store_id
              AND p.is_active = 1
            ORDER BY p.plan_type_id, p.time_limit_minutes, p.plan_id
        SQL;
        $statement = $this->pdo->prepare($sql);
        $statement->execute([':store_id' => $storeId]);

        return $statement->fetchAll();
    }

    public function findActivePlanForUpdate(string $storeId, int $planId): ?array
    {
        $sql = <<<'SQL'
            SELECT p.plan_id, p.plan_type_id, p.price, p.time_limit_minutes, pt.plan_type_name
            FROM plans AS p
            INNER JOIN plan_types AS pt ON pt.plan_type_id = p.plan_type_id
            WHERE p.plan_id = :plan_id
              AND p.store_id = :store_id
              AND p.is_active = 1
            LIMIT 1
            FOR UPDATE
        SQL;
        $statement = $this->pdo->prepare($sql);
        $statement->execute([':plan_id' => $planId, ':store_id' => $storeId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /** 最新の利用中セッションと、それに対応する有効なプランを取得する。 */
    public function currentSelection(string $storeId, int $customerId, bool $forUpdate = false): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $sql = <<<'SQL'
            SELECT
                s.session_id,
                s.customer_id,
                s.table_number,
                s.started_at AS session_started_at,
                s.expired_at,
                cp.customer_plan_id,
                cp.plan_id,
                cp.started_at AS plan_started_at,
                cp.ended_at AS plan_ended_at,
                p.plan_type_id,
                pt.plan_type_name,
                p.is_active AS plan_is_active
            FROM sessions AS s
            LEFT JOIN customer_plans AS cp
                ON cp.customer_id = s.customer_id
               AND cp.started_at <= s.started_at
               AND (cp.ended_at IS NULL OR cp.ended_at > NOW())
            LEFT JOIN plans AS p
                ON p.plan_id = cp.plan_id
               AND p.store_id = s.store_id
            LEFT JOIN plan_types AS pt ON pt.plan_type_id = p.plan_type_id
            WHERE s.customer_id = :customer_id
              AND s.store_id = :store_id
              AND s.session_status = 'ACTIVE'
            ORDER BY s.session_id DESC, cp.customer_plan_id DESC
            LIMIT 1
        SQL;
        $statement = $this->pdo->prepare($sql . $lock);
        $statement->execute([':customer_id' => $customerId, ':store_id' => $storeId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function insertCustomerPlan(int $customerId, array $plan, string $startedAt, string $endedAt): void
    {
        $sql = <<<'SQL'
            INSERT INTO customer_plans (customer_id, plan_id, started_at, ended_at, unit_price)
            VALUES (:customer_id, :plan_id, :started_at, :ended_at, :unit_price)
        SQL;
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            ':customer_id' => $customerId,
            ':plan_id' => (int)$plan['plan_id'],
            ':started_at' => $startedAt,
            ':ended_at' => $endedAt,
            ':unit_price' => (int)$plan['price'],
        ]);
    }

    public function insertSession(int $customerId, string $storeId, string $tableNumber, string $startedAt, ?string $expiredAt): int
    {
        $sql = <<<'SQL'
            INSERT INTO sessions (customer_id, store_id, table_number, session_status, started_at, expired_at)
            VALUES (:customer_id, :store_id, :table_number, 'ACTIVE', :started_at, :expired_at)
        SQL;
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            ':customer_id' => $customerId,
            ':store_id' => $storeId,
            ':table_number' => $tableNumber,
            ':started_at' => $startedAt,
            ':expired_at' => $expiredAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function insertCart(int $sessionId): void
    {
        $statement = $this->pdo->prepare('INSERT INTO carts (session_id, version_no) VALUES (:session_id, 0)');
        $statement->execute([':session_id' => $sessionId]);
    }
}
