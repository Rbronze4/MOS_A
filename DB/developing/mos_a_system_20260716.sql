-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2026-07-16 15:27:22
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

--
-- テーブルのデータのダンプ `carts`
--

INSERT INTO `carts` (`cart_id`, `session_id`, `version_no`, `created_at`, `updated_at`) VALUES
(4, 1, 0, '2026-07-07 05:24:27', '2026-07-07 05:24:27'),
(5, 4, 0, '2026-07-07 05:31:30', '2026-07-07 05:31:30'),
(6, 5, 0, '2026-07-07 05:38:18', '2026-07-07 05:38:18'),
(7, 6, 0, '2026-07-07 05:39:04', '2026-07-07 05:39:04'),
(8, 7, 0, '2026-07-07 05:44:29', '2026-07-07 05:44:29'),
(9, 8, 0, '2026-07-07 05:45:02', '2026-07-07 05:45:02'),
(10, 9, 0, '2026-07-07 05:51:21', '2026-07-07 05:51:21'),
(11, 10, 0, '2026-07-07 05:52:51', '2026-07-07 05:52:51'),
(12, 11, 0, '2026-07-07 06:07:43', '2026-07-07 06:07:43'),
(13, 12, 0, '2026-07-07 13:41:03', '2026-07-07 13:41:03'),
(14, 13, 0, '2026-07-07 13:45:54', '2026-07-07 13:45:54'),
(15, 14, 0, '2026-07-07 14:09:42', '2026-07-07 14:09:42'),
(16, 15, 0, '2026-07-07 14:12:19', '2026-07-07 14:12:19');

-- --------------------------------------------------------

--
-- テーブルの構造 `cart_details`
--

