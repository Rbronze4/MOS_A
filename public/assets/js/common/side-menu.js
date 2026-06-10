window.MOS = window.MOS || {};

window.MOS.initSideMenu = function initSideMenu() {
    const sideMenuLayer = document.getElementById('sideMenuLayer');
    const closeMenuButton = document.getElementById('closeMenuButton');

    if (!sideMenuLayer) {
        return;
    }

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
