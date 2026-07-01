/**
 * スタッフ ダッシュボード モジュール：注文。
 * 注文一覧（注文中/提供済み/キャンセル）の描画、提供・取消・一括キャンセル等の操作、
 * 注文詳細の描画、注文編集モーダルを担当する。dashboard.js から context を受け取り生成。
 *
 * 主な関数: renderOrders() / setOrderTabActive() / renderOrderDetail() / openOrderEditModal()
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

        // 該当する注文が0件のときは、空の表だけが残って分かりづらいので、
        // タブ（注文中/提供済み/キャンセル）に応じた空メッセージを1行表示する
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

            // 0件時は選択できる注文がないので、一括キャンセルボタンは無効に戻す
            const bulkCancelButton = document.getElementById('bulkCancelButton');
            if (bulkCancelButton) {
                bulkCancelButton.setAttribute('disabled', 'true');
            }

            return;
        }

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

        // 表示できる注文明細が0件のときは、空メッセージを1行表示する
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

    function openOrderEditModal() {
        const orderId = state.selectedOrderDetailId;
        const order = state.orders.find(item => String(item.id) === String(orderId));

        if (!order) {
            openCompleteModal('注文データが見つかりません');
            return;
        }

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
                    <button id="cancelOrderEditButton" class="white-button">キャンセル</button>
                </div>
            </div>
        `);

        // キャンセル：変更を保存せずモーダルを閉じるだけ（枠外クリックと同じ挙動）
        document.getElementById('cancelOrderEditButton').addEventListener('click', closeModal);

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

            if (deleteCheck && deleteCheck.checked) {
                if (confirm('この注文をキャンセルしてもよろしいですか？')) {
                    order.status = 'canceled';
                    renderOrderDetail();
                    renderOrders();
                    openCompleteModal('注文をキャンセルしました');
                }
                return;
            }

            order.qty = qty;

            if (order.servedQty > order.qty) {
                order.servedQty = order.qty;
            }

            renderOrderDetail();
            renderOrders();

            openCompleteModal('注文の変更が完了しました');
        });
    }

    return {
        renderOrders,
        setOrderTabActive,
        renderOrderDetail,
        openOrderEditModal
    };
};
