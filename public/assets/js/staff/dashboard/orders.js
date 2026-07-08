/**
 * スタッフダッシュボード 注文一覧モジュール。
 * 注文一覧の表示、提供数の更新、キャンセル更新、注文詳細の描画を担当します。
 */
window.MOS = window.MOS || {};
window.MOS.staffDashboard = window.MOS.staffDashboard || {};

window.MOS.staffDashboard.createOrderModule = function createOrderModule(context) {
    const {
        state,
        openModal,
        closeModal,
        openCompleteModal
    } = context;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    async function postProvisionAction(orderDetailId, action) {
        const body = new URLSearchParams();
        body.set('order_detail_id', String(orderDetailId));
        body.set('action', action);

        const response = await fetch('/MOS_A/public/staff/order/provision', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body
        });

        const payload = await parseJson(response);

        if (!response.ok || !payload || payload.ok !== true) {
            throw new Error(payload?.message || '提供数の更新に失敗しました。');
        }

        return payload.order;
    }

    async function postCancelAction(orderDetailIds) {
        const body = new URLSearchParams();

        orderDetailIds.forEach(orderDetailId => {
            body.append('order_detail_ids[]', String(orderDetailId));
        });

        const response = await fetch('/MOS_A/public/staff/order/cancel', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body
        });

        const payload = await parseJson(response);

        if (!response.ok || !payload || payload.ok !== true) {
            throw new Error(payload?.message || '注文のキャンセルに失敗しました。');
        }

        return payload.orders || [];
    }

    async function parseJson(response) {
        try {
            return await response.json();
        } catch (error) {
            return null;
        }
    }

    function replaceOrder(updatedOrder) {
        const index = state.orders.findIndex(item => String(item.id) === String(updatedOrder.id));

        if (index >= 0) {
            state.orders[index] = {
                ...state.orders[index],
                ...updatedOrder
            };
        }
    }

    function replaceOrders(updatedOrders) {
        updatedOrders.forEach(replaceOrder);
    }

    async function cancelOrders(orderDetailIds) {
        const updatedOrders = await postCancelAction(orderDetailIds);
        replaceOrders(updatedOrders);
        renderOrders();
        renderOrderDetail();

        return updatedOrders;
    }

    function getProductColor(name) {
        const productName = String(name);

        if (
            productName.includes('ビール') ||
            productName.includes('枝豆') ||
            productName.includes('もも')
        ) {
            return 'table-blue';
        }

        if (
            productName.includes('とりかわ') ||
            productName.includes('チキン')
        ) {
            return 'table-orange';
        }

        if (
            productName.includes('赤') ||
            productName.includes('ハイ')
        ) {
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

        // タブごとに該当する注文がない場合は、空メッセージを表示します。
        if (orders.length === 0) {
            const emptyMessage = state.orderMode === 'waiting'
                ? '注文中の商品はありません'
                : state.orderMode === 'served'
                    ? '提供済みの商品はありません'
                    : 'キャンセルされた注文はありません';

            body.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-row">${emptyMessage}</td>
                </tr>
            `;

            const bulkCancelButton = document.getElementById('bulkCancelButton');
            if (bulkCancelButton) {
                bulkCancelButton.setAttribute('disabled', 'true');
            }

            return;
        }

        body.innerHTML = orders.map(order => {
            let actionButtons = '';
            const orderDetailId = order.order_detail_id ?? order.id;
            const escapedTableNo = escapeHtml(order.table_no);
            const escapedName = escapeHtml(order.name);

            if (state.orderMode === 'waiting') {
                actionButtons = `
                    <button class="row-button green-button" data-action="serveOne" data-id="${order.id}" data-order-detail-id="${orderDetailId}">1つ提供</button>
                    <button class="row-button green-button" data-action="serveAll" data-id="${order.id}" data-order-detail-id="${orderDetailId}">全て提供</button>
                    <button class="row-button red-button" data-action="minusOne" data-id="${order.id}" data-order-detail-id="${orderDetailId}">1つ減らす</button>
                `;
            }

            if (state.orderMode === 'served') {
                actionButtons = `
                    <button class="row-button red-button" data-action="undoServe" data-id="${order.id}" data-order-detail-id="${orderDetailId}">提供取消</button>
                `;
            }

            if (state.orderMode === 'canceled') {
                actionButtons = `
                    <button class="row-button red-button" data-action="undoCancel" data-id="${order.id}" data-order-detail-id="${orderDetailId}">取消解除</button>
                `;
            }

            return `
                <tr data-order-detail-id="${orderDetailId}">
                    <td>
                        <input type="checkbox" class="order-checkbox" data-id="${order.id}" data-order-detail-id="${orderDetailId}">
                    </td>

                    <td class="${order.table_no === '12番' || order.table_no === '3番' ? 'table-red' : ''}">
                        ${escapedTableNo}
                    </td>

                    <td class="${getProductColor(order.name)}">
                        ${escapedName}
                    </td>

                    <td>${order.qty}</td>

                    <td>${order.servedQty}/${order.qty}</td>

                    <td>
                        <div class="order-actions">${actionButtons}</div>
                    </td>
                </tr>
            `;
        }).join('');

        body.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', async () => {
                const order = state.orders.find(item => String(item.id) === String(button.dataset.id));
                if (!order) return;

                const action = button.dataset.action;
                const orderDetailId = button.dataset.orderDetailId || order.order_detail_id || order.id;

                button.disabled = true;

                try {
                    if (['serveOne', 'serveAll', 'minusOne', 'undoServe'].includes(action)) {
                        const updatedOrder = await postProvisionAction(orderDetailId, action);
                        replaceOrder(updatedOrder);
                    }

                    // 取消解除はDB連携未実装の既存挙動を維持しています。
                    if (action === 'undoCancel') {
                        order.status = 'waiting';

                        if (order.qty <= 0) {
                            order.qty = 1;
                        }

                        order.servedQty = 0;
                    }

                    renderOrders();
                    renderOrderDetail();
                } catch (error) {
                    openCompleteModal(error.message || '提供数の更新に失敗しました。');
                } finally {
                    button.disabled = false;
                }
            });
        });

        const bulkCancelButton = document.getElementById('bulkCancelButton');
        if (bulkCancelButton) {
            const checkboxes = body.querySelectorAll('.order-checkbox');

            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const checkedCount = body.querySelectorAll('.order-checkbox:checked').length;

                    if (checkedCount > 0) {
                        bulkCancelButton.removeAttribute('disabled');
                    } else {
                        bulkCancelButton.setAttribute('disabled', 'true');
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

        if (orders.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="4" class="empty-row">注文はありません</td>
                </tr>
            `;
            return;
        }

        body.innerHTML = orders.map(order => {
            const selectedClass = String(order.id) === String(state.selectedOrderDetailId)
                ? 'selected-row'
                : '';

            const checked = String(order.id) === String(state.selectedOrderDetailId)
                ? 'checked'
                : '';
            const orderDetailId = order.order_detail_id ?? order.id;

            return `
                <tr class="${selectedClass}" data-order-id="${order.id}" data-order-detail-id="${orderDetailId}">
                    <td>
                        <input
                            type="radio"
                            name="selectedOrderDetail"
                            class="order-detail-radio"
                            value="${order.id}"
                            data-order-detail-id="${orderDetailId}"
                            ${checked}
                        >
                    </td>
                    <td>${escapeHtml(order.name)}</td>
                    <td>${order.qty}</td>
                    <td>${escapeHtml(order.time)}</td>
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

    function openOrderEditModal() {
        const orderId = state.selectedOrderDetailId;
        const order = state.orders.find(item => String(item.id) === String(orderId));

        if (!order) {
            openCompleteModal('注文データが見つかりません。');
            return;
        }

        let qty = order.qty;

        openModal(`
            <div class="edit-modal">
                <div class="edit-row">
                    <span>個数変更</span>
                    <div class="qty-control">
                        <button id="minusQtyButton">-</button>
                        <span id="editQtyValue">${qty}</span>
                        <button id="plusQtyButton">+</button>
                    </div>
                </div>

                <div class="edit-row">
                    <span>注文削除</span>
                    <input id="deleteOrderCheck" class="delete-check" type="checkbox">
                </div>

                <div class="form-buttons">
                    <button id="saveOrderEditButton" class="white-button">決定</button>
                    <button id="cancelOrderEditButton" class="white-button">キャンセル</button>
                </div>
            </div>
        `);

        document.getElementById('cancelOrderEditButton').addEventListener('click', closeModal);

        document.getElementById('minusQtyButton').addEventListener('click', () => {
            qty = Math.max(1, qty - 1);
            document.getElementById('editQtyValue').textContent = qty;
        });

        document.getElementById('plusQtyButton').addEventListener('click', () => {
            qty += 1;
            document.getElementById('editQtyValue').textContent = qty;
        });

        document.getElementById('saveOrderEditButton').addEventListener('click', async () => {
            const deleteCheck = document.getElementById('deleteOrderCheck');
            const saveButton = document.getElementById('saveOrderEditButton');

            if (deleteCheck && deleteCheck.checked) {
                if (!confirm('この注文をキャンセルしてもよろしいですか？')) {
                    return;
                }

                saveButton.disabled = true;

                try {
                    await cancelOrders([order.order_detail_id ?? order.id]);
                    closeModal();
                    openCompleteModal('注文をキャンセルしました。');
                } catch (error) {
                    openCompleteModal(error.message || '注文のキャンセルに失敗しました。');
                }

                return;
            }

            order.qty = qty;

            if (order.servedQty > order.qty) {
                order.servedQty = order.qty;
            }

            renderOrderDetail();
            renderOrders();

            openCompleteModal('注文の変更が完了しました。');
        });
    }

    return {
        renderOrders,
        setOrderTabActive,
        renderOrderDetail,
        openOrderEditModal,
        cancelOrders
    };
};
