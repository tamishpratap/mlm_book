-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: 834681197_mlm_book
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ad_campaign_activities`
--

DROP TABLE IF EXISTS `ad_campaign_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_campaign_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad_campaign_id` bigint(20) unsigned NOT NULL,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `action_label` varchar(100) DEFAULT NULL,
  `qualifying_event_id` varchar(100) DEFAULT NULL,
  `ad_reward_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ad_campaign_activities_ad_reward_id_foreign` (`ad_reward_id`),
  KEY `ad_campaign_activities_ad_campaign_id_created_at_index` (`ad_campaign_id`,`created_at`),
  KEY `ad_campaign_activities_ad_campaign_id_action_index` (`ad_campaign_id`,`action`),
  KEY `ad_campaign_activities_ad_campaign_id_member_id_index` (`ad_campaign_id`,`member_id`),
  KEY `ad_campaign_activities_business_page_id_created_at_index` (`business_page_id`,`created_at`),
  KEY `ad_campaign_activities_member_id_created_at_index` (`member_id`,`created_at`),
  KEY `ad_campaign_activities_action_index` (`action`),
  KEY `ad_campaign_activities_qualifying_event_id_index` (`qualifying_event_id`),
  CONSTRAINT `ad_campaign_activities_ad_campaign_id_foreign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_campaign_activities_ad_reward_id_foreign` FOREIGN KEY (`ad_reward_id`) REFERENCES `ad_rewards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_campaign_activities_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_campaign_activities_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_campaign_activities`
--

LOCK TABLES `ad_campaign_activities` WRITE;
/*!40000 ALTER TABLE `ad_campaign_activities` DISABLE KEYS */;
INSERT INTO `ad_campaign_activities` VALUES (1,3,1,14,'clicked','Ad Click','clk_camp_zb8t6jdek2u1_7_14_1790579736284',NULL,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','{\"placement\":\"social_feed\"}','2026-09-28 12:46:55','2026-09-28 12:46:55'),(2,3,1,14,'clicked','Ad Click','clk_camp_zb8t6jdek2u1_7_14_1790579736908',NULL,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','{\"placement\":\"social_feed\"}','2026-09-28 12:46:56','2026-09-28 12:46:56'),(3,3,1,14,'interested','Interested','int_3_14_1790579917',NULL,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','{\"click_key\":\"int_3_14_1790579917\"}','2026-09-28 12:48:37','2026-09-28 12:48:37'),(4,3,1,14,'interested','Interested','int_3_14_1790579958',NULL,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','{\"click_key\":\"int_3_14_1790579958\"}','2026-09-28 12:49:18','2026-09-28 12:49:18'),(5,3,1,14,'interested','Interested','int_3_14_1790580016',NULL,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','{\"click_key\":\"int_3_14_1790580016\"}','2026-09-28 12:50:16','2026-09-28 12:50:16');
/*!40000 ALTER TABLE `ad_campaign_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ad_campaigns`
--

DROP TABLE IF EXISTS `ad_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` varchar(32) NOT NULL,
  `campaign_type` varchar(32) NOT NULL DEFAULT 'business_ad',
  `business_page_id` bigint(20) unsigned DEFAULT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `event_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `budget` decimal(14,4) unsigned NOT NULL,
  `additional_funding` decimal(14,4) unsigned NOT NULL DEFAULT 0.0000,
  `total_funded` decimal(14,4) unsigned NOT NULL DEFAULT 0.0000,
  `currency` varchar(8) NOT NULL DEFAULT 'USD',
  `fee_percent` decimal(5,2) unsigned NOT NULL DEFAULT 2.50,
  `fee_amount` decimal(14,4) unsigned NOT NULL DEFAULT 0.0000,
  `wallet_debit` decimal(14,4) unsigned NOT NULL DEFAULT 0.0000,
  `spent_amount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `remaining_amount` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `target_audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_audience`)),
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'draft',
  `approval_status` varchar(32) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ad_campaigns_campaign_id_unique` (`campaign_id`),
  UNIQUE KEY `ad_campaigns_event_id_unique` (`event_id`),
  KEY `ad_campaigns_approved_by_foreign` (`approved_by`),
  KEY `ad_campaigns_business_page_id_status_index` (`business_page_id`,`status`),
  KEY `ad_campaigns_member_id_status_index` (`member_id`,`status`),
  KEY `ad_campaigns_status_index` (`status`),
  KEY `ad_campaigns_approval_status_index` (`approval_status`),
  KEY `ad_campaigns_campaign_type_index` (`campaign_type`),
  KEY `ad_campaigns_post_id_index` (`post_id`),
  CONSTRAINT `ad_campaigns_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_campaigns_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_campaigns_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_campaigns_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_campaigns_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_campaigns`
--

