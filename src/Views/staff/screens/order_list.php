<section id="orderListScreen" class="screen order-list-screen">
    <div class="screen-header">
        <button class="back-button" type="button">←</button>
        <h1 id="orderListTitle">注文一覧</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <div class="order-list-top">
        <p class="note">※赤色は飲み放題注文者</p>

        <div class="order-switch-buttons">
            <button id="showWaitingOrders" class="active" type="button">注文一覧</button>
            <button id="showServedOrders" type="button">提供済み一覧</button>
            <button id="showCanceledOrders" type="button">キャンセル済一覧</button>
        </div>
    </div>

    <div class="order-table-scroll">
        <table class="data-table order-table">
            <thead>
                <tr>
                    <th></th>
                    <th>卓</th>
                    <th>商品名</th>
                    <th>注文個数</th>
                    <th>提供数</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="orderTableBody"></tbody>
        </table>
    </div>
</section>