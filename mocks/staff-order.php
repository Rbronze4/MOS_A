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
 */

require __DIR__ . '/_data.php';

$orders = mock_orders();
$products = mock_products();

$title = 'スタッフ注文 (mock)';

// mock情報パネルで現在の画面に対応する変更ログを強調表示するための識別子
$mockArea = 'staff-order';

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
