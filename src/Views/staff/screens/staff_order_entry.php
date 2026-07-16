<?php
$customerIdValue = (int)($customerId ?: 0);
$returnRef = $returnRef ?? 'customerList';
$plans = $plans ?? [];
$entryError = $entryError ?? '';
$oldTableNumber = $oldTableNumber ?? '';
$oldPlanChoice = $oldPlanChoice ?? '';
?>
<section class="staff-order-entry-page">
    <div class="staff-order-entry-top">
        <button id="staffOrderBackButton" class="back-button" type="button">←</button>
        <h1 class="staff-order-entry-heading">スタッフ注文</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <h1 class="staff-order-entry-title">卓番号とコースを選択してください</h1>

    <?php if ($entryError !== ''): ?>
        <div class="staff-order-message staff-order-message-error" role="alert">
            <?= htmlspecialchars($entryError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/MOS_A/public/staff/order-entry" class="staff-order-entry-form">
        <div class="staff-table-row">
            <label for="tableNumber">卓番号</label>
            <input
                type="number"
                id="tableNumber"
                name="table_number"
                value="<?= htmlspecialchars($oldTableNumber, ENT_QUOTES, 'UTF-8') ?>"
                min="1"
                max="99"
                step="1"
                inputmode="numeric"
                required
            >
        </div>

        <fieldset class="staff-plan-row">
            <legend>コース</legend>
            <?php foreach ($plans as $plan): ?>
                <?php $planId = (string)$plan['plan_id']; ?>
                <label>
                    <input
                        type="radio"
                        name="plan_choice"
                        value="<?= htmlspecialchars($planId, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $oldPlanChoice === $planId ? 'checked' : '' ?>
                        required
                    >
                    <span>
                        <?= htmlspecialchars((string)$plan['plan_type_name'], ENT_QUOTES, 'UTF-8') ?>
                        <?= number_format((int)$plan['time_limit_minutes']) ?>分
                        （税込前 <?= number_format((int)$plan['price']) ?>円）
                    </span>
                </label>
            <?php endforeach; ?>

            <label>
                <input
                    type="radio"
                    name="plan_choice"
                    value="single"
                    <?= $oldPlanChoice === 'single' ? 'checked' : '' ?>
                    required
                >
                <span>単品</span>
            </label>
        </fieldset>

        <input type="hidden" name="customer_id" value="<?= $customerIdValue ?>">
        <input type="hidden" name="ref" value="<?= htmlspecialchars($returnRef, ENT_QUOTES, 'UTF-8') ?>">

        <button type="submit" class="staff-order-decision-button">決定</button>
    </form>
</section>

<script>
(() => {
    const input = document.getElementById('tableNumber');
    const form = document.querySelector('.staff-order-entry-form');
    const submitButton = form?.querySelector('.staff-order-decision-button');

    if (!input || !form || !submitButton) return;

    input.addEventListener('input', () => {
        // number入力でも貼り付け等を考慮し、数字以外を除去する。
        input.value = input.value.replace(/\D/g, '').slice(0, 2);
    });

    form.addEventListener('submit', () => {
        // ネイティブ検証を通過した送信だけを一度に制限する。
        submitButton.disabled = true;
    });

    window.addEventListener('pageshow', () => {
        // ブラウザの戻る操作で復元された場合も再送信できるようにする。
        submitButton.disabled = false;
    });
})();
</script>
