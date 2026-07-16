<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

/**
 * 客側の注文かご登録を担当するModel。
 *
 * 今回は cart_details への追加、数量変更、削除、現在のカート内容取得までを扱う。
 * orders / order_details への登録、注文確定、オプション選択はまだ行わない。
 */
final class CartModel
{
    /**
     * 指定された商品を、既存のテスト用カートに追加する。
     *
     * 同じ商品がすでに cart_details にある場合は、新規行を作らず数量だけ増やす。
     */
    public function addProduct(int $sessionId, string $storeId, int $productId, int $quantity): array
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('数量が正しくありません。');
        }

        $pdo = db();

        // 指示どおり、既存のACTIVEな sessions.session_id だけを利用する。
        $this->assertActiveSession($sessionId);

        // カートへ入れる前に、緑橋本店で販売中の商品か必ず確認する。
        // カート追加時も現在のプランをDBから確認し、飲み放題対象なら表示単価を0円にする。
        $planTypeId = $this->currentPlanTypeIdForSession($sessionId);
        $product = $this->findOnSaleProduct($storeId, $productId, $planTypeId);

        if ($product === null) {
            throw new RuntimeException('この店舗で販売中の商品ではありません。');
        }

        try {
            $pdo->beginTransaction();

            // 今回は carts の新規作成は行わず、DBにあるテスト用カートを使う。
            $cartId = $this->findExistingCartId($sessionId);

            if ($cartId === null) {
                throw new RuntimeException('指定されたsession_idに紐づくカートが見つかりません。');
            }

            $cartDetail = $this->findCartDetail($cartId, $productId);

            if ($cartDetail !== null) {
                $this->incrementCartDetailQuantity((int)$cartDetail['cart_detail_id'], $quantity);
            } else {
                $this->insertCartDetail($cartId, $productId, $quantity);
            }

            $pdo->commit();

            return [
                'cart_id' => $cartId,
                'product_name' => (string)$product['product_name'],
                'cart_items' => $this->cartItemsForSession($sessionId),
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * 既存のカート明細の数量を、指定された数量へ変更する。
     */
    public function updateProductQuantity(int $sessionId, int $productId, int $quantity): array
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('数量が正しくありません。');
        }

        $pdo = db();
        $this->assertActiveSession($sessionId);

        try {
            $pdo->beginTransaction();

            $cartId = $this->findExistingCartId($sessionId);

            if ($cartId === null) {
                throw new RuntimeException('指定されたsession_idに紐づくカートが見つかりません。');
            }

            $cartDetail = $this->findCartDetail($cartId, $productId);

            if ($cartDetail === null) {
                throw new RuntimeException('変更対象の商品がカートに見つかりません。');
            }

            $this->setCartDetailQuantity((int)$cartDetail['cart_detail_id'], $quantity);

            $pdo->commit();

            return [
                'cart_id' => $cartId,
                'cart_items' => $this->cartItemsForSession($sessionId),
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * 既存のカート明細を削除する。
     */
    public function deleteProduct(int $sessionId, int $productId): array
    {
        $pdo = db();
        $this->assertActiveSession($sessionId);

        try {
            $pdo->beginTransaction();

            $cartId = $this->findExistingCartId($sessionId);

            if ($cartId === null) {
                throw new RuntimeException('指定されたsession_idに紐づくカートが見つかりません。');
            }

            $cartDetail = $this->findCartDetail($cartId, $productId);

            if ($cartDetail === null) {
                throw new RuntimeException('削除対象の商品がカートに見つかりません。');
            }

            $this->deleteCartDetail((int)$cartDetail['cart_detail_id']);

            $pdo->commit();

            return [
                'cart_id' => $cartId,
                'cart_items' => $this->cartItemsForSession($sessionId),
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * 画面表示用に、現在のカート内容をフロント側の既存state.cart形式へ整えて返す。
     */
    public function cartItemsForSession(int $sessionId): array
    {
        $sql = <<<SQL
            SELECT
                p.product_id,
                p.product_name,
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
                p.price AS normal_price,
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
                    ) THEN 1
                    ELSE 0
                END AS plan_applied_flag
            FROM carts AS c
            INNER JOIN sessions AS s
                ON s.session_id = c.session_id
            INNER JOIN cart_details AS cd
                ON cd.cart_id = c.cart_id
            INNER JOIN products AS p
                ON p.product_id = cd.product_id
            WHERE c.session_id = :session_id
            ORDER BY
                cd.added_at ASC,
                cd.cart_detail_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();

        $items = [];

        foreach ($statement->fetchAll() as $row) {
            $items[] = [
                'id' => (int)$row['product_id'],
                'name' => (string)$row['product_name'],
                'price' => (int)$row['display_unit_price'],
                'normal_price' => (int)$row['normal_price'],
                'plan_applied_flag' => (int)$row['plan_applied_flag'],
                'quantity' => (int)$row['quantity'],
            ];
        }

        return $items;
    }

    /**
     * テスト用に固定利用する session_id が、ACTIVE状態で存在するか確認する。
     */
    private function assertActiveSession(int $sessionId): void
    {
        $sql = <<<SQL
            SELECT session_id
            FROM sessions
            WHERE session_id = :session_id
              AND session_status = 'ACTIVE'
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();

        if ($statement->fetch() === false) {
            throw new RuntimeException('有効なテスト用session_idが見つかりません。');
        }
    }

    /**
     * カート追加前に、対象商品が指定店舗で販売中か確認する。
     */
    private function findOnSaleProduct(string $storeId, int $productId, ?int $planTypeId): ?array
    {
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
            FROM products AS p
            LEFT JOIN plan_type_products AS ptp
                ON ptp.product_id = p.product_id
               AND ptp.plan_type_id = :plan_type_id
            WHERE p.store_id = :store_id
              AND p.product_id = :product_id
              AND p.sale_status = 'ON_SALE'
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        if ($planTypeId === null) {
            $statement->bindValue(':plan_type_id', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':plan_type_id', $planTypeId, PDO::PARAM_INT);
        }
        $statement->execute();

        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    private function currentPlanTypeIdForSession(int $sessionId): ?int
    {
        $sql = <<<SQL
            SELECT
                p.plan_type_id
            FROM sessions AS s
            INNER JOIN customer_plans AS cp
                ON cp.customer_id = s.customer_id
               AND cp.started_at <= NOW()
            INNER JOIN plans AS p
                ON p.plan_id = cp.plan_id
            WHERE s.session_id = :session_id
              AND s.session_status = 'ACTIVE'
              AND (cp.ended_at IS NULL OR cp.ended_at > NOW())
              AND p.is_active = 1
            ORDER BY cp.started_at DESC, cp.customer_plan_id DESC
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();

        $planTypeId = $statement->fetchColumn();

        return $planTypeId === false ? null : (int)$planTypeId;
    }

    /**
     * 指定session_idに紐づく既存カートを取得する。
     *
     * cart_details 更新と同じトランザクション内でロックし、同時更新時のズレを防ぐ。
     */
    private function findExistingCartId(int $sessionId): ?int
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
     * すでに同じ商品がかごに入っているか確認する。
     */
    private function findCartDetail(int $cartId, int $productId): ?array
    {
        $sql = <<<SQL
            SELECT
                cart_detail_id,
                quantity
            FROM cart_details
            WHERE cart_id = :cart_id
              AND product_id = :product_id
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->execute();

        $cartDetail = $statement->fetch();

        return $cartDetail === false ? null : $cartDetail;
    }

    /**
     * 既存明細がある場合は数量だけ増やす。
     */
    private function incrementCartDetailQuantity(int $cartDetailId, int $quantity): void
    {
        $sql = <<<SQL
            UPDATE cart_details
            SET quantity = quantity + :quantity
            WHERE cart_detail_id = :cart_detail_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $statement->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * 変更画面で指定された数量へ上書きする。
     */
    private function setCartDetailQuantity(int $cartDetailId, int $quantity): void
    {
        $sql = <<<SQL
            UPDATE cart_details
            SET quantity = :quantity
            WHERE cart_detail_id = :cart_detail_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $statement->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * カート画面の削除ボタンから、対象明細をDB上でも削除する。
     */
    private function deleteCartDetail(int $cartDetailId): void
    {
        $sql = <<<SQL
            DELETE FROM cart_details
            WHERE cart_detail_id = :cart_detail_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * まだ同じ商品がない場合は、新しい cart_details 行を作成する。
     */
    private function insertCartDetail(int $cartId, int $productId, int $quantity): void
    {
        $sql = <<<SQL
            INSERT INTO cart_details (
                cart_id,
                product_id,
                quantity
            )
            VALUES (
                :cart_id,
                :product_id,
                :quantity
            )
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $statement->execute();
    }
}
