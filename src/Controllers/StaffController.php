<?php
declare(strict_types=1);

final class StaffController
{
    public function index(): void
    {
        $title = 'MOS 店員画面';

        $cssFile = '/MOS_A/public/assets/css/staff.css';
        $jsFile = '/MOS_A/public/assets/js/staff.js';

        $customers = $this->customers();
        $orders = $this->orders();
        $products = $this->products();

        $view = dirname(__DIR__) . '/Views/staff/dashboard.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function orderEntry(): void
    {
        $title = 'スタッフ注文';

        $cssFile = '/MOS_A/public/assets/css/staff_order.css?v=' . time();

        $jsFiles = [
            '/MOS_A/public/assets/js/staff.js?v=' . time(),
            '/MOS_A/public/assets/js/staff_order.js?v=' . time(),
        ];

        $orders = $this->orders();
        $products = $this->products();

        $view = dirname(__DIR__) . '/Views/staff/screens/staff_order_entry.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function orderMenu(): void
    {
        $title = 'スタッフ注文';

        $cssFile = '/MOS_A/public/assets/css/staff_order.css?v=' . time();

        $jsFiles = [
            '/MOS_A/public/assets/js/staff.js?v=' . time(),
            '/MOS_A/public/assets/js/staff_order.js?v=' . time(),
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
            ['id' => 1, 'table_no' => '12番', 'name' => 'モモ串　しお', 'qty' => 3, 'time' => '19:05', 'status' => 'waiting'],
            ['id' => 2, 'table_no' => '5番', 'name' => 'ビール', 'qty' => 5, 'time' => '19:25', 'status' => 'served'],
            ['id' => 3, 'table_no' => '3番', 'name' => 'コークハイ', 'qty' => 1, 'time' => '19:40', 'status' => 'canceled'],
        ];
    }

    private function products(): array
    {
        return [
            ['id' => 1, 'name' => 'もも串　タレ', 'category' => '串', 'stock' => 30, 'price' => 200],
            ['id' => 2, 'name' => 'もも串　しお', 'category' => '串', 'stock' => 100, 'price' => 200],
            ['id' => 3, 'name' => 'ビール', 'category' => 'ドリンク', 'stock' => 200, 'price' => 200],
        ];
    }
}