<?php /**
 * 客側アプリの本体ビュー。
 * 各画面（卓番号入力→プラン選択→メニュー→商品詳細→カート→注文履歴）と
 * 確認モーダル（プラン確認・注文確認）、トースト要素をまとめて読み込む。
 * 画面切り替えは customer/app.js が .screen の active 付け替えで行う。
 */ ?>
<div class="app">

    <?php require __DIR__ . '/screens/table.php'; ?>
    <?php require __DIR__ . '/screens/plan.php'; ?>
    <?php require __DIR__ . '/screens/menu.php'; ?>
    <?php require __DIR__ . '/screens/product.php'; ?>
    <?php require __DIR__ . '/screens/cart.php'; ?>
    <?php require __DIR__ . '/screens/history.php'; ?>

    <?php require __DIR__ . '/modals/plan_confirm.php'; ?>
    <?php require __DIR__ . '/modals/order_confirm.php'; ?>

    <div id="toast" class="toast"></div>

</div>