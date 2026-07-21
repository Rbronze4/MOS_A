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
        planTaxRate,
        taxIncludedPrice,
        formatYen,
        findMenu,
        findPlan,
        showScreen,
        showToast,
        getDisplayPrice,
        openProduct,
        refreshCategoryScrollButtons,
        deleteCartFromServer,
        submitOrderToServer
    } = context;

    // 商品名はスタッフが商品管理画面から自由に登録でき、そのままDB経由で
    // ここに届く。innerHTMLへ差し込む値は必ずエスケープする。
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function headcount() {
        const count = Number(state.peopleCount || 2);
        return Number.isFinite(count) && count > 0 ? count : 2;
    }

    function cartTotal() {
        return state.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    }

    // 選択中のコース（飲み放題プラン）。飲み放題なし(single,料金0)や未選択時は null 扱い。
    function selectedCoursePlan() {
        const plan = findPlan(state.selectedPlanId);
        if (!plan) {
            return null;
        }

        // 単価はDBのcustomer_plans（プラン確定時に登録された実際の単価）だけを使う。
        // 画面用のプラン定義は価格を持たない（店舗・制限時間で価格が変わるため）。
        // この単価は税抜のため、表示・合計では必ず税込にする。
        const dbPlan = state.activeCustomerPlan || null;
        const netUnitPrice = Number(dbPlan?.unit_price ?? dbPlan?.price ?? 0);

        if (netUnitPrice <= 0) {
            return null;
        }

        return {
            ...plan,
            netPrice: netUnitPrice,
            price: taxIncludedPrice(netUnitPrice, planTaxRate)
        };
    }

    // コース料金の合計（税込）。コースなしは0。
    // 「税抜合計×税率」で求める。単価を税込にしてから人数を掛けると端数処理が先に入り、
    // 税抜合計へ課税するレジ側の計算とずれることがある。
    function courseTotal() {
        const plan = selectedCoursePlan();
        return plan ? taxIncludedPrice(plan.netPrice * headcount(), planTaxRate) : 0;
    }

    function historyTotal() {
        const itemsTotal = state.history.reduce((sum, item) => sum + item.price * item.quantity, 0);
        return itemsTotal + courseTotal();
    }

    // 一人当たりの金額（合計 ÷ 人数）。端数は切り上げ。
    function perPersonTotal() {
        return Math.ceil(historyTotal() / headcount());
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
                <span class="cart-item-main">
                    ${escapeHtml(item.name)}
                    ${(item.options || []).length > 0
                        ? `<small class="cart-item-options">${escapeHtml(item.options.map(option => option.name).join('、'))}</small>`
                        : ''}
                </span>
                <span>${escapeHtml(item.quantity)}</span>
                <span>${formatYen(item.price)}</span>

                <button class="pill-button change-button" data-action="change" data-cart-detail-id="${escapeHtml(item.cart_detail_id)}">
                    変更
                </button>

                <button class="pill-button delete-button" data-action="delete" data-cart-detail-id="${escapeHtml(item.cart_detail_id)}">
                    削除
                </button>
            </div>
        `).join('');

        cartList.querySelectorAll('.pill-button').forEach(button => {
            button.addEventListener('click', async () => {
                const cartDetailId = button.dataset.cartDetailId;
                const action = button.dataset.action;
                const cartItem = state.cart.find(item => String(item.cart_detail_id) === String(cartDetailId));

                if (!cartItem) return;

                if (action === 'delete') {
                    // 削除前に確認ダイアログを表示
                    if (!confirm(`「${cartItem.name}」をカートから削除しますか？`)) {
                        return;
                    }

                    try {
                        const result = await deleteCartFromServer(cartDetailId);

                        state.cart = result.cart_items || [];
                        renderCart();
                        showToast(result.message || `${cartItem.name}を削除しました`);
                    } catch (error) {
                        showToast(error.message || '削除に失敗しました');
                    }

                    return;
                }

                if (action === 'change') {
                    const menu = findMenu(cartItem.id);
                    if (!menu) return;

                    state.editingItem = cartItem;
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
                <span>
                    ${escapeHtml(item.name)}
                    ${(item.options || []).length > 0
                        ? `<small class="cart-item-options">${escapeHtml(item.options.map(option => option.name).join('、'))}</small>`
                        : ''}
                </span>
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
            headcountEl.textContent = String(headcount());
        }

        const historyList = document.getElementById('historyList');

        const rows = [];

        // コース料金を注文の一つとして先頭に表示（数量列に人数、金額列に料金×人数）。
        const coursePlan = selectedCoursePlan();
        if (coursePlan) {
            rows.push(`
                <div class="history-row">
                    <span class="history-status">[コース]</span>
                    <span>${escapeHtml(coursePlan.name)}</span>
                    <span>${headcount()}名</span>
                    <span>${formatYen(courseTotal())}</span>
                </div>
            `);
        }

        state.history.forEach(item => {
            rows.push(`
                <div class="history-row">
                    <span class="history-status">[注文済み]</span>
                    <span>
                        ${escapeHtml(item.name)}
                        ${item.option_summary ? `<small class="cart-item-options">${escapeHtml(item.option_summary)}</small>` : ''}
                    </span>
                    <span>${escapeHtml(item.quantity)}</span>
                    <span>${formatYen(item.price)}</span>

                    <button class="reorder-button" data-menu-id="${escapeHtml(item.id)}">
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

        document.getElementById('submitOrderButton').addEventListener('click', async () => {
            if (!state.sessionId) {
                showToast('卓番号とプランを選択してください');
                return;
            }

            if (state.cart.length === 0) {
                showToast('商品が選択されていません');
                return;
            }

            try {
                const result = await submitOrderToServer();

                // 注文履歴はDBのorders/order_detailsから、顧客ID単位で再取得した結果を使う。
                state.history = result.history_items || [];
                state.cart = result.cart_items || [];

                closeOrderModal();
                renderCart();
                renderHistory();

                showToast(result.message || '注文を送信しました');
                showScreen('menuScreen');
                requestAnimationFrame(refreshCategoryScrollButtons);
            } catch (error) {
                showToast(error.message || '注文送信に失敗しました');
            }
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
