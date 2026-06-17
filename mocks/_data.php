<?php
declare(strict_types=1);

/**
 * mocks 共通ダミーデータ
 * ------------------------------------------------------------------
 * 本体コントローラー(StaffController / CustomerController)の
 * ダミーデータをコピーしたもの。
 * レイアウト確認専用。ここを編集しても本体には影響しません。
 */

/** 客側プラン */
function mock_plans(): array
{
    return [
        [
            'id' => 'standard',
            'name' => 'スタンダードプラン',
            'price' => 5000,
            'description' => '飲み放題20品 / ¥2,500×2人',
            'details' => ['飲み放題20品', '¥2,500/人', '大人2人'],
        ],
        [
            'id' => 'premium',
            'name' => 'プレミアムプラン',
            'price' => 6000,
            'description' => '飲み放題40品 / ¥3,000×2人',
            'details' => ['飲み放題40品', '¥3,000/人', '大人2人'],
        ],
        [
            'id' => 'single',
            'name' => '飲み放題なし',
            'price' => 0,
            'description' => '単品注文のみ',
            'details' => ['単品注文のみ', '飲み放題は付きません', '※ドリンクは個別注文・精算となります'],
        ],
    ];
}

/** 客側カテゴリ */
function mock_categories(): array
{
    return ['ドリンク', '串', '一品', '揚げ物', 'ご飯もの', '期間限定', '店舗限定'];
}

/** 客側メニュー */
function mock_menus(): array
{
    return [
        ['id' => 1, 'category' => 'ドリンク', 'name' => 'ビール', 'price' => 200, 'image_path' => '/MOS_A/public/assets/images/menu/beer.png'],
        ['id' => 2, 'category' => 'ドリンク', 'name' => 'ハイボール', 'price' => 200, 'image_path' => '/MOS_A/public/assets/images/menu/highball.png'],
        ['id' => 3, 'category' => 'ドリンク', 'name' => '焼酎', 'price' => 200, 'image_path' => '/MOS_A/public/assets/images/menu/shochu.png'],
        ['id' => 4, 'category' => 'ドリンク', 'name' => 'レモンサワー', 'price' => 200, 'image_path' => '/MOS_A/public/assets/images/menu/lemonsour.png'],
        ['id' => 5, 'category' => 'ドリンク', 'name' => 'カクテル', 'price' => 200, 'image_path' => '/MOS_A/public/assets/images/menu/cocktail.png'],
        ['id' => 6, 'category' => 'ドリンク', 'name' => 'ウーロン茶', 'price' => 100, 'image_path' => '/MOS_A/public/assets/images/menu/oolongtea.png'],
        ['id' => 7, 'category' => '串', 'name' => 'もも串しお', 'price' => 100, 'image_path' => '/MOS_A/public/assets/images/menu/Chicken_thigh.png'],
        ['id' => 8, 'category' => '串', 'name' => '鳥皮たれ', 'price' => 100, 'image_path' => '/MOS_A/public/assets/images/menu/Chicken_skin.png'],
        ['id' => 9, 'category' => 'ご飯もの', 'name' => '白ごはん', 'price' => 150, 'image_path' => '/MOS_A/public/assets/images/menu/rice.png'],
        ['id' => 10, 'category' => '一品', 'name' => '枝豆', 'price' => 250, 'image_path' => '/MOS_A/public/assets/images/menu/edamame.png'],
        ['id' => 11, 'category' => '揚げ物', 'name' => '唐揚げ', 'price' => 400, 'image_path' => '/MOS_A/public/assets/images/menu/karage.png'],
    ];
}

/** スタッフ側 顧客一覧 */
function mock_customers(): array
{
    return [
        ['table_no' => '1番', 'customer_no' => '1234567', 'people' => 4],
        ['table_no' => '2番', 'customer_no' => '1234567', 'people' => 5],
        ['table_no' => '3番', 'customer_no' => '1234567', 'people' => 3],
    ];
}

/** スタッフ側 注文一覧 */
function mock_orders(): array
{
    return [
        ['id' => 1, 'table_no' => '12番', 'name' => 'もも串 塩', 'qty' => 3, 'time' => '19:05', 'status' => 'waiting'],
        ['id' => 2, 'table_no' => '5番', 'name' => 'ビール', 'qty' => 5, 'time' => '19:25', 'status' => 'served'],
        ['id' => 3, 'table_no' => '3番', 'name' => 'コークハイ', 'qty' => 1, 'time' => '19:40', 'status' => 'canceled'],
    ];
}

/** スタッフ側 商品一覧 */
function mock_products(): array
{
    return [
        ['id' => 1, 'name' => 'もも串 タレ', 'category' => '串', 'stock' => 30, 'price' => 200],
        ['id' => 2, 'name' => 'もも串 塩', 'category' => '串', 'stock' => 100, 'price' => 200],
        ['id' => 3, 'name' => 'ビール', 'category' => 'ドリンク', 'stock' => 200, 'price' => 200],
    ];
}
