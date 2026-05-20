<div class="app">

    <!-- 卓番号入力画面 -->
    <section id="tableScreen" class="screen active">
        <div class="logo-frame">ロゴ枠</div>

        <p class="screen-message">
            従業員から配られた卓番号を<br>
            入力してください
        </p>

        <input
            id="tableNumberInput"
            class="number-input"
            type="text"
            inputmode="numeric"
            maxlength="3"
            value=""
        >

        <p id="tableError" class="error-message"></p>

        <button id="tableSubmitButton" class="black-button">
            確定
        </button>
    </section>

    <!-- プラン選択画面 -->
    <section id="planScreen" class="screen">
        <h1 class="page-title">プランを選択してください</h1>

        <div class="plan-list">
            <?php foreach ($plans as $plan): ?>
                <?php if ($plan['id'] !== 'single'): ?>
                    <button
                        class="plan-banner"
                        data-plan-id="<?= h($plan['id']) ?>"
                    >
                        <div class="plan-image-frame">画像枠</div>
                        <div class="plan-text">
                            <?= h($plan['name']) ?>
                        </div>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button id="singleOrderButton" class="single-order-button" data-plan-id="single">
            <span class="icon-frame">icon</span>
            単品注文
        </button>
    </section>

    <!-- メニュー画面 -->
    <section id="menuScreen" class="screen menu-screen">
        <div id="categoryTabs" class="category-tabs"></div>

        <div id="menuGrid" class="menu-grid"></div>

        <nav class="bottom-nav">
            <button id="historyButton" type="button">注文履歴</button>
            <button id="cartButton" type="button">カート</button>
        </nav>
    </section>

    <!-- 商品詳細画面 -->
    <section id="productScreen" class="screen">
        <button id="productBackButton" class="white-button">
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

        <button id="addCartButton" class="black-button large">
            確定
        </button>
    </section>

    <!-- カート画面 -->
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
            <button id="orderConfirmButton" class="black-button">
                注文確認
            </button>
        </div>
    </section>

    <!-- 注文履歴画面 -->
    <section id="historyScreen" class="screen gray-screen">
        <header class="page-header">
            <div class="header-label">注文履歴</div>
        </header>

        <button id="historyBackButton" class="back-line" type="button">
            &lt;
        </button>

        <div class="total-row">
            <span>現在の合計金額</span>
            <strong id="historyTotal">¥0</strong>
        </div>

        <div id="historyList" class="history-list"></div>
    </section>

    <!-- プラン確認モーダル -->
    <div id="planModal" class="modal-layer">
        <div class="modal-card">
            <button id="closePlanModalButton" class="modal-back" type="button">
                &lt;
            </button>

            <p class="modal-title">
                このプランでよろしいでしょうか？
            </p>

            <div class="modal-total">
                <span>合計金額</span>
                <strong id="modalPlanPrice">¥0</strong>
            </div>

            <div class="modal-detail">
                <h2 id="modalPlanName"></h2>
                <ul id="modalPlanDetails"></ul>
            </div>

            <button id="planConfirmButton" class="black-button">
                確定
            </button>
        </div>
    </div>

    <!-- 注文確認モーダル -->
    <div id="orderModal" class="modal-layer">
        <div class="modal-card">
            <button id="closeOrderModalButton" class="modal-back" type="button">
                &lt;
            </button>

            <p class="modal-title">
                この内容で注文を確定しますか？
            </p>

            <div class="modal-total">
                <span>合計金額</span>
                <strong id="modalOrderTotal">¥0</strong>
            </div>

            <div id="modalOrderList" class="modal-order-list"></div>

            <button id="submitOrderButton" class="black-button">
                注文
            </button>
        </div>
    </div>

    <div id="toast" class="toast"></div>

</div>