CREATE TABLE `cart_details` (
  `cart_detail_id` bigint(20) NOT NULL COMMENT 'かご明細ID',
  `cart_id` bigint(20) NOT NULL COMMENT 'かごID',
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `quantity` int(11) NOT NULL COMMENT '数量',
  `added_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '追加日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `cart_details`
--

INSERT INTO `cart_details` (`cart_detail_id`, `cart_id`, `product_id`, `quantity`, `added_at`, `updated_at`) VALUES
(22, 12, 621, 1, '2026-07-07 06:08:00', '2026-07-16 22:12:55');

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
  `qr_token_hash` varchar(255) DEFAULT NULL COMMENT 'QRトークンのハッシュ値',
  `people_count` int(11) NOT NULL DEFAULT 1 COMMENT '人数',
  `billing_status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '会計状況 1:受付中 2:会計済み 4:未収金 8:会計中',
  `order_hash` varchar(64) DEFAULT NULL COMMENT '注文ハッシュ',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '作成日時',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '更新日時'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `customers`
--

INSERT INTO `customers` (`customer_id`, `store_id`, `qr_token_hash`, `people_count`, `billing_status`, `order_hash`, `created_at`, `updated_at`) VALUES
(1000001, 'MH', 'test_qr_token_hash_1000001', 2, 1, NULL, '2026-07-07 03:54:37', '2026-07-07 03:54:37'),
(1000002, 'MH', 'test_qr_token_hash_1000002', 2, 1, NULL, '2026-07-07 05:26:48', '2026-07-07 05:26:48'),
(1000003, 'MH', 'test_qr_token_hash_1000002', 2, 1, NULL, '2026-07-07 05:28:19', '2026-07-07 05:28:19'),
(1000004, 'MH', 'test_qr_token_hash_1000004', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000005, 'MH', 'test_qr_token_hash_1000005', 3, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000006, 'MH', 'test_qr_token_hash_1000006', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000007, 'MH', 'test_qr_token_hash_1000007', 5, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000008, 'MH', 'test_qr_token_hash_1000008', 6, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000009, 'MH', 'test_qr_token_hash_1000009', 1, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000010, 'MH', 'test_qr_token_hash_1000010', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000011, 'MH', 'test_qr_token_hash_1000011', 3, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000012, 'MH', 'test_qr_token_hash_1000012', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000013, 'MH', 'test_qr_token_hash_1000013', 5, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000014, 'MH', 'test_qr_token_hash_1000014', 6, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000015, 'MH', 'test_qr_token_hash_1000015', 1, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000016, 'MH', 'test_qr_token_hash_1000016', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000017, 'MH', 'test_qr_token_hash_1000017', 3, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000018, 'MH', 'test_qr_token_hash_1000018', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000019, 'MH', 'test_qr_token_hash_1000019', 5, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000020, 'MH', 'test_qr_token_hash_1000020', 6, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000021, 'MH', 'test_qr_token_hash_1000021', 1, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000022, 'MH', 'test_qr_token_hash_1000022', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000023, 'MH', 'test_qr_token_hash_1000023', 3, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000024, 'MH', 'test_qr_token_hash_1000024', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000025, 'MH', 'test_qr_token_hash_1000025', 5, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000026, 'MH', 'test_qr_token_hash_1000026', 6, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000027, 'MH', 'test_qr_token_hash_1000027', 1, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000028, 'MH', 'test_qr_token_hash_1000028', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000029, 'MH', 'test_qr_token_hash_1000029', 3, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000030, 'MH', 'test_qr_token_hash_1000030', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000031, 'MH', 'test_qr_token_hash_1000031', 5, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000032, 'MH', 'test_qr_token_hash_1000032', 6, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000033, 'MH', 'test_qr_token_hash_1000033', 1, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000034, 'MH', 'test_qr_token_hash_1000034', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000035, 'MH', 'test_qr_token_hash_1000035', 3, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000036, 'MH', 'test_qr_token_hash_1000036', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000037, 'MH', 'test_qr_token_hash_1000037', 5, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000038, 'MH', 'test_qr_token_hash_1000038', 6, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000039, 'MH', 'test_qr_token_hash_1000039', 1, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000040, 'MH', 'test_qr_token_hash_1000040', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000041, 'MH', 'test_qr_token_hash_1000041', 3, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000042, 'MH', 'test_qr_token_hash_1000042', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000043, 'MH', 'test_qr_token_hash_1000043', 5, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000044, 'MH', 'test_qr_token_hash_1000044', 6, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000045, 'MH', 'test_qr_token_hash_1000045', 1, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000046, 'MH', 'test_qr_token_hash_1000046', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000047, 'MH', 'test_qr_token_hash_1000047', 3, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000048, 'MH', 'test_qr_token_hash_1000048', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000049, 'MH', 'test_qr_token_hash_1000049', 5, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000050, 'MH', 'test_qr_token_hash_1000050', 6, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000051, 'MH', 'test_qr_token_hash_1000051', 2, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23'),
(1000052, 'MH', 'test_qr_token_hash_1000052', 4, 1, NULL, '2026-07-07 05:50:23', '2026-07-07 05:50:23');

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

--
-- テーブルのデータのダンプ `customer_plans`
--

INSERT INTO `customer_plans` (`customer_plan_id`, `customer_id`, `plan_id`, `started_at`, `ended_at`, `unit_price`, `created_at`, `updated_at`) VALUES
(1, 1000001, 18, '2026-07-07 05:13:23', NULL, 2200, '2026-07-07 05:13:23', '2026-07-07 05:13:23'),
(2, 1000003, 18, '2026-07-07 05:38:18', NULL, 2200, '2026-07-07 05:38:18', '2026-07-07 05:38:18'),
(3, 1000004, 17, '2026-07-07 05:51:20', NULL, 3000, '2026-07-07 05:51:20', '2026-07-07 05:51:20'),
(4, 1000040, 18, '2026-07-07 13:41:03', NULL, 2200, '2026-07-07 13:41:03', '2026-07-07 13:41:03'),
(5, 1000041, 18, '2026-07-07 14:09:42', NULL, 2200, '2026-07-07 14:09:42', '2026-07-07 14:09:42');

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

--
-- テーブルのデータのダンプ `options`
--

INSERT INTO `options` (`option_id`, `option_group_id`, `option_name`, `additional_price`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 1, '氷あり', 0, 1, '2026-07-07 07:18:50', '2026-07-07 07:38:44'),
(2, 1, '氷無し', 0, 2, '2026-07-07 07:18:50', '2026-07-07 07:38:44');

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

--
-- テーブルのデータのダンプ `option_groups`
--

INSERT INTO `option_groups` (`option_group_id`, `option_group_name`, `selection_type`, `is_required`, `created_at`, `updated_at`) VALUES
(1, '氷あり/なし', 'SINGLE', 1, '2026-07-07 07:18:50', '2026-07-07 07:38:44');

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

--
-- テーブルのデータのダンプ `orders`
--

INSERT INTO `orders` (`order_id`, `session_id`, `idempotency_key`, `ordered_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'customer-1-19ecdbcbb182331fe1d764eab0538aa7', '2026-07-07 04:51:26', '2026-07-07 04:51:26', '2026-07-07 04:51:26'),
(2, 2, 'customer-2-5fc93631bd5cc7499659175d6dbd165b', '2026-07-07 05:14:36', '2026-07-07 05:14:36', '2026-07-07 05:14:36'),
(3, 1, 'customer-1-c93f05112191ba2ae444c11433ef13fa', '2026-07-07 05:29:01', '2026-07-07 05:29:01', '2026-07-07 05:29:01'),
(4, 5, 'customer-5-b570dadeb5d8e757876ffd758cd0bfd1', '2026-07-07 05:38:58', '2026-07-07 05:38:58', '2026-07-07 05:38:58'),
(5, 7, 'customer-7-1f988b4375051e4b618cc898ed132e59', '2026-07-07 05:44:46', '2026-07-07 05:44:46', '2026-07-07 05:44:46'),
(6, 9, 'customer-9-e49c5cfee12e5431941a0e34bbef907c', '2026-07-07 05:51:42', '2026-07-07 05:51:42', '2026-07-07 05:51:42'),
(7, 9, 'customer-9-ada390be7ae6d2762f81c89d0439ee37', '2026-07-07 05:53:42', '2026-07-07 05:53:42', '2026-07-07 05:53:42'),
(8, 9, 'customer-9-ad72844ee5b97af8c0d0191e1ce3b48c', '2026-07-07 06:07:25', '2026-07-07 06:07:25', '2026-07-07 06:07:25'),
(9, 12, 'customer-12-024164194858c9020082ef08fdca1b4b', '2026-07-07 13:41:51', '2026-07-07 13:41:51', '2026-07-07 13:41:51'),
(10, 14, 'customer-14-f0cdf65c71f94d2c02a6af392955859b', '2026-07-07 14:10:49', '2026-07-07 14:10:49', '2026-07-07 14:10:49');

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

--
-- テーブルのデータのダンプ `order_details`
--

INSERT INTO `order_details` (`order_detail_id`, `order_id`, `product_id`, `ordered_product_name`, `quantity`, `ordered_unit_price`, `plan_applied_flag`, `ordered_at`, `detail_status`, `provided_quantity`, `provided_at`, `cancelled_at`, `created_at`, `updated_at`) VALUES
(1, 1, 621, 'ウーロン茶', 6, 300, 0, '2026-07-07 04:51:26', 'CANCELLED', 0, NULL, '2026-07-07 06:53:15', '2026-07-07 04:51:26', '2026-07-16 22:12:55'),
(2, 1, 631, 'ねぎま', 1, 190, 0, '2026-07-07 04:51:26', 'PROVIDED', 1, '2026-07-06 23:46:53', NULL, '2026-07-07 04:51:26', '2026-07-16 22:12:55'),
(3, 1, 623, 'レモンサワー', 6, 450, 0, '2026-07-07 04:51:26', 'ORDERED', 1, NULL, NULL, '2026-07-07 04:51:26', '2026-07-16 22:12:55'),
(4, 2, 621, 'ウーロン茶', 1, 300, 0, '2026-07-07 05:14:36', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:14:36', '2026-07-16 22:12:55'),
(5, 2, 624, '焼酎', 1, 450, 0, '2026-07-07 05:14:36', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:14:36', '2026-07-16 22:12:55'),
(6, 2, 631, 'ねぎま', 1, 190, 0, '2026-07-07 05:14:36', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:14:36', '2026-07-16 22:12:55'),
(7, 3, 626, 'ビール', 1, 500, 0, '2026-07-07 05:29:01', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:29:01', '2026-07-16 22:12:55'),
(8, 3, 623, 'レモンサワー', 1, 450, 0, '2026-07-07 05:29:01', 'CANCELLED', 0, NULL, '2026-07-07 14:18:46', '2026-07-07 05:29:01', '2026-07-16 22:12:55'),
(9, 4, 624, '焼酎', 1, 450, 0, '2026-07-07 05:38:58', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:38:58', '2026-07-16 22:12:55'),
(10, 4, 621, 'ウーロン茶', 1, 300, 0, '2026-07-07 05:38:58', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:38:58', '2026-07-16 22:12:55'),
(11, 5, 624, '焼酎', 6, 450, 0, '2026-07-07 05:44:46', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:44:46', '2026-07-16 22:12:55'),
(12, 5, 621, 'ウーロン茶', 1, 300, 0, '2026-07-07 05:44:46', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:44:46', '2026-07-16 22:12:55'),
(13, 6, 621, 'ウーロン茶', 1, 300, 0, '2026-07-07 05:51:42', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:51:42', '2026-07-16 22:12:55'),
(14, 6, 626, 'ビール', 1, 500, 0, '2026-07-07 05:51:42', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:51:42', '2026-07-16 22:12:55'),
(15, 6, 634, 'もも', 1, 180, 0, '2026-07-07 05:51:42', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:51:42', '2026-07-16 22:12:55'),
(16, 7, 621, 'ウーロン茶', 1, 300, 0, '2026-07-07 05:53:42', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:53:42', '2026-07-16 22:12:55'),
(17, 7, 626, 'ビール', 1, 500, 0, '2026-07-07 05:53:42', 'ORDERED', 0, NULL, NULL, '2026-07-07 05:53:42', '2026-07-16 22:12:55'),
(18, 8, 623, 'レモンサワー', 4, 0, 1, '2026-07-07 06:07:25', 'ORDERED', 0, NULL, NULL, '2026-07-07 06:07:25', '2026-07-16 22:12:55'),
(19, 8, 621, 'ウーロン茶', 3, 300, 0, '2026-07-07 06:07:25', 'CANCELLED', 0, NULL, '2026-07-07 13:38:01', '2026-07-07 06:07:25', '2026-07-16 22:12:55'),
(20, 9, 623, 'レモンサワー', 3, 0, 1, '2026-07-07 13:41:51', 'ORDERED', 0, NULL, NULL, '2026-07-07 13:41:51', '2026-07-16 22:12:55'),
(21, 10, 623, 'レモンサワー', 5, 0, 1, '2026-07-07 14:10:49', 'ORDERED', 0, NULL, NULL, '2026-07-07 14:10:49', '2026-07-16 22:12:55'),
(22, 10, 621, 'ウーロン茶', 5, 300, 0, '2026-07-07 14:10:49', 'CANCELLED', 0, NULL, '2026-07-07 14:17:27', '2026-07-07 14:10:49', '2026-07-16 22:12:55');

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
(1, 519, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 522, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 545, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 548, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 571, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 574, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 597, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 600, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 623, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 626, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 650, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 653, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 676, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 679, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 702, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 705, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 728, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 731, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 754, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(1, 757, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 517, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 518, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 519, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 520, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 521, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 522, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 543, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 544, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 545, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 546, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 547, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 548, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 569, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 570, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 571, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 572, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 573, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 574, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 595, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 596, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 597, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 598, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 599, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 600, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 621, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 622, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 623, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 624, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 625, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 626, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 648, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 649, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 650, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 651, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 652, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 653, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 674, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 675, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 676, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 677, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 678, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 679, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 700, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 701, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 702, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 703, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 704, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 705, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 726, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 727, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 728, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 729, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 730, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 731, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 752, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 753, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 754, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 755, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 756, '2026-07-05 00:41:21', '2026-07-05 00:41:21'),
(2, 757, '2026-07-05 00:41:21', '2026-07-05 00:41:21');

-- --------------------------------------------------------

--
-- テーブルの構造 `products`
--

CREATE TABLE `products` (
  `product_id` bigint(20) NOT NULL COMMENT '商品ID',
  `store_id` char(2) NOT NULL COMMENT '店舗ID',
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

INSERT INTO `products` (`product_id`, `store_id`, `product_name`, `price`, `tax_rate`, `sale_status`, `category_id`, `created_at`, `updated_at`, `image_path`) VALUES
(513, 'FB', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(514, 'FB', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(515, 'FB', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(516, 'FB', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(517, 'FB', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(518, 'FB', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(519, 'FB', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(520, 'FB', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(521, 'FB', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(522, 'FB', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(523, 'FB', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(524, 'FB', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(525, 'FB', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(526, 'FB', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(527, 'FB', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(528, 'FB', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(529, 'FB', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(530, 'FB', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(531, 'FB', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(532, 'FB', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(533, 'FB', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(534, 'FB', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(535, 'FB', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(536, 'FB', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(537, 'FB', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(538, 'FB', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(539, 'HM', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(540, 'HM', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(541, 'HM', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(542, 'HM', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(543, 'HM', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(544, 'HM', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(545, 'HM', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(546, 'HM', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(547, 'HM', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(548, 'HM', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(549, 'HM', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(550, 'HM', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(551, 'HM', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(552, 'HM', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(553, 'HM', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(554, 'HM', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(555, 'HM', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(556, 'HM', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(557, 'HM', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(558, 'HM', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(559, 'HM', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(560, 'HM', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(561, 'HM', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(562, 'HM', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(563, 'HM', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(564, 'HM', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(565, 'IM', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(566, 'IM', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(567, 'IM', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(568, 'IM', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(569, 'IM', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(570, 'IM', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(571, 'IM', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(572, 'IM', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(573, 'IM', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(574, 'IM', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(575, 'IM', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(576, 'IM', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(577, 'IM', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(578, 'IM', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(579, 'IM', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(580, 'IM', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(581, 'IM', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(582, 'IM', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(583, 'IM', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(584, 'IM', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(585, 'IM', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(586, 'IM', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(587, 'IM', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(588, 'IM', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(589, 'IM', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(590, 'IM', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(591, 'KB', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(592, 'KB', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(593, 'KB', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(594, 'KB', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(595, 'KB', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(596, 'KB', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(597, 'KB', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(598, 'KB', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(599, 'KB', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(600, 'KB', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(601, 'KB', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(602, 'KB', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(603, 'KB', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(604, 'KB', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(605, 'KB', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(606, 'KB', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(607, 'KB', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(608, 'KB', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(609, 'KB', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(610, 'KB', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(611, 'KB', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(612, 'KB', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(613, 'KB', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(614, 'KB', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(615, 'KB', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(616, 'KB', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(617, 'MH', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(618, 'MH', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(619, 'MH', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(620, 'MH', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(621, 'MH', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(622, 'MH', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(623, 'MH', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(624, 'MH', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(625, 'MH', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(626, 'MH', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(627, 'MH', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(628, 'MH', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(629, 'MH', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(630, 'MH', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(631, 'MH', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(632, 'MH', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(633, 'MH', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(634, 'MH', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(635, 'MH', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(636, 'MH', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(637, 'MH', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(638, 'MH', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(639, 'MH', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(640, 'MH', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(641, 'MH', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(642, 'MH', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(643, 'MH', 'ももサワー', 200, 10.00, 'ON_SALE', 1, '2026-07-07 07:18:50', '2026-07-07 07:38:44', '/assets/images/products/product_20260707_003844_0ac11abb.jpg'),
(644, 'MN', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(645, 'MN', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(646, 'MN', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(647, 'MN', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(648, 'MN', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(649, 'MN', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(650, 'MN', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(651, 'MN', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(652, 'MN', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(653, 'MN', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(654, 'MN', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(655, 'MN', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(656, 'MN', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(657, 'MN', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(658, 'MN', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(659, 'MN', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(660, 'MN', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(661, 'MN', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(662, 'MN', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(663, 'MN', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(664, 'MN', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(665, 'MN', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(666, 'MN', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(667, 'MN', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(668, 'MN', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(669, 'MN', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(670, 'NB', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(671, 'NB', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(672, 'NB', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(673, 'NB', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(674, 'NB', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(675, 'NB', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(676, 'NB', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(677, 'NB', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(678, 'NB', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(679, 'NB', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(680, 'NB', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(681, 'NB', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(682, 'NB', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(683, 'NB', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(684, 'NB', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(685, 'NB', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(686, 'NB', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(687, 'NB', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(688, 'NB', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(689, 'NB', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(690, 'NB', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(691, 'NB', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(692, 'NB', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(693, 'NB', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(694, 'NB', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(695, 'NB', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(696, 'TH', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(697, 'TH', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(698, 'TH', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(699, 'TH', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(700, 'TH', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(701, 'TH', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(702, 'TH', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(703, 'TH', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(704, 'TH', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(705, 'TH', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(706, 'TH', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(707, 'TH', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(708, 'TH', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(709, 'TH', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(710, 'TH', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(711, 'TH', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(712, 'TH', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(713, 'TH', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(714, 'TH', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(715, 'TH', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(716, 'TH', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(717, 'TH', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(718, 'TH', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(719, 'TH', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(720, 'TH', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(721, 'TH', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(722, 'TM', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(723, 'TM', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(724, 'TM', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(725, 'TM', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(726, 'TM', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(727, 'TM', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(728, 'TM', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(729, 'TM', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(730, 'TM', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(731, 'TM', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(732, 'TM', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(733, 'TM', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(734, 'TM', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(735, 'TM', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(736, 'TM', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(737, 'TM', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(738, 'TM', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(739, 'TM', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(740, 'TM', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(741, 'TM', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(742, 'TM', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(743, 'TM', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(744, 'TM', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(745, 'TM', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(746, 'TM', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(747, 'TM', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(748, 'TY', '鶏雑炊', 580, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(749, 'TY', 'お茶漬け', 480, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(750, 'TY', '焼きおにぎり', 320, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(751, 'TY', '白ご飯', 250, 10.00, 'ON_SALE', 5, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/rice.png'),
(752, 'TY', 'ウーロン茶', 300, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/oolongtea.png'),
(753, 'TY', 'カクテル', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/cocktail.png'),
(754, 'TY', 'レモンサワー', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/lemonsour.png'),
(755, 'TY', '焼酎', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/shochu.png'),
(756, 'TY', 'ハイボール', 450, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/highball.png'),
(757, 'TY', 'ビール', 500, 10.00, 'ON_SALE', 1, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/beer.png'),
(758, 'TY', 'たこわさ', 380, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(759, 'TY', 'だし巻き卵', 480, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(760, 'TY', '冷奴', 320, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(761, 'TY', '枝豆', 300, 10.00, 'ON_SALE', 3, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/edamame.png'),
(762, 'TY', 'ねぎま', 190, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(763, 'TY', 'つくね', 200, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(764, 'TY', '皮', 160, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_skin.png'),
(765, 'TY', 'もも', 180, 10.00, 'ON_SALE', 2, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/chicken_thigh.png'),
(766, 'TY', '軟骨唐揚げ', 520, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(767, 'TY', '揚げ出し豆腐', 480, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(768, 'TY', 'ポテトフライ', 420, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(769, 'TY', '唐揚げ', 580, 10.00, 'ON_SALE', 4, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/karage.png'),
(770, 'TY', '限定デザート', 450, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(771, 'TY', '冷やしトマト', 380, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(772, 'TY', '旬野菜の天ぷら', 620, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png'),
(773, 'TY', '季節の串盛り', 680, 10.00, 'ON_SALE', 6, '2026-07-04 23:14:07', '2026-07-04 23:25:17', '/MOS_A/public/assets/images/menu/no_image.png');

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

--
-- テーブルのデータのダンプ `product_option_groups`
--

INSERT INTO `product_option_groups` (`product_id`, `option_group_id`, `display_order`, `created_at`, `updated_at`) VALUES
(643, 1, 1, '2026-07-07 07:18:50', '2026-07-07 07:38:44');

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

--
-- テーブルのデータのダンプ `sessions`
--

INSERT INTO `sessions` (`session_id`, `customer_id`, `store_id`, `table_number`, `session_status`, `started_at`, `expired_at`, `ended_at`, `created_at`, `updated_at`) VALUES
(1, 1000001, 'MH', '1', 'ACTIVE', '2026-07-07 03:54:37', NULL, NULL, '2026-07-07 03:54:37', '2026-07-07 03:54:37'),
(2, 1000001, 'MH', '2', 'ACTIVE', '2026-07-07 05:13:23', '2026-07-07 00:13:23', NULL, '2026-07-07 05:13:23', '2026-07-07 05:13:23'),
(4, 1000001, 'MH', '5', 'ACTIVE', '2026-07-07 05:31:30', '2026-07-07 00:31:30', NULL, '2026-07-07 05:31:30', '2026-07-07 05:31:30'),
(5, 1000003, 'MH', '1', 'ACTIVE', '2026-07-07 05:38:18', '2026-07-07 00:38:18', NULL, '2026-07-07 05:38:18', '2026-07-07 05:38:18'),
(6, 1000003, 'MH', '6', 'ACTIVE', '2026-07-07 05:39:04', '2026-07-07 00:39:04', NULL, '2026-07-07 05:39:04', '2026-07-07 05:39:04'),
(7, 1000003, 'MH', '5', 'ACTIVE', '2026-07-07 05:44:29', '2026-07-07 00:44:29', NULL, '2026-07-07 05:44:29', '2026-07-07 05:44:29'),
(8, 1000003, 'MH', '8', 'ACTIVE', '2026-07-07 05:45:02', '2026-07-07 00:45:02', NULL, '2026-07-07 05:45:02', '2026-07-07 05:45:02'),
(9, 1000004, 'MH', '1', 'ACTIVE', '2026-07-07 05:51:20', '2026-07-07 01:51:20', NULL, '2026-07-07 05:51:20', '2026-07-07 05:51:20'),
(10, 1000004, 'MH', '2', 'ACTIVE', '2026-07-07 05:52:51', '2026-07-07 01:52:51', NULL, '2026-07-07 05:52:51', '2026-07-07 05:52:51'),
(11, 1000004, 'MH', '7', 'ACTIVE', '2026-07-07 06:07:43', '2026-07-07 02:07:43', NULL, '2026-07-07 06:07:43', '2026-07-07 06:07:43'),
(12, 1000040, 'MH', '1', 'ACTIVE', '2026-07-07 13:41:03', '2026-07-07 08:41:03', NULL, '2026-07-07 13:41:03', '2026-07-07 13:41:03'),
(13, 1000040, 'MH', '4', 'ACTIVE', '2026-07-07 13:45:54', '2026-07-07 08:45:54', NULL, '2026-07-07 13:45:54', '2026-07-07 13:45:54'),
(14, 1000041, 'MH', '1', 'ACTIVE', '2026-07-07 14:09:42', '2026-07-07 09:09:42', NULL, '2026-07-07 14:09:42', '2026-07-07 14:09:42'),
(15, 1000041, 'MH', '2', 'ACTIVE', '2026-07-07 14:12:19', '2026-07-07 09:12:19', NULL, '2026-07-07 14:12:19', '2026-07-07 14:12:19');

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
  ADD UNIQUE KEY `uq_cart_details_cart_product` (`cart_id`,`product_id`),
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
  ADD UNIQUE KEY `uq_store_accounts_login_id` (`login_id`),
  ADD UNIQUE KEY `uq_store_accounts_store_id` (`store_id`),
  ADD KEY `idx_store_accounts_store_id` (`store_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'かごID', AUTO_INCREMENT=17;

--
-- テーブルの AUTO_INCREMENT `cart_details`
--
ALTER TABLE `cart_details`
  MODIFY `cart_detail_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'かご明細ID', AUTO_INCREMENT=26;

--
-- テーブルの AUTO_INCREMENT `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '顧客ID', AUTO_INCREMENT=1000053;

--
-- テーブルの AUTO_INCREMENT `customer_plans`
--
ALTER TABLE `customer_plans`
  MODIFY `customer_plan_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '顧客プランID', AUTO_INCREMENT=6;

--
-- テーブルの AUTO_INCREMENT `options`
--
ALTER TABLE `options`
  MODIFY `option_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'オプションID', AUTO_INCREMENT=3;

--
-- テーブルの AUTO_INCREMENT `option_groups`
--
ALTER TABLE `option_groups`
  MODIFY `option_group_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'オプショングループID', AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '注文ID', AUTO_INCREMENT=11;

--
-- テーブルの AUTO_INCREMENT `order_details`
--
ALTER TABLE `order_details`
  MODIFY `order_detail_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '注文詳細ID', AUTO_INCREMENT=23;

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
  MODIFY `product_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '商品ID', AUTO_INCREMENT=774;

--
-- テーブルの AUTO_INCREMENT `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'カテゴリID', AUTO_INCREMENT=8;

--
-- テーブルの AUTO_INCREMENT `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'セッションID', AUTO_INCREMENT=16;

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
