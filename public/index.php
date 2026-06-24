<?php
declare(strict_types=1);

/**
 * アプリのエントリポイント（フロントコントローラー）。
 * すべてのリクエストはここを通る。セッションを開始し、ルーター(web.php)へ処理を委譲する。
 */

session_start();

require_once dirname(__DIR__) . '/src/Routes/web.php';