<?php /**
 * スタッフ：商品管理画面。
 * 商品一覧（名称・カテゴリ・在庫・値段・画像）を表示し、追加/編集/削除を行う。
 * 一覧描画とフォームは dashboard/products.js が担う。
 */ ?>
<section id="productScreen" class="screen">
    <div class="screen-header">
        <button class="back-button" type="button">←</button>
        <h1>商品管理</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <table class="data-table product-table">
        <thead>
            <tr>
                <th>選択</th>
                <th>商品名</th>
                <th>カテゴリ</th>
                <th>在庫</th>
                <th>値段</th>
                <th>画像</th>
            </tr>
        </thead>
        <tbody id="productTableBody"></tbody>
    </table>

    <div class="bottom-buttons product-buttons">
        <button id="addProductButton" class="white-button" type="button">追加</button>
        <button id="editProductButton" class="white-button" type="button">編集</button>
        <button id="deleteProductButton" class="white-button" type="button">削除</button>
    </div>
</section>
