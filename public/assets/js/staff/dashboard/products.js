/**
 * スタッフ ダッシュボード モジュール：商品管理。
 * 商品一覧（名称・カテゴリ・値段・画像）の描画、商品の選択、追加/編集フォームのモーダル表示を担当する。
 * dashboard.js から context を受け取り生成。
 *
 * 主な関数: renderProducts() / selectedProduct() / openProductForm()
 */
window.MOS = window.MOS || {};
window.MOS.staffDashboard = window.MOS.staffDashboard || {};

window.MOS.staffDashboard.createProductModule = function createProductModule(context) {
    const {
        state,
        openModal,
        closeModal,
        openCompleteModal
    } = context;

    // 消費税率（10%）。将来変更する場合はここだけ直せばよいように定数化する
    const TAX_RATE = 0.1;

    // 税抜金額から税込金額を求める。端数（1円未満）は切り捨て
    function taxIncluded(priceExcludingTax) {
        return Math.floor(priceExcludingTax * (1 + TAX_RATE));
    }

    // 保存済み金額は税込のため、編集フォームの「税抜」欄に戻すときは逆算する。
    // 切り捨てで丸めた税込からは元の税抜を完全復元できないため、四捨五入で近似する
    function taxExcluded(priceIncludingTax) {
        return Math.round(priceIncludingTax / (1 + TAX_RATE));
    }

    function renderProducts() {
        const body = document.getElementById('productTableBody');
        if (!body) return;

        // 商品が0件のときは、空の表だけにならないよう空メッセージを1行表示する
        if (state.products.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="5" class="empty-row">商品が登録されていません</td>
                </tr>
            `;
            return;
        }

        body.innerHTML = state.products.map(product => {
            const selectedClass = String(product.id) === String(state.selectedProductId) ? 'selected-row' : '';
            const checked = String(product.id) === String(state.selectedProductId) ? 'checked' : '';

            return `
                <tr class="${selectedClass}" data-product-id="${product.id}">
                    <td>
                        <input
                            type="radio"
                            name="selectedProduct"
                            class="product-radio"
                            value="${product.id}"
                            ${checked}
                        >
                    </td>
                    <td>${product.name}</td>
                    <td>${product.category}</td>
                    <td>${product.price}</td>
                    <td><button class="row-button" type="button">画像選択</button></td>
                </tr>
            `;
        }).join('');

        body.querySelectorAll('tr').forEach(row => {
            row.addEventListener('click', () => {
                state.selectedProductId = Number(row.dataset.productId);
                renderProducts();
            });
        });

        body.querySelectorAll('.product-radio').forEach(radio => {
            radio.addEventListener('click', event => {
                event.stopPropagation();
                state.selectedProductId = Number(radio.value);
                renderProducts();
            });
        });
    }

    function selectedProduct() {
        return state.products.find(product => Number(product.id) === Number(state.selectedProductId));
    }

    function openProductForm(mode) {
        const product = mode === 'edit'
            ? selectedProduct()
            : { name: '', category: '串', price: '' };

        if (mode === 'edit' && !product) {
            openCompleteModal('編集する商品を選択してください');
            return;
        }

        // 税抜入力欄の初期値。追加時は空、編集時は保存済み（税込）から税抜を逆算して表示する
        const priceExcludingTax = product.price === '' ? '' : taxExcluded(Number(product.price));

        openModal(`
            <div class="product-form">
                <label>
                    <span>商品名</span>
                    <input id="productNameInput" type="text" value="${product.name}">
                </label>

                <label>
                    <span>カテゴリ</span>
                    <select id="productCategoryInput">
                        <option ${product.category === 'ドリンク' ? 'selected' : ''}>ドリンク</option>
                        <option ${product.category === '串' ? 'selected' : ''}>串</option>
                        <option ${product.category === '一品' ? 'selected' : ''}>一品</option>
                        <option ${product.category === '揚げ物' ? 'selected' : ''}>揚げ物</option>
                        <option ${product.category === 'ご飯もの' ? 'selected' : ''}>ご飯もの</option>
                        <option ${product.category === '期間限定' ? 'selected' : ''}>期間限定</option>
                        <option ${product.category === '店舗限定' ? 'selected' : ''}>店舗限定</option>
                    </select>
                </label>

                <label>
                    <span>画像</span>
                    <div class="image-box">ここに画像を挿入</div>
                </label>

                <label>
                    <span>値段（税抜）</span>
                    <input id="productPriceInput" type="number" value="${priceExcludingTax}">
                </label>

                <label>
                    <span>値段（税込）</span>
                    <!-- 税込は税抜×1.1の自動計算結果。手入力させず表示専用にする -->
                    <input id="productPriceTaxIncludedInput" type="number" value="${product.price}" readonly>
                </label>

                <div class="form-buttons">
                    <button id="saveProductButton" class="white-button">決定</button>
                    <button id="cancelProductButton" class="white-button">取消</button>
                </div>
            </div>
        `);

        document.getElementById('cancelProductButton').addEventListener('click', closeModal);

        // 税抜欄への入力に合わせて、税込欄（表示専用）をリアルタイムに更新する
        const priceInput = document.getElementById('productPriceInput');
        const taxIncludedInput = document.getElementById('productPriceTaxIncludedInput');
        priceInput.addEventListener('input', () => {
            const excluded = Number(priceInput.value || 0);
            taxIncludedInput.value = taxIncluded(excluded);
        });

        document.getElementById('saveProductButton').addEventListener('click', () => {
            const name = document.getElementById('productNameInput').value.trim();
            const category = document.getElementById('productCategoryInput').value;
            // 入力は税抜。保存・一覧表示は税込に統一するため、ここで税込へ変換する
            const priceExcluded = Number(priceInput.value || 0);
            const price = taxIncluded(priceExcluded);

            if (!name) {
                alert('商品名を入力してください');
                return;
            }

            if (mode === 'add') {
                const ids = state.products.map(item => Number(item.id));
                const nextId = ids.length > 0 ? Math.max(...ids) + 1 : 1;

                state.products.push({
                    id: nextId,
                    name,
                    category,
                    price
                });

                renderProducts();
                openCompleteModal('商品を追加しました');
                return;
            }

            product.name = name;
            product.category = category;
            product.price = price;

            renderProducts();
            openCompleteModal('商品を編集しました');
        });
    }

    return {
        renderProducts,
        selectedProduct,
        openProductForm
    };
};
