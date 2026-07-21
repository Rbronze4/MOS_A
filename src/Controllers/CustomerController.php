<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/MenuModel.php';
require_once dirname(__DIR__) . '/Models/CartModel.php';
require_once dirname(__DIR__) . '/Models/OrderModel.php';
require_once dirname(__DIR__) . '/Models/CustomerSessionModel.php';
require_once dirname(__DIR__) . '/Models/PlanModel.php';

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
        $planModel = new PlanModel();
        $cartItems = [];
        $historyItems = [];
        $storeId = 'MH';
        $planTypeId = null;
        $peopleCount = 2;
        $activeCustomerPlan = null;
        $hasActiveCustomerPlan = false;

        // 店舗別・制限時間別のプラン単価（税抜）。店舗が確定してからDBで取得する。
        $planUnitPrices = [];

        // プラン単価は税抜のため、客側の表示は必ずこの税率で税込にする。
        // レジもAPIのtaxRateで税を上乗せするので、同じ税率を使わないと請求額とずれる。
        $planTaxRate = PlanModel::COURSE_TAX_RATE;

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
            $categories = $menuModel->categoriesForStore($storeId, $hasActiveCustomerPlan);
            $menus = $menuModel->menusForStore($storeId, $planTypeId);

            // プラン確認モーダルの合計金額計算に使う。店舗が確定した後に取得する。
            $planUnitPrices = $planModel->unitPricesForStore($storeId);
        } catch (Throwable $exception) {
            error_log('[customer-menu] DB error: ' . $exception->getMessage());

            // DB未起動や未インポートでも画面全体を落とさず、商品なし表示にする。
            $categories = [];
            $menus = [];
            $cartItems = [];
            $historyItems = [];
            $activeCustomerPlan = null;
            $hasActiveCustomerPlan = false;
            $planUnitPrices = [];
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
                'categories' => $menuModel->categoriesForStore((string)$result['store_id'], $activeCustomerPlan !== null),
                'menus' => $menuModel->menusForStore((string)$result['store_id'], $planTypeId),
            ]);
        } catch (Throwable $exception) {
            error_log('[customer-session-start] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage($exception, 'セッションの開始に失敗しました。時間をおいて再度お試しください。'),
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
        $optionIds = $this->validatedOptionIds();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->addProduct(
                (int)$session['session_id'],
                (string)$session['store_id'],
                $productId,
                $quantity,
                $optionIds
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
                'message' => $this->safeMessage(
                    $exception,
                    'カート追加に失敗しました。時間をおいて再度お試しください。'
                ),
            ], $exception instanceof PDOException ? 500 : 422);
        }
    }

    public function updateCart(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $session = $this->validatedActiveSession();
        $cartDetailId = $this->validatedCartDetailId();
        $quantity = $this->validatedQuantity();
        $optionIds = $this->validatedOptionIds();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->updateCartDetail(
                (int)$session['session_id'],
                (string)$session['store_id'],
                $cartDetailId,
                $quantity,
                $optionIds
            );

            $this->json([
                'ok' => true,
                'message' => '数量を変更しました。',
                'cart_items' => $result['cart_items'],
            ]);
        } catch (Throwable $exception) {
            error_log('[customer-cart-update] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage(
                    $exception,
                    '数量変更に失敗しました。時間をおいて再度お試しください。'
                ),
            ], $exception instanceof PDOException ? 500 : 422);
        }
    }

    public function deleteCart(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/MOS_A/public/customer');
        }

        $session = $this->validatedActiveSession();
        $cartDetailId = $this->validatedCartDetailId();

        try {
            $cartModel = new CartModel();
            $result = $cartModel->deleteCartDetail((int)$session['session_id'], $cartDetailId);

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
                'message' => $this->safeMessage($exception, '注文の送信に失敗しました。時間をおいて再度お試しください。'),
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

    private function validatedCartDetailId(): int
    {
        $cartDetailId = filter_input(INPUT_POST, 'cart_detail_id', FILTER_VALIDATE_INT);

        if ($cartDetailId === false || $cartDetailId === null || $cartDetailId < 1) {
            $this->json([
                'ok' => false,
                'message' => 'カート明細IDが正しくありません。',
            ], 422);
        }

        return (int)$cartDetailId;
    }

    private function validatedQuantity(): int
    {
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($quantity === false || $quantity === null || $quantity < 1) {
            $quantity = 1;
        }

        return min(99, max(1, (int)$quantity));
    }

    /**
     * フロントから届く選択済みoption_id配列を整数の重複なしリストへ正規化する。
     * 商品への所属や必須条件は、改ざん対策としてModelでDBを参照して検証する。
     */
    private function validatedOptionIds(): array
    {
        $raw = $_POST['option_ids'] ?? '[]';
        $decoded = is_array($raw) ? $raw : json_decode((string)$raw, true);

        if (!is_array($decoded)) {
            $this->json([
                'ok' => false,
                'message' => 'オプションの指定が正しくありません。',
            ], 422);
        }

        $optionIds = [];

        foreach ($decoded as $value) {
            $optionId = filter_var($value, FILTER_VALIDATE_INT);

            if ($optionId === false || $optionId < 1) {
                $this->json([
                    'ok' => false,
                    'message' => 'オプションの指定が正しくありません。',
                ], 422);
            }

            $optionIds[] = (int)$optionId;
        }

        return array_values(array_unique($optionIds));
    }

    /**
     * 例外メッセージを客へ出してよい形に落とす。
     *
     * Model が投げる InvalidArgumentException / RuntimeException は
     * 「プランを選択してください。」のように客へ見せる前提の日本語メッセージなので
     * そのまま返す。それ以外（DB接続失敗・SQLエラー等）はSQL文やテーブル構造が
     * 混ざるため定型文に差し替える。詳細は error_log 側にだけ残す。
     *
     * PDOException は RuntimeException を継承しているため、必ず先に弾くこと。
     */
    private function safeMessage(Throwable $exception, string $fallback): string
    {
        if ($exception instanceof PDOException) {
            return $fallback;
        }

        if ($exception instanceof InvalidArgumentException || $exception instanceof RuntimeException) {
            return $exception->getMessage();
        }

        return $fallback;
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
     *
     * 価格は店舗・制限時間ごとにDBのplansで変わるため、ここには持たせない。
     * 単価はPlanModel::unitPricesForStore()で取得し、合計金額（単価×人数）と
     * 「¥○○/人」「大人○人」の表示はplans.jsが動的に組み立てる。
     * detailsには価格・人数に依存しない説明だけを置く。
     */
    private function plans(): array
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
}
