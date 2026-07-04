-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-07-04 07:04:54
-- サーバのバージョン： 10.4.32-MariaDB
-- PHP のバージョン: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `mos_a_system`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `carts`
--

CREATE TABLE `carts` (
  `cart_id` bigint(20) NOT NULL COMMENT 'かごID',
  `session_id` bigint(20) NOT NULL COMMENT 'セッションID',
  `version_no` bigint(20) NOT NULL DEFAULT 0 COMMENT '排他制御用版番号',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `cart_details`
--

CREATE TABLE `cart_details` (
  `cart_detail_id` bigint(20) NOT NULL COMMENT 'かご明細ID',
  `cart_id` bigint(20) NOT NULL COMMENT 'かごID',
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `quantity` int(11) NOT NULL COMMENT '数量',
  `display_unit_price` int(11) NOT NULL COMMENT '画面表示時単価',
  `added_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '追加日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `cart_detail_options`
--

CREATE TABLE `cart_detail_options` (
  `cart_detail_id` bigint(20) NOT NULL COMMENT 'かご明細ID',
  `option_id` bigint(20) NOT NULL COMMENT 'オプションID',
  `selected_option_name` varchar(100) NOT NULL COMMENT '選択時オプション名',
  `selected_additional_price` int(11) NOT NULL DEFAULT 0 COMMENT '選択時追加料金',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `customers`
--

CREATE TABLE `customers` (
  `customer_id` bigint(20) NOT NULL COMMENT '顧客ID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `qr_token_hash` varchar(255) NOT NULL COMMENT 'QRトークンハッシュ',
  `people_count` int(11) NOT NULL DEFAULT 1 COMMENT '人数',
  `billing_status` enum('UNPAID','PAYMENT_PENDING','PAID','CANCELLED') NOT NULL DEFAULT 'UNPAID' COMMENT '会計状況',
  `order_hash` varchar(64) DEFAULT NULL COMMENT '注文ハッシュ',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `customer_plans`
--

CREATE TABLE `customer_plans` (
  `customer_plan_id` bigint(20) NOT NULL COMMENT '顧客プランID',
  `customer_id` bigint(20) NOT NULL COMMENT '顧客ID',
  `plan_id` bigint(20) NOT NULL COMMENT 'プランID',
  `started_at` datetime NOT NULL COMMENT '開始日時',
  `ended_at` datetime DEFAULT NULL COMMENT '終了時刻',
  `unit_price` int(11) NOT NULL COMMENT '単価',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `options`
--

CREATE TABLE `options` (
  `option_id` bigint(20) NOT NULL COMMENT 'オプションID',
  `option_group_id` bigint(20) NOT NULL COMMENT 'オプショングループID',
  `option_name` varchar(100) NOT NULL COMMENT 'オプション名',
  `additional_price` int(11) NOT NULL DEFAULT 0 COMMENT '追加料金',
  `display_order` int(11) NOT NULL DEFAULT 1 COMMENT '表示順',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `option_groups`
--

CREATE TABLE `option_groups` (
  `option_group_id` bigint(20) NOT NULL COMMENT 'オプショングループID',
  `option_group_name` varchar(100) NOT NULL COMMENT 'オプショングループ名',
  `selection_type` enum('SINGLE','MULTIPLE') NOT NULL COMMENT '選択式',
  `is_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT '必須フラグ',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `orders`
--

CREATE TABLE `orders` (
  `order_id` bigint(20) NOT NULL COMMENT '注文ID',
  `session_id` bigint(20) NOT NULL COMMENT 'セッションID',
  `idempotency_key` varchar(100) DEFAULT NULL COMMENT '冪等キー',
  `ordered_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '注文日時',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `order_details`
--

CREATE TABLE `order_details` (
  `order_detail_id` bigint(20) NOT NULL COMMENT '注文詳細ID',
  `order_id` bigint(20) NOT NULL COMMENT '注文ID',
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `ordered_product_name` varchar(100) NOT NULL COMMENT '注文時商品名',
  `quantity` int(11) NOT NULL COMMENT '注文数量',
  `ordered_unit_price` int(11) NOT NULL COMMENT '注文時単価',
  `plan_applied_flag` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'プラン適用フラグ',
  `ordered_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '注文時刻',
  `detail_status` enum('ORDERED','PROVIDED','CANCELLED') NOT NULL DEFAULT 'ORDERED' COMMENT '注文詳細状況',
  `provided_quantity` int(11) NOT NULL DEFAULT 0 COMMENT '提供数',
  `provided_at` datetime DEFAULT NULL COMMENT '提供時刻',
  `cancelled_at` datetime DEFAULT NULL COMMENT 'キャンセル時刻',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `order_detail_options`
--

CREATE TABLE `order_detail_options` (
  `order_detail_id` bigint(20) NOT NULL COMMENT '注文詳細ID',
  `option_id` bigint(20) NOT NULL COMMENT 'オプションID',
  `ordered_option_name` varchar(100) NOT NULL COMMENT '注文時オプション名',
  `ordered_additional_price` int(11) NOT NULL DEFAULT 0 COMMENT '注文時追加料金',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `plans`
--

CREATE TABLE `plans` (
  `plan_id` bigint(20) NOT NULL COMMENT 'プランID',
  `plan_name` varchar(100) NOT NULL COMMENT 'プラン名',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `time_limit_minutes` int(11) NOT NULL COMMENT '制限時間(分)',
  `price` int(11) NOT NULL COMMENT '価格',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `plan_products`
--

CREATE TABLE `plan_products` (
  `plan_id` bigint(20) NOT NULL COMMENT 'プランID',
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `products`
--

CREATE TABLE `products` (
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `product_name` varchar(100) NOT NULL COMMENT '商品名',
  `price` int(11) NOT NULL COMMENT '値段',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT '税率',
  `sale_status` enum('ON_SALE','SOLD_OUT','HIDDEN') NOT NULL DEFAULT 'ON_SALE' COMMENT '販売状態',
  `category_id` bigint(20) NOT NULL COMMENT 'カテゴリID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  `image_path` varchar(255) DEFAULT NULL COMMENT '画像'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` bigint(20) NOT NULL COMMENT 'カテゴリID',
  `category_name` varchar(100) NOT NULL COMMENT 'カテゴリ名',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `product_option_groups`
--

CREATE TABLE `product_option_groups` (
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `option_group_id` bigint(20) NOT NULL COMMENT 'オプショングループID',
  `display_order` int(11) NOT NULL DEFAULT 1 COMMENT '表示順',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `sessions`
--

CREATE TABLE `sessions` (
  `session_id` bigint(20) NOT NULL COMMENT 'セッションID',
  `customer_id` bigint(20) NOT NULL COMMENT '顧客ID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `table_number` varchar(20) NOT NULL COMMENT '卓番号',
  `session_status` enum('ACTIVE','EXPIRED','CLOSED') NOT NULL DEFAULT 'ACTIVE' COMMENT 'セッション状態',
  `started_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '開始時刻',
  `expired_at` datetime DEFAULT NULL COMMENT '失効時刻',
  `ended_at` datetime DEFAULT NULL COMMENT '終了時刻',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `stores`
--

CREATE TABLE `stores` (
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `store_name` varchar(100) NOT NULL COMMENT '店舗名',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `store_accounts`
--

CREATE TABLE `store_accounts` (
  `account_id` bigint(20) NOT NULL COMMENT 'アカウントID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `password_hash` varchar(255) NOT NULL COMMENT 'パスワードハッシュ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `idx_carts_session_id` (`session_id`);

--
-- テーブルのインデックス `cart_details`
--
ALTER TABLE `cart_details`
  ADD PRIMARY KEY (`cart_detail_id`),
  ADD KEY `idx_cart_details_cart_id` (`cart_id`),
  ADD KEY `idx_cart_details_product_id` (`product_id`);

--
-- テーブルのインデックス `cart_detail_options`
--
ALTER TABLE `cart_detail_options`
  ADD PRIMARY KEY (`cart_detail_id`,`option_id`),
  ADD KEY `idx_cart_detail_options_option_id` (`option_id`);

--
-- テーブルのインデックス `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `idx_customers_store_id` (`store_id`);

--
-- テーブルのインデックス `customer_plans`
--
ALTER TABLE `customer_plans`
  ADD PRIMARY KEY (`customer_plan_id`),
  ADD KEY `idx_customer_plans_customer_id` (`customer_id`),
  ADD KEY `idx_customer_plans_plan_id` (`plan_id`);

--
-- テーブルのインデックス `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `idx_options_group_id` (`option_group_id`);

--
-- テーブルのインデックス `option_groups`
--
ALTER TABLE `option_groups`
  ADD PRIMARY KEY (`option_group_id`);

--
-- テーブルのインデックス `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_session_id` (`session_id`);

--
-- テーブルのインデックス `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`order_detail_id`),
  ADD KEY `idx_order_details_order_id` (`order_id`),
  ADD KEY `idx_order_details_product_id` (`product_id`),
  ADD KEY `idx_order_details_status` (`detail_status`);

--
-- テーブルのインデックス `order_detail_options`
--
ALTER TABLE `order_detail_options`
  ADD PRIMARY KEY (`order_detail_id`,`option_id`),
  ADD KEY `idx_order_detail_options_option_id` (`option_id`);

--
-- テーブルのインデックス `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `idx_plans_store_id` (`store_id`);

--
-- テーブルのインデックス `plan_products`
--
ALTER TABLE `plan_products`
  ADD PRIMARY KEY (`plan_id`,`product_id`),
  ADD KEY `idx_plan_products_product_id` (`product_id`);

--
-- テーブルのインデックス `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_products_category_id` (`category_id`),
  ADD KEY `idx_products_store_id` (`store_id`);

--
-- テーブルのインデックス `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uq_product_categories_category_name` (`category_name`);

--
-- テーブルのインデックス `product_option_groups`
--
ALTER TABLE `product_option_groups`
  ADD PRIMARY KEY (`product_id`,`option_group_id`),
  ADD KEY `idx_product_option_groups_group_id` (`option_group_id`);

--
-- テーブルのインデックス `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_sessions_customer_id` (`customer_id`),
  ADD KEY `idx_sessions_store_id` (`store_id`);

--
-- テーブルのインデックス `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`store_id`);

--
-- テーブルのインデックス `store_accounts`
--
ALTER TABLE `store_accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `idx_store_accounts_store_id` (`store_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'かごID';

--
-- テーブルの AUTO_INCREMENT `cart_details`
--
ALTER TABLE `cart_details`
  MODIFY `cart_detail_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'かご明細ID';

--
-- テーブルの AUTO_INCREMENT `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '顧客ID';

--
-- テーブルの AUTO_INCREMENT `customer_plans`
--
ALTER TABLE `customer_plans`
  MODIFY `customer_plan_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '顧客プランID';

--
-- テーブルの AUTO_INCREMENT `options`
--
ALTER TABLE `options`
  MODIFY `option_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'オプションID';

--
-- テーブルの AUTO_INCREMENT `option_groups`
--
ALTER TABLE `option_groups`
  MODIFY `option_group_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'オプショングループID';

--
-- テーブルの AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '注文ID';

--
-- テーブルの AUTO_INCREMENT `order_details`
--
ALTER TABLE `order_details`
  MODIFY `order_detail_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '注文詳細ID';

--
-- テーブルの AUTO_INCREMENT `plans`
--
ALTER TABLE `plans`
  MODIFY `plan_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'プランID';

--
-- テーブルの AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `product_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '商品ID';

--
-- テーブルの AUTO_INCREMENT `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'カテゴリID';

--
-- テーブルの AUTO_INCREMENT `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'セッションID';

--
-- テーブルの AUTO_INCREMENT `store_accounts`
--
ALTER TABLE `store_accounts`
  MODIFY `account_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'アカウントID';

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_carts_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- テーブルの制約 `cart_details`
--
ALTER TABLE `cart_details`
  ADD CONSTRAINT `fk_cart_details_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `cart_detail_options`
--
ALTER TABLE `cart_detail_options`
  ADD CONSTRAINT `fk_cart_detail_options_cart_detail` FOREIGN KEY (`cart_detail_id`) REFERENCES `cart_details` (`cart_detail_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_detail_options_detail` FOREIGN KEY (`cart_detail_id`) REFERENCES `cart_details` (`cart_detail_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_detail_options_option` FOREIGN KEY (`option_id`) REFERENCES `options` (`option_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `customer_plans`
--
ALTER TABLE `customer_plans`
  ADD CONSTRAINT `fk_customer_plans_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customer_plans_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`plan_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `fk_options_group` FOREIGN KEY (`option_group_id`) REFERENCES `option_groups` (`option_group_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- テーブルの制約 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `fk_order_details_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `order_detail_options`
--
ALTER TABLE `order_detail_options`
  ADD CONSTRAINT `fk_order_detail_options_detail` FOREIGN KEY (`order_detail_id`) REFERENCES `order_details` (`order_detail_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_detail_options_option` FOREIGN KEY (`option_id`) REFERENCES `options` (`option_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `plans`
--
ALTER TABLE `plans`
  ADD CONSTRAINT `fk_plans_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `plan_products`
--
ALTER TABLE `plan_products`
  ADD CONSTRAINT `fk_plan_products_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`plan_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_plan_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `product_option_groups`
--
ALTER TABLE `product_option_groups`
  ADD CONSTRAINT `fk_product_option_groups_group` FOREIGN KEY (`option_group_id`) REFERENCES `option_groups` (`option_group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_option_groups_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- テーブルの制約 `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_sessions_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sessions_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `store_accounts`
--
ALTER TABLE `store_accounts`
  ADD CONSTRAINT `fk_store_accounts_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
