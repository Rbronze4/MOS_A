<?php
declare(strict_types=1);

/**
 * 客側画面 レイアウト確認用エントリ
 * アクセス: http://localhost/MOS_A/mocks/customer.php
 *
 * CSS … mocks/css を参照（ここを編集すると即反映）
 * JS  … 本物の public/assets/js を流用
 */

require __DIR__ . '/_data.php';

$plans = mock_plans();
$categories = mock_categories();
$menus = mock_menus();

$title = 'MOS 客側画面 (mock)';

// mock情報パネルで現在の画面に対応する変更ログを強調表示するための識別子
$mockArea = 'customer';

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
