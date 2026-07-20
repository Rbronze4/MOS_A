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

    // 数量変更をDBへ保存する。
    // 以前はフロントのstateだけ書き換えていたため、リロードすると元に戻っていた。
    async function postQuantityAction(orderDetailId, quantity) {
        const body = new URLSearchParams();
        body.set('order_detail_id', String(orderDetailId));
        body.set('quantity', String(quantity));

        const response = await fetch('/MOS_A/public/staff/order/quantity', {
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
            throw new Error(payload?.message || '注文数量の変更に失敗しました。');
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

    // 取消解除：キャンセル済みの明細を注文中に戻す（DBも更新する）。
    async function postRestoreAction(orderDetailIds) {
        const body = new URLSearchParams();

        orderDetailIds.forEach(orderDetailId => {
            body.append('order_detail_ids[]', String(orderDetailId));
        });

        const response = await fetch('/MOS_A/public/staff/order/restore', {
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
            throw new Error(payload?.message || '取消解除に失敗しました。');
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

        // 取得中・保留中の一覧はこの操作より前の内容になるため無効にする
        invalidatePendingOrders();
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

    // 一括取消解除：チェックした明細をまとめて注文中に戻す。
    async function restoreOrders(orderDetailIds) {
        const updatedOrders = await postRestoreAction(orderDetailIds);
        replaceOrders(updatedOrders);
        renderOrders();
        renderOrderDetail();

        return updatedOrders;
    }

    /**
     * 商品名に色を付けるためのCSSクラスを返す。
     *
     * 飲み放題プランの対象商品（plan_applied_flag=1）だけを色付きにして、
     * スタッフが「この注文は個別会計が不要」と一目で判断できるようにする。
     *
     * 以前は商品名の文字列一致（「ビール」を含むなど）で色を決めていたため、
     * 飲み放題対象でない商品にも色が付き、逆に対象商品が無色になっていた。
     * 判定は必ずDB由来の plan_applied_flag を使う。
     */
    function getProductColor(order) {
        return Number(order?.plan_applied_flag || 0) === 1 ? 'table-blue' : '';
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

                    <td class="${getProductColor(order)}">
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

                    // 取消解除：DBもキャンセル済み→注文中に戻す。
                    // これをしないと、DB上はキャンセルのままで提供数変更が拒否される。
                    if (action === 'undoCancel') {
                        const updatedOrders = await postRestoreAction([orderDetailId]);
                        replaceOrders(updatedOrders);
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

        // 提供済み数を下回る数量にはできない（サーバー側でも弾かれる）。
        // ボタンで下げられない下限を設けて、押しても失敗するだけの操作を防ぐ。
        const minQty = Math.max(1, Number(order.servedQty) || 0);

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
            qty = Math.max(minQty, qty - 1);
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
                    saveButton.disabled = false;
                }

                return;
            }

            // 数量に変更がなければサーバーへ送らない
            if (Number(qty) === Number(order.qty)) {
                closeModal();
                return;
            }

            saveButton.disabled = true;

            try {
                const updatedOrder = await postQuantityAction(order.order_detail_id ?? order.id, qty);
                replaceOrder(updatedOrder);

                renderOrderDetail();
                renderOrders();

                closeModal();
                openCompleteModal('注文の変更が完了しました。');
            } catch (error) {
                openCompleteModal(error.message || '注文数量の変更に失敗しました。');
                saveButton.disabled = false;
            }
        });
    }

    // ============================================================
    // 注文一覧の自動更新（タブレット運用向け）
    //
    // 客がスマホで注文してもスタッフの画面はひとりでに変わらないため、
    // 一定間隔でサーバーへ最新の注文一覧を問い合わせる。
    //
    // ただし取得のたびに描画すると、一括取消のチェックや編集中のモーダルが
    // 消えてしまう。提供ボタンを押す瞬間に行が入れ替わると誤操作にもつながる。
    // そのため「操作中は反映を保留し、バナーで知らせるだけ」にする。
    // ============================================================

    const POLL_INTERVAL_MS = 20000;

    // 取得済みだがまだ画面へ反映していない注文一覧。操作が終わったら反映する。
    let pendingOrders = null;
    let pollTimerId = null;

    /**
     * スタッフ操作の世代番号。
     *
     * 提供・取消などの操作は state.orders を直接書き換えるため、
     * その操作より前に始まった取得結果を後から反映すると、画面だけ操作前へ巻き戻る。
     * 操作のたびにこの番号を進め、取得開始時の番号と一致する結果だけを採用する。
     */
    let ordersGeneration = 0;

    /**
     * 取得リクエストの連番。
     *
     * 定期取得・手動更新・タブ復帰時の取得は同時に走り得る。
     * 応答が返る順序は保証されないため、あとから始めた取得のほうが先に返ることがある。
     * この番号で「最後に開始した取得」を覚えておき、それ以外の結果は破棄する。
     * （古い応答が後から届いて画面が巻き戻るのを防ぐ）
     */
    let latestRequestId = 0;

    /**
     * スタッフが注文を操作したことを記録する。
     *
     * 取得中だった古いレスポンスと、反映前に保留していた一覧を無効にする。
     * 保留分を捨てても、次の取得（最大20秒後）で最新に追いつく。
     */
    function invalidatePendingOrders() {
        ordersGeneration += 1;
        pendingOrders = null;
        renderUpdateBanner();
    }

    /**
     * いま画面を書き換えると操作の邪魔になるかどうか。
     *
     * ・モーダル表示中（数量編集などの入力が消えるため）
     * ・一括取消のチェックが1つ以上入っている（選択が外れるため）
     */
    function isStaffBusy() {
        const modalLayer = document.getElementById('modalLayer');

        if (modalLayer && modalLayer.classList.contains('show')) {
            return true;
        }

        return document.querySelectorAll('.order-checkbox:checked').length > 0;
    }

    // 新着を知らせるバナーの表示を更新する（件数は増えた注文の数ではなく変化の有無で出す）
    function renderUpdateBanner() {
        const banner = document.getElementById('orderUpdateBanner');

        if (!banner) {
            return;
        }

        banner.classList.toggle('show', pendingOrders !== null);
    }

    // 保留していた最新の注文一覧を画面へ反映する
    function applyPendingOrders() {
        if (pendingOrders === null) {
            return;
        }

        // 保留中にスタッフが提供・取消などを行っていた場合、この内容は操作前のもの。
        // 反映すると画面だけ巻き戻るため捨てて、次の取得で最新に追いつく。
        if (pendingOrders.generation !== ordersGeneration) {
            pendingOrders = null;
            renderUpdateBanner();
            return;
        }

        state.orders = pendingOrders.orders;
        pendingOrders = null;

        renderOrders();
        renderUpdateBanner();
    }

    /**
     * 取得した一覧が現在の表示と違うかどうか。
     * 毎回描画すると操作中でなくても画面がちらつくため、変化がある時だけ反映する。
     */
    function hasOrderChanges(latestOrders) {
        if (latestOrders.length !== state.orders.length) {
            return true;
        }

        const currentById = new Map(state.orders.map(order => [String(order.id), order]));

        return latestOrders.some(latest => {
            const current = currentById.get(String(latest.id));

            if (!current) {
                return true;
            }

            // 提供数・状態・数量が変われば画面の表示も変わる
            return current.status !== latest.status
                || Number(current.qty) !== Number(latest.qty)
                || Number(current.servedQty) !== Number(latest.servedQty);
        });
    }

    /**
     * 最新の注文一覧を取得する。
     *
     * @param {boolean} force 手動更新かどうか。
     *   自動更新(false)は操作中なら反映を保留するが、
     *   手動更新(true)はスタッフが自分で押した操作なので、その場で反映する。
     */
    async function fetchLatestOrders(force = false) {
        // 別タブへ切り替えている間は取得しない（無駄な通信とバッテリー消費を避ける）
        // ただし手動更新は、スタッフが今まさに見ているので必ず実行する。
        if (!force && document.hidden) {
            return;
        }

        // 取得を始めた時点の世代。応答が返るまでにスタッフが操作していたら、
        // この結果は操作前の内容なので破棄する（画面が巻き戻るのを防ぐ）。
        const requestedGeneration = ordersGeneration;

        // この取得の連番。あとから始まった取得が先に反映されていたら、こちらは古い。
        const requestId = ++latestRequestId;

        try {
            const response = await fetch('/MOS_A/public/staff/orders/latest', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            // セッション切れなどでログイン画面へ飛ばされた場合は静かに諦める
            if (!response.ok) {
                return;
            }

            const payload = await response.json();

            if (!payload || payload.ok !== true || !Array.isArray(payload.orders)) {
                return;
            }

            // より新しい取得が始まっている場合、この結果は古いので捨てる。
            // 定期取得・手動更新・タブ復帰の取得が並行したときに順序が入れ替わるのを防ぐ。
            if (requestId !== latestRequestId) {
                return;
            }

            // 通信中にスタッフが提供・取消などを行っていた場合は古い内容のため捨てる
            if (requestedGeneration !== ordersGeneration) {
                return;
            }

            if (!hasOrderChanges(payload.orders)) {
                // 手動更新では、変化が無くてもバナーを消して「最新の状態」を明確にする
                if (force) {
                    pendingOrders = null;
                    renderUpdateBanner();
                }

                return;
            }

            // どの世代の取得結果かを保持する。保留中にスタッフが操作したら破棄する。
            pendingOrders = { generation: requestedGeneration, orders: payload.orders };

            // 自動更新は操作中なら反映を保留してバナーで知らせるだけにする。
            // 手動更新はスタッフ自身の操作なので、そのまま反映してよい。
            if (!force && isStaffBusy()) {
                renderUpdateBanner();
                return;
            }

            applyPendingOrders();
        } catch (error) {
            // 通信断は次回の取得で回復するため、画面には出さない
        }
    }

    /**
     * 定期取得のタイマーを引き直す。
     *
     * 取得した直後は次の取得までフルに間隔を空けたい。
     * 手動更新の直後にタイマーが発火すると、取得が並行して手動分が
     * 「古い結果」として捨てられることがあるため、それも避けられる。
     */
    function restartPollTimer() {
        if (pollTimerId === null) {
            return;
        }

        clearInterval(pollTimerId);
        pollTimerId = setInterval(fetchLatestOrders, POLL_INTERVAL_MS);
    }

    /**
     * 更新ボタンから呼ぶ手動更新。
     * 押したことが分かるようボタンをグレーにし、取得中は連打を受け付けない。
     */
    async function refreshOrdersManually() {
        const button = document.getElementById('orderRefreshButton');

        if (button) {
            button.disabled = true;
            button.classList.add('is-loading');
        }

        // 取得の直後にタイマーが重ならないよう、次回までの間隔を取り直す
        restartPollTimer();

        try {
            await fetchLatestOrders(true);
        } finally {
            // 一瞬で戻ると押した実感が無いため、最低でも400msはグレーのままにする
            setTimeout(() => {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                }
            }, 400);
        }
    }

    function startOrderPolling() {
        if (pollTimerId !== null) {
            return;
        }

        // dashboard.jsはスタッフ注文入力・メニュー画面でも読み込まれる。
        // 注文一覧が無い画面で取得しても使い道がないため、DOMの有無で判定する。
        if (!document.getElementById('orderTableBody')) {
            return;
        }

        pollTimerId = setInterval(fetchLatestOrders, POLL_INTERVAL_MS);

        // 別タブから戻ってきた時は、次の周期を待たずに最新へ追いつく
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                restartPollTimer();
                fetchLatestOrders();
            }
        });

        const banner = document.getElementById('orderUpdateBanner');

        if (banner) {
            banner.addEventListener('click', applyPendingOrders);
        }

        const refreshButton = document.getElementById('orderRefreshButton');

        if (refreshButton) {
            refreshButton.addEventListener('click', refreshOrdersManually);
        }
    }

    return {
        renderOrders,
        setOrderTabActive,
        renderOrderDetail,
        openOrderEditModal,
        cancelOrders,
        restoreOrders,
        startOrderPolling
    };
};
