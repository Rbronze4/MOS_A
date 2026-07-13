<?php
$customerId = trim((string)($_GET['customer_id'] ?? ''));
$returnRef = (string)($_GET['ref'] ?? 'customerDetail');
?>
<section class="staff-order-entry-page">
    <div class="staff-order-entry-top">
        <button id="staffOrderBackButton" class="back-button" type="button">←</button>
        <h1 class="staff-order-entry-heading">スタッフ注文</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <h1 class="staff-order-entry-title">卓番号とプランを選択してください</h1>

    <form method="get" action="/MOS_A/public/staff/order-menu" class="staff-order-entry-form">
        <div class="staff-table-row">
            <label for="tableNo">卓番号</label>
            <input
                type="number"
                id="tableNo"
                name="tableNo"
                min="1"
                max="99"
                step="1"
                required
            >
        </div>

        <div class="staff-plan-row">
            <label>
                <input type="radio" name="plan" value="premium" required>
                プレミアム
            </label>

            <label>
                <input type="radio" name="plan" value="standard">
                スタンダード
            </label>

            <label>
                <input type="radio" name="plan" value="single">
                単品
            </label>
        </div>

        <?php if ($customerId !== ''): ?>
            <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customerId, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <input type="hidden" name="ref" value="<?= htmlspecialchars($returnRef, ENT_QUOTES, 'UTF-8') ?>">

        <button type="submit" class="staff-order-decision-button">
            決定
        </button>
    </form>
</section>

<script>
    (() => {
        const input = document.getElementById('tableNo');

        if (!input) {
            return;
        }

        const normalize = value => String(value || '').replace(/\D/g, '').slice(0, 2);

        input.addEventListener('input', () => {
            const normalized = normalize(input.value);

            if (input.value !== normalized) {
                input.value = normalized;
            }
        });

        input.addEventListener('keydown', event => {
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            if (event.key.length === 1 && !/\d/.test(event.key)) {
                event.preventDefault();
            }
        });
    })();
</script>

<?php require dirname(__DIR__) . '/parts/side_menu.php'; ?>
