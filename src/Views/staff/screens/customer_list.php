<?php
/**
 * スタッフ：顧客一覧画面。
 *
 * 顧客はQR発行のたびに増え、会計後も行が残るため、会計状況で絞り込んで表示する。
 * 既定は「会計前」＝いま店にいる客。タブはページ再読込でサーバー側から絞り込む
 * （スタッフ注文画面のカテゴリタブと同じ方式）。
 */
$customers = $customers ?? [];
$customerFilter = $customerFilter ?? StaffCustomerModel::DEFAULT_FILTER;

$customerFilterTabs = [
    StaffCustomerModel::FILTER_SEATED => '着席中',
    StaffCustomerModel::FILTER_NO_TABLE => '卓未入力',
    StaffCustomerModel::FILTER_UNPAID => '会計前',
    StaffCustomerModel::FILTER_PAID => '会計済み',
    StaffCustomerModel::FILTER_UNRECOVERED => '未収金',
    StaffCustomerModel::FILTER_ALL => '全体',
];

$customerEmptyMessages = [
    StaffCustomerModel::FILTER_SEATED => '着席中の顧客はいません',
    StaffCustomerModel::FILTER_NO_TABLE => '卓番号が未入力の顧客はいません',
    StaffCustomerModel::FILTER_UNPAID => '会計前の顧客はいません',
    StaffCustomerModel::FILTER_PAID => '会計済みの顧客はいません',
    StaffCustomerModel::FILTER_UNRECOVERED => '未収金の顧客はいません',
    StaffCustomerModel::FILTER_ALL => '顧客情報がありません',
];
?>
<section id="customerListScreen" class="screen customer-list-screen">
    <div class="screen-header">
        <button class="back-button" type="button">←</button>
        <h1>顧客一覧</h1>
        <button class="hamburger-button" type="button">☰</button>
    </div>

    <div class="customer-list-top">
        <nav class="customer-filter-tabs">
            <?php foreach ($customerFilterTabs as $filterKey => $filterLabel): ?>
                <a
                    href="/MOS_A/public/staff?ref=customerList&status=<?= h($filterKey) ?>"
                    class="<?= $filterKey === $customerFilter ? 'active' : '' ?>"
                ><?= h($filterLabel) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="order-table-scroll customer-list-scroll">
        <table class="data-table customer-table">
            <thead>
                <tr>
                    <th>選択</th>
                    <th>卓番号</th>
                    <th>顧客番号</th>
                    <th>人数</th>
                    <th>会計状態</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($customers === []): ?>
                    <tr>
                        <td colspan="6" class="empty-row">
                            <?= h($customerEmptyMessages[$customerFilter] ?? '顧客情報がありません') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
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
                            <td><?= h((string)($customer['billing_status_label'] ?? '')) ?></td>
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
    </div>

    <div class="bottom-buttons">
        <button id="customerOrderDetailButton" class="white-button" type="button">注文詳細</button>
        <button id="staffOrderFromCustomerButton" class="white-button" type="button">スタッフ注文</button>
        <button id="qrReissueButton" class="white-button" type="button">QR再発行</button>
    </div>
</section>
