<?php /**
 * 客側：プラン確認モーダル。
 * 選択プランの名称・価格・内容を表示し、「確定」でプランを確定する。
 * （制限時間機能では、ここに120分/180分のトグルを追加する予定）
 */ ?>
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

        <button id="planConfirmButton" class="black-button" type="button">
            確定
        </button>
    </div>
</div>