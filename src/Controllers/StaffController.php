<?php
declare(strict_types=1);

/**
 * スタッフ側画面のコントローラー。
 * 現状はDB未接続のため、顧客・注文・商品データをハードコード(private メソッド)で用意し、
 * 画面ごとに読み込むCSS/JS($cssFiles/$jsFiles)を指定して共通レイアウト(app.php)で描画する。
 * 注文・商品データはレイアウトを通じて window.STAFF_DATA としてJSへ渡される。
 *
 * メソッド:
 *   index()      … スタッフダッシュボード（ログイン〜各管理画面）
 *   orderEntry() … スタッフ代理注文：卓番号・プラン入力画面
 *   orderMenu()  … スタッフ代理注文：メニュー選択画面
 *   customers()/orders()/products() … ダミーデータ提供（private）
 */
final class StaffController
{
    public function index(): void
    {
        $title = 'MOS 店員画面';

        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css',
            '/MOS_A/public/assets/css/staff/base.css',
            '/MOS_A/public/assets/css/staff/orders.css',
            '/MOS_A/public/assets/css/staff/modals-products.css',
            '/MOS_A/public/assets/css/staff/navigation.css',
            '/MOS_A/public/assets/css/staff/order-list.css',
        ];
        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js',
            '/MOS_A/public/assets/js/staff/dashboard/orders.js',
            '/MOS_A/public/assets/js/staff/dashboard/products.js',
            '/MOS_A/public/assets/js/staff/dashboard/customers.js',
            '/MOS_A/public/assets/js/staff/dashboard/qr.js',
            '/MOS_A/public/assets/js/staff/dashboard.js',
        ];

        $customers = $this->customers();
        $orders = $this->orders();
        $products = $this->products();

        $view = dirname(__DIR__) . '/Views/staff/dashboard.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function orderEntry(): void
    {
        $title = 'スタッフ注文';

        $assetVersion = time();
        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/base.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/entry.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/menu.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/cart.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/navigation.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/responsive.css?v=' . $assetVersion,
        ];

        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/orders.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/products.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/customers.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/qr.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/order-menu.js?v=' . $assetVersion,
        ];

        $orders = $this->orders();
        $products = $this->products();

        $view = dirname(__DIR__) . '/Views/staff/screens/staff_order_entry.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function orderMenu(): void
    {
        $title = 'スタッフ注文';

        $assetVersion = time();
        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/base.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/entry.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/menu.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/cart.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/navigation.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/responsive.css?v=' . $assetVersion,
        ];

        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/orders.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/products.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/customers.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/qr.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/order-menu.js?v=' . $assetVersion,
        ];

        $orders = $this->orders();
        $products = $this->products();

        $view = dirname(__DIR__) . '/Views/staff/screens/staff_order_menu.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    private function customers(): array
    {
        return [
            ['table_no' => '1番', 'customer_no' => '1234567', 'people' => 4],
            ['table_no' => '2番', 'customer_no' => '1234567', 'people' => 5],
            ['table_no' => '3番', 'customer_no' => '1234567', 'people' => 3],
        ];
    }

    private function orders(): array
    {
        return [
            ['id' => 1, 'table_no' => '12番', 'name' => 'もも串 塩', 'qty' => 3, 'time' => '19:05', 'status' => 'waiting'],
            ['id' => 2, 'table_no' => '5番', 'name' => 'ビール', 'qty' => 5, 'time' => '19:25', 'status' => 'served'],
            ['id' => 3, 'table_no' => '3番', 'name' => 'コークハイ', 'qty' => 1, 'time' => '19:40', 'status' => 'canceled'],
        ];
    }

    private function products(): array
    {
        return [
            ['id' => 1, 'name' => 'もも串 タレ', 'category' => '串', 'stock' => 30, 'price' => 200],
            ['id' => 2, 'name' => 'もも串 塩', 'category' => '串', 'stock' => 100, 'price' => 200],
            ['id' => 3, 'name' => 'ビール', 'category' => 'ドリンク', 'stock' => 200, 'price' => 200],
        ];
    }
}
