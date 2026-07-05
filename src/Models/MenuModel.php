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

    /**
     * 指定店舗で販売中の商品が存在するカテゴリだけを取得する。
     *
     * 店舗ごとの販売設定は store_products にあるため、必ず store_products を起点にする。
     */
    public function categoriesForStore(string $storeId): array
    {
        $sql = <<<SQL
            SELECT DISTINCT
                c.category_id,
                c.category_name
            FROM store_products AS sp
            INNER JOIN products AS p
                ON p.product_id = sp.product_id
            INNER JOIN product_categories AS c
                ON c.category_id = p.category_id
            WHERE sp.store_id = :store_id
              AND sp.sale_status = 'ON_SALE'
              AND p.sale_status = 'ON_SALE'
            ORDER BY
                c.category_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        return array_map(
            static fn (array $row): string => (string)$row['category_name'],
            $statement->fetchAll()
        );
    }

    /**
     * 指定店舗の商品一覧を取得する。
     *
     * 既存フロントJSが期待する id/category/name/price/image_path の形へ整える。
     */
    public function menusForStore(string $storeId): array
    {
        $sql = <<<SQL
            SELECT
                p.product_id,
                p.product_name,
                p.price,
                p.image_path,
                p.category_id,
                c.category_name,
                sp.display_order
            FROM store_products AS sp
            INNER JOIN products AS p
                ON p.product_id = sp.product_id
            INNER JOIN product_categories AS c
                ON c.category_id = p.category_id
            WHERE sp.store_id = :store_id
              AND sp.sale_status = 'ON_SALE'
              AND p.sale_status = 'ON_SALE'
            ORDER BY
                sp.display_order ASC,
                p.product_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $menus = [];

        foreach ($statement->fetchAll() as $row) {
            $menus[] = [
                'id' => (int)$row['product_id'],
                'category' => (string)$row['category_name'],
                'name' => (string)$row['product_name'],
                'price' => (int)$row['price'],
                'image_path' => $this->imagePath($row['image_path'] ?? null),
            ];
        }

        return $menus;
    }

    private function imagePath(?string $imagePath): string
    {
        $imagePath = trim((string)$imagePath);

        if ($imagePath === '') {
            return self::NO_IMAGE_PATH;
        }

        return $imagePath;
    }
}
