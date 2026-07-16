<?php
declare(strict_types=1);

/**
 * スタッフ ダッシュボード レイアウト確認用エントリ
 * アクセス: http://localhost/MOS_A/mocks/staff.php
 *
 * CSS … mocks/css を参照（ここを編集すると即反映）
 * JS  … 本物の public/assets/js を流用
 *
 * 本体のStaffController::index()がビューへ渡す変数と同じものを用意する。
 */

require __DIR__ . '/_data.php';

$customers = mock_customers();
$orders = mock_orders();
$products = mock_products();

// 商品追加・編集モーダルのカテゴリ選択で使う（window.STAFF_DATAへ渡る）
$productCategories = mock_product_categories();

// ログイン中の店舗。本体ではセッションから取得する。
$storeName = '本店';

$title = 'MOS 店員画面 (mock)';

$cssFiles = [
    '/MOS_A/mocks/css/common/base.css',
    '/MOS_A/mocks/css/staff/base.css',
    '/MOS_A/mocks/css/staff/orders.css',
    '/MOS_A/mocks/css/staff/modals-products.css',
    '/MOS_A/mocks/css/staff/navigation.css',
    '/MOS_A/mocks/css/staff/order-list.css',
];

$jsFiles = [
    '/MOS_A/public/assets/js/common/side-menu.js',
    '/MOS_A/public/assets/js/staff/dashboard/orders.js',
    '/MOS_A/public/assets/js/staff/dashboard/products.js',
    '/MOS_A/public/assets/js/staff/dashboard/customers.js',
    '/MOS_A/public/assets/js/staff/dashboard/qr.js',
    '/MOS_A/public/assets/js/staff/dashboard.js',
];

$view = __DIR__ . '/Views/staff/dashboard.php';

require __DIR__ . '/Views/layouts/app.php';
