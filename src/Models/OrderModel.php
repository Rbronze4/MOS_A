<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

/**
 * 客側の注文確定を担当するModel。
 *
 * 現在はテスト用の既存 session_id / cart_id を使い、
 * cart_details の内容から orders / order_details を作成する。
 */
final class OrderModel
{
    /**
     * 注文かごの内容を注文として確定する。
     *
     * orders、order_details、cart_details削除は必ず同じトランザクション内で行う。
     */
    public function submitCart(int $sessionId, string $storeId): array
    {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $session = $this->findActiveSession($sessionId);

            if ($session === null) {
                throw new RuntimeException('有効なセッションが見つかりません。');
            }

            if ((string)$session['store_id'] !== $storeId) {
                throw new RuntimeException('セッションの店舗と注文対象店舗が一致しません。');
            }

            $cartId = $this->findCartId($sessionId);

            if ($cartId === null) {
                throw new RuntimeException('指定されたsession_idに紐づくカートが見つかりません。');
            }

            $cartDetailCount = $this->countCartDetails($cartId);

            if ($cartDetailCount === 0) {
                throw new RuntimeException('注文かごが空です。商品を追加してから注文してください。');
            }

            $cartItems = $this->cartItemsForOrder($cartId, $storeId);

            if (count($cartItems) !== $cartDetailCount) {
                throw new RuntimeException('販売中ではない商品が注文かごに含まれています。');
            }

            $orderId = $this->insertOrder($sessionId);
            $totalAmount = 0;
            $totalQuantity = 0;

            foreach ($cartItems as $item) {
                $quantity = (int)$item['quantity'];
                $unitPrice = (int)$item['display_unit_price'];
                $optionAdditionalPrice = (int)$item['option_additional_price'];
                $planAppliedFlag = $unitPrice === 0 ? 1 : 0;

                // オプションの追加料金は税抜で保存されているため、商品の税抜価格と合算して
                // から税を掛ける。個別に税込化して足すと端数処理が2回入り、税抜合計へ
                // 課税するレジ側の計算と1円ずれることがある。
                // プラン対象商品は商品分が0円なので、オプション分だけに課税される。
                $taxIncludedTotalUnitPrice = $this->taxIncludedPrice(
                    ($planAppliedFlag === 1 ? 0 : $unitPrice) + $optionAdditionalPrice,
                    (float)$item['tax_rate']
                );

                $orderDetailId = $this->insertOrderDetail(
                    $orderId,
                    (int)$item['product_id'],
                    (string)$item['product_name'],
                    $quantity,
                    $unitPrice,
                    $planAppliedFlag
                );
                $this->copyCartOptionsToOrder((int)$item['cart_detail_id'], $orderDetailId);

                $totalQuantity += $quantity;

                // 税込単価（オプション込み）はすでに合算済みのため、ここで再度足さない。
                $totalAmount += $taxIncludedTotalUnitPrice * $quantity;
            }

            $this->deleteCartDetails($cartId);

            $pdo->commit();

            return [
                'order_id' => $orderId,
                'customer_id' => (int)$session['customer_id'],
                'cart_id' => $cartId,
                'total_quantity' => $totalQuantity,
                'total_amount' => $totalAmount,
                'cart_items' => [],
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * 注文履歴はカートと違い、session_id単位ではなくcustomer_id単位で取得する。
     *
     * 1人の顧客が複数セッションを持つ場合でも、orders -> sessionsをたどって
     * 同じcustomer_idの注文明細をまとめて表示する。
     */
    public function historyItemsForCustomer(int $customerId): array
    {
        $sql = <<<SQL
            SELECT
                od.order_detail_id,
                od.order_id,
                od.product_id,
                od.ordered_product_name,
                od.quantity,
                od.ordered_unit_price,
                p.tax_rate,
                COALESCE((
                    SELECT SUM(odo.ordered_additional_price)
                    FROM order_detail_options AS odo
                    WHERE odo.order_detail_id = od.order_detail_id
                ), 0) AS option_additional_price,
                (
                    SELECT GROUP_CONCAT(odo.ordered_option_name ORDER BY odo.option_id SEPARATOR '、')
                    FROM order_detail_options AS odo
                    WHERE odo.order_detail_id = od.order_detail_id
                ) AS option_summary,
                od.detail_status,
                od.ordered_at
            FROM orders AS o
            INNER JOIN sessions AS s
                ON s.session_id = o.session_id
            INNER JOIN order_details AS od
                ON od.order_id = o.order_id
            INNER JOIN products AS p
                ON p.product_id = od.product_id
            WHERE s.customer_id = :customer_id
              AND od.detail_status <> 'CANCELLED'
            ORDER BY
                o.ordered_at ASC,
                od.order_detail_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->execute();

        $items = [];

        foreach ($statement->fetchAll() as $row) {
            $items[] = [
                'id' => (int)$row['product_id'],
                'order_detail_id' => (int)$row['order_detail_id'],
                'order_id' => (int)$row['order_id'],
                'name' => (string)$row['ordered_product_name'],
                // オプションの追加料金は税抜で保存されているため、商品の税抜価格と合算して
                // から税を掛ける。個別に税込化して足すと端数処理が2回入り、税抜合計へ
                // 課税するレジ側の計算と1円ずれることがある。
                'price' => $this->taxIncludedPrice(
                    (int)$row['ordered_unit_price'] + (int)$row['option_additional_price'],
                    (float)$row['tax_rate']
                ),
                'quantity' => (int)$row['quantity'],
                'option_summary' => $row['option_summary'] === null ? '' : (string)$row['option_summary'],
                'status' => (string)$row['detail_status'],
                'ordered_at' => (string)$row['ordered_at'],
            ];
        }

        return $items;
    }

    /**
     * 注文履歴など顧客向け表示用の税込価格を、税抜価格と税率から算出する。
     */
    private function taxIncludedPrice(int $price, float $taxRate): int
    {
        $taxRateBasisPoints = (int)round($taxRate * 100);

        return intdiv($price * (10000 + $taxRateBasisPoints), 10000);
    }

    /**
     * 固定session_idがACTIVEで、customersにも紐づいていることを確認する。
     */
    private function findActiveSession(int $sessionId): ?array
    {
        $sql = <<<SQL
            SELECT
                s.session_id,
                s.customer_id,
                s.store_id,
                s.session_status
            FROM sessions AS s
            INNER JOIN customers AS c
                ON c.customer_id = s.customer_id
            WHERE s.session_id = :session_id
              AND s.session_status = 'ACTIVE'
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();

        $session = $statement->fetch();

        return $session === false ? null : $session;
    }

    /**
     * cart_idは固定せず、必ずsession_idから取得する。
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

    /**
     * 空の注文かごからordersを作らないため、明細数を先に確認する。
     */
    private function countCartDetails(int $cartId): int
    {
        $sql = <<<SQL
            SELECT COUNT(*) AS detail_count
            FROM cart_details
            WHERE cart_id = :cart_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->execute();

        return (int)$statement->fetchColumn();
    }

    /**
     * 注文登録に使う商品情報をDBから取得する。
     *
     * POST値は信用せず、商品名と単価は products / cart_details から取得する。
     * products の店舗所属と販売状態もここで確認する。
     */
    private function cartItemsForOrder(int $cartId, string $storeId): array
    {
        $sql = <<<SQL
            SELECT
                cd.cart_detail_id,
                cd.cart_id,
                cd.product_id,
                cd.quantity,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM customer_plans AS cp
                        INNER JOIN plans AS pl
                            ON pl.plan_id = cp.plan_id
                        INNER JOIN plan_type_products AS ptp
                            ON ptp.plan_type_id = pl.plan_type_id
                           AND ptp.product_id = p.product_id
                        WHERE cp.customer_id = s.customer_id
                          AND cp.started_at <= NOW()
                          AND (cp.ended_at IS NULL OR cp.ended_at > NOW())
                          AND pl.is_active = 1
                    ) THEN 0
                    ELSE p.price
                END AS display_unit_price,
                COALESCE((
                    SELECT SUM(cdo.selected_additional_price)
                    FROM cart_detail_options AS cdo
                    WHERE cdo.cart_detail_id = cd.cart_detail_id
                ), 0) AS option_additional_price,
                p.product_name,
                p.price,
                p.tax_rate
            FROM cart_details AS cd
            INNER JOIN carts AS c
                ON c.cart_id = cd.cart_id
            INNER JOIN sessions AS s
                ON s.session_id = c.session_id
            INNER JOIN products AS p
                ON p.product_id = cd.product_id
            WHERE cd.cart_id = :cart_id
              AND p.store_id = :store_id
              AND p.sale_status = 'ON_SALE'
            ORDER BY cd.cart_detail_id ASC
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * 注文ヘッダを登録する。
     *
     * 現在のordersテーブルにはcustomer_id/order_statusカラムがないため、
     * 実DB定義に合わせてsession_idとidempotency_keyを登録する。
     */
    private function insertOrder(int $sessionId): int
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
        $statement->bindValue(':idempotency_key', $this->newIdempotencyKey($sessionId), PDO::PARAM_STR);
        $statement->execute();

        return (int)db()->lastInsertId();
    }

    /**
     * 注文時点の商品名・単価・数量を注文明細に保存する。
     */
    private function insertOrderDetail(
        int $orderId,
        int $productId,
        string $productName,
        int $quantity,
        int $unitPrice,
        int $planAppliedFlag
    ): int {
        $sql = <<<SQL
            INSERT INTO order_details (
                order_id,
                product_id,
                ordered_product_name,
                quantity,
                ordered_unit_price,
                plan_applied_flag
            )
            VALUES (
                :order_id,
                :product_id,
                :ordered_product_name,
                :quantity,
                :ordered_unit_price,
                :plan_applied_flag
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

        return (int)db()->lastInsertId();
    }

    /**
     * カートで確定した名称・追加料金を注文オプションへスナップショットとして引き継ぐ。
     */
    private function copyCartOptionsToOrder(int $cartDetailId, int $orderDetailId): void
    {
        $sql = <<<SQL
            INSERT INTO order_detail_options (
                order_detail_id,
                option_id,
                ordered_option_name,
                ordered_additional_price
            )
            SELECT
                :order_detail_id,
                option_id,
                selected_option_name,
                selected_additional_price
            FROM cart_detail_options
            WHERE cart_detail_id = :cart_detail_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':order_detail_id', $orderDetailId, PDO::PARAM_INT);
        $statement->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * 注文成功後、対象cart_idの明細だけを削除する。
     */
    private function deleteCartDetails(int $cartId): void
    {
        $sql = <<<SQL
            DELETE FROM cart_details
            WHERE cart_id = :cart_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->execute();
    }

    private function newIdempotencyKey(int $sessionId): string
    {
        return 'customer-' . $sessionId . '-' . bin2hex(random_bytes(16));
    }
}
