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

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function renderCategoryTabs() {
        const categoryTabs = document.getElementById('categoryTabs');

        categoryTabs.innerHTML = categories.map(category => {
            const activeClass = category === state.activeCategory ? 'active' : '';
            const escapedCategory = escapeHtml(category);

            return `
                <button class="category-tab ${activeClass}" data-category="${escapedCategory}">
                    ${escapedCategory}
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
            const imageSrc = menu.image_path || '/MOS_A/public/assets/images/menu/no_image.png';
            const displayPrice = getDisplayPrice(menu);
            const escapedName = escapeHtml(menu.name);
            const escapedImageSrc = escapeHtml(imageSrc);

            return `
                <button class="menu-card" data-menu-id="${escapeHtml(menu.id)}">
                    <div class="menu-image-frame" style="display: flex; align-items: center; justify-content: center; background: #eee;">
                        <img src="${escapedImageSrc}"
                             alt="${escapedName}"
                             style="width: 100%; height: 100%; object-fit: cover; display: block;"
                             onerror="this.src='/MOS_A/public/assets/images/menu/no_image.png'; this.onerror=null;">
                    </div>

                    <div class="menu-card-body">
                        <div class="menu-name">${escapedName}</div>
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
