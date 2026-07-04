<?php
/**
 * スタッフ：ホーム画面。
 * 店舗名表示と、各管理画面（注文一覧/顧客詳細/商品管理/QR発行/ログアウト/スタッフ注文）への
 * ナビゲーションボタンを配置。
 */
?>

<section id="homeScreen" class="screen active">
    <div class="top-bar">
        <div class="top-info">
        </div>

        <button class="hamburger-button" type="button">☰</button>
    </div>

    <div class="store-name-box">
        <div class="store-name">
            <?= htmlspecialchars($storeName ?? '店舗未選択', ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <div class="home-menu">
        <button data-move="orderListScreen" type="button">注文一覧</button>
        <button data-move="customerListScreen" type="button">顧客詳細</button>
        <button data-move="productScreen" type="button">商品管理</button>
        <button data-move="qrScreen" type="button">QR発行</button>

        <form method="post" action="/MOS_A/public/logout" style="margin:0;">
            <button type="submit">ログアウト</button>
        </form>

        <button type="button" onclick="location.href='/MOS_A/public/staff/order-entry?ref=home'">
            スタッフ注文
        </button>
    </div>
</section>
