<?php
/**
 * スタッフダッシュボード本体。
 *
 * このViewはログイン済みユーザー専用。未ログインの場合はControllerで
 * /staff/login へリダイレクトする。
 */
?>
<div class="staff-app">

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

    <div id="modalLayer" class="modal-layer">
        <div id="modalCard" class="modal-card"></div>
    </div>

</div>
