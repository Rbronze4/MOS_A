window.MOS = window.MOS || {};
window.MOS.staffDashboard = window.MOS.staffDashboard || {};

window.MOS.staffDashboard.createQrModule = function createQrModule(context) {
    const {
        openModal,
        closeModal
    } = context;

    function issueCustomerNumber() {
        return String(Math.floor(Math.random() * 10000000)).padStart(7, '0');
    }

    function openQrCompleteModal(messagePrefix = 'QR発行が完了しました') {
        const number = issueCustomerNumber();

        openModal(`
            <h2>${messagePrefix}</h2>
            <div>顧客番号</div>
            <div class="generated-number">${number}</div>
            <button class="white-button" id="closeModalButton">閉じる</button>
        `);

        document.getElementById('closeModalButton').addEventListener('click', closeModal);
    }

    return {
        openQrCompleteModal
    };
};
