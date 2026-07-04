<?php
declare(strict_types=1);

/**
 * StaffController.php
 *
 * スタッフ側画面を表示するController。
 *
 * 主な役割：
 * - ログイン済みか確認する
 * - スタッフダッシュボードを表示する
 * - スタッフ代理注文の卓番号・プラン入力画面を表示する
 * - スタッフ代理注文のメニュー選択画面を表示する
 * - 現状はDB未接続のため、顧客・注文・商品データをダミーデータとして用意する
 *
 * ログイン済みの場合は、AuthControllerでセッションに保存した
 * 店舗ID・店舗名を各画面で利用できる。
 */
final class StaffController
{
    /**
     * ログイン済みか確認する。
     *
     * 未ログインの場合はログイン画面へリダイレクトする。
     */
    private function requireLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['is_logged_in'])) {
            header('Location: /MOS_A/public/login');
            exit;
        }
    }

    /**
     * スタッフダッシュボード画面を表示する。
     */
    public function index(): void
    {
        $this->requireLogin();

        $title = 'MOS 店員画面';

        // ログイン時に保存した店舗情報
        $storeId = $_SESSION['store_id'] ?? '';
        $storeName = $_SESSION['store_name'] ?? '店舗未選択';

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

    /**
     * スタッフ代理注文：卓番号・プラン入力画面を表示する。
     */
    public function orderEntry(): void
    {
        $this->requireLogin();

        $title = 'スタッフ注文';

        // ログイン時に保存した店舗情報
        $storeId = $_SESSION['store_id'] ?? '';
        $storeName = $_SESSION['store_name'] ?? '店舗未選択';

        // CSS/JSのキャッシュ対策
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

    /**
     * スタッフ代理注文：メニュー選択画面を表示する。
     */
    public function orderMenu(): void
    {
        $this->requireLogin();

        $title = 'スタッフ注文';

        // ログイン時に保存した店舗情報
        $storeId = $_SESSION['store_id'] ?? '';
        $storeName = $_SESSION['store_name'] ?? '店舗未選択';

        // CSS/JSのキャッシュ対策
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

    /**
     * 顧客一覧のダミーデータ。
     *
     * DB接続後はRepositoryから取得する形に変更する。
     */
    private function customers(): array
    {
        return [
            [
                'table_no' => '1番',
                'customer_no' => '1234567',
                'people' => 4,
            ],
            [
                'table_no' => '2番',
                'customer_no' => '1234567',
                'people' => 5,
            ],
            [
                'table_no' => '3番',
                'customer_no' => '1234567',
                'people' => 3,
            ],
        ];
    }

    /**
     * 注文一覧のダミーデータ。
     *
     * DB接続後はRepositoryから取得する形に変更する。
     */
    private function orders(): array
    {
        return [
            [
                'id' => 1,
                'table_no' => '12番',
                'name' => 'もも串 塩',
                'qty' => 3,
                'time' => '19:05',
                'status' => 'waiting',
            ],
            [
                'id' => 2,
                'table_no' => '5番',
                'name' => 'ビール',
                'qty' => 5,
                'time' => '19:25',
                'status' => 'served',
            ],
            [
                'id' => 3,
                'table_no' => '3番',
                'name' => 'コークハイ',
                'qty' => 1,
                'time' => '19:40',
                'status' => 'canceled',
            ],
        ];
    }

    /**
     * 商品一覧のダミーデータ。
     *
     * DB接続後はRepositoryから取得する形に変更する。
     */
    private function products(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'もも串 タレ',
                'category' => '串',
                'stock' => 30,
                'price' => 200,
            ],
            [
                'id' => 2,
                'name' => 'もも串 塩',
                'category' => '串',
                'stock' => 100,
                'price' => 200,
            ],
            [
                'id' => 3,
                'name' => 'ビール',
                'category' => 'ドリンク',
                'stock' => 200,
                'price' => 200,
            ],
        ];
    }
}