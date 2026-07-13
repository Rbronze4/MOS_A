<?php /**
 * 客側：注文履歴画面。
 * 注文済み商品の一覧・現在の合計金額・再注文ボタンを表示。
 * 内容は cart-history.js が描画する。
 */ ?>
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

    <div class="total-row per-person-row">
        <span>お一人様あたり（<span id="historyHeadcount">2</span>名）</span>
        <strong id="historyPerPerson">¥0</strong>
    </div>

    <div id="historyList" class="history-list"></div>
</section>