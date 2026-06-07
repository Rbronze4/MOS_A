document.addEventListener('DOMContentLoaded', () => {
    console.log('staff.js loaded');

    const state = {
        orders: window.STAFF_DATA.orders.map(order => ({ ...order })),
        products: window.STAFF_DATA.products.map(product => ({ ...product })),
        orderMode: 'waiting',
        selectedProductId: null,
        selectedOrderDetailId: null,
        selectedCustomerIndex: null,
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

    const screenHistory = [];

    const modalLayer = document.getElementById('modalLayer');
    const modalCard = document.getElementById('modalCard');
    const sideMenuLayer = document.getElementById('sideMenuLayer');
    const closeMenuButton = document.getElementById('closeMenuButton');

    function showScreen(screenId, saveHistory = true) {
        const currentScreen = document.querySelector('.screen.active');

        if (
            saveHistory &&
            currentScreen &&
            currentScreen.id !== screenId
        ) {
            screenHistory.push(currentScreen.id);
        }

        screens.forEach(id => {
            const screen = document.getElementById(id);
            if (screen) {
                screen.classList.toggle('active', id === screenId);
            }
        });
    }

    function goBackScreen() {
        const previousScreenId = screenHistory.pop();

        if (!previousScreenId) {
            showScreen('homeScreen', false);
            return;
        }

        showScreen(previousScreenId, false);
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
        const title = document.getElementById('orderListTitle');
        const body = document.getElementById('orderTableBody');

        if (!title || !body) return;

        title.textContent = statusTitle();

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
                    <td class="${order.table_no === '12番' || order.table_no === '3番' ? 'table-red' : ''}">
                        ${order.table_no}
                    </td>
                    <td class="${getProductColor(order.name)}">
                        ${order.name}
                    </td>
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
        if (!body) return;

        const orders = state.orders.filter(order => order.status !== 'canceled');

        body.innerHTML = orders.map(order => {
            const selectedClass = String(order.id) === String(state.selectedOrderDetailId)
                ? 'selected-row'
                : '';

            const checked = String(order.id) === String(state.selectedOrderDetailId)
                ? 'checked'
                : '';

            return `
                <tr class="${selectedClass}" data-order-id="${order.id}">
                    <td>
                        <input
                            type="radio"
                            name="selectedOrderDetail"
                            class="order-detail-radio"
                            value="${order.id}"
                            ${checked}
                        >
                    </td>
                    <td>${order.name}</td>
                    <td>${order.qty}</td>
                    <td>${order.time}</td>
                </tr>
            `;
        }).join('');

        body.querySelectorAll('tr').forEach(row => {
            row.addEventListener('click', () => {
                state.selectedOrderDetailId = Number(row.dataset.orderId);
                renderOrderDetail();
            });
        });

        body.querySelectorAll('.order-detail-radio').forEach(radio => {
            radio.addEventListener('click', event => {
                event.stopPropagation();
                state.selectedOrderDetailId = Number(radio.value);
                renderOrderDetail();
            });
        });
    }

    function setupCustomerSelection() {
        const rows = document.querySelectorAll('#customerListScreen tbody tr');

        rows.forEach(row => {
            const radio = row.querySelector('input[name="selectedCustomer"]');

            if (!radio) return;

            const updateSelectedRow = () => {
                rows.forEach(targetRow => {
                    targetRow.classList.remove('selected-row');
                });

                row.classList.add('selected-row');
                radio.checked = true;
                state.selectedCustomerIndex = radio.value;
            };

            row.addEventListener('click', () => {
                updateSelectedRow();
            });

            radio.addEventListener('click', event => {
                event.stopPropagation();
                updateSelectedRow();
            });
        });
    }

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
                    <td><button class="row-button" type="button">ここをクリック</button></td>
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

    function openCompleteModal(message) {
        openModal(`
            <h2>${message}</h2>
            <button class="white-button" id="closeModalButton">閉じる</button>
        `);

        document.getElementById('closeModalButton').addEventListener('click', closeModal);
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

    function prepareScreen(target) {
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
    }

    const loginButton = document.getElementById('loginButton');

    if (loginButton) {
        loginButton.addEventListener('click', () => {
            const loginId = document.getElementById('loginId').value.trim();
            const password = document.getElementById('loginPassword').value.trim();
            const loginError = document.getElementById('loginError');

            if (!loginId || !password) {
                loginError.textContent = '店舗IDとパスワードを入力してください';
                return;
            }

            loginError.textContent = '';
            screenHistory.length = 0;

            showScreen('homeScreen', false);
        });
    } else {
        console.error('loginButton が見つかりません');
    }

    document.querySelectorAll('[data-move]').forEach(button => {
        button.addEventListener('click', () => {
            const target = button.dataset.move;

            prepareScreen(target);
            showScreen(target);
        });
    });

    document.querySelectorAll('.back-button').forEach(button => {
        button.addEventListener('click', () => {
            goBackScreen();
        });
    });

    document.querySelectorAll('.hamburger-button').forEach(button => {
        button.addEventListener('click', () => {
            sideMenuLayer.classList.add('show');
        });
    });

    if (closeMenuButton) {
        closeMenuButton.addEventListener('click', () => {
            sideMenuLayer.classList.remove('show');
        });
    }

    if (sideMenuLayer) {
        sideMenuLayer.addEventListener('click', event => {
            if (event.target === sideMenuLayer) {
                sideMenuLayer.classList.remove('show');
            }
        });
    }

    document.querySelectorAll('[data-menu-move]').forEach(button => {
        button.addEventListener('click', () => {
            const target = button.dataset.menuMove;

            sideMenuLayer.classList.remove('show');

            if (target === 'loginScreen') {
                window.location.href = 'login.php';
                return;
            }

            prepareScreen(target);
            showScreen(target);
        });
    });

    const showWaitingOrders = document.getElementById('showWaitingOrders');
    if (showWaitingOrders) {
        showWaitingOrders.addEventListener('click', () => {
            state.orderMode = 'waiting';
            renderOrders();
        });
    }

    const showServedOrders = document.getElementById('showServedOrders');
    if (showServedOrders) {
        showServedOrders.addEventListener('click', () => {
            state.orderMode = 'served';
            renderOrders();
        });
    }

    const showCanceledOrders = document.getElementById('showCanceledOrders');
    if (showCanceledOrders) {
        showCanceledOrders.addEventListener('click', () => {
            state.orderMode = 'canceled';
            renderOrders();
        });
    }

    const customerOrderDetailButton = document.getElementById('customerOrderDetailButton');
    if (customerOrderDetailButton) {
        customerOrderDetailButton.addEventListener('click', () => {
            const selectedCustomer = document.querySelector('input[name="selectedCustomer"]:checked');

            if (!selectedCustomer) {
                openCompleteModal('顧客を選択してください');
                return;
            }

            renderOrderDetail();
            showScreen('orderDetailScreen');
        });
    }

    const qrReissueButton = document.getElementById('qrReissueButton');
    if (qrReissueButton) {
        qrReissueButton.addEventListener('click', () => {
            const selectedCustomer = document.querySelector('input[name="selectedCustomer"]:checked');

            if (!selectedCustomer) {
                openCompleteModal('顧客を選択してください');
                return;
            }

            openQrCompleteModal('QR再発行が完了しました');
        });
    }

    const orderEditButton = document.getElementById('orderEditButton');
    if (orderEditButton) {
        orderEditButton.addEventListener('click', () => {
            const selectedOrder = document.querySelector('input[name="selectedOrderDetail"]:checked');

            if (!selectedOrder) {
                openCompleteModal('注文を選択してください');
                return;
            }

            openOrderEditModal();
        });
    }

    const addProductButton = document.getElementById('addProductButton');
    if (addProductButton) {
        addProductButton.addEventListener('click', () => {
            openProductForm('add');
        });
    }

    const editProductButton = document.getElementById('editProductButton');
    if (editProductButton) {
        editProductButton.addEventListener('click', () => {
            openProductForm('edit');
        });
    }

    const deleteProductButton = document.getElementById('deleteProductButton');
    if (deleteProductButton) {
        deleteProductButton.addEventListener('click', () => {
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
    }

    const issueQrButton = document.getElementById('issueQrButton');
    if (issueQrButton) {
        issueQrButton.addEventListener('click', () => {
            const people = Number(document.getElementById('peopleInput').value || 0);

            if (people <= 0) {
                openCompleteModal('人数を入力してください');
                return;
            }

            openQrCompleteModal('QR発行が完了しました');
        });
    }

    if (modalLayer) {
        modalLayer.addEventListener('click', event => {
            if (event.target === modalLayer) {
                closeModal();
            }
        });
    }

    renderOrders();
    renderProducts();
    renderOrderDetail();
    setupCustomerSelection();
});