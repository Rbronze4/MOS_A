<?php
/**
 * スタッフホーム画面。
 *
 * ログイン時にセッションへ保存した店舗名を表示し、各スタッフ機能へ遷移する。
 */
$storeName = $storeName ?? (string)($_SESSION['store_name'] ?? '');
$displayStoreName = $storeName !== '' ? '居酒屋みどり亭 ' . $storeName : '居酒屋みどり亭';
?>
<section id="homeScreen" class="screen active">
    <div class="top-bar">
        <div class="top-info">
        </div>

        <button class="hamburger-button" type="button" aria-label="メニューを開く">☰</button>
    </div>

    <div class="store-name-box">
        <div class="store-name-label">店舗名</div>
        <div class="store-name"><?= h($displayStoreName) ?></div>
    </div>

    <div class="home-menu">
        <button data-move="orderListScreen" type="button">注文一覧</button>
        <button data-move="customerListScreen" type="button">顧客詳細</button>
        <button data-move="productScreen" type="button">商品管理</button>
        <button data-move="qrScreen" type="button">QR発行</button>
        <button data-logout type="button">ログアウト</button>
        <button type="button" onclick="location.href='/MOS_A/public/staff/order-entry?ref=home'">スタッフ注文</button>
    </div>
</section>
