<?php
/**
 * スタッフ側 顧客別注文詳細画面。
 *
 * ログイン中店舗に属する指定顧客の order_details を表示し、
 * 1件ずつ数量変更またはキャンセルできるようにする。
 */
$customerDetail = $customerDetail ?? null;
$customerOrders = is_array($customerOrders ?? null) ? $customerOrders : [];
$customerOrderError = $customerOrderError ?? '';
$customerOrderMessage = $customerOrderMessage ?? '';
$customerId = (int)($_GET['customer_id'] ?? 0);
?>
<div class="staff-app">
    <section id="customerOrdersScreen" class="screen active">
        <div class="screen-header">
            <button class="back-button" type="button" onclick="location.href='/MOS_A/public/staff?ref=customerList'">←</button>
            <h1>注文詳細</h1>
            <button class="hamburger-button" type="button" aria-label="メニューを開く">☰</button>
        </div>

        <div class="customer-order-summary">
            <span>顧客番号：<?= h((string)$customerId) ?></span>
            <?php if ($customerDetail !== null): ?>
                <span>人数：<?= h((string)$customerDetail['people_count']) ?>人</span>
                <span>プラン：<?= h((string)$customerDetail['plan_label']) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($customerOrderMessage !== ''): ?>
            <p class="staff-message"><?= h($customerOrderMessage) ?></p>
        <?php endif; ?>

        <?php if ($customerOrderError !== ''): ?>
            <p class="error-text customer-order-error"><?= h($customerOrderError) ?></p>
        <?php endif; ?>

        <div class="order-table-scroll customer-orders-scroll">
            <table class="data-table customer-orders-table">
                <thead>
                    <tr>
                        <th>選択</th>
                        <th>商品名</th>
                        <th>数量</th>
                        <th>提供数</th>
                        <th>注文時間</th>
                        <th>状態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customerOrders === []): ?>
                        <tr>
                            <td colspan="6" class="empty-row">注文詳細はありません。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customerOrders as $order): ?>
                            <?php
                            $orderDetailId = (int)$order['order_detail_id'];
                            $quantity = (int)$order['qty'];
                            $providedQuantity = (int)$order['servedQty'];
                            $detailStatus = (string)$order['detail_status'];
                            $isCancelled = $detailStatus === 'CANCELLED';
                            $canCancel = !$isCancelled && $providedQuantity === 0;
                            $canUpdate = !$isCancelled;
                            ?>
                            <tr>
                                <td>
                                    <input
                                        type="radio"
                                        name="selectedCustomerOrder"
                                        class="customer-order-radio"
                                        value="<?= h((string)$orderDetailId) ?>"
                                        data-product-name="<?= h((string)$order['name']) ?>"
                                        data-quantity="<?= h((string)$quantity) ?>"
                                        data-provided-quantity="<?= h((string)$providedQuantity) ?>"
                                        data-can-update="<?= $canUpdate ? '1' : '0' ?>"
                                        data-can-cancel="<?= $canCancel ? '1' : '0' ?>"
                                    >
                                </td>
                                <td><?= h((string)$order['name']) ?></td>
                                <td><?= h((string)$quantity) ?></td>
                                <td><?= h((string)$providedQuantity) ?></td>
                                <td><?= h((string)$order['ordered_at_label']) ?></td>
                                <td><?= h((string)$order['status_label']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="bottom-right customer-orders-actions">
            <button id="customerOrderEditButton" class="white-button" type="button">注文編集</button>
        </div>
    </section>

    <div id="customerOrderEditModal" class="staff-edit-modal" aria-hidden="true">
        <div class="staff-edit-modal-panel">
            <h2>注文編集</h2>

            <p id="editOrderProductName" class="staff-edit-product-name"></p>

            <form method="post" action="/MOS_A/public/staff/customer/orders/update" class="staff-edit-form">
                <input type="hidden" name="customer_id" value="<?= h((string)$customerId) ?>">
                <input id="editOrderDetailId" type="hidden" name="order_detail_id" value="">

                <label class="staff-edit-field">
                    <span>現在の数量</span>
                    <input id="currentOrderQuantity" type="text" value="" readonly>
                </label>

                <label class="staff-edit-field">
                    <span>提供済み数</span>
                    <input id="currentProvidedQuantity" type="text" value="" readonly>
                </label>

                <label class="staff-edit-field">
                    <span>新しい数量</span>
                    <input id="editOrderQuantity" type="number" name="quantity" min="1" step="1" value="1">
                </label>

                <div class="form-buttons">
                    <button id="saveCustomerOrderButton" class="white-button" type="submit">保存</button>
                    <button id="cancelCustomerOrderButton" class="white-button" type="submit" formaction="/MOS_A/public/staff/customer/orders/cancel">キャンセルする</button>
                    <button id="closeCustomerOrderModalButton" class="white-button" type="button">閉じる</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    $staffSideMenuMode = 'link';
    require dirname(__DIR__) . '/parts/side_menu.php';
    unset($staffSideMenuMode);
    ?>
</div>

<script>
(() => {
    const modal = document.getElementById('customerOrderEditModal');
    const openButton = document.getElementById('customerOrderEditButton');
    const closeButton = document.getElementById('closeCustomerOrderModalButton');
    const productName = document.getElementById('editOrderProductName');
    const orderDetailId = document.getElementById('editOrderDetailId');
    const currentQuantity = document.getElementById('currentOrderQuantity');
    const providedQuantity = document.getElementById('currentProvidedQuantity');
    const editQuantity = document.getElementById('editOrderQuantity');
    const saveButton = document.getElementById('saveCustomerOrderButton');
    const cancelButton = document.getElementById('cancelCustomerOrderButton');

    function selectedOrder() {
        return document.querySelector('input[name="selectedCustomerOrder"]:checked');
    }

    function openModal() {
        const selected = selectedOrder();

        if (!selected) {
            alert('編集する注文を選択してください。');
            return;
        }

        const quantity = Number(selected.dataset.quantity || '1');
        const served = Number(selected.dataset.providedQuantity || '0');
        const canUpdate = selected.dataset.canUpdate === '1';
        const canCancel = selected.dataset.canCancel === '1';

        productName.textContent = selected.dataset.productName || '';
        orderDetailId.value = selected.value;
        currentQuantity.value = String(quantity);
        providedQuantity.value = String(served);
        editQuantity.value = String(Math.max(1, quantity));
        editQuantity.min = String(Math.max(1, served));
        editQuantity.disabled = !canUpdate;
        saveButton.disabled = !canUpdate;
        cancelButton.disabled = !canCancel;

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.customer-orders-table tbody tr').forEach(row => {
        const radio = row.querySelector('.customer-order-radio');

        if (!radio) return;

        row.addEventListener('click', () => {
            document.querySelectorAll('.customer-orders-table tbody tr').forEach(target => {
                target.classList.remove('selected-row');
            });

            row.classList.add('selected-row');
            radio.checked = true;
        });

        radio.addEventListener('click', event => {
            event.stopPropagation();
            row.click();
        });
    });

    openButton?.addEventListener('click', openModal);
    closeButton?.addEventListener('click', closeModal);

    cancelButton?.addEventListener('click', event => {
        if (!confirm('この注文をキャンセルしますか？')) {
            event.preventDefault();
        }
    });
})();
</script>
