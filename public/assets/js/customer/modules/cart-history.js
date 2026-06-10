window.MOS = window.MOS || {};
window.MOS.customer = window.MOS.customer || {};

window.MOS.customer.createCartHistoryModule = function createCartHistoryModule(context) {
    const {
        state,
        formatYen,
        findMenu,
        showScreen,
        showToast,
        getDisplayPrice,
        openProduct
    } = context;

    function cartTotal() {
        return state.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    }

    function historyTotal() {
        return state.history.reduce((sum, item) => sum + item.price * item.quantity, 0);
    }

    function addCart(menu, quantity, price) {
        const existing = state.cart.find(item => String(item.id) === String(menu.id));

        if (existing) {
            existing.quantity += quantity;
        } else {
            state.cart.push({
                id: menu.id,
                name: menu.name,
                price,
                quantity
            });
        }

        renderCart();
    }

    function renderCart() {
        document.getElementById('cartTotal').textContent = formatYen(cartTotal());

        const cartList = document.getElementById('cartList');

        if (state.cart.length === 0) {
            cartList.innerHTML = '<p class="empty-message">カートは空です</p>';
            return;
        }

        cartList.innerHTML = state.cart.map(item => `
            <div class="cart-row">
                <span>${item.name}</span>
                <span>${item.quantity}</span>
                <span>${formatYen(item.price)}</span>

                <button class="pill-button change-button" data-action="change" data-menu-id="${item.id}">
                    変更
                </button>

                <button class="pill-button delete-button" data-action="delete" data-menu-id="${item.id}">
                    削除
                </button>
            </div>
        `).join('');

        cartList.querySelectorAll('.pill-button').forEach(button => {
            button.addEventListener('click', () => {
                const menuId = button.dataset.menuId;
                const action = button.dataset.action;
                const cartItem = state.cart.find(item => String(item.id) === String(menuId));

                if (!cartItem) return;

                if (action === 'delete') {
                    state.cart = state.cart.filter(item => String(item.id) !== String(menuId));
                    renderCart();
                    showToast(`${cartItem.name}を削除しました`);
                    return;
                }

                if (action === 'change') {
                    const menu = findMenu(menuId);
                    if (!menu) return;

                    state.editingItem = cartItem;
                    state.cart = state.cart.filter(item => String(item.id) !== String(menuId));
                    openProduct(menu, cartItem.quantity, false);
                }
            });
        });
    }

    function openOrderModal() {
        if (state.cart.length === 0) {
            showToast('商品が選択されていません');
            return;
        }

        document.getElementById('modalOrderTotal').textContent = formatYen(cartTotal());
        document.getElementById('modalOrderList').innerHTML = state.cart.map(item => `
            <div class="modal-order-row">
                <span>${item.name}</span>
                <span>${item.quantity}</span>
                <span>${formatYen(item.price)}</span>
            </div>
        `).join('');

        document.getElementById('orderModal').classList.add('show');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.remove('show');
    }

    function renderHistory() {
        document.getElementById('historyTotal').textContent = formatYen(historyTotal());

        const historyList = document.getElementById('historyList');

        if (state.history.length === 0) {
            historyList.innerHTML = '<p class="empty-message">注文履歴はありません</p>';
            return;
        }

        historyList.innerHTML = state.history.map(item => `
            <div class="history-row">
                <span class="history-status">[注文済み]</span>
                <span>${item.name}</span>
                <span>${item.quantity}</span>
                <span>${formatYen(item.price)}</span>

                <button class="reorder-button" data-menu-id="${item.id}">
                    再注文
                </button>
            </div>
        `).join('');

        historyList.querySelectorAll('.reorder-button').forEach(button => {
            button.addEventListener('click', () => {
                const historyItem = state.history.find(item => String(item.id) === String(button.dataset.menuId));
                if (!historyItem) return;

                const menu = findMenu(historyItem.id);
                if (!menu) return;

                openProduct(menu, historyItem.quantity || 1, true);
            });
        });
    }

    function bindCartHistoryEvents() {
        document.getElementById('cartButton').addEventListener('click', () => {
            renderCart();
            showScreen('cartScreen');
        });

        document.getElementById('historyButton').addEventListener('click', () => {
            renderHistory();
            showScreen('historyScreen');
        });

        document.getElementById('cartBackButton').addEventListener('click', () => {
            showScreen('menuScreen');
        });

        document.getElementById('historyBackButton').addEventListener('click', () => {
            showScreen('menuScreen');
        });

        document.getElementById('orderConfirmButton').addEventListener('click', () => {
            openOrderModal();
        });

        document.getElementById('closeOrderModalButton').addEventListener('click', () => {
            closeOrderModal();
        });

        document.getElementById('submitOrderButton').addEventListener('click', () => {
            state.history.push(...state.cart.map(item => ({ ...item })));
            state.cart = [];

            closeOrderModal();
            renderCart();
            renderHistory();

            showToast('注文を送信しました');
            showScreen('menuScreen');
        });
    }

    return {
        addCart,
        renderCart,
        renderHistory,
        bindCartHistoryEvents,
        cartTotal,
        historyTotal
    };
};
