<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

/**
 * スタッフ側の注文一覧を取得するModel。
 *
 * スタッフ画面では「どの商品を何個提供するか」を扱うため、
 * ordersではなくorder_detailsの1行を1表示行として返す。
 */
final class StaffOrderModel
{
    public function activeSessionByTable(string $storeId, string $tableNumber, bool $forUpdate = false): ?array
    {
        $lockSql = $forUpdate ? 'FOR UPDATE' : '';
        $sql = <<<SQL
            SELECT
                session_id,
                customer_id,
                store_id,
                table_number,
                session_status
            FROM sessions
            WHERE store_id = :store_id
              AND table_number = :table_number
              AND session_status = 'ACTIVE'
            ORDER BY started_at DESC
            LIMIT 1
            $lockSql
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->bindValue(':table_number', $tableNumber, PDO::PARAM_STR);
        $statement->execute();

        $session = $statement->fetch();

        return $session === false ? null : $session;
    }

    public function activeSessionByCustomer(string $storeId, int $customerId, ?string $tableNumber = null, bool $forUpdate = false): ?array
    {
        $tableSql = $tableNumber === null ? '' : 'AND table_number = :table_number';
        $lockSql = $forUpdate ? 'FOR UPDATE' : '';
        $sql = <<<SQL
            SELECT
                session_id,
                customer_id,
                store_id,
                table_number,
                session_status
            FROM sessions
            WHERE store_id = :store_id
              AND customer_id = :customer_id
              AND session_status = 'ACTIVE'
              $tableSql
            ORDER BY started_at DESC
            LIMIT 1
            $lockSql
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);

        if ($tableNumber !== null) {
            $statement->bindValue(':table_number', $tableNumber, PDO::PARAM_STR);
        }

        $statement->execute();

        $session = $statement->fetch();

