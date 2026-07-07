<?php
declare(strict_types=1);

/**
 * 客側画面のコントローラー。
 * 現状はDB未接続のため、プラン($plans)・カテゴリ($categories)・メニュー($menus)を
 * ハードコードで用意し、共通レイアウト(app.php)経由で客側ビュー(customer_app.php)を描画する。
 * これらのデータはレイアウトを通じて window.MOS_DATA としてJSへ渡される。
 *
 * メソッド:
 *   index() … 客側トップ（卓番号入力〜プラン選択〜メニュー）を表示
 */
final class CustomerController
{
    public function index(): void
    {
        $plans = [
            [
                'id' => 'standard',
                'name' => 'スタンダードプラン',
                'price' => 5000,
                'description' => '飲み放題20品 / ¥2,500×2人',
                'details' => [
                    '飲み放題20品',
                    '¥2,500/人',
                    '大人2人',
                ],
            ],
            [
                'id' => 'premium',
                'name' => 'プレミアムプラン',
                'price' => 6000,
                'description' => '飲み放題40品 / ¥3,000×2人',
                'details' => [
                    '飲み放題40品',
                    '¥3,000/人',
                    '大人2人',
                ],
            ],
            [
                'id' => 'single',
                'name' => '飲み放題なし',
                'price' => 0,
                'description' => '単品注文のみ',
                'details' => [
                    '単品注文のみ',
                    '飲み放題は付きません',
                    '※ドリンクは個別注文・精算となります',
                ],
            ],
        ];

        $categories = [
            'ドリンク',
            '串',
            '一品',
            '揚げ物',
            'ご飯もの',
            '期間限定',
            '店舗限定'
        ];

        $menus = [
            [
                'id' => 1,
                'category' => 'ドリンク',
                'name' => 'ビール',
                'price' => 200,
                'image_path' => '/MOS_A/public/assets/images/menu/beer.png',
            ],
            [
                'id' => 2,
                'category' => 'ドリンク',
                'name' => 'ハイボール',
                'price' => 200,
                'image_path' => '/MOS_A/public/assets/images/menu/highball.png',
            ],
            [
                'id' => 3,
                'category' => 'ドリンク',
                'name' => '焼酎',
                'price' => 200,
                'image_path' => '/MOS_A/public/assets/images/menu/shochu.png',
            ],
            [
                'id' => 4,
                'category' => 'ドリンク',
                'name' => 'レモンサワー',
                'price' => 200,
                'image_path' => '/MOS_A/public/assets/images/menu/lemonsour.png',
            ],
            [
                'id' => 5,
                'category' => 'ドリンク',
                'name' => 'カクテル',
                'price' => 200,
                'image_path' => '/MOS_A/public/assets/images/menu/cocktail.png',
            ],
            [
                'id' => 6,
                'category' => 'ドリンク',
                'name' => 'ウーロン茶',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/oolongtea.png',
            ],
            [
                'id' => 7,
                'category' => '串',
                'name' => 'もも串しお',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/Chicken_thigh.png',
            ],
            [
                'id' => 8,
                'category' => '串',
                'name' => '鳥皮たれ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/Chicken_skin.png',
            ],
            [
                'id' => 9,
                'category' => 'ご飯もの',
                'name' => '白ごはん',
                'price' => 150,
                'image_path' => '/MOS_A/public/assets/images/menu/rice.png',
            ],
            [
                'id' => 10,
                'category' => '一品',
                'name' => '枝豆',
                'price' => 250,
                'image_path' => '/MOS_A/public/assets/images/menu/edamame.png',
            ],
            [
                'id' => 11,
                'category' => '揚げ物',
                'name' => '唐揚げ',
                'price' => 400,
                'image_path' => '/MOS_A/public/assets/images/menu/karage.png',
            ],
            [/** 商品の追加　画像は後から付きます　付いてなかったら教えてください */
                'id' => 12,
                'category' => '串',
                'name' => 'ももタレ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/momo_tare.png',
            ],
            [
                'id' => 13,
                'category' => '串',
                'name' => 'つくねチーズ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/tsukune_cheese.png',
            ],
            [
                'id' => 14,
                'category' => '串',
                'name' => 'ねぎま',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/negima.png',
            ],
            [
                'id' => 15,
                'category' => '串',
                'name' => 'チーズもち',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/cheese_mochi.png',
            ],
            [
                'id' => 16,
                'category' => '一品',
                'name' => 'たこわさ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/takowasa.png',
            ],
            [
                'id' => 17,
                'category' => '一品',
                'name' => 'やみつきキャベツ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/yamitsuki_cabbage.png',
            ],
            [
                'id' => 18,
                'category' => '一品',
                'name' => '鉄板やまいも',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/teppan_yakiimo.png',
            ],
            [
                'id' => 19,
                'category' => '一品',
                'name' => '明太ポテサラ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/mentai_potesara.png',
            ],
            [
                'id' => 20,
                'category' => '一品',
                'name' => 'チャンジャ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/changja.png',
            ],
            [
                'id' => 21,
                'category' => '揚げ物',
                'name' => 'ポテトフライ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/potato_fry.png',
            ],
            [
                'id' => 22,
                'category' => '揚げ物',
                'name' => '揚げ餃子',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/age_gyoza.png',
            ],
            [
                'id' => 23,
                'category' => '揚げ物',
                'name' => 'チキン南蛮',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/chicken_nanban.png',
            ],
            [
                'id' => 24,
                'category' => '揚げ物',
                'name' => '手羽先の唐揚げ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/tebasaki_karaage.png',
            ],
            [
                'id' => 25,
                'category' => '揚げ物',
                'name' => '軟骨の唐揚げ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/nankotsu_karaage.png',
            ],
            [
                'id' => 26,
                'category' => 'ご飯もの',
                'name' => '焼き鳥丼',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/yakitori_don.png',
            ],
            [
                'id' => 27,
                'category' => 'ご飯もの',
                'name' => '鳥雑炊',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/tori_zosui.png',
            ],
            [
                'id' => 28,
                'category' => 'ご飯もの',
                'name' => 'ソースかつ丼',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/sauce_katsu_don.png',
            ],
            [
                'id' => 29,
                'category' => 'ご飯もの',
                'name' => 'ご飯セット',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/gohan_set.png',
            ],
            [
                'id' => 30,
                'category' => 'ご飯もの',
                'name' => '釜めし',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/kamameshi.png',
            ],
            [
                'id' => 31,
                'category' => 'ご飯もの',
                'name' => 'からマヨ丼',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/kara_mayo_don.png',
            ],
            [
                'id' => 32,
                'category' => '期間限定',
                'name' => '豚汁',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/tonjiru.png',
            ],
            [
                'id' => 33,
                'category' => '期間限定',
                'name' => 'カニ雑炊',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/kani_zosui.png',
            ],
            [
                'id' => 34,
                'category' => '期間限定',
                'name' => 'せせりのバター炒め',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/seseri_butter.png',
            ],
            [
                'id' => 35,
                'category' => '店舗限定',
                'name' => 'アヒージョ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/ajillo.png',
            ],
            [
                'id' => 36,
                'category' => '店舗限定',
                'name' => 'ベーコンステーキ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/bacon_steak.png',
            ],
            [
                'id' => 37,
                'category' => '店舗限定',
                'name' => 'ひとくちチョコケーキ',
                'price' => 100,
                'image_path' => '/MOS_A/public/assets/images/menu/chocolate_cake.png',
            ],
        ];

        $title = 'MOS 客側画面';
        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css',
            '/MOS_A/public/assets/css/customer/base.css',
            '/MOS_A/public/assets/css/customer/plans.css',
            '/MOS_A/public/assets/css/customer/menu.css',
            '/MOS_A/public/assets/css/customer/product-cart-history.css',
            '/MOS_A/public/assets/css/customer/overlays.css',
        ];
        $jsFiles = [
            '/MOS_A/public/assets/js/customer/modules/plans.js',
            '/MOS_A/public/assets/js/customer/modules/menu.js',
            '/MOS_A/public/assets/js/customer/modules/cart-history.js',
            '/MOS_A/public/assets/js/customer/app.js',
        ];
        $view = dirname(__DIR__) . '/Views/customer/customer_app.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }
}
