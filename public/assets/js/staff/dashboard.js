/**
 * スタッフダッシュボードの中心スクリプト。
 * 各画面の表示切替、モーダル表示、注文・商品・顧客・QRモジュールの初期化を担当します。
 */
console.log('staff dashboard loaded');

const state = {
    orders: (window.STAFF_DATA?.orders || []).map(order => ({
        ...order,
        servedQty: order.servedQty ?? 0
    })),
    products: (window.STAFF_DATA?.products || []).map(product => ({ ...product })),
    productCategories: (window.STAFF_DATA?.productCategories || []).map(category => ({ ...category })),
    orderMode: 'waiting',
    selectedProductId: null,
    selectedOrderDetailId: null,
    selectedCustomerIndex: null,
};

const screens = [
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

    if (saveHistory && currentScreen && currentScreen.id !== screenId) {
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
    showScreen(previousScreenId || 'homeScreen', false);
}

function openModal(html) {
    if (!modalCard || !modalLayer) return;

    modalCard.innerHTML = html;
    modalLayer.classList.add('show');
}

function closeModal() {
    if (!modalCard || !modalLayer) return;

    modalLayer.classList.remove('show');
    modalCard.innerHTML = '';
}

function openCompleteModal(message) {
    openModal(`
        <h2>${message}</h2>
        <button class="white-button" id="closeModalButton">閉じる</button>
    `);

    document.getElementById('closeModalButton')?.addEventListener('click', closeModal);
}

function performLogout() {
    location.href = '/MOS_A/public/staff/logout';
}

function confirmLogout() {
    openModal(`
        <h2>ログアウトしますか？</h2>
        <p class="modal-note">ログイン画面に戻ります。</p>
        <div class="form-buttons">
            <button class="white-button" id="confirmLogoutButton">ログアウト</button>
            <button class="white-button" id="cancelLogoutButton">キャンセル</button>
        </div>
    `);

    document.getElementById('confirmLogoutButton')?.addEventListener('click', () => {
        closeModal();
        performLogout();
    });

    document.getElementById('cancelLogoutButton')?.addEventListener('click', closeModal);
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
    openOrderEditModal,
    cancelOrders
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
        setOrderTabActive('showWaitingOrders');
        renderOrders();
    }

    if (target === 'productScreen') {
        renderProducts();
    }

    if (target === 'orderDetailScreen') {
        renderOrderDetail();
    }
}

document.querySelectorAll('[data-move]').forEach(button => {
    button.addEventListener('click', () => {
        const target = button.dataset.move;

        if (target === 'loginScreen') {
            confirmLogout();
            return;
        }

        prepareScreen(target);
        showScreen(target);
    });
});

document.querySelectorAll('[data-logout]').forEach(button => {
    button.addEventListener('click', confirmLogout);
});

document.querySelectorAll('.back-button').forEach(button => {
    button.addEventListener('click', goBackScreen);
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

        if (sideMenuLayer) {
            sideMenuLayer.classList.remove('show');
        }

        if (target === 'loginScreen') {
            confirmLogout();
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
        document.getElementById('bulkCancelButton')?.setAttribute('disabled', 'true');
    });
}

const showServedOrders = document.getElementById('showServedOrders');
if (showServedOrders) {
    showServedOrders.addEventListener('click', () => {
        state.orderMode = 'served';
        setOrderTabActive('showServedOrders');
        renderOrders();
        document.getElementById('bulkCancelButton')?.setAttribute('disabled', 'true');
    });
}

const showCanceledOrders = document.getElementById('showCanceledOrders');
if (showCanceledOrders) {
    showCanceledOrders.addEventListener('click', () => {
        state.orderMode = 'canceled';
        setOrderTabActive('showCanceledOrders');
        renderOrders();
        document.getElementById('bulkCancelButton')?.setAttribute('disabled', 'true');
    });
}

const customerOrderDetailButton = document.getElementById('customerOrderDetailButton');
if (customerOrderDetailButton) {
    customerOrderDetailButton.addEventListener('click', () => {
        const selectedCustomer = document.querySelector('input[name="selectedCustomer"]:checked');

        if (!selectedCustomer) {
            openCompleteModal('顧客を選択してください。');
            return;
        }

        const customerId = selectedCustomer.dataset.customerId || selectedCustomer.value;

        if (!customerId) {
            openCompleteModal('顧客番号が取得できません。');
            return;
        }

        location.href = `/MOS_A/public/staff/customer/orders?customer_id=${encodeURIComponent(customerId)}`;
    });
}