        return $session === false ? null : $session;
    }

    public function planTypeIdForSession(int $sessionId): ?int
    {
        $sql = <<<SQL
            SELECT
                p.plan_type_id
            FROM sessions AS s
            INNER JOIN customer_plans AS cp
                ON cp.customer_id = s.customer_id
            INNER JOIN plans AS p
                ON p.plan_id = cp.plan_id
            WHERE s.session_id = :session_id
              AND s.session_status = 'ACTIVE'
              AND cp.ended_at IS NULL
              AND p.is_active = 1
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();

        $planTypeId = $statement->fetchColumn();

        return $planTypeId === false ? null : (int)$planTypeId;
    }

    public function submitStaffOrder(string $storeId, array $payload): array
    {
        $tableNumber = trim((string)($payload['table_number'] ?? $payload['tableNo'] ?? ''));
        $customerId = filter_var($payload['customer_id'] ?? null, FILTER_VALIDATE_INT);
        $items = $this->normalizeStaffOrderItems($payload['items'] ?? null);

        if ($tableNumber === '' && ($customerId === false || $customerId === null || $customerId < 1)) {
            throw new InvalidArgumentException('卓番号または顧客番号を指定してください。');
        }

        if ($tableNumber !== '' && !preg_match('/^\d{1,2}$/', $tableNumber)) {
            throw new InvalidArgumentException('卓番号は1〜2桁の数字で入力してください。');
        }

        if ($items === []) {
            throw new InvalidArgumentException('注文する商品を選択してください。');
        }

        $pdo = db();

        try {
            $pdo->beginTransaction();

            if ($customerId !== false && $customerId !== null && $customerId > 0) {
                $session = $this->activeSessionByCustomer(
                    $storeId,
                    (int)$customerId,
                    $tableNumber === '' ? null : $tableNumber,
                    true
                );
            } else {
                $session = $this->activeSessionByTable($storeId, $tableNumber, true);
            }

            if ($session === null) {
                throw new RuntimeException('指定された卓番号または顧客番号の利用中セッションが見つかりません。先にQR読込または卓番号入力とプラン選択を行ってください。');
            }

            $planTypeId = $this->planTypeIdForSession((int)$session['session_id']);
            $productIds = array_column($items, 'product_id');
            $products = $this->onSaleProductsForStaffOrder($storeId, $productIds, $planTypeId);

            if (count($products) !== count($productIds)) {
                throw new RuntimeException('販売中ではない商品が含まれています。メニューを再読み込みしてください。');
            }

            $orderId = $this->insertStaffOrder((int)$session['session_id']);
            $totalQuantity = 0;
            $totalAmount = 0;

            foreach ($items as $item) {
                $product = $products[(int)$item['product_id']];
                $quantity = (int)$item['quantity'];
                $unitPrice = (int)$product['plan_applied_flag'] === 1 ? 0 : (int)$product['price'];
                $planAppliedFlag = (int)$product['plan_applied_flag'];

                $this->insertStaffOrderDetail(
                    $orderId,
                    (int)$product['product_id'],
                    (string)$product['product_name'],
                    $quantity,
                    $unitPrice,
                    $planAppliedFlag
                );

                $totalQuantity += $quantity;
                $totalAmount += $unitPrice * $quantity;
            }

            $pdo->commit();

            return [
                'order_id' => $orderId,
                'session_id' => (int)$session['session_id'],
                'customer_id' => (int)$session['customer_id'],
                'table_number' => (string)$session['table_number'],
                'total_quantity' => $totalQuantity,
                'total_amount' => $totalAmount,
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * ログイン中店舗の注文詳細一覧を取得する。
     *
     * 店舗判定はsessions.store_idで行う。
     * 固定店舗IDは使わず、Controllerから渡されたログイン中のstore_idを必ずバインドする。
     */
    public function ordersForStore(string $storeId): array
    {
        $sql = <<<SQL
            SELECT
                od.order_detail_id,
                od.order_id,
                od.product_id,
                od.ordered_product_name,
                od.quantity,
                od.provided_quantity,
                od.ordered_unit_price,
                od.plan_applied_flag,
                od.detail_status,
                od.ordered_at,
                o.session_id,
                o.ordered_at AS order_ordered_at,
                s.store_id,
                s.table_number,
                s.customer_id
            FROM order_details AS od
            INNER JOIN orders AS o
                ON o.order_id = od.order_id
            INNER JOIN sessions AS s
                ON s.session_id = o.session_id
            WHERE s.store_id = :store_id
            ORDER BY
                o.ordered_at ASC,
                od.order_detail_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $orders = [];

        foreach ($statement->fetchAll() as $row) {
            $orders[] = $this->formatOrderRow($row);
        }

        return $orders;
    }

    public function orderDetailsForCustomer(string $storeId, int $customerId): array
    {
        $this->assertCustomerBelongsToStore($storeId, $customerId);

        $sql = <<<SQL
            SELECT
                od.order_detail_id,
                od.order_id,
                od.product_id,
                od.ordered_product_name,
                od.quantity,
                od.provided_quantity,
                od.ordered_unit_price,
                od.plan_applied_flag,
                od.detail_status,
                od.ordered_at,
                od.cancelled_at,
                o.session_id,
                o.ordered_at AS order_ordered_at,
                s.store_id,
                s.table_number,
                s.customer_id
            FROM order_details AS od
            INNER JOIN orders AS o
                ON o.order_id = od.order_id
            INNER JOIN sessions AS s
                ON s.session_id = o.session_id
            INNER JOIN customers AS c
                ON c.customer_id = s.customer_id
               AND c.store_id = s.store_id
            WHERE s.store_id = :store_id
              AND s.customer_id = :customer_id
            ORDER BY
                o.ordered_at DESC,
                od.order_detail_id DESC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->execute();

        $orders = [];

        foreach ($statement->fetchAll() as $row) {
            $orders[] = $this->formatOrderRow($row);
        }

        return $orders;
    }

    public function updateCustomerOrderDetailQuantity(
        string $storeId,
        int $customerId,
        int $orderDetailId,
        int $quantity
    ): array {
        if ($quantity < 1) {
            throw new InvalidArgumentException('数量は1以上で入力してください。');
        }

        $pdo = db();

        try {
            $pdo->beginTransaction();

            $detail = $this->findCustomerOrderDetailForUpdate($storeId, $customerId, $orderDetailId);

            if ($detail === null) {
                throw new RuntimeException('注文詳細が見つかりません。');
            }

            if ((string)$detail['detail_status'] === 'CANCELLED') {
                throw new RuntimeException('キャンセル済みの商品は数量変更できません。');
            }

            $providedQuantity = (int)$detail['provided_quantity'];
            $minimumQuantity = max(1, $providedQuantity);

            if ($quantity < $minimumQuantity) {
                throw new RuntimeException('提供済み数より少ない数量には変更できません。');
            }

            $detailStatus = $providedQuantity >= $quantity ? 'PROVIDED' : 'ORDERED';
            $providedAt = $detailStatus === 'PROVIDED'
                ? ((string)($detail['provided_at'] ?? '') !== '' ? (string)$detail['provided_at'] : date('Y-m-d H:i:s'))
                : ($providedQuantity > 0 ? ($detail['provided_at'] ?? null) : null);

            $sql = <<<SQL
                UPDATE order_details
                SET
                    quantity = :quantity,
                    detail_status = :detail_status,
                    provided_at = :provided_at,
                    updated_at = NOW()
                WHERE order_detail_id = :order_detail_id
            SQL;

            $statement = $pdo->prepare($sql);
            $statement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $statement->bindValue(':detail_status', $detailStatus, PDO::PARAM_STR);

            if ($providedAt === null || $providedAt === '') {
                $statement->bindValue(':provided_at', null, PDO::PARAM_NULL);
            } else {
                $statement->bindValue(':provided_at', (string)$providedAt, PDO::PARAM_STR);
            }

            $statement->bindValue(':order_detail_id', $orderDetailId, PDO::PARAM_INT);
            $statement->execute();

            $updated = $this->findCustomerOrderDetailForUpdate($storeId, $customerId, $orderDetailId);

            if ($updated === null) {
                throw new RuntimeException('更新後の注文詳細が見つかりません。');
            }

            $pdo->commit();

            return $this->formatOrderRow($updated);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function cancelCustomerOrderDetail(string $storeId, int $customerId, int $orderDetailId): array
    {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $detail = $this->findCustomerOrderDetailForUpdate($storeId, $customerId, $orderDetailId);

            if ($detail === null) {
                throw new RuntimeException('注文詳細が見つかりません。');
            }

            if ((string)$detail['detail_status'] === 'CANCELLED') {
                throw new RuntimeException('すでにキャンセル済みです。');
            }

            if ((int)$detail['provided_quantity'] > 0) {
                throw new RuntimeException('提供済みの商品はキャンセルできません。');
            }

            $sql = <<<SQL
                UPDATE order_details
                SET
                    detail_status = 'CANCELLED',
                    cancelled_at = NOW(),
                    provided_quantity = 0,
                    provided_at = NULL,
                    updated_at = NOW()
                WHERE order_detail_id = :order_detail_id
            SQL;

            $statement = $pdo->prepare($sql);
            $statement->bindValue(':order_detail_id', $orderDetailId, PDO::PARAM_INT);
            $statement->execute();

            $updated = $this->findCustomerOrderDetailForUpdate($storeId, $customerId, $orderDetailId);

            if ($updated === null) {
                throw new RuntimeException('キャンセル後の注文詳細が見つかりません。');
            }

            $pdo->commit();

            return $this->formatOrderRow($updated);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * 提供数を更新する。
     *
     * 操作対象はorder_detailsの1行。ログイン中店舗以外の明細を更新しないよう、
     * orders/sessionsをJOINしてstore_idも同時に確認する。
     */
    public function updateProvidedQuantity(string $storeId, int $orderDetailId, string $action): array
    {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $detail = $this->findOrderDetailForUpdate($storeId, $orderDetailId);

            if ($detail === null) {
                throw new RuntimeException('注文明細が見つかりません。');
            }

            if ((string)$detail['detail_status'] === 'CANCELLED') {
                throw new RuntimeException('キャンセル済みの注文は提供数を変更できません。');
            }

            $quantity = (int)$detail['quantity'];
            $providedQuantity = (int)$detail['provided_quantity'];

            if ($action === 'serveOne') {
                $providedQuantity = min($quantity, $providedQuantity + 1);
            } elseif ($action === 'serveAll') {
                $providedQuantity = $quantity;
            } elseif ($action === 'minusOne') {
                $providedQuantity = max(0, $providedQuantity - 1);
            } elseif ($action === 'undoServe') {
                $providedQuantity = 0;
            } else {
                throw new InvalidArgumentException('提供数の操作が正しくありません。');
            }

            $detailStatus = $providedQuantity >= $quantity ? 'PROVIDED' : 'ORDERED';
            $providedAt = $detailStatus === 'PROVIDED' ? date('Y-m-d H:i:s') : null;

            $this->updateProvidedQuantityRow($orderDetailId, $providedQuantity, $detailStatus, $providedAt);

            $updated = $this->findOrderDetailForUpdate($storeId, $orderDetailId);

            if ($updated === null) {
                throw new RuntimeException('更新後の注文明細が見つかりません。');
            }

            $pdo->commit();

            return $this->formatOrderRow($updated);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function cancelOrderDetails(string $storeId, array $orderDetailIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $orderDetailIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            throw new InvalidArgumentException('キャンセル対象の注文明細が選択されていません。');
        }

        $pdo = db();
        $placeholders = [];
        $params = [
            ':store_id' => $storeId,
        ];

        foreach ($ids as $index => $id) {
            $placeholder = ':order_detail_id_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $inSql = implode(', ', $placeholders);

        try {
            $pdo->beginTransaction();

            // ログイン中の店舗に紐づく注文明細だけをキャンセル対象にします。
            $updateSql = <<<SQL
                UPDATE order_details AS od
                INNER JOIN orders AS o
                    ON o.order_id = od.order_id
                INNER JOIN sessions AS s
                    ON s.session_id = o.session_id
                SET
                    od.detail_status = 'CANCELLED',
                    od.cancelled_at = NOW(),
                    od.provided_quantity = 0,
                    od.provided_at = NULL
                WHERE s.store_id = :store_id
                  AND od.order_detail_id IN ($inSql)
            SQL;

            $statement = $pdo->prepare($updateSql);
            $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);

            foreach ($ids as $index => $id) {
                $statement->bindValue(':order_detail_id_' . $index, $id, PDO::PARAM_INT);
            }

            $statement->execute();

            $selectSql = <<<SQL
                SELECT
                    od.order_detail_id,
                    od.order_id,
                    od.product_id,
                    od.ordered_product_name,
                    od.quantity,
                    od.provided_quantity,
                    od.ordered_unit_price,
                    od.plan_applied_flag,
                    od.detail_status,
                    od.ordered_at,
                    o.session_id,
                    o.ordered_at AS order_ordered_at,
                    s.store_id,
                    s.table_number,
                    s.customer_id
                FROM order_details AS od
                INNER JOIN orders AS o
                    ON o.order_id = od.order_id
                INNER JOIN sessions AS s
                    ON s.session_id = o.session_id
                WHERE s.store_id = :store_id
                  AND od.order_detail_id IN ($inSql)
                ORDER BY od.order_detail_id ASC
            SQL;

            $statement = $pdo->prepare($selectSql);
            $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);

            foreach ($ids as $index => $id) {
                $statement->bindValue(':order_detail_id_' . $index, $id, PDO::PARAM_INT);
            }

            $statement->execute();

            $orders = [];

            foreach ($statement->fetchAll() as $row) {
                $orders[] = $this->formatOrderRow($row);
            }

            if ($orders === []) {
                throw new RuntimeException('キャンセル対象の注文明細が見つかりません。');
            }

            $pdo->commit();

            return $orders;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * キャンセル済みの注文明細を「注文中(ORDERED)」に戻す（取消解除）。
     *
     * キャンセルの逆操作。detail_status を ORDERED に戻し、cancelled_at をクリアし、
     * 提供数は0にリセットする。ログイン中店舗の明細だけを対象にする。
     * 戻した明細を formatOrderRow の形で返し、フロントの一覧を更新できるようにする。
     */
    public function restoreOrderDetails(string $storeId, array $orderDetailIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $orderDetailIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            throw new InvalidArgumentException('取消解除の対象が選択されていません。');
        }

        $pdo = db();
        $placeholders = [];

        foreach ($ids as $index => $id) {
            $placeholders[] = ':order_detail_id_' . $index;
        }

        $inSql = implode(', ', $placeholders);

        try {
            $pdo->beginTransaction();

            // キャンセル済みの明細だけを注文中(ORDERED)に戻す。提供数はリセット。
            $updateSql = <<<SQL
                UPDATE order_details AS od
                INNER JOIN orders AS o
                    ON o.order_id = od.order_id
                INNER JOIN sessions AS s
                    ON s.session_id = o.session_id
                SET
                    od.detail_status = 'ORDERED',
                    od.cancelled_at = NULL,
                    od.provided_quantity = 0,
                    od.provided_at = NULL
                WHERE s.store_id = :store_id
                  AND od.detail_status = 'CANCELLED'
                  AND od.order_detail_id IN ($inSql)
            SQL;

            $statement = $pdo->prepare($updateSql);
            $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);

            foreach ($ids as $index => $id) {
                $statement->bindValue(':order_detail_id_' . $index, $id, PDO::PARAM_INT);
            }

            $statement->execute();

            $selectSql = <<<SQL
                SELECT
                    od.order_detail_id,
                    od.order_id,
                    od.product_id,
                    od.ordered_product_name,
                    od.quantity,
                    od.provided_quantity,
                    od.ordered_unit_price,
                    od.plan_applied_flag,
                    od.detail_status,
                    od.ordered_at,
                    o.session_id,
                    o.ordered_at AS order_ordered_at,
                    s.store_id,
                    s.table_number,
                    s.customer_id
                FROM order_details AS od
                INNER JOIN orders AS o
                    ON o.order_id = od.order_id
                INNER JOIN sessions AS s
                    ON s.session_id = o.session_id
                WHERE s.store_id = :store_id
                  AND od.order_detail_id IN ($inSql)
                ORDER BY od.order_detail_id ASC
            SQL;

            $statement = $pdo->prepare($selectSql);
            $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);

            foreach ($ids as $index => $id) {
                $statement->bindValue(':order_detail_id_' . $index, $id, PDO::PARAM_INT);
            }

            $statement->execute();

            $orders = [];

            foreach ($statement->fetchAll() as $row) {
                $orders[] = $this->formatOrderRow($row);
            }

            if ($orders === []) {
                throw new RuntimeException('取消解除の対象が見つかりません。');
            }

            $pdo->commit();

            return $orders;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function normalizeStaffOrderItems(mixed $rawItems): array
    {
        if (!is_array($rawItems)) {
            return [];
        }

        $items = [];

        foreach ($rawItems as $rawItem) {
            if (!is_array($rawItem)) {
                continue;
            }

            $productId = filter_var($rawItem['product_id'] ?? $rawItem['id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($rawItem['quantity'] ?? $rawItem['qty'] ?? null, FILTER_VALIDATE_INT);

            if ($productId === false || $productId === null || $productId < 1) {
                throw new InvalidArgumentException('商品IDが正しくありません。');
            }

            if ($quantity === false || $quantity === null || $quantity < 1) {
                throw new InvalidArgumentException('数量は1以上の整数で入力してください。');
            }

            if (!isset($items[(int)$productId])) {
                $items[(int)$productId] = [
                    'product_id' => (int)$productId,
                    'quantity' => 0,
                ];
            }

            $items[(int)$productId]['quantity'] += (int)$quantity;
        }

        return array_values($items);
    }

    private function onSaleProductsForStaffOrder(string $storeId, array $productIds, ?int $planTypeId): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        if ($productIds === []) {
            return [];
        }

        $placeholders = [];

        foreach ($productIds as $index => $productId) {
            $placeholders[] = ':product_id_' . $index;
        }

        $inSql = implode(', ', $placeholders);
        $sql = <<<SQL
            SELECT
                p.product_id,
                p.product_name,
                p.price,
                p.tax_rate,
                CASE
                    WHEN ptp.product_id IS NOT NULL THEN 1
                    ELSE 0
                END AS plan_applied_flag
            FROM store_products AS sp
            INNER JOIN products AS p
                ON p.product_id = sp.product_id
            LEFT JOIN plan_type_products AS ptp
                ON ptp.product_id = p.product_id
               AND ptp.plan_type_id = :plan_type_id
            WHERE sp.store_id = :store_id
              AND sp.product_id IN ($inSql)
              AND sp.sale_status = 'ON_SALE'
              AND p.sale_status = 'ON_SALE'
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);

        if ($planTypeId === null) {
            $statement->bindValue(':plan_type_id', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':plan_type_id', $planTypeId, PDO::PARAM_INT);
        }

        foreach ($productIds as $index => $productId) {
            $statement->bindValue(':product_id_' . $index, $productId, PDO::PARAM_INT);
        }

        $statement->execute();

        $products = [];

        foreach ($statement->fetchAll() as $row) {
            $products[(int)$row['product_id']] = $row;
        }

        return $products;
    }

    private function insertStaffOrder(int $sessionId): int
    {
        $sql = <<<SQL
            INSERT INTO orders (
                session_id,
                idempotency_key
            )
            VALUES (
                :session_id,
                :idempotency_key
            )
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->bindValue(':idempotency_key', 'staff-' . $sessionId . '-' . bin2hex(random_bytes(16)), PDO::PARAM_STR);
        $statement->execute();

        return (int)db()->lastInsertId();
    }

    private function insertStaffOrderDetail(
        int $orderId,
        int $productId,
        string $productName,
        int $quantity,
        int $unitPrice,
        int $planAppliedFlag
    ): void {
        $sql = <<<SQL
            INSERT INTO order_details (
                order_id,
                product_id,
                ordered_product_name,
                quantity,
                ordered_unit_price,
                plan_applied_flag,
                detail_status
            )
            VALUES (
                :order_id,
                :product_id,
                :ordered_product_name,
                :quantity,
                :ordered_unit_price,
                :plan_applied_flag,
                'ORDERED'
            )
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->bindValue(':ordered_product_name', $productName, PDO::PARAM_STR);
        $statement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $statement->bindValue(':ordered_unit_price', $unitPrice, PDO::PARAM_INT);
        $statement->bindValue(':plan_applied_flag', $planAppliedFlag, PDO::PARAM_INT);
        $statement->execute();
    }

    private function findOrderDetailForUpdate(string $storeId, int $orderDetailId): ?array
    {
        $sql = <<<SQL
            SELECT
                od.order_detail_id,
                od.order_id,
                od.product_id,
                od.ordered_product_name,
                od.quantity,
                od.provided_quantity,
                od.ordered_unit_price,
                od.plan_applied_flag,
                od.detail_status,
                od.ordered_at,
                o.session_id,
                o.ordered_at AS order_ordered_at,
                s.store_id,
                s.table_number,
                s.customer_id
            FROM order_details AS od
            INNER JOIN orders AS o
                ON o.order_id = od.order_id
            INNER JOIN sessions AS s
                ON s.session_id = o.session_id
            WHERE od.order_detail_id = :order_detail_id
              AND s.store_id = :store_id
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':order_detail_id', $orderDetailId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $detail = $statement->fetch();

        return $detail === false ? null : $detail;
    }

    private function findCustomerOrderDetailForUpdate(string $storeId, int $customerId, int $orderDetailId): ?array
    {
        $sql = <<<SQL
            SELECT
                od.order_detail_id,
                od.order_id,
                od.product_id,
                od.ordered_product_name,
                od.quantity,
                od.provided_quantity,
                od.ordered_unit_price,
                od.plan_applied_flag,
                od.detail_status,
                od.ordered_at,
                od.provided_at,
                od.cancelled_at,
                o.session_id,
                o.ordered_at AS order_ordered_at,
                s.store_id,
                s.table_number,
                s.customer_id
            FROM order_details AS od
            INNER JOIN orders AS o
                ON o.order_id = od.order_id
            INNER JOIN sessions AS s
                ON s.session_id = o.session_id
            WHERE od.order_detail_id = :order_detail_id
              AND s.customer_id = :customer_id
              AND s.store_id = :store_id
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':order_detail_id', $orderDetailId, PDO::PARAM_INT);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $detail = $statement->fetch();

        return $detail === false ? null : $detail;
    }

    private function assertCustomerBelongsToStore(string $storeId, int $customerId): void
    {
        $sql = <<<SQL
            SELECT customer_id
            FROM customers
            WHERE customer_id = :customer_id
              AND store_id = :store_id
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        if ($statement->fetch() === false) {
            throw new RuntimeException('顧客情報が見つかりません。');
        }
    }

    private function updateProvidedQuantityRow(
        int $orderDetailId,
        int $providedQuantity,
        string $detailStatus,
        ?string $providedAt
    ): void {
        $sql = <<<SQL
            UPDATE order_details
            SET
                provided_quantity = :provided_quantity,
                detail_status = :detail_status,
                provided_at = :provided_at
            WHERE order_detail_id = :order_detail_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':provided_quantity', $providedQuantity, PDO::PARAM_INT);
        $statement->bindValue(':detail_status', $detailStatus, PDO::PARAM_STR);

        if ($providedAt === null) {
            $statement->bindValue(':provided_at', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':provided_at', $providedAt, PDO::PARAM_STR);
        }

        $statement->bindValue(':order_detail_id', $orderDetailId, PDO::PARAM_INT);
        $statement->execute();
    }

    private function formatOrderRow(array $row): array
    {
        $quantity = (int)$row['quantity'];
        $providedQuantity = (int)$row['provided_quantity'];
        $detailStatus = (string)$row['detail_status'];
        $displayProvidedQuantity = $detailStatus === 'PROVIDED'
            ? max($providedQuantity, $quantity)
            : min($providedQuantity, $quantity);

        return [
            // 既存JSはorder.idを操作対象として使うため、注文ヘッダではなく明細IDを入れる。
            'id' => (int)$row['order_detail_id'],
            'order_detail_id' => (int)$row['order_detail_id'],
            'order_id' => (int)$row['order_id'],
            'product_id' => (int)$row['product_id'],
            'customer_id' => (int)$row['customer_id'],
            'session_id' => (int)$row['session_id'],
            'store_id' => (string)$row['store_id'],
            'table_no' => (string)$row['table_number'] . '番',
            'name' => (string)$row['ordered_product_name'],
            'qty' => $quantity,
            'servedQty' => $displayProvidedQuantity,
            'time' => $this->formatTime($row['order_ordered_at'] ?? $row['ordered_at'] ?? null),
            'ordered_at_label' => $this->formatDateTime($row['order_ordered_at'] ?? $row['ordered_at'] ?? null),
            'status' => $this->displayStatus($detailStatus, $quantity, $displayProvidedQuantity),
            'status_label' => $this->statusLabel($detailStatus, $quantity, $displayProvidedQuantity),
            'detail_status' => $detailStatus,
            'price' => (int)$row['ordered_unit_price'],
            'plan_applied_flag' => (int)$row['plan_applied_flag'],
        ];
    }

    private function displayStatus(string $detailStatus, int $quantity, int $providedQuantity): string
    {
        if ($detailStatus === 'CANCELLED') {
            return 'canceled';
        }

        if ($detailStatus === 'PROVIDED' || $providedQuantity >= $quantity) {
            return 'served';
        }

        return 'waiting';
    }

    private function formatTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = strtotime((string)$value);

        if ($timestamp === false) {
            return '';
        }

        return date('H:i', $timestamp);
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = strtotime((string)$value);

        if ($timestamp === false) {
            return '';
        }

        return date('Y/m/d H:i', $timestamp);
    }

    private function statusLabel(string $detailStatus, int $quantity, int $providedQuantity): string
    {
        if ($detailStatus === 'CANCELLED') {
            return 'キャンセル済み';
        }

        if ($detailStatus === 'PROVIDED' || $providedQuantity >= $quantity) {
            return '提供済み';
        }

        return '注文済み';
    }
}
