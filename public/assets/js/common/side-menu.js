/**
 * 共通モジュール：サイドメニュー（ハンバーガー）の開閉制御。
 * .hamburger-button で開き、×ボタンや背景クリックで閉じる。
 *
 * このファイルを読み込んだページでは自動で初期化される。
 * 以前は dashboard.js / order-menu.js からの呼び出しに依存していたため、
 * それらを読み込まないページ（顧客詳細・注文詳細）ではハンバーガーが反応せず、
 * また呼び出し元のJSがエラーで止まると連鎖してハンバーガーも効かなくなっていた。
 */
window.MOS = window.MOS || {};

window.MOS.initSideMenu = function initSideMenu() {
    const sideMenuLayer = document.getElementById('sideMenuLayer');
    const closeMenuButton = document.getElementById('closeMenuButton');

    if (!sideMenuLayer) {
        return;
    }

    // 自動初期化と既存の明示呼び出しが重なってもイベントを二重登録しない
    if (sideMenuLayer.dataset.sideMenuInitialized === 'true') {
        return;
    }

    sideMenuLayer.dataset.sideMenuInitialized = 'true';

    document.querySelectorAll('.hamburger-button').forEach(button => {
        button.addEventListener('click', () => {
            sideMenuLayer.classList.add('show');
        });
    });

    if (closeMenuButton) {
        closeMenuButton.addEventListener('click', () => {
            sideMenuLayer.classList.remove('show');
        });
    }

    sideMenuLayer.addEventListener('click', event => {
        if (event.target === sideMenuLayer) {
            sideMenuLayer.classList.remove('show');
        }
    });
};

// 呼び出し側の実装に依存せず、読み込まれたページで必ず初期化する。
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.MOS.initSideMenu();
    });
} else {
    window.MOS.initSideMenu();
}
