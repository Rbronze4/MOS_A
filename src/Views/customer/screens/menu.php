<?php /**
 * 客側：メニュー画面。
 * 上部にカテゴリタブ（左右スクロール対応）、中央にメニュー一覧、下部に
 * 注文履歴/カートのナビを配置。タブとメニューの中身は menu.js が動的描画する。
 */ ?>
<section id="menuScreen" class="screen menu-screen">
    <!--
        注文画面 上部バー（左:現在の卓番号 / 右:飲み放題の残り時間）。
        卓番号は app.js が state.tableNumber から、残り時間はタイマーが動的に更新する。
        単品プランでは残り時間(#menuRemainTime)は非表示。
    -->
    <div class="menu-topbar">
        <span id="menuTableNo" class="menu-topbar-table"></span>
        <span id="menuRemainTime" class="menu-topbar-remain"></span>
    </div>

    <div class="category-tabs-wrapper">
        <button id="categoryScrollLeft" class="category-scroll-button left" type="button">‹</button>
        <div id="categoryTabs" class="category-tabs"></div>
        <button id="categoryScrollRight" class="category-scroll-button right" type="button">›</button>
    </div>

    <main class="menu-panel">
        <div id="menuGrid" class="menu-grid"></div>
    </main>

    <nav class="bottom-nav">
        <button id="historyButton" type="button" class="history-nav-button">
            <span class="history-icon">□</span>
            <span>注文履歴</span>
        </button>

        <button id="cartButton" type="button" class="cart-nav-button">
            <span>カートを見る</span>
            <span class="cart-icon">🛒</span>
        </button>
    </nav>
</section>