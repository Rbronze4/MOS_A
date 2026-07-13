<?php
declare(strict_types=1);

/**
 * スタッフ注文 レイアウト確認用エントリ
 * アクセス:
 *   卓番号入力画面 … http://localhost/MOS_A/mocks/staff-order.php
 *   メニュー画面   … http://localhost/MOS_A/mocks/staff-order.php?screen=menu&tableNo=5&plan=standard
 *
 * CSS … mocks/css を参照（ここを編集すると即反映）
 * JS  … 本物の public/assets/js を流用
 *
 * 本体のStaffController::orderEntry() / orderMenu()がビューへ渡す変数と同じものを用意する。
 */

require __DIR__ . '/_data.php';

$orders = mock_orders();
$products = mock_products();
$productCategories = mock_product_categories();

// メニュー画面（staff_order_menu.php）が必要とする変数。
// 本体ではDBから取得するが、mocksではダミーデータを使う。
$categories = mock_categories();
$menus = mock_menus();
$staffOrderError = '';

// 利用中セッション。本体ではDBから取得する。
// nullにすると「セッションが見つかりません」の表示になるため、ダミーを入れておく。
$activeSession = [
    'session_id' => 1,
    'customer_id' => 1000001,
    'store_id' => 'MH',
    'table_number' => (string)($_GET['tableNo'] ?? '5'),
    'session_status' => 'ACTIVE',
];

$storeName = '本店';

$title = 'スタッフ注文 (mock)';

$cssFiles = [
    '/MOS_A/mocks/css/common/base.css',
    '/MOS_A/mocks/css/staff-order/base.css',
    '/MOS_A/mocks/css/staff-order/entry.css',
    '/MOS_A/mocks/css/staff-order/menu.css',
    '/MOS_A/mocks/css/staff-order/cart.css',
    '/MOS_A/mocks/css/staff-order/navigation.css',
    '/MOS_A/mocks/css/staff-order/responsive.css',
];

$jsFiles = [
    '/MOS_A/public/assets/js/common/side-menu.js',
    '/MOS_A/public/assets/js/staff/dashboard/orders.js',
    '/MOS_A/public/assets/js/staff/dashboard/products.js',
    '/MOS_A/public/assets/js/staff/dashboard/customers.js',
    '/MOS_A/public/assets/js/staff/dashboard/qr.js',
    '/MOS_A/public/assets/js/staff/dashboard.js',
    '/MOS_A/public/assets/js/staff/order-menu.js',
];

// ?screen=menu でメニュー画面、それ以外は卓番号入力画面
$screen = $_GET['screen'] ?? 'entry';

if ($screen === 'menu') {
    $view = __DIR__ . '/Views/staff/screens/staff_order_menu.php';
} else {
    $view = __DIR__ . '/Views/staff/screens/staff_order_entry.php';
}

require __DIR__ . '/Views/layouts/app.php';
