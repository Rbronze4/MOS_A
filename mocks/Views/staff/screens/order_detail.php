<section id="orderDetailScreen" class="screen">
    <div class="screen-header">
        <button class="back-button" type="button">←</button>
        <h1>注文詳細</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <table class="data-table detail-table">
        <thead>
            <tr>
                <th>選択</th>
                <th>商品名</th>
                <th>個数</th>
                <th>注文時間</th>
            </tr>
        </thead>
        <tbody id="orderDetailBody"></tbody>
    </table>

    <div class="bottom-right">
        <button id="orderEditButton" class="white-button" type="button">注文編集</button>
        <button id="staffOrderFromDetailButton" class="white-button" type="button">スタッフ注文</button>
    </div>
</section>
