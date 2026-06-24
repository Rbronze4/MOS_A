/**
 * スタッフ ダッシュボード モジュール：QR発行。
 * 顧客番号（7桁）の発行と、発行完了モーダルの表示を担当する。
 * dashboard.js から context を受け取り生成。
 *
 * 主な関数: openQrCompleteModal()
 */
window.MOS = window.MOS || {};
window.MOS.staffDashboard = window.MOS.staffDashboard || {};

window.MOS.staffDashboard.createQrModule = function createQrModule(context) {
    const {
        openModal,
        closeModal
    } = context;

    // 【追加】QRコード生成ライブラリ（QRious）を動的に読み込む処理
    // HTMLファイル側（ビュー）を変更せずに済むよう、JSの実行時に外部CDNからライブラリを取得します。
    function loadQrLibrary(callback) {
        // 既にライブラリが読み込まれている場合（モーダルを2回目以降開いた場合など）は、
        // 重複して読み込まずにすぐコールバック（QR描画処理）を実行します。
        if (typeof QRious !== 'undefined') {
            callback();
            return;
        }
        
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
        
        // スクリプトの読み込みが完了したタイミングで、引数として渡されたQR描画処理を実行させます。
        // これにより「ライブラリが存在しないエラー」を防ぎます。
        script.onload = callback;
        document.head.appendChild(script);
    }

    function issueCustomerNumber() {
        return String(Math.floor(Math.random() * 10000000)).padStart(7, '0');
    }

    function openQrCompleteModal(messagePrefix = 'QR発行が完了しました') {
        const number = issueCustomerNumber();

        // MOSの仕様に合わせた店舗IDのダミーデータ（大文字アルファベット2文字）
        // ※実際はログイン中の店舗情報などから取得しますが、今回はQRコードの要件を満たすため固定値とします。
        const storeId = 'AB';
        /* 将来的には店舗識別用のDBを作成してそれに記載の識別コードを参照する形にしたいのですが、
        まだ作ってないのでとりあえずそれっぽく動くようにしています。ご了承ください*/

        // お客様がスマートフォンで読み取るための客側注文画面URLを生成
        // QRコードには単なる番号ではなく、アクセス可能なURLを埋め込むことで、
        // 読み取り後すぐにブラウザで注文画面（顧客IDと店舗IDを渡した状態）を開けるようにします。
        const orderUrl = `${window.location.origin}/customer?storeId=${storeId}&customerId=${number}`;

        // 既存のモーダルUIに、QRコード表示用のcanvas要素を追加して展開します。
        openModal(`
            <h2>${messagePrefix}</h2>
            <div>顧客番号</div>
            <div class="generated-number">${number}</div>
            
            <div class="qr-container" style="margin: 20px 0; text-align: center;">
                <canvas id="qrcode-canvas"></canvas>
            </div>
            
            <div style="font-size: 12px; word-break: break-all; margin-bottom: 20px; color: #666;">
                アクセスURL: ${orderUrl}
            </div>

            <button class="white-button" id="closeModalButton">閉じる</button>
        `);

        // モーダルのDOMが画面上に展開された後に、閉じるボタンのイベントを設定
        document.getElementById('closeModalButton').addEventListener('click', closeModal);

        // 【追加】ライブラリを読み込み、準備ができたらQRコードを生成・描画する
        // 描画先の <canvas id="qrcode-canvas"> がDOMに存在する必要があるため、
        // openModal() を実行した「後」にこの処理を呼ぶことが重要です。
        loadQrLibrary(function() {
            const canvas = document.getElementById('qrcode-canvas');
            if (canvas) {
                // QRiousライブラリを使って、指定したcanvas要素にQRコードを描画します
                new QRious({
                    element: canvas,
                    value: orderUrl, // QRコードに埋め込むデータ（客側画面のURL）
                    size: 200,       // QRコードのサイズ（200px）
                    level: 'H'       // 誤り訂正レベル（High: スマホの影や画面の反射があっても読み取りやすくするため高めに設定）
                });
            }
        });
    }

    return {
        openQrCompleteModal
    };
};