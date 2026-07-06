<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/MenuModel.php';
require_once dirname(__DIR__) . '/Models/CartModel.php';

/**
 * 客側画面のController。
 *
 * 商品一覧表示はDBから取得する。
 * カート操作は、現在はテスト用 session_id=1 / store_id=MH を固定で使用する。
 */
final class CustomerController
{
    private const TEST_STORE_ID = 'MH';
    private const TEST_SESSION_ID = 1;

    public function index(): void
    {
        $cartFlash = $_SESSION['cart_flash'] ?? null;
        unset($_SESSION['cart_flash']);

        $plans = $this->plans();

        // TODO: QRコード・セッション管理が完成したら、sessionsテーブルまたはセッション情報からstore_idを取得する
        $storeId = self::TEST_STORE_ID;

        // TODO: QRコード・卓番号入力・プラン選択が完成したら、実際のsessions.session_idを使用する
        // DB確認済みのテスト用データ: sessions.session_id = 1 / carts.cart_id = 1
        $sessionId = self::TEST_SESSION_ID;

        $menuModel = new MenuModel();
        $cartModel = new CartModel();
        $cartItems = [];

        try {
            $categories = $menuModel->categoriesForStore($storeId);
            $menus = $menuModel->menusForStore($storeId);
            $cartItems = $cartModel->cartItemsForSession($sessionId);
        } catch (Throwable $exception) {
            error_log('[customer-menu] DB error: ' . $exception->getMessage());

            // DB未起動や未インポートでも画面全体を落とさず、商品なし表示にする。
            $categories = [];
            $menus = [];
            $cartItems = [];
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

    public function addCart(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $productId = $this->validatedProductId();
        $quantity = $this->validatedQuantity();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->addProduct(
                self::TEST_SESSION_ID,
                self::TEST_STORE_ID,
                $productId,
                $quantity
            );
            $message = $result['product_name'] . 'をカートに追加しました。';

            $this->json([
                'ok' => true,
                'message' => $message,
                'cart_items' => $result['cart_items'],
            ]);
        } catch (Throwable $exception) {
            error_log('[customer-cart-add] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => 'カート追加に失敗しました。テスト用session_id=1、既存カート、商品販売設定を確認してください。',
            ], 500);
        }
    }

    public function updateCart(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $productId = $this->validatedProductId();
        $quantity = $this->validatedQuantity();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->updateProductQuantity(self::TEST_SESSION_ID, $productId, $quantity);

            $this->json([
                'ok' => true,
                'message' => '数量を変更しました。',
                'cart_items' => $result['cart_items'],
            ]);
        } catch (Throwable $exception) {
            error_log('[customer-cart-update] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => '数量変更に失敗しました。カート内容を確認してください。',
            ], 500);
        }
    }

    public function deleteCart(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $productId = $this->validatedProductId();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->deleteProduct(self::TEST_SESSION_ID, $productId);

            $this->json([
                'ok' => true,
                'message' => '商品を削除しました。',
                'cart_items' => $result['cart_items'],
            ]);
        } catch (Throwable $exception) {
            error_log('[customer-cart-delete] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => '削除に失敗しました。カート内容を確認してください。',
            ], 500);
        }
    }

    private function validatedProductId(): int
    {
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

        if ($productId === false || $productId === null || $productId < 1) {
            $this->json([
                'ok' => false,
                'message' => '商品IDが正しくありません。',
            ], 422);
        }

        return (int)$productId;
    }

    private function validatedQuantity(): int
    {
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($quantity === false || $quantity === null || $quantity < 1) {
            $quantity = 1;
        }

        return min(99, max(1, (int)$quantity));
    }

    private function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    /**
     * プラン表示は今回のDB結合・カート操作対象外のため、既存どおりController内の固定データを使う。
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
