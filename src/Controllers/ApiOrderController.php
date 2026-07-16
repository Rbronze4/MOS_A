<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/ApiOrderModel.php';

/**
 * レジ連携APIのリクエスト不正を表す例外。
 *
 * 契約で定められたerrorCode（INVALID_JSON_FORMAT / INVALID_REQUEST /
 * INVALID_PARAMETER / INVALID_BILL_STATUS / ORDER_NOT_FOUND）を保持する。
 */
final class ApiOrderRequestException extends RuntimeException
{
    private string $errorCode;

    public function __construct(string $errorCode, string $message)
    {
        parent::__construct($message);

        $this->errorCode = $errorCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}

/**
 * レジ連携API（POST /api/orders）のController。
 *
 * レジ（他社システム）からのJSONリクエストを受け、注文の取得と会計状況の更新を行う。
 * レジはブラウザではないため、セッションもCookieも使わないステートレスな実装とする。
 *
 * 主なメソッド:
 * - handle()       : JSONを検証し、methodごとに振り分ける
 * - getOrders()    : 条件に一致する注文を配列で返す
 * - updateStatus() : ハッシュで同一性を確認したうえで会計状況を更新する
 */
final class ApiOrderController
{
    private ApiOrderModel $model;

    public function __construct()
    {
        $this->model = new ApiOrderModel();
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $request = json_decode((string)file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($request)) {
            $this->respondError(400, 'INVALID_JSON_FORMAT', 'リクエストボディがJSONとして解釈できません。');
            return;
        }

        try {
            $method = $request['method'] ?? null;

            if ($method === 'getOrders') {
                $this->getOrders($request);
                return;
            }

            if ($method === 'updateStatus') {
                $this->updateStatus($request);
                return;
            }

            throw new ApiOrderRequestException(
                'INVALID_REQUEST',
                'methodにはgetOrdersまたはupdateStatusを指定してください。'
            );
        } catch (ApiOrderRequestException $exception) {
            $this->respondError(400, $exception->errorCode(), $exception->getMessage());
        } catch (Throwable $exception) {
            // DB障害などの想定外エラー。契約のerrorCodeには該当しないため500で返す。
            $this->respondError(500, 'INTERNAL_ERROR', 'サーバ内部でエラーが発生しました。');
        }
    }

    /**
     * @param array<string, mixed> $request
     */
    private function getOrders(array $request): void
    {
        $orders = $this->model->findOrders(
            $this->optionalCustomerId($request['customerId'] ?? null),
            $this->optionalBillStatus($request['billStatus'] ?? null),
            $this->optionalDateTime($request['fromTime'] ?? null, 'fromTime'),
            $this->optionalDateTime($request['toTime'] ?? null, 'toTime')
        );

        http_response_code(200);
        echo json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 会計状況を更新する。正常時はHTTP 200＋空ボディを返す契約。
     *
     * @param array<string, mixed> $request
     */
    private function updateStatus(array $request): void
    {
        $customerId = $this->requiredCustomerId($request['customerId'] ?? null);
        $hash = $this->optionalHash($request['hash'] ?? null);

        // 存在しない顧客に範囲外のbillStatusが来た場合はINVALID_BILL_STATUSを返す契約のため、
        // 顧客の照会より前にbillStatusを検証する。
        $billStatus = $this->requiredBillStatus($request['billStatus'] ?? null);

        $order = $this->model->findOrder($customerId);

        if ($order === null) {
            throw new ApiOrderRequestException('ORDER_NOT_FOUND', '指定された顧客の注文が見つかりません。');
        }

        // レジが注文を取得してから会計するまでの間に注文内容が変わっていないかを確認する。
        if ($hash !== null && !hash_equals((string)$order['hash'], strtolower($hash))) {
            throw new ApiOrderRequestException('ORDER_NOT_FOUND', '注文内容が変更されているため会計できません。');
        }

        $this->model->updateBillingStatus($customerId, $billStatus, (string)$order['hash']);

        http_response_code(200);
    }

    /**
     * @param mixed $value
     */
    private function optionalCustomerId($value): ?string
    {
        return $value === null ? null : $this->requiredCustomerId($value);
    }

    /**
     * @param mixed $value
     */
    private function requiredCustomerId($value): string
    {
        if (!is_string($value) || preg_match('/^[0-9]{7}$/', $value) !== 1) {
            throw new ApiOrderRequestException('INVALID_PARAMETER', 'customerIdは7桁の数字で指定してください。');
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    private function optionalHash($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || preg_match('/^[0-9a-fA-F]{8,64}$/', $value) !== 1) {
            throw new ApiOrderRequestException('INVALID_PARAMETER', 'hashの形式が不正です。');
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    private function optionalBillStatus($value): ?int
    {
        return $value === null ? null : $this->requiredBillStatus($value);
    }

    /**
     * @param mixed $value
     */
    private function requiredBillStatus($value): int
    {
        if (!is_int($value) || $value < 1 || $value > ApiOrderModel::BILL_STATUS_MAX) {
            throw new ApiOrderRequestException('INVALID_BILL_STATUS', 'billStatusは1〜15で指定してください。');
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    private function optionalDateTime($value, string $fieldName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $value) !== 1) {
            throw new ApiOrderRequestException(
                'INVALID_PARAMETER',
                $fieldName . 'はISO8601形式（YYYY-MM-DDTHH:MM:SS）で指定してください。'
            );
        }

        return $value;
    }

    private function respondError(int $statusCode, string $errorCode, string $message): void
    {
        http_response_code($statusCode);

        echo json_encode([
            'errorCode' => $errorCode,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
    }
}
