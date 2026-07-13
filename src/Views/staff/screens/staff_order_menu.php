<?php
$tableNo = trim((string)($tableNo ?? ($_GET['tableNo'] ?? '')));
$customerIdValue = $_GET['customer_id'] ?? '';
$returnRef = (string)($_GET['ref'] ?? 'home');
$categories = $categories ?? [];
$menus = $menus ?? [];
$staffOrderError = (string)($staffOrderError ?? '');
$activeSession = $activeSession ?? null;

$currentCategory = (string)($_GET['category'] ?? '');

if ($currentCategory === '' && $categories !== []) {
    $currentCategory = (string)$categories[0]['id'];
}

$filteredMenus = array_values(array_filter($menus, static function (array $menu) use ($currentCategory): bool {
    return (string)$menu['category_id'] === $currentCategory;
}));

$targetLabel = $tableNo !== ''
    ? '卓番号：' . $tableNo . '番'
    : ((string)$customerIdValue !== '' ? '顧客番号：' . (string)$customerIdValue : '注文対象未指定');
?>

<section class="staff-order-menu-page">
    <header class="staff-order-header">
        <button id="staffOrderMenuBackButton" class="back-button" type="button">←</button>

        <div class="staff-order-title">スタッフ注文</div>

        <div class="staff-order-header-right">
            <div class="staff-table-box">
                <?= htmlspecialchars($targetLabel, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <button class="hamburger-button" type="button">☰</button>
        </div>
    </header>

    <?php if ($staffOrderError !== ''): ?>
        <div class="staff-order-message staff-order-message-error">
            <?= htmlspecialchars($staffOrderError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php elseif ($activeSession === null): ?>
        <div class="staff-order-message staff-order-message-error">
            指定された卓番号または顧客番号の利用中セッションが見つかりません。
        </div>
    <?php endif; ?>

    <nav class="staff-category-tabs">
        <?php foreach ($categories as $category): ?>
            <?php
            $categoryId = (string)$category['id'];
            $href = '/MOS_A/public/staff/order-menu?tableNo=' . urlencode($tableNo)
                . '&customer_id=' . urlencode((string)$customerIdValue)
                . '&category=' . urlencode($categoryId)
                . '&ref=' . urlencode($returnRef);
            ?>
            <a
                href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                class="<?= $categoryId === $currentCategory ? 'active' : '' ?>"
            >
                <?= htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="staff-order-main">
        <div class="staff-menu-grid">
            <?php if ($filteredMenus === []): ?>
                <p class="empty-cart-text">表示できる商品がありません。</p>
            <?php endif; ?>

            <?php foreach ($filteredMenus as $menu): ?>
                <button
                    type="button"
                    class="staff-menu-card"
                    data-menu-id="<?= htmlspecialchars((string)$menu['id'], ENT_QUOTES, 'UTF-8') ?>"
                    data-menu-name="<?= htmlspecialchars((string)$menu['name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-menu-price="<?= htmlspecialchars((string)$menu['display_price'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= $activeSession === null ? 'disabled' : '' ?>
                >
                    <img
                        src="<?= htmlspecialchars((string)$menu['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars((string)$menu['name'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="staff-menu-name">
                        <?= htmlspecialchars((string)$menu['name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div class="staff-menu-price">
                        <?php if ((int)$menu['plan_applied_flag'] === 1): ?>
                            飲み放題対象 ￥0
                        <?php else: ?>
                            税抜 ￥<?= number_format((int)$menu['price']) ?><br>
                            税込 ￥<?= number_format((int)floor((int)$menu['price'] * 1.1)) ?>
                        <?php endif; ?>
                    </div>
                </button>
            <?php endforeach; ?>
        </div>

        <aside class="staff-cart-panel">
            <div class="staff-cart-header">
                <h2>注文かご</h2>
                <button id="staffCartClearButton" type="button" class="staff-cart-clear-button">
                    すべて削除
                </button>
            </div>

            <div id="staffCartList" class="staff-cart-list">
                <p class="empty-cart-text">商品が選択されていません</p>
            </div>

            <div class="staff-cart-total-row">
                <span>合計金額</span>
                <strong id="staffCartTotal">￥0</strong>
            </div>

            <button
                id="staffOrderSubmitButton"
                type="button"
                class="staff-order-submit-button"
                <?= $activeSession === null ? 'disabled' : '' ?>
            >
                この内容で注文する
            </button>
        </aside>
    </div>
</section>

<?php require dirname(__DIR__) . '/parts/side_menu.php'; ?>

<script>
    window.staffOrderInfo = {
        tableNo: <?= json_encode($tableNo, JSON_UNESCAPED_UNICODE) ?>,
        customerId: <?= json_encode((string)$customerIdValue, JSON_UNESCAPED_UNICODE) ?>,
        returnRef: <?= json_encode($returnRef, JSON_UNESCAPED_UNICODE) ?>,
        submitUrl: '/MOS_A/public/staff/order/submit'
    };
</script>
