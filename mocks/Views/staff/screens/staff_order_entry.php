<section class="staff-order-entry-page">
    <div class="staff-order-entry-top">
        <button id="staffOrderBackButton" class="back-button" type="button">←</button>
        <h1 class="staff-order-entry-heading">スタッフ注文</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <h1 class="staff-order-entry-title">卓番号を入力してください</h1>

    <form method="get" action="/MOS_A/public/staff/order-menu" class="staff-order-entry-form">
        <div class="staff-table-row">
            <label for="tableNo">卓番号</label>
            <input
                type="number"
                id="tableNo"
                name="tableNo"
                min="1"
                required
            >
        </div>

        <div class="staff-plan-row">
            <label>
                <input type="radio" name="plan" value="standard" required>
                スタンダード
            </label>

            <label>
                <input type="radio" name="plan" value="premium">
                プレミアム
            </label>

            <label>
                <input type="radio" name="plan" value="single">
                単品
            </label>
        </div>

        <input type="hidden" name="ref" value="<?= htmlspecialchars($_GET['ref'] ?? 'home', ENT_QUOTES, 'UTF-8') ?>">

        <button type="submit" class="staff-order-decision-button">
            決定
        </button>
    </form>
</section>

<?php require dirname(__DIR__) . '/parts/side_menu.php'; ?>
