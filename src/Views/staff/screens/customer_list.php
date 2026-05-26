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
                <th>卓番</th>
                <th>客番号</th>
                <th>人数</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $index => $customer): ?>
                <tr>
                    <td>
                        <input
                            type="radio"
                            name="selectedCustomer"
                            value="<?= h((string)$index) ?>"
                            class="customer-radio"
                        >
                    </td>
                    <td><?= h($customer['table_no']) ?></td>
                    <td><?= h($customer['customer_no']) ?></td>
                    <td><?= h((string)$customer['people']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="bottom-buttons">
        <button id="customerOrderDetailButton" class="white-button" type="button">注文詳細</button>
        <button id="qrReissueButton" class="white-button" type="button">QR再発行</button>
    </div>
</section>