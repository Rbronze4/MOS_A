<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

/**
 * 客側メニュー画面の商品取得モデル。
 *
 * 今回は「商品選択画面に表示するカテゴリ・商品一覧」のDB取得だけを担当する。
 * カート登録、注文登録、プラン対象商品の制御、オプション制御はここでは行わない。
 */
final class MenuModel
{
    private const NO_IMAGE_PATH = '/MOS_A/public/assets/images/menu/no_image.png';
    private const ALL_YOU_CAN_DRINK_CATEGORY_ID = 'all_you_can_drink';
    private const ALL_YOU_CAN_DRINK_CATEGORY_NAME = '飲み放題';

    /**
     * 指定店舗で販売中の商品が存在するカテゴリだけを取得する。
     *
     * 店舗所属と販売状態は products に集約されているため、products を起点にする。
     */
    public function categoriesForStore(string $storeId, bool $hasActivePlan = false): array
    {
        $sql = <<<SQL
            SELECT DISTINCT
                c.category_id,
                c.category_name
            FROM products AS p
            INNER JOIN product_categories AS c
                ON c.category_id = p.category_id
            WHERE p.store_id = :store_id
              AND p.sale_status = 'ON_SALE'
            ORDER BY
                c.category_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $categories = [];

        if ($hasActivePlan) {
            $categories[] = [
                'id' => self::ALL_YOU_CAN_DRINK_CATEGORY_ID,
                'name' => self::ALL_YOU_CAN_DRINK_CATEGORY_NAME,
                'is_virtual' => true,
            ];
        }

        foreach ($statement->fetchAll() as $row) {
            $categories[] = [
                'id' => (string)$row['category_id'],
                'name' => (string)$row['category_name'],
                'is_virtual' => false,
            ];
        }

        return $categories;
    }

    /**
     * 指定店舗の商品一覧を取得する。
     *
     * 既存フロントJSが期待する id/category/name/price/image_path の形へ整える。
     */
    public function menusForStore(string $storeId, ?int $planTypeId = null): array
    {
        $sql = <<<SQL
            SELECT
                p.product_id,
                p.product_name,
                p.price,
                p.image_path,
                p.category_id,
                c.category_name,
                p.product_id AS display_order,
                CASE
                    WHEN ptp.product_id IS NOT NULL THEN 1
                    ELSE 0
                END AS plan_applied_flag
            FROM products AS p
            INNER JOIN product_categories AS c
                ON c.category_id = p.category_id
            LEFT JOIN plan_type_products AS ptp
                ON ptp.product_id = p.product_id
               AND ptp.plan_type_id = :plan_type_id
            WHERE p.store_id = :store_id
              AND p.sale_status = 'ON_SALE'
            ORDER BY
                c.category_id ASC,
                p.product_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        if ($planTypeId === null) {
            $statement->bindValue(':plan_type_id', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':plan_type_id', $planTypeId, PDO::PARAM_INT);
        }
        $statement->execute();

        $rows = $statement->fetchAll();
        $optionGroupsByProduct = $this->optionGroupsByProduct(array_column($rows, 'product_id'));
        $menus = [];

        foreach ($rows as $row) {
            $planApplied = (int)$row['plan_applied_flag'] === 1;
            $productId = (int)$row['product_id'];

            $menus[] = [
                'id' => $productId,
                'category_id' => (string)$row['category_id'],
                'category' => (string)$row['category_name'],
                'name' => (string)$row['product_name'],
                'price' => (int)$row['price'],
                'display_price' => $planApplied ? 0 : (int)$row['price'],
                'plan_applied_flag' => $planApplied ? 1 : 0,
                'image_path' => $this->imagePath($row['image_path'] ?? null),
                'has_options' => isset($optionGroupsByProduct[$productId]),
                'option_groups' => $optionGroupsByProduct[$productId] ?? [],
            ];
        }

        return $menus;
    }

    /**
     * 商品詳細で使うオプショングループと選択肢を、DBの表示順で商品ごとにまとめる。
     */
    private function optionGroupsByProduct(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = <<<SQL
            SELECT
                pog.product_id,
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
            WHERE pog.product_id IN ($placeholders)
            ORDER BY
                pog.product_id,
                pog.display_order,
                og.option_group_id,
                o.display_order,
                o.option_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->execute($productIds);
        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $productId = (int)$row['product_id'];
            $groupId = (int)$row['option_group_id'];

            if (!isset($grouped[$productId][$groupId])) {
                $grouped[$productId][$groupId] = [
                    'option_group_id' => $groupId,
                    'group_name' => (string)$row['option_group_name'],
                    'selection_type' => (string)$row['selection_type'],
                    'is_required' => (int)$row['is_required'],
                    'options' => [],
                ];
            }

            if ($row['option_id'] !== null) {
                $grouped[$productId][$groupId]['options'][] = [
                    'option_id' => (int)$row['option_id'],
                    'option_name' => (string)$row['option_name'],
                    'additional_price' => (int)$row['additional_price'],
                ];
            }
        }

        foreach ($grouped as $productId => $groups) {
            $grouped[$productId] = array_values($groups);
        }

        return $grouped;
    }

    private function imagePath(?string $imagePath): string
    {
        $imagePath = trim((string)$imagePath);

        if ($imagePath === '') {
            return self::NO_IMAGE_PATH;
        }

        if (str_starts_with($imagePath, '/assets/')) {
            return '/MOS_A/public' . $imagePath;
        }

        return $imagePath;
    }
}
