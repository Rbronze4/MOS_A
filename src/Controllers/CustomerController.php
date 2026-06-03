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
            'ご飯もの',
            '串',
            '一品',
            '揚げ物',
            'ドリンク',
        ];

        $menus = [
            [
                'id' => 1,
                'category' => 'ドリンク',
                'name' => 'ビール',
                'price' => 200,
                'image' => '/MOS_A/public/assets/images/menu/img.jpg',
            ],
            [
                'id' => 2,
                'category' => 'ドリンク',
                'name' => 'ハイボール',
                'price' => 200,
                'image' => '/MOS_A/public/assets/images/menu/highball.png',
            ],
            [
                'id' => 3,
                'category' => 'ドリンク',
                'name' => '焼酎',
                'price' => 200,
                'image' => '/MOS_A/public/assets/images/menu/shochu.png',
            ],
            [
                'id' => 4,
                'category' => 'ドリンク',
                'name' => 'レモンサワー',
                'price' => 200,
                'image' => '/MOS_A/public/assets/images/menu/lemonsour.png',
            ],
            [
                'id' => 5,
                'category' => 'ドリンク',
                'name' => 'カクテル',
                'price' => 200,
                'image' => '/MOS_A/public/assets/images/menu/cocktail.png',
            ],
            [
                'id' => 6,
                'category' => 'ドリンク',
                'name' => 'ウーロン茶',
                'price' => 100,
                'image' => '/MOS_A/public/assets/images/menu/oolong tea.png',
            ],
            [
                'id' => 7,
                'category' => '串',
                'name' => 'もも串しお',
                'price' => 100,
                'image' => '/MOS_A/public/assets/images/menu/Chicken thigh skewers with salt.png',
            ],
            [
                'id' => 8,
                'category' => '串',
                'name' => '鳥皮たれ',
                'price' => 100,
                'image' => '/MOS_A/public/assets/images/menu/Chicken skin sauce.png',
            ],
            [
                'id' => 9,
                'category' => 'ご飯もの',
                'name' => '白ごはん',
                'price' => 150,
                'image' => '/MOS_A/public/assets/images/menu/rice.png',
            ],
            [
                'id' => 10,
                'category' => '一品',
                'name' => '枝豆',
                'price' => 250,
                'image' => '/MOS_A/public/assets/images/menu/edamame.png',
            ],
            [
                'id' => 11,
                'category' => '揚げ物',
                'name' => '唐揚げ',
                'price' => 400,
                'image' => '/MOS_A/public/assets/images/menu/karage.png',
            ],
        ];

        // 公開フォルダ内に画像ファイルが存在するか確認し、無ければ公開フォルダ内の最初の画像をフォールバックとして使用
        $publicBase = dirname(__DIR__, 2) . '/public/assets/images/menu';
        $fallbackFile = null;
        if (is_dir($publicBase)) {
            $files = scandir($publicBase);
            foreach ($files as $f) {
                if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg'])) {
                    $fallbackFile = $f;
                    break;
                }
            }
        }

        foreach ($menus as $i => $m) {
            $imageUrl = $m['image'] ?? '';
            $filename = basename($imageUrl);
            $fsPath = $publicBase . '/' . $filename;

            if ($filename !== '' && file_exists($fsPath)) {
                $menus[$i]['image'] = '/MOS_A/public/assets/images/menu/' . $filename;
            } elseif ($fallbackFile !== null) {
                $menus[$i]['image'] = '/MOS_A/public/assets/images/menu/' . $fallbackFile;
            } else {
                // ファイルが全く無ければ空文字にしてフロント側で扱う
                $menus[$i]['image'] = '';
            }
        }

        $title = 'MOS 客側画面';
        $cssFile = '/MOS_A/public/assets/css/customer.css';
        $jsFile = '/MOS_A/public/assets/js/customer.js';
        $view = dirname(__DIR__) . '/Views/customer/customer_app.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }
}