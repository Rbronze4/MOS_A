<?php
declare(strict_types=1);

final class StaffController
{
    public function index(): void
    {
        $title = 'MOS 店員画面';
        $cssFile = '/MOS_A/public/assets/css/staff.css';
        $jsFile = '/MOS_A/public/assets/js/staff.js';

        $customers = [
            ['table_no' => '1番', 'customer_no' => '1234567', 'people' => 4],
            ['table_no' => '2番', 'customer_no' => '1234567', 'people' => 5],
            ['table_no' => '3番', 'customer_no' => '1234567', 'people' => 3],
            ['table_no' => '4番', 'customer_no' => '1234567', 'people' => 2],
            ['table_no' => '5番', 'customer_no' => '1234567', 'people' => 3],
            ['table_no' => '6番', 'customer_no' => '1234567', 'people' => 3],
            ['table_no' => '7番', 'customer_no' => '1234567', 'people' => 3],
            ['table_no' => '8番', 'customer_no' => '1234567', 'people' => 5],
            ['table_no' => '9番', 'customer_no' => '1234567', 'people' => 5],
            ['table_no' => '10番', 'customer_no' => '1234567', 'people' => 2],
        ];

        $orders = [
            ['id' => 1, 'table_no' => '12番', 'name' => 'モモ串　しお', 'qty' => 3, 'time' => '19:05', 'status' => 'waiting'],
            ['id' => 2, 'table_no' => '12番', 'name' => 'もも串　しお', 'qty' => 3, 'time' => '19:05', 'status' => 'waiting'],
            ['id' => 3, 'table_no' => '5番', 'name' => 'とりかわ　たれ', 'qty' => 2, 'time' => '19:05', 'status' => 'waiting'],
            ['id' => 4, 'table_no' => '5番', 'name' => 'ビール', 'qty' => 5, 'time' => '19:25', 'status' => 'served'],
            ['id' => 5, 'table_no' => '5番', 'name' => '枝豆', 'qty' => 3, 'time' => '19:25', 'status' => 'waiting'],
            ['id' => 6, 'table_no' => '5番', 'name' => 'チキン南蛮', 'qty' => 1, 'time' => '19:25', 'status' => 'waiting'],
            ['id' => 7, 'table_no' => '3番', 'name' => 'コークハイ', 'qty' => 1, 'time' => '19:40', 'status' => 'canceled'],
            ['id' => 8, 'table_no' => '3番', 'name' => 'ハイボール', 'qty' => 1, 'time' => '19:40', 'status' => 'waiting'],
            ['id' => 9, 'table_no' => '1番', 'name' => '釜めし', 'qty' => 2, 'time' => '19:40', 'status' => 'served'],
            ['id' => 10, 'table_no' => '1番', 'name' => '豚汁', 'qty' => 3, 'time' => '19:40', 'status' => 'waiting'],
        ];

        $products = [
            ['id' => 1, 'name' => 'もも串　タレ', 'category' => '串', 'stock' => 30, 'price' => 200],
            ['id' => 2, 'name' => 'もも串　しお', 'category' => '串', 'stock' => 100, 'price' => 200],
            ['id' => 3, 'name' => 'とりかわ　たれ', 'category' => '串', 'stock' => 300, 'price' => 200],
            ['id' => 4, 'name' => 'とりかわ　しお', 'category' => '串', 'stock' => 200, 'price' => 200],
            ['id' => 5, 'name' => 'ももあげ', 'category' => '揚げ物', 'stock' => 100, 'price' => 300],
            ['id' => 6, 'name' => 'ポテト', 'category' => '揚げ物', 'stock' => 300, 'price' => 300],
            ['id' => 7, 'name' => 'チキン南蛮', 'category' => '揚げ物', 'stock' => 200, 'price' => 500],
            ['id' => 8, 'name' => '軟骨の唐揚げ', 'category' => '揚げ物', 'stock' => 100, 'price' => 300],
            ['id' => 9, 'name' => '手羽先の唐揚げ', 'category' => '揚げ物', 'stock' => 200, 'price' => 300],
            ['id' => 10, 'name' => '揚餃子', 'category' => '揚げ物', 'stock' => 200, 'price' => 400],
        ];

        $view = dirname(__DIR__) . '/Views/staff/dashboard.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }
}