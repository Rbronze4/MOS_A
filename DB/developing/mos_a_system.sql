-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-05-27 09:36:32
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
  `order_id` bigint(20) DEFAULT NULL COMMENT '注文ID',
  `customer_id` bigint(20) NOT NULL COMMENT '顧客ID',
  `cart_status` enum('ACTIVE','ORDERED','CLOSED') NOT NULL DEFAULT 'ACTIVE' COMMENT 'かご状態',
  `exclusive_control_no` bigint(20) NOT NULL DEFAULT 0 COMMENT '排他制御用番号',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  `table_number` varchar(20) NOT NULL COMMENT '卓番号',
  `store_id` char(2) NOT NULL COMMENT '店舗ID'
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
  `display_unit_price` int(11) NOT NULL COMMENT '画面表示単価',
  `added_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '追加日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `customers`
--

CREATE TABLE `customers` (
  `customer_id` bigint(20) NOT NULL COMMENT '顧客ID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `qr_identifier` varchar(100) NOT NULL COMMENT 'QR識別値',
  `qr_token_hash` varchar(255) NOT NULL COMMENT 'QRトークンハッシュ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `people_count` int(11) NOT NULL DEFAULT 1 COMMENT '人数',
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
  `scheduled_end_at` datetime NOT NULL COMMENT '終了予定時刻',
  `unit_price` int(11) NOT NULL COMMENT '単価',
  `plan_status` enum('ACTIVE','ENDED','CANCELLED') NOT NULL DEFAULT 'ACTIVE' COMMENT '状態',
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
  `customer_id` bigint(20) NOT NULL COMMENT '顧客ID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `customer_plan_id` bigint(20) DEFAULT NULL COMMENT '顧客プランID',
  `table_number` varchar(20) NOT NULL COMMENT '卓番号',
  `billing_status` enum('ACTIVE','PAYMENT_PENDING','PAID','UNCOLLECTED','CANCELLED') NOT NULL DEFAULT 'ACTIVE' COMMENT '会計状況',
  `reserve_key` varchar(100) DEFAULT NULL COMMENT '置きキー',
  `ordered_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '注文時刻',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  `order_hash` varchar(255) DEFAULT NULL COMMENT '注文ハッシュ'
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
  `cancelled_at` datetime DEFAULT NULL COMMENT '取消時刻',
  `detail_status` enum('ORDERED','PROVIDED','CANCELLED') NOT NULL DEFAULT 'ORDERED' COMMENT '注文詳細状況'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `order_management`
--

CREATE TABLE `order_management` (
  `order_detail_id` bigint(20) NOT NULL COMMENT '注文詳細ID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `provide_status` enum('NOT_PROVIDED','PROVIDING','PROVIDED','CANCELLED') NOT NULL DEFAULT 'NOT_PROVIDED' COMMENT '提供状況',
  `provided_quantity` int(11) NOT NULL DEFAULT 0 COMMENT '提供数量',
  `provided_at` datetime DEFAULT NULL COMMENT '提供時刻',
  `cancelled_at` datetime DEFAULT NULL COMMENT 'キャンセル時間',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新時刻'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `plans`
--

CREATE TABLE `plans` (
  `plan_id` bigint(20) NOT NULL COMMENT 'プランID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `plan_name` varchar(100) NOT NULL COMMENT 'プラン名',
  `time_limit_minutes` int(11) NOT NULL COMMENT '制限時間 分',
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時'
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
  `is_sold_out` tinyint(1) NOT NULL DEFAULT 0 COMMENT '売り切れフラグ',
  `is_on_sale` tinyint(1) NOT NULL DEFAULT 1 COMMENT '販売中フラグ',
  `category_id` bigint(20) NOT NULL COMMENT 'カテゴリID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` bigint(20) NOT NULL COMMENT 'カテゴリID',
  `category_name` varchar(100) NOT NULL COMMENT 'カテゴリ名'
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
  `session_status` enum('ACTIVE','PAYMENT_PENDING','PAID','EXPIRED','CLOSED','REMOVED') NOT NULL DEFAULT 'ACTIVE' COMMENT 'セッション状態',
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
  `business_hours` varchar(100) DEFAULT NULL COMMENT '営業時間',
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
  `login_id` varchar(100) NOT NULL COMMENT 'ログインID',
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
  ADD KEY `idx_carts_order_id` (`order_id`),
  ADD KEY `idx_carts_customer_id` (`customer_id`),
  ADD KEY `idx_carts_store_id` (`store_id`),
  ADD KEY `idx_carts_status` (`cart_status`);

--
-- テーブルのインデックス `cart_details`
--
ALTER TABLE `cart_details`
  ADD PRIMARY KEY (`cart_detail_id`),
  ADD KEY `idx_cart_details_cart_id` (`cart_id`),
  ADD KEY `idx_cart_details_product_id` (`product_id`);

--
-- テーブルのインデックス `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `uq_customers_qr_identifier` (`qr_identifier`),
  ADD KEY `idx_customers_store_id` (`store_id`);

--
-- テーブルのインデックス `customer_plans`
--
ALTER TABLE `customer_plans`
  ADD PRIMARY KEY (`customer_plan_id`),
  ADD KEY `idx_customer_plans_customer_id` (`customer_id`),
  ADD KEY `idx_customer_plans_plan_id` (`plan_id`),
  ADD KEY `idx_customer_plans_status` (`plan_status`);

--
-- テーブルのインデックス `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_session_id` (`session_id`),
  ADD KEY `idx_orders_customer_id` (`customer_id`),
  ADD KEY `idx_orders_store_id` (`store_id`),
  ADD KEY `idx_orders_customer_plan_id` (`customer_plan_id`),
  ADD KEY `idx_orders_billing_status` (`billing_status`);

--
-- テーブルのインデックス `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`order_detail_id`),
  ADD KEY `idx_order_details_order_id` (`order_id`),
  ADD KEY `idx_order_details_product_id` (`product_id`),
  ADD KEY `idx_order_details_status` (`detail_status`);

--
-- テーブルのインデックス `order_management`
--
ALTER TABLE `order_management`
  ADD PRIMARY KEY (`order_detail_id`),
  ADD KEY `idx_order_management_store_id` (`store_id`),
  ADD KEY `idx_order_management_status` (`provide_status`);

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
  ADD UNIQUE KEY `uq_store_accounts_login_id` (`login_id`),
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
  ADD CONSTRAINT `fk_carts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_carts_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_carts_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `cart_details`
--
ALTER TABLE `cart_details`
  ADD CONSTRAINT `fk_cart_details_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;

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
-- テーブルの制約 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_customer_plan` FOREIGN KEY (`customer_plan_id`) REFERENCES `customer_plans` (`customer_plan_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `fk_order_details_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `order_management`
--
ALTER TABLE `order_management`
  ADD CONSTRAINT `fk_order_management_order_detail` FOREIGN KEY (`order_detail_id`) REFERENCES `order_details` (`order_detail_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_management_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;

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
