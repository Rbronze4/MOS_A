<?php
declare(strict_types=1);

/**
 * 簡易ルーター。
 * REQUEST_URI のパスから basePath(/MOS_A/public) を除き、switch で対応する
 * コントローラーのメソッドを呼び分ける。
 *   / ・/staff            → StaffController::index（ダッシュボード）
 *   /staff/order-entry    → StaffController::orderEntry（スタッフ注文 卓番号入力）
 *   /staff/order-menu     → StaffController::orderMenu（スタッフ注文 メニュー）
 *   /customer             → CustomerController::index（客側）
 *   該当なし              → 404
 */

require_once dirname(__DIR__) . '/Controllers/StaffController.php';
require_once dirname(__DIR__) . '/Controllers/CustomerController.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/MOS_A/public';

if (str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}

$path = $path === '' ? '/' : $path;

switch ($path) {
    case '/':
    case '/staff':
        $controller = new StaffController();
        $controller->index();
        break;

    case '/staff/order-entry':
        $controller = new StaffController();
        $controller->orderEntry();
        break;

    case '/staff/order-menu':
        $controller = new StaffController();
        $controller->orderMenu();
        break;

    case '/customer':
        $controller = new CustomerController();
        $controller->index();
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}