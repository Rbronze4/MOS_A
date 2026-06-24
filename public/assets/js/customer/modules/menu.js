/**
 * 客側モジュール：メニュー画面。
 * カテゴリタブの描画、メニュー一覧（プラン適用後の価格込み）の描画、
 * カテゴリ左右スクロールボタンの制御を担当する。app.js から context を受け取り生成される。
 *
 * 主な関数: renderCategoryTabs() / renderMenu() / bindCategoryScroll()
 */
window.MOS = window.MOS || {};
window.MOS.customer = window.MOS.customer || {};

window.MOS.customer.createMenuModule = function createMenuModule(context) {
    const {
        categories,
        menus,
        state,
        formatYen,
        findMenu,
        getDisplayPrice,
        openProduct
    } = context;

    let refreshCategoryScrollButtons = () => {};

    function renderCategoryTabs() {
        const categoryTabs = document.getElementById('categoryTabs');

        categoryTabs.innerHTML = categories.map(category => {
            const activeClass = category === state.activeCategory ? 'active' : '';

            return `
                <button class="category-tab ${activeClass}" data-category="${category}">
                    ${category}
                </button>
            `;
        }).join('');

        categoryTabs.querySelectorAll('.category-tab').forEach(button => {
            button.addEventListener('click', () => {
                state.activeCategory = button.dataset.category;
                renderMenu();
                renderCategoryTabs();
            });
        });

        requestAnimationFrame(refreshCategoryScrollButtons);
    }

    function renderMenu() {
        const menuGrid = document.getElementById('menuGrid');
        const filteredMenus = menus.filter(menu => menu.category === state.activeCategory);

        if (filteredMenus.length === 0) {
            menuGrid.innerHTML = '<p class="empty-message">商品がありません</p>';
            return;
        }

        menuGrid.innerHTML = filteredMenus.map(menu => {
            const imageSrc = menu.image_path || 'assets/images/common/img.jpg';
            const displayPrice = getDisplayPrice(menu);

            return `
                <button class="menu-card" data-menu-id="${menu.id}">
                    <div class="menu-image-frame" style="display: flex; align-items: center; justify-content: center; background: #eee;">
                        <img src="${imageSrc}"
                             alt="${menu.name}"
                             style="width: 100%; height: 100%; object-fit: cover; display: block;"
                             onerror="this.parentElement.style.display='none'; console.error('画像の読み込みに失敗しました', '${imageSrc}')">
                    </div>

                    <div class="menu-card-body">
                        <div class="menu-name">${menu.name}</div>
                        <div class="menu-price">${formatYen(displayPrice)}</div>
                    </div>
                </button>
            `;
        }).join('');

        menuGrid.querySelectorAll('.menu-card').forEach(card => {
            card.addEventListener('click', () => {
                const menu = findMenu(card.dataset.menuId);
                if (!menu) return;

                openProduct(menu, 1, true);
            });
        });
    }

    function bindCategoryScroll() {
        const categoryTabs = document.getElementById('categoryTabs');
        const categoryScrollLeft = document.getElementById('categoryScrollLeft');
        const categoryScrollRight = document.getElementById('categoryScrollRight');

        refreshCategoryScrollButtons = function updateCategoryScrollButtons() {
            if (!categoryTabs || !categoryScrollLeft || !categoryScrollRight) return;

            const maxScrollLeft = categoryTabs.scrollWidth - categoryTabs.clientWidth;

            categoryScrollLeft.classList.toggle('hidden', categoryTabs.scrollLeft <= 0);
            categoryScrollRight.classList.toggle('hidden', maxScrollLeft <= 1 || categoryTabs.scrollLeft >= maxScrollLeft - 1);
        };

        if (categoryScrollLeft) {
            categoryScrollLeft.addEventListener('click', () => {
                categoryTabs.scrollBy({ left: -220, behavior: 'smooth' });
            });
        }

        if (categoryScrollRight) {
            categoryScrollRight.addEventListener('click', () => {
                categoryTabs.scrollBy({ left: 220, behavior: 'smooth' });
            });
        }

        if (categoryTabs) {
            categoryTabs.addEventListener('scroll', refreshCategoryScrollButtons);
            window.addEventListener('resize', refreshCategoryScrollButtons);
            refreshCategoryScrollButtons();
        }
    }

    return {
        renderCategoryTabs,
        renderMenu,
        bindCategoryScroll,
        refreshCategoryScrollButtons: () => refreshCategoryScrollButtons()
    };
};
