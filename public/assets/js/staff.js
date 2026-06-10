document.addEventListener('DOMContentLoaded', () => {
    console.log('staff.js loaded');
    const state = {
        orders: window.STAFF_DATA.orders.map(order => ({
            ...order,
            servedQty: order.servedQty ?? 0
        })),
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
                    <button class="row-button green-button" data-action="serveOne" data-id="${order.id}">1つ提供</button>
                    <button class="row-button green-button" data-action="serveAll" data-id="${order.id}">全て提供</button>
                    <button class="row-button red-button" data-action="minusOne" data-id="${order.id}">1つ減らす</button>
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
                    <td>
                        <input type="checkbox" class="order-checkbox" data-id="${order.id}">
                    </td>

                    <td class="${order.table_no === '12番' || order.table_no === '3番' ? 'table-red' : ''}">
                        ${order.table_no}
                    </td>

                    <td class="${getProductColor(order.name)}">
                        ${order.name}
                    </td>

                    <td>${order.qty}</td>

                    <td>${order.servedQty}/${order.qty}</td>

                    <td>${actionButtons}</td>
                </tr>
            `;
        }).join('');

        // 各行のボタンのイベント設定
        body.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', () => {
                const order = state.orders.find(item => String(item.id) === String(button.dataset.id));
                if (!order) return;

                if (button.dataset.action === 'serveOne') {
                    order.servedQty = Math.min(order.qty, order.servedQty + 1);

                    if (order.servedQty >= order.qty) {
                        order.status = 'served';
                    }
                }

                if (button.dataset.action === 'serveAll') {
                    order.servedQty = order.qty;
                    order.status = 'served';
                }

                if (button.dataset.action === 'minusOne') {
                    order.servedQty = Math.max(0, order.servedQty - 1);
                }

                if (button.dataset.action === 'undoServe') {
                    order.status = 'waiting';
                    order.servedQty = 0;
                }

                if (button.dataset.action === 'undoCancel') {
                    order.status = 'waiting';

                    if (order.qty <= 0) {
                        order.qty = 1;
                    }

                    order.servedQty = 0;
                }

                renderOrders();
            });
        });

        // ★★★ ここから下を確実でシンプルなロジックに修正しました ★★★
        const bulkCancelButton = document.getElementById('bulkCancelButton');
        if (bulkCancelButton) {
            // テーブル内のチェックボックスを取得
            const checkboxes = body.querySelectorAll('.order-checkbox');

            // チェックボックスがクリックされるたびに、ボタンの活性・非活性を切り替える
            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const checkedCount = body.querySelectorAll('.order-checkbox:checked').length;
                    
                    if (checkedCount > 0) {
                        bulkCancelButton.removeAttribute('disabled'); // disabled属性を完全に消し去る
                    } else {
                        bulkCancelButton.setAttribute('disabled', 'true'); // 0個なら付与する
                    }
                });
            });
        }
    }
    function setOrderTabActive(activeButtonId) {
        const tabIds = [
            'showWaitingOrders',
            'showServedOrders',
            'showCanceledOrders'
        ];

        tabIds.forEach(id => {
            const button = document.getElementById(id);
            if (!button) return;

            button.classList.toggle('active', id === activeButtonId);
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
    }function openOrderEditModal() {
        // 現在選択されている注文データを特定する
        const orderId = state.selectedOrderDetailId;
        const order = state.orders.find(item => String(item.id) === String(orderId));

        if (!order) {
            openCompleteModal('注文データが見つかりません');
            return;
        }

        // モーダルを開いたときの初期値として、現在の注文個数をセット
        let qty = order.qty;

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
            const deleteCheck = document.getElementById('deleteOrderCheck');
            
            // 1. 注文削除にチェックが入っている場合
            if (deleteCheck && deleteCheck.checked) {
                if (confirm('この注文を削除（キャンセル）してもよろしいですか？')) {
                    order.status = 'canceled'; // ステータスをキャンセルに変更
                    
                    // 画面を再描画してデータを最新にする
                    renderOrderDetail(); 
                    renderOrders(); // 裏側の注文一覧画面も同期
                    
                    openCompleteModal('注文を削除しました');
                }
                return;
            }

            // 2. 個数変更のみの場合
            order.qty = qty; // 実際のデータを書き換える

            // 提供数が変更後の注文数を上回ってしまわないように調整
            if (order.servedQty > order.qty) {
                order.servedQty = order.qty;
            }

            // 画面を再描画してデータを最新にする
            renderOrderDetail();
            renderOrders(); // 裏側の注文一覧画面も同期

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
            const storeSelect = document.getElementById('storeId');
            const passwordInput = document.getElementById('loginPassword');
            const loginError = document.getElementById('loginError');

            const storeId = storeSelect ? storeSelect.value : '';
            const password = passwordInput ? passwordInput.value.trim() : '';

            if (!storeId) {
                loginError.textContent = '店舗を選択してください';
                return;
            }

            if (!password) {
                loginError.textContent = 'パスワードを入力してください';
                return;
            }

            loginError.textContent = '';
            screenHistory.length = 0;

            const selectedOption = storeSelect.options[storeSelect.selectedIndex];
            const selectedStoreName = selectedOption ? selectedOption.textContent : '';

            const storeNameElement = document.querySelector('.store-name');
            if (storeNameElement) {
                storeNameElement.textContent = `居酒屋みどり亭 ${selectedStoreName}`;
            }

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

    const initialStaffRef = new URLSearchParams(window.location.search).get('ref');
    if (initialStaffRef === 'orderDetail') {
        renderOrderDetail();
        showScreen('orderDetailScreen', false);
    } else if (initialStaffRef === 'home') {
        showScreen('homeScreen', false);
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
                screenHistory.length = 0;

                const passwordInput = document.getElementById('loginPassword');
                const loginError = document.getElementById('loginError');

                if (passwordInput) {
                    passwordInput.value = '';
                }

                if (loginError) {
                    loginError.textContent = '';
                }

                showScreen('loginScreen', false);
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
            setOrderTabActive('showWaitingOrders');
            renderOrders();
            // ★追加: タブ切り替え時にボタンを一回グレーに戻す
            const bulkCancelButton = document.getElementById('bulkCancelButton');
            if (bulkCancelButton) bulkCancelButton.disabled = true; 
        });
    }

    const showServedOrders = document.getElementById('showServedOrders');
    if (showServedOrders) {
        showServedOrders.addEventListener('click', () => {
            state.orderMode = 'served';
            setOrderTabActive('showServedOrders');
            renderOrders();
            // ★追加: タブ切り替え時にボタンを一回グレーに戻す
            const bulkCancelButton = document.getElementById('bulkCancelButton');
            if (bulkCancelButton) bulkCancelButton.disabled = true;
        });
    }

    const showCanceledOrders = document.getElementById('showCanceledOrders');
    if (showCanceledOrders) {
        showCanceledOrders.addEventListener('click', () => {
            state.orderMode = 'canceled';
            setOrderTabActive('showCanceledOrders');
            renderOrders();
            // ★追加: タブ切り替え時にボタンを一回グレーに戻す
            const bulkCancelButton = document.getElementById('bulkCancelButton');
            if (bulkCancelButton) bulkCancelButton.disabled = true;
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
    
    const staffOrderFromDetailButton = document.getElementById('staffOrderFromDetailButton');

    if (staffOrderFromDetailButton) {
        staffOrderFromDetailButton.addEventListener('click', () => {
            let tableNo = '';

            const selectedCustomer = document.querySelector('input[name="selectedCustomer"]:checked');

            if (selectedCustomer) {
                const customerRow = selectedCustomer.closest('tr');

                if (customerRow) {
                    const cells = customerRow.querySelectorAll('td');

                    if (cells.length > 1) {
                        tableNo = cells[1].textContent.trim();
                    }
                }
            }

            if (!tableNo) {
                const selectedOrder = document.querySelector('input[name="selectedOrderDetail"]:checked');

                if (selectedOrder) {
                    const order = state.orders.find(item => String(item.id) === String(selectedOrder.value));

                    if (order) {
                        tableNo = order.table_no;
                    }
                }
            }

            if (!tableNo) {
                location.href = '/MOS_A/public/staff/order-entry?ref=detail';
                return;
            }

            tableNo = String(tableNo).replace('番', '').trim();

            const plan = 'single';

            const cartStorageKey = `staffOrderCart_${tableNo}_${plan}`;
            sessionStorage.removeItem(cartStorageKey);

            location.href = `/MOS_A/public/staff/order-menu?tableNo=${encodeURIComponent(tableNo)}&plan=${encodeURIComponent(plan)}&mode=add&ref=detail`;
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

    // ==========================================
    // ★追加：一括キャンセルボタンを押した時の処理
    // ==========================================
    const bulkCancelButtonElement = document.getElementById('bulkCancelButton');
    if (bulkCancelButtonElement) {
        bulkCancelButtonElement.addEventListener('click', () => {
            const body = document.getElementById('orderTableBody');
            if (!body) return;

            // チェックされているチェックボックスをすべて取得
            const checkedBoxes = body.querySelectorAll('.order-checkbox:checked');
            if (checkedBoxes.length === 0) return;

            // 確認アラートを表示
            if (confirm(`選択された ${checkedBoxes.length} 件の注文をキャンセルしますか？`)) {
                
                // チェックされた行のデータIDを元に、stateのステータスを書き換える
                checkedBoxes.forEach(cb => {
                    const targetId = cb.dataset.id;
                    const order = state.orders.find(item => String(item.id) === String(targetId));
                    if (order) {
                        order.status = 'canceled'; // ステータスを「キャンセル」に変更
                    }
                });

                // 状態が更新されたので再描画（これで自動的に画面から消えます）
                renderOrders();

                // ボタンをグレーアウト（非活性）に戻す
                bulkCancelButtonElement.setAttribute('disabled', 'true');
            }
        });
    }

    setOrderTabActive('showWaitingOrders');
    renderOrders();
    renderProducts();
    renderOrderDetail();
    setupCustomerSelection();
});