<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Database/db.php';

final class StaffProductModel
{
    private const TAX_RATE = '10.00';

    public function productsForStore(string $storeId): array
    {
        $sql = <<<SQL
            SELECT
                p.product_id,
                p.product_name,
                p.price,
                p.tax_rate,
                p.sale_status AS product_sale_status,
                p.image_path,
                pc.category_id,
                pc.category_name,
                sp.store_id,
                sp.sale_status AS store_sale_status,
                sp.display_order,
                MAX(CASE
                    WHEN pog.product_id IS NULL THEN 0
                    ELSE 1
                END) AS has_options
            FROM store_products AS sp
            INNER JOIN products AS p
                ON sp.product_id = p.product_id
            INNER JOIN product_categories AS pc
                ON p.category_id = pc.category_id
            LEFT JOIN product_option_groups AS pog
                ON pog.product_id = p.product_id
            WHERE sp.store_id = :store_id
            GROUP BY
                p.product_id,
                p.product_name,
                p.price,
                p.tax_rate,
                p.sale_status,
                p.image_path,
                pc.category_id,
                pc.category_name,
                sp.store_id,
                sp.sale_status,
                sp.display_order
            ORDER BY
                pc.category_id ASC,
                sp.display_order ASC,
                p.product_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        $products = [];

        foreach ($statement->fetchAll() as $row) {
            $products[] = $this->formatProductRow($row);
        }

        return $products;
    }

    public function categories(): array
    {
        $sql = <<<SQL
            SELECT
                category_id,
                category_name
            FROM product_categories
            ORDER BY category_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->execute();

        $categories = [];

        foreach ($statement->fetchAll() as $row) {
            $categories[] = [
                'category_id' => (int)$row['category_id'],
                'category_name' => (string)$row['category_name'],
            ];
        }

        return $categories;
    }

    public function addProduct(string $storeId, array $input, ?array $imageFile): array
    {
        $categories = $this->categories();
        $categoryIds = array_map(
            static fn (array $category): int => (int)$category['category_id'],
            $categories
        );

        $productName = trim((string)($input['product_name'] ?? ''));
        $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
        $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_INT);
        $saleStatus = trim((string)($input['sale_status'] ?? 'ON_SALE'));
        $hasOptions = (string)($input['has_options'] ?? '0') === '1';
        $optionGroups = $this->normalizeOptionGroups($input['option_groups'] ?? []);

        if ($productName === '') {
            throw new InvalidArgumentException('商品名を入力してください。');
        }

        if ($categoryId === false || !in_array((int)$categoryId, $categoryIds, true)) {
            throw new InvalidArgumentException('カテゴリを選択してください。');
        }

        if ($price === false || $price < 0) {
            throw new InvalidArgumentException('税抜価格は0以上の数値で入力してください。');
        }

        if (!in_array($saleStatus, ['ON_SALE', 'SOLD_OUT', 'HIDDEN'], true)) {
            throw new InvalidArgumentException('販売状態が正しくありません。');
        }

        if ($hasOptions && $optionGroups === []) {
            throw new InvalidArgumentException('オプションありの場合は、オプショングループを1つ以上入力してください。');
        }

        $savedImagePath = $this->saveImageFile($imageFile);
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $productSql = <<<SQL
                INSERT INTO products (
                    product_name,
                    category_id,
                    price,
                    tax_rate,
                    sale_status,
                    image_path,
                    created_at,
                    updated_at
                ) VALUES (
                    :product_name,
                    :category_id,
                    :price,
                    :tax_rate,
                    :sale_status,
                    :image_path,
                    NOW(),
                    NOW()
                )
            SQL;

            $statement = $pdo->prepare($productSql);
            $statement->bindValue(':product_name', $productName, PDO::PARAM_STR);
            $statement->bindValue(':category_id', (int)$categoryId, PDO::PARAM_INT);
            $statement->bindValue(':price', (int)$price, PDO::PARAM_INT);
            $statement->bindValue(':tax_rate', self::TAX_RATE, PDO::PARAM_STR);
            $statement->bindValue(':sale_status', $saleStatus, PDO::PARAM_STR);

            if ($savedImagePath === null) {
                $statement->bindValue(':image_path', null, PDO::PARAM_NULL);
            } else {
                $statement->bindValue(':image_path', $savedImagePath, PDO::PARAM_STR);
            }

            $statement->execute();
            $productId = (int)$pdo->lastInsertId();
            $displayOrder = $this->nextDisplayOrder($storeId);

