<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';
require_once dirname(__DIR__) . '/Models/StaffCustomerModel.php';
require_once dirname(__DIR__) . '/Models/StaffOrderModel.php';
require_once dirname(__DIR__) . '/Models/StaffProductModel.php';
require_once dirname(__DIR__) . '/Models/MenuModel.php';
require_once dirname(__DIR__) . '/Models/CustomerSessionModel.php';
require_once dirname(__DIR__) . '/Services/StaffOrderEntryService.php';

/**
 * スタッフ側画面のコントローラー。
 *
 * ログイン認証だけDBを使用し、既存の注文・商品などの画面データは現状のモックを維持する。
 * ログイン後はセッションに店舗情報を保持し、スタッフホームへ遷移する。
 */
final class StaffController
{
    private const ROLE_STAFF = 'STAFF';

    public function login(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/MOS_A/public/staff');
        }

        $this->renderLogin();
    }

    public function authenticate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/MOS_A/public/staff/login');
        }

        $storeId = trim((string)($_POST['store_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        try {
            $stores = $this->fetchActiveStores();
        } catch (Throwable $exception) {
            error_log('[staff-login] Store fetch error: ' . $exception->getMessage());

            $this->renderLogin(['店舗情報の取得に失敗しました。時間をおいて再度お試しください。'], [
                'store_id' => $storeId,
            ], []);
            return;
        }

        $errors = $this->validateLoginInput($storeId, $password, $stores);

        if ($errors !== []) {
            $this->renderLogin($errors, [
                'store_id' => $storeId,
            ], $stores);
            return;
        }

        try {
            $account = $this->findStaffAccount($storeId);
        } catch (Throwable $exception) {
            error_log('[staff-login] DB error: ' . $exception->getMessage());

            $this->renderLogin(['ログイン処理中にエラーが発生しました。時間をおいて再度お試しください。'], [
                'store_id' => $storeId,
            ], $stores);
            return;
        }

        if ($account === null || !password_verify($password, (string)$account['password_hash'])) {
            $this->renderLogin(['パスワードが正しくありません。'], [
                'store_id' => $storeId,
            ], $stores);
            return;
        }

        session_regenerate_id(true);

        $_SESSION['store_id'] = $storeId;
        $_SESSION['store_name'] = (string)$account['store_name'];
        $_SESSION['staff_id'] = (string)$account['account_id'];
        $_SESSION['role'] = self::ROLE_STAFF;

        $this->redirect('/MOS_A/public/staff');
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->redirect('/MOS_A/public/staff/login');
    }

    public function index(): void
    {
        $this->requireStaffLogin();

        $title = 'スタッフホーム';

        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css',
            '/MOS_A/public/assets/css/staff/base.css',
            '/MOS_A/public/assets/css/staff/orders.css',
            '/MOS_A/public/assets/css/staff/modals-products.css',
            '/MOS_A/public/assets/css/staff/navigation.css',
            '/MOS_A/public/assets/css/staff/order-list.css',
        ];
        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js',
            '/MOS_A/public/assets/js/staff/dashboard/orders.js',
            '/MOS_A/public/assets/js/staff/dashboard/products.js',
            '/MOS_A/public/assets/js/staff/dashboard/customers.js',
            '/MOS_A/public/assets/js/staff/dashboard/qr.js',
            '/MOS_A/public/assets/js/staff/dashboard.js',
        ];

        $storeName = (string)($_SESSION['store_name'] ?? '');

        // 顧客一覧の絞り込み（会計前/会計済み/未収金/全体）。未指定・不正値は「会計前」に落とす。
        $customerFilter = StaffCustomerModel::normalizeFilter($_GET['status'] ?? null);

        $customers = $this->customers($customerFilter);
        $orders = $this->orders();
        $products = $this->products();
        $productCategories = $this->productCategories();

        $view = dirname(__DIR__) . '/Views/staff/dashboard.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function orderEntry(): void
    {
        $this->requireStaffLogin();

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $customerId = filter_input(
            ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? INPUT_POST : INPUT_GET,
            'customer_id',
            FILTER_VALIDATE_INT
        );
        $returnRef = trim((string)((($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : $_GET)['ref'] ?? 'customerList'));
        $entryError = '';
        $plans = [];
        $oldTableNumber = trim((string)($_POST['table_number'] ?? ''));
        $oldPlanChoice = trim((string)($_POST['plan_choice'] ?? ''));

        if ($storeId === '' || $customerId === false || $customerId === null || $customerId < 1) {
            http_response_code(422);
            $entryError = '顧客情報が見つかりません。';
        } else {
            try {
                $pdo = db();
                $service = new StaffOrderEntryService($pdo, new StaffOrderEntryRepository($pdo));
                /*
                * POST時は、先に卓番号とコースを登録する。
                */
                if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                    $service->register(
                        $storeId,
                        (int)$customerId,
                        $oldTableNumber,
                        $oldPlanChoice
                    );

                    $this->redirect(
                        '/MOS_A/public/staff/order-menu'
                        . '?customer_id=' . (int)$customerId
                        . '&ref=' . urlencode($returnRef),
                        303
                    );
                }

                /*
                * GET時は現在の卓・コース情報を取得する。
                */
                $entryData = $service->entryData($storeId, (int)$customerId);
                $plans = $entryData['plans'];

                /*
                * 卓番号と有効なコース、または単品セッションが揃っていれば
                * 卓・コース選択画面を省略する。
                */
                if ($entryData['selection'] !== null) {
                    $this->redirect(
                        '/MOS_A/public/staff/order-menu'
                        . '?customer_id=' . (int)$customerId
                        . '&ref=' . urlencode($returnRef)
                    );
                }
            } catch (InvalidArgumentException $exception) {
                $entryError = $exception->getMessage();
            } catch (Throwable $exception) {
                error_log('[staff-order-entry] ' . $exception->getMessage());
                $entryError = '登録処理に失敗しました。もう一度お試しください。';
            }
        }

        $title = 'スタッフ注文';

        $assetVersion = time();
        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/base.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/entry.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/menu.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/cart.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/navigation.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/responsive.css?v=' . $assetVersion,
        ];

        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/orders.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/products.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/customers.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/qr.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/order-menu.js?v=' . $assetVersion,
        ];

        $view = dirname(__DIR__) . '/Views/staff/screens/staff_order_entry.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function orderMenu(): void
    {
        $this->requireStaffLogin();

        $title = 'スタッフ注文';

        $assetVersion = time();
        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/base.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/entry.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/menu.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/cart.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/navigation.css?v=' . $assetVersion,
            '/MOS_A/public/assets/css/staff-order/responsive.css?v=' . $assetVersion,
        ];

        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/orders.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/products.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/customers.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard/qr.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/dashboard.js?v=' . $assetVersion,
            '/MOS_A/public/assets/js/staff/order-menu.js?v=' . $assetVersion,
        ];

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $customerId = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);
        $tableNo = '';
        $staffOrderError = '';
        $activeSession = null;
        $planTypeId = null;
        $categories = [];
        $menus = [];

        if ($storeId === '') {
            http_response_code(403);
            echo '店舗情報を取得できません。再度ログインしてください。';
            return;
        }

        try {
            $orderModel = new StaffOrderModel();

            if ($customerId !== false && $customerId !== null && $customerId > 0) {
                $pdo = db();
                $entryService = new StaffOrderEntryService($pdo, new StaffOrderEntryRepository($pdo));
                $entryData = $entryService->entryData($storeId, (int)$customerId);
                $entryData = $entryService->entryData(
                    $storeId,
                    (int)$customerId
                );

                if ($entryData['selection'] === null) {
                    $this->redirect(
                        '/MOS_A/public/staff/order-entry'
                        . '?customer_id=' . (int)$customerId
                        . '&ref=' . urlencode(
                            (string)($_GET['ref'] ?? 'customerList')
                        )
                    );
                }

                $tableNo = (string)$entryData['selection']['table_number'];
                $tableNo = (string)$entryData['selection']['table_number'];
                $activeSession = $orderModel->activeSessionByCustomer($storeId, (int)$customerId, $tableNo);
                if ($activeSession !== null) {
                    // 商品選択画面でも顧客・卓・プラン・開始終了時刻を参照可能にする。
                    $activeSession = array_merge($activeSession, $entryData['selection']);
                }
            }

            if ($activeSession !== null) {
                $planTypeId = $orderModel->planTypeIdForSession((int)$activeSession['session_id']);
            }

            $menuModel = new MenuModel();
            $categories = $menuModel->categoriesForStore($storeId, $planTypeId !== null);
            $menus = $menuModel->menusForStore($storeId, $planTypeId);
        } catch (Throwable $exception) {
            error_log('[staff-order-menu] ' . $exception->getMessage());
            $staffOrderError = $this->safeMessage($exception, 'メニューの取得に失敗しました。時間をおいて再度お試しください。');
        }

        $view = dirname(__DIR__) . '/Views/staff/screens/staff_order_menu.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function submitStaffOrder(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'ok' => false,
                'message' => 'POSTで送信してください。',
            ], 405);
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));

        if ($storeId === '') {
            $this->json([
                'ok' => false,
                'message' => '店舗情報を取得できません。再度ログインしてください。',
            ], 403);
        }

        try {
            $model = new StaffOrderModel();
            $result = $model->submitStaffOrder($storeId, $this->jsonPayload());

            $this->json([
                'ok' => true,
                'order' => $result,
            ]);
        } catch (Throwable $exception) {
            error_log('[staff-order-submit] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage($exception, '注文登録に失敗しました。時間をおいて再度お試しください。'),
            ], 422);
        }
    }

    public function customerDetail(): void
    {
        $this->requireStaffLogin();

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $customerId = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);

        if ($storeId === '') {
            http_response_code(403);
            echo '店舗情報が取得できません。再度ログインしてください。';
            return;
        }

        if ($customerId === false || $customerId === null || $customerId < 1) {
            http_response_code(422);
            echo '顧客番号が正しくありません。';
            return;
        }

        try {
            $model = new StaffCustomerModel();
            $customerDetail = $model->customerDetail($storeId, (int)$customerId);
            $customerDetailError = '';
        } catch (Throwable $exception) {
            error_log('[staff-customer-detail] ' . $exception->getMessage());
            $customerDetail = null;
            $customerDetailError = $this->safeMessage($exception, '顧客情報の取得に失敗しました。時間をおいて再度お試しください。');
        }

        $title = '顧客詳細';
        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css',
            '/MOS_A/public/assets/css/staff/base.css',
            '/MOS_A/public/assets/css/staff/orders.css',
            '/MOS_A/public/assets/css/staff/modals-products.css',
            '/MOS_A/public/assets/css/staff/navigation.css',
            '/MOS_A/public/assets/css/staff/order-list.css',
        ];
        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js',
        ];
        $view = dirname(__DIR__) . '/Views/staff/screens/customer_detail.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function customerOrders(): void
    {
        $this->requireStaffLogin();

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $customerId = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);

        if ($storeId === '') {
            http_response_code(403);
            echo '店舗情報が取得できません。再度ログインしてください。';
            return;
        }

        if ($customerId === false || $customerId === null || $customerId < 1) {
            http_response_code(422);
            echo '顧客番号が正しくありません。';
            return;
        }

        $customerOrderError = '';
        $customerOrderMessage = (string)($_SESSION['staff_customer_order_message'] ?? '');
        unset($_SESSION['staff_customer_order_message']);

        try {
            $customerModel = new StaffCustomerModel();
            $orderModel = new StaffOrderModel();
            $customerDetail = $customerModel->customerDetail($storeId, (int)$customerId);
            $customerOrders = $orderModel->orderDetailsForCustomer($storeId, (int)$customerId);
        } catch (Throwable $exception) {
            error_log('[staff-customer-orders] ' . $exception->getMessage());
            $customerDetail = null;
            $customerOrders = [];
            $customerOrderError = $this->safeMessage($exception, '注文詳細の取得に失敗しました。時間をおいて再度お試しください。');
        }

        $title = '注文詳細';
        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css',
            '/MOS_A/public/assets/css/staff/base.css',
            '/MOS_A/public/assets/css/staff/orders.css',
            '/MOS_A/public/assets/css/staff/modals-products.css',
            '/MOS_A/public/assets/css/staff/navigation.css',
            '/MOS_A/public/assets/css/staff/order-list.css',
        ];
        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js',
        ];
        $view = dirname(__DIR__) . '/Views/staff/screens/customer_orders.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function updateCustomerOrderDetail(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo '405 Method Not Allowed';
            return;
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $customerId = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
        $orderDetailId = filter_input(INPUT_POST, 'order_detail_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($storeId === '') {
            $_SESSION['staff_customer_order_message'] = '店舗情報が取得できません。再度ログインしてください。';
            $this->redirect('/MOS_A/public/staff');
        }

        if ($customerId === false || $customerId === null || $customerId < 1) {
            $_SESSION['staff_customer_order_message'] = '顧客番号が正しくありません。';
            $this->redirect('/MOS_A/public/staff?ref=customerList');
        }

        $redirectPath = '/MOS_A/public/staff/customer/orders?customer_id=' . (int)$customerId;

        if ($orderDetailId === false || $orderDetailId === null || $orderDetailId < 1) {
            $_SESSION['staff_customer_order_message'] = '編集する注文を選択してください。';
            $this->redirect($redirectPath);
        }

        if ($quantity === false || $quantity === null || $quantity < 1) {
            $_SESSION['staff_customer_order_message'] = '数量は1以上の整数で入力してください。';
            $this->redirect($redirectPath);
        }

        try {
            $model = new StaffOrderModel();
            $model->updateCustomerOrderDetailQuantity($storeId, (int)$customerId, (int)$orderDetailId, (int)$quantity);
            $_SESSION['staff_customer_order_message'] = '注文数量を変更しました。';
        } catch (Throwable $exception) {
            error_log('[staff-customer-order-update] ' . $exception->getMessage());
            $_SESSION['staff_customer_order_message'] = $this->safeMessage($exception, '注文数量の変更に失敗しました。時間をおいて再度お試しください。');
        }

        $this->redirect($redirectPath);
    }

    public function cancelCustomerOrderDetail(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo '405 Method Not Allowed';
            return;
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $customerId = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT);
        $orderDetailId = filter_input(INPUT_POST, 'order_detail_id', FILTER_VALIDATE_INT);

        if ($storeId === '') {
            $_SESSION['staff_customer_order_message'] = '店舗情報が取得できません。再度ログインしてください。';
            $this->redirect('/MOS_A/public/staff');
        }

        if ($customerId === false || $customerId === null || $customerId < 1) {
            $_SESSION['staff_customer_order_message'] = '顧客番号が正しくありません。';
            $this->redirect('/MOS_A/public/staff?ref=customerList');
        }

        $redirectPath = '/MOS_A/public/staff/customer/orders?customer_id=' . (int)$customerId;

        if ($orderDetailId === false || $orderDetailId === null || $orderDetailId < 1) {
            $_SESSION['staff_customer_order_message'] = 'キャンセルする注文を選択してください。';
            $this->redirect($redirectPath);
        }

        try {
            $model = new StaffOrderModel();
            $model->cancelCustomerOrderDetail($storeId, (int)$customerId, (int)$orderDetailId);
            $_SESSION['staff_customer_order_message'] = '注文をキャンセルしました。';
        } catch (Throwable $exception) {
            error_log('[staff-customer-order-cancel] ' . $exception->getMessage());
            $_SESSION['staff_customer_order_message'] = $this->safeMessage($exception, '注文のキャンセルに失敗しました。時間をおいて再度お試しください。');
        }

        $this->redirect($redirectPath);
    }

    public function updateOrderProvision(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'ok' => false,
                'message' => 'POSTで送信してください。',
            ], 405);
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $orderDetailId = filter_input(INPUT_POST, 'order_detail_id', FILTER_VALIDATE_INT);
        $action = trim((string)($_POST['action'] ?? ''));

        if ($storeId === '') {
            $this->json([
                'ok' => false,
                'message' => '店舗情報が取得できません。再度ログインしてください。',
            ], 403);
        }

        if ($orderDetailId === false || $orderDetailId === null || $orderDetailId < 1) {
            $this->json([
                'ok' => false,
                'message' => '注文明細IDが正しくありません。',
            ], 422);
        }

        try {
            $model = new StaffOrderModel();
            $order = $model->updateProvidedQuantity($storeId, (int)$orderDetailId, $action);

            $this->json([
                'ok' => true,
                'order' => $order,
            ]);
        } catch (Throwable $exception) {
            error_log('[staff-order-provision] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage($exception, '提供数の更新に失敗しました。時間をおいて再度お試しください。'),
            ], 500);
        }
    }

    /**
     * 注文詳細（注文編集モーダル）からの数量変更。
     *
     * 従来はフロントのstateだけ書き換えて「変更が完了しました」と表示しており、
     * リロードすると元に戻っていた。DBのorder_detailsを実際に更新する。
     */
    public function updateOrderQuantity(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'ok' => false,
                'message' => 'POSTで送信してください。',
            ], 405);
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $orderDetailId = filter_input(INPUT_POST, 'order_detail_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($storeId === '') {
            $this->json([
                'ok' => false,
                'message' => '店舗情報が取得できません。再度ログインしてください。',
            ], 403);
        }

        if ($orderDetailId === false || $orderDetailId === null || $orderDetailId < 1) {
            $this->json([
                'ok' => false,
                'message' => '注文明細IDが正しくありません。',
            ], 422);
        }

        if ($quantity === false || $quantity === null || $quantity < 1) {
            $this->json([
                'ok' => false,
                'message' => '数量は1以上の整数で入力してください。',
            ], 422);
        }

        try {
            $model = new StaffOrderModel();
            $order = $model->updateOrderDetailQuantity($storeId, (int)$orderDetailId, (int)$quantity);

            $this->json([
                'ok' => true,
                'order' => $order,
            ]);
        } catch (Throwable $exception) {
            error_log('[staff-order-quantity] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage($exception, '注文数量の変更に失敗しました。時間をおいて再度お試しください。'),
            ], 422);
        }
    }

    public function cancelOrderDetails(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'ok' => false,
                'message' => 'POSTで送信してください。',
            ], 405);
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $rawIds = $_POST['order_detail_ids'] ?? [];

        if ($storeId === '') {
            $this->json([
                'ok' => false,
                'message' => '店舗情報が取得できません。再度ログインしてください。',
            ], 403);
        }

        if (!is_array($rawIds)) {
            $rawIds = [$rawIds];
        }

        $orderDetailIds = array_values(array_filter(
            array_map('intval', $rawIds),
            static fn (int $id): bool => $id > 0
        ));

        if ($orderDetailIds === []) {
            $this->json([
                'ok' => false,
                'message' => 'キャンセル対象の注文明細を選択してください。',
            ], 422);
        }

        try {
            $model = new StaffOrderModel();
            $orders = $model->cancelOrderDetails($storeId, $orderDetailIds);

            $this->json([
                'ok' => true,
                'orders' => $orders,
            ]);
        } catch (Throwable $exception) {
            error_log('[staff-order-cancel] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage($exception, '注文のキャンセルに失敗しました。時間をおいて再度お試しください。'),
            ], 500);
        }
    }

    /**
     * 取消解除：キャンセル済みの注文明細を注文中(ORDERED)に戻す。
     * 戻した後は提供数の変更が可能になる。
     */
    public function restoreOrderDetails(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'ok' => false,
                'message' => 'POSTで送信してください。',
            ], 405);
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $rawIds = $_POST['order_detail_ids'] ?? [];

        if ($storeId === '') {
            $this->json([
                'ok' => false,
                'message' => '店舗情報が取得できません。再度ログインしてください。',
            ], 403);
        }

        if (!is_array($rawIds)) {
            $rawIds = [$rawIds];
        }

        $orderDetailIds = array_values(array_filter(
            array_map('intval', $rawIds),
            static fn (int $id): bool => $id > 0
        ));

        if ($orderDetailIds === []) {
            $this->json([
                'ok' => false,
                'message' => '取消解除の対象を選択してください。',
            ], 422);
        }

        try {
            $model = new StaffOrderModel();
            $orders = $model->restoreOrderDetails($storeId, $orderDetailIds);

            $this->json([
                'ok' => true,
                'orders' => $orders,
            ]);
        } catch (Throwable $exception) {
            error_log('[staff-order-restore] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage($exception, '取消解除に失敗しました。時間をおいて再度お試しください。'),
            ], 500);
        }
    }

    public function addProduct(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'ok' => false,
                'message' => 'POSTで送信してください。',
            ], 405);
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));

        if ($storeId === '') {
            $this->json([
                'ok' => false,
                'message' => '店舗情報が取得できません。再度ログインしてください。',
            ], 403);
        }

        try {
            $model = new StaffProductModel();
            $product = $model->addProduct($storeId, $_POST, $_FILES['product_image'] ?? null);

            $this->json([
                'ok' => true,
                'product' => $product,
            ]);
        } catch (Throwable $exception) {
            error_log('[staff-product-add] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage($exception, '商品の追加に失敗しました。時間をおいて再度お試しください。'),
            ], 500);
        }
    }

    public function updateProduct(): void
    {
        $this->requireStaffLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json([
                'ok' => false,
                'message' => 'POSTで送信してください。',
            ], 405);
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

        if ($storeId === '') {
            $this->json([
                'ok' => false,
                'message' => '店舗情報が取得できません。再度ログインしてください。',
            ], 403);
        }

        if ($productId === false || $productId === null || $productId < 1) {
            $this->json([
                'ok' => false,
                'message' => '商品IDが正しくありません。',
            ], 422);
        }

        try {
            $model = new StaffProductModel();
            $product = $model->updateProduct($storeId, (int)$productId, $_POST, $_FILES['product_image'] ?? null);

            $this->json([
                'ok' => true,
                'product' => $product,
            ]);
        } catch (Throwable $exception) {
            error_log('[staff-product-update] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => $this->safeMessage($exception, '商品の更新に失敗しました。時間をおいて再度お試しください。'),
            ], 500);
        }
    }

    private function renderLogin(array $errors = [], array $old = [], ?array $stores = null): void
    {
        $title = 'みどり亭 ログイン';

        if ($stores === null) {
            try {
                $stores = $this->fetchActiveStores();
            } catch (Throwable $exception) {
                error_log('[staff-login] Store fetch error: ' . $exception->getMessage());
                $stores = [];
                $errors[] = '店舗情報の取得に失敗しました。時間をおいて再度お試しください。';
            }
        }

        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css',
            '/MOS_A/public/assets/css/staff/base.css',
            '/MOS_A/public/assets/css/staff/login.css',
        ];
        $jsFiles = [];
        $view = dirname(__DIR__) . '/Views/staff/screens/login.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    private function validateLoginInput(string $storeId, string $password, array $stores): array
    {
        $errors = [];

        if ($storeId === '' || !array_key_exists($storeId, $stores)) {
            $errors[] = '店舗を選択してください。';
        }

        if ($password === '') {
            $errors[] = 'パスワードを入力してください。';
        }

        return $errors;
    }

    private function fetchActiveStores(): array
    {
        $sql = <<<SQL
            SELECT
                store_id,
                store_name
            FROM stores
            WHERE is_active = 1
            ORDER BY
                CASE store_id
                    WHEN 'MH' THEN 1
                    WHEN 'MN' THEN 2
                    WHEN 'TM' THEN 3
                    WHEN 'TH' THEN 4
                    WHEN 'IM' THEN 5
                    WHEN 'FB' THEN 6
                    WHEN 'TY' THEN 7
                    WHEN 'HM' THEN 8
                    WHEN 'KB' THEN 9
                    WHEN 'NB' THEN 10
                    ELSE 99
                END,
                store_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->execute();

        $stores = [];

        foreach ($statement->fetchAll() as $store) {
            $stores[(string)$store['store_id']] = (string)$store['store_name'];
        }

        return $stores;
    }

    private function findStaffAccount(string $storeId): ?array
    {
        $sql = <<<SQL
            SELECT
                a.account_id,
                a.store_id,
                a.password_hash,
                s.store_name
            FROM store_accounts AS a
            INNER JOIN stores AS s
                ON s.store_id = a.store_id
            WHERE a.store_id = :store_id
              AND a.is_active = 1
              AND s.is_active = 1
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->execute([
            ':store_id' => $storeId,
        ]);

        $account = $statement->fetch();

        return $account === false ? null : $account;
    }

    private function requireStaffLogin(): void
    {
        if ($this->isLoggedIn()) {
            return;
        }

        $this->redirect('/MOS_A/public/staff/login');
    }

    private function isLoggedIn(): bool
    {
        return isset($_SESSION['staff_id'], $_SESSION['store_id'], $_SESSION['store_name'], $_SESSION['role'])
            && $_SESSION['role'] === self::ROLE_STAFF;
    }

    private function redirect(string $path, int $statusCode = 302): void
    {
        header('Location: ' . $path, true, $statusCode);
        exit;
    }

    /**
     * QR発行：ログイン中店舗で新しい顧客を連番で作成し、customer_idを返す。
     * 返したcustomer_idで客側画面を利用できる。
     */
    public function issueQr(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo '405 Method Not Allowed';
            return;
        }

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        if ($storeId === '') {
            $this->json([
                'ok' => false,
                'message' => '店舗情報が取得できません。再度ログインしてください。',
            ], 401);
        }

        $peopleCount = filter_input(INPUT_POST, 'people_count', FILTER_VALIDATE_INT);
        if ($peopleCount === false || $peopleCount === null || $peopleCount < 1) {
            $this->json([
                'ok' => false,
                'message' => '人数を正しく入力してください。',
            ], 422);
        }
        $peopleCount = min(99, (int)$peopleCount);

        try {
            $model = new StaffCustomerModel();
            $result = $model->issueCustomer($storeId, $peopleCount);

            $this->json([
                'ok' => true,
                'message' => 'QRを発行しました。',
                'customer_id' => $result['customer_id'],
                'store_id' => $result['store_id'],
                'people_count' => $result['people_count'],
            ]);
        } catch (Throwable $exception) {
            error_log('[staff-qr-issue] ' . $exception->getMessage());

            $this->json([
                'ok' => false,
                'message' => 'QR発行に失敗しました。',
            ], 500);
        }
    }

    /**
     * QR印刷ページ：発行済みQRコードを伝票風レイアウトで表示し、ブラウザの印刷機能で印刷する。
     * QR発行完了モーダルの「印刷」ボタンから別タブで開かれる（regiの領収書画面と同じ方式）。
     */
    public function qrPrint(): void
    {
        $this->requireStaffLogin();

        $storeId = trim((string)($_SESSION['store_id'] ?? ''));
        $storeName = trim((string)($_SESSION['store_name'] ?? ''));
        $customerId = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);

        // 再発行ボタン経由の場合は印刷物に「再発行」ラベルを表示する
        $isReissue = (string)($_GET['reissue'] ?? '') === '1';

        if ($storeId === '') {
            http_response_code(403);
            echo '店舗情報が取得できません。再度ログインしてください。';
            return;
        }

        if ($customerId === false || $customerId === null || $customerId < 1) {
            http_response_code(422);
            echo '顧客番号が正しくありません。';
            return;
        }

        try {
            $model = new StaffCustomerModel();

            // store_id一致も条件のため、他店舗の顧客番号を指定してもnullになる
            $customer = $model->customerForPrint($storeId, (int)$customerId);
        } catch (Throwable $exception) {
            error_log('[staff-qr-print] ' . $exception->getMessage());
            http_response_code(500);
            echo '顧客情報の取得に失敗しました。';
            return;
        }

        if ($customer === null) {
            http_response_code(404);
            echo '顧客情報が見つかりません。';
            return;
        }

        // 客側注文画面のURL（qr.jsのbuildOrderUrlと同じ形式にすること）
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $orderUrl = $scheme . '://' . $host . '/MOS_A/public/customer?customer_id=' . (int)$customerId;

        // 単独の印刷用ページのため、共通レイアウト(app.php)は使わない
        require dirname(__DIR__) . '/Views/staff/screens/qr_print.php';
    }

    /**
     * 例外メッセージを画面・APIへ出してよい形に落とす。
     *
     * Model が投げる InvalidArgumentException / RuntimeException は
     * 「卓番号は1〜99の数字で入力してください。」のように利用者へ見せる前提の
     * 日本語メッセージなので、そのまま返す。
     *
     * それ以外（DB接続失敗・SQLエラー・TypeError など）はSQL文やテーブル構造が
     * 混ざるため、定型文に差し替える。詳細は error_log 側にだけ残す。
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

    private function jsonPayload(): array
    {
        $rawPayload = file_get_contents('php://input');

        if ($rawPayload === false || trim($rawPayload) === '') {
            return $_POST;
        }

        $payload = json_decode($rawPayload, true);

        if (!is_array($payload)) {
            throw new InvalidArgumentException('送信データの形式が正しくありません。');
        }

        return $payload;
    }

    private function customers(string $filter = StaffCustomerModel::DEFAULT_FILTER): array
    {
        $storeId = trim((string)($_SESSION['store_id'] ?? ''));

        if ($storeId === '') {
            error_log('[staff-customers] store_id is missing from session.');
            return [];
        }

        try {
            $model = new StaffCustomerModel();

            return $model->customersForStore($storeId, $filter);
        } catch (Throwable $exception) {
            error_log('[staff-customers] DB error: ' . $exception->getMessage());

            return [];
        }
    }

    private function orders(): array
    {
        $storeId = trim((string)($_SESSION['store_id'] ?? ''));

        if ($storeId === '') {
            error_log('[staff-orders] store_id is missing from session.');
            return [];
        }

        try {
            $model = new StaffOrderModel();

            return $model->ordersForStore($storeId);
        } catch (Throwable $exception) {
            error_log('[staff-orders] DB error: ' . $exception->getMessage());

            return [];
        }
    }

    private function productCategories(): array
    {
        try {
            $model = new StaffProductModel();

            return $model->categories();
        } catch (Throwable $exception) {
            error_log('[staff-product-categories] DB error: ' . $exception->getMessage());

            return [];
        }
    }

    private function products(): array
    {
        $storeId = trim((string)($_SESSION['store_id'] ?? ''));

        if ($storeId === '') {
            error_log('[staff-products] store_id is missing from session.');
            return [];
        }

        try {
            $model = new StaffProductModel();

            return $model->productsForStore($storeId);
        } catch (Throwable $exception) {
            error_log('[staff-products] DB error: ' . $exception->getMessage());

            return [];
        }

        return [
            ['id' => 1, 'name' => 'もも串 タレ', 'category' => '串', 'stock' => 30, 'price' => 200],
            ['id' => 2, 'name' => 'もも串 塩', 'category' => '串', 'stock' => 100, 'price' => 200],
            ['id' => 3, 'name' => 'ビール', 'category' => 'ドリンク', 'stock' => 200, 'price' => 200],
        ];
    }
}
