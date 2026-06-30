<section id="menuScreen" class="screen menu-screen">
    <!--
        注文画面 上部バー（左:現在の卓番号 / 右:飲み放題の残り時間）。
        ※ここはレイアウト確認用の静的表示。
          卓番号は本来 app.js の state.tableNumber、残り時間は今後のタイマーJSで動的更新する。
    -->
    <div class="menu-topbar">
        <span id="menuTableNo" class="menu-topbar-table">卓 12番</span>
        <span id="menuRemainTime" class="menu-topbar-remain">残り 120分</span>
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