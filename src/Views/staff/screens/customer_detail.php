<?php
$customerDetail = $customerDetail ?? null;
$customerDetailError = $customerDetailError ?? '';
$tableNumbers = is_array($customerDetail['table_numbers'] ?? null)
    ? $customerDetail['table_numbers']
    : [];
?>
<div class="staff-app">
    <section id="customerDetailScreen" class="screen active">
        <div class="screen-header">
            <button class="back-button" type="button" onclick="location.href='/MOS_A/public/staff?ref=customerList'">←</button>
            <h1>顧客詳細</h1>
            <button class="hamburger-button" type="button" aria-label="メニューを開く">☰</button>
        </div>

        <?php if ($customerDetail === null): ?>
            <div class="customer-detail-card">
                <p class="error-text"><?= h($customerDetailError !== '' ? $customerDetailError : '顧客情報が見つかりません。') ?></p>
            </div>
        <?php else: ?>
            <?php
            $customerId = (string)$customerDetail['customer_id'];
            // 登録状況は画面表示値ではなく、注文開始ServiceがDBから再判定する。
            $staffOrderHref = '/MOS_A/public/staff/order-entry?customer_id=' . urlencode($customerId) . '&ref=customerDetail';
            ?>
            <div class="customer-detail-card">
                <table class="data-table customer-detail-table">
                    <tbody>
                        <tr>
                            <th>顧客番号</th>
                            <td><?= h($customerId) ?></td>
                        </tr>
                        <tr>
                            <th>人数</th>
                            <td><?= h((string)$customerDetail['people_count']) ?>人</td>
                        </tr>
                        <tr>
                            <th>飲み放題プラン</th>
                            <td><?= h((string)$customerDetail['plan_label']) ?></td>
                        </tr>
                        <tr>
                            <th>卓番号</th>
                            <td>
                                <?php if ($tableNumbers === []): ?>
                                    なし
                                <?php else: ?>
                                    <?= h(implode('、', $tableNumbers)) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>会計状態</th>
                            <td><?= h((string)$customerDetail['billing_status_label']) ?></td>
                        </tr>
                    </tbody>
                </table>
                <div class="customer-detail-actions">
                    <button
                        class="white-button"
                        type="button"
                        onclick="location.href='/MOS_A/public/staff/customer/orders?customer_id=<?= h($customerId) ?>'"
                    >注文詳細</button>
                    <?php
                    // 会計を通した顧客はスタッフ注文できないため、そもそも押せなくする。
                    // 押せてしまうと画面に入った先でエラーになり、操作が無駄になる。
                    $canStaffOrder = ($customerDetail['can_staff_order'] ?? true) === true;
                    ?>
                    <button
                        class="white-button"
                        type="button"
                        <?= $canStaffOrder ? '' : 'disabled' ?>
                        <?php if ($canStaffOrder): ?>
                            onclick="location.href='<?= h($staffOrderHref) ?>'"
                        <?php else: ?>
                            title="お会計が完了しているため、スタッフ注文はご利用いただけません。"
                        <?php endif; ?>
                    >スタッフ注文</button>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <?php
    $staffSideMenuMode = 'link';
    require dirname(__DIR__) . '/parts/side_menu.php';
    unset($staffSideMenuMode);
    ?>
</div>
