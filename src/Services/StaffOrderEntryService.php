<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/StaffOrderEntryRepository.php';

/**
 * 会計を通した顧客に対して注文しようとしたことを表す例外。
 *
 * 卓番号の入力ミスなどの「直せばやり直せるエラー」と違い、
 * この顧客にはもう注文できないため、画面側で入力自体を止める必要がある。
 * 呼び出し側がその区別をできるよう、専用の例外にしている。
 */
final class StaffOrderNotAcceptingException extends InvalidArgumentException
{
}

/**
 * 卓・コース選択の判定と登録を担当するService。
 */
final class StaffOrderEntryService
{
    /**
     * 注文を受け付けてよい会計状態（1=受付中）。
     * 2=会計済み / 4=未収金 / 8=会計中 の顧客に注文を追加すると、
     * 請求できない注文が増えたり、会計中に内容が変わってレジ側の
     * 同一性チェック（hash）が通らなくなるため、受付中以外は登録させない。
     */
    private const BILLING_STATUS_ACCEPTING = 1;

    public function __construct(
        private PDO $pdo,
        private StaffOrderEntryRepository $repository
    ) {
    }

    /**
     * 卓・コース選択画面に必要なデータを取得する。
     */
    public function entryData(string $storeId, int $customerId): array
    {
        $customer = $this->repository->findCustomer($storeId, $customerId);

        if ($customer === null) {
            throw new RuntimeException('顧客情報が見つかりません。');
        }

        /*
         * 会計を通した顧客は、そもそもスタッフ注文画面に入れないようにする。
         * 注文確定時にも弾いているが、そこまで進ませると
         * 商品を選び終えてから失敗することになり操作が無駄になる。
         * 卓・コース選択画面とメニュー画面はどちらもここを通るため、
         * この1か所で両方の入口を塞げる。
         */
        if ((int)$customer['billing_status'] !== self::BILLING_STATUS_ACCEPTING) {
            throw new StaffOrderNotAcceptingException(
                'この顧客はお会計が完了しているため、スタッフ注文はご利用いただけません。'
            );
        }

        $selection = $this->repository->currentSelection(
            $storeId,
            $customerId
        );

        return [
            'selection' => $this->validSelection($selection),
            'plans' => $this->repository->activePlansForStore($storeId),
        ];
    }

