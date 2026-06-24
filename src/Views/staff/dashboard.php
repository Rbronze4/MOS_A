<?php /**
 * スタッフ ダッシュボードの本体ビュー。
 * ログイン・ホーム・注文一覧・顧客詳細・注文詳細・商品管理・QR発行の各画面と、
 * サイドメニュー(ハンバーガー)・汎用モーダルをまとめて読み込む。
 * 画面切り替えやモーダル制御は staff/dashboard.js が行う。
 */ ?>
<div class="staff-app">

    <?php require __DIR__ . '/screens/login.php'; ?>
    <?php require __DIR__ . '/screens/home.php'; ?>
    <?php require __DIR__ . '/screens/order_list.php'; ?>
    <?php require __DIR__ . '/screens/customer_list.php'; ?>
    <?php require __DIR__ . '/screens/order_detail.php'; ?>
    <?php require __DIR__ . '/screens/product_manage.php'; ?>
    <?php require __DIR__ . '/screens/qr_issue.php'; ?>

    <?php
    $staffSideMenuMode = 'screen';
    require __DIR__ . '/parts/side_menu.php';
    unset($staffSideMenuMode);
    ?>

    <!-- 共通モーダル -->
    <div id="modalLayer" class="modal-layer">
        <div id="modalCard" class="modal-card"></div>
    </div>

</div>
