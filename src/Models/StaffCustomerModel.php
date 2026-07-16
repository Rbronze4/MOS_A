<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

final class StaffCustomerModel
{
    /**
     * 顧客一覧の絞り込み種別。顧客一覧画面のタブと1対1で対応する。
     *
     * customers.billing_status は tinyint（1:受付中 2:会計済み 4:未収金 8:会計中）。
     * 顧客はQR発行のたびに増え、会計後も行が残るため、既定では
     * 「まだ会計が終わっていない客（＝いま店にいる客）」だけを表示する。
     */
    public const FILTER_UNPAID = 'unpaid';
    public const FILTER_SEATED = 'seated';
    public const FILTER_NO_TABLE = 'no_table';
    public const FILTER_PAID = 'paid';
    public const FILTER_UNRECOVERED = 'unrecovered';
    public const FILTER_ALL = 'all';

    // 顧客一覧を開く目的は「着席中の客に注文を入れる／注文詳細を見る」ことなので、
    // 既定は着席中（＝卓番号が入っている客）にする。
    // Controller・Viewからも既定値として参照するためpublicにする。
    public const DEFAULT_FILTER = self::FILTER_SEATED;

    // 絞り込み種別 → 対象のbilling_status。ALLはここに持たない（絞り込みなし）。
    private const BILLING_STATUSES_BY_FILTER = [
        self::FILTER_UNPAID => [1, 8],
        self::FILTER_SEATED => [1, 8],
        self::FILTER_NO_TABLE => [1, 8],
        self::FILTER_PAID => [2],
        self::FILTER_UNRECOVERED => [4],
    ];

    /**
     * 卓番号の有無による絞り込み。「会計前」をさらに2つに分ける。
     *
     *   着席中   … 卓番号が入っている（＝いま注文を受けられる客）
     *   卓未入力 … QRは発行済みだが、まだ卓番号が入っていない客
     *
     * 卓番号はsessionsに入るため、判定はACTIVEセッションの有無で行う。
     * ただしセッションの有無だけで絞ると、会計を終えてセッションが閉じた客も
     * 「卓未入力」に該当してしまうため、必ずbilling_status（会計前）とのANDで判定する。
     *
     * table_numbersはACTIVEセッションのGROUP_CONCATなので、該当なしならNULLになる。
     */
    private const TABLE_CONDITION_BY_FILTER = [
        self::FILTER_SEATED => 'HAVING table_numbers IS NOT NULL',
        self::FILTER_NO_TABLE => 'HAVING table_numbers IS NULL',
    ];

    /**
     * URLクエリなど外部から渡された絞り込み種別を、既知の値に正規化する。
     * 未知の値は既定（会計前）に落とす。
     */
    public static function normalizeFilter(?string $filter): string
    {
        $filter = trim((string)$filter);

        if ($filter === self::FILTER_ALL || isset(self::BILLING_STATUSES_BY_FILTER[$filter])) {
            return $filter;
        }

        return self::DEFAULT_FILTER;
    }

