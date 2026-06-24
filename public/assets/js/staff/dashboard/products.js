/**
 * スタッフ ダッシュボード モジュール：商品管理。
 * 商品一覧の描画、商品の選択、追加/編集フォームのモーダル表示を担当する。
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

    function renderProducts() {
        const body = document.getElementById('productTableBody');
        if (!body) return;

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
                    <td>${product.stock}</td>
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
            : { name: '', category: '串', stock: '', price: '' };

        if (mode === 'edit' && !product) {
            openCompleteModal('編集する商品を選択してください');
            return;
        }

        openModal(`
            <div class="product-form">
                <label>
                    <span>商品名</span>
                    <input id="productNameInput" type="text" value="${product.name}">
                </label>

                <label>
                    <span>カテゴリ</span>
                    <select id="productCategoryInput">
                        <option ${product.category === '串' ? 'selected' : ''}>串</option>
                        <option ${product.category === '揚げ物' ? 'selected' : ''}>揚げ物</option>
                        <option ${product.category === '一品' ? 'selected' : ''}>一品</option>
                        <option ${product.category === 'ドリンク' ? 'selected' : ''}>ドリンク</option>
                    </select>
                </label>

                <label>
                    <span>画像</span>
                    <div class="image-box">ここに画像を挿入</div>
                </label>

                <label>
                    <span>在庫</span>
                    <input id="productStockInput" type="number" value="${product.stock}">
                </label>

                <label>
                    <span>値段</span>
                    <input id="productPriceInput" type="number" value="${product.price}">
                </label>

                <div class="form-buttons">
                    <button id="saveProductButton" class="white-button">決定</button>
                    <button id="cancelProductButton" class="white-button">取消</button>
                </div>
            </div>
        `);

        document.getElementById('cancelProductButton').addEventListener('click', closeModal);

        document.getElementById('saveProductButton').addEventListener('click', () => {
            const name = document.getElementById('productNameInput').value.trim();
            const category = document.getElementById('productCategoryInput').value;
            const stock = Number(document.getElementById('productStockInput').value || 0);
            const price = Number(document.getElementById('productPriceInput').value || 0);

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
                    stock,
                    price
                });

                renderProducts();
                openCompleteModal('商品を追加しました');
                return;
            }

            product.name = name;
            product.category = category;
            product.stock = stock;
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
