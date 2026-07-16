<?php /**
 * 客側：注文確認モーダル。
 * カート内容の最終確認と合計金額を表示し、「注文」で送信（履歴へ移動）する。
 * 明細は cart-history.js が描画する。
 */ ?>
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

        <button id="submitOrderButton" class="black-button" type="button">
            注文
        </button>
    </div>
</div>