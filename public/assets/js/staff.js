document.addEventListener('DOMContentLoaded', () => {
    const state = {
        orders: window.STAFF_DATA.orders.map(order => ({ ...order })),
        products: window.STAFF_DATA.products.map(product => ({ ...product })),
        orderMode: 'waiting',
        selectedProductId: null
    };

    const screens = [
        'loginScreen',
        'homeScreen',
        'orderListScreen',
        'customerListScreen',
        'orderDetailScreen',
        'productScreen',
        'qrScreen'
    ];

    const modalLayer = document.getElementById('modalLayer');
    const modalCard = document.getElementById('modalCard');

    function showScreen(screenId) {
        screens.forEach(id => {
            const screen = document.getElementById(id);
            if (screen) {
                screen.classList.toggle('active', id === screenId);
            }
        });
    }

    function openModal(html) {
        modalCard.innerHTML = html;
        modalLayer.classList.add('show');
    }

    function closeModal() {
        modalLayer.classList.remove('show');
        modalCard.innerHTML = '';
    }

    function getProductColor(name) {
        if (name.includes('ビール') || name.includes('枝豆') || name.includes('モモ') || name.includes('もも')) {
            return 'table-blue';
        }

        if (name.includes('とりかわ') || name.includes('チキン')) {
            return 'table-orange';
        }

        if (name.includes('釜') || name.includes('ハイ')) {
            return 'table-red';
        }

        return '';
    }

    function statusTitle() {
        if (state.orderMode === 'waiting') {
            return '注文一覧';
        }

        if (state.orderMode === 'served') {
            return '提供済み注文一覧';
        }

        return 'キャンセル注文一覧';
    }

    function renderOrders() {
        document.getElementById('orderListTitle').textContent = statusTitle();

        const body = document.getElementById('orderTableBody');
        const orders = state.orders.filter(order => order.status === state.orderMode);

        body.innerHTML = orders.map(order => {
            let actionButtons = '';

            if (state.orderMode === 'waiting') {
                actionButtons = `
                    <button class="row-button green-button" data-action="serve" data-id="${order.id}">提供完了</button>
                    <button class="row-button red-button" data-action="cancel" data-id="${order.id}">注文取消</button>
                `;
            }

            if (state.orderMode === 'served') {
                actionButtons = `
                    <button class="row-button red-button" data-action="undoServe" data-id="${order.id}">提供取消</button>
                `;
            }

            if (state.orderMode === 'canceled') {
                actionButtons = `
                    <button class="row-button red-button" data-action="undoCancel" data-id="${order.id}">取消解除</button>
                `;
            }

            return `
                <tr>
                    <td class="${order.table_no === '12番' || order.table_no === '3番' ? 'table-red' : ''}">${order.table_no}</td>
                    <td class="${getProductColor(order.name)}">${order.name}</td>
                    <td>${order.qty}</td>
                    <td>${actionButtons}</td>
                </tr>
            `;
        }).join('');

        body.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', () => {
                const order = state.orders.find(item => String(item.id) === String(button.dataset.id));
                if (!order) return;

                if (button.dataset.action === 'serve') {
                    order.status = 'served';
                }

                if (button.dataset.action === 'cancel') {
                    order.status = 'canceled';
                }

                if (button.dataset.action === 'undoServe') {
                    order.status = 'waiting';
                }

                if (button.dataset.action === 'undoCancel') {
                    order.status = 'waiting';
                }

                renderOrders();
            });
        });
    }

    function renderOrderDetail() {
        const body = document.getElementById('orderDetailBody');
        const orders = state.orders.filter(order => order.status !== 'canceled');

        body.innerHTML = orders.map(order => {
            return `
                <tr>
                    <td></td>
                    <td>${order.name}</td>
                    <td>${order.qty}</td>
                    <td>${order.time}</td>
                </tr>
            `;
        }).join('');
    }

    function renderProducts() {
        const body = document.getElementById('productTableBody');

        body.innerHTML = state.products.map(product => {
            const selectedClass = String(product.id) === String(state.selectedProductId) ? 'selected-row' : '';

            return `
                <tr class="${selectedClass}" data-product-id="${product.id}">
                    <td>${product.name}</td>
                    <td>${product.category}</td>
                    <td>${product.stock}</td>
                    <td>${product.price}</td>
                    <td><button class="row-button">ここをクリック</button></td>
                </tr>
            `;
        }).join('');

        body.querySelectorAll('tr').forEach(row => {
            row.addEventListener('click', () => {
                state.selectedProductId = Number(row.dataset.productId);
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
            openModal(`
                <h2>編集する商品を選択してください</h2>
                <button class="white-button" id="closeModalButton">閉じる</button>
            `);
            document.getElementById('closeModalButton').addEventListener('click', closeModal);
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
                    <span>写真</span>
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
                    <button id="saveProductButton" class="white-button">OK</button>
                    <button id="cancelProductButton" class="white-button">cancel</button>
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
                const nextId = Math.max(...state.products.map(item => item.id)) + 1;

                state.products.push({
                    id: nextId,
                    name,
                    category,
                    stock,
                    price
                });

                openCompleteModal('商品を追加しました');
            } else {
                product.name = name;
                product.category = category;
                product.stock = stock;
                product.price = price;

                openCompleteModal('商品を編集しました');
            }

            renderProducts();
        });
    }

    function openCompleteModal(message) {
        openModal(`
            <h2>${message}</h2>
            <button class="white-button" id="closeModalButton">閉じる</button>
        `);

        document.getElementById('closeModalButton').addEventListener('click', closeModal);
    }

    function openOrderEditModal() {
        let qty = 1;

        openModal(`
            <div class="edit-modal">
                <div class="edit-row">
                    <span>個数変更</span>
                    <div class="qty-control">
                        <button id="minusQtyButton">−</button>
                        <span id="editQtyValue">${qty}</span>
                        <button id="plusQtyButton">＋</button>
                    </div>
                </div>

                <div class="edit-row">
                    <span>注文削除</span>
                    <input id="deleteOrderCheck" class="delete-check" type="checkbox">
                </div>

                <div class="form-buttons">
                    <button id="saveOrderEditButton" class="white-button">決定</button>
                </div>
            </div>
        `);

        document.getElementById('minusQtyButton').addEventListener('click', () => {
            qty = Math.max(1, qty - 1);
            document.getElementById('editQtyValue').textContent = qty;
        });

        document.getElementById('plusQtyButton').addEventListener('click', () => {
            qty += 1;
            document.getElementById('editQtyValue').textContent = qty;
        });

        document.getElementById('saveOrderEditButton').addEventListener('click', () => {
            openCompleteModal('注文の変更が完了しました');
        });
    }

    function issueCustomerNumber() {
        return String(Math.floor(Math.random() * 10000000)).padStart(7, '0');
    }

    function openQrCompleteModal(messagePrefix = 'QR発行が完了しました') {
        const number = issueCustomerNumber();

        openModal(`
            <h2>${messagePrefix}</h2>
            <div>顧客番号</div>
            <div class="generated-number">${number}</div>
            <button class="white-button" id="closeModalButton">閉じる</button>
        `);

        document.getElementById('closeModalButton').addEventListener('click', closeModal);
    }

    document.getElementById('loginButton').addEventListener('click', () => {
        const loginId = document.getElementById('loginId').value.trim();
        const password = document.getElementById('loginPassword').value.trim();

        if (!loginId || !password) {
            document.getElementById('loginError').textContent = '店舗IDとパスワードを入力してください';
            return;
        }

        document.getElementById('loginError').textContent = '';
        showScreen('homeScreen');
    });

    document.querySelectorAll('[data-move]').forEach(button => {
        button.addEventListener('click', () => {
            const target = button.dataset.move;

            if (target === 'orderListScreen') {
                state.orderMode = 'waiting';
                renderOrders();
            }

            if (target === 'productScreen') {
                renderProducts();
            }

            if (target === 'orderDetailScreen') {
                renderOrderDetail();
            }

            showScreen(target);
        });
    });

    document.getElementById('showWaitingOrders').addEventListener('click', () => {
        state.orderMode = 'waiting';
        renderOrders();
    });

    document.getElementById('showServedOrders').addEventListener('click', () => {
        state.orderMode = 'served';
        renderOrders();
    });

    document.getElementById('showCanceledOrders').addEventListener('click', () => {
        state.orderMode = 'canceled';
        renderOrders();
    });

    document.getElementById('customerOrderDetailButton').addEventListener('click', () => {
        renderOrderDetail();
        showScreen('orderDetailScreen');
    });

    document.getElementById('qrReissueButton').addEventListener('click', () => {
        openQrCompleteModal('QR再発行が完了しました');
    });

    document.getElementById('orderEditButton').addEventListener('click', () => {
        openOrderEditModal();
    });

    document.getElementById('addProductButton').addEventListener('click', () => {
        openProductForm('add');
    });

    document.getElementById('editProductButton').addEventListener('click', () => {
        openProductForm('edit');
    });

    document.getElementById('deleteProductButton').addEventListener('click', () => {
        const product = selectedProduct();

        if (!product) {
            openCompleteModal('削除する商品を選択してください');
            return;
        }

        state.products = state.products.filter(item => Number(item.id) !== Number(product.id));
        state.selectedProductId = null;
        renderProducts();
        openCompleteModal('商品を削除しました');
    });

    document.getElementById('issueQrButton').addEventListener('click', () => {
        const people = Number(document.getElementById('peopleInput').value || 0);

        if (people <= 0) {
            openCompleteModal('人数を入力してください');
            return;
        }

        openQrCompleteModal('QR発行が完了しました');
    });

    modalLayer.addEventListener('click', event => {
        if (event.target === modalLayer) {
            closeModal();
        }
    });

    renderOrders();
    renderProducts();
    renderOrderDetail();
});