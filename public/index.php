<?php
declare(strict_types=1);

/**
 * アプリのエントリポイント（フロントコントローラー）。
 * すべてのリクエストはここを通る。セッションを開始し、ルーター(web.php)へ処理を委譲する。
 *
 * ただしレジ連携API(/api/orders)はサーバ間通信でCookieを持たないため、セッションを開始しない。
 * 開始するとレジがAPIを叩くたびに新しいセッションファイルが作られ、増え続けてしまう。
 */

$requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (!str_ends_with(rtrim($requestPath, '/'), '/api/orders')) {
    session_start();
}

require_once dirname(__DIR__) . '/src/Routes/web.php';