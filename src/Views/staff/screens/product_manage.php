<?php /**
 * スタッフ：商品管理画面。
 * 商品一覧（名称・カテゴリ・値段・画像）を表示し、追加/編集を行う。
 * 一覧描画とフォームは dashboard/products.js が担う。
 */ ?>
<section id="productScreen" class="screen">
    <div class="screen-header">
        <button class="back-button" type="button">←</button>
        <h1>商品管理</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <form class="product-filter-panel" id="productFilterForm" role="search">
        <label>
            <span>商品名</span>
            <input id="productNameFilter" type="search" placeholder="商品名を入力">
        </label>

        <label>
            <span>カテゴリ</span>
            <select id="productCategoryFilter">
                <option value="">すべて</option>
            </select>
        </label>

        <label>
            <span>対応プラン</span>
            <select id="productPlanFilter">
                <option value="">すべて</option>
                <option value="none">単品のみ</option>
            </select>
        </label>

        <label>
            <span>販売状況</span>
            <select id="productSaleStatusFilter">
                <option value="">すべて</option>
                <option value="ON_SALE">販売中</option>
                <option value="SOLD_OUT">売り切れ</option>
                <option value="HIDDEN">非表示</option>
            </select>
        </label>

        <label>
            <span>並び順</span>
            <select id="productSortOrder">
                <option value="id-asc">登録順</option>
                <option value="name-asc">商品名順</option>
                <option value="category-asc">カテゴリ順</option>
                <option value="price-asc">価格が安い順</option>
                <option value="price-desc">価格が高い順</option>
            </select>
        </label>

        <button id="resetProductFilters" class="white-button product-filter-reset" type="button">条件をクリア</button>
        <p id="productFilterResultCount" class="product-filter-count" aria-live="polite"></p>
    </form>

    <table class="data-table product-table">
        <thead>
            <tr>
                <th>選択</th>
                <th>商品名</th>
                <th>カテゴリ</th>
                <th>値段</th>
                <th>対応プラン</th>
                <th>画像</th>
            </tr>
        </thead>
        <tbody id="productTableBody"></tbody>
    </table>

    <nav id="productPagination" class="product-pagination" aria-label="商品一覧のページ" hidden>
        <button id="previousProductPage" class="white-button" type="button">前へ</button>
        <span id="productPageStatus" aria-live="polite"></span>
        <button id="nextProductPage" class="white-button" type="button">次へ</button>
    </nav>

    <div class="bottom-buttons product-buttons">
        <button id="addProductButton" class="white-button" type="button">追加</button>
        <button id="editProductButton" class="white-button" type="button">編集</button>
    </div>
</section>
