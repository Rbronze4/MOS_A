<section id="productScreen" class="screen">
    <button id="productBackButton" class="white-button" type="button">
        戻る
    </button>

    <div class="product-card">
        <div class="product-image-frame">商品画像枠</div>

        <div class="product-body">
            <p id="productName" class="product-name">商品名</p>
            <p id="productPrice" class="product-price">¥0</p>
        </div>
    </div>

    <input
        id="quantityInput"
        class="quantity-input"
        type="text"
        inputmode="numeric"
        value="1"
    >

    <div class="quantity-buttons">
        <button id="minusButton" type="button">-</button>
        <button id="plusButton" type="button">+</button>
    </div>

    <button id="addCartButton" class="black-button large" type="button">
        確定
    </button>
</section>