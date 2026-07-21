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
     * 同じ商品・同じオプション構成がすでにある場合は、新規行を作らず数量だけ増やす。
     */
    public function addProduct(
        int $sessionId,
        string $storeId,
        int $productId,
        int $quantity,
        array $optionIds = []
    ): array
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
            $selectedOptions = $this->validatedOptionsForProduct($productId, $optionIds);
            $optionSignature = $this->optionSignature($selectedOptions);

            // 今回は carts の新規作成は行わず、DBにあるテスト用カートを使う。
            $cartId = $this->findExistingCartId($sessionId);

            if ($cartId === null) {
                throw new RuntimeException('指定されたsession_idに紐づくカートが見つかりません。');
            }

            $cartDetail = $this->findCartDetailByConfiguration($cartId, $productId, $optionSignature);

            if ($cartDetail !== null) {
                $cartDetailId = (int)$cartDetail['cart_detail_id'];
                $this->incrementCartDetailQuantity($cartDetailId, $quantity);
            } else {
                $cartDetailId = $this->insertCartDetail($cartId, $productId, $optionSignature, $quantity);
                $this->replaceCartDetailOptions($cartDetailId, $selectedOptions);
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
    public function updateCartDetail(
        int $sessionId,
        string $storeId,
        int $cartDetailId,
        int $quantity,
        array $optionIds = []
    ): array
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

            $cartDetail = $this->findCartDetailById($cartId, $cartDetailId);

            if ($cartDetail === null) {
                throw new RuntimeException('変更対象の商品がカートに見つかりません。');
            }

            $productId = (int)$cartDetail['product_id'];
            $planTypeId = $this->currentPlanTypeIdForSession($sessionId);

            if ($this->findOnSaleProduct($storeId, $productId, $planTypeId) === null) {
                throw new RuntimeException('この店舗で販売中の商品ではありません。');
            }

            $selectedOptions = $this->validatedOptionsForProduct($productId, $optionIds);
            $optionSignature = $this->optionSignature($selectedOptions);
            $sameConfiguration = $this->findCartDetailByConfiguration($cartId, $productId, $optionSignature);

            if ($sameConfiguration !== null && (int)$sameConfiguration['cart_detail_id'] !== $cartDetailId) {
                $this->incrementCartDetailQuantity((int)$sameConfiguration['cart_detail_id'], $quantity);
                $this->deleteCartDetailRow($cartDetailId, $cartId);
            } else {
                $this->updateCartDetailValues($cartDetailId, $cartId, $quantity, $optionSignature);
                $this->replaceCartDetailOptions($cartDetailId, $selectedOptions);
            }

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
    public function deleteCartDetail(int $sessionId, int $cartDetailId): array
    {
        $pdo = db();
        $this->assertActiveSession($sessionId);

        try {
            $pdo->beginTransaction();

            $cartId = $this->findExistingCartId($sessionId);

            if ($cartId === null) {
                throw new RuntimeException('指定されたsession_idに紐づくカートが見つかりません。');
            }

            $cartDetail = $this->findCartDetailById($cartId, $cartDetailId);

            if ($cartDetail === null) {
                throw new RuntimeException('削除対象の商品がカートに見つかりません。');
            }

            $this->deleteCartDetailRow($cartDetailId, $cartId);

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
                cd.cart_detail_id,
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
                    ELSE FLOOR(p.price * (1 + (p.tax_rate / 100)))
                END AS display_unit_price,
                FLOOR(p.price * (1 + (p.tax_rate / 100))) AS normal_price,
                -- オプション料金にも同じ税率を掛けるため、税抜単価と税率をそのまま渡す。
                -- 税込化はPHP側で「税抜(商品+オプション)合計 × 税率」としてまとめて行う。
                p.price AS net_unit_price,
                p.tax_rate,
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

        $optionsByCartDetail = $this->cartOptionsForSession($sessionId);
        $items = [];

        foreach ($statement->fetchAll() as $row) {
            $cartDetailId = (int)$row['cart_detail_id'];
            $options = $optionsByCartDetail[$cartDetailId] ?? [];

            // オプションの追加料金は税抜で保存されているため、商品の税抜価格と合算してから
            // 税を掛ける。個別に税込化して足すと端数処理が2回入り、税抜合計へ課税する
            // レジ側の計算と1円ずれることがある。
            $additionalPrice = array_sum(array_column($options, 'additional_price'));
            $taxRate = (float)$row['tax_rate'];
            $planApplied = (int)$row['plan_applied_flag'] === 1;

            // プラン対象商品は商品分が0円。オプション分だけに課税する。
            $netUnitPrice = $planApplied ? 0 : (int)$row['net_unit_price'];

            $items[] = [
                'cart_detail_id' => $cartDetailId,
                'id' => (int)$row['product_id'],
                'name' => (string)$row['product_name'],
                'price' => $this->taxIncludedPrice($netUnitPrice + $additionalPrice, $taxRate),
                'normal_price' => $this->taxIncludedPrice((int)$row['net_unit_price'] + $additionalPrice, $taxRate),
                'plan_applied_flag' => (int)$row['plan_applied_flag'],
                'quantity' => (int)$row['quantity'],
                'option_ids' => array_column($options, 'option_id'),
                'options' => $options,
            ];
        }

        return $items;
    }

    /**
     * 税抜価格へ税率を適用し、税込価格の1円未満を切り捨てる。
     *
     * 浮動小数点の誤差を避けるため、税率をベーシスポイント（100倍の整数）にして整数演算する。
     * MenuModel / OrderModel の同名メソッドと同じ計算にすること。
     */
    private function taxIncludedPrice(int $price, float $taxRate): int
    {
        $taxRateBasisPoints = (int)round($taxRate * 100);

        return intdiv($price * (10000 + $taxRateBasisPoints), 10000);
    }

    /**
     * カートに保存済みのオプション名・追加料金スナップショットを明細ごとに返す。
     */
    private function cartOptionsForSession(int $sessionId): array
    {
        $sql = <<<SQL
            SELECT
                cdo.cart_detail_id,
                cdo.option_id,
                cdo.selected_option_name,
                cdo.selected_additional_price
            FROM carts AS c
            INNER JOIN cart_details AS cd
                ON cd.cart_id = c.cart_id
            INNER JOIN cart_detail_options AS cdo
                ON cdo.cart_detail_id = cd.cart_detail_id
            WHERE c.session_id = :session_id
            ORDER BY cd.cart_detail_id, cdo.option_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $statement->execute();
        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $grouped[(int)$row['cart_detail_id']][] = [
                'option_id' => (int)$row['option_id'],
                'name' => (string)$row['selected_option_name'],
                'additional_price' => (int)$row['selected_additional_price'],
            ];
        }

        return $grouped;
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
     * 同じ商品・オプション構成の明細がすでにあるか確認する。
     */
    private function findCartDetailByConfiguration(int $cartId, int $productId, string $optionSignature): ?array
    {
        $sql = <<<SQL
            SELECT
                cart_detail_id,
                quantity
            FROM cart_details
            WHERE cart_id = :cart_id
              AND product_id = :product_id
              AND option_signature = :option_signature
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->bindValue(':option_signature', $optionSignature, PDO::PARAM_STR);
        $statement->execute();

        $cartDetail = $statement->fetch();

        return $cartDetail === false ? null : $cartDetail;
    }

    /** 対象明細が現在のカートに属することを確認しながらロックする。 */
    private function findCartDetailById(int $cartId, int $cartDetailId): ?array
    {
        $statement = db()->prepare(<<<SQL
            SELECT cart_detail_id, product_id, quantity, option_signature
            FROM cart_details
            WHERE cart_id = :cart_id
              AND cart_detail_id = :cart_detail_id
            LIMIT 1
            FOR UPDATE
        SQL);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
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
    private function updateCartDetailValues(
        int $cartDetailId,
        int $cartId,
        int $quantity,
        string $optionSignature
    ): void
    {
        $sql = <<<SQL
            UPDATE cart_details
            SET quantity = :quantity,
                option_signature = :option_signature
            WHERE cart_detail_id = :cart_detail_id
              AND cart_id = :cart_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $statement->bindValue(':option_signature', $optionSignature, PDO::PARAM_STR);
        $statement->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * カート画面の削除ボタンから、対象明細をDB上でも削除する。
     */
    private function deleteCartDetailRow(int $cartDetailId, int $cartId): void
    {
        $sql = <<<SQL
            DELETE FROM cart_details
            WHERE cart_detail_id = :cart_detail_id
              AND cart_id = :cart_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * 同じ商品・オプション構成がない場合は、新しい cart_details 行を作成する。
     */
    private function insertCartDetail(
        int $cartId,
        int $productId,
        string $optionSignature,
        int $quantity
    ): int
    {
        $sql = <<<SQL
            INSERT INTO cart_details (
                cart_id,
                product_id,
                option_signature,
                quantity
            )
            VALUES (
                :cart_id,
                :product_id,
                :option_signature,
                :quantity
            )
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->bindValue(':option_signature', $optionSignature, PDO::PARAM_STR);
        $statement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $statement->execute();

        return (int)db()->lastInsertId();
    }

    /** 検証済みオプションIDを昇順で連結し、同一構成判定用の値を作る。 */
    private function optionSignature(array $selectedOptions): string
    {
        $optionIds = array_map('intval', array_column($selectedOptions, 'option_id'));
        sort($optionIds, SORT_NUMERIC);

        return implode(',', $optionIds);
    }

    /**
     * 送信されたoption_idを商品構成と照合し、必須・単一／複数選択を検証する。
     * 戻り値には、カートへ保存する時点の名称と追加料金を含める。
     */
    private function validatedOptionsForProduct(int $productId, array $optionIds): array
    {
        $optionIds = array_values(array_unique(array_filter(
            array_map('intval', $optionIds),
            static fn (int $optionId): bool => $optionId > 0
        )));

        $sql = <<<SQL
            SELECT
                og.option_group_id,
                og.option_group_name,
                og.selection_type,
                og.is_required,
                o.option_id,
                o.option_name,
                o.additional_price
            FROM product_option_groups AS pog
            INNER JOIN option_groups AS og
                ON og.option_group_id = pog.option_group_id
            LEFT JOIN options AS o
                ON o.option_group_id = og.option_group_id
            WHERE pog.product_id = :product_id
            ORDER BY pog.display_order, og.option_group_id, o.display_order, o.option_id
            FOR UPDATE
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->execute();
        $groups = [];
        $validOptions = [];

        foreach ($statement->fetchAll() as $row) {
            $groupId = (int)$row['option_group_id'];

            $groups[$groupId] ??= [
                'name' => (string)$row['option_group_name'],
                'selection_type' => (string)$row['selection_type'],
                'is_required' => (int)$row['is_required'],
                'option_ids' => [],
            ];

            if ($row['option_id'] !== null) {
                $optionId = (int)$row['option_id'];
                $groups[$groupId]['option_ids'][] = $optionId;
                $validOptions[$optionId] = [
                    'option_id' => $optionId,
                    'option_name' => (string)$row['option_name'],
                    'additional_price' => (int)$row['additional_price'],
                ];
            }
        }

        foreach ($optionIds as $optionId) {
            if (!isset($validOptions[$optionId])) {
                throw new InvalidArgumentException('商品に存在しないオプションが選択されています。');
            }
        }

        foreach ($groups as $group) {
            $selectedCount = count(array_intersect($optionIds, $group['option_ids']));

            if ($group['is_required'] === 1 && $selectedCount === 0) {
                throw new InvalidArgumentException($group['name'] . 'を選択してください。');
            }

            if ($group['selection_type'] === 'SINGLE' && $selectedCount > 1) {
                throw new InvalidArgumentException($group['name'] . 'は1つだけ選択してください。');
            }
        }

        return array_values(array_intersect_key($validOptions, array_flip($optionIds)));
    }

    /**
     * 編集時の選択内容を、対象カート明細へ保存し直す。
     */
    private function replaceCartDetailOptions(int $cartDetailId, array $selectedOptions): void
    {
        $delete = db()->prepare('DELETE FROM cart_detail_options WHERE cart_detail_id = :cart_detail_id');
        $delete->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
        $delete->execute();

        if ($selectedOptions === []) {
            return;
        }

        $sql = <<<SQL
            INSERT INTO cart_detail_options (
                cart_detail_id,
                option_id,
                selected_option_name,
                selected_additional_price
            ) VALUES (
                :cart_detail_id,
                :option_id,
                :selected_option_name,
                :selected_additional_price
            )
        SQL;
        $statement = db()->prepare($sql);

        foreach ($selectedOptions as $option) {
            $statement->bindValue(':cart_detail_id', $cartDetailId, PDO::PARAM_INT);
            $statement->bindValue(':option_id', (int)$option['option_id'], PDO::PARAM_INT);
            $statement->bindValue(':selected_option_name', (string)$option['option_name'], PDO::PARAM_STR);
            $statement->bindValue(':selected_additional_price', (int)$option['additional_price'], PDO::PARAM_INT);
            $statement->execute();
        }
    }
}