LOCK TABLES `ad_campaigns` WRITE;
/*!40000 ALTER TABLE `ad_campaigns` DISABLE KEYS */;
INSERT INTO `ad_campaigns` VALUES (3,'camp_zb8t6jdek2u1','business_ad',1,13,7,NULL,'Digi Tech Solutions Pvt Ltd Campaign - 9/28/2026',50.0000,0.0000,50.0000,'USD',2.50,1.2500,51.2500,0.0000,50.0000,NULL,'2026-09-28 07:13:00',NULL,'approved','approved',25,'2026-09-28 12:44:35',NULL,'2026-09-28 07:14:20','2026-09-28 07:14:35');
/*!40000 ALTER TABLE `ad_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ad_clicks`
--

DROP TABLE IF EXISTS `ad_clicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_clicks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad_campaign_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `placement` varchar(50) NOT NULL DEFAULT 'social_feed',
  `click_key` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ad_clicks_member_id_foreign` (`member_id`),
  KEY `ad_clicks_post_id_foreign` (`post_id`),
  KEY `ad_clicks_ad_campaign_id_created_at_index` (`ad_campaign_id`,`created_at`),
  KEY `ad_clicks_ad_campaign_id_member_id_created_at_index` (`ad_campaign_id`,`member_id`,`created_at`),
  KEY `ad_clicks_placement_index` (`placement`),
  KEY `ad_clicks_click_key_index` (`click_key`),
  KEY `ad_clicks_created_at_index` (`created_at`),
  CONSTRAINT `ad_clicks_ad_campaign_id_foreign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_clicks_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_clicks_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_clicks`
--

LOCK TABLES `ad_clicks` WRITE;
/*!40000 ALTER TABLE `ad_clicks` DISABLE KEYS */;
INSERT INTO `ad_clicks` VALUES (1,3,14,7,'social_feed','clk_camp_zb8t6jdek2u1_7_14_1790579736284','2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-28 07:16:55'),(2,3,14,7,'social_feed','clk_camp_zb8t6jdek2u1_7_14_1790579736908','2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-28 07:16:56'),(3,3,14,NULL,'interested_feed_cta','int_3_14_1790579917','2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-28 07:18:37'),(4,3,14,NULL,'interested_feed_cta','int_3_14_1790579958','2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-28 07:19:18'),(5,3,14,NULL,'interested_feed_cta','int_3_14_1790580016','2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-28 07:20:16');
/*!40000 ALTER TABLE `ad_clicks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ad_deposits`
--

DROP TABLE IF EXISTS `ad_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_deposits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deposit_id` varchar(32) NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `business_page_id` bigint(20) unsigned DEFAULT NULL,
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
  `block_number` bigint(20) unsigned DEFAULT NULL,
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
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ad_deposits_deposit_id_unique` (`deposit_id`),
  KEY `ad_deposits_member_id_foreign` (`member_id`),
  KEY `ad_deposits_business_page_id_foreign` (`business_page_id`),
  KEY `ad_deposits_verified_by_foreign` (`verified_by`),
  KEY `ad_deposits_transaction_reference_index` (`transaction_reference`),
  KEY `ad_deposits_status_index` (`status`),
  CONSTRAINT `ad_deposits_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_deposits_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_deposits_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_deposits`
--

LOCK TABLES `ad_deposits` WRITE;
/*!40000 ALTER TABLE `ad_deposits` DISABLE KEYS */;
INSERT INTO `ad_deposits` VALUES (1,'DEP-BROTNBY0CI',13,NULL,5500.00,10.00,500.00,5000.00,'USDT','USDT','BEP-20','USDT','0x55d398326f99059fF775485246999027B3197955','0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c',38920145,5500.0000,5500.0000,1.0000,5000.00,'0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066','0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066','approved','verified','bsc_rpc',NULL,'{\"verified\":true,\"status\":\"verified\",\"tx_hash\":\"0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066\",\"from_address\":\"0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c\",\"to_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"transferred_amount\":5500,\"submitted_amount\":5500,\"token\":\"USDT\",\"network\":\"BEP-20\",\"contract_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"block_number\":38920145,\"confirmations\":12,\"verification_source\":\"bsc_rpc\",\"message\":\"Successfully verified 5500 USDT transfer on BNB Smart Chain.\"}','Web3 DApp Instant Deposit: Auto-approved on-chain. Received: 5500 USDT. Net credited: $5000 USD to Member Fund Wallet.',NULL,'2026-09-28 12:20:02','2026-09-28 12:20:02',NULL,'2026-09-28 06:50:02','2026-09-28 06:50:02',NULL),(2,'DEP-UVDUEYSTBC',1,NULL,55.00,10.00,5.00,50.00,'USDT','USDT','BEP-20','USDT','0x55d398326f99059fF775485246999027B3197955','0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584',38920145,55.0000,55.0000,1.0000,50.00,'0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3','0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3','approved','verified','bsc_rpc',NULL,'{\"verified\":true,\"status\":\"verified\",\"tx_hash\":\"0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3\",\"from_address\":\"0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584\",\"to_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"transferred_amount\":55,\"submitted_amount\":55,\"token\":\"USDT\",\"network\":\"BEP-20\",\"contract_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"block_number\":38920145,\"confirmations\":12,\"verification_source\":\"bsc_rpc\",\"message\":\"Successfully verified 55 USDT transfer on BNB Smart Chain.\"}','Web3 DApp Instant Deposit: Auto-approved on-chain. Received: 55 USDT. Net credited: $50 USD to Member Fund Wallet.',NULL,'2026-09-28 14:31:32','2026-09-28 14:31:32',NULL,'2026-09-28 14:31:32','2026-09-28 14:31:32',NULL);
/*!40000 ALTER TABLE `ad_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ad_impressions`
--

DROP TABLE IF EXISTS `ad_impressions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_impressions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad_campaign_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `placement` varchar(50) NOT NULL DEFAULT 'social_feed',
  `impression_key` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ad_impressions_member_id_foreign` (`member_id`),
  KEY `ad_impressions_post_id_foreign` (`post_id`),
  KEY `ad_impressions_ad_campaign_id_created_at_index` (`ad_campaign_id`,`created_at`),
  KEY `ad_impressions_ad_campaign_id_member_id_created_at_index` (`ad_campaign_id`,`member_id`,`created_at`),
  KEY `ad_impressions_placement_index` (`placement`),
  KEY `ad_impressions_impression_key_index` (`impression_key`),
  KEY `ad_impressions_created_at_index` (`created_at`),
  CONSTRAINT `ad_impressions_ad_campaign_id_foreign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_impressions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_impressions_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_impressions`
--

LOCK TABLES `ad_impressions` WRITE;
/*!40000 ALTER TABLE `ad_impressions` DISABLE KEYS */;
INSERT INTO `ad_impressions` VALUES (1,3,14,7,'social_feed','imp_camp_zb8t6jdek2u1_7_14','2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-28 07:15:00'),(2,3,14,7,'social_feed','imp_camp_zb8t6jdek2u1_7_14','2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-28 07:20:02');
/*!40000 ALTER TABLE `ad_impressions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ad_reward_rules`
--

DROP TABLE IF EXISTS `ad_reward_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_reward_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rule_type` varchar(32) NOT NULL DEFAULT 'business_ad',
  `min_referrals` int(10) unsigned NOT NULL DEFAULT 0,
  `max_referrals` int(10) unsigned DEFAULT NULL,
  `reward_amount` decimal(10,4) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ad_reward_rules_created_by_foreign` (`created_by`),
  KEY `ad_reward_rules_updated_by_foreign` (`updated_by`),
  KEY `ad_reward_rules_min_referrals_index` (`min_referrals`),
  KEY `ad_reward_rules_max_referrals_index` (`max_referrals`),
  KEY `ad_reward_rules_is_active_index` (`is_active`),
  KEY `ad_reward_rules_rule_type_index` (`rule_type`),
  CONSTRAINT `ad_reward_rules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_reward_rules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_reward_rules`
--

LOCK TABLES `ad_reward_rules` WRITE;
/*!40000 ALTER TABLE `ad_reward_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `ad_reward_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ad_rewards`
--

DROP TABLE IF EXISTS `ad_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ad_rewards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad_campaign_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `ad_reward_rule_id` bigint(20) unsigned DEFAULT NULL,
  `reward_rank_rule_id` bigint(20) unsigned DEFAULT NULL,
  `direct_verified_referral_count` int(10) unsigned DEFAULT NULL,
  `team_count` int(10) unsigned DEFAULT NULL,
  `rule_min_referrals` int(10) unsigned DEFAULT NULL,
  `rule_max_referrals` int(10) unsigned DEFAULT NULL,
  `rule_version` varchar(50) DEFAULT NULL,
  `rank_at_reward` varchar(50) DEFAULT NULL,
  `reward_amount_usd` decimal(14,4) unsigned NOT NULL DEFAULT 0.0500,
  `qualifying_event_id` varchar(100) DEFAULT NULL,
  `landing_page_url` varchar(2048) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'credited',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ad_rewards_campaign_member_unique` (`ad_campaign_id`,`member_id`),
  UNIQUE KEY `ad_reward_unique_event` (`ad_campaign_id`,`member_id`,`qualifying_event_id`),
  KEY `ad_rewards_ad_campaign_id_member_id_index` (`ad_campaign_id`,`member_id`),
  KEY `ad_rewards_ad_campaign_id_index` (`ad_campaign_id`),
  KEY `ad_rewards_member_id_index` (`member_id`),
  KEY `ad_rewards_qualifying_event_id_index` (`qualifying_event_id`),
  KEY `ad_rewards_status_index` (`status`),
  KEY `ad_rewards_ad_reward_rule_id_index` (`ad_reward_rule_id`),
  KEY `ad_rewards_reward_rank_rule_id_index` (`reward_rank_rule_id`),
  CONSTRAINT `ad_rewards_ad_campaign_id_foreign` FOREIGN KEY (`ad_campaign_id`) REFERENCES `ad_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_rewards_ad_reward_rule_id_foreign` FOREIGN KEY (`ad_reward_rule_id`) REFERENCES `ad_reward_rules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ad_rewards_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_rewards_reward_rank_rule_id_foreign` FOREIGN KEY (`reward_rank_rule_id`) REFERENCES `reward_rank_rules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_rewards`
--

LOCK TABLES `ad_rewards` WRITE;
/*!40000 ALTER TABLE `ad_rewards` DISABLE KEYS */;
/*!40000 ALTER TABLE `ad_rewards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_roles`
--

DROP TABLE IF EXISTS `admin_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_roles_admin_id_role_id_unique` (`admin_id`,`role_id`),
  KEY `admin_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `admin_roles_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_roles`
--

LOCK TABLES `admin_roles` WRITE;
/*!40000 ALTER TABLE `admin_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (24,'Admin','admin@gmail.com','$2y$12$nhVZQFZG6qI4ZneKRgegP.gdgZCP74jKood2ydbRFmSxNxm2ck6ka',NULL,'active','N9uWeruQkmG2gUCDkSqdUzinucFG7tyaEiRW1sU0PnwORZstrTcmlvjVa7kl','2026-09-28 11:02:00','2405:201:6821:5810:bd94:97dd:7c8:ea8b','2026-09-01 07:40:21','2026-09-28 11:02:00'),(25,'Admin User','pro@cpanel.com','$2y$12$M16jVLy2Kc7XWg39mDCiDuDQHIlp2Q5VnHNYGyQU2TH4NUO4LkEl.',NULL,'active','mDBqnmllyFNZa6DdbDTU4SglbLVoY9AAdihh0OBQonJsqRfE5uKkwn7QsECj','2026-09-26 18:12:03','49.43.160.232','2026-09-01 07:40:30','2026-09-26 18:12:03');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocked_users`
--

DROP TABLE IF EXISTS `blocked_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocked_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `blocked_member_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blocked_users_member_id_blocked_member_id_unique` (`member_id`,`blocked_member_id`),
  KEY `blocked_users_blocked_member_id_foreign` (`blocked_member_id`),
  CONSTRAINT `blocked_users_blocked_member_id_foreign` FOREIGN KEY (`blocked_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blocked_users_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_users`
--

LOCK TABLES `blocked_users` WRITE;
/*!40000 ALTER TABLE `blocked_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocked_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_analytics_snapshots`
--

DROP TABLE IF EXISTS `business_analytics_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_analytics_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `snapshot_date` date NOT NULL,
  `followers_count` int(11) NOT NULL DEFAULT 0,
  `posts_count` int(11) NOT NULL DEFAULT 0,
  `reviews_count` int(11) NOT NULL DEFAULT 0,
  `average_rating` double NOT NULL DEFAULT 0,
  `messages_count` int(11) NOT NULL DEFAULT 0,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `health_score` double NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_snap_page_date_unique` (`business_page_id`,`snapshot_date`),
  CONSTRAINT `business_analytics_snapshots_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_analytics_snapshots`
--

LOCK TABLES `business_analytics_snapshots` WRITE;
/*!40000 ALTER TABLE `business_analytics_snapshots` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_analytics_snapshots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_analytics_views`
--

DROP TABLE IF EXISTS `business_analytics_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_analytics_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `device_type` varchar(255) NOT NULL DEFAULT 'desktop' COMMENT 'desktop, mobile, tablet',
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `business_analytics_views_member_id_foreign` (`member_id`),
  KEY `business_analytics_views_business_page_id_viewed_at_index` (`business_page_id`,`viewed_at`),
  CONSTRAINT `business_analytics_views_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_analytics_views_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_analytics_views`
--

LOCK TABLES `business_analytics_views` WRITE;
/*!40000 ALTER TABLE `business_analytics_views` DISABLE KEYS */;
INSERT INTO `business_analytics_views` VALUES (1,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:29:48'),(2,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:35:07'),(3,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:35:29'),(4,1,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:42:52'),(5,1,14,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:42:54'),(6,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:43:27'),(7,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:43:33'),(8,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:44:46'),(9,1,14,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:48:38'),(10,1,14,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:48:45'),(11,1,14,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:49:18'),(12,1,14,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:49:22'),(13,1,14,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:49:34'),(14,1,14,'2405:201:6821:5810:bd19:df89:1af1:a0af','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:50:17'),(15,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 12:54:12'),(16,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 13:01:55'),(17,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 13:08:53'),(18,1,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 13:09:12'),(19,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 13:09:18'),(20,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 13:13:26'),(21,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 13:24:43'),(22,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 13:36:44'),(23,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 13:36:54'),(24,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 13:36:59'),(25,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 13:37:00'),(26,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 13:43:15'),(27,1,13,'2405:201:6821:5810:c53b:d274:55f2:9935','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Meerut','2026-09-28 14:25:34'),(28,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 14:25:50'),(29,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 14:31:35'),(30,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 14:31:36'),(31,2,1,'2405:201:6819:317e:78f4:c6d:a626:73b6','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','desktop','India','Online','2026-09-28 14:32:21');
/*!40000 ALTER TABLE `business_analytics_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_conversations`
--

DROP TABLE IF EXISTS `business_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active' COMMENT 'active, pending_request, archived, closed',
  `is_starred` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_conversations_business_page_id_customer_id_unique` (`business_page_id`,`customer_id`),
  KEY `business_conversations_customer_id_foreign` (`customer_id`),
  KEY `business_conversations_business_page_id_status_index` (`business_page_id`,`status`),
  KEY `business_conversations_business_page_id_last_message_at_index` (`business_page_id`,`last_message_at`),
  CONSTRAINT `business_conversations_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_conversations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_conversations`
--

LOCK TABLES `business_conversations` WRITE;
/*!40000 ALTER TABLE `business_conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_follower_invitations`
--

DROP TABLE IF EXISTS `business_follower_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_follower_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `inviter_id` bigint(20) unsigned NOT NULL,
  `invitee_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, accepted, declined',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_follower_invitations_business_page_id_invitee_id_unique` (`business_page_id`,`invitee_id`),
  KEY `business_follower_invitations_inviter_id_foreign` (`inviter_id`),
  KEY `business_follower_invitations_business_page_id_status_index` (`business_page_id`,`status`),
  KEY `business_follower_invitations_invitee_id_status_index` (`invitee_id`,`status`),
  CONSTRAINT `business_follower_invitations_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_follower_invitations_invitee_id_foreign` FOREIGN KEY (`invitee_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_follower_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_follower_invitations`
--

LOCK TABLES `business_follower_invitations` WRITE;
/*!40000 ALTER TABLE `business_follower_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_follower_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_followers`
--

DROP TABLE IF EXISTS `business_followers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_followers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'accepted' COMMENT 'accepted, pending, blocked',
  `followed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_followers_business_page_id_member_id_unique` (`business_page_id`,`member_id`),
  KEY `business_followers_business_page_id_status_index` (`business_page_id`,`status`),
  KEY `business_followers_member_id_status_index` (`member_id`,`status`),
  CONSTRAINT `business_followers_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_followers_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_followers`
--

LOCK TABLES `business_followers` WRITE;
/*!40000 ALTER TABLE `business_followers` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_followers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_invitations`
--

DROP TABLE IF EXISTS `business_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `inviter_id` bigint(20) unsigned NOT NULL,
  `invitee_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'editor',
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, accepted, rejected, cancelled, expired',
  `invite_code` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_invitations_inviter_id_foreign` (`inviter_id`),
  KEY `business_invitations_business_page_id_status_index` (`business_page_id`,`status`),
  KEY `business_invitations_invitee_id_status_index` (`invitee_id`,`status`),
  CONSTRAINT `business_invitations_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_invitations_invitee_id_foreign` FOREIGN KEY (`invitee_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_invitations`
--

LOCK TABLES `business_invitations` WRITE;
/*!40000 ALTER TABLE `business_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_messages`
--

DROP TABLE IF EXISTS `business_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_conversation_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `sender_type` varchar(255) NOT NULL DEFAULT 'customer' COMMENT 'customer, business',
  `message` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_type` varchar(255) DEFAULT NULL COMMENT 'image, document, audio, video, location',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_messages_sender_id_foreign` (`sender_id`),
  KEY `business_messages_business_conversation_id_created_at_index` (`business_conversation_id`,`created_at`),
  KEY `business_messages_business_conversation_id_is_read_index` (`business_conversation_id`,`is_read`),
  CONSTRAINT `business_messages_business_conversation_id_foreign` FOREIGN KEY (`business_conversation_id`) REFERENCES `business_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_messages`
--

LOCK TABLES `business_messages` WRITE;
/*!40000 ALTER TABLE `business_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_notifications`
--

DROP TABLE IF EXISTS `business_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL COMMENT 'new_message, new_follower, new_review, new_team_invite, new_mention, new_comment',
  `title` varchar(255) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_notifications_business_page_id_is_read_index` (`business_page_id`,`is_read`),
  KEY `business_notifications_member_id_index` (`member_id`),
  CONSTRAINT `business_notifications_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_notifications_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_notifications`
--

LOCK TABLES `business_notifications` WRITE;
/*!40000 ALTER TABLE `business_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_page_categories`
--

DROP TABLE IF EXISTS `business_page_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_page_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_page_categories_name_unique` (`name`),
  UNIQUE KEY `business_page_categories_slug_unique` (`slug`),
  KEY `business_page_categories_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_page_categories`
--

LOCK TABLES `business_page_categories` WRITE;
/*!40000 ALTER TABLE `business_page_categories` DISABLE KEYS */;
INSERT INTO `business_page_categories` VALUES (1,'Binary MLM Plan','binary-mlm-plan',NULL,1,10,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(2,'Matrix MLM Plan','matrix-mlm-plan',NULL,1,11,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(3,'Unilevel MLM Plan','unilevel-mlm-plan',NULL,1,12,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(4,'Board / Revolving Matrix Plan','board-revolving-matrix-plan',NULL,1,13,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(5,'Generation / Level Plan','generation-level-plan',NULL,1,14,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(6,'Monoline / Single Leg Plan','monoline-single-leg-plan',NULL,1,15,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(7,'Stair-Step Breakaway Plan','stair-step-breakaway-plan',NULL,1,16,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(8,'Crowdfunding / Helping MLM Plan','crowdfunding-helping-mlm-plan',NULL,1,17,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(9,'Spillover / Auto-Pool Plan','spillover-auto-pool-plan',NULL,1,18,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(10,'Gift / Donation MLM Plan','gift-donation-mlm-plan',NULL,1,19,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(11,'Health, Nutrition & Wellness MLM','health-nutrition-wellness-mlm',NULL,1,20,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(12,'Cosmetics & Personal Care MLM','cosmetics-personal-care-mlm',NULL,1,21,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(13,'Crypto, Forex & FinTech MLM','crypto-forex-fintech-mlm',NULL,1,22,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(14,'E-Commerce & Affiliate MLM','e-commerce-affiliate-mlm',NULL,1,23,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(15,'Real Estate & Investment MLM','real-estate-investment-mlm',NULL,1,24,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(16,'Digital Services & EdTech MLM','digital-services-edtech-mlm',NULL,1,25,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(17,'Travel & Hospitality MLM','travel-hospitality-mlm',NULL,1,26,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(18,'MLM Software & App Solutions','mlm-software-app-solutions',NULL,1,27,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(19,'MLM Legal & Compliance Consultancy','mlm-legal-compliance-consultancy',NULL,1,28,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(20,'MLM Lead Generation & Marketing','mlm-lead-generation-marketing',NULL,1,29,'2026-09-28 05:22:50','2026-09-28 05:22:50'),(21,'Top Leaders & Networker Profiles','top-leaders-networker-profiles',NULL,1,30,'2026-09-28 05:22:50','2026-09-28 05:22:50');
/*!40000 ALTER TABLE `business_page_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_pages`
--

DROP TABLE IF EXISTS `business_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` varchar(255) DEFAULT NULL COMMENT 'Unique string ID e.g. biz_xxxx',
  `member_id` bigint(20) unsigned NOT NULL,
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
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_pages_page_username_unique` (`page_username`),
  UNIQUE KEY `business_pages_slug_unique` (`slug`),
  UNIQUE KEY `business_pages_page_id_unique` (`page_id`),
  KEY `business_pages_member_id_foreign` (`member_id`),
  KEY `business_pages_category_index` (`category`),
  KEY `business_pages_visibility_index` (`visibility`),
  KEY `business_pages_status_index` (`status`),
  KEY `business_pages_is_featured_index` (`is_featured`),
  KEY `business_pages_trending_score_index` (`trending_score`),
  CONSTRAINT `business_pages_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_pages`
--

LOCK TABLES `business_pages` WRITE;
/*!40000 ALTER TABLE `business_pages` DISABLE KEYS */;
INSERT INTO `business_pages` VALUES (1,'biz_wBhmso3lKH',13,'Digi Tech Solutions Pvt Ltd','digi','digi-tech-solutions-pvt-ltd','Binary MLM Plan','Digi Tech Solutions Pvt. Ltd. is a professional software development company focused on delivering innovative, scalable, and business-oriented technology solutions. We specialize in developing a wide range of software solutions tailored to meet the unique requirements of businesses across different industries.','https://digitech.com','digitech155@gmail.com','+919917558069','India','Uttar Pradesh','Meerut','New Nagar','uploads/business_pages/logos/cd22234d-8dc2-4008-a0a9-5b8aecf03c0e.png','uploads/business_pages/covers/328da8b1-d55a-49af-b60a-b5a7f3bde91c.png','public','active',0,0,NULL,NULL,NULL,NULL,0,'2026-09-28 06:59:48','2026-09-28 06:59:48',NULL),(2,'biz_HnsWYhQXFS',1,'MLM Staking','mlm_staking','mlm-staking','Binary MLM Plan','gthgtyhythrhrhtrhrththtrhtrrt',NULL,'tamishpratap1713@gmail.com','+918979703005','India',NULL,NULL,NULL,NULL,NULL,'public','active',0,0,NULL,NULL,NULL,NULL,0,'2026-09-28 13:36:44','2026-09-28 13:36:44',NULL);
/*!40000 ALTER TABLE `business_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_quick_replies`
--

DROP TABLE IF EXISTS `business_quick_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_quick_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `shortcut` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_quick_replies_business_page_id_index` (`business_page_id`),
  CONSTRAINT `business_quick_replies_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_quick_replies`
--

LOCK TABLES `business_quick_replies` WRITE;
/*!40000 ALTER TABLE `business_quick_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_quick_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_review_replies`
--

DROP TABLE IF EXISTS `business_review_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_review_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_review_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `reply` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_review_replies_business_review_id_unique` (`business_review_id`),
  KEY `business_review_replies_member_id_foreign` (`member_id`),
  CONSTRAINT `business_review_replies_business_review_id_foreign` FOREIGN KEY (`business_review_id`) REFERENCES `business_reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_review_replies_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_review_replies`
--

LOCK TABLES `business_review_replies` WRITE;
/*!40000 ALTER TABLE `business_review_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_review_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_review_reports`
--

DROP TABLE IF EXISTS `business_review_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_review_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_review_id` bigint(20) unsigned NOT NULL,
  `reporter_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL COMMENT 'spam, fake_review, harassment, offensive, other',
  `details` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, reviewed, dismissed',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_review_reports_reporter_id_foreign` (`reporter_id`),
  KEY `business_review_reports_business_review_id_status_index` (`business_review_id`,`status`),
  CONSTRAINT `business_review_reports_business_review_id_foreign` FOREIGN KEY (`business_review_id`) REFERENCES `business_reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_review_reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_review_reports`
--

LOCK TABLES `business_review_reports` WRITE;
/*!40000 ALTER TABLE `business_review_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_review_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_review_votes`
--

DROP TABLE IF EXISTS `business_review_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_review_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_review_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `vote_type` varchar(255) NOT NULL DEFAULT 'helpful' COMMENT 'helpful, unhelpful',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_review_votes_business_review_id_member_id_unique` (`business_review_id`,`member_id`),
  KEY `business_review_votes_member_id_foreign` (`member_id`),
  CONSTRAINT `business_review_votes_business_review_id_foreign` FOREIGN KEY (`business_review_id`) REFERENCES `business_reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_review_votes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_review_votes`
--

LOCK TABLES `business_review_votes` WRITE;
/*!40000 ALTER TABLE `business_review_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_review_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_reviews`
--

DROP TABLE IF EXISTS `business_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT 5 COMMENT '1 to 5 stars',
  `recommendation` varchar(255) NOT NULL DEFAULT 'recommend' COMMENT 'recommend, not_recommend',
  `title` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_count` int(10) unsigned NOT NULL DEFAULT 0,
  `unhelpful_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_reviews_business_page_id_member_id_unique` (`business_page_id`,`member_id`),
  KEY `business_reviews_member_id_foreign` (`member_id`),
  KEY `business_reviews_business_page_id_rating_index` (`business_page_id`,`rating`),
  KEY `business_reviews_business_page_id_is_hidden_index` (`business_page_id`,`is_hidden`),
  CONSTRAINT `business_reviews_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_reviews_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_reviews`
--

LOCK TABLES `business_reviews` WRITE;
/*!40000 ALTER TABLE `business_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_team_members`
--

DROP TABLE IF EXISTS `business_team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_team_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'editor' COMMENT 'owner, admin, editor, moderator, analyst',
  `status` varchar(255) NOT NULL DEFAULT 'active' COMMENT 'active, suspended',
  `joined_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_team_members_business_page_id_member_id_unique` (`business_page_id`,`member_id`),
  KEY `business_team_members_business_page_id_role_index` (`business_page_id`,`role`),
  KEY `business_team_members_member_id_status_index` (`member_id`,`status`),
  CONSTRAINT `business_team_members_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_team_members_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_team_members`
--

LOCK TABLES `business_team_members` WRITE;
/*!40000 ALTER TABLE `business_team_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_team_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_verifications`
--

DROP TABLE IF EXISTS `business_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_page_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'pending, verified, rejected, expired',
  `document_type` varchar(255) NOT NULL COMMENT 'business_registration, gst, license, govt_id, other',
  `document_number` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) NOT NULL,
  `admin_notes` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_verifications_member_id_foreign` (`member_id`),
  KEY `business_verifications_business_page_id_status_index` (`business_page_id`,`status`),
  CONSTRAINT `business_verifications_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_verifications_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_verifications`
--

LOCK TABLES `business_verifications` WRITE;
/*!40000 ALTER TABLE `business_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('mlm-book-cache-4a019e67a4a66185c205f3180a921ab7df299ca7','i:1;',1790579145),('mlm-book-cache-4a019e67a4a66185c205f3180a921ab7df299ca7:timer','i:1790579145;',1790579145),('mlm-book-cache-5e807f2c86bd7c0e8c8dc7014a0452bcf089242a','i:2;',1790578074),('mlm-book-cache-5e807f2c86bd7c0e8c8dc7014a0452bcf089242a:timer','i:1790578074;',1790578074),('mlm-book-cache-ad_reward_rules_active','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1790589688),('mlm-book-cache-central_reward_rules_active','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1790589149),('mlm-book-cache-event_reward_rules_active','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1790589688),('mlm-book-cache-member-register-request:2405:201:6821:5810:c53b:d274:55f2:9935','i:1;',1790578101),('mlm-book-cache-member-register-request:2405:201:6821:5810:c53b:d274:55f2:9935:timer','i:1790578101;',1790578101),('mlm-book-cache-member-verification-hi-request:13','i:1;',1790578178),('mlm-book-cache-member-verification-hi-request:13:timer','i:1790578178;',1790578178),('mlm-book-cache-member-verification-hi-request:14','i:1;',1790579518),('mlm-book-cache-member-verification-hi-request:14:timer','i:1790579518;',1790579518),('mlm-book-cache-reward_rank_rules_active','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:5:{i:0;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:1;s:8:\"rank_key\";s:10:\"advertiser\";s:9:\"rank_name\";s:10:\"Advertiser\";s:8:\"priority\";i:1;s:20:\"referral_requirement\";i:0;s:16:\"team_requirement\";i:0;s:13:\"reward_amount\";s:6:\"0.0250\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";i:24;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:53:40\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:1;s:8:\"rank_key\";s:10:\"advertiser\";s:9:\"rank_name\";s:10:\"Advertiser\";s:8:\"priority\";i:1;s:20:\"referral_requirement\";i:0;s:16:\"team_requirement\";i:0;s:13:\"reward_amount\";s:6:\"0.0250\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";i:24;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:53:40\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:2;s:8:\"rank_key\";s:10:\"influencer\";s:9:\"rank_name\";s:10:\"Influencer\";s:8:\"priority\";i:2;s:20:\"referral_requirement\";i:10;s:16:\"team_requirement\";i:0;s:13:\"reward_amount\";s:6:\"0.1000\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";i:24;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:54:27\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:2;s:8:\"rank_key\";s:10:\"influencer\";s:9:\"rank_name\";s:10:\"Influencer\";s:8:\"priority\";i:2;s:20:\"referral_requirement\";i:10;s:16:\"team_requirement\";i:0;s:13:\"reward_amount\";s:6:\"0.1000\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";i:24;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:54:27\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:3;s:8:\"rank_key\";s:7:\"leaders\";s:9:\"rank_name\";s:7:\"Leaders\";s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:15;s:16:\"team_requirement\";i:50;s:13:\"reward_amount\";s:6:\"0.0500\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:3;s:8:\"rank_key\";s:7:\"leaders\";s:9:\"rank_name\";s:7:\"Leaders\";s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:15;s:16:\"team_requirement\";i:50;s:13:\"reward_amount\";s:6:\"0.0500\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:4;s:8:\"rank_key\";s:11:\"pro_leaders\";s:9:\"rank_name\";s:11:\"Pro Leaders\";s:8:\"priority\";i:4;s:20:\"referral_requirement\";i:30;s:16:\"team_requirement\";i:150;s:13:\"reward_amount\";s:6:\"0.0750\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:4;s:8:\"rank_key\";s:11:\"pro_leaders\";s:9:\"rank_name\";s:11:\"Pro Leaders\";s:8:\"priority\";i:4;s:20:\"referral_requirement\";i:30;s:16:\"team_requirement\";i:150;s:13:\"reward_amount\";s:6:\"0.0750\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:4;O:25:\"App\\Models\\RewardRankRule\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"reward_rank_rules\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:12:{s:2:\"id\";i:5;s:8:\"rank_key\";s:14:\"master_leaders\";s:9:\"rank_name\";s:14:\"Master Leaders\";s:8:\"priority\";i:5;s:20:\"referral_requirement\";i:50;s:16:\"team_requirement\";i:500;s:13:\"reward_amount\";s:6:\"0.1000\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:11:\"\0*\0original\";a:12:{s:2:\"id\";i:5;s:8:\"rank_key\";s:14:\"master_leaders\";s:9:\"rank_name\";s:14:\"Master Leaders\";s:8:\"priority\";i:5;s:20:\"referral_requirement\";i:50;s:16:\"team_requirement\";i:500;s:13:\"reward_amount\";s:6:\"0.1000\";s:9:\"is_active\";i:1;s:10:\"created_by\";N;s:10:\"updated_by\";N;s:10:\"created_at\";s:19:\"2026-09-28 12:50:55\";s:10:\"updated_at\";s:19:\"2026-09-28 12:50:55\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:7:{s:8:\"priority\";s:7:\"integer\";s:20:\"referral_requirement\";s:7:\"integer\";s:16:\"team_requirement\";s:7:\"integer\";s:13:\"reward_amount\";s:9:\"decimal:4\";s:9:\"is_active\";s:7:\"boolean\";s:10:\"created_at\";s:8:\"datetime\";s:10:\"updated_at\";s:8:\"datetime\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:9:{i:0;s:8:\"rank_key\";i:1;s:9:\"rank_name\";i:2;s:8:\"priority\";i:3;s:20:\"referral_requirement\";i:4;s:16:\"team_requirement\";i:5;s:13:\"reward_amount\";i:6;s:9:\"is_active\";i:7;s:10:\"created_by\";i:8;s:10:\"updated_by\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1790589333);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comment_reactions`
--

DROP TABLE IF EXISTS `comment_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment_reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comment_reactions_comment_member_unique` (`comment_id`,`member_id`),
  KEY `comment_reactions_comment_id_index` (`comment_id`),
  KEY `comment_reactions_member_id_index` (`member_id`),
  KEY `comment_reactions_reaction_index` (`reaction`),
  CONSTRAINT `comment_reactions_comment_id_foreign` FOREIGN KEY (`comment_id`) REFERENCES `post_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comment_reactions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment_reactions`
--

LOCK TABLES `comment_reactions` WRITE;
/*!40000 ALTER TABLE `comment_reactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `comment_reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communities`
--

DROP TABLE IF EXISTS `communities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` varchar(255) NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'Technology',
  `visibility` varchar(255) NOT NULL DEFAULT 'public',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `trending_score` int(10) unsigned NOT NULL DEFAULT 0,
  `posting_permissions` varchar(255) NOT NULL DEFAULT 'everyone',
  `join_approval_mode` varchar(255) NOT NULL DEFAULT 'instant',
  `cover_photo` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `invite_code` varchar(255) DEFAULT NULL,
  `member_count` int(10) unsigned NOT NULL DEFAULT 1,
  `post_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communities_community_id_unique` (`community_id`),
  UNIQUE KEY `communities_slug_unique` (`slug`),
  UNIQUE KEY `communities_invite_code_unique` (`invite_code`),
  KEY `communities_visibility_category_index` (`visibility`,`category`),
  KEY `communities_owner_id_status_index` (`owner_id`,`status`),
  KEY `communities_visibility_is_featured_member_count_index` (`visibility`,`is_featured`,`member_count`),
  CONSTRAINT `communities_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communities`
--

LOCK TABLES `communities` WRITE;
/*!40000 ALTER TABLE `communities` DISABLE KEYS */;
/*!40000 ALTER TABLE `communities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_audit_logs`
--

DROP TABLE IF EXISTS `community_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) unsigned NOT NULL,
  `actor_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(255) DEFAULT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_audit_logs_actor_id_foreign` (`actor_id`),
  KEY `community_audit_logs_community_id_created_at_index` (`community_id`,`created_at`),
  CONSTRAINT `community_audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_audit_logs_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_audit_logs`
--

LOCK TABLES `community_audit_logs` WRITE;
/*!40000 ALTER TABLE `community_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_bans`
--

DROP TABLE IF EXISTS `community_bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_bans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `banned_by_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_permanent` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_bans_community_id_member_id_unique` (`community_id`,`member_id`),
  KEY `community_bans_member_id_foreign` (`member_id`),
  KEY `community_bans_banned_by_id_foreign` (`banned_by_id`),
  CONSTRAINT `community_bans_banned_by_id_foreign` FOREIGN KEY (`banned_by_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_bans_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_bans_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_bans`
--

LOCK TABLES `community_bans` WRITE;
/*!40000 ALTER TABLE `community_bans` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_bans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_invitations`
--

DROP TABLE IF EXISTS `community_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) unsigned NOT NULL,
  `inviter_id` bigint(20) unsigned NOT NULL,
  `invitee_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `invite_code` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `max_uses` int(10) unsigned DEFAULT NULL,
  `use_count` int(10) unsigned NOT NULL DEFAULT 0,
  `type` varchar(255) NOT NULL DEFAULT 'unlimited',
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0,
  `source` varchar(255) NOT NULL DEFAULT 'direct',
  `qr_data` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_invitations_invite_code_unique` (`invite_code`),
  KEY `community_invitations_invitee_id_foreign` (`invitee_id`),
  KEY `community_invitations_community_id_status_index` (`community_id`,`status`),
  KEY `community_invitations_inviter_id_index` (`inviter_id`),
  KEY `community_invitations_invite_code_is_revoked_index` (`invite_code`,`is_revoked`),
  CONSTRAINT `community_invitations_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_invitations_invitee_id_foreign` FOREIGN KEY (`invitee_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_invitations`
--

LOCK TABLES `community_invitations` WRITE;
/*!40000 ALTER TABLE `community_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_members`
--

DROP TABLE IF EXISTS `community_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'member',
  `status` varchar(255) NOT NULL DEFAULT 'accepted',
  `notification_level` varchar(255) NOT NULL DEFAULT 'all',
  `muted_until` datetime DEFAULT NULL,
  `joined_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_members_community_id_member_id_unique` (`community_id`,`member_id`),
  KEY `community_members_community_id_status_index` (`community_id`,`status`),
  KEY `community_members_member_id_status_index` (`member_id`,`status`),
  KEY `community_members_community_id_role_index` (`community_id`,`role`),
  CONSTRAINT `community_members_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_members_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_members`
--

LOCK TABLES `community_members` WRITE;
/*!40000 ALTER TABLE `community_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_mutes`
--

DROP TABLE IF EXISTS `community_mutes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_mutes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `muted_by_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_mutes_community_id_member_id_unique` (`community_id`,`member_id`),
  KEY `community_mutes_member_id_foreign` (`member_id`),
  KEY `community_mutes_muted_by_id_foreign` (`muted_by_id`),
  CONSTRAINT `community_mutes_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_mutes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_mutes_muted_by_id_foreign` FOREIGN KEY (`muted_by_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_mutes`
--

LOCK TABLES `community_mutes` WRITE;
/*!40000 ALTER TABLE `community_mutes` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_mutes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_reports`
--

DROP TABLE IF EXISTS `community_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) unsigned NOT NULL,
  `reporter_id` bigint(20) unsigned NOT NULL,
  `reportable_type` varchar(255) NOT NULL,
  `reportable_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `resolved_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_reports_reporter_id_foreign` (`reporter_id`),
  KEY `community_reports_resolved_by_id_foreign` (`resolved_by_id`),
  KEY `community_reports_community_id_status_index` (`community_id`,`status`),
  CONSTRAINT `community_reports_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_reports_resolved_by_id_foreign` FOREIGN KEY (`resolved_by_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_reports`
--

LOCK TABLES `community_reports` WRITE;
/*!40000 ALTER TABLE `community_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_warnings`
--

DROP TABLE IF EXISTS `community_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_warnings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `warned_by_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `community_warnings_member_id_foreign` (`member_id`),
  KEY `community_warnings_warned_by_id_foreign` (`warned_by_id`),
  KEY `community_warnings_community_id_member_id_index` (`community_id`,`member_id`),
  CONSTRAINT `community_warnings_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_warnings_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `community_warnings_warned_by_id_foreign` FOREIGN KEY (`warned_by_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_warnings`
--

LOCK TABLES `community_warnings` WRITE;
/*!40000 ALTER TABLE `community_warnings` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_warnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `direct_messages`
--

DROP TABLE IF EXISTS `direct_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `direct_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `direct_messages_sender_id_receiver_id_index` (`sender_id`,`receiver_id`),
  KEY `direct_messages_receiver_id_is_read_index` (`receiver_id`,`is_read`),
  CONSTRAINT `direct_messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `direct_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direct_messages`
--

LOCK TABLES `direct_messages` WRITE;
/*!40000 ALTER TABLE `direct_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `direct_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_invitations`
--

DROP TABLE IF EXISTS `event_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `inviter_id` bigint(20) unsigned NOT NULL,
  `invited_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_invitations_event_id_invited_id_unique` (`event_id`,`invited_id`),
  KEY `event_invitations_inviter_id_foreign` (`inviter_id`),
  KEY `event_invitations_invited_id_foreign` (`invited_id`),
  CONSTRAINT `event_invitations_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_invitations_invited_id_foreign` FOREIGN KEY (`invited_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_invitations`
--

LOCK TABLES `event_invitations` WRITE;
/*!40000 ALTER TABLE `event_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_responses`
--

DROP TABLE IF EXISTS `event_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_responses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `response` varchar(255) NOT NULL DEFAULT 'going',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_responses_event_id_member_id_unique` (`event_id`,`member_id`),
  KEY `event_responses_member_id_foreign` (`member_id`),
  KEY `event_responses_event_id_response_index` (`event_id`,`response`),
  CONSTRAINT `event_responses_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_responses_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_responses`
--

LOCK TABLES `event_responses` WRITE;
/*!40000 ALTER TABLE `event_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organizer_id` bigint(20) unsigned NOT NULL,
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
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_slug_unique` (`slug`),
  KEY `events_organizer_id_foreign` (`organizer_id`),
  KEY `events_start_date_event_type_privacy_index` (`start_date`,`event_type`,`privacy`),
  CONSTRAINT `events_organizer_id_foreign` FOREIGN KEY (`organizer_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback_suggestions`
--

DROP TABLE IF EXISTS `feedback_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'feedback',
  `subject` varchar(191) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `admin_response` text DEFAULT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_suggestions_admin_id_foreign` (`admin_id`),
  KEY `feedback_suggestions_member_id_index` (`member_id`),
  KEY `feedback_suggestions_type_index` (`type`),
  KEY `feedback_suggestions_status_index` (`status`),
  KEY `feedback_suggestions_created_at_index` (`created_at`),
  CONSTRAINT `feedback_suggestions_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feedback_suggestions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_suggestions`
--

LOCK TABLES `feedback_suggestions` WRITE;
/*!40000 ALTER TABLE `feedback_suggestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_suggestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `followers`
--

DROP TABLE IF EXISTS `followers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `followers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `follower_id` bigint(20) unsigned NOT NULL,
  `following_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'accepted',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `followers_follower_id_following_id_unique` (`follower_id`,`following_id`),
  KEY `followers_following_id_status_index` (`following_id`,`status`),
  CONSTRAINT `followers_follower_id_foreign` FOREIGN KEY (`follower_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `followers_following_id_foreign` FOREIGN KEY (`following_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `followers`
--

LOCK TABLES `followers` WRITE;
/*!40000 ALTER TABLE `followers` DISABLE KEYS */;
/*!40000 ALTER TABLE `followers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `friendships`
--

DROP TABLE IF EXISTS `friendships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `friendships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_one_id` bigint(20) unsigned NOT NULL,
  `member_two_id` bigint(20) unsigned NOT NULL,
  `requested_by_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `accepted_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `friendships_unique_member_pair` (`member_one_id`,`member_two_id`),
  KEY `friendships_member_one_index` (`member_one_id`),
  KEY `friendships_member_two_index` (`member_two_id`),
  KEY `friendships_requested_by_index` (`requested_by_id`),
  KEY `friendships_status_index` (`status`),
  KEY `idx_friendships_m1_status` (`member_one_id`,`status`),
  KEY `idx_friendships_m2_status` (`member_two_id`,`status`),
  CONSTRAINT `friendships_member_one_id_foreign` FOREIGN KEY (`member_one_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `friendships_member_two_id_foreign` FOREIGN KEY (`member_two_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `friendships_requested_by_id_foreign` FOREIGN KEY (`requested_by_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friendships`
--

LOCK TABLES `friendships` WRITE;
/*!40000 ALTER TABLE `friendships` DISABLE KEYS */;
INSERT INTO `friendships` VALUES (1,1,13,1,'pending',NULL,NULL,'2026-09-28 12:42:59','2026-09-28 12:42:59');
/*!40000 ALTER TABLE `friendships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_invitations`
--

DROP TABLE IF EXISTS `group_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `inviter_id` bigint(20) unsigned NOT NULL,
  `invited_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_invitations_group_id_invited_id_unique` (`group_id`,`invited_id`),
  KEY `group_invitations_inviter_id_foreign` (`inviter_id`),
  KEY `group_invitations_invited_id_foreign` (`invited_id`),
  CONSTRAINT `group_invitations_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_invitations_invited_id_foreign` FOREIGN KEY (`invited_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_invitations_inviter_id_foreign` FOREIGN KEY (`inviter_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_invitations`
--

LOCK TABLES `group_invitations` WRITE;
/*!40000 ALTER TABLE `group_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_members`
--

DROP TABLE IF EXISTS `group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'member',
  `status` varchar(255) NOT NULL DEFAULT 'accepted',
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_members_group_id_member_id_unique` (`group_id`,`member_id`),
  KEY `group_members_member_id_foreign` (`member_id`),
  KEY `group_members_group_id_status_index` (`group_id`,`status`),
  CONSTRAINT `group_members_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_members_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_members`
--

LOCK TABLES `group_members` WRITE;
/*!40000 ALTER TABLE `group_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) unsigned NOT NULL,
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
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groups_slug_unique` (`slug`),
  KEY `groups_owner_id_foreign` (`owner_id`),
  KEY `groups_privacy_category_index` (`privacy`,`category`),
  CONSTRAINT `groups_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hidden_posts`
--

DROP TABLE IF EXISTS `hidden_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hidden_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `hidden_posts_member_post_unique` (`member_id`,`post_id`),
  KEY `hidden_posts_member_id_index` (`member_id`),
  KEY `hidden_posts_post_id_index` (`post_id`),
  CONSTRAINT `hidden_posts_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hidden_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hidden_posts`
--

LOCK TABLES `hidden_posts` WRITE;
/*!40000 ALTER TABLE `hidden_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `hidden_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `import_fund`
--

DROP TABLE IF EXISTS `import_fund`;
/*!50001 DROP VIEW IF EXISTS `import_fund`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `import_fund` AS SELECT
 1 AS `id`,
  1 AS `memberid`,
  1 AS `user_id`,
  1 AS `member_id`,
  1 AS `txnid`,
  1 AS `transaction_hash`,
  1 AS `orderid`,
  1 AS `amount`,
  1 AS `type`,
  1 AS `wallet_type`,
  1 AS `wallet_address`,
  1 AS `network`,
  1 AS `token`,
  1 AS `contract_address`,
  1 AS `added_by`,
  1 AS `status`,
  1 AS `verification_status`,
  1 AS `deposit_status`,
  1 AS `verification_payload`,
  1 AS `admin_notes`,
  1 AS `rejection_reason`,
  1 AS `verified_at`,
  1 AS `verified_by`,
  1 AS `mode`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `import_funds`
--

DROP TABLE IF EXISTS `import_funds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_funds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `memberid` varchar(50) DEFAULT NULL,
  `user_id` varchar(30) DEFAULT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
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
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `mode` varchar(50) NOT NULL DEFAULT 'dapp_web3',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_funds_memberid_index` (`memberid`),
  KEY `import_funds_user_id_index` (`user_id`),
  KEY `import_funds_member_id_index` (`member_id`),
  KEY `import_funds_txnid_index` (`txnid`),
  KEY `import_funds_transaction_hash_index` (`transaction_hash`),
  KEY `import_funds_orderid_index` (`orderid`),
  KEY `import_funds_status_index` (`status`),
  KEY `import_funds_verification_status_index` (`verification_status`),
  KEY `import_funds_deposit_status_index` (`deposit_status`),
  KEY `import_funds_verified_by_index` (`verified_by`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_funds`
--

LOCK TABLES `import_funds` WRITE;
/*!40000 ALTER TABLE `import_funds` DISABLE KEYS */;
INSERT INTO `import_funds` VALUES (3,'shub648025','shub648025',13,'0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066','0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066','DEP-WSJFTY08II',5000.0000,'Add','USDT','0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c','BEP-20','USDT','0x55d398326f99059fF775485246999027B3197955','User','Approved','verified','approved','{\"verified\":true,\"status\":\"verified\",\"tx_hash\":\"0x7e57a4437dd642821e420eac2ad2ed051e53270e963d5f841961f781ffc7b066\",\"from_address\":\"0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c\",\"to_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"transferred_amount\":5500,\"submitted_amount\":5500,\"token\":\"USDT\",\"network\":\"BEP-20\",\"contract_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"block_number\":38920145,\"confirmations\":12,\"verification_source\":\"bsc_rpc\",\"message\":\"Successfully verified 5500 USDT transfer on BNB Smart Chain.\"}','Web3 DApp Instant Deposit: Auto-approved on-chain. 5500 USDT from 0x2ae3d1127eee12e2b53102be8cdc0e4887070a0c. Net: $5000 USD credited directly to Fund Wallet.',NULL,'2026-09-28 12:20:02',NULL,'Online','2026-09-28 12:20:02','2026-09-28 12:20:02'),(4,'tami383474','tami383474',1,'0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3','0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3','DEP-RT2EY7RBAS',50.0000,'Add','USDT','0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584','BEP-20','USDT','0x55d398326f99059fF775485246999027B3197955','User','Approved','verified','approved','{\"verified\":true,\"status\":\"verified\",\"tx_hash\":\"0x7e57653e3a9efa5c356d2ead929a1ae2e7d03be3524d8a1e8ca834936985f5b3\",\"from_address\":\"0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584\",\"to_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"transferred_amount\":55,\"submitted_amount\":55,\"token\":\"USDT\",\"network\":\"BEP-20\",\"contract_address\":\"0x55d398326f99059fF775485246999027B3197955\",\"block_number\":38920145,\"confirmations\":12,\"verification_source\":\"bsc_rpc\",\"message\":\"Successfully verified 55 USDT transfer on BNB Smart Chain.\"}','Web3 DApp Instant Deposit: Auto-approved on-chain. 55 USDT from 0x1908b2adb7c5eb87ac27c620499d9cc1ceedf584. Net: $50 USD credited directly to Fund Wallet.',NULL,'2026-09-28 14:31:32',NULL,'Online','2026-09-28 14:31:32','2026-09-28 14:31:32');
/*!40000 ALTER TABLE `import_funds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_verification_otps`
--

DROP TABLE IF EXISTS `member_verification_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_verification_otps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `pending_value` varchar(255) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_verification_otps_member_id_purpose_index` (`member_id`,`purpose`),
  KEY `member_verification_otps_purpose_index` (`purpose`),
  KEY `member_verification_otps_expires_at_index` (`expires_at`),
  CONSTRAINT `member_verification_otps_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_verification_otps`
--

LOCK TABLES `member_verification_otps` WRITE;
/*!40000 ALTER TABLE `member_verification_otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_verification_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `introducer_id` varchar(30) DEFAULT NULL,
  `direct_referral_count` int(10) unsigned NOT NULL DEFAULT 0,
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
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `members_email_unique` (`email`),
  UNIQUE KEY `members_user_id_unique` (`user_id`),
  UNIQUE KEY `members_google_id_unique` (`google_id`),
  UNIQUE KEY `members_phone_unique` (`phone`),
  KEY `members_blocked_at_index` (`blocked_at`),
  KEY `members_introducer_id_index` (`introducer_id`),
  KEY `members_direct_referral_count_index` (`direct_referral_count`),
  KEY `members_referral_counted_at_index` (`referral_counted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` VALUES (1,'Tamish Pratap Singh','tami383474',NULL,0,NULL,'tamishpratap1713@gmail.com','$2y$12$gObQ7sn8nNkMrvGdMHvzke356pfzpn8y8K.0cuJGUqrDoWl7Xn9P2',NULL,'111948971301762916699','+918979703005','2026-09-28 12:17:37','2026-09-28 10:56:22',50.00,0.0000,NULL,'Advertiser','BEP-20','USDT',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'https://lh3.googleusercontent.com/a/ACg8ocJL7nXn1I2G7m3_2aDEOnOK6LttiLaya0PrDNY-loD-1zlBXckMyg=s96-c',NULL,'2026-09-28 14:33:40',NULL,'2026-09-28 10:52:23','2026-09-28 14:33:40'),(13,'Shubham Verma','shub648025',NULL,0,NULL,'shubverma155@gmail.com','$2y$12$rko1Loq.maXWga.yLytTv.XHXUuRbGi8LqjUqOx20EH7Y4AwurGra',NULL,NULL,'+919917558069','2026-09-28 12:18:52','2026-09-28 12:18:38',4948.75,0.0000,NULL,'Advertiser','BEP-20','USDT',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-28 14:28:07',NULL,'2026-09-28 12:17:57','2026-09-28 14:28:07'),(14,'Anand Kashyap','anan918051',NULL,0,NULL,'anandkashyapassociates@gmail.com','$2y$12$pe0rMJdwxrAcQ1EvRIZvoe3L0zK8bIqbKZ7dJoMbVGLdb.hzoLHG2',NULL,'117536838539675557905','+919997078388','2026-09-28 12:41:27','2026-09-28 12:40:58',0.00,0.0000,NULL,'Advertiser','BEP-20','USDT',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'https://lh3.googleusercontent.com/a/ACg8ocL8xhgRjACtwR7tVDBX2hZxlA-U_sGNbMw8zMD743Wh8rYIUg=s96-c',NULL,'2026-09-28 13:26:08',NULL,'2026-09-28 12:35:12','2026-09-28 13:26:08');
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_09_28_120000_remove_on_update_current_timestamp_from_expiries',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `idx_notifications_notifiable_read` (`notifiable_type`,`notifiable_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('0c9c9c5a-32c1-4258-9228-0680e84f2357','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"New Member Registered\",\"message\":\"Anand Kashyap (anan918051) has registered via Google.\",\"body\":\"Anand Kashyap (anan918051) has registered via Google.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"14\",\"action_url\":\"\\/admin\\/members\\/14\",\"metadata\":{\"member_id\":14,\"user_id\":\"anan918051\",\"phone\":\"+919997078388\"}}',NULL,'2026-09-28 12:35:12','2026-09-28 12:35:12'),('22ae8998-7031-4e94-a10c-587945086c69','App\\Notifications\\PostReactionNotification','App\\Models\\Member',13,'{\"title\":\"Post reaction\",\"message\":\"Anand Kashyap reacted \\ud83d\\udc4d to your post.\",\"actor_id\":14,\"actor_name\":\"Anand Kashyap\",\"actor_photo\":\"https:\\/\\/lh3.googleusercontent.com\\/a\\/ACg8ocL8xhgRjACtwR7tVDBX2hZxlA-U_sGNbMw8zMD743Wh8rYIUg=s96-c\",\"reference_type\":\"post\",\"reference_id\":\"7\",\"icon\":\"thumbs-up\",\"category\":\"posts\",\"url\":\"https:\\/\\/mlmbookai.com\\/backend\\/public\\/member\\/posts\\/7\"}','2026-09-28 14:25:45','2026-09-28 12:46:58','2026-09-28 14:25:45'),('2e38ec4f-9ce8-46bc-94f3-4357944e2d8e','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"New Ad Campaign Created\",\"message\":\"Campaign \\\"Digi Tech Solutions Pvt Ltd Campaign - 9\\/28\\/2026\\\" was created for \\\"Digi Tech Solutions Pvt Ltd\\\" and submitted for review.\",\"body\":\"Campaign \\\"Digi Tech Solutions Pvt Ltd Campaign - 9\\/28\\/2026\\\" was created for \\\"Digi Tech Solutions Pvt Ltd\\\" and submitted for review.\",\"icon\":\"megaphone\",\"source_type\":\"ad_campaign\",\"source_id\":\"3\",\"action_url\":\"\\/admin\\/ad-campaigns\",\"metadata\":{\"campaign_id\":3,\"business_page_id\":1}}',NULL,'2026-09-28 12:44:20','2026-09-28 12:44:20'),('302a8eb3-775b-4957-af1a-143bab3f36b2','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"New Business Page Registered\",\"message\":\"\\\"Digi Tech Solutions Pvt Ltd\\\" was registered by Shubham Verma.\",\"body\":\"\\\"Digi Tech Solutions Pvt Ltd\\\" was registered by Shubham Verma.\",\"icon\":\"building\",\"source_type\":\"business_page\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/business-pages\",\"metadata\":{\"page_id\":1,\"page_name\":\"Digi Tech Solutions Pvt Ltd\"}}','2026-09-28 12:33:38','2026-09-28 12:29:48','2026-09-28 12:33:38'),('334ce3bd-04ec-4786-a79b-6c6b0221abd9','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"New Member Registered\",\"message\":\"Shubham Verma (shub648025) has registered on MLM Book.\",\"body\":\"Shubham Verma (shub648025) has registered on MLM Book.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"13\",\"action_url\":\"\\/admin\\/members\\/13\",\"metadata\":{\"member_id\":13,\"user_id\":\"shub648025\"}}','2026-09-28 12:33:38','2026-09-28 12:17:57','2026-09-28 12:33:38'),('38ff13a1-803e-4f5b-b71e-07e1e072df91','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"New Business Page Registered\",\"message\":\"\\\"Digi Tech Solutions Pvt Ltd\\\" was registered by Shubham Verma.\",\"body\":\"\\\"Digi Tech Solutions Pvt Ltd\\\" was registered by Shubham Verma.\",\"icon\":\"building\",\"source_type\":\"business_page\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/business-pages\",\"metadata\":{\"page_id\":1,\"page_name\":\"Digi Tech Solutions Pvt Ltd\"}}',NULL,'2026-09-28 12:29:48','2026-09-28 12:29:48'),('40ec1412-c3fe-4584-9d26-260d5720be58','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Shubham Verma (shub648025) sent \\\"Hi\\\" from +9199******69 and requested verification.\",\"body\":\"Shubham Verma (shub648025) sent \\\"Hi\\\" from +9199******69 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"13\",\"action_url\":\"\\/admin\\/members\\/13\",\"metadata\":{\"member_id\":13,\"user_id\":\"shub648025\",\"phone\":\"+919917558069\",\"requested_at\":\"2026-09-28T12:18:38+05:30\"}}','2026-09-28 12:33:38','2026-09-28 12:18:38','2026-09-28 12:33:38'),('45991d12-18e5-4a28-a1da-beb803145215','App\\Notifications\\FriendRequestReceivedNotification','App\\Models\\Member',13,'{\"title\":\"New Connection request\",\"message\":\"Tamish Pratap Singh sent you a connection request.\",\"actor_id\":1,\"actor_name\":\"Tamish Pratap Singh\",\"actor_photo\":\"https:\\/\\/lh3.googleusercontent.com\\/a\\/ACg8ocJL7nXn1I2G7m3_2aDEOnOK6LttiLaya0PrDNY-loD-1zlBXckMyg=s96-c\",\"friendship_id\":1,\"icon\":\"user-plus\",\"url\":\"https:\\/\\/mlmbookai.com\\/backend\\/public\\/member\\/friend-requests\"}','2026-09-28 14:25:45','2026-09-28 12:42:59','2026-09-28 14:25:45'),('685b947d-302b-4ca5-aabf-c9efd25b8d3d','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"New Business Page Registered\",\"message\":\"\\\"MLM Staking\\\" was registered by Tamish Pratap Singh.\",\"body\":\"\\\"MLM Staking\\\" was registered by Tamish Pratap Singh.\",\"icon\":\"building\",\"source_type\":\"business_page\",\"source_id\":\"2\",\"action_url\":\"\\/admin\\/business-pages\",\"metadata\":{\"page_id\":2,\"page_name\":\"MLM Staking\"}}','2026-09-28 14:33:43','2026-09-28 13:36:44','2026-09-28 14:33:43'),('6e4a64dd-503f-4fae-b6f7-6b8ca673b065','App\\Notifications\\SystemNotification','App\\Models\\Member',13,'{\"title\":\"WhatsApp Verification Approved\",\"message\":\"Your WhatsApp mobile number has been verified successfully. Your verified badge is now active!\",\"actor_id\":0,\"actor_name\":\"MLM Book System\",\"actor_photo\":null,\"reference_type\":\"system\",\"reference_id\":\"0\",\"icon\":\"bell\",\"category\":\"system\",\"url\":\"\\/account\\/settings\"}','2026-09-28 12:20:23','2026-09-28 12:18:52','2026-09-28 12:20:23'),('70c08b19-36ee-46ed-a68d-3f67c7b824b7','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"New Business Page Registered\",\"message\":\"\\\"MLM Staking\\\" was registered by Tamish Pratap Singh.\",\"body\":\"\\\"MLM Staking\\\" was registered by Tamish Pratap Singh.\",\"icon\":\"building\",\"source_type\":\"business_page\",\"source_id\":\"2\",\"action_url\":\"\\/admin\\/business-pages\",\"metadata\":{\"page_id\":2,\"page_name\":\"MLM Staking\"}}',NULL,'2026-09-28 13:36:44','2026-09-28 13:36:44'),('7118c681-ce41-4ba2-89b5-c3efe5e9dcf6','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Tamish Pratap Singh (tami383474) sent \\\"Hi\\\" from +9189******05 and requested verification.\",\"body\":\"Tamish Pratap Singh (tami383474) sent \\\"Hi\\\" from +9189******05 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/members\\/1\",\"metadata\":{\"member_id\":1,\"user_id\":\"tami383474\",\"phone\":\"+918979703005\",\"requested_at\":\"2026-09-28T05:26:22+00:00\"}}',NULL,'2026-09-28 10:56:22','2026-09-28 10:56:22'),('73014868-4bfe-4a11-9e24-36c0639f0499','App\\Notifications\\SystemNotification','App\\Models\\Member',14,'{\"title\":\"WhatsApp Verification Approved\",\"message\":\"Your WhatsApp mobile number has been verified successfully. Your verified badge is now active!\",\"actor_id\":0,\"actor_name\":\"MLM Book System\",\"actor_photo\":null,\"reference_type\":\"system\",\"reference_id\":\"0\",\"icon\":\"bell\",\"category\":\"system\",\"url\":\"\\/account\\/settings\"}','2026-09-28 12:41:51','2026-09-28 12:41:27','2026-09-28 12:41:51'),('778a6eaa-fae2-47e7-8aed-f7c7af372077','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"New Member Registered\",\"message\":\"Tamish Pratap Singh (tami383474) has registered via Google.\",\"body\":\"Tamish Pratap Singh (tami383474) has registered via Google.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/members\\/1\",\"metadata\":{\"member_id\":1,\"user_id\":\"tami383474\",\"phone\":\"+918979703005\"}}',NULL,'2026-09-28 10:52:23','2026-09-28 10:52:23'),('7b7bcffd-31ca-4c3d-b097-ff6e429c412a','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Tamish Pratap Singh (tami383474) sent \\\"Hi\\\" from +9189******05 and requested verification.\",\"body\":\"Tamish Pratap Singh (tami383474) sent \\\"Hi\\\" from +9189******05 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/members\\/1\",\"metadata\":{\"member_id\":1,\"user_id\":\"tami383474\",\"phone\":\"+918979703005\",\"requested_at\":\"2026-09-28T05:26:22+00:00\"}}','2026-09-28 12:17:35','2026-09-28 10:56:22','2026-09-28 12:17:35'),('80a77a70-8e81-45e1-ad33-1c9d340036d6','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"New Member Registered\",\"message\":\"Anand Kashyap (anan918051) has registered via Google.\",\"body\":\"Anand Kashyap (anan918051) has registered via Google.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"14\",\"action_url\":\"\\/admin\\/members\\/14\",\"metadata\":{\"member_id\":14,\"user_id\":\"anan918051\",\"phone\":\"+919997078388\"}}','2026-09-28 13:13:48','2026-09-28 12:35:12','2026-09-28 13:13:48'),('9363fab8-8510-4ba4-82dd-b194cf8e4bed','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"New Member Registered\",\"message\":\"Shubham Verma (shub648025) has registered on MLM Book.\",\"body\":\"Shubham Verma (shub648025) has registered on MLM Book.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"13\",\"action_url\":\"\\/admin\\/members\\/13\",\"metadata\":{\"member_id\":13,\"user_id\":\"shub648025\"}}',NULL,'2026-09-28 12:17:57','2026-09-28 12:17:57'),('b735099a-9082-4d27-94ea-caa999378d68','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"New Member Registered\",\"message\":\"Tamish Pratap Singh (tami383474) has registered via Google.\",\"body\":\"Tamish Pratap Singh (tami383474) has registered via Google.\",\"icon\":\"user\",\"source_type\":\"member\",\"source_id\":\"1\",\"action_url\":\"\\/admin\\/members\\/1\",\"metadata\":{\"member_id\":1,\"user_id\":\"tami383474\",\"phone\":\"+918979703005\"}}','2026-09-28 10:53:32','2026-09-28 10:52:23','2026-09-28 10:53:32'),('d2b35290-6d82-4228-9089-0d1c0eefcf94','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"New Ad Campaign Created\",\"message\":\"Campaign \\\"Digi Tech Solutions Pvt Ltd Campaign - 9\\/28\\/2026\\\" was created for \\\"Digi Tech Solutions Pvt Ltd\\\" and submitted for review.\",\"body\":\"Campaign \\\"Digi Tech Solutions Pvt Ltd Campaign - 9\\/28\\/2026\\\" was created for \\\"Digi Tech Solutions Pvt Ltd\\\" and submitted for review.\",\"icon\":\"megaphone\",\"source_type\":\"ad_campaign\",\"source_id\":\"3\",\"action_url\":\"\\/admin\\/ad-campaigns\",\"metadata\":{\"campaign_id\":3,\"business_page_id\":1}}','2026-09-28 13:13:48','2026-09-28 12:44:20','2026-09-28 13:13:48'),('e1e4c2be-1103-44d5-a594-b4a084b0e651','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Anand Kashyap (anan918051) sent \\\"Hi\\\" from +9199******88 and requested verification.\",\"body\":\"Anand Kashyap (anan918051) sent \\\"Hi\\\" from +9199******88 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"14\",\"action_url\":\"\\/admin\\/members\\/14\",\"metadata\":{\"member_id\":14,\"user_id\":\"anan918051\",\"phone\":\"+919997078388\",\"requested_at\":\"2026-09-28T12:40:58+05:30\"}}',NULL,'2026-09-28 12:40:58','2026-09-28 12:40:58'),('effb0429-810b-43d3-b004-9e1ccf00ba7f','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',25,'{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Shubham Verma (shub648025) sent \\\"Hi\\\" from +9199******69 and requested verification.\",\"body\":\"Shubham Verma (shub648025) sent \\\"Hi\\\" from +9199******69 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"13\",\"action_url\":\"\\/admin\\/members\\/13\",\"metadata\":{\"member_id\":13,\"user_id\":\"shub648025\",\"phone\":\"+919917558069\",\"requested_at\":\"2026-09-28T12:18:38+05:30\"}}',NULL,'2026-09-28 12:18:38','2026-09-28 12:18:38'),('fb7fc9e9-6e4c-4399-b76d-2ecb9c093096','App\\Notifications\\AdminAlertNotification','App\\Models\\Admin',24,'{\"title\":\"WhatsApp Verification Requested\",\"message\":\"Anand Kashyap (anan918051) sent \\\"Hi\\\" from +9199******88 and requested verification.\",\"body\":\"Anand Kashyap (anan918051) sent \\\"Hi\\\" from +9199******88 and requested verification.\",\"icon\":\"shield\",\"source_type\":\"member_verification\",\"source_id\":\"14\",\"action_url\":\"\\/admin\\/members\\/14\",\"metadata\":{\"member_id\":14,\"user_id\":\"anan918051\",\"phone\":\"+919997078388\",\"requested_at\":\"2026-09-28T12:40:58+05:30\"}}','2026-09-28 13:13:48','2026-09-28 12:40:58','2026-09-28 13:13:48');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pending_member_registrations`
--

DROP TABLE IF EXISTS `pending_member_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pending_member_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `introducer_id` varchar(30) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `last_resend_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pending_member_registrations_token_unique` (`token`),
  KEY `pending_member_registrations_user_id_index` (`user_id`),
  KEY `pending_member_registrations_email_index` (`email`),
  KEY `pending_member_registrations_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_member_registrations`
--

LOCK TABLES `pending_member_registrations` WRITE;
/*!40000 ALTER TABLE `pending_member_registrations` DISABLE KEYS */;
INSERT INTO `pending_member_registrations` VALUES (2,'8aHAdcAEn56jePVldWLOFui3ammaf937ozXjRKetCDdkeVqCvy7HDYlqtj8lq9vS','Sagar Rajput','saga387722',NULL,'sagar@gmail.com','+914444455555','$2y$12$RZByViLCEkGNtOsvZQ3co.KKUjG5LZWszBxvjXg4f8U0.jn4DEdaq','$2y$12$oYM7FC4qbKq53P/8761PdO.Zpw4hTXXb96TW7f6QGNmyId0RFVxxi','2026-09-28 05:43:45',0,'2026-09-28 05:33:45','2026-09-28 11:03:45','2026-09-28 11:03:45');
/*!40000 ALTER TABLE `pending_member_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `group` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_comments`
--

DROP TABLE IF EXISTS `post_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_comments_post_id_index` (`post_id`),
  KEY `post_comments_member_id_index` (`member_id`),
  KEY `post_comments_created_at_index` (`created_at`),
  KEY `post_comments_parent_id_index` (`parent_id`),
  KEY `idx_post_comments_p_parent` (`post_id`,`parent_id`),
  CONSTRAINT `post_comments_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `post_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_comments_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_comments`
--

LOCK TABLES `post_comments` WRITE;
/*!40000 ALTER TABLE `post_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_likes`
--

DROP TABLE IF EXISTS `post_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_likes_post_member_unique` (`post_id`,`member_id`),
  KEY `post_likes_post_id_index` (`post_id`),
  KEY `post_likes_member_id_index` (`member_id`),
  KEY `idx_post_likes_p_m` (`post_id`,`member_id`),
  CONSTRAINT `post_likes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_likes_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_likes`
--

LOCK TABLES `post_likes` WRITE;
/*!40000 ALTER TABLE `post_likes` DISABLE KEYS */;
INSERT INTO `post_likes` VALUES (2,7,14,'2026-09-28 12:46:58','2026-09-28 12:46:58');
/*!40000 ALTER TABLE `post_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_reactions`
--

DROP TABLE IF EXISTS `post_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_reactions_post_member_unique` (`post_id`,`member_id`),
  KEY `post_reactions_post_id_index` (`post_id`),
  KEY `post_reactions_member_id_index` (`member_id`),
  KEY `post_reactions_reaction_index` (`reaction`),
  CONSTRAINT `post_reactions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_reactions_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_reactions`
--

LOCK TABLES `post_reactions` WRITE;
/*!40000 ALTER TABLE `post_reactions` DISABLE KEYS */;
INSERT INTO `post_reactions` VALUES (2,7,14,'like','2026-09-28 12:46:58','2026-09-28 12:46:58');
/*!40000 ALTER TABLE `post_reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_shares`
--

DROP TABLE IF EXISTS `post_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `original_post_id` bigint(20) unsigned NOT NULL,
  `shared_post_id` bigint(20) unsigned NOT NULL,
  `shared_by` bigint(20) unsigned NOT NULL,
  `share_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_shares_original_post_id_index` (`original_post_id`),
  KEY `post_shares_shared_post_id_index` (`shared_post_id`),
  KEY `post_shares_shared_by_index` (`shared_by`),
  CONSTRAINT `post_shares_original_post_id_foreign` FOREIGN KEY (`original_post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_shares_shared_by_foreign` FOREIGN KEY (`shared_by`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_shares_shared_post_id_foreign` FOREIGN KEY (`shared_post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_shares`
--

LOCK TABLES `post_shares` WRITE;
/*!40000 ALTER TABLE `post_shares` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_shares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `community_id` bigint(20) unsigned DEFAULT NULL,
  `business_page_id` bigint(20) unsigned DEFAULT NULL,
  `event_id` bigint(20) unsigned DEFAULT NULL,
  `is_announcement` tinyint(1) NOT NULL DEFAULT 0,
  `original_post_id` bigint(20) unsigned DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `body` text DEFAULT NULL,
  `media_type` varchar(255) DEFAULT NULL,
  `media_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `posts_member_id_foreign` (`member_id`),
  KEY `posts_original_post_id_index` (`original_post_id`),
  KEY `posts_is_pinned_index` (`is_pinned`),
  KEY `idx_posts_group_created` (`group_id`,`created_at`),
  KEY `idx_posts_event_created` (`event_id`,`created_at`),
  KEY `posts_community_id_is_pinned_created_at_index` (`community_id`,`is_pinned`,`created_at`),
  KEY `posts_community_id_is_announcement_created_at_index` (`community_id`,`is_announcement`,`created_at`),
  KEY `posts_business_page_id_is_pinned_created_at_index` (`business_page_id`,`is_pinned`,`created_at`),
  KEY `posts_business_page_id_is_featured_created_at_index` (`business_page_id`,`is_featured`,`created_at`),
  KEY `posts_business_page_id_is_announcement_created_at_index` (`business_page_id`,`is_announcement`,`created_at`),
  CONSTRAINT `posts_business_page_id_foreign` FOREIGN KEY (`business_page_id`) REFERENCES `business_pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_community_id_foreign` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `posts_original_post_id_foreign` FOREIGN KEY (`original_post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (7,13,NULL,NULL,1,NULL,0,NULL,0,0,'💻 Custom Software | Web & Mobile Apps | CRM & ERP | AI | FinTech | E-Commerce & More','image','uploads/posts/images/biz_post_13_1790579107_neIjhUFZ.png','2026-09-28 07:05:07','2026-09-28 07:05:07'),(8,13,NULL,NULL,NULL,NULL,0,NULL,0,0,'Testing post share','image','uploads/posts/images/post_13_1790579425_zj6vacfk.jpg','2026-09-28 07:10:25','2026-09-28 07:10:25'),(9,1,NULL,NULL,NULL,NULL,0,NULL,0,0,NULL,'image','uploads/posts/images/post_1_1790579593_mhecxp0f.jpg','2026-09-28 07:13:13','2026-09-28 07:13:13'),(10,1,NULL,NULL,NULL,NULL,0,NULL,0,0,NULL,'image','uploads/posts/images/post_1_1790579675_luo4vrqt.png','2026-09-28 12:44:35','2026-09-28 12:44:35'),(11,1,NULL,NULL,2,NULL,0,NULL,0,0,NULL,'image','uploads/posts/images/biz_post_1_1790582813_YqbtDSYM.jpg','2026-09-28 13:36:53','2026-09-28 13:36:53');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_media`
--

DROP TABLE IF EXISTS `product_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `media_type` varchar(255) NOT NULL DEFAULT 'image',
  `media_path` varchar(255) NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_media_product_id_foreign` (`product_id`),
  CONSTRAINT `product_media_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_media`
--

LOCK TABLES `product_media` WRITE;
/*!40000 ALTER TABLE `product_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `sub_category_id` bigint(20) unsigned DEFAULT NULL,
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
  `views_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_sub_category_id_foreign` (`sub_category_id`),
  KEY `products_category_id_status_index` (`category_id`,`status`),
  KEY `products_member_id_status_index` (`member_id`,`status`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `profile_visits`
--

DROP TABLE IF EXISTS `profile_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_owner_id` bigint(20) unsigned NOT NULL,
  `visitor_id` bigint(20) unsigned NOT NULL,
  `visited_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_visits_visitor_id_foreign` (`visitor_id`),
  KEY `profile_visits_profile_owner_id_visited_at_index` (`profile_owner_id`,`visited_at`),
  CONSTRAINT `profile_visits_profile_owner_id_foreign` FOREIGN KEY (`profile_owner_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `profile_visits_visitor_id_foreign` FOREIGN KEY (`visitor_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profile_visits`
--

LOCK TABLES `profile_visits` WRITE;
/*!40000 ALTER TABLE `profile_visits` DISABLE KEYS */;
INSERT INTO `profile_visits` VALUES (1,13,1,'2026-09-28 12:42:57','2026-09-28 12:42:57','2026-09-28 12:42:57');
/*!40000 ALTER TABLE `profile_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reported_posts`
--

DROP TABLE IF EXISTS `reported_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reported_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reported_posts_member_id_index` (`member_id`),
  KEY `reported_posts_post_id_index` (`post_id`),
  KEY `reported_posts_status_index` (`status`),
  CONSTRAINT `reported_posts_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reported_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reported_posts`
--

LOCK TABLES `reported_posts` WRITE;
/*!40000 ALTER TABLE `reported_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `reported_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reported_products`
--

DROP TABLE IF EXISTS `reported_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reported_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reported_products_member_id_foreign` (`member_id`),
  KEY `reported_products_product_id_foreign` (`product_id`),
  CONSTRAINT `reported_products_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reported_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reported_products`
--

LOCK TABLES `reported_products` WRITE;
/*!40000 ALTER TABLE `reported_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `reported_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_rank_rules`
--

DROP TABLE IF EXISTS `reward_rank_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reward_rank_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rank_key` varchar(32) NOT NULL,
  `rank_name` varchar(50) NOT NULL,
  `priority` int(10) unsigned NOT NULL DEFAULT 1,
  `referral_requirement` int(10) unsigned NOT NULL DEFAULT 0,
  `team_requirement` int(10) unsigned NOT NULL DEFAULT 0,
  `reward_amount` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reward_rank_rules_rank_key_unique` (`rank_key`),
  KEY `reward_rank_rules_created_by_foreign` (`created_by`),
  KEY `reward_rank_rules_updated_by_foreign` (`updated_by`),
  KEY `reward_rank_rules_priority_index` (`priority`),
  KEY `reward_rank_rules_referral_requirement_index` (`referral_requirement`),
  KEY `reward_rank_rules_team_requirement_index` (`team_requirement`),
  KEY `reward_rank_rules_is_active_index` (`is_active`),
  CONSTRAINT `reward_rank_rules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reward_rank_rules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_rank_rules`
--

LOCK TABLES `reward_rank_rules` WRITE;
/*!40000 ALTER TABLE `reward_rank_rules` DISABLE KEYS */;
INSERT INTO `reward_rank_rules` VALUES (1,'advertiser','Advertiser',1,0,0,0.0250,1,NULL,24,'2026-09-28 12:50:55','2026-09-28 12:53:40'),(2,'influencer','Influencer',2,10,0,0.1000,1,NULL,24,'2026-09-28 12:50:55','2026-09-28 12:54:27'),(3,'leaders','Leaders',3,15,50,0.0500,1,NULL,NULL,'2026-09-28 12:50:55','2026-09-28 12:50:55'),(4,'pro_leaders','Pro Leaders',4,30,150,0.0750,1,NULL,NULL,'2026-09-28 12:50:55','2026-09-28 12:50:55'),(5,'master_leaders','Master Leaders',5,50,500,0.1000,1,NULL,NULL,'2026-09-28 12:50:55','2026-09-28 12:50:55');
/*!40000 ALTER TABLE `reward_rank_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_posts`
--

DROP TABLE IF EXISTS `saved_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saved_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saved_posts_member_post_unique` (`member_id`,`post_id`),
  KEY `saved_posts_member_id_index` (`member_id`),
  KEY `saved_posts_post_id_index` (`post_id`),
  CONSTRAINT `saved_posts_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_posts`
--

LOCK TABLES `saved_posts` WRITE;
/*!40000 ALTER TABLE `saved_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `saved_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_products`
--

DROP TABLE IF EXISTS `saved_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saved_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saved_products_member_id_product_id_unique` (`member_id`,`product_id`),
  KEY `saved_products_product_id_foreign` (`product_id`),
  CONSTRAINT `saved_products_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_products`
--

LOCK TABLES `saved_products` WRITE;
/*!40000 ALTER TABLE `saved_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `saved_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `group` varchar(100) NOT NULL DEFAULT 'general',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_favicon','storage/branding/favicon_1789809966_h0ybzqtd.png','branding','2026-09-19 14:56:07','2026-09-19 14:56:07'),(2,'deposit_fee_percent','10.00','funds','2026-09-25 19:41:04','2026-09-26 16:30:48'),(3,'service_charge_percent','5.00','funds','2026-09-25 19:41:04','2026-09-25 19:42:31'),(4,'deposit_currency','USDT','funds','2026-09-25 19:42:31','2026-09-26 15:46:27'),(5,'deposit_network','BEP-20','funds','2026-09-25 19:42:31','2026-09-26 15:46:27'),(6,'deposit_token','USDT','funds','2026-09-25 19:42:31','2026-09-26 15:46:27'),(7,'bsc_network','mainnet','blockchain','2026-09-25 19:42:31','2026-09-25 19:42:31'),(8,'deposit_crypto_wallet_address','0x55d398326f99059fF775485246999027B3197955','funds','2026-09-25 19:42:31','2026-09-26 15:46:27'),(9,'deposit_instructions','Transfer payment in USDT (BEP-20) using the configured crypto wallet address or QR code. Enter your transaction hash after completing payment.','funds','2026-09-25 19:42:31','2026-09-26 15:46:27'),(18,'campaign_platform_fee_percent','0','ads','2026-09-28 13:36:27','2026-09-28 14:32:33');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stories`
--

DROP TABLE IF EXISTS `stories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `media_type` varchar(255) NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stories_member_id_expires_at_index` (`member_id`,`expires_at`),
  KEY `stories_expires_at_index` (`expires_at`),
  CONSTRAINT `stories_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stories`
--

LOCK TABLES `stories` WRITE;
/*!40000 ALTER TABLE `stories` DISABLE KEYS */;
INSERT INTO `stories` VALUES (2,1,NULL,'image','uploads/stories/images/story_1_1790578065_ivxlv9pv.jpg','2026-09-29 06:47:45','2026-09-28 06:47:45','2026-09-28 06:47:45'),(3,1,NULL,'image','uploads/stories/images/story_1_1790578871_br9b9uc7.jpg','2026-09-29 12:31:11','2026-09-28 12:31:11','2026-09-28 12:31:11'),(4,1,NULL,'image','uploads/stories/images/story_1_1790579669_8niy1cu3.jpg','2026-09-29 12:44:29','2026-09-28 12:44:29','2026-09-28 12:44:29');
/*!40000 ALTER TABLE `stories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_likes`
--

DROP TABLE IF EXISTS `story_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `story_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `story_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_likes_story_member_unique` (`story_id`,`member_id`),
  KEY `story_likes_story_id_index` (`story_id`),
  KEY `story_likes_member_id_index` (`member_id`),
  CONSTRAINT `story_likes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_likes_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_likes`
--

LOCK TABLES `story_likes` WRITE;
/*!40000 ALTER TABLE `story_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `story_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_reactions`
--

DROP TABLE IF EXISTS `story_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `story_reactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `story_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_reactions_story_member_unique` (`story_id`,`member_id`),
  KEY `story_reactions_story_id_index` (`story_id`),
  KEY `story_reactions_member_id_index` (`member_id`),
  CONSTRAINT `story_reactions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_reactions_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_reactions`
--

LOCK TABLES `story_reactions` WRITE;
/*!40000 ALTER TABLE `story_reactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `story_reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_replies`
--

DROP TABLE IF EXISTS `story_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `story_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `story_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `story_replies_story_id_index` (`story_id`),
  KEY `story_replies_sender_id_index` (`sender_id`),
  KEY `story_replies_receiver_id_index` (`receiver_id`),
  KEY `story_replies_is_seen_index` (`is_seen`),
  CONSTRAINT `story_replies_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_replies_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_replies_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_replies`
--

LOCK TABLES `story_replies` WRITE;
/*!40000 ALTER TABLE `story_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `story_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `story_views`
--

DROP TABLE IF EXISTS `story_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `story_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `story_id` bigint(20) unsigned NOT NULL,
  `viewer_member_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_views_story_viewer_unique` (`story_id`,`viewer_member_id`),
  KEY `story_views_story_id_index` (`story_id`),
  KEY `story_views_viewer_member_id_index` (`viewer_member_id`),
  CONSTRAINT `story_views_story_id_foreign` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `story_views_viewer_member_id_foreign` FOREIGN KEY (`viewer_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `story_views`
--

LOCK TABLES `story_views` WRITE;
/*!40000 ALTER TABLE `story_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `story_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdrawal_requests`
--

DROP TABLE IF EXISTS `withdrawal_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `withdrawal_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned DEFAULT NULL,
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
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `withdrawal_requests_member_id_foreign` (`member_id`),
  CONSTRAINT `withdrawal_requests_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawal_requests`
--

LOCK TABLES `withdrawal_requests` WRITE;
/*!40000 ALTER TABLE `withdrawal_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `withdrawal_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdrawal_settings`
--

DROP TABLE IF EXISTS `withdrawal_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `withdrawal_settings` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `private_key` varchar(300) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawal_settings`
--

LOCK TABLES `withdrawal_settings` WRITE;
/*!40000 ALTER TABLE `withdrawal_settings` DISABLE KEYS */;
INSERT INTO `withdrawal_settings` VALUES (1,'0x36da85fd4g8fd47c4185fe6a8e98a25b3aaac9d27a44b48c7186af339864639ab','2026-09-08 18:49:29','2026-09-08 18:49:29');
/*!40000 ALTER TABLE `withdrawal_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `import_fund`
--

/*!50001 DROP VIEW IF EXISTS `import_fund`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u834681197_Mlm_Book`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `import_fund` AS select `834681197_mlm_book`.`import_funds`.`id` AS `id`,`834681197_mlm_book`.`import_funds`.`memberid` AS `memberid`,`834681197_mlm_book`.`import_funds`.`user_id` AS `user_id`,`834681197_mlm_book`.`import_funds`.`member_id` AS `member_id`,`834681197_mlm_book`.`import_funds`.`txnid` AS `txnid`,`834681197_mlm_book`.`import_funds`.`transaction_hash` AS `transaction_hash`,`834681197_mlm_book`.`import_funds`.`orderid` AS `orderid`,`834681197_mlm_book`.`import_funds`.`amount` AS `amount`,`834681197_mlm_book`.`import_funds`.`type` AS `type`,`834681197_mlm_book`.`import_funds`.`wallet_type` AS `wallet_type`,`834681197_mlm_book`.`import_funds`.`wallet_address` AS `wallet_address`,`834681197_mlm_book`.`import_funds`.`network` AS `network`,`834681197_mlm_book`.`import_funds`.`token` AS `token`,`834681197_mlm_book`.`import_funds`.`contract_address` AS `contract_address`,`834681197_mlm_book`.`import_funds`.`added_by` AS `added_by`,`834681197_mlm_book`.`import_funds`.`status` AS `status`,`834681197_mlm_book`.`import_funds`.`verification_status` AS `verification_status`,`834681197_mlm_book`.`import_funds`.`deposit_status` AS `deposit_status`,`834681197_mlm_book`.`import_funds`.`verification_payload` AS `verification_payload`,`834681197_mlm_book`.`import_funds`.`admin_notes` AS `admin_notes`,`834681197_mlm_book`.`import_funds`.`rejection_reason` AS `rejection_reason`,`834681197_mlm_book`.`import_funds`.`verified_at` AS `verified_at`,`834681197_mlm_book`.`import_funds`.`verified_by` AS `verified_by`,`834681197_mlm_book`.`import_funds`.`mode` AS `mode`,`834681197_mlm_book`.`import_funds`.`created_at` AS `created_at`,`834681197_mlm_book`.`import_funds`.`updated_at` AS `updated_at` from `import_funds` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-28 14:51:16
