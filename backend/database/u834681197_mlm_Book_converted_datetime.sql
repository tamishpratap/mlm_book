-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 28, 2026 at 09:09 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u834681197_mlm_Book`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `profile_photo`, `status`, `remember_token`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES
(24, 'Admin', 'admin@gmail.com', '$2y$12$nhVZQFZG6qI4ZneKRgegP.gdgZCP74jKood2ydbRFmSxNxm2ck6ka', NULL, 'active', 'N9uWeruQkmG2gUCDkSqdUzinucFG7tyaEiRW1sU0PnwORZstrTcmlvjVa7kl', '2026-09-28 05:32:00', '2405:201:6821:5810:bd94:97dd:7c8:ea8b', '2026-09-01 02:10:21', '2026-09-28 05:32:00'),
(25, 'Admin User', 'pro@cpanel.com', '$2y$12$M16jVLy2Kc7XWg39mDCiDuDQHIlp2Q5VnHNYGyQU2TH4NUO4LkEl.', NULL, 'active', 'mDBqnmllyFNZa6DdbDTU4SglbLVoY9AAdihh0OBQonJsqRfE5uKkwn7QsECj', '2026-09-26 12:42:03', '49.43.160.232', '2026-09-01 02:10:30', '2026-09-26 12:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_campaigns`
--

CREATE TABLE `ad_campaigns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campaign_id` varchar(32) NOT NULL,
  `campaign_type` varchar(32) NOT NULL DEFAULT 'business_ad',
  `business_page_id` bigint(20) UNSIGNED DEFAULT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_id` bigint(20) UNSIGNED DEFAULT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `budget` decimal(14,4) UNSIGNED NOT NULL,
  `additional_funding` decimal(14,4) UNSIGNED NOT NULL DEFAULT 0.0000,
  `total_funded` decimal(14,4) UNSIGNED NOT NULL DEFAULT 0.0000,
  `currency` varchar(8) NOT NULL DEFAULT 'USD',
  `fee_percent` decimal(5,2) UNSIGNED NOT NULL DEFAULT 2.50,
  `fee_amount` decimal(14,4) UNSIGNED NOT NULL DEFAULT 0.0000,
  `wallet_debit` decimal(14,4) UNSIGNED NOT NULL DEFAULT 0.0000,
  `spent_amount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `remaining_amount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_audience`)),
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'draft',
  `approval_status` varchar(32) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_campaigns`
--

INSERT INTO `ad_campaigns` (`id`, `campaign_id`, `campaign_type`, `business_page_id`, `member_id`, `post_id`, `event_id`, `campaign_name`, `budget`, `additional_funding`, `total_funded`, `currency`, `fee_percent`, `fee_amount`, `wallet_debit`, `spent_amount`, `remaining_amount`, `target_audience`, `start_at`, `end_at`, `status`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(3, 'camp_zb8t6jdek2u1', 'business_ad', 1, 13, 7, NULL, 'Digi Tech Solutions Pvt Ltd Campaign - 9/28/2026', 50.0000, 0.0000, 50.0000, 'USD', 2.50, 1.2500, 51.2500, 0.0000, 50.0000, NULL, '2026-09-28 07:13:00', NULL, 'approved', 'approved', 25, '2026-09-28 12:44:35', NULL, '2026-09-28 07:14:20', '2026-09-28 07:14:35');

-- --------------------------------------------------------

--
-- Table structure for table `ad_campaign_activities`
--

CREATE TABLE `ad_campaign_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ad_campaign_id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `action_label` varchar(100) DEFAULT NULL,
  `qualifying_event_id` varchar(100) DEFAULT NULL,
  `ad_reward_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_campaign_activities`
--

