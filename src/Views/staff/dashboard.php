<div class="staff-app">

    <?php require __DIR__ . '/screens/login.php'; ?>
    <?php require __DIR__ . '/screens/home.php'; ?>
    <?php require __DIR__ . '/screens/order_list.php'; ?>
    <?php require __DIR__ . '/screens/customer_list.php'; ?>
    <?php require __DIR__ . '/screens/order_detail.php'; ?>
    <?php require __DIR__ . '/screens/product_manage.php'; ?>
    <?php require __DIR__ . '/screens/qr_issue.php'; ?>

    <!-- ハンバーガーメニュー -->
    <div id="sideMenuLayer" class="side-menu-layer">
        <div class="side-menu">
            <button id="closeMenuButton" class="menu-close-button" type="button">×</button>

            <h2>メニュー</h2>

            <button data-menu-move="homeScreen" type="button">ホーム</button>
            <button type="button" onclick="location.href='/MOS_A/public/staff/order-entry?ref=home'">スタッフ注文</button>
            <button data-menu-move="orderListScreen" type="button">注文一覧</button>
            <button data-menu-move="customerListScreen" type="button">顧客詳細</button>
            <button data-menu-move="productScreen" type="button">商品管理</button>
            <button data-menu-move="qrScreen" type="button">QR発行</button>
            <button data-menu-move="loginScreen" type="button">ログアウト</button>
        </div>
    </div>

    <!-- 共通モーダル -->
    <div id="modalLayer" class="modal-layer">
        <div id="modalCard" class="modal-card"></div>
    </div>

</div>
