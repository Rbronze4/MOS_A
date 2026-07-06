<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';
require_once dirname(__DIR__) . '/Models/StaffOrderModel.php';
require_once dirname(__DIR__) . '/Models/StaffProductModel.php';

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
        $customers = $this->customers();
        $orders = $this->orders();
        $products = $this->products();
        $productCategories = $this->productCategories();

        $view = dirname(__DIR__) . '/Views/staff/dashboard.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    public function orderEntry(): void
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

        $orders = $this->orders();
        $products = $this->products();

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

        $orders = $this->orders();
        $products = $this->products();

        $view = dirname(__DIR__) . '/Views/staff/screens/staff_order_menu.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
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
                'message' => $exception->getMessage(),
            ], 500);
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
                'message' => $exception->getMessage(),
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
                'message' => $exception->getMessage(),
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
                'message' => $exception->getMessage(),
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

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    private function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function customers(): array
    {
        return [
            ['table_no' => '1番', 'customer_no' => '1234567', 'people' => 4],
            ['table_no' => '2番', 'customer_no' => '1234567', 'people' => 5],
            ['table_no' => '3番', 'customer_no' => '1234567', 'people' => 3],
        ];
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
