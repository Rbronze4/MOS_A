<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

/**
 * レジ連携API（/api/orders）用のModel。
 *
 * MOSの「顧客→セッション→注文→注文明細」という4階層を、
 * レジが期待する「1顧客＝1オブジェクト＋items[]」の形へ平坦化して返す。
 *
 * storeId / entryTime は sessions ではなく customers から取る。
 * QR発行直後でセッションが未開始の顧客も返す必要があるため。
 *
 * 主なメソッド:
 * - findOrders()          : 条件に一致する注文を契約どおりの形で返す
 * - findOrder()           : 顧客ID1件分を取得する
 * - computeHash()         : 注文内容からSHA-256ハッシュを計算する
 * - updateBillingStatus() : 会計状況とハッシュを更新する
 */
final class ApiOrderModel
{
    /** billStatusのビットマスク上限（1:受付中 | 2:会計済み | 4:未収金 | 8:会計中）。 */
    public const BILL_STATUS_MAX = 15;

    /**
     * 条件に一致する注文を、レジの契約どおりの形で返す。
     *
     * 契約にstoreIdの指定が無いため店舗では絞らない。どの店舗の注文を扱うかはレジ側の責務。
     *
     * @return array<int, array<string, mixed>>
     */
    public function findOrders(
        ?string $customerId,
        ?int $billStatus,
        ?string $fromTime,
        ?string $toTime
    ): array {
        $conditions = [];
        $params = [];

        if ($customerId !== null) {
            $conditions[] = 'c.customer_id = :customer_id';
            $params[':customer_id'] = (int)$customerId;
        }

        if ($billStatus !== null) {
            // billStatusはビットマスク。指定されたビットのいずれかが立っていれば対象とする。
            $conditions[] = '(c.billing_status & :bill_status) <> 0';
            $params[':bill_status'] = $billStatus;
        }

        // fromTime / toTime は来店時刻（entryTime = customers.created_at）で絞る。
        if ($fromTime !== null) {
            $conditions[] = 'c.created_at >= :from_time';
            $params[':from_time'] = $this->toDatabaseDateTime($fromTime);
        }

        if ($toTime !== null) {
            $conditions[] = 'c.created_at <= :to_time';
            $params[':to_time'] = $this->toDatabaseDateTime($toTime);
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = <<<SQL
            SELECT
                c.customer_id,
                c.store_id,
                c.billing_status,
                c.created_at
            FROM customers c
            $where
            ORDER BY c.customer_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->execute($params);
        $customers = $statement->fetchAll();

        if ($customers === []) {
            return [];
        }

        $itemsByCustomer = $this->itemsByCustomer(array_column($customers, 'customer_id'));
        $orders = [];

        foreach ($customers as $customer) {
            $customerKey = (int)$customer['customer_id'];
            $orders[] = $this->formatOrder($customer, $itemsByCustomer[$customerKey] ?? []);
        }

        return $orders;
    }

    /**
     * 顧客ID1件分の注文を返す。存在しなければnull。
     *
     * @return array<string, mixed>|null
     */
    public function findOrder(string $customerId): ?array
    {
        return $this->findOrders($customerId, null, null, null)[0] ?? null;
    }

    /**
     * 会計状況と、その時点の注文内容のハッシュを保存する。
     */
    public function updateBillingStatus(string $customerId, int $billStatus, string $hash): void
    {
        $statement = db()->prepare(
            'UPDATE customers
                SET billing_status = :bill_status,
                    order_hash = :order_hash
              WHERE customer_id = :customer_id'
        );

        $statement->execute([
            ':bill_status' => $billStatus,
            ':order_hash' => $hash,
            ':customer_id' => (int)$customerId,
        ]);
    }

    /**
     * 注文内容からハッシュを計算する。
     *
     * レジ側の生成規則（参照用/api_test_tool/mos_test/hash_util.py）に合わせる。
     * categoryNameはハッシュに含めない。
     *
     * @param array<string, mixed> $order
     */
    public function computeHash(array $order): string
    {
        $items = [];

        foreach ($order['items'] as $item) {
            // Pythonのsort_keys=Trueに合わせ、キーをアルファベット順に並べる。
            $items[] = [
                'menuName' => $item['menuName'],
                'offerQty' => $item['offerQty'],
                'orderQty' => $item['orderQty'],
                'orderTime' => $item['orderTime'],
                'taxRate' => $item['taxRate'],
                'unitPrice' => $item['unitPrice'],
            ];
        }

        $material = [
            'customerId' => $order['customerId'],
            'entryTime' => $order['entryTime'],
            'items' => $items,
            'storeId' => $order['storeId'],
        ];

        // json.dumps(ensure_ascii=False, separators=(",",":"), sort_keys=True) と同等の文字列にする。
        // PHPの区切り文字は既に「,」「:」（スペース無し）なので、指定が要るのは下記2つだけ。
        $json = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', (string)$json);
    }

    /**
     * 顧客の注文明細をまとめて取得し、顧客IDごとにグループ化して返す。
     *
     * キャンセル済み明細は会計対象外のため除外する。
     *
     * @param array<int, mixed> $customerIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function itemsByCustomer(array $customerIds): array
    {
        $placeholders = implode(',', array_fill(0, count($customerIds), '?'));

        $sql = <<<SQL
            SELECT
                s.customer_id,
                od.ordered_at,
                od.ordered_product_name,
                od.ordered_unit_price,
                od.quantity,
                od.provided_quantity,
                p.tax_rate,
                pc.category_name
            FROM sessions s
            INNER JOIN orders o ON o.session_id = s.session_id
            INNER JOIN order_details od ON od.order_id = o.order_id
            INNER JOIN products p ON p.product_id = od.product_id
            LEFT JOIN product_categories pc ON pc.category_id = p.category_id
            WHERE s.customer_id IN ($placeholders)
              AND od.detail_status <> 'CANCELLED'
            ORDER BY s.customer_id, od.ordered_at, od.order_detail_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->execute(array_map('intval', $customerIds));

        $grouped = [];

        foreach ($statement->fetchAll() as $row) {
            $grouped[(int)$row['customer_id']][] = [
                'orderTime' => $this->toIso8601((string)$row['ordered_at']),
                'menuName' => (string)$row['ordered_product_name'],
                'unitPrice' => (int)$row['ordered_unit_price'],
                // 契約上taxRateはintだが、products.tax_rateはdecimal(5,2)で"10.00"と返るため丸める。
                'taxRate' => (int)round((float)$row['tax_rate']),
                'orderQty' => (int)$row['quantity'],
                'offerQty' => (int)$row['provided_quantity'],
                'categoryName' => $row['category_name'] === null ? null : (string)$row['category_name'],
            ];
        }

        return $grouped;
    }

    /**
     * 顧客1件と、その明細から、契約どおりの注文オブジェクトを組み立てる。
     *
     * @param array<string, mixed> $customer
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function formatOrder(array $customer, array $items): array
    {
        $order = [
            'storeId' => (string)$customer['store_id'],
            // customerIdは7桁の文字列で返す契約。
            'customerId' => str_pad((string)$customer['customer_id'], 7, '0', STR_PAD_LEFT),
            'entryTime' => $this->toIso8601((string)$customer['created_at']),
            'billStatus' => (int)$customer['billing_status'],
            'items' => $items,
        ];

        $order['hash'] = $this->computeHash($order);

        return $order;
    }

    /**
     * MySQLのdatetime（"Y-m-d H:i:s"）をISO8601（"Y-m-d\TH:i:s"）へ変換する。
     */
    private function toIso8601(string $dateTime): string
    {
        return str_replace(' ', 'T', $dateTime);
    }

    /**
     * ISO8601（"Y-m-d\TH:i:s"）をMySQLのdatetime（"Y-m-d H:i:s"）へ変換する。
     */
    private function toDatabaseDateTime(string $dateTime): string
    {
        return str_replace('T', ' ', $dateTime);
    }
}
