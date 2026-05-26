<?php
declare(strict_types=1);

final class StaffController
{
    public function index(): void
    {
        $title = 'MOS 店員画面';

        // ここが重要
        $cssFile = '/MOS_A/public/assets/css/staff.css';
        $jsFile = '/MOS_A/public/assets/js/staff.js';

        $customers = [
            ['table_no' => '1番', 'customer_no' => '1234567', 'people' => 4],
            ['table_no' => '2番', 'customer_no' => '1234567', 'people' => 5],
            ['table_no' => '3番', 'customer_no' => '1234567', 'people' => 3],
        ];

        $orders = [
            ['id' => 1, 'table_no' => '12番', 'name' => 'モモ串　しお', 'qty' => 3, 'time' => '19:05', 'status' => 'waiting'],
            ['id' => 2, 'table_no' => '5番', 'name' => 'ビール', 'qty' => 5, 'time' => '19:25', 'status' => 'served'],
            ['id' => 3, 'table_no' => '3番', 'name' => 'コークハイ', 'qty' => 1, 'time' => '19:40', 'status' => 'canceled'],
        ];

        $products = [
            ['id' => 1, 'name' => 'もも串　タレ', 'category' => '串', 'stock' => 30, 'price' => 200],
            ['id' => 2, 'name' => 'もも串　しお', 'category' => '串', 'stock' => 100, 'price' => 200],
            ['id' => 3, 'name' => 'ビール', 'category' => 'ドリンク', 'stock' => 200, 'price' => 200],
        ];

        $view = dirname(__DIR__) . '/Views/staff/dashboard.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }
}