    /**
     * 卓番号とコースを登録し、スタッフ注文用セッションを開始する。
     */
    public function register(
        string $storeId,
        int $customerId,
        string $tableNumber,
        string $choice
    ): array {
        $tableNumber = trim($tableNumber);
        $choice = trim($choice);

        if ($tableNumber === '') {
            throw new InvalidArgumentException(
                '卓番号を選択してください。'
            );
        }

        /*
         * 現在は卓マスタがないため、1～99を有効な卓番号とする。
         */
        if (!preg_match('/^[1-9]\d?$/', $tableNumber)) {
            throw new InvalidArgumentException(
                '存在しない卓番号は登録できません。'
            );
        }

        if ($choice === '') {
            throw new InvalidArgumentException(
                'コースまたは単品を選択してください。'
            );
        }

        try {
            $this->pdo->beginTransaction();

            $customer = $this->repository->findCustomerForUpdate(
                $storeId,
                $customerId
            );

            if ($customer === null) {
                throw new InvalidArgumentException(
                    '顧客情報が見つかりません。'
                );
            }

            // レジで会計を通した後もセッションは残るため、会計状態を見ないと
            // 会計済みの顧客に対して新しく卓・コースを登録できてしまう。
            if ((int)$customer['billing_status'] !== self::BILLING_STATUS_ACCEPTING) {
                throw new StaffOrderNotAcceptingException(
                    'この顧客はお会計が完了しているため、注文を登録できません。'
                );
            }

            /*
             * 二重送信時は、すでに利用可能なセッションがあれば
             * 新しいセッションを作らず既存情報を返す。
             */
            $existingSelection = $this->repository->currentSelection(
                $storeId,
                $customerId,
                true
            );

            $existingSelection = $this->validSelection(
                $existingSelection
            );

            // 着席後は単品・コースを問わず選択を確定済みとして扱い、変更しない。
            if ($existingSelection !== null) {
                $this->pdo->commit();

                return $existingSelection;
            }

            $plan = null;

            if ($choice !== 'single') {
                if (!ctype_digit($choice) || (int)$choice < 1) {
                    throw new InvalidArgumentException(
                        'コースまたは単品を選択してください。'
                    );
                }

                $plan = $this->repository->findActivePlanForUpdate(
                    $storeId,
                    (int)$choice
                );

                if ($plan === null) {
                    throw new InvalidArgumentException(
                        '選択されたコースは現在利用できません。'
                    );
                }
            }

            /*
             * customer_plansとsessionsで同じ開始日時を使用する。
             * Repositoryでは、この日時を使って両者を関連付ける。
             */
            $startedAt = $this->repository->currentTimestamp();
            // DBの壁時計を基準に加算する。PHPのデフォルトタイムゾーンは使わない。
            $start = new DateTimeImmutable($startedAt, new DateTimeZone('UTC'));

            $endedAt = null;

            if ($plan !== null) {
                $endedAt = $start
                    ->modify(
                        '+' . (int)$plan['time_limit_minutes'] . ' minutes'
                    )
                    ->format('Y-m-d H:i:s');

                $this->repository->insertCustomerPlan(
                    $customerId,
                    $plan,
                    $startedAt,
                    $endedAt
                );
            }

            $sessionId = $this->repository->insertSession(
                $customerId,
                $storeId,
                $tableNumber,
                $startedAt,
                $endedAt
            );

            $this->repository->insertCart($sessionId);

            $this->pdo->commit();

            /*
             * 登録結果をDBから再確認する。
             */
            $registeredSelection = $this->repository->currentSelection(
                $storeId,
                $customerId
            );

            $registeredSelection = $this->validSelection(
                $registeredSelection
            );

            if ($registeredSelection === null) {
                throw new RuntimeException(
                    '注文セッションの登録結果を確認できませんでした。'
                );
            }

            return $registeredSelection;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * 卓番号と有効なコース、または単品セッションが
     * 揃っているか確認する。
     */
    private function validSelection(?array $selection): ?array
    {
        if ($selection === null) {
            return null;
        }

        /*
        * 卓番号が登録されていること。
        */
        $tableNumber = trim(
            (string)($selection['table_number'] ?? '')
        );

        if ($tableNumber === '') {
            return null;
        }

        /*
        * セッションが利用中であること。
        */
        if (($selection['session_status'] ?? '') !== 'ACTIVE') {
            return null;
        }

        /*
        * 明示的に終了済みのセッションは利用できない。
        */
        $sessionEndedAt = $selection['session_ended_at'] ?? null;

        if (
            $sessionEndedAt !== null
            && trim((string)$sessionEndedAt) !== ''
        ) {
            return null;
        }

        $customerPlanId = $selection['customer_plan_id'] ?? null;
        $planId = $selection['plan_id'] ?? null;
        $planEndedAt = $selection['plan_ended_at'] ?? null;
        $planIsActive = $selection['plan_is_active'] ?? null;

        /*
        * 現在有効なcustomer_planが存在しない場合は単品として扱う。
        *
        * 単品はcustomer_plansへ登録しない仕様のため、
        * ACTIVEなセッションと卓番号があれば有効。
        */
        if ($customerPlanId === null) {
            return $selection;
        }

        /*
        * コースの場合はplan_idと有効なplansレコードが必要。
        */
        if (
            $planId === null
            || (int)$planIsActive !== 1
        ) {
            return null;
        }

        // currentSelection() がDBのNOW()を基準に有効期限を判定済み。
        return $selection;
    }
}
