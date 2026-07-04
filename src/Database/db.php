<?php
declare(strict_types=1);

/**
 * db.php
 *
 * データベース接続を管理するファイル。
 *
 * 主な役割：
 * - MySQLにPDOで接続する
 * - DB接続オブジェクトを db() 関数で返す
 * - 同じリクエスト内では接続を使い回す
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = 'localhost';
    $dbname = 'mos_a_system';
    $user = 'root';
    $password = '';

    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
