<?php
declare(strict_types=1);

/**
 * mocks 共通ダミーデータ
 * ------------------------------------------------------------------
 * 本体のModel（MenuModel / StaffOrderModel / StaffProductModel など）が
 * DBから取得して返す配列と同じ構造のダミーデータ。
 * mocksはDBに接続しないため、ここで同じ形のデータを用意する。
 *
 * レイアウト確認専用。ここを編集しても本体には影響しません。
 * 本体のModelが返すキーを変更した場合は、ここも合わせて更新すること。
 */

/**
 * 客側プラン（本体: CustomerController::plans()）
 *
 * 価格は持たない。店舗・制限時間ごとにDBのplansで変わるため、
 * 単価は mock_plan_unit_prices() 側で持ち、JSが「単価×人数」で合計を計算する。
 */
function mock_plans(): array
{
    return [
        [
            'id' => 'standard',
            'name' => 'スタンダードプラン',
            'details' => [
                '飲み放題20品',
            ],
        ],
        [
            'id' => 'premium',
            'name' => 'プレミアムプラン',
            'details' => [
                '飲み放題40品',
            ],
        ],
        [
            'id' => 'single',
            'name' => '飲み放題なし',
            'details' => [
                '単品注文のみ',
                '飲み放題は付きません',
                '※ドリンクは個別注文・精算となります',
            ],
        ],
    ];
}

/**
 * 店舗別・制限時間別のプラン単価（本体: PlanModel::unitPricesForStore()）
 *
 * DBのplansから取得される単価と同じ形。JSはこの単価に人数を掛けて合計金額を出す。
 * 実DB（MH店舗）と同じ値にしてあるので、mocksでも本番同様の金額が表示される。
 */
function mock_plan_unit_prices(): array
{
    return [
        'standard' => [120 => 2200, 180 => 3000],
        'premium' => [120 => 3200, 180 => 4200],
    ];
}

/**
 * 客側カテゴリ（本体: MenuModel::categoriesForStore()）
 *
 * 本体はid/nameを持つ連想配列を返す（旧mockは文字列の配列だった）。
 */
function mock_categories(): array
{
    return [
        ['id' => 1, 'name' => 'ドリンク'],
        ['id' => 2, 'name' => '串'],
        ['id' => 3, 'name' => '一品'],
        ['id' => 4, 'name' => '揚げ物'],
        ['id' => 5, 'name' => 'ご飯もの'],
    ];
}

/**
 * 客側メニュー（本体: MenuModel::menusForStore()）
 *
 * plan_applied_flag=1 は飲み放題プランの対象商品。
 * その場合 display_price は0になり、画面では「プラン対象」として0円表示される。
 */
function mock_menus(): array
{
    return [
        ['id' => 1, 'category_id' => '1', 'category' => 'ドリンク', 'name' => 'ビール', 'price' => 200, 'display_price' => 0, 'plan_applied_flag' => 1, 'image_path' => '/MOS_A/public/assets/images/menu/beer.png'],
        ['id' => 2, 'category_id' => '1', 'category' => 'ドリンク', 'name' => 'ハイボール', 'price' => 200, 'display_price' => 0, 'plan_applied_flag' => 1, 'image_path' => '/MOS_A/public/assets/images/menu/highball.png'],
        ['id' => 3, 'category_id' => '1', 'category' => 'ドリンク', 'name' => '焼酎', 'price' => 200, 'display_price' => 0, 'plan_applied_flag' => 1, 'image_path' => '/MOS_A/public/assets/images/menu/shochu.png'],
        ['id' => 4, 'category_id' => '1', 'category' => 'ドリンク', 'name' => 'レモンサワー', 'price' => 200, 'display_price' => 200, 'plan_applied_flag' => 0, 'image_path' => '/MOS_A/public/assets/images/menu/lemonsour.png'],
        ['id' => 5, 'category_id' => '1', 'category' => 'ドリンク', 'name' => 'カクテル', 'price' => 200, 'display_price' => 200, 'plan_applied_flag' => 0, 'image_path' => '/MOS_A/public/assets/images/menu/cocktail.png'],
        ['id' => 6, 'category_id' => '1', 'category' => 'ドリンク', 'name' => 'ウーロン茶', 'price' => 100, 'display_price' => 100, 'plan_applied_flag' => 0, 'image_path' => '/MOS_A/public/assets/images/menu/oolongtea.png'],
        ['id' => 7, 'category_id' => '2', 'category' => '串', 'name' => 'もも串しお', 'price' => 100, 'display_price' => 100, 'plan_applied_flag' => 0, 'image_path' => '/MOS_A/public/assets/images/menu/Chicken_thigh.png'],
        ['id' => 8, 'category_id' => '2', 'category' => '串', 'name' => '鳥皮たれ', 'price' => 100, 'display_price' => 100, 'plan_applied_flag' => 0, 'image_path' => '/MOS_A/public/assets/images/menu/Chicken_skin.png'],
        ['id' => 9, 'category_id' => '5', 'category' => 'ご飯もの', 'name' => '白ごはん', 'price' => 150, 'display_price' => 150, 'plan_applied_flag' => 0, 'image_path' => '/MOS_A/public/assets/images/menu/rice.png'],
        ['id' => 10, 'category_id' => '3', 'category' => '一品', 'name' => '枝豆', 'price' => 250, 'display_price' => 250, 'plan_applied_flag' => 0, 'image_path' => '/MOS_A/public/assets/images/menu/edamame.png'],
        ['id' => 11, 'category_id' => '4', 'category' => '揚げ物', 'name' => '唐揚げ', 'price' => 400, 'display_price' => 400, 'plan_applied_flag' => 0, 'image_path' => '/MOS_A/public/assets/images/menu/karage.png'],
    ];
}

