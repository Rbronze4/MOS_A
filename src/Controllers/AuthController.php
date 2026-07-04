<?php
declare(strict_types=1);

/**
 * AuthController.php
 *
 * ログイン・ログアウトを担当するController。
 *
 * 主な役割：
 * - ログイン画面を表示する
 * - 店舗選択とパスワードでログイン認証を行う
 * - ログイン成功時にセッションへ店舗情報を保存する
 * - ログアウト時にセッションを破棄する
 */

require_once dirname(__DIR__) . '/Repositories/AuthRepository.php';

final class AuthController
{
    private AuthRepository $authRepository;

    public function __construct()
    {
        $this->authRepository = new AuthRepository();
    }

    /**
     * ログイン画面を表示する
     */
    public function showLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        $stores = $this->authRepository->getActiveStores();

        $title = 'ログイン';

        $cssFiles = [
            '/MOS_A/public/assets/css/common/base.css',
            '/MOS_A/public/assets/css/staff/base.css',
        ];

        $jsFiles = [
            '/MOS_A/public/assets/js/common/side-menu.js',
        ];

        $view = dirname(__DIR__) . '/Views/staff/screens/login.php';

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    /**
     * ログイン処理
     */
    public function login(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $storeId = trim((string)($_POST['store_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($storeId === '' || $password === '') {
            $_SESSION['login_error'] = '店舗とパスワードを入力してください。';
            header('Location: /MOS_A/public/login');
            exit;
        }

        $account = $this->authRepository->findActiveAccountByStoreId($storeId);

        if ($account === null) {
            $_SESSION['login_error'] = '店舗またはパスワードが正しくありません。';
            header('Location: /MOS_A/public/login');
            exit;
        }

        if (!password_verify($password, $account['password_hash'])) {
            $_SESSION['login_error'] = '店舗またはパスワードが正しくありません。';
            header('Location: /MOS_A/public/login');
            exit;
        }

        session_regenerate_id(true);

        $_SESSION['is_logged_in'] = true;
        $_SESSION['account_id'] = $account['account_id'];
        $_SESSION['store_id'] = $account['store_id'];
        $_SESSION['store_name'] = $account['store_name'];
        $_SESSION['login_id'] = $account['login_id'];

        header('Location: /MOS_A/public/staff');
        exit;
    }

    /**
     * ログアウト処理
     */
    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

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

        header('Location: /MOS_A/public/login');
        exit;
    }
}