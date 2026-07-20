<?php /**
 * スタッフ：注文一覧画面。
 * 注文中/提供済み/キャンセル済みのタブで切り替え、商品ごとの提供操作や
 * 一括キャンセルを行う。表本体は dashboard/orders.js が動的描画する。
 */ ?>
<section id="orderListScreen" class="screen order-list-screen">
    <div class="screen-header">
        <button class="back-button" type="button">←</button>
        <h1 id="orderListTitle">注文一覧</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <!--
        新しい注文が入ったことを知らせるバナー。
        一括取消のチェック中や編集モーダル表示中は画面を書き換えず、ここで知らせるだけにする。
        タップすると保留していた最新の内容へ更新する（dashboard/orders.js）。
    -->
    <button id="orderUpdateBanner" class="order-update-banner" type="button">
        新しい注文があります（タップして更新）
    </button>

    <div class="order-list-top">
        <div class="order-list-note-area">
            <p class="note">※青色は飲み放題対象の商品</p>

            <!--
                今すぐ最新の注文を確認したいときに押す。自動更新(20秒間隔)を待たずに取得する。
                タブごとの切替ボタンとは離した位置に置き、忙しい時の誤タップを避ける。
            -->
            <button id="orderRefreshButton" class="order-refresh-button" type="button">
                最新の注文を読み込む
            </button>
        </div>

        <div class="order-switch-buttons">
            <button id="showWaitingOrders" class="active" type="button">注文一覧</button>
            <button id="showServedOrders" type="button">提供済み一覧</button>
            <button id="showCanceledOrders" type="button">キャンセル済み一覧</button>
        </div>
    </div>

    <div class="order-table-scroll">
        <table class="data-table order-table">
            <thead>
                <tr>
                    <th></th>
                    <th>席</th>
                    <th>商品名</th>
                    <th>注文個数</th>
                    <th>提供数</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="orderTableBody">
            </tbody>
        </table>

        <div class="table-action-area">
            <button id="bulkCancelButton" class="bulk-cancel-btn" type="button" disabled>キャンセル</button>
        </div>
    </div>
</section>
