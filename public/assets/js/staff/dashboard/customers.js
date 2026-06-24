/**
 * スタッフ ダッシュボード モジュール：顧客（卓）選択。
 * 顧客詳細一覧の行選択（ラジオ）と選択状態のハイライトを担当する。
 * dashboard.js から context を受け取り生成。
 *
 * 主な関数: setupCustomerSelection()
 */
window.MOS = window.MOS || {};
window.MOS.staffDashboard = window.MOS.staffDashboard || {};

window.MOS.staffDashboard.createCustomerModule = function createCustomerModule(context) {
    const { state } = context;

    function setupCustomerSelection() {
        const rows = document.querySelectorAll('#customerListScreen tbody tr');

        rows.forEach(row => {
            const radio = row.querySelector('input[name="selectedCustomer"]');

            if (!radio) return;

            const updateSelectedRow = () => {
                rows.forEach(targetRow => {
                    targetRow.classList.remove('selected-row');
                });

                row.classList.add('selected-row');
                radio.checked = true;
                state.selectedCustomerIndex = radio.value;
            };

            row.addEventListener('click', () => {
                updateSelectedRow();
            });

            radio.addEventListener('click', event => {
                event.stopPropagation();
                updateSelectedRow();
            });
        });
    }

    return {
        setupCustomerSelection
    };
};