            $storeProductSql = <<<SQL
                INSERT INTO store_products (
                    store_id,
                    product_id,
                    sale_status,
                    display_order,
                    created_at,
                    updated_at
                ) VALUES (
                    :store_id,
                    :product_id,
                    :sale_status,
                    :display_order,
                    NOW(),
                    NOW()
                )
            SQL;

            $statement = $pdo->prepare($storeProductSql);
            $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
            $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $statement->bindValue(':sale_status', $saleStatus, PDO::PARAM_STR);
            $statement->bindValue(':display_order', $displayOrder, PDO::PARAM_INT);
            $statement->execute();

            if ($hasOptions) {
                $this->insertOptionGroups($productId, $optionGroups);
            }

            $pdo->commit();

            return $this->productForStore($storeId, $productId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function updateProduct(string $storeId, int $productId, array $input, ?array $imageFile): array
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('商品IDが正しくありません。');
        }

        $currentProduct = $this->productForStore($storeId, $productId);
        $categories = $this->categories();
        $categoryIds = array_map(
            static fn (array $category): int => (int)$category['category_id'],
            $categories
        );

        $productName = trim((string)($input['product_name'] ?? ''));
        $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
        $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_INT);
        $saleStatus = trim((string)($input['sale_status'] ?? 'ON_SALE'));
        $hasOptions = (string)($input['has_options'] ?? '0') === '1';
        $optionGroups = $this->normalizeOptionGroups($input['option_groups'] ?? []);

        if ($productName === '') {
            throw new InvalidArgumentException('商品名を入力してください。');
        }

        if ($categoryId === false || !in_array((int)$categoryId, $categoryIds, true)) {
            throw new InvalidArgumentException('カテゴリを選択してください。');
        }

        if ($price === false || $price < 0) {
            throw new InvalidArgumentException('税抜価格は0以上の数値で入力してください。');
        }

        if (!in_array($saleStatus, ['ON_SALE', 'SOLD_OUT', 'HIDDEN'], true)) {
            throw new InvalidArgumentException('販売状態が正しくありません。');
        }

        if ($hasOptions && $optionGroups === [] && !$currentProduct['has_options']) {
            throw new InvalidArgumentException('オプションありの場合は、オプショングループを1つ以上入力してください。');
        }

        $savedImagePath = $this->saveImageFile($imageFile);
        $imagePath = $savedImagePath ?? (string)$currentProduct['image_path'];
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $productSql = <<<SQL
                UPDATE products
                SET
                    product_name = :product_name,
                    category_id = :category_id,
                    price = :price,
                    tax_rate = :tax_rate,
                    sale_status = :sale_status,
                    image_path = :image_path,
                    updated_at = NOW()
                WHERE product_id = :product_id
            SQL;

            $statement = $pdo->prepare($productSql);
            $statement->bindValue(':product_name', $productName, PDO::PARAM_STR);
            $statement->bindValue(':category_id', (int)$categoryId, PDO::PARAM_INT);
            $statement->bindValue(':price', (int)$price, PDO::PARAM_INT);
            $statement->bindValue(':tax_rate', self::TAX_RATE, PDO::PARAM_STR);
            $statement->bindValue(':sale_status', $saleStatus, PDO::PARAM_STR);

            if ($imagePath === '') {
                $statement->bindValue(':image_path', null, PDO::PARAM_NULL);
            } else {
                $statement->bindValue(':image_path', $imagePath, PDO::PARAM_STR);
            }

            $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $statement->execute();

            $storeProductSql = <<<SQL
                UPDATE store_products
                SET
                    sale_status = :sale_status,
                    updated_at = NOW()
                WHERE store_id = :store_id
                  AND product_id = :product_id
            SQL;

            $statement = $pdo->prepare($storeProductSql);
            $statement->bindValue(':sale_status', $saleStatus, PDO::PARAM_STR);
            $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
            $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $statement->execute();

            if ($hasOptions && $optionGroups !== []) {
                $this->upsertOptionGroups($productId, $optionGroups);
            }

            $pdo->commit();

            return $this->productForStore($storeId, $productId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function productForStore(string $storeId, int $productId): array
    {
        $sql = <<<SQL
            SELECT
                p.product_id,
                p.product_name,
                p.price,
                p.tax_rate,
                p.sale_status AS product_sale_status,
                p.image_path,
                pc.category_id,
                pc.category_name,
                sp.store_id,
                sp.sale_status AS store_sale_status,
                sp.display_order,
                MAX(CASE
                    WHEN pog.product_id IS NULL THEN 0
                    ELSE 1
                END) AS has_options
            FROM store_products AS sp
            INNER JOIN products AS p
                ON sp.product_id = p.product_id
            INNER JOIN product_categories AS pc
                ON p.category_id = pc.category_id
            LEFT JOIN product_option_groups AS pog
                ON pog.product_id = p.product_id
            WHERE sp.store_id = :store_id
              AND p.product_id = :product_id
            GROUP BY
                p.product_id,
                p.product_name,
                p.price,
                p.tax_rate,
                p.sale_status,
                p.image_path,
                pc.category_id,
                pc.category_name,
                sp.store_id,
                sp.sale_status,
                sp.display_order
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->execute();

        $row = $statement->fetch();

        if ($row === false) {
            throw new RuntimeException('追加した商品を取得できませんでした。');
        }

        return $this->formatProductRow($row);
    }

    private function optionGroupsForProduct(int $productId): array
    {
        $sql = <<<SQL
            SELECT
                og.option_group_id,
                og.option_group_name,
                og.selection_type,
                og.is_required,
                pog.display_order AS group_display_order,
                o.option_id,
                o.option_name,
                o.additional_price,
                o.display_order AS option_display_order
            FROM product_option_groups AS pog
            INNER JOIN option_groups AS og
                ON og.option_group_id = pog.option_group_id
            LEFT JOIN options AS o
                ON o.option_group_id = og.option_group_id
            WHERE pog.product_id = :product_id
            ORDER BY
                pog.display_order ASC,
                og.option_group_id ASC,
                o.display_order ASC,
                o.option_id ASC
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->execute();

        $groups = [];

        foreach ($statement->fetchAll() as $row) {
            $groupId = (int)$row['option_group_id'];

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [
                    'option_group_id' => $groupId,
                    'group_name' => (string)$row['option_group_name'],
                    'selection_type' => (string)$row['selection_type'],
                    'is_required' => (int)$row['is_required'],
                    'options' => [],
                ];
            }

            if ($row['option_id'] !== null) {
                $groups[$groupId]['options'][] = [
                    'option_id' => (int)$row['option_id'],
                    'option_name' => (string)$row['option_name'],
                    'additional_price' => (int)$row['additional_price'],
                ];
            }
        }

        return array_values($groups);
    }

    private function nextDisplayOrder(string $storeId): int
    {
        $sql = <<<SQL
            SELECT COALESCE(MAX(display_order), 0) + 1 AS next_display_order
            FROM store_products
            WHERE store_id = :store_id
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':store_id', $storeId, PDO::PARAM_STR);
        $statement->execute();

        return (int)$statement->fetchColumn();
    }

    private function insertOptionGroups(int $productId, array $optionGroups): void
    {
        $pdo = db();

        foreach ($optionGroups as $groupIndex => $group) {
            $groupSql = <<<SQL
                INSERT INTO option_groups (
                    option_group_name,
                    selection_type,
                    is_required,
                    created_at,
                    updated_at
                ) VALUES (
                    :option_group_name,
                    :selection_type,
                    :is_required,
                    NOW(),
                    NOW()
                )
            SQL;

            $statement = $pdo->prepare($groupSql);
            $statement->bindValue(':option_group_name', $group['group_name'], PDO::PARAM_STR);
            $statement->bindValue(':selection_type', $group['selection_type'], PDO::PARAM_STR);
            $statement->bindValue(':is_required', $group['is_required'], PDO::PARAM_INT);
            $statement->execute();

            $optionGroupId = (int)$pdo->lastInsertId();

            $linkSql = <<<SQL
                INSERT INTO product_option_groups (
                    product_id,
                    option_group_id,
                    display_order,
                    created_at,
                    updated_at
                ) VALUES (
                    :product_id,
                    :option_group_id,
                    :display_order,
                    NOW(),
                    NOW()
                )
            SQL;

            $statement = $pdo->prepare($linkSql);
            $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $statement->bindValue(':option_group_id', $optionGroupId, PDO::PARAM_INT);
            $statement->bindValue(':display_order', $groupIndex + 1, PDO::PARAM_INT);
            $statement->execute();

            foreach ($group['options'] as $optionIndex => $option) {
                $optionName = is_array($option) ? (string)$option['option_name'] : (string)$option;
                $additionalPrice = is_array($option) ? (int)($option['additional_price'] ?? 0) : 0;

                $optionSql = <<<SQL
                    INSERT INTO options (
                        option_group_id,
                        option_name,
                        additional_price,
                        display_order,
                        created_at,
                        updated_at
                    ) VALUES (
                        :option_group_id,
                        :option_name,
                        :additional_price,
                        :display_order,
                        NOW(),
                        NOW()
                    )
                SQL;

                $statement = $pdo->prepare($optionSql);
                $statement->bindValue(':option_group_id', $optionGroupId, PDO::PARAM_INT);
                $statement->bindValue(':option_name', $optionName, PDO::PARAM_STR);
                $statement->bindValue(':additional_price', $additionalPrice, PDO::PARAM_INT);
                $statement->bindValue(':display_order', $optionIndex + 1, PDO::PARAM_INT);
                $statement->execute();
            }
        }
    }

    private function upsertOptionGroups(int $productId, array $optionGroups): void
    {
        $pdo = db();

        foreach ($optionGroups as $groupIndex => $group) {
            $optionGroupId = (int)($group['option_group_id'] ?? 0);

            if ($optionGroupId > 0 && $this->productHasOptionGroup($productId, $optionGroupId)) {
                $groupSql = <<<SQL
                    UPDATE option_groups
                    SET
                        option_group_name = :option_group_name,
                        selection_type = :selection_type,
                        is_required = :is_required,
                        updated_at = NOW()
                    WHERE option_group_id = :option_group_id
                SQL;

                $statement = $pdo->prepare($groupSql);
                $statement->bindValue(':option_group_name', $group['group_name'], PDO::PARAM_STR);
                $statement->bindValue(':selection_type', $group['selection_type'], PDO::PARAM_STR);
                $statement->bindValue(':is_required', $group['is_required'], PDO::PARAM_INT);
                $statement->bindValue(':option_group_id', $optionGroupId, PDO::PARAM_INT);
                $statement->execute();

                $linkSql = <<<SQL
                    UPDATE product_option_groups
                    SET
                        display_order = :display_order,
                        updated_at = NOW()
                    WHERE product_id = :product_id
                      AND option_group_id = :option_group_id
                SQL;

                $statement = $pdo->prepare($linkSql);
                $statement->bindValue(':display_order', $groupIndex + 1, PDO::PARAM_INT);
                $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $statement->bindValue(':option_group_id', $optionGroupId, PDO::PARAM_INT);
                $statement->execute();

                $this->upsertOptions($optionGroupId, $group['options']);
                continue;
            }

            $this->insertOptionGroups($productId, [$group]);
        }
    }

    private function productHasOptionGroup(int $productId, int $optionGroupId): bool
    {
        $sql = <<<SQL
            SELECT 1
            FROM product_option_groups
            WHERE product_id = :product_id
              AND option_group_id = :option_group_id
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $statement->bindValue(':option_group_id', $optionGroupId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    private function upsertOptions(int $optionGroupId, array $options): void
    {
        $pdo = db();

        foreach ($options as $optionIndex => $option) {
            $optionId = (int)($option['option_id'] ?? 0);
            $optionName = is_array($option) ? (string)$option['option_name'] : (string)$option;
            $additionalPrice = is_array($option) ? (int)($option['additional_price'] ?? 0) : 0;

            if ($optionId > 0 && $this->optionBelongsToGroup($optionGroupId, $optionId)) {
                $sql = <<<SQL
                    UPDATE options
                    SET
                        option_name = :option_name,
                        additional_price = :additional_price,
                        display_order = :display_order,
                        updated_at = NOW()
                    WHERE option_id = :option_id
                      AND option_group_id = :option_group_id
                SQL;

                $statement = $pdo->prepare($sql);
                $statement->bindValue(':option_name', $optionName, PDO::PARAM_STR);
                $statement->bindValue(':additional_price', $additionalPrice, PDO::PARAM_INT);
                $statement->bindValue(':display_order', $optionIndex + 1, PDO::PARAM_INT);
                $statement->bindValue(':option_id', $optionId, PDO::PARAM_INT);
                $statement->bindValue(':option_group_id', $optionGroupId, PDO::PARAM_INT);
                $statement->execute();
                continue;
            }

            $sql = <<<SQL
                INSERT INTO options (
                    option_group_id,
                    option_name,
                    additional_price,
                    display_order,
                    created_at,
                    updated_at
                ) VALUES (
                    :option_group_id,
                    :option_name,
                    :additional_price,
                    :display_order,
                    NOW(),
                    NOW()
                )
            SQL;

            $statement = $pdo->prepare($sql);
            $statement->bindValue(':option_group_id', $optionGroupId, PDO::PARAM_INT);
            $statement->bindValue(':option_name', $optionName, PDO::PARAM_STR);
            $statement->bindValue(':additional_price', $additionalPrice, PDO::PARAM_INT);
            $statement->bindValue(':display_order', $optionIndex + 1, PDO::PARAM_INT);
            $statement->execute();
        }
    }

    private function optionBelongsToGroup(int $optionGroupId, int $optionId): bool
    {
        $sql = <<<SQL
            SELECT 1
            FROM options
            WHERE option_group_id = :option_group_id
              AND option_id = :option_id
            LIMIT 1
        SQL;

        $statement = db()->prepare($sql);
        $statement->bindValue(':option_group_id', $optionGroupId, PDO::PARAM_INT);
        $statement->bindValue(':option_id', $optionId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    private function normalizeOptionGroups(mixed $rawGroups): array
    {
        if (!is_array($rawGroups)) {
            return [];
        }

        $groups = [];

        foreach ($rawGroups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $groupName = trim((string)($group['group_name'] ?? ''));
            $optionGroupId = filter_var($group['option_group_id'] ?? 0, FILTER_VALIDATE_INT);
            $selectionType = (string)($group['selection_type'] ?? 'SINGLE') === 'MULTIPLE'
                ? 'MULTIPLE'
                : 'SINGLE';
            $isRequired = isset($group['is_required']) && (string)$group['is_required'] === '1' ? 1 : 0;
            $rawOptions = $group['options'] ?? [];

            if (!is_array($rawOptions)) {
                $rawOptions = [];
            }

            $options = [];

            foreach ($rawOptions as $rawOption) {
                $optionId = 0;
                $optionName = '';
                $additionalPrice = 0;

                if (is_array($rawOption)) {
                    $optionId = (int)($rawOption['option_id'] ?? 0);
                    $optionName = trim((string)($rawOption['option_name'] ?? ''));
                    $validatedPrice = filter_var(
                        $rawOption['additional_price'] ?? 0,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 0]]
                    );
                    $additionalPrice = $validatedPrice === false ? 0 : (int)$validatedPrice;
                } else {
                    $optionName = trim((string)$rawOption);
                }

                if ($optionName !== '') {
                    $options[] = [
                        'option_id' => $optionId,
                        'option_name' => $optionName,
                        'additional_price' => $additionalPrice,
                    ];
                }
            }

            if ($groupName !== '' && $options !== []) {
                $groups[] = [
                    'option_group_id' => $optionGroupId === false ? 0 : (int)$optionGroupId,
                    'group_name' => $groupName,
                    'selection_type' => $selectionType,
                    'is_required' => $isRequired,
                    'options' => $options,
                ];
            }
        }

        return $groups;
    }

    private function saveImageFile(?array $imageFile): ?string
    {
        if ($imageFile === null || ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($imageFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('画像のアップロードに失敗しました。');
        }

        $tmpName = (string)($imageFile['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('アップロード画像を確認できませんでした。');
        }

        $mimeType = mime_content_type($tmpName);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mimeType])) {
            throw new InvalidArgumentException('画像はjpg、png、webpのいずれかを選択してください。');
        }

        $relativeDirectory = '/assets/images/products';
        $absoluteDirectory = dirname(__DIR__, 2) . '/public' . $relativeDirectory;

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('画像保存先を作成できませんでした。');
        }

        $fileName = 'product_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extensions[$mimeType];
        $absolutePath = $absoluteDirectory . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $absolutePath)) {
            throw new RuntimeException('画像を保存できませんでした。');
        }

        return $relativeDirectory . '/' . $fileName;
    }

    private function formatProductRow(array $row): array
    {
        $price = (int)$row['price'];
        $taxRate = (float)$row['tax_rate'];
        $taxIncludedPrice = (int)floor($price * (1 + ($taxRate / 100)));

        return [
            'id' => (int)$row['product_id'],
            'product_id' => (int)$row['product_id'],
            'name' => (string)$row['product_name'],
            'product_name' => (string)$row['product_name'],
            'category_id' => (int)$row['category_id'],
            'category' => (string)$row['category_name'],
            'category_name' => (string)$row['category_name'],
            'price' => $price,
            'tax_rate' => $taxRate,
            'tax_included_price' => $taxIncludedPrice,
            'product_sale_status' => (string)$row['product_sale_status'],
            'store_sale_status' => (string)$row['store_sale_status'],
            'sale_status' => (string)$row['store_sale_status'],
            'image_path' => $row['image_path'] === null ? '' : (string)$row['image_path'],
            'display_order' => (int)$row['display_order'],
            'has_options' => (int)$row['has_options'] === 1,
            'option_groups' => $this->optionGroupsForProduct((int)$row['product_id']),
        ];
    }
}
