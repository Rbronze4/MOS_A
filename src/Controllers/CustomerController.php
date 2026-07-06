<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/MenuModel.php';
require_once dirname(__DIR__) . '/Models/CartModel.php';
require_once dirname(__DIR__) . '/Models/OrderModel.php';
require_once dirname(__DIR__) . '/Models/CustomerSessionModel.php';

/**
 * 客側画面のController。
 *
 * QR連携が本格化するまでは、customer_idをURLパラメータで受け取る。
 * プラン確定時にsessions / customer_plans / cartsを作成または再利用する。
 */
final class CustomerController
{
    private const TEST_CUSTOMER_ID = 1000004;

    public function index(): void
    {
        $cartFlash = $_SESSION['cart_flash'] ?? null;
        unset($_SESSION['cart_flash']);

        // TODO: QRコード連携が完成したら、QRから渡されたcustomer_idを必須にする
        $customerId = $this->requestCustomerId();
        if ($customerId === null) {
            $customerId = self::TEST_CUSTOMER_ID;
        }

        $sessionId = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
        if ($sessionId === false || $sessionId === null || $sessionId < 1) {
            $sessionId = null;
        }

        $plans = $this->plans();
        $menuModel = new MenuModel();
        $sessionModel = new CustomerSessionModel();
        $cartModel = new CartModel();
        $orderModel = new OrderModel();
        $cartItems = [];
        $historyItems = [];
        $storeId = 'MH';
        $planTypeId = null;
        $peopleCount = 2;
        $activeCustomerPlan = null;
        $hasActiveCustomerPlan = false;

        try {
            if ($sessionId !== null) {
                $activeSession = $sessionModel->activeSession($sessionId);

                if ($activeSession === null) {
                    throw new RuntimeException('有効なセッションが見つかりません。');
                }

                if ((int)$activeSession['customer_id'] === $customerId) {
                    $customerId = (int)$activeSession['customer_id'];
                    $storeId = (string)$activeSession['store_id'];
                    $cartItems = $cartModel->cartItemsForSession($sessionId);
                    $activeCustomerPlan = $sessionModel->activeCustomerPlan($customerId);
                } else {
                    // URLに古いsession_idが残っている場合、別のテスト顧客IDを上書きしない。
                    $sessionId = null;
                    $customer = $sessionModel->findCustomer($customerId);

                    if ($customer !== null) {
                        $storeId = (string)$customer['store_id'];
                    }

                    $activeCustomerPlan = $sessionModel->activeCustomerPlan($customerId);
                }
            } else {
                $customer = $sessionModel->findCustomer($customerId);

                if ($customer !== null) {
                    $storeId = (string)$customer['store_id'];
                }

                $activeCustomerPlan = $sessionModel->activeCustomerPlan($customerId);
            }

            $currentCustomer = $sessionModel->findCustomer($customerId);
            if ($currentCustomer !== null) {
                $peopleCount = (int)$currentCustomer['people_count'];
            }

            $hasActiveCustomerPlan = $activeCustomerPlan !== null;
            $planTypeId = $activeCustomerPlan === null ? null : (int)$activeCustomerPlan['plan_type_id'];
            $historyItems = $orderModel->historyItemsForCustomer($customerId);
            $categories = $menuModel->categoriesForStore($storeId);
            $menus = $menuModel->menusForStore($storeId, $planTypeId);
        } catch (Throwable $exception) {
            error_log('[customer-menu] DB error: ' . $exception->getMessage());

            // DB未起動や未インポートでも画面全体を落とさず、商品なし表示にする。
            $categories = [];
            $menus = [];
            $cartItems = [];
            $historyItems = [];
            $activeCustomerPlan = null;
            $hasActiveCustomerPlan = false;
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

    public function startSession(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $customerId = $this->validatedCustomerId();
        $tableNumber = $this->validatedTableNumber();
        $sessionModel = new CustomerSessionModel();
        $activeCustomerPlan = $sessionModel->activeCustomerPlan($customerId);
        $planKey = $activeCustomerPlan === null ? $this->validatedPlanKey() : null;
        $planMinutes = $this->validatedPlanMinutes();

        try {
            $cartModel = new CartModel();
            $menuModel = new MenuModel();
            $result = $sessionModel->start($customerId, $tableNumber, $planKey, $planMinutes);
            $activeCustomerPlan = $sessionModel->activeCustomerPlan((int)$result['customer_id']);
            $planTypeId = $activeCustomerPlan === null ? null : (int)$activeCustomerPlan['plan_type_id'];
            $customer = $sessionModel->findCustomer((int)$result['customer_id']);
            $peopleCount = $customer === null ? 2 : (int)$customer['people_count'];

            $this->json([
                'ok' => true,
                'message' => '利用セッションを開始しました。',
                'customer_id' => $result['customer_id'],
                'store_id' => $result['store_id'],
                'session_id' => $result['session_id'],
                'cart_id' => $result['cart_id'],
                'plan_id' => $result['plan_id'],
                'active_customer_plan' => $activeCustomerPlan,
                'people_count' => $peopleCount,
                'cart_items' => $cartModel->cartItemsForSession((int)$result['session_id']),
                'categories' => $menuModel->categoriesForStore((string)$result['store_id']),
                'menus' => $menuModel->menusForStore((string)$result['store_id'], $planTypeId),
            ]);
        } catch (Throwable $exception) {
            error_log('[customer-session-start] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function addCart(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $session = $this->validatedActiveSession();
        $productId = $this->validatedProductId();
        $quantity = $this->validatedQuantity();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->addProduct(
                (int)$session['session_id'],
                (string)$session['store_id'],
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
                'message' => 'カート追加に失敗しました。セッション、既存カート、商品販売設定を確認してください。',
            ], 500);
        }
    }

    public function updateCart(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $session = $this->validatedActiveSession();
        $productId = $this->validatedProductId();
        $quantity = $this->validatedQuantity();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->updateProductQuantity((int)$session['session_id'], $productId, $quantity);

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

        $session = $this->validatedActiveSession();
        $productId = $this->validatedProductId();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->deleteProduct((int)$session['session_id'], $productId);

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

    public function submitOrder(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $session = $this->validatedActiveSession();

        try {
            $orderModel = new OrderModel();
            $result = $orderModel->submitCart((int)$session['session_id'], (string)$session['store_id']);
            $historyItems = $orderModel->historyItemsForCustomer((int)$result['customer_id']);

            $this->json([
                'ok' => true,
                'message' => '注文を送信しました。',
                'order_id' => $result['order_id'],
                'total_quantity' => $result['total_quantity'],
                'total_amount' => $result['total_amount'],
                'cart_items' => $result['cart_items'],
                'history_items' => $historyItems,
            ]);
        } catch (Throwable $exception) {
            error_log('[customer-order-submit] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    private function validatedActiveSession(): array
    {
        $sessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);

        if ($sessionId === false || $sessionId === null || $sessionId < 1) {
            $this->json([
                'ok' => false,
                'message' => 'セッション情報がありません。卓番号とプランを選択してください。',
            ], 422);
        }

        $sessionModel = new CustomerSessionModel();
        $session = $sessionModel->activeSession((int)$sessionId);

        if ($session === null) {
            $this->json([
                'ok' => false,
                'message' => '有効なセッションが見つかりません。',
            ], 422);
        }

        return $session;
    }

    private function validatedCustomerId(): int
    {
        $customerId = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);

        if ($customerId === false || $customerId === null || $customerId < 1) {
            $this->json([
                'ok' => false,
                'message' => '顧客IDが正しくありません。',
            ], 422);
        }

        return (int)$customerId;
    }

    private function requestCustomerId(): ?int
    {
        $customerId = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);

        if ($customerId === false || $customerId === null || $customerId < 1) {
            $customerId = filter_input(INPUT_GET, 'customerId', FILTER_VALIDATE_INT);
        }

        if ($customerId === false || $customerId === null || $customerId < 1) {
            return null;
        }

        return (int)$customerId;
    }

    private function validatedTableNumber(): string
    {
        $tableNumber = trim((string)($_POST['table_number'] ?? ''));

        if (!preg_match('/^\d{1,3}$/', $tableNumber)) {
            $this->json([
                'ok' => false,
                'message' => '卓番号を数字で入力してください。',
            ], 422);
        }

        return $tableNumber;
    }

    private function validatedPlanKey(): string
    {
        $planKey = trim((string)($_POST['plan_key'] ?? ''));

        if (!in_array($planKey, ['standard', 'premium', 'single'], true)) {
            $this->json([
                'ok' => false,
                'message' => 'プランが正しくありません。',
            ], 422);
        }

        return $planKey;
    }

    private function validatedPlanMinutes(): ?int
    {
        $minutes = filter_input(INPUT_POST, 'plan_minutes', FILTER_VALIDATE_INT);

        if ($minutes === false || $minutes === null || $minutes < 1) {
            return null;
        }

        return (int)$minutes;
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
     * 画面表示用のプラン定義。
     *
     * 実DBのplansとは、プラン確定時にplan_key + plan_minutesから紐づける。
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
