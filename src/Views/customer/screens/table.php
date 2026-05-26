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

    <button id="tableSubmitButton" class="black-button" type="button">
        確定
    </button>
</section>