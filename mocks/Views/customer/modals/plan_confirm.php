<div id="planModal" class="modal-layer">
    <div class="modal-card">
        <button id="closePlanModalButton" class="modal-back" type="button">
            &lt;
        </button>

        <p class="modal-title">
            このプランでよろしいでしょうか？
        </p>

        <!--
            制限時間の選択（飲み放題プランで 120分／180分 を選ばせる想定）。
            ※ここはレイアウト確認用の静的マークアップ。
              クリックでの選択切替・価格連動は本機能の JS（plans.js）実装時に対応する。
              単品プランでは非表示にする想定（mock では常時表示で見た目だけ確認）。
        -->
        <div class="modal-time-select" id="modalTimeSelect">
            <span class="modal-time-label">制限時間を選択</span>
            <div class="time-toggle">
                <button type="button" class="time-option is-active" data-minutes="120">120分</button>
                <button type="button" class="time-option" data-minutes="180">180分</button>
            </div>
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