<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/**
 * アプリのエントリポイント（フロントコントローラー）。
 * すべてのリクエストはここを通る。
 * セッションを開始し、ルート定義(web.php)を読み込み、
 * URLに対応するControllerのメソッドを実行する。
 */

session_start();

/**
 * Controllerファイルを読み込む
 */
require_once dirname(__DIR__) . '/src/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/src/Controllers/StaffController.php';

/**
 * ルート定義を読み込む
 */
$routes = require dirname(__DIR__) . '/src/Routes/web.php';

/**
 * 現在アクセスされているパスを取得する
 *
 * 例：
 * /MOS_A/public/login → /login
 * /MOS_A/public/staff → /staff
 */
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/MOS_A/public';

$path = $requestUri;

if (str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}

if ($path === '') {
    $path = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

/**
 * ルート定義と照合する
 */
foreach ($routes as $route) {
    [$routeMethod, $routePath, $handler] = $route;

    if ($method === $routeMethod && $path === $routePath) {
        [$controllerName, $actionName] = explode('@', $handler);

        if (!class_exists($controllerName)) {
            http_response_code(500);
            echo 'Controllerが見つかりません: ' . htmlspecialchars($controllerName, ENT_QUOTES, 'UTF-8');
            exit;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $actionName)) {
            http_response_code(500);
            echo 'メソッドが見つかりません: ' . htmlspecialchars($controllerName . '@' . $actionName, ENT_QUOTES, 'UTF-8');
            exit;
        }

        $controller->$actionName();
        exit;
    }
}

/**
 * 一致するルートがない場合
 */
http_response_code(404);
echo '404 Not Found<br>';
echo 'method: ' . htmlspecialchars($method, ENT_QUOTES, 'UTF-8') . '<br>';
echo 'path: ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '<br>';