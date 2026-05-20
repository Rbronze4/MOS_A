<div class="staff-app">

    <!-- ログイン -->
    <section id="loginScreen" class="screen active">
        <h1 class="login-title">ログイン</h1>

        <div class="login-form">
            <label>
                <span>店舗ID</span>
                <input id="loginId" type="text">
            </label>

            <label>
                <span>パスワード</span>
                <input id="loginPassword" type="password">
            </label>

            <button id="loginButton" class="white-button">ログイン</button>
            <p id="loginError" class="error-text"></p>
        </div>
    </section>

    <!-- ホーム -->
    <section id="homeScreen" class="screen">
        <div class="top-info">
            <strong>【営業中】</strong>
            <span>19:00</span>
        </div>

        <div class="home-menu">
            <button data-move="orderListScreen">注文一覧</button>
            <button data-move="customerListScreen">顧客詳細</button>
            <button data-move="productScreen">商品管理</button>
            <button data-move="qrScreen">QR発行</button>
        </div>
    </section>

    <!-- 注文一覧 -->
    <section id="orderListScreen" class="screen">
        <header class="screen-header">
            <h1 id="orderListTitle">注文一覧</h1>
            <button class="hamburger" data-move="homeScreen">☰</button>
        </header>

        <div class="top-buttons">
            <button id="showWaitingOrders">注文一覧</button>
            <button id="showServedOrders">提供済み一覧</button>
            <button id="showCanceledOrders">キャンセル済一覧</button>
        </div>

        <p class="note">※赤色は飲み放題注文者</p>

        <table class="data-table order-table">
            <thead>
                <tr>
                    <th>卓番</th>
                    <th>商品名</th>
                    <th>注文個数</th>
                    <th class="action-col">操作</th>
                </tr>
            </thead>
            <tbody id="orderTableBody"></tbody>
        </table>
    </section>

    <!-- 顧客詳細 -->
    <section id="customerListScreen" class="screen">
        <header class="screen-header">
            <h1>顧客詳細</h1>
            <button class="hamburger" data-move="homeScreen">☰</button>
        </header>

        <table class="data-table customer-table">
            <thead>
                <tr>
                    <th></th>
                    <th>卓番</th>
                    <th>客番号</th>
                    <th>人数</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $index => $customer): ?>
                    <tr>
                        <td></td>
                        <td><?= h($customer['table_no']) ?></td>
                        <td><?= h($customer['customer_no']) ?></td>
                        <td><?= h((string)$customer['people']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="bottom-buttons">
            <button id="customerOrderDetailButton" class="white-button">注文詳細</button>
            <button id="qrReissueButton" class="white-button">QR再発行</button>
        </div>
    </section>

    <!-- 注文詳細 -->
    <section id="orderDetailScreen" class="screen">
        <header class="screen-header">
            <h1>注文詳細</h1>
            <button class="hamburger" data-move="customerListScreen">☰</button>
        </header>

        <table class="data-table detail-table">
            <thead>
                <tr>
                    <th></th>
                    <th>商品名</th>
                    <th>個数</th>
                    <th>注文時間</th>
                </tr>
            </thead>
            <tbody id="orderDetailBody"></tbody>
        </table>

        <div class="bottom-right">
            <button id="orderEditButton" class="white-button">注文編集</button>
        </div>
    </section>

    <!-- 商品管理 -->
    <section id="productScreen" class="screen">
        <header class="screen-header">
            <h1>商品管理</h1>
            <button class="hamburger" data-move="homeScreen">☰</button>
        </header>

        <table class="data-table product-table">
            <thead>
                <tr>
                    <th>商品名</th>
                    <th>カテゴリ</th>
                    <th>在庫</th>
                    <th>値段</th>
                    <th>画像</th>
                </tr>
            </thead>
            <tbody id="productTableBody"></tbody>
        </table>

        <div class="bottom-buttons product-buttons">
            <button id="addProductButton" class="white-button">追加</button>
            <button id="editProductButton" class="white-button">編集</button>
            <button id="deleteProductButton" class="white-button">削除</button>
        </div>
    </section>

    <!-- QR発行 -->
    <section id="qrScreen" class="screen qr-screen">
        <header class="screen-header">
            <h1>QR発行</h1>
            <button class="hamburger" data-move="homeScreen">☰</button>
        </header>

        <div class="qr-panel">
            <p>人数を入力してください</p>

            <label>
                <span>人数</span>
                <input id="peopleInput" type="number" min="1" max="99">
            </label>

            <button id="issueQrButton" class="issue-button">発行</button>
        </div>
    </section>

    <!-- 汎用モーダル -->
    <div id="modalLayer" class="modal-layer">
        <div class="modal-card" id="modalCard"></div>
    </div>

</div>

<script>
    window.STAFF_DATA = {
        orders: <?= json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        products: <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };
</script>