<?php /**
 * 客側：カート画面。
 * カート内の商品一覧と合計金額を表示し、「注文確認」で注文確認モーダルを開く。
 * 一覧と合計は cart-history.js が描画する。
 */ ?>
<section id="cartScreen" class="screen gray-screen">
    <header class="page-header">
        <div class="header-label">カート</div>
    </header>

    <button id="cartBackButton" class="back-line" type="button">
        &lt;
    </button>

    <div class="total-row">
        <span>合計金額</span>
        <strong id="cartTotal">¥0</strong>
    </div>

    <div id="cartList" class="cart-list"></div>

    <div class="bottom-action">
        <button id="orderConfirmButton" class="black-button" type="button">
            注文確認
        </button>
    </div>
</section>