-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-07-04 17:51:23
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
  `plan_type_id` bigint(20) NOT NULL COMMENT 'プラン区分ID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `time_limit_minutes` int(11) NOT NULL COMMENT '制限時間(分)',
  `price` int(11) NOT NULL COMMENT '価格',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `plans`
--

INSERT INTO `plans` (`plan_id`, `plan_type_id`, `store_id`, `time_limit_minutes`, `price`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 1, 'FB', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(2, 1, 'FB', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(3, 2, 'FB', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(4, 2, 'FB', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(5, 1, 'HM', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(6, 1, 'HM', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(7, 2, 'HM', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(8, 2, 'HM', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(9, 1, 'IM', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(10, 1, 'IM', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(11, 2, 'IM', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(12, 2, 'IM', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(13, 1, 'KB', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(14, 1, 'KB', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(15, 2, 'KB', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(16, 2, 'KB', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(17, 1, 'MH', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(18, 1, 'MH', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(19, 2, 'MH', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(20, 2, 'MH', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(21, 1, 'MN', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(22, 1, 'MN', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(23, 2, 'MN', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(24, 2, 'MN', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(25, 1, 'NB', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(26, 1, 'NB', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(27, 2, 'NB', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(28, 2, 'NB', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(29, 1, 'TH', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(30, 1, 'TH', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(31, 2, 'TH', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(32, 2, 'TH', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(33, 1, 'TM', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(34, 1, 'TM', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(35, 2, 'TM', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(36, 2, 'TM', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(37, 1, 'TY', 180, 3000, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(38, 1, 'TY', 120, 2200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(39, 2, 'TY', 180, 4200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1),
(40, 2, 'TY', 120, 3200, '2026-07-05 00:36:27', '2026-07-05 00:36:27', 1);

-- --------------------------------------------------------

--
-- テーブルの構造 `plan_types`
--

CREATE TABLE `plan_types` (
  `plan_type_id` bigint(20) NOT NULL COMMENT 'プラン区分ID',
  `plan_type_name` varchar(100) NOT NULL COMMENT 'プラン区分名',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='プラン区分';

--
-- テーブルのデータのダンプ `plan_types`
--

INSERT INTO `plan_types` (`plan_type_id`, `plan_type_name`, `created_at`, `updated_at`) VALUES
(1, 'スタンダード', '2026-07-05 00:26:44', '2026-07-05 00:26:44'),
(2, 'プレミアム', '2026-07-05 00:26:44', '2026-07-05 00:26:44');

-- --------------------------------------------------------

--
-- テーブルの構造 `plan_type_products`
--

CREATE TABLE `plan_type_products` (
  `plan_type_id` bigint(20) NOT NULL COMMENT 'プラン区分ID',
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='プラン対象商品';

--
-- テーブルのデータのダンプ `plan_type_products`
--

INSERT INTO `plan_type_products` (`plan_type_id`, `product_id`, `created_at`, `updated_at`) VALUES
(1, 7, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 10, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 5, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 6, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 7, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 8, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 9, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 10, '2026-07-05 00:41:21', '2026-07-05 00:41:21');

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時',
  `image_path` varchar(255) DEFAULT NULL COMMENT '画像'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `price`, `tax_rate`, `sale_status`, `category_id`, `created_at`, `updated_at`, `image_path`) VALUES
(1, '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(2, 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(3, '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(4, '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(5, 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(6, 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(7, 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(8, '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(9, 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(10, 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(11, 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(12, 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(13, '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(14, '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(15, 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(16, 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(17, '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(18, 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(19, '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(20, '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(21, 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(22, '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(23, '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(24, '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(25, '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(26, '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png');

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

--
-- テーブルのデータのダンプ `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `category_name`, `created_at`, `updated_at`) VALUES
(1, 'ドリンク', '2026-07-04 23:01:21', '2026-07-04 23:01:21'),
(2, '串', '2026-07-04 23:01:21', '2026-07-04 23:01:21'),
(3, '一品', '2026-07-04 23:01:21', '2026-07-04 23:01:21'),
(4, '揚げ物', '2026-07-04 23:01:21', '2026-07-04 23:01:21'),
(5, 'ご飯もの', '2026-07-04 23:01:21', '2026-07-04 23:01:21'),
(6, '期間限定', '2026-07-04 23:01:21', '2026-07-04 23:01:21'),
(7, '店舗限定', '2026-07-04 23:01:21', '2026-07-04 23:01:21');

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

--
-- テーブルのデータのダンプ `stores`
--

INSERT INTO `stores` (`store_id`, `store_name`, `is_active`, `created_at`, `updated_at`) VALUES
('FB', '深江橋店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('HM', '本町店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('IM', '今里店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('KB', '京橋店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('MH', '緑橋本店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('MN', '森ノ宮店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('NB', 'なんば店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('TH', '鶴橋店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('TM', '玉造店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39'),
('TY', '谷町四丁目店', 1, '2026-07-04 22:53:39', '2026-07-04 22:53:39');

-- --------------------------------------------------------

--
-- テーブルの構造 `store_accounts`
--

CREATE TABLE `store_accounts` (
  `account_id` bigint(20) NOT NULL COMMENT 'アカウントID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `login_id` varchar(50) NOT NULL COMMENT 'ログインID',
  `password_hash` varchar(255) NOT NULL COMMENT 'パスワードハッシュ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `store_accounts`
--

INSERT INTO `store_accounts` (`account_id`, `store_id`, `login_id`, `password_hash`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'MH', 'midoribashi', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(2, 'MN', 'morinomiya', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(3, 'TM', 'tamatsukuri', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(4, 'TH', 'tsuruhashi', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(5, 'IM', 'imazato', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(6, 'FB', 'fukaebashi', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(7, 'TY', 'tanimachi', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(8, 'HM', 'honmachi', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(9, 'KB', 'kyobashi', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09'),
(10, 'NB', 'namba', '$2y$12$kpZ5YOwkiZ9Dh76YmQniq.U8Kzuntgn5IY4btoHYy63Ebh9r4W2C.', 1, '2026-07-04 22:57:09', '2026-07-04 22:57:09');

-- --------------------------------------------------------

--
-- テーブルの構造 `store_products`
--

CREATE TABLE `store_products` (
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `sale_status` enum('ON_SALE','SOLD_OUT','HIDDEN') NOT NULL DEFAULT 'ON_SALE' COMMENT '販売状態',
  `display_order` int(11) NOT NULL DEFAULT 1 COMMENT '表示順',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='店舗別商品設定';

--
-- テーブルのデータのダンプ `store_products`
--

INSERT INTO `store_products` (`store_id`, `product_id`, `sale_status`, `display_order`, `created_at`, `updated_at`) VALUES
('FB', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('FB', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('HM', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('IM', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('KB', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MH', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('MN', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('NB', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TH', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TM', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 1, 'ON_SALE', 1, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 2, 'ON_SALE', 2, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 3, 'ON_SALE', 3, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 4, 'ON_SALE', 4, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 5, 'ON_SALE', 5, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 6, 'ON_SALE', 6, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 7, 'ON_SALE', 7, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 8, 'ON_SALE', 8, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 9, 'ON_SALE', 9, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 10, 'ON_SALE', 10, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 11, 'ON_SALE', 11, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 12, 'ON_SALE', 12, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 13, 'ON_SALE', 13, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 14, 'ON_SALE', 14, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 15, 'ON_SALE', 15, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 16, 'ON_SALE', 16, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 17, 'ON_SALE', 17, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 18, 'ON_SALE', 18, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 19, 'ON_SALE', 19, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 20, 'ON_SALE', 20, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 21, 'ON_SALE', 21, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 22, 'ON_SALE', 22, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 23, 'ON_SALE', 23, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 24, 'ON_SALE', 24, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 25, 'ON_SALE', 25, '2026-07-05 00:04:03', '2026-07-05 00:04:03'),
('TY', 26, 'ON_SALE', 26, '2026-07-05 00:04:03', '2026-07-05 00:04:03');

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
  ADD UNIQUE KEY `uq_plans_store_type_time` (`store_id`,`plan_type_id`,`time_limit_minutes`),
  ADD KEY `idx_plans_store_id` (`store_id`),
  ADD KEY `fk_plans_plan_type` (`plan_type_id`);

--
-- テーブルのインデックス `plan_types`
--
ALTER TABLE `plan_types`
  ADD PRIMARY KEY (`plan_type_id`),
  ADD UNIQUE KEY `uq_plan_types_name` (`plan_type_name`);

--
-- テーブルのインデックス `plan_type_products`
--
ALTER TABLE `plan_type_products`
  ADD PRIMARY KEY (`plan_type_id`,`product_id`),
  ADD KEY `idx_plan_type_products_product_id` (`product_id`);

--
-- テーブルのインデックス `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_products_category_id` (`category_id`);

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
  ADD UNIQUE KEY `uq_store_accounts_login_id` (`login_id`),
  ADD UNIQUE KEY `uq_store_accounts_store_id` (`store_id`),
  ADD KEY `idx_store_accounts_store_id` (`store_id`);

--
-- テーブルのインデックス `store_products`
--
ALTER TABLE `store_products`
  ADD PRIMARY KEY (`store_id`,`product_id`),
  ADD KEY `idx_store_products_product_id` (`product_id`);

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
  MODIFY `plan_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'プランID', AUTO_INCREMENT=64;

--
-- テーブルの AUTO_INCREMENT `plan_types`
--
ALTER TABLE `plan_types`
  MODIFY `plan_type_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'プラン区分ID', AUTO_INCREMENT=3;

--
-- テーブルの AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `product_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '商品ID', AUTO_INCREMENT=512;

--
-- テーブルの AUTO_INCREMENT `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'カテゴリID', AUTO_INCREMENT=8;

--
-- テーブルの AUTO_INCREMENT `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'セッションID';

--
-- テーブルの AUTO_INCREMENT `store_accounts`
--
ALTER TABLE `store_accounts`
  MODIFY `account_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'アカウントID', AUTO_INCREMENT=11;

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
  ADD CONSTRAINT `fk_plans_plan_type` FOREIGN KEY (`plan_type_id`) REFERENCES `plan_types` (`plan_type_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_plans_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON UPDATE CASCADE;

--
-- テーブルの制約 `plan_type_products`
--
ALTER TABLE `plan_type_products`
  ADD CONSTRAINT `fk_plan_type_products_plan_type` FOREIGN KEY (`plan_type_id`) REFERENCES `plan_types` (`plan_type_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_plan_type_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- テーブルの制約 `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON UPDATE CASCADE;

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

--
-- テーブルの制約 `store_products`
--
ALTER TABLE `store_products`
  ADD CONSTRAINT `fk_store_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_store_products_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
