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

    <!--
        コース重複バナー。QRを複数端末で読み、他端末が先にコースを確定していた場合に
        「すでにコースは選択されています」と画面上部に固定表示する。OKで閉じるまで残る。
    -->
    <div id="planConflictBanner" class="plan-conflict-banner" role="alert" aria-hidden="true">
        <p id="planConflictMessage" class="plan-conflict-banner__message"></p>
        <button id="planConflictOkButton" class="plan-conflict-banner__button" type="button">OK</button>
    </div>

    <?php if (!empty($cartFlash) && is_array($cartFlash)): ?>
        <script>
            window.MOS_CART_FLASH = <?= json_encode($cartFlash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        </script>
    <?php endif; ?>

</div>
