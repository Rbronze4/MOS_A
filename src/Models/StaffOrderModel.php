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