INSERT INTO `ad_campaign_activities` (`id`, `ad_campaign_id`, `business_page_id`, `member_id`, `action`, `action_label`, `qualifying_event_id`, `ad_reward_id`, `ip_address`, `user_agent`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 14, 'clicked', 'Ad Click', 'clk_camp_zb8t6jdek2u1_7_14_1790579736284', NULL, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"placement\":\"social_feed\"}', '2026-09-28 12:46:55', '2026-09-28 12:46:55'),
(2, 3, 1, 14, 'clicked', 'Ad Click', 'clk_camp_zb8t6jdek2u1_7_14_1790579736908', NULL, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"placement\":\"social_feed\"}', '2026-09-28 12:46:56', '2026-09-28 12:46:56'),
(3, 3, 1, 14, 'interested', 'Interested', 'int_3_14_1790579917', NULL, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"click_key\":\"int_3_14_1790579917\"}', '2026-09-28 12:48:37', '2026-09-28 12:48:37'),
(4, 3, 1, 14, 'interested', 'Interested', 'int_3_14_1790579958', NULL, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"click_key\":\"int_3_14_1790579958\"}', '2026-09-28 12:49:18', '2026-09-28 12:49:18'),
(5, 3, 1, 14, 'interested', 'Interested', 'int_3_14_1790580016', NULL, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '{\"click_key\":\"int_3_14_1790580016\"}', '2026-09-28 12:50:16', '2026-09-28 12:50:16');

-- --------------------------------------------------------

--
-- Table structure for table `ad_clicks`
--

CREATE TABLE `ad_clicks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ad_campaign_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `placement` varchar(50) NOT NULL DEFAULT 'social_feed',
  `click_key` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_clicks`
--

INSERT INTO `ad_clicks` (`id`, `ad_campaign_id`, `member_id`, `post_id`, `placement`, `click_key`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 14, 7, 'social_feed', 'clk_camp_zb8t6jdek2u1_7_14_1790579736284', '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 07:16:55'),
(2, 3, 14, 7, 'social_feed', 'clk_camp_zb8t6jdek2u1_7_14_1790579736908', '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 07:16:56'),
(3, 3, 14, NULL, 'interested_feed_cta', 'int_3_14_1790579917', '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 07:18:37'),
(4, 3, 14, NULL, 'interested_feed_cta', 'int_3_14_1790579958', '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 07:19:18'),
(5, 3, 14, NULL, 'interested_feed_cta', 'int_3_14_1790580016', '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 07:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `ad_deposits`
--

CREATE TABLE `ad_deposits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `deposit_id` varchar(32) NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount_inr` decimal(14,2) NOT NULL,
  `fee_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fee_amount_inr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net_amount_inr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency_in` varchar(8) NOT NULL DEFAULT 'INR',
  `currency_out` varchar(8) NOT NULL DEFAULT 'USD',
  `network` varchar(50) DEFAULT 'BEP-20',
  `token` varchar(50) DEFAULT 'USDT',
  `wallet_address` varchar(255) DEFAULT NULL,
  `sender_address` varchar(255) DEFAULT NULL,
  `block_number` bigint(20) UNSIGNED DEFAULT NULL,
  `submitted_amount` decimal(16,4) DEFAULT NULL,
  `verified_amount` decimal(16,4) DEFAULT NULL,
  `exchange_rate` decimal(12,4) NOT NULL,
  `expected_usd_amount` decimal(14,2) NOT NULL,
  `transaction_reference` varchar(100) NOT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `verification_status` varchar(50) DEFAULT 'unverified',
  `verification_source` varchar(50) DEFAULT NULL,
  `verification_error` text DEFAULT NULL,
  `verification_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_payload`)),
  `admin_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_deposits`
--

INSERT INTO `ad_deposits` (`id`, `deposit_id`, `member_id`, `business_page_id`, `amount_inr`, `fee_percent`, `fee_amount_inr`, `net_amount_inr`, `currency_in`, `currency_out`, `network`, `token`, `wallet_address`, `sender_address`, `block_number`, `submitted_amount`, `verified_amount`, `exchange_rate`, `expected_usd_amount`, `transaction_reference`, `transaction_hash`, `status`, `verification_status`, `verification_source`, `verification_error`, `verification_payload`, `admin_notes`, `rejection_reason`, `submitted_at`, `verified_at`, `verified_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'DEP-BROTNBY0CI', 13, NULL, 5500.00, 10.00, 500.00, 5000.00, 'USDT', 'USDT', 'BEP-20', 'USDT', '0x55d398326f99059fF775485246999027B3197955', '0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c', 38920145, 5500.0000, 5500.0000, 1.0000, 5000.00, '0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066', '0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066', 'approved', 'verified', 'bsc_rpc', NULL, '{\"verified\":true,\"status\":\"verified\",\"tx_hash\":\"0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066\",\"from_address\":\"0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c\",\"to_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"transferred_amount\":5500,\"submitted_amount\":5500,\"token\":\"USDT\",\"network\":\"BEP-20\",\"contract_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"block_number\":38920145,\"confirmations\":12,\"verification_source\":\"bsc_rpc\",\"message\":\"Successfully verified 5500 USDT transfer on BNB Smart Chain.\"}', 'Web3 DApp Instant Deposit: Auto-approved on-chain. Received: 5500 USDT. Net credited: $5000 USD to Member Fund Wallet.', NULL, '2026-09-28 06:50:02', '2026-09-28 06:50:02', NULL, '2026-09-28 06:50:02', '2026-09-28 06:50:02', NULL),
(2, 'DEP-UVDUEYSTBC', 1, NULL, 55.00, 10.00, 5.00, 50.00, 'USDT', 'USDT', 'BEP-20', 'USDT', '0x55d398326f99059fF775485246999027B3197955', '0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584', 38920145, 55.0000, 55.0000, 1.0000, 50.00, '0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3', '0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3', 'approved', 'verified', 'bsc_rpc', NULL, '{\"verified\":true,\"status\":\"verified\",\"tx_hash\":\"0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3\",\"from_address\":\"0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584\",\"to_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"transferred_amount\":55,\"submitted_amount\":55,\"token\":\"USDT\",\"network\":\"BEP-20\",\"contract_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"block_number\":38920145,\"confirmations\":12,\"verification_source\":\"bsc_rpc\",\"message\":\"Successfully verified 55 USDT transfer on BNB Smart Chain.\"}', 'Web3 DApp Instant Deposit: Auto-approved on-chain. Received: 55 USDT. Net credited: $50 USD to Member Fund Wallet.', NULL, '2026-09-28 09:01:32', '2026-09-28 09:01:32', NULL, '2026-09-28 14:31:32', '2026-09-28 14:31:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ad_impressions`
--

CREATE TABLE `ad_impressions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ad_campaign_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `placement` varchar(50) NOT NULL DEFAULT 'social_feed',
  `impression_key` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_impressions`
--

INSERT INTO `ad_impressions` (`id`, `ad_campaign_id`, `member_id`, `post_id`, `placement`, `impression_key`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 14, 7, 'social_feed', 'imp_camp_zb8t6jdek2u1_7_14', '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 07:15:00'),
(2, 3, 14, 7, 'social_feed', 'imp_camp_zb8t6jdek2u1_7_14', '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-28 07:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `ad_rewards`
--

CREATE TABLE `ad_rewards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ad_campaign_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `ad_reward_rule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reward_rank_rule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `direct_verified_referral_count` int(10) UNSIGNED DEFAULT NULL,
  `team_count` int(10) UNSIGNED DEFAULT NULL,
  `rule_min_referrals` int(10) UNSIGNED DEFAULT NULL,
  `rule_max_referrals` int(10) UNSIGNED DEFAULT NULL,
  `rule_version` varchar(50) DEFAULT NULL,
  `rank_at_reward` varchar(50) DEFAULT NULL,
  `reward_amount_usd` decimal(14,4) UNSIGNED NOT NULL DEFAULT 0.0500,
  `qualifying_event_id` varchar(100) DEFAULT NULL,
  `landing_page_url` varchar(2048) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'credited',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_reward_rules`
--

CREATE TABLE `ad_reward_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rule_type` varchar(32) NOT NULL DEFAULT 'business_ad',
  `min_referrals` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `max_referrals` int(10) UNSIGNED DEFAULT NULL,
  `reward_amount` decimal(10,4) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `blocked_member_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_analytics_snapshots`
--

CREATE TABLE `business_analytics_snapshots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `snapshot_date` date NOT NULL,
  `followers_count` int(11) NOT NULL DEFAULT 0,
  `posts_count` int(11) NOT NULL DEFAULT 0,
  `reviews_count` int(11) NOT NULL DEFAULT 0,
  `average_rating` double NOT NULL DEFAULT 0,
  `messages_count` int(11) NOT NULL DEFAULT 0,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `health_score` double NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_analytics_views`
--

CREATE TABLE `business_analytics_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `device_type` varchar(255) NOT NULL DEFAULT 'desktop' COMMENT 'desktop, mobile, tablet',
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_analytics_views`
--

INSERT INTO `business_analytics_views` (`id`, `business_page_id`, `member_id`, `ip_address`, `user_agent`, `device_type`, `country`, `city`, `viewed_at`) VALUES
(1, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 06:59:48'),
(2, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:05:07'),
(3, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:05:29'),
(4, 1, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:12:52'),
(5, 1, 14, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:12:54'),
(6, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:13:27'),
(7, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:13:33'),
(8, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:14:46'),
(9, 1, 14, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:18:38'),
(10, 1, 14, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:18:45'),
(11, 1, 14, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:19:18'),
(12, 1, 14, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:19:22'),
(13, 1, 14, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:19:34'),
(14, 1, 14, '2405:201:6821:5810:bd19:df89:1af1:a0af', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:20:17'),
(15, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:24:12'),
(16, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:31:55'),
(17, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:38:53'),
(18, 1, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:39:12'),
(19, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:39:18'),
(20, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:43:26'),
(21, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 07:54:43'),
(22, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 08:06:44'),
(23, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 08:06:54'),
(24, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 08:06:59'),
(25, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 08:07:00'),
(26, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 08:13:15'),
(27, 1, 13, '2405:201:6821:5810:c53b:d274:55f2:9935', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Meerut', '2026-09-28 08:55:34'),
(28, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 08:55:50'),
(29, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 09:01:35'),
(30, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 09:01:36'),
(31, 2, 1, '2405:201:6819:317e:78f4:c6d:a626:73b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'desktop', 'India', 'Online', '2026-09-28 09:02:21');

-- --------------------------------------------------------

--
-- Table structure for table `business_conversations`
--

CREATE TABLE `business_conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active' COMMENT 'active, pending_request, archived, closed',
  `is_starred` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_followers`
--

CREATE TABLE `business_followers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'accepted' COMMENT 'accepted, pending, blocked',
  `followed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_follower_invitations`
--

CREATE TABLE `business_follower_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `inviter_id` bigint(20) UNSIGNED NOT NULL,
  `invitee_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, accepted, declined',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_invitations`
--

CREATE TABLE `business_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `inviter_id` bigint(20) UNSIGNED NOT NULL,
  `invitee_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'editor',
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, accepted, rejected, cancelled, expired',
  `invite_code` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_messages`
--

CREATE TABLE `business_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` varchar(255) NOT NULL DEFAULT 'customer' COMMENT 'customer, business',
  `message` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_type` varchar(255) DEFAULT NULL COMMENT 'image, document, audio, video, location',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_notifications`
--

CREATE TABLE `business_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL COMMENT 'new_message, new_follower, new_review, new_team_invite, new_mention, new_comment',
  `title` varchar(255) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_pages`
--

CREATE TABLE `business_pages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `page_id` varchar(255) DEFAULT NULL COMMENT 'Unique string ID e.g. biz_xxxx',
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `page_username` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `visibility` varchar(255) NOT NULL DEFAULT 'public' COMMENT 'public, private, draft',
  `status` varchar(255) NOT NULL DEFAULT 'active' COMMENT 'active, inactive',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `seo_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `trending_score` double NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_pages`
--

INSERT INTO `business_pages` (`id`, `page_id`, `member_id`, `page_name`, `page_username`, `slug`, `category`, `description`, `website`, `email`, `phone`, `country`, `state`, `city`, `address`, `logo`, `cover_photo`, `visibility`, `status`, `is_verified`, `is_featured`, `seo_title`, `meta_description`, `social_links`, `business_hours`, `trending_score`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'biz_wBhmso3lKH', 13, 'Digi Tech Solutions Pvt Ltd', 'digi', 'digi-tech-solutions-pvt-ltd', 'Binary MLM Plan', 'Digi Tech Solutions Pvt. Ltd. is a professional software development company focused on delivering innovative, scalable, and business-oriented technology solutions. We specialize in developing a wide range of software solutions tailored to meet the unique requirements of businesses across different industries.', 'https://digitech.com', 'digitech155@gmail.com', '+919917558069', 'India', 'Uttar Pradesh', 'Meerut', 'New Nagar', 'uploads/business_pages/logos/cd22234d-8dc2-4008-a0a9-5b8aecf03c0e.png', 'uploads/business_pages/covers/328da8b1-d55a-49af-b60a-b5a7f3bde91c.png', 'public', 'active', 0, 0, NULL, NULL, NULL, NULL, 0, '2026-09-28 06:59:48', '2026-09-28 06:59:48', NULL),
(2, 'biz_HnsWYhQXFS', 1, 'MLM Staking', 'mlm_staking', 'mlm-staking', 'Binary MLM Plan', 'gthgtyhythrhrhtrhrththtrhtrrt', NULL, 'tamishpratap1713@gmail.com', '+918979703005', 'India', NULL, NULL, NULL, NULL, NULL, 'public', 'active', 0, 0, NULL, NULL, NULL, NULL, 0, '2026-09-28 13:36:44', '2026-09-28 13:36:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `business_page_categories`
--

CREATE TABLE `business_page_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_page_categories`
--

INSERT INTO `business_page_categories` (`id`, `name`, `slug`, `description`, `is_active`, `order`, `created_at`, `updated_at`) VALUES
(1, 'Binary MLM Plan', 'binary-mlm-plan', NULL, 1, 10, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(2, 'Matrix MLM Plan', 'matrix-mlm-plan', NULL, 1, 11, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(3, 'Unilevel MLM Plan', 'unilevel-mlm-plan', NULL, 1, 12, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(4, 'Board / Revolving Matrix Plan', 'board-revolving-matrix-plan', NULL, 1, 13, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(5, 'Generation / Level Plan', 'generation-level-plan', NULL, 1, 14, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(6, 'Monoline / Single Leg Plan', 'monoline-single-leg-plan', NULL, 1, 15, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(7, 'Stair-Step Breakaway Plan', 'stair-step-breakaway-plan', NULL, 1, 16, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(8, 'Crowdfunding / Helping MLM Plan', 'crowdfunding-helping-mlm-plan', NULL, 1, 17, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(9, 'Spillover / Auto-Pool Plan', 'spillover-auto-pool-plan', NULL, 1, 18, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(10, 'Gift / Donation MLM Plan', 'gift-donation-mlm-plan', NULL, 1, 19, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(11, 'Health, Nutrition & Wellness MLM', 'health-nutrition-wellness-mlm', NULL, 1, 20, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(12, 'Cosmetics & Personal Care MLM', 'cosmetics-personal-care-mlm', NULL, 1, 21, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(13, 'Crypto, Forex & FinTech MLM', 'crypto-forex-fintech-mlm', NULL, 1, 22, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(14, 'E-Commerce & Affiliate MLM', 'e-commerce-affiliate-mlm', NULL, 1, 23, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(15, 'Real Estate & Investment MLM', 'real-estate-investment-mlm', NULL, 1, 24, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(16, 'Digital Services & EdTech MLM', 'digital-services-edtech-mlm', NULL, 1, 25, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(17, 'Travel & Hospitality MLM', 'travel-hospitality-mlm', NULL, 1, 26, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(18, 'MLM Software & App Solutions', 'mlm-software-app-solutions', NULL, 1, 27, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(19, 'MLM Legal & Compliance Consultancy', 'mlm-legal-compliance-consultancy', NULL, 1, 28, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(20, 'MLM Lead Generation & Marketing', 'mlm-lead-generation-marketing', NULL, 1, 29, '2026-09-28 05:22:50', '2026-09-28 05:22:50'),
(21, 'Top Leaders & Networker Profiles', 'top-leaders-networker-profiles', NULL, 1, 30, '2026-09-28 05:22:50', '2026-09-28 05:22:50');

-- --------------------------------------------------------

--
-- Table structure for table `business_quick_replies`
--

CREATE TABLE `business_quick_replies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `shortcut` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_reviews`
--

CREATE TABLE `business_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5 COMMENT '1 to 5 stars',
  `recommendation` varchar(255) NOT NULL DEFAULT 'recommend' COMMENT 'recommend, not_recommend',
  `title` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `unhelpful_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_review_replies`
--

CREATE TABLE `business_review_replies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_review_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `reply` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_review_reports`
--

CREATE TABLE `business_review_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_review_id` bigint(20) UNSIGNED NOT NULL,
  `reporter_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL COMMENT 'spam, fake_review, harassment, offensive, other',
  `details` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, reviewed, dismissed',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_review_votes`
--

CREATE TABLE `business_review_votes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_review_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `vote_type` varchar(255) NOT NULL DEFAULT 'helpful' COMMENT 'helpful, unhelpful',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_team_members`
--

CREATE TABLE `business_team_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'editor' COMMENT 'owner, admin, editor, moderator, analyst',
  `status` varchar(255) NOT NULL DEFAULT 'active' COMMENT 'active, suspended',
  `joined_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_verifications`
--

CREATE TABLE `business_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_page_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, verified, rejected, expired',
  `document_type` varchar(255) NOT NULL COMMENT 'business_registration, gst, license, govt_id, other',
  `document_number` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) NOT NULL,
  `admin_notes` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('mlm-book-cache-4a019e67a4a66185c205f3180a921ab7df299ca7', 'i:1;', 1790579145),
('mlm-book-cache-4a019e67a4a66185c205f3180a921ab7df299ca7:timer', 'i:1790579145;', 1790579145),
('mlm-book-cache-5e807f2c86bd7c0e8c8dc7014a0452bcf089242a', 'i:2;', 1790578074),
('mlm-book-cache-5e807f2c86bd7c0e8c8dc7014a0452bcf089242a:timer', 'i:1790578074;', 1790578074),
('mlm-book-cache-ad_reward_rules_active', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1790589688),
('mlm-book-cache-central_reward_rules_active', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1790589149),
('mlm-book-cache-event_reward_rules_active', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1790589688),
('mlm-book-cache-member-register-request:2405:201:6821:5810:c53b:d274:55f2:9935', 'i:1;', 1790578101),
('mlm-book-cache-member-register-request:2405:201:6821:5810:c53b:d274:55f2:9935:timer', 'i:1790578101;', 1790578101),
('mlm-book-cache-member-verification-hi-request:13', 'i:1;', 1790578178),
('mlm-book-cache-member-verification-hi-request:13:timer', 'i:1790578178;', 1790578178),
('mlm-book-cache-member-verification-hi-request:14', 'i:1;', 1790579518),
('mlm-book-cache-member-verification-hi-request:14:timer', 'i:1790579518;', 1790579518),
('mlm-book-cache-reward_rank_rules_active', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:5:{i:0;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:1;s:8:\"rank_key\";s:10:\"advertiser\";s:9:\"rank_name\";s:10:\"Advertiser\";s:8:\"priority\";i:1;s:20:\"referral_requirement\";i:0;s:16:\"team_requirement\";i:0;s:13:\"reward_amount\";s:6:\"0.0250\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";i:24;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:53:40\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:1;s:8:\"rank_key\";s:10:\"advertiser\";s:9:\"rank_name\";s:10:\"Advertiser\";s:8:\"priority\";i:1;s:20:\"referral_requirement\";i:0;s:16:\"team_requirement\";i:0;s:13:\"reward_amount\";s:6:\"0.0250\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";i:24;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:53:40\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:2;s:8:\"rank_key\";s:10:\"influencer\";s:9:\"rank_name\";s:10:\"Influencer\";s:8:\"priority\";i:2;s:20:\"referral_requirement\";i:10;s:16:\"team_requirement\";i:0;s:13:\"reward_amount\";s:6:\"0.1000\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";i:24;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:54:27\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:2;s:8:\"rank_key\";s:10:\"influencer\";s:9:\"rank_name\";s:10:\"Influencer\";s:8:\"priority\";i:2;s:20:\"referral_requirement\";i:10;s:16:\"team_requirement\";i:0;s:13:\"reward_amount\";s:6:\"0.1000\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";i:24;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:54:27\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:3;s:8:\"rank_key\";s:7:\"leaders\";s:9:\"rank_name\";s:7:\"Leaders\";s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:15;s:16:\"team_requirement\";i:50;s:13:\"reward_amount\";s:6:\"0.0500\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:3;s:8:\"rank_key\";s:7:\"leaders\";s:9:\"rank_name\";s:7:\"Leaders\";s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:15;s:16:\"team_requirement\";i:50;s:13:\"reward_amount\";s:6:\"0.0500\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:4;s:8:\"rank_key\";s:11:\"pro_leaders\";s:9:\"rank_name\";s:11:\"Pro Leaders\";s:8:\"priority\";i:4;s:20:\"referral_requirement\";i:30;s:16:\"team_requirement\";i:150;s:13:\"reward_amount\";s:6:\"0.0750\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:4;s:8:\"rank_key\";s:11:\"pro_leaders\";s:9:\"rank_name\";s:11:\"Pro Leaders\";s:8:\"priority\";i:4;s:20:\"referral_requirement\";i:30;s:16:\"team_requirement\";i:150;s:13:\"reward_amount\";s:6:\"0.0750\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:4;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:5;s:8:\"rank_key\";s:14:\"master_leaders\";s:9:\"rank_name\";s:14:\"Master Leaders\";s:8:\"priority\";i:5;s:20:\"referral_requirement\";i:50;s:16:\"team_requirement\";i:500;s:13:\"reward_amount\";s:6:\"0.1000\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:5;s:8:\"rank_key\";s:14:\"master_leaders\";s:9:\"rank_name\";s:14:\"Master Leaders\";s:8:\"priority\";i:5;s:20:\"referral_requirement\";i:50;s:16:\"team_requirement\";i:500;s:13:\"reward_amount\";s:6:\"0.1000\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1790589333);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment_reactions`
--

CREATE TABLE `comment_reactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `comment_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `community_id` varchar(255) NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'Technology',
  `visibility` varchar(255) NOT NULL DEFAULT 'public',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `trending_score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `posting_permissions` varchar(255) NOT NULL DEFAULT 'everyone',
  `join_approval_mode` varchar(255) NOT NULL DEFAULT 'instant',
  `cover_photo` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `invite_code` varchar(255) DEFAULT NULL,
  `member_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `post_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_audit_logs`
--

CREATE TABLE `community_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `community_id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(255) DEFAULT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_bans`
--

CREATE TABLE `community_bans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `community_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `banned_by_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_permanent` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_invitations`
--

CREATE TABLE `community_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `community_id` bigint(20) UNSIGNED NOT NULL,
  `inviter_id` bigint(20) UNSIGNED NOT NULL,
  `invitee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `invite_code` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `max_uses` int(10) UNSIGNED DEFAULT NULL,
  `use_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(255) NOT NULL DEFAULT 'unlimited',
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0,
  `source` varchar(255) NOT NULL DEFAULT 'direct',
  `qr_data` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_members`
--

CREATE TABLE `community_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `community_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'member',
  `status` varchar(255) NOT NULL DEFAULT 'accepted',
  `notification_level` varchar(255) NOT NULL DEFAULT 'all',
  `muted_until` datetime DEFAULT NULL,
  `joined_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_mutes`
--

CREATE TABLE `community_mutes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `community_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `muted_by_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_reports`
--

CREATE TABLE `community_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `community_id` bigint(20) UNSIGNED NOT NULL,
  `reporter_id` bigint(20) UNSIGNED NOT NULL,
  `reportable_type` varchar(255) NOT NULL,
  `reportable_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `resolved_by_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_warnings`
--

CREATE TABLE `community_warnings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `community_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `warned_by_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `direct_messages`
--

CREATE TABLE `direct_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `organizer_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'General',
  `event_type` varchar(255) NOT NULL DEFAULT 'offline',
  `privacy` varchar(255) NOT NULL DEFAULT 'public',
  `cover_photo` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'UTC',
  `max_guests` int(11) DEFAULT NULL,
  `location_address` text DEFAULT NULL,
  `location_city` varchar(255) DEFAULT NULL,
  `location_state` varchar(255) DEFAULT NULL,
  `location_country` varchar(255) DEFAULT NULL,
  `google_maps_link` text DEFAULT NULL,
  `meeting_link` text DEFAULT NULL,
  `meeting_password` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'published',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_invitations`
--

CREATE TABLE `event_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `inviter_id` bigint(20) UNSIGNED NOT NULL,
  `invited_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_responses`
--

CREATE TABLE `event_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `response` varchar(255) NOT NULL DEFAULT 'going',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_suggestions`
--

CREATE TABLE `feedback_suggestions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'feedback',
  `subject` varchar(191) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `admin_response` text DEFAULT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followers`
--

CREATE TABLE `followers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `follower_id` bigint(20) UNSIGNED NOT NULL,
  `following_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'accepted',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `friendships`
--

CREATE TABLE `friendships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_one_id` bigint(20) UNSIGNED NOT NULL,
  `member_two_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `accepted_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `friendships`
--

INSERT INTO `friendships` (`id`, `member_one_id`, `member_two_id`, `requested_by_id`, `status`, `accepted_at`, `rejected_at`, `created_at`, `updated_at`) VALUES
(1, 1, 13, 1, 'pending', NULL, NULL, '2026-09-28 07:12:59', '2026-09-28 07:12:59');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'General',
  `privacy` varchar(255) NOT NULL DEFAULT 'public',
  `cover_photo` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_invitations`
--

CREATE TABLE `group_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `inviter_id` bigint(20) UNSIGNED NOT NULL,
  `invited_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'member',
  `status` varchar(255) NOT NULL DEFAULT 'accepted',
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hidden_posts`
--

CREATE TABLE `hidden_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `import_fund`
-- (See below for the actual view)
--
CREATE TABLE `import_fund` (
`id` bigint(20) unsigned
,`memberid` varchar(50)
,`user_id` varchar(30)
,`member_id` bigint(20) unsigned
,`txnid` varchar(255)
,`transaction_hash` varchar(255)
,`orderid` varchar(100)
,`amount` decimal(16,4)
,`type` varchar(50)
,`wallet_type` varchar(50)
,`wallet_address` varchar(255)
,`network` varchar(50)
,`token` varchar(50)
,`contract_address` varchar(255)
,`added_by` varchar(50)
,`status` varchar(50)
,`verification_status` varchar(50)
,`deposit_status` varchar(50)
,`verification_payload` longtext
,`admin_notes` text
,`rejection_reason` text
,`verified_at` datetime
,`verified_by` bigint(20) unsigned
,`mode` varchar(50)
,`created_at` datetime
,`updated_at` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `import_funds`
--

CREATE TABLE `import_funds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `memberid` varchar(50) DEFAULT NULL,
  `user_id` varchar(30) DEFAULT NULL,
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `txnid` varchar(255) DEFAULT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `orderid` varchar(100) DEFAULT NULL,
  `amount` decimal(16,4) NOT NULL DEFAULT 0.0000,
  `type` varchar(50) NOT NULL DEFAULT 'credit',
  `wallet_type` varchar(50) NOT NULL DEFAULT 'p2p_wallet',
  `wallet_address` varchar(255) DEFAULT NULL,
  `network` varchar(50) NOT NULL DEFAULT 'BEP-20',
  `token` varchar(50) NOT NULL DEFAULT 'USDT',
  `contract_address` varchar(255) DEFAULT NULL,
  `added_by` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `verification_status` varchar(50) NOT NULL DEFAULT 'unverified',
  `deposit_status` varchar(50) NOT NULL DEFAULT 'pending',
  `verification_payload` longtext DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `mode` varchar(50) NOT NULL DEFAULT 'dapp_web3',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `import_funds`
--

INSERT INTO `import_funds` (`id`, `memberid`, `user_id`, `member_id`, `txnid`, `transaction_hash`, `orderid`, `amount`, `type`, `wallet_type`, `wallet_address`, `network`, `token`, `contract_address`, `added_by`, `status`, `verification_status`, `deposit_status`, `verification_payload`, `admin_notes`, `rejection_reason`, `verified_at`, `verified_by`, `mode`, `created_at`, `updated_at`) VALUES
(3, 'shub648025', 'shub648025', 13, '0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066', '0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066', 'DEP-WSJFTY08II', 5000.0000, 'Add', 'USDT', '0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c', 'BEP-20', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 'User', 'Approved', 'verified', 'approved', '{\"verified\":true,\"status\":\"verified\",\"tx_hash\":\"0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066\",\"from_address\":\"0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c\",\"to_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"transferred_amount\":5500,\"submitted_amount\":5500,\"token\":\"USDT\",\"network\":\"BEP-20\",\"contract_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"block_number\":38920145,\"confirmations\":12,\"verification_source\":\"bsc_rpc\",\"message\":\"Successfully verified 5500 USDT transfer on BNB Smart Chain.\"}', 'Web3 DApp Instant Deposit: Auto-approved on-chain. 5500 USDT from 0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c. Net: $5000 USD credited directly to Fund Wallet.', NULL, '2026-09-28 06:50:02', NULL, 'Online', '2026-09-28 06:50:02', '2026-09-28 06:50:02'),
(4, 'tami383474', 'tami383474', 1, '0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3', '0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3', 'DEP-RT2EY7RBAS', 50.0000, 'Add', 'USDT', '0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584', 'BEP-20', 'USDT', '0x55d398326f99059fF775485246999027B3197955', 'User', 'Approved', 'verified', 'approved', '{\"verified\":true,\"status\":\"verified\",\"tx_hash\":\"0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3\",\"from_address\":\"0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584\",\"to_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"transferred_amount\":55,\"submitted_amount\":55,\"token\":\"USDT\",\"network\":\"BEP-20\",\"contract_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"block_number\":38920145,\"confirmations\":12,\"verification_source\":\"bsc_rpc\",\"message\":\"Successfully verified 55 USDT transfer on BNB Smart Chain.\"}', 'Web3 DApp Instant Deposit: Auto-approved on-chain. 55 USDT from 0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584. Net: $50 USD credited directly to Fund Wallet.', NULL, '2026-09-28 09:01:32', NULL, 'Online', '2026-09-28 09:01:32', '2026-09-28 09:01:32');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `introducer_id` varchar(30) DEFAULT NULL,
  `direct_referral_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `referral_counted_at` datetime DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `mobile_verified_at` datetime DEFAULT NULL,
  `mobile_verification_requested_at` datetime DEFAULT NULL,
  `p2p_wallet` decimal(16,2) NOT NULL DEFAULT 0.00,
  `wallet` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `wallet_address` varchar(255) DEFAULT NULL,
  `reward_rank` varchar(100) NOT NULL DEFAULT 'Advertiser',
  `reward_wallet_network` varchar(30) DEFAULT 'BEP-20',
  `reward_wallet_currency` varchar(20) DEFAULT 'USDT',
  `reward_wallet_verified_at` datetime DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `blocked_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `name`, `user_id`, `introducer_id`, `direct_referral_count`, `referral_counted_at`, `email`, `password`, `remember_token`, `google_id`, `phone`, `mobile_verified_at`, `mobile_verification_requested_at`, `p2p_wallet`, `wallet`, `wallet_address`, `reward_rank`, `reward_wallet_network`, `reward_wallet_currency`, `reward_wallet_verified_at`, `bio`, `date_of_birth`, `gender`, `city`, `country`, `website`, `profile_photo`, `cover_photo`, `last_seen_at`, `blocked_at`, `created_at`, `updated_at`) VALUES
(1, 'Tamish Pratap Singh', 'tami383474', NULL, 0, NULL, 'tamishpratap1713@gmail.com', '$2y$12$gObQ7sn8nNkMrvGdMHvzke356pfzpn8y8K.0cuJGUqrDoWl7Xn9P2', NULL, '111948971301762916699', '+918979703005', '2026-09-28 12:17:37', '2026-09-28 10:56:22', 50.00, 0.0000, NULL, 'Advertiser', 'BEP-20', 'USDT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://lh3.googleusercontent.com/a/ACg8ocJL7nXn1I2G7m3_2aDEOnOK6LttiLaya0PrDNY-loD-1zlBXckMyg=s96-c', NULL, '2026-09-28 14:33:40', NULL, '2026-09-28 10:52:23', '2026-09-28 14:33:40'),
(13, 'Shubham Verma', 'shub648025', NULL, 0, NULL, 'shubverma155@gmail.com', '$2y$12$rko1Loq.maXWga.yLytTv.XHXUuRbGi8LqjUqOx20EH7Y4AwurGra', NULL, NULL, '+919917558069', '2026-09-28 12:18:52', '2026-09-28 12:18:38', 4948.75, 0.0000, NULL, 'Advertiser', 'BEP-20', 'USDT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-28 14:28:07', NULL, '2026-09-28 12:17:57', '2026-09-28 14:28:07'),
(14, 'Anand Kashyap', 'anan918051', NULL, 0, NULL, 'anandkashyapassociates@gmail.com', '$2y$12$pe0rMJdwxrAcQ1EvRIZvoe3L0zK8bIqbKZ7dJoMbVGLdb.hzoLHG2', NULL, '117536838539675557905', '+919997078388', '2026-09-28 12:41:27', '2026-09-28 12:40:58', 0.00, 0.0000, NULL, 'Advertiser', 'BEP-20', 'USDT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://lh3.googleusercontent.com/a/ACg8ocL8xhgRjACtwR7tVDBX2hZxlA-U_sGNbMw8zMD743Wh8rYIUg=s96-c', NULL, '2026-09-28 13:26:08', NULL, '2026-09-28 12:35:12', '2026-09-28 13:26:08');

-- --------------------------------------------------------

--
-- Table structure for table `member_verification_otps`
--

CREATE TABLE `member_verification_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `pending_value` varchar(255) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_09_28_120000_remove_on_update_current_timestamp_from_expiries', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('0c9c9c5a-32c1-4258-9228-0680e84f2357', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"New Member Registered\",\"message\":\"Anand Kashyap (anan918051) has registered via Google.\",\"body\":\"Anand Kashyap (anan918051) has registered via Google.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"14\",\"action_url\":\"\\/admin\\/members\\/14\",\"metadata\":{\"member_id\":14,\"user_id\":\"anan918051\",\"phone\":\"+919997078388\"}}', NULL, '2026-09-28 07:05:12', '2026-09-28 07:05:12'),
('22ae8998-7031-4e94-a10c-587945086c69', 'App\\Notifications\\PostReactionNotification', 'App\\Models\\Member', 13, '{\"title\":\"Post reaction\",\"message\":\"Anand Kashyap reacted \\ud83d\\udc4d to your post.\",\"actor_id\":14,\"actor_name\":\"Anand Kashyap\",\"actor_photo\":\"https:\\/\\/lh3.googleusercontent.com\\/a\\/ACg8ocL8xhgRjACtwR7tVDBX2hZxlA-U_sGNbMw8zMD743Wh8rYIUg=s96-c\",\"reference_type\":\"post\",\"reference_id\":\"7\",\"icon\":\"thumbs-up\",\"category\":\"posts\",\"url\":\"https:\\/\\/mlmbookai.com\\/backend\\/public\\/member\\/posts\\/7\"}', '2026-09-28 08:55:45', '2026-09-28 07:16:58', '2026-09-28 08:55:45'),
('2e38ec4f-9ce8-46bc-94f3-4357944e2d8e', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"New Ad Campaign Created\",\"message\":\"Campaign \\\"Digi Tech Solutions Pvt Ltd Campaign - 9\\/28\\/2026\\\" was created for \\\"Digi Tech Solutions Pvt Ltd\\\" and submitted for review.\",\"body\":\"Campaign \\\"Digi Tech Solutions Pvt Ltd Campaign - 9\\/28\\/2026\\\" was created for \\\"Digi Tech Solutions Pvt Ltd\\\" and submitted for review.\",\"icon\":\"megaphone\",\"source_type\":\"ad_campaign\",\"source_id\":\"3\",\"action_url\":\"\\/admin\\/ad-campaigns\",\"metadata\":{\"campaign_id\":3,\"business_page_id\":1}}', NULL, '2026-09-28 07:14:20', '2026-09-28 07:14:20'),
('302a8eb3-775b-4957-af1a-143bab3f36b2', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"New Business Page Registered\",\"message\":\"\\\"Digi Tech Solutions Pvt Ltd\\\" was registered by Shubham Verma.\",\"body\":\"\\\"Digi Tech Solutions Pvt Ltd\\\" was registered by Shubham Verma.\",\"icon\":\"building\",\"source_type\":\"business_page\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/business-pages\",\"metadata\":{\"page_id\":1,\"page_name\":\"Digi Tech Solutions Pvt Ltd\"}}', '2026-09-28 07:03:38', '2026-09-28 06:59:48', '2026-09-28 07:03:38'),
('334ce3bd-04ec-4786-a79b-6c6b0221abd9', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"New Member Registered\",\"message\":\"Shubham Verma (shub648025) has registered on MLM Book.\",\"body\":\"Shubham Verma (shub648025) has registered on MLM Book.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"13\",\"action_url\":\"\\/admin\\/members\\/13\",\"metadata\":{\"member_id\":13,\"user_id\":\"shub648025\"}}', '2026-09-28 07:03:38', '2026-09-28 06:47:57', '2026-09-28 07:03:38'),
('38ff13a1-803e-4f5b-b71e-07e1e072df91', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"New Business Page Registered\",\"message\":\"\\\"Digi Tech Solutions Pvt Ltd\\\" was registered by Shubham Verma.\",\"body\":\"\\\"Digi Tech Solutions Pvt Ltd\\\" was registered by Shubham Verma.\",\"icon\":\"building\",\"source_type\":\"business_page\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/business-pages\",\"metadata\":{\"page_id\":1,\"page_name\":\"Digi Tech Solutions Pvt Ltd\"}}', NULL, '2026-09-28 06:59:48', '2026-09-28 06:59:48'),
('40ec1412-c3fe-4584-9d26-260d5720be58', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Shubham Verma (shub648025) sent \\\"Hi\\\" from +9199******69 and requested verification.\",\"body\":\"Shubham Verma (shub648025) sent \\\"Hi\\\" from +9199******69 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"13\",\"action_url\":\"\\/admin\\/members\\/13\",\"metadata\":{\"member_id\":13,\"user_id\":\"shub648025\",\"phone\":\"+919917558069\",\"requested_at\":\"2026-09-28T12:18:38+05:30\"}}', '2026-09-28 07:03:38', '2026-09-28 06:48:38', '2026-09-28 07:03:38'),
('45991d12-18e5-4a28-a1da-beb803145215', 'App\\Notifications\\FriendRequestReceivedNotification', 'App\\Models\\Member', 13, '{\"title\":\"New Connection request\",\"message\":\"Tamish Pratap Singh sent you a connection request.\",\"actor_id\":1,\"actor_name\":\"Tamish Pratap Singh\",\"actor_photo\":\"https:\\/\\/lh3.googleusercontent.com\\/a\\/ACg8ocJL7nXn1I2G7m3_2aDEOnOK6LttiLaya0PrDNY-loD-1zlBXckMyg=s96-c\",\"friendship_id\":1,\"icon\":\"user-plus\",\"url\":\"https:\\/\\/mlmbookai.com\\/backend\\/public\\/member\\/friend-requests\"}', '2026-09-28 08:55:45', '2026-09-28 07:12:59', '2026-09-28 08:55:45'),
('685b947d-302b-4ca5-aabf-c9efd25b8d3d', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"New Business Page Registered\",\"message\":\"\\\"MLM Staking\\\" was registered by Tamish Pratap Singh.\",\"body\":\"\\\"MLM Staking\\\" was registered by Tamish Pratap Singh.\",\"icon\":\"building\",\"source_type\":\"business_page\",\"source_id\":\"2\",\"action_url\":\"\\/admin\\/business-pages\",\"metadata\":{\"page_id\":2,\"page_name\":\"MLM Staking\"}}', '2026-09-28 09:03:43', '2026-09-28 08:06:44', '2026-09-28 09:03:43'),
('6e4a64dd-503f-4fae-b6f7-6b8ca673b065', 'App\\Notifications\\SystemNotification', 'App\\Models\\Member', 13, '{\"title\":\"WhatsApp Verification Approved\",\"message\":\"Your WhatsApp mobile number has been verified successfully. Your verified badge is now active!\",\"actor_id\":0,\"actor_name\":\"MLM Book System\",\"actor_photo\":null,\"reference_type\":\"system\",\"reference_id\":\"0\",\"icon\":\"bell\",\"category\":\"system\",\"url\":\"\\/account\\/settings\"}', '2026-09-28 06:50:23', '2026-09-28 06:48:52', '2026-09-28 06:50:23'),
('70c08b19-36ee-46ed-a68d-3f67c7b824b7', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"New Business Page Registered\",\"message\":\"\\\"MLM Staking\\\" was registered by Tamish Pratap Singh.\",\"body\":\"\\\"MLM Staking\\\" was registered by Tamish Pratap Singh.\",\"icon\":\"building\",\"source_type\":\"business_page\",\"source_id\":\"2\",\"action_url\":\"\\/admin\\/business-pages\",\"metadata\":{\"page_id\":2,\"page_name\":\"MLM Staking\"}}', NULL, '2026-09-28 08:06:44', '2026-09-28 08:06:44'),
('7118c681-ce41-4ba2-89b5-c3efe5e9dcf6', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Tamish Pratap Singh (tami383474) sent \\\"Hi\\\" from +9189******05 and requested verification.\",\"body\":\"Tamish Pratap Singh (tami383474) sent \\\"Hi\\\" from +9189******05 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/members\\/1\",\"metadata\":{\"member_id\":1,\"user_id\":\"tami383474\",\"phone\":\"+918979703005\",\"requested_at\":\"2026-09-28T05:26:22+00:00\"}}', NULL, '2026-09-28 05:26:22', '2026-09-28 05:26:22'),
('73014868-4bfe-4a11-9e24-36c0639f0499', 'App\\Notifications\\SystemNotification', 'App\\Models\\Member', 14, '{\"title\":\"WhatsApp Verification Approved\",\"message\":\"Your WhatsApp mobile number has been verified successfully. Your verified badge is now active!\",\"actor_id\":0,\"actor_name\":\"MLM Book System\",\"actor_photo\":null,\"reference_type\":\"system\",\"reference_id\":\"0\",\"icon\":\"bell\",\"category\":\"system\",\"url\":\"\\/account\\/settings\"}', '2026-09-28 07:11:51', '2026-09-28 07:11:27', '2026-09-28 07:11:51'),
('778a6eaa-fae2-47e7-8aed-f7c7af372077', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"New Member Registered\",\"message\":\"Tamish Pratap Singh (tami383474) has registered via Google.\",\"body\":\"Tamish Pratap Singh (tami383474) has registered via Google.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/members\\/1\",\"metadata\":{\"member_id\":1,\"user_id\":\"tami383474\",\"phone\":\"+918979703005\"}}', NULL, '2026-09-28 05:22:23', '2026-09-28 05:22:23'),
('7b7bcffd-31ca-4c3d-b097-ff6e429c412a', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Tamish Pratap Singh (tami383474) sent \\\"Hi\\\" from +9189******05 and requested verification.\",\"body\":\"Tamish Pratap Singh (tami383474) sent \\\"Hi\\\" from +9189******05 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/members\\/1\",\"metadata\":{\"member_id\":1,\"user_id\":\"tami383474\",\"phone\":\"+918979703005\",\"requested_at\":\"2026-09-28T05:26:22+00:00\"}}', '2026-09-28 06:47:35', '2026-09-28 05:26:22', '2026-09-28 06:47:35'),
('80a77a70-8e81-45e1-ad33-1c9d340036d6', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"New Member Registered\",\"message\":\"Anand Kashyap (anan918051) has registered via Google.\",\"body\":\"Anand Kashyap (anan918051) has registered via Google.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"14\",\"action_url\":\"\\/admin\\/members\\/14\",\"metadata\":{\"member_id\":14,\"user_id\":\"anan918051\",\"phone\":\"+919997078388\"}}', '2026-09-28 07:43:48', '2026-09-28 07:05:12', '2026-09-28 07:43:48'),
('9363fab8-8510-4ba4-82dd-b194cf8e4bed', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"New Member Registered\",\"message\":\"Shubham Verma (shub648025) has registered on MLM Book.\",\"body\":\"Shubham Verma (shub648025) has registered on MLM Book.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"13\",\"action_url\":\"\\/admin\\/members\\/13\",\"metadata\":{\"member_id\":13,\"user_id\":\"shub648025\"}}', NULL, '2026-09-28 06:47:57', '2026-09-28 06:47:57'),
('b735099a-9082-4d27-94ea-caa999378d68', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"New Member Registered\",\"message\":\"Tamish Pratap Singh (tami383474) has registered via Google.\",\"body\":\"Tamish Pratap Singh (tami383474) has registered via Google.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/members\\/1\",\"metadata\":{\"member_id\":1,\"user_id\":\"tami383474\",\"phone\":\"+918979703005\"}}', '2026-09-28 05:23:32', '2026-09-28 05:22:23', '2026-09-28 05:23:32'),
('d2b35290-6d82-4228-9089-0d1c0eefcf94', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"New Ad Campaign Created\",\"message\":\"Campaign \\\"Digi Tech Solutions Pvt Ltd Campaign - 9\\/28\\/2026\\\" was created for \\\"Digi Tech Solutions Pvt Ltd\\\" and submitted for review.\",\"body\":\"Campaign \\\"Digi Tech Solutions Pvt Ltd Campaign - 9\\/28\\/2026\\\" was created for \\\"Digi Tech Solutions Pvt Ltd\\\" and submitted for review.\",\"icon\":\"megaphone\",\"source_type\":\"ad_campaign\",\"source_id\":\"3\",\"action_url\":\"\\/admin\\/ad-campaigns\",\"metadata\":{\"campaign_id\":3,\"business_page_id\":1}}', '2026-09-28 07:43:48', '2026-09-28 07:14:20', '2026-09-28 07:43:48'),
('e1e4c2be-1103-44d5-a594-b4a084b0e651', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Anand Kashyap (anan918051) sent \\\"Hi\\\" from +9199******88 and requested verification.\",\"body\":\"Anand Kashyap (anan918051) sent \\\"Hi\\\" from +9199******88 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"14\",\"action_url\":\"\\/admin\\/members\\/14\",\"metadata\":{\"member_id\":14,\"user_id\":\"anan918051\",\"phone\":\"+919997078388\",\"requested_at\":\"2026-09-28T12:40:58+05:30\"}}', NULL, '2026-09-28 07:10:58', '2026-09-28 07:10:58'),
('effb0429-810b-43d3-b004-9e1ccf00ba7f', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 25, '{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Shubham Verma (shub648025) sent \\\"Hi\\\" from +9199******69 and requested verification.\",\"body\":\"Shubham Verma (shub648025) sent \\\"Hi\\\" from +9199******69 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"13\",\"action_url\":\"\\/admin\\/members\\/13\",\"metadata\":{\"member_id\":13,\"user_id\":\"shub648025\",\"phone\":\"+919917558069\",\"requested_at\":\"2026-09-28T12:18:38+05:30\"}}', NULL, '2026-09-28 06:48:38', '2026-09-28 06:48:38'),
('fb7fc9e9-6e4c-4399-b76d-2ecb9c093096', 'App\\Notifications\\AdminAlertNotification', 'App\\Models\\Admin', 24, '{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Anand Kashyap (anan918051) sent \\\"Hi\\\" from +9199******88 and requested verification.\",\"body\":\"Anand Kashyap (anan918051) sent \\\"Hi\\\" from +9199******88 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"14\",\"action_url\":\"\\/admin\\/members\\/14\",\"metadata\":{\"member_id\":14,\"user_id\":\"anan918051\",\"phone\":\"+919997078388\",\"requested_at\":\"2026-09-28T12:40:58+05:30\"}}', '2026-09-28 07:43:48', '2026-09-28 07:10:58', '2026-09-28 07:43:48');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_member_registrations`
--

CREATE TABLE `pending_member_registrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `introducer_id` varchar(30) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `last_resend_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pending_member_registrations`
--

INSERT INTO `pending_member_registrations` (`id`, `token`, `name`, `user_id`, `introducer_id`, `email`, `phone`, `password_hash`, `otp_hash`, `expires_at`, `attempts`, `last_resend_at`, `created_at`, `updated_at`) VALUES
(2, '8aHAdcAEn56jePVldWLOFui3ammaf937ozXjRKetCDdkeVqCvy7HDYlqtj8lq9vS', 'Sagar Rajput', 'saga387722', NULL, 'sagar@gmail.com', '+914444455555', '$2y$12$RZByViLCEkGNtOsvZQ3co.KKUjG5LZWszBxvjXg4f8U0.jn4DEdaq', '$2y$12$oYM7FC4qbKq53P/8761PdO.Zpw4hTXXb96TW7f6QGNmyId0RFVxxi', '2026-09-28 05:43:45', 0, '2026-09-28 05:33:45', '2026-09-28 05:33:45', '2026-09-28 05:33:45');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `group` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED DEFAULT NULL,
  `community_id` bigint(20) UNSIGNED DEFAULT NULL,
  `business_page_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_announcement` tinyint(1) NOT NULL DEFAULT 0,
  `original_post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `body` text DEFAULT NULL,
  `media_type` varchar(255) DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `member_id`, `group_id`, `community_id`, `business_page_id`, `event_id`, `is_announcement`, `original_post_id`, `is_pinned`, `is_featured`, `body`, `media_type`, `media_path`, `created_at`, `updated_at`) VALUES
(7, 13, NULL, NULL, 1, NULL, 0, NULL, 0, 0, '💻 Custom Software | Web & Mobile Apps | CRM & ERP | AI | FinTech | E-Commerce & More', 'image', 'uploads/posts/images/biz_post_13_1790579107_neIjhUFZ.png', '2026-09-28 07:05:07', '2026-09-28 07:05:07'),
(8, 13, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, 'Testing post share', 'image', 'uploads/posts/images/post_13_1790579425_zj6vacfk.jpg', '2026-09-28 07:10:25', '2026-09-28 07:10:25'),
(9, 1, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 'image', 'uploads/posts/images/post_1_1790579593_mhecxp0f.jpg', '2026-09-28 07:13:13', '2026-09-28 07:13:13'),
(10, 1, NULL, NULL, NULL, NULL, 0, NULL, 0, 0, NULL, 'image', 'uploads/posts/images/post_1_1790579675_luo4vrqt.png', '2026-09-28 12:44:35', '2026-09-28 12:44:35'),
(11, 1, NULL, NULL, 2, NULL, 0, NULL, 0, 0, NULL, 'image', 'uploads/posts/images/biz_post_1_1790582813_YqbtDSYM.jpg', '2026-09-28 13:36:53', '2026-09-28 13:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `post_comments`
--

CREATE TABLE `post_comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `member_id`, `created_at`, `updated_at`) VALUES
(2, 7, 14, '2026-09-28 07:16:58', '2026-09-28 07:16:58');

-- --------------------------------------------------------

--
-- Table structure for table `post_reactions`
--

CREATE TABLE `post_reactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_reactions`
--

INSERT INTO `post_reactions` (`id`, `post_id`, `member_id`, `reaction`, `created_at`, `updated_at`) VALUES
(2, 7, 14, 'like', '2026-09-28 07:16:58', '2026-09-28 07:16:58');

-- --------------------------------------------------------

--
-- Table structure for table `post_shares`
--

CREATE TABLE `post_shares` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `original_post_id` bigint(20) UNSIGNED NOT NULL,
  `shared_post_id` bigint(20) UNSIGNED NOT NULL,
  `shared_by` bigint(20) UNSIGNED NOT NULL,
  `share_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `sub_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `condition` varchar(255) NOT NULL DEFAULT 'like_new',
  `brand` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `is_negotiable` tinyint(1) NOT NULL DEFAULT 0,
  `tags` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'available',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `views_count` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_media`
--

CREATE TABLE `product_media` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `media_type` varchar(255) NOT NULL DEFAULT 'image',
  `media_path` varchar(255) NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profile_visits`
--

CREATE TABLE `profile_visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `profile_owner_id` bigint(20) UNSIGNED NOT NULL,
  `visitor_id` bigint(20) UNSIGNED NOT NULL,
  `visited_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profile_visits`
--

INSERT INTO `profile_visits` (`id`, `profile_owner_id`, `visitor_id`, `visited_at`, `created_at`, `updated_at`) VALUES
(1, 13, 1, '2026-09-28 07:12:57', '2026-09-28 07:12:57', '2026-09-28 07:12:57');

-- --------------------------------------------------------

--
-- Table structure for table `reported_posts`
--

CREATE TABLE `reported_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reported_products`
--

CREATE TABLE `reported_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reward_rank_rules`
--

CREATE TABLE `reward_rank_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rank_key` varchar(32) NOT NULL,
  `rank_name` varchar(50) NOT NULL,
  `priority` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `referral_requirement` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `team_requirement` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reward_amount` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reward_rank_rules`
--

INSERT INTO `reward_rank_rules` (`id`, `rank_key`, `rank_name`, `priority`, `referral_requirement`, `team_requirement`, `reward_amount`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'advertiser', 'Advertiser', 1, 0, 0, 0.0250, 1, NULL, 24, '2026-09-28 07:20:55', '2026-09-28 07:23:40'),
(2, 'influencer', 'Influencer', 2, 10, 0, 0.1000, 1, NULL, 24, '2026-09-28 07:20:55', '2026-09-28 07:24:27'),
(3, 'leaders', 'Leaders', 3, 15, 50, 0.0500, 1, NULL, NULL, '2026-09-28 07:20:55', '2026-09-28 07:20:55'),
(4, 'pro_leaders', 'Pro Leaders', 4, 30, 150, 0.0750, 1, NULL, NULL, '2026-09-28 07:20:55', '2026-09-28 07:20:55'),
(5, 'master_leaders', 'Master Leaders', 5, 50, 500, 0.1000, 1, NULL, NULL, '2026-09-28 07:20:55', '2026-09-28 07:20:55');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_posts`
--

CREATE TABLE `saved_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_products`
--

CREATE TABLE `saved_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `group` varchar(100) NOT NULL DEFAULT 'general',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `group`, `created_at`, `updated_at`) VALUES
(1, 'site_favicon', 'storage/branding/favicon_1789809966_h0ybzqtd.png', 'branding', '2026-09-19 09:26:07', '2026-09-19 09:26:07'),
(2, 'deposit_fee_percent', '10.00', 'funds', '2026-09-25 14:11:04', '2026-09-26 11:00:48'),
(3, 'service_charge_percent', '5.00', 'funds', '2026-09-25 14:11:04', '2026-09-25 14:12:31'),
(4, 'deposit_currency', 'USDT', 'funds', '2026-09-25 14:12:31', '2026-09-26 10:16:27'),
(5, 'deposit_network', 'BEP-20', 'funds', '2026-09-25 14:12:31', '2026-09-26 10:16:27'),
(6, 'deposit_token', 'USDT', 'funds', '2026-09-25 14:12:31', '2026-09-26 10:16:27'),
(7, 'bsc_network', 'mainnet', 'blockchain', '2026-09-25 14:12:31', '2026-09-25 14:12:31'),
(8, 'deposit_crypto_wallet_address', '0x55d398326f99059fF775485246999027B3197955', 'funds', '2026-09-25 14:12:31', '2026-09-26 10:16:27'),
(9, 'deposit_instructions', 'Transfer payment in USDT (BEP-20) using the configured crypto wallet address or QR code. Enter your transaction hash after completing payment.', 'funds', '2026-09-25 14:12:31', '2026-09-26 10:16:27'),
(18, 'campaign_platform_fee_percent', '0', 'ads', '2026-09-28 08:06:27', '2026-09-28 09:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE `stories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `media_type` varchar(255) NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stories`
--

INSERT INTO `stories` (`id`, `member_id`, `caption`, `media_type`, `media_path`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, 1, NULL, 'image', 'uploads/stories/images/story_1_1790578065_ivxlv9pv.jpg', '2026-09-29 06:47:45', '2026-09-28 06:47:45', '2026-09-28 06:47:45'),
(3, 1, NULL, 'image', 'uploads/stories/images/story_1_1790578871_br9b9uc7.jpg', '2026-09-29 12:31:11', '2026-09-28 12:31:11', '2026-09-28 12:31:11'),
(4, 1, NULL, 'image', 'uploads/stories/images/story_1_1790579669_8niy1cu3.jpg', '2026-09-29 12:44:29', '2026-09-28 12:44:29', '2026-09-28 12:44:29');

-- --------------------------------------------------------

--
-- Table structure for table `story_likes`
--

CREATE TABLE `story_likes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `story_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `story_reactions`
--

CREATE TABLE `story_reactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `story_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `story_replies`
--

CREATE TABLE `story_replies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `story_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `story_views`
--

CREATE TABLE `story_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `story_id` bigint(20) UNSIGNED NOT NULL,
  `viewer_member_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED DEFAULT NULL,
  `request_date` datetime NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `request_id` varchar(255) NOT NULL,
  `txnid` varchar(255) DEFAULT NULL,
  `memberid` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `gross_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `type` enum('Auto','User') DEFAULT NULL,
  `status` enum('Pending','Cancelled','Approved','Verified') DEFAULT 'Pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_settings`
--

CREATE TABLE `withdrawal_settings` (
  `id` int(12) NOT NULL,
  `private_key` varchar(300) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `withdrawal_settings`
--

INSERT INTO `withdrawal_settings` (`id`, `private_key`, `created_at`, `updated_at`) VALUES
(1, '0x36da85fd4g8fd47c4185fe6a8e98a25b3aaac9d27a44b48c7186af339864639ab', '2026-09-08 13:19:29', '2026-09-08 13:19:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_roles_admin_id_role_id_unique` (`admin_id`,`role_id`),
  ADD KEY `admin_roles_role_id_foreign` (`role_id`);

--
-- Indexes for table `ad_campaigns`
--
ALTER TABLE `ad_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ad_campaigns_campaign_id_unique` (`campaign_id`),
  ADD UNIQUE KEY `ad_campaigns_event_id_unique` (`event_id`),
  ADD KEY `ad_campaigns_approved_by_foreign` (`approved_by`),
  ADD KEY `ad_campaigns_business_page_id_status_index` (`business_page_id`,`status`),
  ADD KEY `ad_campaigns_member_id_status_index` (`member_id`,`status`),
  ADD KEY `ad_campaigns_status_index` (`status`),
  ADD KEY `ad_campaigns_approval_status_index` (`approval_status`),
  ADD KEY `ad_campaigns_campaign_type_index` (`campaign_type`),
  ADD KEY `ad_campaigns_post_id_index` (`post_id`);

--
-- Indexes for table `ad_campaign_activities`
--
ALTER TABLE `ad_campaign_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_campaign_activities_ad_reward_id_foreign` (`ad_reward_id`),
  ADD KEY `ad_campaign_activities_ad_campaign_id_created_at_index` (`ad_campaign_id`,`created_at`),
  ADD KEY `ad_campaign_activities_ad_campaign_id_action_index` (`ad_campaign_id`,`action`),
  ADD KEY `ad_campaign_activities_ad_campaign_id_member_id_index` (`ad_campaign_id`,`member_id`),
  ADD KEY `ad_campaign_activities_business_page_id_created_at_index` (`business_page_id`,`created_at`),
  ADD KEY `ad_campaign_activities_member_id_created_at_index` (`member_id`,`created_at`),
  ADD KEY `ad_campaign_activities_action_index` (`action`),
  ADD KEY `ad_campaign_activities_qualifying_event_id_index` (`qualifying_event_id`);

--
-- Indexes for table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_clicks_member_id_foreign` (`member_id`),
  ADD KEY `ad_clicks_post_id_foreign` (`post_id`),
  ADD KEY `ad_clicks_ad_campaign_id_created_at_index` (`ad_campaign_id`,`created_at`),
  ADD KEY `ad_clicks_ad_campaign_id_member_id_created_at_index` (`ad_campaign_id`,`member_id`,`created_at`),
  ADD KEY `ad_clicks_placement_index` (`placement`),
  ADD KEY `ad_clicks_click_key_index` (`click_key`),
  ADD KEY `ad_clicks_created_at_index` (`created_at`);

--
-- Indexes for table `ad_deposits`
--
ALTER TABLE `ad_deposits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ad_deposits_deposit_id_unique` (`deposit_id`),
  ADD KEY `ad_deposits_member_id_foreign` (`member_id`),
  ADD KEY `ad_deposits_business_page_id_foreign` (`business_page_id`),
  ADD KEY `ad_deposits_verified_by_foreign` (`verified_by`),
  ADD KEY `ad_deposits_transaction_reference_index` (`transaction_reference`),
  ADD KEY `ad_deposits_status_index` (`status`);

--
-- Indexes for table `ad_impressions`
--
ALTER TABLE `ad_impressions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_impressions_member_id_foreign` (`member_id`),
  ADD KEY `ad_impressions_post_id_foreign` (`post_id`),
  ADD KEY `ad_impressions_ad_campaign_id_created_at_index` (`ad_campaign_id`,`created_at`),
  ADD KEY `ad_impressions_ad_campaign_id_member_id_created_at_index` (`ad_campaign_id`,`member_id`,`created_at`),
  ADD KEY `ad_impressions_placement_index` (`placement`),
  ADD KEY `ad_impressions_impression_key_index` (`impression_key`),
  ADD KEY `ad_impressions_created_at_index` (`created_at`);

--
-- Indexes for table `ad_rewards`
--
ALTER TABLE `ad_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ad_rewards_campaign_member_unique` (`ad_campaign_id`,`member_id`),
  ADD UNIQUE KEY `ad_reward_unique_event` (`ad_campaign_id`,`member_id`,`qualifying_event_id`),
  ADD KEY `ad_rewards_ad_campaign_id_member_id_index` (`ad_campaign_id`,`member_id`),
  ADD KEY `ad_rewards_ad_campaign_id_index` (`ad_campaign_id`),
  ADD KEY `ad_rewards_member_id_index` (`member_id`),
  ADD KEY `ad_rewards_qualifying_event_id_index` (`qualifying_event_id`),
  ADD KEY `ad_rewards_status_index` (`status`),
  ADD KEY `ad_rewards_ad_reward_rule_id_index` (`ad_reward_rule_id`),
  ADD KEY `ad_rewards_reward_rank_rule_id_index` (`reward_rank_rule_id`);

--
-- Indexes for table `ad_reward_rules`
--
ALTER TABLE `ad_reward_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_reward_rules_created_by_foreign` (`created_by`),
  ADD KEY `ad_reward_rules_updated_by_foreign` (`updated_by`),
  ADD KEY `ad_reward_rules_min_referrals_index` (`min_referrals`),
  ADD KEY `ad_reward_rules_max_referrals_index` (`max_referrals`),
  ADD KEY `ad_reward_rules_is_active_index` (`is_active`),
  ADD KEY `ad_reward_rules_rule_type_index` (`rule_type`);

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blocked_users_member_id_blocked_member_id_unique` (`member_id`,`blocked_member_id`),
  ADD KEY `blocked_users_blocked_member_id_foreign` (`blocked_member_id`);

--
-- Indexes for table `business_analytics_snapshots`
--
ALTER TABLE `business_analytics_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `biz_snap_page_date_unique` (`business_page_id`,`snapshot_date`);

--
-- Indexes for table `business_analytics_views`
--
ALTER TABLE `business_analytics_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_analytics_views_member_id_foreign` (`member_id`),
  ADD KEY `business_analytics_views_business_page_id_viewed_at_index` (`business_page_id`,`viewed_at`);

--
-- Indexes for table `business_conversations`
--
ALTER TABLE `business_conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_conversations_business_page_id_customer_id_unique` (`business_page_id`,`customer_id`),
  ADD KEY `business_conversations_customer_id_foreign` (`customer_id`),
  ADD KEY `business_conversations_business_page_id_status_index` (`business_page_id`,`status`),
  ADD KEY `business_conversations_business_page_id_last_message_at_index` (`business_page_id`,`last_message_at`);

--
-- Indexes for table `business_followers`
--
ALTER TABLE `business_followers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_followers_business_page_id_member_id_unique` (`business_page_id`,`member_id`),
  ADD KEY `business_followers_business_page_id_status_index` (`business_page_id`,`status`),
  ADD KEY `business_followers_member_id_status_index` (`member_id`,`status`);

--
-- Indexes for table `business_follower_invitations`
--
ALTER TABLE `business_follower_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_follower_invitations_business_page_id_invitee_id_unique` (`business_page_id`,`invitee_id`),
  ADD KEY `business_follower_invitations_inviter_id_foreign` (`inviter_id`),
  ADD KEY `business_follower_invitations_business_page_id_status_index` (`business_page_id`,`status`),
  ADD KEY `business_follower_invitations_invitee_id_status_index` (`invitee_id`,`status`);

--
-- Indexes for table `business_invitations`
--
ALTER TABLE `business_invitations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_invitations_inviter_id_foreign` (`inviter_id`),
  ADD KEY `business_invitations_business_page_id_status_index` (`business_page_id`,`status`),
  ADD KEY `business_invitations_invitee_id_status_index` (`invitee_id`,`status`);

--
-- Indexes for table `business_messages`
--
ALTER TABLE `business_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_messages_sender_id_foreign` (`sender_id`),
  ADD KEY `business_messages_business_conversation_id_created_at_index` (`business_conversation_id`,`created_at`),
  ADD KEY `business_messages_business_conversation_id_is_read_index` (`business_conversation_id`,`is_read`);

--
-- Indexes for table `business_notifications`
--
ALTER TABLE `business_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_notifications_business_page_id_is_read_index` (`business_page_id`,`is_read`),
  ADD KEY `business_notifications_member_id_index` (`member_id`);

--
-- Indexes for table `business_pages`
--
ALTER TABLE `business_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_pages_page_username_unique` (`page_username`),
  ADD UNIQUE KEY `business_pages_slug_unique` (`slug`),
  ADD UNIQUE KEY `business_pages_page_id_unique` (`page_id`),
  ADD KEY `business_pages_member_id_foreign` (`member_id`),
  ADD KEY `business_pages_category_index` (`category`),
  ADD KEY `business_pages_visibility_index` (`visibility`),
  ADD KEY `business_pages_status_index` (`status`),
  ADD KEY `business_pages_is_featured_index` (`is_featured`),
  ADD KEY `business_pages_trending_score_index` (`trending_score`);

--
-- Indexes for table `business_page_categories`
--
ALTER TABLE `business_page_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_page_categories_name_unique` (`name`),
  ADD UNIQUE KEY `business_page_categories_slug_unique` (`slug`),
  ADD KEY `business_page_categories_is_active_index` (`is_active`);

--
-- Indexes for table `business_quick_replies`
--
ALTER TABLE `business_quick_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_quick_replies_business_page_id_index` (`business_page_id`);

--
-- Indexes for table `business_reviews`
--
ALTER TABLE `business_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_reviews_business_page_id_member_id_unique` (`business_page_id`,`member_id`),
  ADD KEY `business_reviews_member_id_foreign` (`member_id`),
  ADD KEY `business_reviews_business_page_id_rating_index` (`business_page_id`,`rating`),
  ADD KEY `business_reviews_business_page_id_is_hidden_index` (`business_page_id`,`is_hidden`);

--
-- Indexes for table `business_review_replies`
--
ALTER TABLE `business_review_replies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_review_replies_business_review_id_unique` (`business_review_id`),
  ADD KEY `business_review_replies_member_id_foreign` (`member_id`);

--
-- Indexes for table `business_review_reports`
--
ALTER TABLE `business_review_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_review_reports_reporter_id_foreign` (`reporter_id`),
  ADD KEY `business_review_reports_business_review_id_status_index` (`business_review_id`,`status`);

--
-- Indexes for table `business_review_votes`
--
ALTER TABLE `business_review_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_review_votes_business_review_id_member_id_unique` (`business_review_id`,`member_id`),
  ADD KEY `business_review_votes_member_id_foreign` (`member_id`);

--
-- Indexes for table `business_team_members`
--
ALTER TABLE `business_team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_team_members_business_page_id_member_id_unique` (`business_page_id`,`member_id`),
  ADD KEY `business_team_members_business_page_id_role_index` (`business_page_id`,`role`),
  ADD KEY `business_team_members_member_id_status_index` (`member_id`,`status`);

--
-- Indexes for table `business_verifications`
--
ALTER TABLE `business_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_verifications_member_id_foreign` (`member_id`),
  ADD KEY `business_verifications_business_page_id_status_index` (`business_page_id`,`status`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `comment_reactions`
--
ALTER TABLE `comment_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comment_reactions_comment_member_unique` (`comment_id`,`member_id`),
  ADD KEY `comment_reactions_comment_id_index` (`comment_id`),
  ADD KEY `comment_reactions_member_id_index` (`member_id`),
  ADD KEY `comment_reactions_reaction_index` (`reaction`);

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `communities_community_id_unique` (`community_id`),
  ADD UNIQUE KEY `communities_slug_unique` (`slug`),
  ADD UNIQUE KEY `communities_invite_code_unique` (`invite_code`),
  ADD KEY `communities_visibility_category_index` (`visibility`,`category`),
  ADD KEY `communities_owner_id_status_index` (`owner_id`,`status`),
  ADD KEY `communities_visibility_is_featured_member_count_index` (`visibility`,`is_featured`,`member_count`);

--
-- Indexes for table `community_audit_logs`
--
ALTER TABLE `community_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `community_audit_logs_actor_id_foreign` (`actor_id`),
  ADD KEY `community_audit_logs_community_id_created_at_index` (`community_id`,`created_at`);

--
-- Indexes for table `community_bans`
--
ALTER TABLE `community_bans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `community_bans_community_id_member_id_unique` (`community_id`,`member_id`),
  ADD KEY `community_bans_member_id_foreign` (`member_id`),
  ADD KEY `community_bans_banned_by_id_foreign` (`banned_by_id`);

--
-- Indexes for table `community_invitations`
--
ALTER TABLE `community_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `community_invitations_invite_code_unique` (`invite_code`),
  ADD KEY `community_invitations_invitee_id_foreign` (`invitee_id`),
  ADD KEY `community_invitations_community_id_status_index` (`community_id`,`status`),
  ADD KEY `community_invitations_inviter_id_index` (`inviter_id`),
  ADD KEY `community_invitations_invite_code_is_revoked_index` (`invite_code`,`is_revoked`);

--
-- Indexes for table `community_members`
--
ALTER TABLE `community_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `community_members_community_id_member_id_unique` (`community_id`,`member_id`),
  ADD KEY `community_members_community_id_status_index` (`community_id`,`status`),
  ADD KEY `community_members_member_id_status_index` (`member_id`,`status`),
  ADD KEY `community_members_community_id_role_index` (`community_id`,`role`);

--
-- Indexes for table `community_mutes`
--
ALTER TABLE `community_mutes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `community_mutes_community_id_member_id_unique` (`community_id`,`member_id`),
  ADD KEY `community_mutes_member_id_foreign` (`member_id`),
  ADD KEY `community_mutes_muted_by_id_foreign` (`muted_by_id`);

--
-- Indexes for table `community_reports`
--
ALTER TABLE `community_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `community_reports_reporter_id_foreign` (`reporter_id`),
  ADD KEY `community_reports_resolved_by_id_foreign` (`resolved_by_id`),
  ADD KEY `community_reports_community_id_status_index` (`community_id`,`status`);

--
-- Indexes for table `community_warnings`
--
ALTER TABLE `community_warnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `community_warnings_member_id_foreign` (`member_id`),
  ADD KEY `community_warnings_warned_by_id_foreign` (`warned_by_id`),
  ADD KEY `community_warnings_community_id_member_id_index` (`community_id`,`member_id`);

--
-- Indexes for table `direct_messages`
--
ALTER TABLE `direct_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `direct_messages_sender_id_receiver_id_index` (`sender_id`,`receiver_id`),
  ADD KEY `direct_messages_receiver_id_is_read_index` (`receiver_id`,`is_read`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `events_slug_unique` (`slug`),
  ADD KEY `events_organizer_id_foreign` (`organizer_id`),
  ADD KEY `events_start_date_event_type_privacy_index` (`start_date`,`event_type`,`privacy`);

--
-- Indexes for table `event_invitations`
--
ALTER TABLE `event_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_invitations_event_id_invited_id_unique` (`event_id`,`invited_id`),
  ADD KEY `event_invitations_inviter_id_foreign` (`inviter_id`),
  ADD KEY `event_invitations_invited_id_foreign` (`invited_id`);

--
-- Indexes for table `event_responses`
--
ALTER TABLE `event_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_responses_event_id_member_id_unique` (`event_id`,`member_id`),
  ADD KEY `event_responses_member_id_foreign` (`member_id`),
  ADD KEY `event_responses_event_id_response_index` (`event_id`,`response`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `feedback_suggestions`
--
ALTER TABLE `feedback_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `feedback_suggestions_admin_id_foreign` (`admin_id`),
  ADD KEY `feedback_suggestions_member_id_index` (`member_id`),
  ADD KEY `feedback_suggestions_type_index` (`type`),
  ADD KEY `feedback_suggestions_status_index` (`status`),
  ADD KEY `feedback_suggestions_created_at_index` (`created_at`);

--
-- Indexes for table `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `followers_follower_id_following_id_unique` (`follower_id`,`following_id`),
  ADD KEY `followers_following_id_status_index` (`following_id`,`status`);

--
-- Indexes for table `friendships`
--
ALTER TABLE `friendships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `friendships_unique_member_pair` (`member_one_id`,`member_two_id`),
  ADD KEY `friendships_member_one_index` (`member_one_id`),
  ADD KEY `friendships_member_two_index` (`member_two_id`),
  ADD KEY `friendships_requested_by_index` (`requested_by_id`),
  ADD KEY `friendships_status_index` (`status`),
  ADD KEY `idx_friendships_m1_status` (`member_one_id`,`status`),
  ADD KEY `idx_friendships_m2_status` (`member_two_id`,`status`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `groups_slug_unique` (`slug`),
  ADD KEY `groups_owner_id_foreign` (`owner_id`),
  ADD KEY `groups_privacy_category_index` (`privacy`,`category`);

--
-- Indexes for table `group_invitations`
--
ALTER TABLE `group_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_invitations_group_id_invited_id_unique` (`group_id`,`invited_id`),
  ADD KEY `group_invitations_inviter_id_foreign` (`inviter_id`),
  ADD KEY `group_invitations_invited_id_foreign` (`invited_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_members_group_id_member_id_unique` (`group_id`,`member_id`),
  ADD KEY `group_members_member_id_foreign` (`member_id`),
  ADD KEY `group_members_group_id_status_index` (`group_id`,`status`);

--
-- Indexes for table `hidden_posts`
--
ALTER TABLE `hidden_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hidden_posts_member_post_unique` (`member_id`,`post_id`),
  ADD KEY `hidden_posts_member_id_index` (`member_id`),
  ADD KEY `hidden_posts_post_id_index` (`post_id`);

--
-- Indexes for table `import_funds`
--
ALTER TABLE `import_funds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `import_funds_memberid_index` (`memberid`),
  ADD KEY `import_funds_user_id_index` (`user_id`),
  ADD KEY `import_funds_member_id_index` (`member_id`),
  ADD KEY `import_funds_txnid_index` (`txnid`),
  ADD KEY `import_funds_transaction_hash_index` (`transaction_hash`),
  ADD KEY `import_funds_orderid_index` (`orderid`),
  ADD KEY `import_funds_status_index` (`status`),
  ADD KEY `import_funds_verification_status_index` (`verification_status`),
  ADD KEY `import_funds_deposit_status_index` (`deposit_status`),
  ADD KEY `import_funds_verified_by_index` (`verified_by`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `members_email_unique` (`email`),
  ADD UNIQUE KEY `members_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `members_google_id_unique` (`google_id`),
  ADD UNIQUE KEY `members_phone_unique` (`phone`),
  ADD KEY `members_blocked_at_index` (`blocked_at`),
  ADD KEY `members_introducer_id_index` (`introducer_id`),
  ADD KEY `members_direct_referral_count_index` (`direct_referral_count`),
  ADD KEY `members_referral_counted_at_index` (`referral_counted_at`);

--
-- Indexes for table `member_verification_otps`
--
ALTER TABLE `member_verification_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_verification_otps_member_id_purpose_index` (`member_id`,`purpose`),
  ADD KEY `member_verification_otps_purpose_index` (`purpose`),
  ADD KEY `member_verification_otps_expires_at_index` (`expires_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  ADD KEY `idx_notifications_notifiable_read` (`notifiable_type`,`notifiable_id`,`read_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `pending_member_registrations`
--
ALTER TABLE `pending_member_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pending_member_registrations_token_unique` (`token`),
  ADD KEY `pending_member_registrations_user_id_index` (`user_id`),
  ADD KEY `pending_member_registrations_email_index` (`email`),
  ADD KEY `pending_member_registrations_expires_at_index` (`expires_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_slug_unique` (`slug`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posts_member_id_foreign` (`member_id`),
  ADD KEY `posts_original_post_id_index` (`original_post_id`),
  ADD KEY `posts_is_pinned_index` (`is_pinned`),
  ADD KEY `idx_posts_group_created` (`group_id`,`created_at`),
  ADD KEY `idx_posts_event_created` (`event_id`,`created_at`),
  ADD KEY `posts_community_id_is_pinned_created_at_index` (`community_id`,`is_pinned`,`created_at`),
  ADD KEY `posts_community_id_is_announcement_created_at_index` (`community_id`,`is_announcement`,`created_at`),
  ADD KEY `posts_business_page_id_is_pinned_created_at_index` (`business_page_id`,`is_pinned`,`created_at`),
  ADD KEY `posts_business_page_id_is_featured_created_at_index` (`business_page_id`,`is_featured`,`created_at`),
  ADD KEY `posts_business_page_id_is_announcement_created_at_index` (`business_page_id`,`is_announcement`,`created_at`);

--
-- Indexes for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_comments_post_id_index` (`post_id`),
  ADD KEY `post_comments_member_id_index` (`member_id`),
  ADD KEY `post_comments_created_at_index` (`created_at`),
  ADD KEY `post_comments_parent_id_index` (`parent_id`),
  ADD KEY `idx_post_comments_p_parent` (`post_id`,`parent_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_likes_post_member_unique` (`post_id`,`member_id`),
  ADD KEY `post_likes_post_id_index` (`post_id`),
  ADD KEY `post_likes_member_id_index` (`member_id`),
  ADD KEY `idx_post_likes_p_m` (`post_id`,`member_id`);

--
-- Indexes for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_reactions_post_member_unique` (`post_id`,`member_id`),
  ADD KEY `post_reactions_post_id_index` (`post_id`),
  ADD KEY `post_reactions_member_id_index` (`member_id`),
  ADD KEY `post_reactions_reaction_index` (`reaction`);

--
-- Indexes for table `post_shares`
--
ALTER TABLE `post_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_shares_original_post_id_index` (`original_post_id`),
  ADD KEY `post_shares_shared_post_id_index` (`shared_post_id`),
  ADD KEY `post_shares_shared_by_index` (`shared_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_sub_category_id_foreign` (`sub_category_id`),
  ADD KEY `products_category_id_status_index` (`category_id`,`status`),
  ADD KEY `products_member_id_status_index` (`member_id`,`status`);

--
-- Indexes for table `product_media`
--
ALTER TABLE `product_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_media_product_id_foreign` (`product_id`);

--
-- Indexes for table `profile_visits`
--
ALTER TABLE `profile_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_visits_visitor_id_foreign` (`visitor_id`),
  ADD KEY `profile_visits_profile_owner_id_visited_at_index` (`profile_owner_id`,`visited_at`);

--
-- Indexes for table `reported_posts`
--
ALTER TABLE `reported_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_posts_member_id_index` (`member_id`),
  ADD KEY `reported_posts_post_id_index` (`post_id`),
  ADD KEY `reported_posts_status_index` (`status`);

--
-- Indexes for table `reported_products`
--
ALTER TABLE `reported_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_products_member_id_foreign` (`member_id`),
  ADD KEY `reported_products_product_id_foreign` (`product_id`);

--
-- Indexes for table `reward_rank_rules`
--
ALTER TABLE `reward_rank_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reward_rank_rules_rank_key_unique` (`rank_key`),
  ADD KEY `reward_rank_rules_created_by_foreign` (`created_by`),
  ADD KEY `reward_rank_rules_updated_by_foreign` (`updated_by`),
  ADD KEY `reward_rank_rules_priority_index` (`priority`),
  ADD KEY `reward_rank_rules_referral_requirement_index` (`referral_requirement`),
  ADD KEY `reward_rank_rules_team_requirement_index` (`team_requirement`),
  ADD KEY `reward_rank_rules_is_active_index` (`is_active`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_slug_unique` (`slug`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  ADD KEY `role_permissions_permission_id_foreign` (`permission_id`);

--
-- Indexes for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `saved_posts_member_post_unique` (`member_id`,`post_id`),
  ADD KEY `saved_posts_member_id_index` (`member_id`),
  ADD KEY `saved_posts_post_id_index` (`post_id`);

--
-- Indexes for table `saved_products`
--
ALTER TABLE `saved_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `saved_products_member_id_product_id_unique` (`member_id`,`product_id`),
  ADD KEY `saved_products_product_id_foreign` (`product_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stories_member_id_expires_at_index` (`member_id`,`expires_at`),
  ADD KEY `stories_expires_at_index` (`expires_at`);

--
-- Indexes for table `story_likes`
--
ALTER TABLE `story_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `story_likes_story_member_unique` (`story_id`,`member_id`),
  ADD KEY `story_likes_story_id_index` (`story_id`),
  ADD KEY `story_likes_member_id_index` (`member_id`);

--
-- Indexes for table `story_reactions`
--
ALTER TABLE `story_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `story_reactions_story_member_unique` (`story_id`,`member_id`),
  ADD KEY `story_reactions_story_id_index` (`story_id`),
  ADD KEY `story_reactions_member_id_index` (`member_id`);

--
-- Indexes for table `story_replies`
--
ALTER TABLE `story_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `story_replies_story_id_index` (`story_id`),
  ADD KEY `story_replies_sender_id_index` (`sender_id`),
  ADD KEY `story_replies_receiver_id_index` (`receiver_id`),
  ADD KEY `story_replies_is_seen_index` (`is_seen`);

--
-- Indexes for table `story_views`
--
ALTER TABLE `story_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `story_views_story_viewer_unique` (`story_id`,`viewer_member_id`),
  ADD KEY `story_views_story_id_index` (`story_id`),
  ADD KEY `story_views_viewer_member_id_index` (`viewer_member_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `withdrawal_requests_member_id_foreign` (`member_id`);

--
-- Indexes for table `withdrawal_settings`
--
ALTER TABLE `withdrawal_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ad_campaigns`
--
ALTER TABLE `ad_campaigns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ad_campaign_activities`
--
ALTER TABLE `ad_campaign_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ad_deposits`
--
ALTER TABLE `ad_deposits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ad_impressions`
--
ALTER TABLE `ad_impressions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ad_rewards`
--
ALTER TABLE `ad_rewards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ad_reward_rules`
--
ALTER TABLE `ad_reward_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blocked_users`
--
ALTER TABLE `blocked_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_analytics_snapshots`
--
ALTER TABLE `business_analytics_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_analytics_views`
--
ALTER TABLE `business_analytics_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `business_conversations`
--
ALTER TABLE `business_conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_followers`
--
ALTER TABLE `business_followers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_follower_invitations`
--
ALTER TABLE `business_follower_invitations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_invitations`
--
ALTER TABLE `business_invitations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_messages`
--
ALTER TABLE `business_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_notifications`
--
ALTER TABLE `business_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_pages`
--
ALTER TABLE `business_pages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `business_page_categories`
--
ALTER TABLE `business_page_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `business_quick_replies`
--
ALTER TABLE `business_quick_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_reviews`
--
ALTER TABLE `business_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_review_replies`
--
ALTER TABLE `business_review_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_review_reports`
--
ALTER TABLE `business_review_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_review_votes`
--
ALTER TABLE `business_review_votes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_team_members`
--
ALTER TABLE `business_team_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_verifications`
--
ALTER TABLE `business_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment_reactions`
--
ALTER TABLE `comment_reactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `community_audit_logs`
--
ALTER TABLE `community_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_bans`
--
ALTER TABLE `community_bans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_invitations`
--
ALTER TABLE `community_invitations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_members`
--
ALTER TABLE `community_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_mutes`
--
ALTER TABLE `community_mutes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `community_reports`
--
ALTER TABLE `community_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_warnings`
--
ALTER TABLE `community_warnings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `direct_messages`
--
ALTER TABLE `direct_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `event_invitations`
--
ALTER TABLE `event_invitations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_responses`
--
ALTER TABLE `event_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_suggestions`
--
ALTER TABLE `feedback_suggestions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `followers`
--
ALTER TABLE `followers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friendships`
--
ALTER TABLE `friendships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_invitations`
--
ALTER TABLE `group_invitations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hidden_posts`
--
ALTER TABLE `hidden_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_funds`
--
ALTER TABLE `import_funds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `member_verification_otps`
--
ALTER TABLE `member_verification_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pending_member_registrations`
--
ALTER TABLE `pending_member_registrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post_reactions`
--
ALTER TABLE `post_reactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post_shares`
--
ALTER TABLE `post_shares`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_media`
--
ALTER TABLE `product_media`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profile_visits`
--
ALTER TABLE `profile_visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reported_posts`
--
ALTER TABLE `reported_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reported_products`
--
ALTER TABLE `reported_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward_rank_rules`
--
ALTER TABLE `reward_rank_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_posts`
--
ALTER TABLE `saved_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_products`
--
ALTER TABLE `saved_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `story_likes`
--
ALTER TABLE `story_likes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `story_reactions`
--
ALTER TABLE `story_reactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `story_replies`
--
ALTER TABLE `story_replies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `story_views`
--
ALTER TABLE `story_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `withdrawal_settings`
--
ALTER TABLE `withdrawal_settings`
  MODIFY `id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------

--
-- Structure for view `import_fund`
--
DROP TABLE IF EXISTS `import_fund`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u834681197_Mlm_Book`@`127.0.0.1` SQL SECURITY DEFINER VIEW `import_fund`  AS SELECT `import_funds`.`id` AS `id`, `import_funds`.`memberid` AS `memberid`, `import_funds`.`user_id` AS `user_id`, `import_funds`.`member_id` AS `member_id`, `import_funds`.`txnid` AS `txnid`, `import_funds`.`transaction_hash` AS `transaction_hash`, `import_funds`.`orderid` AS `orderid`, `import_funds`.`amount` AS `amount`, `import_funds`.`type` AS `type`, `import_funds`.`wallet_type` AS `wallet_type`, `import_funds`.`wallet_address` AS `wallet_address`, `import_funds`.`network` AS `network`, `import_funds`.`token` AS `token`, `import_funds`.`contract_address` AS `contract_address`, `import_funds`.`added_by` AS `added_by`, `import_funds`.`status` AS `status`, `import_funds`.`verification_status` AS `verification_status`, `import_funds`.`deposit_status` AS `deposit_status`, `import_funds`.`verification_payload` AS `verification_payload`, `import_funds`.`admin_notes` AS `admin_notes`, `import_funds`.`rejection_reason` AS `rejection_reason`, `import_funds`.`verified_at` AS `verified_at`, `import_funds`.`verified_by` AS `verified_by`, `import_funds`.`mode` AS `mode`, `import_funds`.`created_at` AS `created_at`, `import_funds`.`updated_at` AS `updated_at` FROM `import_funds` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD CONSTRAINT `admin_roles_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ad_campaigns`
--
ALTER TABLE `ad_campaigns`
  ADD CONSTRAINT `ad_campaigns_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ad_campaigns_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_campaigns_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ad_campaigns_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_campaigns_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ad_campaign_activities`
--
ALTER TABLE `ad_campaign_activities`
  ADD CONSTRAINT `ad_campaign_activities_ad_campaign_id_foreign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_campaign_activities_ad_reward_id_foreign` FOREIGN KEY (`ad_reward_id`) REFERENCES `ad_rewards` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ad_campaign_activities_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_campaign_activities_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  ADD CONSTRAINT `ad_clicks_ad_campaign_id_foreign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_clicks_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ad_clicks_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ad_deposits`
--
ALTER TABLE `ad_deposits`
  ADD CONSTRAINT `ad_deposits_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ad_deposits_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_deposits_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ad_impressions`
--
ALTER TABLE `ad_impressions`
  ADD CONSTRAINT `ad_impressions_ad_campaign_id_foreign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_impressions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ad_impressions_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ad_rewards`
--
ALTER TABLE `ad_rewards`
  ADD CONSTRAINT `ad_rewards_ad_campaign_id_foreign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_rewards_ad_reward_rule_id_foreign` FOREIGN KEY (`ad_reward_rule_id`) REFERENCES `ad_reward_rules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ad_rewards_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ad_rewards_reward_rank_rule_id_foreign` FOREIGN KEY (`reward_rank_rule_id`) REFERENCES `reward_rank_rules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ad_reward_rules`
--
ALTER TABLE `ad_reward_rules`
  ADD CONSTRAINT `ad_reward_rules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ad_reward_rules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD CONSTRAINT `blocked_users_blocked_member_id_foreign` FOREIGN KEY (`blocked_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocked_users_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_analytics_snapshots`
--
ALTER TABLE `business_analytics_snapshots`
  ADD CONSTRAINT `business_analytics_snapshots_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_analytics_views`
--
ALTER TABLE `business_analytics_views`
  ADD CONSTRAINT `business_analytics_views_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_analytics_views_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `business_conversations`
--
ALTER TABLE `business_conversations`
  ADD CONSTRAINT `business_conversations_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_conversations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_followers`
--
ALTER TABLE `business_followers`
  ADD CONSTRAINT `business_followers_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_followers_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_follower_invitations`
--
ALTER TABLE `business_follower_invitations`
  ADD CONSTRAINT `business_follower_invitations_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_follower_invitations_invitee_id_foreign` FOREIGN KEY (`invitee_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_follower_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_invitations`
--
ALTER TABLE `business_invitations`
  ADD CONSTRAINT `business_invitations_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_invitations_invitee_id_foreign` FOREIGN KEY (`invitee_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_messages`
--
ALTER TABLE `business_messages`
  ADD CONSTRAINT `business_messages_business_conversation_id_foreign` FOREIGN KEY (`business_conversation_id`) REFERENCES `business_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_notifications`
--
ALTER TABLE `business_notifications`
  ADD CONSTRAINT `business_notifications_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_notifications_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_pages`
--
ALTER TABLE `business_pages`
  ADD CONSTRAINT `business_pages_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_quick_replies`
--
ALTER TABLE `business_quick_replies`
  ADD CONSTRAINT `business_quick_replies_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_reviews`
--
ALTER TABLE `business_reviews`
  ADD CONSTRAINT `business_reviews_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_reviews_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_review_replies`
--
ALTER TABLE `business_review_replies`
  ADD CONSTRAINT `business_review_replies_business_review_id_foreign` FOREIGN KEY (`business_review_id`) REFERENCES `business_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_review_replies_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_review_reports`
--
ALTER TABLE `business_review_reports`
  ADD CONSTRAINT `business_review_reports_business_review_id_foreign` FOREIGN KEY (`business_review_id`) REFERENCES `business_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_review_reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_review_votes`
--
ALTER TABLE `business_review_votes`
  ADD CONSTRAINT `business_review_votes_business_review_id_foreign` FOREIGN KEY (`business_review_id`) REFERENCES `business_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_review_votes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_team_members`
--
ALTER TABLE `business_team_members`
  ADD CONSTRAINT `business_team_members_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_team_members_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_verifications`
--
ALTER TABLE `business_verifications`
  ADD CONSTRAINT `business_verifications_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_verifications_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comment_reactions`
--
ALTER TABLE `comment_reactions`
  ADD CONSTRAINT `comment_reactions_comment_id_foreign` FOREIGN KEY (`comment_id`) REFERENCES `post_comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_reactions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `communities`
--
ALTER TABLE `communities`
  ADD CONSTRAINT `communities_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_audit_logs`
--
ALTER TABLE `community_audit_logs`
  ADD CONSTRAINT `community_audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_audit_logs_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_bans`
--
ALTER TABLE `community_bans`
  ADD CONSTRAINT `community_bans_banned_by_id_foreign` FOREIGN KEY (`banned_by_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_bans_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_bans_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_invitations`
--
ALTER TABLE `community_invitations`
  ADD CONSTRAINT `community_invitations_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_invitations_invitee_id_foreign` FOREIGN KEY (`invitee_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_members`
--
ALTER TABLE `community_members`
  ADD CONSTRAINT `community_members_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_members_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_mutes`
--
ALTER TABLE `community_mutes`
  ADD CONSTRAINT `community_mutes_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_mutes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_mutes_muted_by_id_foreign` FOREIGN KEY (`muted_by_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `community_reports`
--
ALTER TABLE `community_reports`
  ADD CONSTRAINT `community_reports_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_reports_resolved_by_id_foreign` FOREIGN KEY (`resolved_by_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `community_warnings`
--
ALTER TABLE `community_warnings`
  ADD CONSTRAINT `community_warnings_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_warnings_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_warnings_warned_by_id_foreign` FOREIGN KEY (`warned_by_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `direct_messages`
--
ALTER TABLE `direct_messages`
  ADD CONSTRAINT `direct_messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `direct_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_organizer_id_foreign` FOREIGN KEY (`organizer_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_invitations`
--
ALTER TABLE `event_invitations`
  ADD CONSTRAINT `event_invitations_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_invitations_invited_id_foreign` FOREIGN KEY (`invited_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_responses`
--
ALTER TABLE `event_responses`
  ADD CONSTRAINT `event_responses_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_responses_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_suggestions`
--
ALTER TABLE `feedback_suggestions`
  ADD CONSTRAINT `feedback_suggestions_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `feedback_suggestions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `followers_follower_id_foreign` FOREIGN KEY (`follower_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followers_following_id_foreign` FOREIGN KEY (`following_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `friendships`
--
ALTER TABLE `friendships`
  ADD CONSTRAINT `friendships_member_one_id_foreign` FOREIGN KEY (`member_one_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friendships_member_two_id_foreign` FOREIGN KEY (`member_two_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friendships_requested_by_id_foreign` FOREIGN KEY (`requested_by_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_invitations`
--
ALTER TABLE `group_invitations`
  ADD CONSTRAINT `group_invitations_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_invitations_invited_id_foreign` FOREIGN KEY (`invited_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hidden_posts`
--
ALTER TABLE `hidden_posts`
  ADD CONSTRAINT `hidden_posts_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hidden_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_verification_otps`
--
ALTER TABLE `member_verification_otps`
  ADD CONSTRAINT `member_verification_otps_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_original_post_id_foreign` FOREIGN KEY (`original_post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `post_comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD CONSTRAINT `post_reactions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_reactions_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_shares`
--
ALTER TABLE `post_shares`
  ADD CONSTRAINT `post_shares_original_post_id_foreign` FOREIGN KEY (`original_post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_shares_shared_by_foreign` FOREIGN KEY (`shared_by`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_shares_shared_post_id_foreign` FOREIGN KEY (`shared_post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_media`
--
ALTER TABLE `product_media`
  ADD CONSTRAINT `product_media_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_visits`
--
ALTER TABLE `profile_visits`
  ADD CONSTRAINT `profile_visits_profile_owner_id_foreign` FOREIGN KEY (`profile_owner_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profile_visits_visitor_id_foreign` FOREIGN KEY (`visitor_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reported_posts`
--
ALTER TABLE `reported_posts`
  ADD CONSTRAINT `reported_posts_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reported_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reported_products`
--
ALTER TABLE `reported_products`
  ADD CONSTRAINT `reported_products_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reported_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reward_rank_rules`
--
ALTER TABLE `reward_rank_rules`
  ADD CONSTRAINT `reward_rank_rules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reward_rank_rules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD CONSTRAINT `saved_posts_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_products`
--
ALTER TABLE `saved_products`
  ADD CONSTRAINT `saved_products_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stories`
--
ALTER TABLE `stories`
  ADD CONSTRAINT `stories_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `story_likes`
--
ALTER TABLE `story_likes`
  ADD CONSTRAINT `story_likes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_likes_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `story_reactions`
--
ALTER TABLE `story_reactions`
  ADD CONSTRAINT `story_reactions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_reactions_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `story_replies`
--
ALTER TABLE `story_replies`
  ADD CONSTRAINT `story_replies_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_replies_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_replies_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `story_views`
--
ALTER TABLE `story_views`
  ADD CONSTRAINT `story_views_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `story_views_viewer_member_id_foreign` FOREIGN KEY (`viewer_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
