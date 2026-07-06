<?php
declare(strict_types=1);

/**
 * DB接続設定。
 *
 * XAMPPのローカル開発環境を基本値にしつつ、環境変数があれば優先する。
 * SQLインジェクション対策のため、DB利用側では必ずプリペアドステートメントを使う。
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('MOS_DB_HOST') ?: 'localhost';
    $port = getenv('MOS_DB_PORT') ?: '3306';
    $database = getenv('MOS_DB_NAME') ?: 'mos_a_system';
    $user = getenv('MOS_DB_USER') ?: 'root';
    $password = getenv('MOS_DB_PASSWORD') ?: '';

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    $socket = @fsockopen($host, (int)$port, $errno, $errstr, 1.0);

    if ($socket === false) {
        throw new RuntimeException(sprintf(
            'Database server is not reachable: %s:%s (%s)',
            $host,
            $port,
            $errstr !== '' ? $errstr : 'connection failed'
        ));
    }

    stream_set_timeout($socket, 1);
    $handshake = fread($socket, 1);
    $meta = stream_get_meta_data($socket);
    fclose($socket);

    if ($handshake === '' || !empty($meta['timed_out'])) {
        throw new RuntimeException(sprintf(
            'Database server did not respond: %s:%s',
            $host,
            $port
        ));
    }

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 2,
    ]);

    return $pdo;
}
