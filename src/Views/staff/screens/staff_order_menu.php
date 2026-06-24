<?php
/**
 * スタッフ代理注文：メニュー選択画面。
 * GETで受けた卓番号・プランに基づき、カテゴリ別メニューをカード表示する。
 * （現状メニューはこのファイル内にハードコード）
 * カート操作・送信は staff/order-menu.js が担当。
 */
$tableNo = $_GET['tableNo'] ?? '';
$plan = $_GET['plan'] ?? '';

// 飲み放題プラン(standard/premium)のときは「ドリンク」カテゴリを0円にする
$planFree = in_array($plan, ['standard', 'premium'], true);

$categories = [
    'ドリンク',
    'ご飯もの',
    '串',
    '一品',
    '揚げ物',
    '限定',
];

$menus = [
    [
        'id' => 1,
        'category' => 'ドリンク',
        'name' => 'ビール',
        'price' => 200,
        'image_path' => '/MOS_A/public/assets/images/menu/beer.png',
    ],
    [
        'id' => 2,
        'category' => 'ドリンク',
        'name' => 'ハイボール',
        'price' => 200,
        'image_path' => '/MOS_A/public/assets/images/menu/highball.png',
    ],
    [
        'id' => 3,
        'category' => 'ドリンク',
        'name' => '焼酎',
        'price' => 200,
        'image_path' => '/MOS_A/public/assets/images/menu/shochu.png',
    ],
    [
        'id' => 4,
        'category' => 'ドリンク',
        'name' => 'レモンサワー',
        'price' => 200,
        'image_path' => '/MOS_A/public/assets/images/menu/lemonsour.png',
    ],
    [
        'id' => 5,
        'category' => 'ドリンク',
        'name' => 'カクテル',
        'price' => 200,
        'image_path' => '/MOS_A/public/assets/images/menu/cocktail.png',
    ],
    [
        'id' => 6,
        'category' => 'ドリンク',
        'name' => 'ウーロン茶',
        'price' => 100,
        'image_path' => '/MOS_A/public/assets/images/menu/oolongtea.png',
    ],
    [
        'id' => 7,
        'category' => '串',
        'name' => 'もも串しお',
        'price' => 100,
        'image_path' => '/MOS_A/public/assets/images/menu/Chicken_thigh.png',
    ],
    [
        'id' => 8,
        'category' => '串',
        'name' => '鳥皮たれ',
        'price' => 100,
        'image_path' => '/MOS_A/public/assets/images/menu/Chicken_skin.png',
    ],
    [
        'id' => 9,
        'category' => 'ご飯もの',
        'name' => '白ごはん',
        'price' => 150,
        'image_path' => '/MOS_A/public/assets/images/menu/rice.png',
    ],
    [
        'id' => 10,
        'category' => '一品',
        'name' => '枝豆',
        'price' => 250,
        'image_path' => '/MOS_A/public/assets/images/menu/edamame.png',
    ],
    [
        'id' => 11,
        'category' => '揚げ物',
        'name' => '唐揚げ',
        'price' => 400,
        'image_path' => '/MOS_A/public/assets/images/menu/karage.png',
    ],
];

$currentCategory = $_GET['category'] ?? 'ドリンク';

$filteredMenus = array_values(array_filter($menus, function ($menu) use ($currentCategory) {
    return $menu['category'] === $currentCategory;
}));
?>

<section class="staff-order-menu-page">
    <header class="staff-order-header">

        <button id="staffOrderMenuBackButton" class="back-button" type="button">←</button>

        <div class="staff-order-title">スタッフ注文</div>

        <div class="staff-order-header-right">
            <div class="staff-table-box">
                卓番号：<?= htmlspecialchars((string)$tableNo, ENT_QUOTES, 'UTF-8') ?>番
            </div>

            <button class="hamburger-button" type="button">☰</button>
        </div>
    </header>

    <nav class="staff-category-tabs">
        <?php foreach ($categories as $category): ?>
            <a
                href="/MOS_A/public/staff/order-menu?tableNo=<?= urlencode((string)$tableNo) ?>&plan=<?= urlencode((string)$plan) ?>&category=<?= urlencode($category) ?>&ref=<?= urlencode($_GET['ref'] ?? 'home') ?>"
                class="<?= $category === $currentCategory ? 'active' : '' ?>"
            >
                <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="staff-order-main">
        <div class="staff-menu-grid">
            <?php foreach ($filteredMenus as $menu): ?>
                <?php $displayPrice = ($planFree && $menu['category'] === 'ドリンク') ? 0 : (int)$menu['price']; ?>
                <button
                    type="button"
                    class="staff-menu-card"
                    data-menu-id="<?= htmlspecialchars((string)$menu['id'], ENT_QUOTES, 'UTF-8') ?>"
                    data-menu-name="<?= htmlspecialchars($menu['name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-menu-price="<?= htmlspecialchars((string)$displayPrice, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <img
                        src="<?= htmlspecialchars($menu['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($menu['name'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="staff-menu-name">
                        <?= htmlspecialchars($menu['name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div class="staff-menu-price">
                        ￥<?= number_format($displayPrice) ?>
                    </div>
                </button>
            <?php endforeach; ?>
        </div>

        <aside class="staff-cart-panel">
            <h2>カート内容</h2>

            <div id="staffCartList" class="staff-cart-list">
                <p class="empty-cart-text">商品が選択されていません</p>
            </div>

            <div class="staff-cart-total-row">
                <span>合計金額</span>
                <strong id="staffCartTotal">￥0</strong>
            </div>

            <button id="staffOrderSubmitButton" type="button" class="staff-order-submit-button">
                この内容で注文する
            </button>
        </aside>
    </div>
    
</section>

<?php require dirname(__DIR__) . '/parts/side_menu.php'; ?>

<script>
    window.staffOrderInfo = {
        tableNo: <?= json_encode($tableNo, JSON_UNESCAPED_UNICODE) ?>,
        plan: <?= json_encode($plan, JSON_UNESCAPED_UNICODE) ?>,
        returnRef: <?= json_encode($_GET['ref'] ?? 'home', JSON_UNESCAPED_UNICODE) ?>
    };
</script>
