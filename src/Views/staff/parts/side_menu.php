<?php
/**
 * スタッフ画面用のサイドメニュー。
 *
 * $staffSideMenuMode が screen の場合は同一ページ内で画面切り替えを行い、
 * それ以外では通常リンクとして遷移する。
 */
$staffSideMenuMode = $staffSideMenuMode ?? 'link';

$staffSideMenuItems = [
    ['label' => 'ホーム', 'screen' => 'homeScreen', 'href' => '/MOS_A/public/staff?ref=home'],
    ['label' => '注文一覧', 'screen' => 'orderListScreen', 'href' => '/MOS_A/public/staff?ref=orderList'],
    ['label' => '顧客詳細', 'screen' => 'customerListScreen', 'href' => '/MOS_A/public/staff?ref=customerList'],
    ['label' => '商品管理', 'screen' => 'productScreen', 'href' => '/MOS_A/public/staff?ref=product'],
    ['label' => 'QR発行', 'screen' => 'qrScreen', 'href' => '/MOS_A/public/staff?ref=qr'],
    ['label' => 'スタッフ注文', 'href' => '/MOS_A/public/staff/order-entry?ref=home'],
    ['label' => 'ログアウト', 'logout' => true, 'href' => '/MOS_A/public/staff/logout'],
];
?>

<div id="sideMenuLayer" class="side-menu-layer">
    <div class="side-menu">
        <button id="closeMenuButton" class="menu-close-button" type="button" aria-label="メニューを閉じる">×</button>

        <?php if ($staffSideMenuMode === 'screen'): ?>
            <h2>メニュー</h2>
        <?php endif; ?>

        <?php foreach ($staffSideMenuItems as $item): ?>
            <?php if (!empty($item['logout'])): ?>
                <button data-logout type="button"><?= h($item['label']) ?></button>
            <?php elseif ($staffSideMenuMode === 'screen' && isset($item['screen'])): ?>
                <button data-menu-move="<?= h($item['screen']) ?>" type="button"><?= h($item['label']) ?></button>
            <?php else: ?>
                <button type="button" onclick="location.href='<?= h($item['href']) ?>'"><?= h($item['label']) ?></button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
