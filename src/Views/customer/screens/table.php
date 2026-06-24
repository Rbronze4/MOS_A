<?php /**
 * 客側：卓番号入力画面（最初に表示）。
 * 入力した卓番号は app.js で検証され state.tableNumber に保持、プラン選択画面へ進む。
 */ ?>
<section id="tableScreen" class="screen active">
    <div class="logo-frame" style="width: 124px; height: 124px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.14);">
        <img src="/MOS_A/public/assets/images/common/logo.png" alt="店舗ロゴ" style="width: 124px; height: 124px; object-fit: cover;">
    </div>

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