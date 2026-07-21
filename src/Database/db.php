<?php
declare(strict_types=1);

/**
 * DB接続設定。
 *
 * XAMPPのローカル開発環境を基本値にしつつ、環境変数があれば優先する。
 * SQLインジェクション対策のため、DB利用側では必ずプリペアドステートメントを使う。
 *
 * 以前はPDO接続の前にfsockopenでMySQLへ疎通確認していたが、これは
 * 「接続して1バイト読んで即切断」という動きのため、MySQL側では毎回
 * 中断された接続（Aborted_connects）として記録されていた。
 * リクエストのたびに加算され、max_connect_errors（既定100）に達すると
 * MySQLがホストごと接続を拒否するため、APIが間欠的に落ちる原因になっていた。
 * 疎通の失敗はPDOが例外で報告するので、事前チェックは行わない。
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

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 2,
        ]);
    } catch (PDOException $exception) {
        // 呼び出し側は「DBに繋がらなかった」ことだけ分かればよい。
        // 接続情報が例外メッセージ経由で画面に出ないよう、host:portのみを含める。
        throw new RuntimeException(
            sprintf('Database server is not reachable: %s:%s', $host, $port),
            0,
            $exception
        );
    }

    return $pdo;
}
