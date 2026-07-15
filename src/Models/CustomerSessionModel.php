<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

/**
 * 客側の利用セッションを作成または再利用するModel。
 *
 * 同じcustomer_idに有効なcustomer_plansがある場合は、そのプランを再利用し、
 * プラン選択をやり直さずにsessions / cartsだけを作成または再利用する。
 */
final class CustomerSessionModel
{
    private const PLAN_TYPE_BY_KEY = [
        'standard' => 1,
        'premium' => 2,
    ];

    private const BILLING_STATUS_ACCEPTING = 1;

    /**
     * customer_id + table_number単位でACTIVEセッションを作成または再利用する。
     *
     * 有効なcustomer_plansがある場合、$planKeyは不要。
     * 有効なcustomer_plansがない場合だけ、画面で選択されたプランを使ってcustomer_plansを作成する。
     */
    public function start(int $customerId, string $tableNumber, ?string $planKey, ?int $planMinutes): array
    {
        $this->validateTableNumber($tableNumber);

        $pdo = db();

        try {
            $pdo->beginTransaction();

            $customer = $this->findCustomer($customerId);

            if ($customer === null) {
                throw new RuntimeException('顧客情報が見つかりません。');
            }

            if ((int)$customer['billing_status'] !== self::BILLING_STATUS_ACCEPTING) {
                throw new RuntimeException('このQRコードは利用できない状態です。');
            }

            $storeId = (string)$customer['store_id'];
            $activeCustomerPlan = $this->findActiveCustomerPlanForUpdate($customerId);
            $plan = null;
            $usedExistingPlan = false;

            if ($activeCustomerPlan !== null) {
                $plan = $this->findPlanById((int)$activeCustomerPlan['plan_id'], $storeId);
                $usedExistingPlan = true;
            } else {
                $plan = $this->resolvePlan($storeId, $planKey, $planMinutes);

                if ($plan !== null) {
                    $this->insertCustomerPlan($customerId, $plan);
                }
            }

            $session = $this->findActiveSession($customerId, $tableNumber);
            $sessionCreated = false;

            if ($session === null) {
                $sessionId = $this->insertSession($customerId, $storeId, $tableNumber, $plan);
                $sessionCreated = true;
            } else {
                $sessionId = (int)$session['session_id'];
            }

            $cartId = $this->findCartId($sessionId);
            $cartCreated = false;

            if ($cartId === null) {
                $cartId = $this->insertCart($sessionId);
                $cartCreated = true;
            }

            $pdo->commit();

            return [
                'customer_id' => $customerId,
                'store_id' => $storeId,
                'session_id' => $sessionId,
                'cart_id' => $cartId,
                'session_created' => $sessionCreated,
                'cart_created' => $cartCreated,
                'plan_id' => $plan === null ? null : (int)$plan['plan_id'],
                'plan_key' => $planKey,
                'used_existing_plan' => $usedExistingPlan,
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function startForStaff(
        int $customerId,
        string $tableNumber,
        ?string $planKey,
        ?int $planMinutes,
        string $storeId
    ): array {
        $this->validateTableNumber($tableNumber);

        $pdo = db();

        try {
            $pdo->beginTransaction();

            $customer = $this->findCustomer($customerId);

            if ($customer === null) {
                throw new RuntimeException('顧客情報が見つかりません。');
            }

            if ((string)$customer['store_id'] !== $storeId) {
                throw new RuntimeException('注文対象の顧客がログイン中の店舗と一致しません。');
            }

            $activeCustomerPlan = $this->findActiveCustomerPlanForUpdate($customerId);
            $plan = null;
            $usedExistingPlan = false;

            if ($activeCustomerPlan !== null) {
                $plan = $this->findPlanById((int)$activeCustomerPlan['plan_id'], $storeId);
                $usedExistingPlan = true;
            } else {
                $plan = $this->resolvePlan($storeId, $planKey, $planMinutes);

                if ($plan !== null) {
                    $this->insertCustomerPlan($customerId, $plan);
                }
            }

            $session = $this->findActiveSession($customerId, $tableNumber);
            $sessionCreated = false;

            if ($session === null) {
                $sessionId = $this->insertSession($customerId, $storeId, $tableNumber, $plan);
                $sessionCreated = true;
            } else {
                $sessionId = (int)$session['session_id'];
            }

            $cartId = $this->findCartId($sessionId);
            $cartCreated = false;

            if ($cartId === null) {
                $cartId = $this->insertCart($sessionId);
                $cartCreated = true;
            }

            $pdo->commit();

            return [
                'customer_id' => $customerId,
                'store_id' => $storeId,
                'session_id' => $sessionId,
                'cart_id' => $cartId,
                'session_created' => $sessionCreated,
                'cart_created' => $cartCreated,
                'plan_id' => $plan === null ? null : (int)$plan['plan_id'],
                'plan_key' => $planKey,
                'used_existing_plan' => $usedExistingPlan,
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * session_idからACTIVEセッションを取得する。
     */
    public function activeSession(int $sessionId): ?array
    {
        $sql = <<<SQL
            SELECT
                session_id,
                customer_id,
                store_id,
                table_number,
                session_status
            FROM sessions
            WHERE session_id = :session_id
              AND session_status = 'ACTIVE'
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();

        $session = $statement->fetch();

        return $session === false ? null : $session;
    }

    /**
     * customer_idからcustomersを取得する。
     */
    public function findCustomer(int $customerId): ?array
    {
        $sql = <<<SQL
            SELECT
                customer_id,
                store_id,
                people_count,
                billing_status
            FROM customers
            WHERE customer_id = :customer_id
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->execute();

        $customer = $statement->fetch();

        return $customer === false ? null : $customer;
    }

    /**
     * QR再読み込み時の画面制御用に、有効なcustomer_plansを取得する。
     */
    public function activeCustomerPlan(int $customerId): ?array
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
                p.store_id,
                p.price,
                p.time_limit_minutes
            FROM customer_plans AS cp
            INNER JOIN plans AS p
                ON p.plan_id = cp.plan_id
            WHERE cp.customer_id = :customer_id
              AND cp.ended_at IS NULL
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->execute();

        $customerPlan = $statement->fetch();

        return $customerPlan === false ? null : $customerPlan;
    }

    /**
     * 卓番号はsessions.table_numberに保存する文字列として扱う。
     */
    private function validateTableNumber(string $tableNumber): void
    {
        if (!preg_match('/^\d{1,3}$/', $tableNumber)) {
            throw new InvalidArgumentException('卓番号を数字で入力してください。');
        }
    }

    /**
     * 初回のみ、画面用planKeyからDBのplans行を取得する。
     */
    private function resolvePlan(string $storeId, ?string $planKey, ?int $planMinutes): ?array
    {
        if ($planKey === 'single') {
            return null;
        }

        if ($planKey === null || !isset(self::PLAN_TYPE_BY_KEY[$planKey])) {
            throw new InvalidArgumentException('プランを選択してください。');
        }

        $minutes = $planMinutes ?? 120;

        $sql = <<<SQL
            SELECT
                plan_id,
                store_id,
                price,
                time_limit_minutes,
                is_active
            FROM plans
            WHERE store_id = :store_id
              AND plan_type_id = :plan_type_id
              AND time_limit_minutes = :time_limit_minutes
              AND is_active = 1
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->bindValue(':plan_type_id', self::PLAN_TYPE_BY_KEY[$planKey], PDO::PARAM_INT);
        $statement->bindValue(':time_limit_minutes', $minutes, PDO::PARAM_INT);
        $statement->execute();

        $plan = $statement->fetch();

        if ($plan === false) {
            throw new RuntimeException('選択されたプランがこの店舗で利用できません。');
        }

        return $plan;
    }

    /**
     * 既存customer_plansのplan_idが今も有効か確認する。
     */
    private function findPlanById(int $planId, string $storeId): array
    {
        $sql = <<<SQL
            SELECT
                plan_id,
                store_id,
                price,
                time_limit_minutes,
                is_active
            FROM plans
            WHERE plan_id = :plan_id
              AND store_id = :store_id
              AND is_active = 1
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':plan_id', $planId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $plan = $statement->fetch();

        if ($plan === false) {
            throw new RuntimeException('既存のプランがこの店舗で利用できません。');
        }

        return $plan;
    }

    private function insertCustomerPlan(int $customerId, array $plan): void
    {
        $sql = <<<SQL
            INSERT INTO customer_plans (
                customer_id,
                plan_id,
                started_at,
                ended_at,
                unit_price
            )
            VALUES (
                :customer_id,
                :plan_id,
                NOW(),
                NULL,
                :unit_price
            )
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':plan_id', (int)$plan['plan_id'], PDO::PARAM_INT);
        $statement->bindValue(':unit_price', (int)$plan['price'], PDO::PARAM_INT);
        $statement->execute();
    }

    private function findActiveCustomerPlanForUpdate(int $customerId): ?array
    {
        $sql = <<<SQL
            SELECT
                customer_plan_id,
                customer_id,
                plan_id,
                started_at,
                ended_at,
                unit_price
            FROM customer_plans
            WHERE customer_id = :customer_id
              AND ended_at IS NULL
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->execute();

        $customerPlan = $statement->fetch();

        return $customerPlan === false ? null : $customerPlan;
    }

    /**
     * 同じcustomer_id + table_numberのACTIVEセッションは再利用する。
     */
    private function findActiveSession(int $customerId, string $tableNumber): ?array
    {
        $sql = <<<SQL
            SELECT
                session_id,
                customer_id,
                store_id,
                table_number,
                session_status
            FROM sessions
            WHERE customer_id = :customer_id
              AND table_number = :table_number
              AND session_status = 'ACTIVE'
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':table_number', $tableNumber, PDO::PARAM_STR);
        $statement->execute();

        $session = $statement->fetch();

        return $session === false ? null : $session;
    }

    private function insertSession(int $customerId, string $storeId, string $tableNumber, ?array $plan): int
    {
        $sql = <<<SQL
            INSERT INTO sessions (
                customer_id,
                store_id,
                table_number,
                session_status,
                started_at,
                expired_at
            )
            VALUES (
                :customer_id,
                :store_id,
                :table_number,
                'ACTIVE',
                NOW(),
                :expired_at
            )
        SQL;

        $expiredAt = null;

        if ($plan !== null) {
            $minutes = (int)$plan['time_limit_minutes'];
            $expiredAt = date('Y-m-d H:i:s', time() + ($minutes * 60));
        }

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->bindValue(':table_number', $tableNumber, PDO::PARAM_STR);

        if ($expiredAt === null) {
            $statement->bindValue(':expired_at', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':expired_at', $expiredAt, PDO::PARAM_STR);
        }

        $statement->execute();

        return (int)db()->lastInsertId();
    }

    /**
     * cartsはsession_id単位。既存があれば再利用する。
     */
    private function findCartId(int $sessionId): ?int
    {
        $sql = <<<SQL
            SELECT cart_id
            FROM carts
            WHERE session_id = :session_id
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();

        $cart = $statement->fetch();

        return $cart === false ? null : (int)$cart['cart_id'];
    }

    private function insertCart(int $sessionId): int
    {
        $sql = <<<SQL
            INSERT INTO carts (
                session_id,
                version_no
            )
            VALUES (
                :session_id,
                0
            )
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();

        return (int)db()->lastInsertId();
    }
}
