<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/MenuModel.php';

/**
 * 客側画面のコントローラー。
 *
 * 今回のDB結合対象は商品選択画面のカテゴリ・商品一覧だけ。
 * プラン選択、カート、注文確定、注文履歴は既存のフロント処理を維持する。
 */
final class CustomerController
{
    public function index(): void
    {
        $plans = $this->plans();

        // TODO: QRコード・セッション管理が完成したら、sessionsテーブルまたはセッション情報からstore_idを取得する
        // 今回は指示どおり緑橋本店(MH)の商品だけを表示する。
        $storeId = 'MH';

        $menuModel = new MenuModel();

        try {
            $categories = $menuModel->categoriesForStore($storeId);
            $menus = $menuModel->menusForStore($storeId);
        } catch (Throwable $exception) {
            error_log('[customer-menu] DB error: ' . $exception->getMessage());

            // DB未起動・未インポートなどでも画面全体は落とさない。
            // 商品欄は既存JSで「商品がありません」と表示される。
            $categories = [];
            $menus = [];
        }

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

    /**
     * プラン表示は今回のDB結合対象外なので、既存どおりController内の固定データを使う。
     */
    private function plans(): array
    {
        return [
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
    }
}
