<?php
/**
 * スタッフ側 顧客一覧画面。
 * ログイン中店舗の顧客を表示し、顧客詳細画面へ遷移できるようにします。
 */
?>
<section id="customerListScreen" class="screen">
    <div class="screen-header">
        <button class="back-button" type="button">←</button>
        <h1>顧客詳細</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <table class="data-table customer-table">
        <thead>
            <tr>
                <th>選択</th>
                <th>卓番号</th>
                <th>顧客番号</th>
                <th>人数</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="5" class="empty-row">顧客情報がありません</td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $index => $customer): ?>
                    <?php $customerId = (string)($customer['customer_id'] ?? $customer['customer_no'] ?? ''); ?>
                    <tr>
                        <td>
                            <input
                                type="radio"
                                name="selectedCustomer"
                                value="<?= h($customerId) ?>"
                                data-customer-id="<?= h($customerId) ?>"
                                class="customer-radio"
                            >
                        </td>
                        <td><?= h((string)$customer['table_no']) ?></td>
                        <td><?= h((string)$customer['customer_no']) ?></td>
                        <td><?= h((string)$customer['people']) ?></td>
                        <td>
                            <button
                                class="row-button"
                                type="button"
                                onclick="location.href='/MOS_A/public/staff/customer/detail?customer_id=<?= h($customerId) ?>'"
                            >詳細</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="bottom-buttons">
        <button id="customerOrderDetailButton" class="white-button" type="button">注文詳細</button>
        <button id="qrReissueButton" class="white-button" type="button">QR再発行</button>
    </div>
</section>
