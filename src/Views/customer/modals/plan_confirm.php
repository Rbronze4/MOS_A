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

        <!--
            制限時間の選択（飲み放題プランで 120分／180分 を選ばせる）。
            単品プランでは plans.js が非表示にする。選択値は確定時にタイマー開始へ渡される。
        -->
        <div class="modal-time-select" id="modalTimeSelect">
            <span class="modal-time-label">制限時間を選択</span>
            <div class="time-toggle">
                <button type="button" class="time-option is-active" data-minutes="120">120分</button>
                <button type="button" class="time-option" data-minutes="180">180分</button>
            </div>
            <p class="modal-time-note">※ ラストオーダーはコース時間の30分前です</p>
        </div>

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