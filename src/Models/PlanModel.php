<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

/**
 * プランModel。
 *
 * 画面表示用のプラン情報を、店舗ごと・制限時間ごとにDBのplansから取得する。
 * 価格は店舗・プラン種別・制限時間の組み合わせで変わるため、
 * 画面側で価格をハードコードせず、必ずここで取得した値を使う。
 *
 * plan_type_id は CustomerSessionModel::PLAN_TYPE_BY_KEY と対応させる。
 *   1 = standard（スタンダード）／ 2 = premium（プレミアム）
 */
final class PlanModel
{
    // 画面用のプランキーとDBのplan_type_idの対応。
    // CustomerSessionModelのPLAN_TYPE_BY_KEYと同じ対応にすること。
    private const PLAN_KEY_BY_TYPE = [
        1 => 'standard',
        2 => 'premium',
    ];

    /**
     * 指定店舗で有効な飲み放題プランの単価を、プランキー・制限時間ごとに取得する。
     *
     * 戻り値の形（画面・JSがそのまま使えるようにキーで引ける形にする）:
     *   [
     *     'standard' => [120 => 2200, 180 => 3000],
     *     'premium'  => [120 => 3200, 180 => 4200],
     *   ]
     *
     * @return array<string, array<int, int>>
     */
    public function unitPricesForStore(string $storeId): array
    {
        $sql = <<<SQL
            SELECT
                plan_type_id,
                time_limit_minutes,
                price
            FROM plans
            WHERE store_id = :store_id
              AND is_active = 1
            ORDER BY
                plan_type_id ASC,
                time_limit_minutes ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $unitPrices = [];

        foreach ($statement->fetchAll() as $row) {
            $planTypeId = (int)$row['plan_type_id'];

            // 画面に存在しないプラン種別がDBに増えても、画面側は無視する
            if (!isset(self::PLAN_KEY_BY_TYPE[$planTypeId])) {
                continue;
            }

            $planKey = self::PLAN_KEY_BY_TYPE[$planTypeId];
            $minutes = (int)$row['time_limit_minutes'];

            $unitPrices[$planKey][$minutes] = (int)$row['price'];
        }

        return $unitPrices;
    }
}
