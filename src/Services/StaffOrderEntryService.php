<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/StaffOrderEntryRepository.php';

/** 卓・コース選択の判定と登録を担当するService。 */
final class StaffOrderEntryService
{
    public function __construct(private PDO $pdo, private StaffOrderEntryRepository $repository)
    {
    }

    public function entryData(string $storeId, int $customerId): array
    {
        if ($this->repository->findCustomer($storeId, $customerId) === null) {
            throw new RuntimeException('顧客情報が見つかりません。');
        }

        return [
            'selection' => $this->validSelection($this->repository->currentSelection($storeId, $customerId)),
            'plans' => $this->repository->activePlansForStore($storeId),
        ];
    }

    public function register(string $storeId, int $customerId, string $tableNumber, string $choice): array
    {
        if ($tableNumber === '') {
            throw new InvalidArgumentException('卓番号を選択してください。');
        }
        // 現行スキーマには卓マスタがないため、システム定義済みの1～99を存在する卓とする。
        if (!preg_match('/^[1-9]\d?$/', $tableNumber)) {
            throw new InvalidArgumentException('存在しない卓番号は登録できません。');
        }
        if ($choice === '') {
            throw new InvalidArgumentException('コースまたは単品を選択してください。');
        }

        try {
            $this->pdo->beginTransaction();
            $customer = $this->repository->findCustomerForUpdate($storeId, $customerId);
            if ($customer === null) {
                throw new InvalidArgumentException('顧客情報が見つかりません。');
            }

            // 二重送信でも既存情報を上書きしない。
            $existing = $this->validSelection($this->repository->currentSelection($storeId, $customerId, true));
            if ($existing !== null) {
                $this->pdo->commit();
                return $existing;
            }

            $plan = null;
            if ($choice !== 'single') {
                if (!ctype_digit($choice) || (int)$choice < 1) {
                    throw new InvalidArgumentException('コースまたは単品を選択してください。');
                }
                $plan = $this->repository->findActivePlanForUpdate($storeId, (int)$choice);
                if ($plan === null) {
                    throw new InvalidArgumentException('選択されたコースは現在利用できません。');
                }
            }

            $start = new DateTimeImmutable('now');
            $startedAt = $start->format('Y-m-d H:i:s');
            $endedAt = $plan === null ? null : $start->modify('+' . (int)$plan['time_limit_minutes'] . ' minutes')->format('Y-m-d H:i:s');

            if ($plan !== null) {
                $this->repository->insertCustomerPlan($customerId, $plan, $startedAt, (string)$endedAt);
            }
            $sessionId = $this->repository->insertSession($customerId, $storeId, $tableNumber, $startedAt, $endedAt);
            $this->repository->insertCart($sessionId);
            $this->pdo->commit();

            return [
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'table_number' => $tableNumber,
                'plan_id' => $plan['plan_id'] ?? null,
            ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function validSelection(?array $selection): ?array
    {
        if ($selection === null || trim((string)$selection['table_number']) === '') {
            return null;
        }
        // expired_atがNULLなら単品。コースは期限内かつ現在も有効なplanのみ利用可能。
        if ($selection['expired_at'] === null) {
            return $selection['customer_plan_id'] === null ? $selection : null;
        }
        if (strtotime((string)$selection['expired_at']) <= time()) {
            return null;
        }

        return $selection['plan_id'] !== null && (int)$selection['plan_is_active'] === 1 ? $selection : null;
    }
}
