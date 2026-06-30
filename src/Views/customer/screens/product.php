<?php /**
 * 客側：商品詳細画面。
 * 選択した商品の画像・名称・価格と数量入力を表示し、カートへ追加する。
 * 内容は app.js / menu.js が選択商品に応じて流し込む。
 */ ?>
<section id="productScreen" class="screen">
    <button id="productBackButton" class="white-button" type="button">
        戻る
    </button>

    <div class="product-card">
        <div class="product-image-frame" id="productImageFrame">商品画像</div>

        <div class="product-body">
            <p id="productName" class="product-name">商品名</p>
            <p id="productPrice" class="product-price">¥0</p>
        </div>
    </div>

    <!--
        数量はキーボード入力不可（readonly）。下の −／＋ ボタンでのみ増減する。
        ※卓番号入力(#tableNumberInput)はキーボード入力可のまま。
    -->
    <input
        id="quantityInput"
        class="quantity-input"
        type="text"
        value="1"
        readonly
        tabindex="-1"
    >

    <div class="quantity-buttons">
        <button id="minusButton" type="button">-</button>
        <button id="plusButton" type="button">+</button>
    </div>

    <button id="addCartButton" class="black-button large" type="button">
        確定
    </button>
</section>