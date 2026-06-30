/**
 * 客側モジュール：カート・注文履歴。
 * カート内容と合計の描画、商品の数量変更/削除/変更、注文確認モーダル、
 * 注文送信（カート→履歴へ移動）、注文履歴の描画・再注文を担当する。
 * 注文履歴の合計にはコース料金（プラン料金×人数。人数はスタッフ入力前のため暫定2名固定）を
 * 加算し、コースを履歴の先頭に1注文として表示する。再注文は個数1の状態で注文画面を開く。
 * app.js から context を受け取り生成される。
 *
 * 主な関数: renderCart() / openOrderModal() / renderHistory() / courseTotal() など
 */
window.MOS = window.MOS || {};
window.MOS.customer = window.MOS.customer || {};

window.MOS.customer.createCartHistoryModule = function createCartHistoryModule(context) {
    const {
        state,
        formatYen,
        findMenu,
        findPlan,
        showScreen,
        showToast,
        getDisplayPrice,
        openProduct,
        refreshCategoryScrollButtons
    } = context;

    // 人数はスタッフがQR発行時に入力する想定だが、現状はその数値を取得する機会が
    // ないため、暫定で2名固定とする（コース料金 = プラン料金 × この人数）。
    const HEADCOUNT = 2;

    function cartTotal() {
        return state.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    }

    // 選択中のコース（飲み放題プラン）。飲み放題なし(single,料金0)や未選択時は null 扱い。
    function selectedCoursePlan() {
        const plan = findPlan(state.selectedPlanId);
        return plan && plan.price > 0 ? plan : null;
    }

    // コース料金の合計（プラン料金 × 人数）。コースなしは0。
    function courseTotal() {
        const plan = selectedCoursePlan();
        return plan ? plan.price * HEADCOUNT : 0;
    }

    function historyTotal() {
        const itemsTotal = state.history.reduce((sum, item) => sum + item.price * item.quantity, 0);
        return itemsTotal + courseTotal();
    }

    // 一人当たりの金額（合計 ÷ 人数）。端数は切り上げ。
    function perPersonTotal() {
        return Math.ceil(historyTotal() / HEADCOUNT);
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

        // 一人当たりの金額（人数は暫定2名固定）を表示
        const perPersonEl = document.getElementById('historyPerPerson');
        if (perPersonEl) {
            perPersonEl.textContent = formatYen(perPersonTotal());
        }
        const headcountEl = document.getElementById('historyHeadcount');
        if (headcountEl) {
            headcountEl.textContent = String(HEADCOUNT);
        }

        const historyList = document.getElementById('historyList');

        const rows = [];

        // コース料金を注文の一つとして先頭に表示（数量列に人数、金額列に料金×人数）。
        const coursePlan = selectedCoursePlan();
        if (coursePlan) {
            rows.push(`
                <div class="history-row">
                    <span class="history-status">[コース]</span>
                    <span>${coursePlan.name}</span>
                    <span>${HEADCOUNT}名</span>
                    <span>${formatYen(coursePlan.price * HEADCOUNT)}</span>
                </div>
            `);
        }

        state.history.forEach(item => {
            rows.push(`
                <div class="history-row">
                    <span class="history-status">[注文済み]</span>
                    <span>${item.name}</span>
                    <span>${item.quantity}</span>
                    <span>${formatYen(item.price)}</span>

                    <button class="reorder-button" data-menu-id="${item.id}">
                        再注文
                    </button>
                </div>
            `);
        });

        if (rows.length === 0) {
            historyList.innerHTML = '<p class="empty-message">注文履歴はありません</p>';
            return;
        }

        historyList.innerHTML = rows.join('');

        historyList.querySelectorAll('.reorder-button').forEach(button => {
            button.addEventListener('click', () => {
                const historyItem = state.history.find(item => String(item.id) === String(button.dataset.menuId));
                if (!historyItem) return;

                const menu = findMenu(historyItem.id);
                if (!menu) return;

                // 再注文は前回の個数を引き継がず、常に個数1の状態で注文画面を開く
                openProduct(menu, 1, true);
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
            requestAnimationFrame(refreshCategoryScrollButtons);
        });

        document.getElementById('historyBackButton').addEventListener('click', () => {
            showScreen('menuScreen');
            requestAnimationFrame(refreshCategoryScrollButtons);
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
            requestAnimationFrame(refreshCategoryScrollButtons);
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