const qrReissueButton = document.getElementById('qrReissueButton');
if (qrReissueButton) {
    qrReissueButton.addEventListener('click', () => {
        const selectedCustomer = document.querySelector('input[name="selectedCustomer"]:checked');

        if (!selectedCustomer) {
            openCompleteModal('顧客を選択してください。');
            return;
        }

        openQrCompleteModal('QR再発行が完了しました。');
    });
}

const staffOrderFromCustomerButton = document.getElementById('staffOrderFromCustomerButton');
if (staffOrderFromCustomerButton) {
    staffOrderFromCustomerButton.addEventListener('click', () => {
        const selectedCustomer = document.querySelector('input[name="selectedCustomer"]:checked');

        if (!selectedCustomer) {
            openCompleteModal('顧客を選択してください。');
            return;
        }

        const customerId = selectedCustomer.dataset.customerId || selectedCustomer.value;

        if (!customerId) {
            openCompleteModal('顧客番号を取得できません。');
            return;
        }

        const customerRow = selectedCustomer.closest('tr');
        const cells = customerRow?.querySelectorAll('td') || [];
        const tableNo = cells.length > 1 ? cells[1].textContent.trim() : '';
        const hasActiveSession = tableNo !== '' && tableNo !== 'なし';
        const path = hasActiveSession ? 'order-menu' : 'order-entry';

        location.href = `/MOS_A/public/staff/${path}?customer_id=${encodeURIComponent(customerId)}&ref=customerList`;
    });
}

const staffOrderFromDetailButton = document.getElementById('staffOrderFromDetailButton');
if (staffOrderFromDetailButton) {
    staffOrderFromDetailButton.addEventListener('click', () => {
        let tableNo = '';
        const selectedCustomer = document.querySelector('input[name="selectedCustomer"]:checked');

        if (selectedCustomer) {
            const customerRow = selectedCustomer.closest('tr');
            const cells = customerRow?.querySelectorAll('td') || [];
            tableNo = cells.length > 1 ? cells[1].textContent.trim() : '';
        }

        if (!tableNo) {
            const selectedOrder = document.querySelector('input[name="selectedOrderDetail"]:checked');

            if (selectedOrder) {
                const order = state.orders.find(item => String(item.id) === String(selectedOrder.value));
                tableNo = order?.table_no || '';
            }
        }

        if (!tableNo) {
            location.href = '/MOS_A/public/staff/order-entry?ref=detail';
            return;
        }

        tableNo = String(tableNo).replace('番', '').replace('逡ｪ', '').trim();

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
            openCompleteModal('注文を選択してください。');
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
        openCompleteModal('商品削除は今回は未実装です。');
    });
}

const issueQrButton = document.getElementById('issueQrButton');
if (issueQrButton) {
    issueQrButton.addEventListener('click', () => {
        const people = Number(document.getElementById('peopleInput')?.value || 0);

        if (people <= 0) {
            openCompleteModal('人数を入力してください。');
            return;
        }

        openQrCompleteModal('QR発行が完了しました。');
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
    bulkCancelButtonElement.addEventListener('click', async () => {
        const body = document.getElementById('orderTableBody');
        if (!body) return;

        const checkedBoxes = body.querySelectorAll('.order-checkbox:checked');
        if (checkedBoxes.length === 0) return;

        if (!confirm(`選択された ${checkedBoxes.length} 件の注文をキャンセルしますか？`)) {
            return;
        }

        const orderDetailIds = Array.from(checkedBoxes).map(cb => cb.dataset.orderDetailId || cb.dataset.id);
        bulkCancelButtonElement.disabled = true;

        try {
            await cancelOrders(orderDetailIds);
            bulkCancelButtonElement.setAttribute('disabled', 'true');
            openCompleteModal('選択した注文をキャンセルしました。');
        } catch (error) {
            openCompleteModal(error.message || '注文のキャンセルに失敗しました。');
        }
    });
}

setOrderTabActive('showWaitingOrders');
renderOrders();
renderProducts();
renderOrderDetail();
setupCustomerSelection();
