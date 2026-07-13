<?php
declare(strict_types=1);

/**
 * 客側画面 レイアウト確認用エントリ
 * アクセス: http://localhost/MOS_A/mocks/customer.php
 *
 * CSS … mocks/css を参照（ここを編集すると即反映）
 * JS  … 本物の public/assets/js を流用
 *
 * 本体のCustomerController::index()がビューへ渡す変数と同じものを用意する。
 * 変数が欠けるとJS(window.MOS_DATA)に値が渡らず、画面が正しく動かない。
 */

require __DIR__ . '/_data.php';

$plans = mock_plans();
$categories = mock_categories();
$menus = mock_menus();

// DBのplans由来の単価。plans.jsが「単価×人数」で合計金額を計算する。
$planUnitPrices = mock_plan_unit_prices();

// 客側の状態。mocksではプラン未選択・卓番号入力前の初期状態から始める。
$customerId = 1000001;
$sessionId = null;
$storeId = 'MH';
$peopleCount = 4;
$cartItems = [];
$historyItems = [];
$activeCustomerPlan = null;
$hasActiveCustomerPlan = false;

$title = 'MOS 客側画面 (mock)';

$cssFiles = [
    '/MOS_A/mocks/css/common/base.css',
    '/MOS_A/mocks/css/customer/base.css',
    '/MOS_A/mocks/css/customer/plans.css',
    '/MOS_A/mocks/css/customer/menu.css',
    '/MOS_A/mocks/css/customer/product-cart-history.css',
    '/MOS_A/mocks/css/customer/overlays.css',
];

$jsFiles = [
    '/MOS_A/public/assets/js/customer/modules/plans.js',
    '/MOS_A/public/assets/js/customer/modules/menu.js',
    '/MOS_A/public/assets/js/customer/modules/cart-history.js',
    '/MOS_A/public/assets/js/customer/app.js',
];

$view = __DIR__ . '/Views/customer/customer_app.php';

require __DIR__ . '/Views/layouts/app.php';
