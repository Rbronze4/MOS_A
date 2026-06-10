<?php
declare(strict_types=1);

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
