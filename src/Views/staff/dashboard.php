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
            <button id="closeMenuButton" class="menu-close-button">×</button>

            <h2>メニュー</h2>

            <button data-menu-move="homeScreen">ホーム</button>
            <button data-menu-move="orderListScreen">注文一覧</button>
            <button data-menu-move="customerListScreen">顧客詳細</button>
            <button data-menu-move="productScreen">商品管理</button>
            <button data-menu-move="qrScreen">QR発行</button>
            <button data-menu-move="loginScreen">ログアウト</button>
        </div>
    </div>

    <!-- 汎用モーダル -->
    <div id="modalLayer" class="modal-layer">
        <div id="modalCard" class="modal-card"></div>
    </div>

</div>