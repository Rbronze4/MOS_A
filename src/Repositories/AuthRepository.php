<?php
declare(strict_types=1);

/**
 * AuthRepository.php
 *
 * ログイン認証に必要なデータをDBから取得するRepository。
 *
 * 主な役割：
 * - 有効な店舗一覧を取得する
 * - 店舗IDに紐づく有効なアカウント情報を取得する
 */

require_once dirname(__DIR__) . '/Database/db.php';

final class AuthRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    /**
     * 店舗IDに紐づく有効なアカウントを取得する
     */
    public function findActiveAccountByStoreId(string $storeId): ?array
    {
        $sql = "
            SELECT
                sa.account_id,
                sa.store_id,
                sa.login_id,
                sa.password_hash,
                s.store_name
            FROM store_accounts sa
            INNER JOIN stores s
                ON sa.store_id = s.store_id
            WHERE sa.store_id = :store_id
              AND sa.is_active = 1
              AND s.is_active = 1
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $stmt->execute();

        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    /**
     * ログイン画面に表示する有効な店舗一覧を取得する
     */
    public function getActiveStores(): array
    {
        $sql = "
            SELECT
                store_id,
                store_name
            FROM stores
            WHERE is_active = 1
            ORDER BY store_id
        ";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}