/**
 * スタッフ側 顧客一覧（本体: StaffCustomerModel::customersForStore()）
 *
 * billing_status は tinyint（1:受付中 2:会計済み 4:未収金 8:会計中）。
 */
function mock_customers(): array
{
    return [
        ['customer_id' => 1000001, 'customer_no' => '1000001', 'table_no' => '1', 'people' => 4, 'billing_status' => 1],
        ['customer_id' => 1000002, 'customer_no' => '1000002', 'table_no' => '2', 'people' => 5, 'billing_status' => 1],
        ['customer_id' => 1000003, 'customer_no' => '1000003', 'table_no' => 'なし', 'people' => 3, 'billing_status' => 2],
    ];
}

/**
 * スタッフ側 注文一覧（本体: StaffOrderModel::ordersForStore()）
 *
 * 1行 = order_details の1明細。idには order_detail_id が入る（JSの操作対象）。
 * status は waiting（未提供）/ served（提供済み）/ canceled（取消済み）。
 */
function mock_orders(): array
{
    return [
        [
            'id' => 1,
            'order_detail_id' => 1,
            'order_id' => 1,
            'product_id' => 7,
            'customer_id' => 1000001,
            'session_id' => 1,
            'store_id' => 'MH',
            'table_no' => '12番',
            'name' => 'もも串 塩',
            'qty' => 3,
            'servedQty' => 0,
            'time' => '19:05',
            'ordered_at_label' => '2026-07-14 19:05',
            'status' => 'waiting',
            'status_label' => '未提供',
            'detail_status' => 'ORDERED',
            'price' => 200,
            'plan_applied_flag' => 0,
        ],
        [
            'id' => 2,
            'order_detail_id' => 2,
            'order_id' => 2,
            'product_id' => 1,
            'customer_id' => 1000002,
            'session_id' => 2,
            'store_id' => 'MH',
            'table_no' => '5番',
            'name' => 'ビール',
            'qty' => 5,
            'servedQty' => 5,
            'time' => '19:25',
            'ordered_at_label' => '2026-07-14 19:25',
            'status' => 'served',
            'status_label' => '提供済み',
            'detail_status' => 'PROVIDED',
            'price' => 0,
            'plan_applied_flag' => 1,
        ],
        [
            'id' => 3,
            'order_detail_id' => 3,
            'order_id' => 3,
            'product_id' => 5,
            'customer_id' => 1000003,
            'session_id' => 3,
            'store_id' => 'MH',
            'table_no' => '3番',
            'name' => 'カクテル',
            'qty' => 1,
            'servedQty' => 0,
            'time' => '19:40',
            'ordered_at_label' => '2026-07-14 19:40',
            'status' => 'canceled',
            'status_label' => '取消済み',
            'detail_status' => 'CANCELLED',
            'price' => 200,
            'plan_applied_flag' => 0,
        ],
    ];
}

/**
 * スタッフ側 商品一覧（本体: StaffProductModel::productsForStore()）
 *
 * sale_status は ON_SALE / SOLD_OUT。在庫(stock)はDBに存在しない。
 */
function mock_products(): array
{
    return [
        [
            'id' => 7,
            'product_id' => 7,
            'name' => 'もも串 タレ',
            'product_name' => 'もも串 タレ',
            'category_id' => 2,
            'category' => '串',
            'category_name' => '串',
            'price' => 200,
            'tax_rate' => 10.0,
            'tax_included_price' => 220,
            'product_sale_status' => 'ON_SALE',
            'store_sale_status' => 'ON_SALE',
            'sale_status' => 'ON_SALE',
            'image_path' => '',
            'display_order' => 1,
            'has_options' => false,
            'option_groups' => [],
        ],
        [
            'id' => 8,
            'product_id' => 8,
            'name' => 'もも串 塩',
            'product_name' => 'もも串 塩',
            'category_id' => 2,
            'category' => '串',
            'category_name' => '串',
            'price' => 200,
            'tax_rate' => 10.0,
            'tax_included_price' => 220,
            'product_sale_status' => 'ON_SALE',
            'store_sale_status' => 'ON_SALE',
            'sale_status' => 'ON_SALE',
            'image_path' => '',
            'display_order' => 2,
            'has_options' => false,
            'option_groups' => [],
        ],
        [
            'id' => 1,
            'product_id' => 1,
            'name' => 'ビール',
            'product_name' => 'ビール',
            'category_id' => 1,
            'category' => 'ドリンク',
            'category_name' => 'ドリンク',
            'price' => 200,
            'tax_rate' => 10.0,
            'tax_included_price' => 220,
            'product_sale_status' => 'ON_SALE',
            'store_sale_status' => 'SOLD_OUT',
            'sale_status' => 'SOLD_OUT',
            'image_path' => '',
            'display_order' => 3,
            'has_options' => false,
            'option_groups' => [],
        ],
    ];
}

/**
 * スタッフ側 商品カテゴリ（本体: StaffProductModel::categories()）
 * 商品追加・編集モーダルのカテゴリ選択で使う。
 */
function mock_product_categories(): array
{
    return [
        ['category_id' => 1, 'category_name' => 'ドリンク'],
        ['category_id' => 2, 'category_name' => '串'],
        ['category_id' => 3, 'category_name' => '一品'],
        ['category_id' => 4, 'category_name' => '揚げ物'],
        ['category_id' => 5, 'category_name' => 'ご飯もの'],
    ];
}