    /**
     * QR発行用に、新しい顧客を1件作成する。
     *
     * customer_id は AUTO_INCREMENT に任せて連番で採番する（現在の最大IDの次番号）。
     * QRトークンのハッシュ化は開発中は見送り、識別しやすい平文プレースホルダを入れる。
     * 発行された顧客は、そのまま customer_id で客側画面を利用できる。
     *
     * @return array{customer_id:int, store_id:string, people_count:int}
     */
    public function issueCustomer(string $storeId, int $peopleCount): array
    {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            // customer_id は指定しない（AUTO_INCREMENTで自動連番）。
            // billing_status は 1:受付中 2:会計済み 4:未収金 8:会計中（tinyint）。新規発行は受付中(1)。
            $insert = $pdo->prepare(
                'INSERT INTO customers (store_id, qr_token_hash, people_count, billing_status)
                 VALUES (:store_id, :placeholder, :people_count, 1)'
            );
            $insert->bindValue(':store_id', $storeId, PDO::PARAM_STR);
            $insert->bindValue(':placeholder', 'pending', PDO::PARAM_STR);
            $insert->bindValue(':people_count', $peopleCount, PDO::PARAM_INT);
            $insert->execute();

            $customerId = (int)$pdo->lastInsertId();

            // ハッシュ化は未実装のため、開発中に識別しやすい平文トークンを入れておく。
            $update = $pdo->prepare('UPDATE customers SET qr_token_hash = :token WHERE customer_id = :customer_id');
            $update->bindValue(':token', 'dev_qr_token_' . $customerId, PDO::PARAM_STR);
            $update->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $update->execute();

            $pdo->commit();

            return [
                'customer_id' => $customerId,
                'store_id' => $storeId,
                'people_count' => $peopleCount,
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * ログイン中店舗の顧客一覧を、会計状況で絞り込んで取得する。
     *
     * 絞り込みはSQL側で行う。全件を取ってからPHPやJSで捨てると、
     * 顧客が増え続けるこのテーブルでは取得コストが下がらないため。
     */
    public function customersForStore(string $storeId, string $filter = self::DEFAULT_FILTER): array
    {
        $filter = self::normalizeFilter($filter);
        $billingStatuses = self::BILLING_STATUSES_BY_FILTER[$filter] ?? [];

        // 「全体」は絞り込みなし。それ以外はbilling_statusのIN句を組み立てる。
        $billingStatusSql = '';
        $placeholders = [];

        foreach ($billingStatuses as $index => $billingStatus) {
            $placeholders[] = ':billing_status_' . $index;
        }

        if ($placeholders !== []) {
            $billingStatusSql = 'AND c.billing_status IN (' . implode(', ', $placeholders) . ')';
        }

        // 「着席中」「卓未入力」だけ、卓番号（＝ACTIVEセッション）の有無でさらに絞る。
        $tableConditionSql = self::TABLE_CONDITION_BY_FILTER[$filter] ?? '';

        $sql = <<<SQL
            SELECT
                c.customer_id,
                c.store_id,
                c.people_count,
                c.billing_status,
                -- table_numberはvarcharのため、そのまま並べると「1, 12, 2, 7」と
                -- 文字列順になる。数値にキャストして「1, 2, 7, 12」の順で並べる。
                -- GROUP_CONCAT(DISTINCT ...)はORDER BYにDISTINCTと同じ式しか
                -- 使えないため、両方をCASTする必要がある。
                GROUP_CONCAT(
                    DISTINCT CAST(s.table_number AS UNSIGNED)
                    ORDER BY CAST(s.table_number AS UNSIGNED) ASC
                    SEPARATOR ', '
                ) AS table_numbers
            FROM customers AS c
            LEFT JOIN sessions AS s
                ON s.customer_id = c.customer_id
               AND s.store_id = c.store_id
               AND s.session_status = 'ACTIVE'
            WHERE c.store_id = :store_id
              $billingStatusSql
            GROUP BY
                c.customer_id,
                c.store_id,
                c.people_count,
                c.billing_status
            $tableConditionSql
            ORDER BY c.customer_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);

        foreach ($billingStatuses as $index => $billingStatus) {
            $statement->bindValue(':billing_status_' . $index, $billingStatus, PDO::PARAM_INT);
        }

        $statement->execute();

        $customers = [];

        foreach ($statement->fetchAll() as $row) {
            $tableNumbers = trim((string)($row['table_numbers'] ?? ''));

            $customers[] = [
                'customer_id' => (int)$row['customer_id'],
                'customer_no' => (string)$row['customer_id'],
                'table_no' => $tableNumbers !== '' ? $tableNumbers : 'なし',
                'people' => (int)$row['people_count'],
                'billing_status' => (int)$row['billing_status'],
                // 「全体」タブでは会計状況が混在するため、一覧にも状態を表示する
                'billing_status_label' => $this->billingStatusLabel((int)$row['billing_status']),
            ];
        }

        return $customers;
    }

    public function customerDetail(string $storeId, int $customerId): array
    {
        $customer = $this->findCustomer($storeId, $customerId);

        if ($customer === null) {
            throw new RuntimeException('顧客情報が見つかりません。');
        }

        $plan = $this->activePlanForCustomer($customerId);
        $sessions = $this->activeSessionsForCustomer($storeId, $customerId);

        return [
            'customer_id' => (int)$customer['customer_id'],
            'people_count' => (int)$customer['people_count'],
            'plan_label' => $this->planLabel($plan),
            'table_numbers' => $this->tableNumberLabels($sessions),
            'has_active_session' => $sessions !== [],
            'billing_status_label' => $this->billingStatusLabel((int)$customer['billing_status']),
        ];
    }

    /**
     * QR印刷ページ用に顧客1件を取得する。
     * 他店舗の顧客番号のQRを印刷できないよう、store_id一致を条件にしたfindCustomerをそのまま使う。
     */
    public function customerForPrint(string $storeId, int $customerId): ?array
    {
        return $this->findCustomer($storeId, $customerId);
    }

    private function findCustomer(string $storeId, int $customerId): ?array
    {
        $sql = <<<SQL
            SELECT
                customer_id,
                store_id,
                people_count,
                billing_status,
                created_at,
                updated_at
            FROM customers
            WHERE customer_id = :customer_id
              AND store_id = :store_id
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $customer = $statement->fetch();

        return $customer === false ? null : $customer;
    }

    private function activePlanForCustomer(int $customerId): ?array
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
                pt.plan_type_name
            FROM customer_plans AS cp
            INNER JOIN plans AS p
                ON cp.plan_id = p.plan_id
            LEFT JOIN plan_types AS pt
                ON p.plan_type_id = pt.plan_type_id
            WHERE cp.customer_id = :customer_id
              AND cp.started_at <= NOW()
              AND (cp.ended_at IS NULL OR cp.ended_at > NOW())
            ORDER BY cp.started_at DESC, cp.customer_plan_id DESC
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->execute();

        $plan = $statement->fetch();

        return $plan === false ? null : $plan;
    }

    private function activeSessionsForCustomer(string $storeId, int $customerId): array
    {
        $sql = <<<SQL
            SELECT
                session_id,
                table_number,
                session_status,
                started_at
            FROM sessions
            WHERE customer_id = :customer_id
              AND store_id = :store_id
              AND session_status = 'ACTIVE'
            ORDER BY
                CAST(table_number AS UNSIGNED) ASC,
                started_at ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetchAll();
    }

    private function planLabel(?array $plan): string
    {
        if ($plan === null) {
            return 'なし';
        }

        $planTypeName = trim((string)($plan['plan_type_name'] ?? ''));

        if ($planTypeName !== '') {
            return $planTypeName;
        }

        return 'あり';
    }

    private function tableNumberLabels(array $sessions): array
    {
        $labels = [];

        foreach ($sessions as $session) {
            $tableNumber = trim((string)$session['table_number']);

            if ($tableNumber !== '') {
                $labels[] = $tableNumber . '番';
            }
        }

        return array_values(array_unique($labels));
    }

    // billing_status（tinyint 1:受付中 2:会計済み 4:未収金 8:会計中）の表示ラベル。
    private function billingStatusLabel(int $status): string
    {
        return match ($status) {
            1 => '未会計',
            8 => '会計待ち',
            2 => '会計済み',
            4 => '未収金',
            default => '不明',
        };
    }
}
