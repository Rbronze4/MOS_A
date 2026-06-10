console.log('staff dashboard loaded');

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

if (window.MOS?.initSideMenu) {
    window.MOS.initSideMenu();
}

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

function openCompleteModal(message) {
    openModal(`
        <h2>${message}</h2>
        <button class="white-button" id="closeModalButton">閉じる</button>
    `);

    document.getElementById('closeModalButton').addEventListener('click', closeModal);
}

const dashboardModules = window.MOS?.staffDashboard || {};
const orderModule = dashboardModules.createOrderModule({
    state,
    openModal,
    closeModal,
    openCompleteModal
});
const productModule = dashboardModules.createProductModule({
    state,
    openModal,
    closeModal,
    openCompleteModal
});
const customerModule = dashboardModules.createCustomerModule({ state });
const qrModule = dashboardModules.createQrModule({
    openModal,
    closeModal
});

const {
    renderOrders,
    setOrderTabActive,
    renderOrderDetail,
    openOrderEditModal
} = orderModule;
const {
    renderProducts,
    selectedProduct,
    openProductForm
} = productModule;
const { setupCustomerSelection } = customerModule;
const { openQrCompleteModal } = qrModule;

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

const initialStaffRef = new URLSearchParams(window.location.search).get('ref');
if (initialStaffRef === 'orderDetail') {
    renderOrderDetail();
    showScreen('orderDetailScreen', false);
} else if (initialStaffRef === 'home') {
    showScreen('homeScreen', false);
} else if (initialStaffRef === 'orderList') {
    state.orderMode = 'waiting';
    setOrderTabActive('showWaitingOrders');
    renderOrders();
    showScreen('orderListScreen', false);
} else if (initialStaffRef === 'customerList') {
    showScreen('customerListScreen', false);
} else if (initialStaffRef === 'product') {
    renderProducts();
    showScreen('productScreen', false);
} else if (initialStaffRef === 'qr') {
    showScreen('qrScreen', false);
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

const bulkCancelButtonElement = document.getElementById('bulkCancelButton');
if (bulkCancelButtonElement) {
    bulkCancelButtonElement.addEventListener('click', () => {
        const body = document.getElementById('orderTableBody');
        if (!body) return;

        const checkedBoxes = body.querySelectorAll('.order-checkbox:checked');
        if (checkedBoxes.length === 0) return;

        if (confirm(`選択された ${checkedBoxes.length} 件の注文をキャンセルしますか？`)) {
            checkedBoxes.forEach(cb => {
                const targetId = cb.dataset.id;
                const order = state.orders.find(item => String(item.id) === String(targetId));
                if (order) {
                    order.status = 'canceled';
                }
            });

            renderOrders();
            bulkCancelButtonElement.setAttribute('disabled', 'true');
        }
    });
}

setOrderTabActive('showWaitingOrders');
renderOrders();
renderProducts();
renderOrderDetail();
setupCustomerSelection();
