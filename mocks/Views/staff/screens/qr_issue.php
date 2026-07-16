<?php /**
 * スタッフ：QR発行画面。
 * 人数・枚数を入力してQR（顧客番号）を発行する。発行処理は dashboard/qr.js。
 */ ?>
<section id="qrScreen" class="screen qr-screen">
    <div class="screen-header">
        <button class="back-button" type="button">←</button>
        <h1>QR発行</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <div class="qr-panel">
        <p>人数を入力してください</p>

        <label>
            <span>人数</span>
            <input id="peopleInput" type="number" min="1" max="99">
        </label>

        <label>
            <span>枚数</span>
            <input id="countInput" type="number" min="1" max="99">
        </label>

        <button id="issueQrButton" class="issue-button">発行</button>
    </div>
</section>
