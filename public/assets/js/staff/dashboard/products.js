/**
 * スタッフダッシュボード 商品管理モジュール。
 * 商品一覧表示、商品追加、商品編集を担当します。削除処理は実装しません。
 */
window.MOS = window.MOS || {};
window.MOS.staffDashboard = window.MOS.staffDashboard || {};

window.MOS.staffDashboard.createProductModule = function createProductModule(context) {
    const {
        state,
        openModal,
        closeModal,
        openCompleteModal
    } = context;

    const TAX_RATE = 0.1;
    const PRODUCTS_PER_PAGE = 20;
    let currentProductPage = 1;
    const productFilters = {
        name: '',
        categoryId: '',
        planTypeId: '',
        saleStatus: '',
        sortOrder: 'id-asc'
    };
    const japaneseCollator = new Intl.Collator('ja', {
        numeric: true,
        sensitivity: 'base'
    });

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function taxIncluded(priceExcludingTax) {
        return Math.floor(Number(priceExcludingTax || 0) * (1 + TAX_RATE));
    }

    function saleStatusText(status) {
        if (status === 'ON_SALE') return '販売中';
        if (status === 'SOLD_OUT') return '売り切れ';
        if (status === 'HIDDEN') return '非表示';
        return status || '';
    }

    function imageUrl(path) {
        if (!path) return '';
        return path.startsWith('/MOS_A/public')
            ? path
            : `/MOS_A/public${path}`;
    }

    function productsForCurrentFilters() {
        const normalizedName = productFilters.name.trim().toLocaleLowerCase('ja');
        const filteredProducts = state.products.filter(product => {
            if (normalizedName !== '' && !String(product.name || '').toLocaleLowerCase('ja').includes(normalizedName)) {
                return false;
            }

            if (productFilters.categoryId !== '' && String(product.category_id) !== productFilters.categoryId) {
                return false;
            }

            const planTypeIds = Array.isArray(product.plan_type_ids)
                ? product.plan_type_ids.map(Number)
                : [];

            if (productFilters.planTypeId === 'none' && planTypeIds.length > 0) {
                return false;
            }

            if (
                productFilters.planTypeId !== ''
                && productFilters.planTypeId !== 'none'
                && !planTypeIds.includes(Number(productFilters.planTypeId))
            ) {
                return false;
            }

            return productFilters.saleStatus === '' || product.sale_status === productFilters.saleStatus;
        });

        return filteredProducts.sort((left, right) => {
            if (productFilters.sortOrder === 'name-asc') {
                return japaneseCollator.compare(left.name || '', right.name || '');
            }

            if (productFilters.sortOrder === 'category-asc') {
                return japaneseCollator.compare(left.category || '', right.category || '')
                    || japaneseCollator.compare(left.name || '', right.name || '');
            }

            if (productFilters.sortOrder === 'price-asc') {
                return Number(left.price || 0) - Number(right.price || 0)
                    || Number(left.id) - Number(right.id);
            }

            if (productFilters.sortOrder === 'price-desc') {
                return Number(right.price || 0) - Number(left.price || 0)
                    || Number(left.id) - Number(right.id);
            }

            return Number(left.id) - Number(right.id);
        });
    }

    function renderProducts() {
        const body = document.getElementById('productTableBody');
        if (!body) return;

        const filteredProducts = productsForCurrentFilters();
        const totalPages = Math.max(1, Math.ceil(filteredProducts.length / PRODUCTS_PER_PAGE));
        currentProductPage = Math.min(currentProductPage, totalPages);

        const pageStart = (currentProductPage - 1) * PRODUCTS_PER_PAGE;
        const products = filteredProducts.slice(pageStart, pageStart + PRODUCTS_PER_PAGE);
        const resultCount = document.getElementById('productFilterResultCount');
        const pagination = document.getElementById('productPagination');
        const previousButton = document.getElementById('previousProductPage');
        const nextButton = document.getElementById('nextProductPage');
        const pageStatus = document.getElementById('productPageStatus');

        if (resultCount) {
            if (filteredProducts.length === 0) {
                resultCount.textContent = `0 / ${state.products.length}件を表示`;
            } else {
                const pageEnd = pageStart + products.length;
                resultCount.textContent = `${pageStart + 1}〜${pageEnd}件 / 絞り込み${filteredProducts.length}件（全${state.products.length}件）`;
            }
        }

        if (pagination && previousButton && nextButton && pageStatus) {
            pagination.hidden = totalPages <= 1;
            previousButton.disabled = currentProductPage <= 1;
            nextButton.disabled = currentProductPage >= totalPages;
            pageStatus.textContent = `${currentProductPage} / ${totalPages}ページ`;
        }

        if (state.products.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-row">商品が登録されていません</td>
                </tr>
            `;
            return;
        }

        if (filteredProducts.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-row">条件に一致する商品がありません</td>
                </tr>
            `;
            return;
        }

        body.innerHTML = products.map(product => {
            const selectedClass = String(product.id) === String(state.selectedProductId) ? 'selected-row' : '';
            const checked = String(product.id) === String(state.selectedProductId) ? 'checked' : '';
            const preview = product.image_path
                ? `<img class="product-thumb" src="${escapeHtml(imageUrl(product.image_path))}" alt="">`
                : '<span class="product-thumb-empty">画像なし</span>';

            return `
                <tr class="${selectedClass}" data-product-id="${product.id}">
                    <td>
                        <input
                            type="radio"
                            name="selectedProduct"
                            class="product-radio"
                            value="${product.id}"
                            ${checked}
                        >
                    </td>
                    <td>${escapeHtml(product.name)}</td>
                    <td>${escapeHtml(product.category)}</td>
                    <td>
                        <div>${Number(product.price || 0).toLocaleString()}円</div>
                        <small>税込 ${Number(product.tax_included_price || taxIncluded(product.price)).toLocaleString()}円</small>
                        <small>${escapeHtml(saleStatusText(product.sale_status))}</small>
                    </td>
                    <td>${escapeHtml(product.plan_summary || '単品のみ')}</td>
                    <td>
                        ${preview}
                        <button class="row-button product-edit-row-button" type="button" data-product-id="${product.id}">編集</button>
                    </td>
                </tr>
            `;
        }).join('');

        body.querySelectorAll('tr').forEach(row => {
            row.addEventListener('click', () => {
                state.selectedProductId = Number(row.dataset.productId);
                renderProducts();
            });
        });

        body.querySelectorAll('.product-radio').forEach(radio => {
            radio.addEventListener('click', event => {
                event.stopPropagation();
                state.selectedProductId = Number(radio.value);
                renderProducts();
            });
        });

        body.querySelectorAll('.product-edit-row-button').forEach(button => {
            button.addEventListener('click', event => {
                event.stopPropagation();
                state.selectedProductId = Number(button.dataset.productId);
                openProductForm('edit');
            });
        });
    }

    function bindProductFilters() {
        const form = document.getElementById('productFilterForm');
        if (!form || form.dataset.bound === '1') return;

        const nameInput = document.getElementById('productNameFilter');
        const categorySelect = document.getElementById('productCategoryFilter');
        const planSelect = document.getElementById('productPlanFilter');
        const saleStatusSelect = document.getElementById('productSaleStatusFilter');
        const sortSelect = document.getElementById('productSortOrder');
        const resetButton = document.getElementById('resetProductFilters');
        const previousButton = document.getElementById('previousProductPage');
        const nextButton = document.getElementById('nextProductPage');

        function resetPageAndRender() {
            currentProductPage = 1;
            renderProducts();
        }

        categorySelect.insertAdjacentHTML('beforeend', state.productCategories.map(category => `
            <option value="${Number(category.category_id)}">${escapeHtml(category.category_name)}</option>
        `).join(''));

        planSelect.insertAdjacentHTML('beforeend', state.productPlanTypes.map(planType => `
            <option value="${Number(planType.plan_type_id)}">${escapeHtml(planType.plan_type_name)}</option>
        `).join(''));

        form.addEventListener('submit', event => event.preventDefault());
        nameInput.addEventListener('input', () => {
            productFilters.name = nameInput.value;
            resetPageAndRender();
        });
        categorySelect.addEventListener('change', () => {
            productFilters.categoryId = categorySelect.value;
            resetPageAndRender();
        });
        planSelect.addEventListener('change', () => {
            productFilters.planTypeId = planSelect.value;
            resetPageAndRender();
        });
        saleStatusSelect.addEventListener('change', () => {
            productFilters.saleStatus = saleStatusSelect.value;
            resetPageAndRender();
        });
        sortSelect.addEventListener('change', () => {
            productFilters.sortOrder = sortSelect.value;
            resetPageAndRender();
        });
        resetButton.addEventListener('click', () => {
            form.reset();
            Object.assign(productFilters, {
                name: '',
                categoryId: '',
                planTypeId: '',
                saleStatus: '',
                sortOrder: 'id-asc'
            });
            resetPageAndRender();
        });
        previousButton?.addEventListener('click', () => {
            if (currentProductPage <= 1) return;
            currentProductPage -= 1;
            renderProducts();
        });
        nextButton?.addEventListener('click', () => {
            const totalPages = Math.ceil(productsForCurrentFilters().length / PRODUCTS_PER_PAGE);
            if (currentProductPage >= totalPages) return;
            currentProductPage += 1;
            renderProducts();
        });

        form.dataset.bound = '1';
    }

    function selectedProduct() {
        return state.products.find(product => Number(product.id) === Number(state.selectedProductId));
    }

    function categoryOptions(selectedCategoryId = '') {
        if (state.productCategories.length === 0) {
            return '<option value="">カテゴリがありません</option>';
        }

        return [
            '<option value="">カテゴリを選択してください</option>',
            ...state.productCategories.map(category => {
                const categoryId = Number(category.category_id);
                const selected = String(categoryId) === String(selectedCategoryId) ? 'selected' : '';

                return `<option value="${categoryId}" ${selected}>${escapeHtml(category.category_name)}</option>`;
            })
        ].join('');
    }

    function planTypeCheckboxes(selectedPlanTypeIds = []) {
        if (state.productPlanTypes.length === 0) {
            return '<p class="product-plan-empty">この店舗で有効なプランはありません</p>';
        }

        const selectedIds = new Set(selectedPlanTypeIds.map(Number));

        return state.productPlanTypes.map(planType => {
            const planTypeId = Number(planType.plan_type_id);
            const checked = selectedIds.has(planTypeId) ? 'checked' : '';
            const timeLimits = Array.isArray(planType.time_limits) && planType.time_limits.length > 0
                ? `（${planType.time_limits.map(minutes => `${Number(minutes)}分`).join('・')}）`
                : '';

            return `
                <label class="product-plan-choice">
                    <input type="checkbox" name="plan_type_ids[]" value="${planTypeId}" ${checked}>
                    <span>${escapeHtml(planType.plan_type_name)}${escapeHtml(timeLimits)}</span>
                </label>
            `;
        }).join('');
    }

    function optionGroupTemplate(index, group = {}) {
        const groupId = Number(group.option_group_id || 0);
        const groupName = group.group_name || '';
        const selectionType = group.selection_type || 'SINGLE';
        const isRequired = Number(group.is_required || 0) === 1 ? '1' : '0';
        const options = Array.isArray(group.options) && group.options.length > 0
            ? group.options
            : [{ option_id: 0, option_name: '', additional_price: 0 }];

        return `
            <div class="option-group-box" data-option-group-index="${index}" data-option-group-id="${groupId}">
                <input type="hidden" name="option_group_id" value="${groupId}">

                <div class="option-group-header">
                    <label>
                        <span>オプショングループ名</span>
                        <input type="text" name="option_group_name" value="${escapeHtml(groupName)}" placeholder="例：辛さ">
                    </label>
                </div>

                <div class="option-group-flags">
                    <label>
                        <span>必須</span>
                        <select name="option_is_required">
                            <option value="1" ${isRequired === '1' ? 'selected' : ''}>ON</option>
                            <option value="0" ${isRequired === '0' ? 'selected' : ''}>OFF</option>
                        </select>
                    </label>

                    <label>
                        <span>複数選択</span>
                        <select name="option_selection_type">
                            <option value="SINGLE" ${selectionType === 'SINGLE' ? 'selected' : ''}>OFF</option>
                            <option value="MULTIPLE" ${selectionType === 'MULTIPLE' ? 'selected' : ''}>ON</option>
                        </select>
                    </label>
                </div>

                <div class="option-values">
                    ${options.map(option => `
                        <label>
                            <span>選択肢</span>
                            <input type="hidden" name="option_id" value="${Number(option.option_id || 0)}">
                            <input type="text" name="option_value" value="${escapeHtml(option.option_name || '')}" placeholder="例：なし">
                        </label>
                        <label>
                            <span>追加料金</span>
                            <input type="number" name="option_additional_price" value="${Number(option.additional_price || 0)}" min="0" step="1" placeholder="0">
                        </label>
                    `).join('')}
                </div>

                <button class="row-button add-option-value-button" type="button">選択肢を追加</button>
            </div>
        `;
    }

    function collectOptionGroups(form) {
        const groups = [];

        form.querySelectorAll('.option-group-box').forEach(groupBox => {
            const groupName = groupBox.querySelector('[name="option_group_name"]').value.trim();
            const optionGroupId = Number(groupBox.querySelector('[name="option_group_id"]').value || 0);
            const selectionType = groupBox.querySelector('[name="option_selection_type"]').value;
            const isRequired = groupBox.querySelector('[name="option_is_required"]').value;
            const optionValueInputs = Array.from(groupBox.querySelectorAll('[name="option_value"]'));
            const optionIdInputs = Array.from(groupBox.querySelectorAll('[name="option_id"]'));
            const additionalPriceInputs = Array.from(groupBox.querySelectorAll('[name="option_additional_price"]'));
            const options = optionValueInputs.map((input, index) => ({
                optionId: Number(optionIdInputs[index]?.value || 0),
                optionName: input.value.trim(),
                additionalPrice: Math.max(0, Number(additionalPriceInputs[index]?.value || 0))
            })).filter(option => option.optionName !== '');

            if (groupName !== '' || options.length > 0) {
                groups.push({
                    optionGroupId,
                    groupName,
                    selectionType,
                    isRequired,
                    options
                });
            }
        });

        return groups;
    }

    function appendOptionGroupsToFormData(formData, groups) {
        groups.forEach((group, groupIndex) => {
            formData.append(`option_groups[${groupIndex}][option_group_id]`, String(group.optionGroupId));
            formData.append(`option_groups[${groupIndex}][group_name]`, group.groupName);
            formData.append(`option_groups[${groupIndex}][selection_type]`, group.selectionType);
            formData.append(`option_groups[${groupIndex}][is_required]`, group.isRequired);

            group.options.forEach((option, optionIndex) => {
                formData.append(`option_groups[${groupIndex}][options][${optionIndex}][option_id]`, String(option.optionId));
                formData.append(`option_groups[${groupIndex}][options][${optionIndex}][option_name]`, option.optionName);
                formData.append(`option_groups[${groupIndex}][options][${optionIndex}][additional_price]`, String(option.additionalPrice));
            });
        });
    }

    async function postProduct(formData, mode) {
        const response = await fetch(
            mode === 'edit' ? '/MOS_A/public/staff/product/update' : '/MOS_A/public/staff/product/add',
            {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            }
        );

        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            payload = null;
        }

        if (!response.ok || !payload || payload.ok !== true) {
            throw new Error(payload?.message || '商品の保存に失敗しました。');
        }

        return payload.product;
    }

    function bindOptionUi(form, product) {
        const hasOptionsSelect = form.querySelector('#productHasOptionsInput');
        const optionArea = form.querySelector('#productOptionArea');
        const optionGroupList = form.querySelector('#optionGroupList');
        const addOptionGroupButton = form.querySelector('#addOptionGroupButton');
        const existingHasOptions = Boolean(product?.has_options);

        function ensureFirstGroup() {
            if (optionGroupList.children.length === 0) {
                optionGroupList.insertAdjacentHTML('beforeend', optionGroupTemplate(0));
            }
        }

        hasOptionsSelect.addEventListener('change', () => {
            if (existingHasOptions && hasOptionsSelect.value === '0') {
                hasOptionsSelect.value = '1';
                openCompleteModal('既存オプションの削除は行いません。必要な場合は販売状態で制御してください。');
                return;
            }

            optionArea.hidden = hasOptionsSelect.value !== '1';

            if (!optionArea.hidden) {
                ensureFirstGroup();
            }
        });

        addOptionGroupButton.addEventListener('click', () => {
            optionGroupList.insertAdjacentHTML('beforeend', optionGroupTemplate(optionGroupList.children.length));
        });

        optionGroupList.addEventListener('click', event => {
            const target = event.target;

            if (!(target instanceof HTMLElement)) return;

            if (target.classList.contains('add-option-value-button')) {
                const values = target.closest('.option-group-box')?.querySelector('.option-values');
                values?.insertAdjacentHTML('beforeend', `
                    <label>
                        <span>選択肢</span>
                        <input type="hidden" name="option_id" value="0">
                        <input type="text" name="option_value" placeholder="例：多め">
                    </label>
                    <label>
                        <span>追加料金</span>
                        <input type="number" name="option_additional_price" min="0" step="1" placeholder="0">
                    </label>
                `);
            }
        });
    }

    function openProductForm(mode) {
        const product = mode === 'edit'
            ? selectedProduct()
            : {
                id: '',
                name: '',
                category_id: '',
                price: '',
                sale_status: 'ON_SALE',
                image_path: '',
                plan_type_ids: [],
                has_options: false,
                option_groups: []
            };

        if (mode === 'edit' && !product) {
            openCompleteModal('編集する商品を選択してください。');
            return;
        }

        const optionGroups = Array.isArray(product.option_groups) ? product.option_groups : [];
        const selectedPlanTypeIds = Array.isArray(product.plan_type_ids) ? product.plan_type_ids : [];
        const hasOptionsValue = product.has_options ? '1' : '0';
        const existingImage = product.image_path
            ? `<img src="${escapeHtml(imageUrl(product.image_path))}" alt="">`
            : '<span>画像プレビュー</span>';

        openModal(`
            <form class="product-form" id="productSaveForm" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="${escapeHtml(product.id)}">

                <label>
                    <span>商品名</span>
                    <input id="productNameInput" name="product_name" type="text" maxlength="100" value="${escapeHtml(product.name)}" required>
                </label>

                <label>
                    <span>カテゴリ</span>
                    <select id="productCategoryInput" name="category_id" required>
                        ${categoryOptions(product.category_id)}
                    </select>
                </label>

                <label>
                    <span>商品画像</span>
                    <input id="productImageInput" name="product_image" type="file" accept="image/jpeg,image/png,image/webp">
                </label>

                <div class="image-preview-box" id="productImagePreview">
                    ${existingImage}
                </div>

                <label>
                    <span>税抜価格</span>
                    <input id="productPriceInput" name="price" type="number" min="0" step="1" value="${escapeHtml(product.price)}" required>
                </label>

                <label>
                    <span>税込価格</span>
                    <input id="productPriceTaxIncludedInput" type="number" value="${taxIncluded(product.price)}" readonly>
                </label>

                <label>
                    <span>販売状態</span>
                    <select id="productSaleStatusInput" name="sale_status">
                        <option value="ON_SALE" ${product.sale_status === 'ON_SALE' ? 'selected' : ''}>販売中</option>
                        <option value="SOLD_OUT" ${product.sale_status === 'SOLD_OUT' ? 'selected' : ''}>売り切れ</option>
                        <option value="HIDDEN" ${product.sale_status === 'HIDDEN' ? 'selected' : ''}>非表示</option>
                    </select>
                </label>

                <fieldset class="product-plan-area">
                    <legend>対応プラン（複数選択可）</legend>
                    <p class="product-plan-note">未選択の場合は単品注文のみの商品になります。</p>
                    <div class="product-plan-choices">
                        ${planTypeCheckboxes(selectedPlanTypeIds)}
                    </div>
                </fieldset>

                <label>
                    <span>オプション</span>
                    <select id="productHasOptionsInput" name="has_options">
                        <option value="0" ${hasOptionsValue === '0' ? 'selected' : ''}>なし</option>
                        <option value="1" ${hasOptionsValue === '1' ? 'selected' : ''}>あり</option>
                    </select>
                </label>

                <div class="product-option-area" id="productOptionArea" ${hasOptionsValue === '1' ? '' : 'hidden'}>
                    <div id="optionGroupList">
                        ${optionGroups.map((group, index) => optionGroupTemplate(index, group)).join('')}
                    </div>
                    <button class="row-button" id="addOptionGroupButton" type="button">オプションを追加</button>
                </div>

                <div class="form-buttons">
                    <button id="saveProductButton" class="white-button" type="submit">決定</button>
                    <button id="cancelProductButton" class="white-button" type="button">取消</button>
                </div>
            </form>
        `);

        const form = document.getElementById('productSaveForm');
        const priceInput = document.getElementById('productPriceInput');
        const taxIncludedInput = document.getElementById('productPriceTaxIncludedInput');
        const imageInput = document.getElementById('productImageInput');
        const imagePreview = document.getElementById('productImagePreview');
        const saveButton = document.getElementById('saveProductButton');

        document.getElementById('cancelProductButton').addEventListener('click', closeModal);

        priceInput.addEventListener('input', () => {
            taxIncludedInput.value = taxIncluded(priceInput.value);
        });

        imageInput.addEventListener('change', () => {
            const file = imageInput.files?.[0];

            if (!file) {
                imagePreview.innerHTML = existingImage;
                return;
            }

            imagePreview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="">`;
        });

        bindOptionUi(form, product);

        form.addEventListener('submit', async event => {
            event.preventDefault();

            const formData = new FormData(form);
            const hasOptions = formData.get('has_options') === '1';

            if (hasOptions) {
                const optionGroupsForSave = collectOptionGroups(form);

                if (optionGroupsForSave.length === 0) {
                    openCompleteModal('オプションありの場合は、オプショングループを入力してください。');
                    return;
                }

                if (optionGroupsForSave.some(group => group.groupName === '' || group.options.length === 0)) {
                    openCompleteModal('各オプショングループ名と選択肢を入力してください。');
                    return;
                }

                appendOptionGroupsToFormData(formData, optionGroupsForSave);
            }

            saveButton.disabled = true;

            try {
                const savedProduct = await postProduct(formData, mode);
                const index = state.products.findIndex(item => Number(item.id) === Number(savedProduct.id));

                if (index >= 0) {
                    state.products[index] = savedProduct;
                } else {
                    state.products.push(savedProduct);
                }

                state.selectedProductId = savedProduct.id;
                renderProducts();
                closeModal();
                openCompleteModal(mode === 'edit' ? '商品を更新しました。' : '商品を追加しました。');
            } catch (error) {
                openCompleteModal(error.message || '商品の保存に失敗しました。');
            } finally {
                saveButton.disabled = false;
            }
        });
    }

    bindProductFilters();

    return {
        renderProducts,
        selectedProduct,
        openProductForm
    };
};
