/**
 * スタッフ ダッシュボード モジュール：QR発行。
 * 発行時にサーバー(/staff/qr/issue)で顧客を連番でDB登録し、返ってきた
 * customer_id で客側URLのQRコードを表示する。発行された顧客はそのまま客として利用できる。
 * dashboard.js から context を受け取り生成。
 *
 * 主な関数:
 *   issueCustomer()      … 新規顧客を連番で発行し、QRを表示（発行ボタン用）
 *   openQrCompleteModal()… 既存の customer_id を指定してQRを表示（再発行ボタン用）
 */
window.MOS = window.MOS || {};
window.MOS.staffDashboard = window.MOS.staffDashboard || {};

window.MOS.staffDashboard.createQrModule = function createQrModule(context) {
    const {
        openModal,
        closeModal
    } = context;

    // QRコード生成ライブラリ（QRious）を動的に読み込む。
    // 2回目以降は再読込しない。
    function loadQrLibrary(callback) {
        if (typeof QRious !== 'undefined') {
            callback();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
        script.onload = callback;
        document.head.appendChild(script);
    }

    // 客側注文画面のURLを組み立てる。
    // ※ アプリは /MOS_A/public 配下で動くため、そのベースパスを必ず含める
    //   （含めないとQRを読み込んでも 404 になり、客側が開けない）。
    function buildOrderUrl(customerId) {
        return `${window.location.origin}/MOS_A/public/customer?customer_id=${encodeURIComponent(customerId)}`;
    }

    // 指定した customer_id でQRコード表示モーダルを開く（表示専用）。
    // isReissue を true にすると、印刷ページに「再発行」ラベルが表示される。
    // printCount は印刷する枚数。同じ顧客が複数の卓に分かれる場合に使う。
    function openQrCompleteModal(
        customerId,
        messagePrefix = 'QR発行が完了しました。',
        isReissue = false,
        printCount = 1
    ) {
        const orderUrl = buildOrderUrl(customerId);
        const sheetCount = Math.min(99, Math.max(1, Number(printCount) || 1));

        openModal(`
            <h2>${messagePrefix}</h2>
            <div>顧客番号</div>
            <div class="generated-number">${customerId}</div>

            <div class="qr-container" style="margin: 20px 0; text-align: center;">
                <canvas id="qrcode-canvas"></canvas>
            </div>

            <div style="font-size: 12px; word-break: break-all; margin-bottom: 20px; color: #666;">
                アクセスURL: ${orderUrl}
            </div>

            <button class="white-button" id="printQrButton">
                ${sheetCount > 1 ? `印刷（${sheetCount}枚）` : '印刷'}
            </button>
            <button class="white-button" id="closeModalButton">閉じる</button>
        `);

        // 印刷ページ（伝票風レイアウト）を別タブで開く。印刷自体はページ側のボタンで行う。
        // ブラウザの印刷ダイアログの「部数」はページ側から変更できないため、
        // 枚数ぶんのページを生成する方式にしている（countパラメータ）。
        document.getElementById('printQrButton').addEventListener('click', function () {
            const printUrl = `/MOS_A/public/staff/qr/print?customer_id=${encodeURIComponent(customerId)}`
                + (isReissue ? '&reissue=1' : '')
                + (sheetCount > 1 ? `&count=${sheetCount}` : '');
            window.open(printUrl, '_blank');
        });

        document.getElementById('closeModalButton').addEventListener('click', closeModal);

        loadQrLibrary(function () {
            const canvas = document.getElementById('qrcode-canvas');
            if (canvas) {
                new QRious({
                    element: canvas,
                    value: orderUrl,
                    size: 200,
                    level: 'H'
                });
            }
        });
    }

    // 新規顧客を連番でDB発行し、返ってきた customer_id でQRを表示する。
    // printCount は印刷枚数。顧客は1件だけ作り、同じQRを指定枚数ぶん印刷する。
    async function issueCustomer(peopleCount, messagePrefix = 'QR発行が完了しました。', printCount = 1) {
        try {
            const response = await fetch('/MOS_A/public/staff/qr/issue', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ people_count: String(peopleCount) })
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                openModal(`
                    <h2>${data.message || 'QR発行に失敗しました。'}</h2>
                    <button class="white-button" id="closeModalButton">閉じる</button>
                `);
                document.getElementById('closeModalButton').addEventListener('click', closeModal);
                return;
            }

            openQrCompleteModal(data.customer_id, messagePrefix, false, printCount);
        } catch (error) {
            openModal(`
                <h2>通信に失敗しました。もう一度お試しください。</h2>
                <button class="white-button" id="closeModalButton">閉じる</button>
            `);
            document.getElementById('closeModalButton').addEventListener('click', closeModal);
        }
    }

    return {
        issueCustomer,
        openQrCompleteModal
    };
};
