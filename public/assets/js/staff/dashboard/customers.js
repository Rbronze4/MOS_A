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
