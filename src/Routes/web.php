<?php
declare(strict_types=1);

/**
 * 簡易ルーター。
 *
 * REQUEST_URIからbasePath(/MOS_A/public)を取り除き、パスとHTTPメソッドで
 * 対応するControllerメソッドへ振り分ける。
 */

require_once dirname(__DIR__) . '/Controllers/StaffController.php';
require_once dirname(__DIR__) . '/Controllers/CustomerController.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$basePath = '/MOS_A/public';

if (str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}

$path = $path === '' ? '/' : $path;

if ($path !== '/') {
    $path = rtrim($path, '/');
}

switch ($path) {
    case '/':
        $controller = new StaffController();
        $controller->login();
        break;

    case '/staff/login':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->authenticate();
            break;
        }

        $controller->login();
        break;

    case '/staff/logout':
        $controller = new StaffController();
        $controller->logout();
        break;

    case '/staff':
        $controller = new StaffController();
        $controller->index();
        break;

    case '/staff/order-entry':
        $controller = new StaffController();
        $controller->orderEntry();
        break;

    case '/staff/order-menu':
        $controller = new StaffController();
        $controller->orderMenu();
        break;

    case '/staff/order/submit':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->submitStaffOrder();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/staff/customer/detail':
        $controller = new StaffController();
        $controller->customerDetail();
        break;

    case '/staff/customer/orders':
        $controller = new StaffController();
        $controller->customerOrders();
        break;

    case '/staff/customer/orders/update':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->updateCustomerOrderDetail();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/staff/customer/orders/cancel':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->cancelCustomerOrderDetail();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/staff/order/provision':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->updateOrderProvision();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/staff/order/cancel':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->cancelOrderDetails();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/staff/qr/issue':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->issueQr();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/staff/qr/print':
        $controller = new StaffController();
        $controller->qrPrint();
        break;

    case '/staff/product/add':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->addProduct();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/staff/product/update':
        $controller = new StaffController();

        if ($method === 'POST') {
            $controller->updateProduct();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/customer':
        $controller = new CustomerController();
        $controller->index();
        break;

    case '/customer/session/start':
        $controller = new CustomerController();

        if ($method === 'POST') {
            $controller->startSession();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/customer/cart/add':
        $controller = new CustomerController();

        if ($method === 'POST') {
            $controller->addCart();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/customer/cart/update':
        $controller = new CustomerController();

        if ($method === 'POST') {
            $controller->updateCart();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/customer/cart/delete':
        $controller = new CustomerController();

        if ($method === 'POST') {
            $controller->deleteCart();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    case '/customer/order/submit':
        $controller = new CustomerController();

        if ($method === 'POST') {
            $controller->submitOrder();
            break;
        }

        http_response_code(405);
        echo '405 Method Not Allowed';
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
