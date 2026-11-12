-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mobileshop2
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
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `type` enum('shipping','billing') DEFAULT 'shipping',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_address_user` (`user_id`),
  CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (1,4,'John Doe','9876543213','Flat 402, Sunshine Heights','MG Road, Indiranagar','Bengaluru','Karnataka','560038',1,'shipping','2026-11-05 16:18:25'),(2,5,'Priya Patel','9876543214','B-12, Shanti Niketan','Satellite Road','Ahmedabad','Gujarat','380015',1,'shipping','2026-11-05 16:18:25');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `record_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_module_action` (`module`,`action`),
  KEY `idx_audit_created_at` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Apple','apple','apple.png',1,'2026-11-05 16:18:25'),(2,'Samsung','samsung','samsung.png',1,'2026-11-05 16:18:25'),(3,'Google','google','google.png',1,'2026-11-05 16:18:25'),(4,'OnePlus','oneplus','oneplus.png',1,'2026-11-05 16:18:25'),(5,'Xiaomi','xiaomi','xiaomi.png',1,'2026-11-05 16:18:25'),(6,'Realme','realme','realme.png',1,'2026-11-05 16:18:25'),(7,'boAt','boat','boat.png',1,'2026-11-05 16:18:25'),(8,'Anker','anker','anker.png',1,'2026-11-05 16:18:25'),(9,'Spigen','spigen','spigen.png',1,'2026-11-05 16:18:25');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cart_session` (`session_id`),
  KEY `idx_cart_user` (`user_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (1,1,'cca2110322c69d928fd6b5626b43b93b','2026-11-06 16:19:51','2026-11-06 16:19:51'),(2,6,'1685c50b54f036a60395135f1c260021','2026-11-06 16:32:01','2026-11-06 16:36:15'),(3,6,'763c1f5669cfb45b478f453fd8d596ba','2026-11-06 16:59:26','2026-11-06 16:59:26'),(4,6,'cd86237b3411eddfd698effe6a785429','2026-11-06 17:51:00','2026-11-06 17:51:00'),(5,NULL,'8e3d052c70efff9d8ea5c6f6525df331','2026-11-06 18:01:00','2026-11-06 18:01:00'),(6,NULL,'c7f3e29d8387bc7064a0bd6dbb0d5d6f','2026-11-06 18:03:27','2026-11-06 18:03:27'),(7,NULL,'bc69ee38d7aac6af473a75622c99c8d7','2026-11-06 18:04:02','2026-11-06 18:04:02'),(8,NULL,'01a8d8add5cca00502189e9dd6628083','2026-11-06 18:16:04','2026-11-06 18:16:04'),(9,NULL,'365e4b9c1528d679b95dd9aa45a04589','2026-11-06 18:32:39','2026-11-06 18:32:39'),(10,NULL,'28465c65237c1cf072b26e061178cfdf','2026-11-06 18:45:49','2026-11-06 18:45:49'),(11,NULL,'735245722771adae37331ab28f624348','2026-11-06 18:46:07','2026-11-06 18:46:07'),(12,NULL,'cd553e334d6a3fbfb2728f9cc6ca5bb9','2026-11-06 18:46:08','2026-11-06 18:46:08'),(13,NULL,'9bcc18a40ecbfe58360e58e5587f0951','2026-11-06 18:46:08','2026-11-06 18:46:08'),(14,NULL,'21f76c07842841f8b2c0538a6bce25ef','2026-11-06 18:49:21','2026-11-06 18:49:21'),(15,NULL,'60a241ffa95b653e3a50b71ba5cae18e','2026-11-06 18:52:22','2026-11-06 18:52:22'),(16,NULL,'de8fb2b603213b5d277e01285d694f05','2026-11-08 15:15:58','2026-11-08 15:15:58'),(17,NULL,'516b427a86b5f2f3e378db5fde88f1e6','2026-11-08 15:28:00','2026-11-08 15:28:00'),(18,4,'6fa5a1be43b598c3243418b9e5c11f96','2026-11-08 15:44:38','2026-11-08 15:44:38'),(19,NULL,'25bfb999569a65977185f9140af85e97','2026-11-08 15:52:54','2026-11-08 15:52:54'),(20,NULL,'b1fa0d5075a3107ba3ea4e588d7d384b','2026-11-08 15:52:54','2026-11-08 15:52:54'),(21,NULL,'e03d6836392772ecceb66cf4275ad2d2','2026-11-08 15:52:54','2026-11-08 15:52:54'),(22,NULL,'c72db8b0edd0ce275402c4ef2cd59017','2026-11-08 15:52:54','2026-11-08 15:52:54'),(23,NULL,'fab89e6d30d38619ce8026041874ad3c','2026-11-08 15:52:54','2026-11-08 15:52:54'),(24,NULL,'3949dd7f6c371b4d801a7747644568d3','2026-11-08 15:52:54','2026-11-08 15:52:54'),(25,4,'1d21bcac4ea5453859c5fba01a785494','2026-11-08 15:52:54','2026-11-08 15:52:54'),(26,4,'bdb4784b50f57e4b0dd71024c761ca81','2026-11-08 15:56:40','2026-11-08 15:56:40'),(27,NULL,'0c7bd9c5f2abcf7bebf3de45f84cc51a','2026-11-08 15:58:05','2026-11-08 15:58:05'),(28,NULL,'efa47d88caa7bafd94f30f5a56960886','2026-11-08 15:58:05','2026-11-08 15:58:05'),(29,NULL,'60941e762ce8bd753d4faceab4305ac2','2026-11-08 15:58:05','2026-11-08 15:58:05'),(30,NULL,'1c1bac5f385d67006d07f59c10c21347','2026-11-08 15:58:05','2026-11-08 15:58:05'),(31,NULL,'3995b7cf2fd07b91ce46b2cb1adef673','2026-11-08 15:58:05','2026-11-08 15:58:05'),(32,NULL,'68ca5da70d9be29232acffdc3d945b91','2026-11-08 15:58:05','2026-11-08 15:58:05'),(33,4,'1c3d39aaf02c859c42adea504583dbe9','2026-11-08 15:58:05','2026-11-08 15:58:05'),(34,4,'345d63b8f32b93114560e678f06388e8','2026-11-08 15:58:35','2026-11-08 15:58:35'),(35,NULL,'f1959055ce78fee4dbfb84316a3f8e3b','2026-11-08 15:58:36','2026-11-08 15:58:36'),(36,NULL,'b34704e848d0fb7d8258fd9837c94aa4','2026-11-08 15:58:36','2026-11-08 15:58:36'),(37,NULL,'8fc9d4d7f9c67f157ceb827e8e0cca2b','2026-11-08 15:58:36','2026-11-08 15:58:36'),(38,NULL,'c5585b29e8eb5d2c170bb1a272cb2c2e','2026-11-08 15:58:36','2026-11-08 15:58:36'),(39,NULL,'f0babeca810e90aba490162e505be7e7','2026-11-08 15:58:36','2026-11-08 15:58:36'),(40,NULL,'c4252f74031e7bd97233bda131cbc3aa','2026-11-08 15:58:36','2026-11-08 15:58:36'),(41,4,'c2c90259d19eae6e513c259e21879292','2026-11-08 15:58:36','2026-11-08 15:58:36'),(42,NULL,'55cd0152b4e9d0b21dfe763da7a27f27','2026-11-08 16:04:57','2026-11-08 16:04:57'),(43,NULL,'9ebc9572a0035315aea74e4542cba3bf','2026-11-08 16:04:57','2026-11-08 16:04:57'),(44,NULL,'e46a90c99f43d97fa17ff81c0774daf3','2026-11-08 16:04:57','2026-11-08 16:04:57'),(45,NULL,'0464233b3b20a88d545d79fc2c127f66','2026-11-08 16:04:57','2026-11-08 16:04:57'),(46,NULL,'e901fea0114cb9fe1257a65e90b6e332','2026-11-08 16:04:57','2026-11-08 16:04:57'),(47,NULL,'45cb290eaacb367d9cb8dcbc1083fba4','2026-11-08 16:04:57','2026-11-08 16:04:57'),(48,NULL,'b91dc36f6ff45c4360a8d7d9c8b09ad4','2026-11-08 16:04:57','2026-11-08 16:04:57'),(49,NULL,'e79827bdd3f14c2b8fb7c022d1e61aa0','2026-11-08 16:04:57','2026-11-08 16:04:57'),(50,NULL,'efab0778c195e30380062f4b4aa7e4ac','2026-11-08 16:04:57','2026-11-08 16:04:57'),(51,NULL,'c1bac10afba62b0d7957aeafd7dd4b79','2026-11-08 16:04:57','2026-11-08 16:04:57'),(52,NULL,'de4fb6ab35fe50b00d53f9be57f50d4c','2026-11-08 16:04:57','2026-11-08 16:04:57'),(53,NULL,'c5e0b4b34f131ed73ec171f703aee056','2026-11-08 16:04:57','2026-11-08 16:04:57'),(54,NULL,'f1141fef4d9264d9f378f993b6bd3ef4','2026-11-08 16:04:57','2026-11-08 16:04:57'),(55,NULL,'0190bf52918e93359aaebaaa597e3256','2026-11-08 16:04:57','2026-11-08 16:04:57'),(56,NULL,'e2715f46111d90ad0d0792724dd0e113','2026-11-08 16:04:58','2026-11-08 16:04:58'),(57,NULL,'c0dbef49d0d772c58dd43e68710899d5','2026-11-08 16:04:58','2026-11-08 16:04:58'),(58,NULL,'248642793677a8cc32e61a83578a75b9','2026-11-08 16:04:58','2026-11-08 16:04:58'),(59,NULL,'4f1114f88396b5282973d9590f8774f3','2026-11-08 16:04:58','2026-11-08 16:04:58'),(60,NULL,'996940806d9ffe692101f7344bb6b574','2026-11-08 16:04:58','2026-11-08 16:04:58'),(61,NULL,'c7e00b76c82c859a4df3a4b9f95006ff','2026-11-08 16:04:58','2026-11-08 16:04:58'),(62,NULL,'ac4f7b62c1623d3eaac76d1f0d823f7e','2026-11-08 16:04:58','2026-11-08 16:04:58'),(63,NULL,'6df2e34621104f8e0e6e298593a16e77','2026-11-08 16:04:58','2026-11-08 16:04:58'),(64,NULL,'30c63e23381dd962021ea746b595fea9','2026-11-08 16:04:58','2026-11-08 16:04:58'),(65,NULL,'f17295ee99ae9dcd42726cdcb21cd9b3','2026-11-08 16:04:58','2026-11-08 16:04:58'),(66,NULL,'f0cd3c1369e2f0c2c93c0efd1449c9dc','2026-11-08 16:04:58','2026-11-08 16:04:58'),(67,NULL,'6270e690201bff643d8e00d7dd3a88b0','2026-11-08 16:16:44','2026-11-08 16:16:44'),(68,NULL,'5ffefd504af74ddd3277835ac1d75fa3','2026-11-08 16:27:42','2026-11-08 16:27:42'),(69,NULL,'348aade7f1498d646bc1e8325079e235','2026-11-08 16:32:48','2026-11-08 16:32:48'),(70,NULL,'602044c28714cfd8e70bc629cc71ff5c','2026-11-08 16:33:42','2026-11-08 16:33:42'),(71,NULL,'0b550f839679579c9e886f8f4654a0d2','2026-11-08 16:33:58','2026-11-08 16:33:58'),(72,NULL,'199c8a2b3a7a2e8ad3ec83883374af63','2026-11-08 16:34:19','2026-11-08 16:34:19'),(73,NULL,'85a0afaf9627bb8e024b36e5f108f328','2026-11-08 16:35:14','2026-11-08 16:35:14'),(74,4,'5677c5d5fdeb274e8958ad099cf92acc','2026-11-08 16:41:24','2026-11-08 16:41:24');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cart_item` (`cart_id`,`product_id`,`variant_id`),
  KEY `fk_ci_prod` (`product_id`),
  KEY `fk_ci_variant` (`variant_id`),
  CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category_parent` (`parent_id`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Smartphones','smartphones','Flagship, premium and budget smartphones from top global brands','cat_smartphones.jpg',1,1,'2026-11-05 16:18:25'),(2,NULL,'Chargers & Adapters','chargers-adapters','Fast GaN chargers, wireless pads, and original USB-C adapters','cat_chargers.jpg',2,1,'2026-11-05 16:18:25'),(3,NULL,'Earphones & Audio','earphones-audio','TWS earbuds, noise-cancelling headphones and studio audio','cat_audio.jpg',3,1,'2026-11-05 16:18:25'),(4,NULL,'Bluetooth Devices','bluetooth-devices','Bluetooth portable speakers, car transmitters and transmitters','cat_bluetooth.jpg',4,1,'2026-11-05 16:18:25'),(5,NULL,'Smartwatches & Bands','smartwatches','Health tracking smartwatches, fitness bands and accessories','cat_smartwatches.jpg',5,1,'2026-11-05 16:18:25'),(6,NULL,'Mobile Covers & Cases','mobile-covers','Shockproof armor, slim silicone, and premium leather cases','cat_covers.jpg',6,1,'2026-11-05 16:18:25'),(7,NULL,'Tempered Glass & Protection','tempered-glass','9H scratch-resistant tempered glass and privacy protectors','cat_tempered.jpg',7,1,'2026-11-05 16:18:25'),(8,NULL,'Batteries & Power Banks','batteries-powerbanks','High-capacity fast-charging power banks and original batteries','cat_powerbanks.jpg',8,1,'2026-11-05 16:18:25'),(9,NULL,'Repair Spare Parts','spare-parts','OEM displays, camera modules, charging ports, and flex cables','cat_spares.jpg',9,1,'2026-11-05 16:18:25');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(12,2) NOT NULL,
  `min_order_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(12,2) DEFAULT NULL,
  `usage_limit` int(10) unsigned DEFAULT 1000,
  `usage_count` int(10) unsigned DEFAULT 0,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_coupon_code` (`code`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'WELCOME10','percentage',10.00,2000.00,1000.00,500,2,'2024-01-01 00:00:00','2028-12-31 23:59:59',1,'2026-11-05 16:18:25'),(2,'FLAT500','fixed',500.00,5000.00,500.00,200,0,'2024-01-01 00:00:00','2028-12-31 23:59:59',1,'2026-11-05 16:18:25'),(3,'FESTIVE15','percentage',15.00,10000.00,2500.00,100,0,'2024-01-01 00:00:00','2028-12-31 23:59:59',1,'2026-11-05 16:18:25');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` enum('rent','electricity','salaries','marketing','equipment','tools','repair_parts','maintenance','other') NOT NULL,
  `title` varchar(200) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','upi','card') NOT NULL DEFAULT 'cash',
  `expense_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_exp_category` (`category`),
  KEY `idx_exp_date` (`expense_date`),
  KEY `fk_exp_creator` (`created_by`),
  KEY `idx_exp_created_at` (`created_at`),
  CONSTRAINT `fk_exp_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,'rent','Showroom Monthly Lease',45000.00,'bank_transfer','2026-10-26','Electronic City Tech Park unit lease',NULL,1,'2026-11-05 16:18:25'),(2,'electricity','BESCOM Commercial Power Bill',6800.00,'upi','2026-10-28','Showroom & repair lab power consumption',NULL,1,'2026-11-05 16:18:25'),(3,'tools','Precision Screwdriver & Heat Mat Kit',4200.00,'card','2026-11-01','iFixit Pro Tech Toolkit for technician workbench',NULL,1,'2026-11-05 16:18:25');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `min_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `max_stock_threshold` int(11) NOT NULL DEFAULT 100,
  `location_rack` varchar(100) DEFAULT 'Main Display',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prod_variant_inv` (`product_id`,`variant_id`),
  KEY `idx_inv_low_stock` (`current_stock`,`min_stock_threshold`),
  KEY `fk_inv_variant` (`variant_id`),
  CONSTRAINT `fk_inv_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,1,1,12,3,30,'Rack A-01 (Safe)','2026-11-05 16:18:25'),(2,1,2,8,2,20,'Rack A-01 (Safe)','2026-11-05 16:18:25'),(3,1,3,15,3,30,'Rack A-01 (Safe)','2026-11-05 16:18:25'),(4,2,4,14,3,25,'Rack A-02','2026-11-05 16:18:25'),(5,2,5,9,2,20,'Rack A-02','2026-11-05 16:18:25'),(6,3,6,18,4,35,'Rack A-03','2026-11-05 16:18:25'),(7,3,7,11,3,25,'Rack A-03','2026-11-05 16:18:25'),(8,4,NULL,2,2,20,'Rack A-04','2026-11-08 16:41:25'),(9,5,NULL,35,5,80,'Shelf B-01','2026-11-08 16:41:25'),(10,6,NULL,40,10,100,'Shelf B-02','2026-11-05 16:18:25'),(11,7,NULL,28,5,60,'Shelf C-01','2026-11-05 16:18:25'),(12,8,NULL,22,5,50,'Shelf C-02','2026-11-05 16:18:25'),(13,9,NULL,6,2,15,'Display Showcase 1','2026-11-05 16:18:25'),(14,10,NULL,50,10,100,'Hook Rack D-01','2026-11-05 16:18:25'),(15,11,NULL,65,15,150,'Hook Rack D-02','2026-11-05 16:18:25'),(16,12,NULL,25,5,60,'Shelf B-03','2026-11-05 16:18:25'),(17,13,NULL,4,2,10,'Parts Drawer P-01','2026-11-05 16:18:25'),(18,14,NULL,5,2,12,'Parts Drawer P-02','2026-11-05 16:18:25'),(19,1,NULL,0,5,100,'Main Display','2026-11-08 16:41:25');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_type` enum('order','repair','pos') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pdf_path` varchar(255) DEFAULT NULL,
  `status` enum('issued','paid','cancelled') NOT NULL DEFAULT 'issued',
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_inv_type_ref` (`invoice_type`,`reference_id`),
  KEY `idx_inv_user` (`user_id`),
  CONSTRAINT `fk_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-202611-0003','order',3,4,296997.00,53459.46,0.00,350456.46,NULL,'issued','2026-11-08 15:44:38'),(2,'INV-202611-0004','order',4,4,296997.00,53459.46,0.00,350456.46,NULL,'issued','2026-11-08 15:56:40'),(3,'INV-202611-0005','order',5,4,10497.00,1889.46,0.00,12386.46,NULL,'issued','2026-11-08 15:58:35'),(4,'INV-202611-0006','order',6,4,10497.00,1889.46,0.00,12386.46,NULL,'issued','2026-11-08 16:41:24');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(191) NOT NULL,
  `attempts` int(10) unsigned DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ip_email` (`ip_address`,`email`),
  KEY `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `variant_name` varchar(200) DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`),
  KEY `fk_oi_product` (`product_id`),
  KEY `fk_oi_variant` (`variant_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_oi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,5,NULL,'Anker Prime 67W GaN Wall Charger',NULL,'ANK-GAN-67W',3499.00,1,3499.00),(2,2,8,NULL,'boAt Stone 352 Bluetooth Speaker',NULL,'BOAT-ST-352',1499.00,1,1499.00),(3,3,4,NULL,'Google Pixel 8 Pro',NULL,'GP8P-BASE',98999.00,3,296997.00),(4,4,4,NULL,'Google Pixel 8 Pro',NULL,'GP8P-BASE',98999.00,3,296997.00),(5,5,5,NULL,'Anker Prime 67W GaN Wall Charger',NULL,'ANK-GAN-67W',3499.00,3,10497.00),(6,6,5,NULL,'Anker Prime 67W GaN Wall Charger',NULL,'ANK-GAN-67W',3499.00,3,10497.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_osh_order` (`order_id`),
  KEY `fk_osh_user` (`changed_by`),
  CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_osh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_history`
--

LOCK TABLES `order_status_history` WRITE;
/*!40000 ALTER TABLE `order_status_history` DISABLE KEYS */;
INSERT INTO `order_status_history` VALUES (1,3,NULL,'pending','Order placed successfully by customer.',4,'2026-11-08 15:44:38'),(2,3,'pending','cancelled','Cancelled by customer.',4,'2026-11-08 15:44:38'),(3,4,NULL,'pending','Order placed successfully by customer.',4,'2026-11-08 15:56:40'),(4,4,'pending','cancelled','Cancelled by customer.',4,'2026-11-08 15:56:40'),(5,5,NULL,'pending','Order placed successfully by customer.',4,'2026-11-08 15:58:35'),(6,5,'pending','cancelled','Cancelled by customer.',4,'2026-11-08 15:58:35'),(7,6,NULL,'pending','Order placed successfully by customer.',4,'2026-11-08 16:41:24'),(8,6,'pending','cancelled','Cancelled by customer.',4,'2026-11-08 16:41:24');
/*!40000 ALTER TABLE `order_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `shipping_address_id` int(10) unsigned DEFAULT NULL,
  `billing_address_id` int(10) unsigned DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `coupon_id` int(10) unsigned DEFAULT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('razorpay','cod','pos_cash','pos_card','pos_upi') NOT NULL DEFAULT 'razorpay',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','processing','shipped','delivered','cancelled','returned') NOT NULL DEFAULT 'pending',
  `shipping_notes` text DEFAULT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  UNIQUE KEY `uk_orders_razorpay_order` (`razorpay_order_id`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`order_status`),
  KEY `idx_orders_payment_status` (`payment_status`),
  KEY `fk_orders_coupon` (`coupon_id`),
  KEY `fk_orders_ship_addr` (`shipping_address_id`),
  KEY `fk_orders_bill_addr` (`billing_address_id`),
  KEY `idx_orders_created_at` (`created_at`),
  KEY `idx_orders_pay_created` (`payment_status`,`created_at`),
  KEY `idx_orders_status_created` (`order_status`,`created_at`),
  CONSTRAINT `fk_orders_bill_addr` FOREIGN KEY (`billing_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_ship_addr` FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ORD-2026-1001',4,1,1,3499.00,349.90,1,566.83,0.00,3715.93,'razorpay','paid','delivered',NULL,'order_N123456789abc',NULL,'2026-10-31 16:18:25','2026-11-05 16:18:25'),(2,'ORD-2026-1002',5,2,2,1499.00,0.00,NULL,269.82,50.00,1818.82,'cod','paid','shipped',NULL,NULL,NULL,'2026-11-03 16:18:25','2026-11-05 16:18:25'),(3,'ORD-202611-580B0',4,1,1,296997.00,0.00,NULL,53459.46,0.00,350456.46,'cod','pending','cancelled','QA Automated Test Order',NULL,NULL,'2026-11-08 15:44:38','2026-11-08 15:44:38'),(4,'ORD-202611-6A368',4,1,1,296997.00,0.00,NULL,53459.46,0.00,350456.46,'cod','pending','cancelled','QA Automated Test Order',NULL,NULL,'2026-11-08 15:56:40','2026-11-08 15:56:40'),(5,'ORD-202611-46C15',4,1,1,10497.00,0.00,NULL,1889.46,0.00,12386.46,'cod','pending','cancelled','QA Automated Test Order',NULL,NULL,'2026-11-08 15:58:35','2026-11-08 15:58:35'),(6,'ORD-202611-71D5A',4,1,1,10497.00,0.00,NULL,1889.46,0.00,12386.46,'cod','pending','cancelled','QA Automated Test Order',NULL,NULL,'2026-11-08 16:41:24','2026-11-08 16:41:24');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned DEFAULT NULL,
  `repair_id` int(10) unsigned DEFAULT NULL,
  `pos_sale_id` int(10) unsigned DEFAULT NULL,
  `transaction_ref` varchar(100) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `gateway` enum('razorpay','cash','card','upi') NOT NULL DEFAULT 'razorpay',
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `status` enum('pending','captured','failed','refunded') NOT NULL DEFAULT 'pending',
  `gateway_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gateway_response`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_ref` (`transaction_ref`),
  KEY `idx_pay_order` (`order_id`),
  KEY `idx_pay_repair` (`repair_id`),
  KEY `idx_pay_pos` (`pos_sale_id`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_pos` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `module` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_dashboard','View ERP Dashboard','dashboard','2026-11-05 16:18:25'),(2,'manage_products','Manage Products & Variants','catalog','2026-11-05 16:18:25'),(3,'manage_inventory','Manage Stock & Adjustments','inventory','2026-11-05 16:18:25'),(4,'manage_suppliers','Manage Suppliers & Purchases','purchases','2026-11-05 16:18:25'),(5,'manage_orders','Manage Customer Orders','orders','2026-11-05 16:18:25'),(6,'manage_repairs','Manage All Repairs & Estimates','repairs','2026-11-05 16:18:25'),(7,'technician_repairs','Access Technician Workbench','repairs','2026-11-05 16:18:25'),(8,'use_pos','Operate POS Cash Register','pos','2026-11-05 16:18:25'),(9,'view_reports','View Sales & Financial Reports','reports','2026-11-05 16:18:25'),(10,'manage_staff','Manage Staff & Roles','staff','2026-11-05 16:18:25'),(11,'manage_settings','Manage System Settings','settings','2026-11-05 16:18:25');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_sale_items`
--

DROP TABLE IF EXISTS `pos_sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_sale_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pos_sale_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `variant_name` varchar(200) DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 18.00,
  `total_price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psi_sale` (`pos_sale_id`),
  KEY `fk_psi_prod` (`product_id`),
  KEY `fk_psi_variant` (`variant_id`),
  CONSTRAINT `fk_psi_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_psi_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_sale_items`
--

LOCK TABLES `pos_sale_items` WRITE;
/*!40000 ALTER TABLE `pos_sale_items` DISABLE KEYS */;
INSERT INTO `pos_sale_items` VALUES (1,1,10,NULL,'Spigen Tough Armor Case for iPhone 15 Pro',NULL,'SPG-TA-IP15P',1899.00,1,18.00,1899.00);
/*!40000 ALTER TABLE `pos_sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_sales`
--

DROP TABLE IF EXISTS `pos_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `cashier_id` int(10) unsigned NOT NULL,
  `customer_name` varchar(150) DEFAULT 'Walk-in Customer',
  `customer_phone` varchar(20) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','card','upi','split') NOT NULL DEFAULT 'cash',
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `change_returned` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('completed','returned','voided') NOT NULL DEFAULT 'completed',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_pos_cashier` (`cashier_id`),
  KEY `idx_pos_created_at` (`created_at`),
  KEY `idx_pos_status_created` (`status`,`created_at`),
  CONSTRAINT `fk_pos_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_sales`
--

LOCK TABLES `pos_sales` WRITE;
/*!40000 ALTER TABLE `pos_sales` DISABLE KEYS */;
INSERT INTO `pos_sales` VALUES (1,'POS-2026-5001',1,'Walk-in Customer (Rajesh)','9900112233',1899.00,341.82,0.00,2240.82,'cash',2500.00,259.18,'completed',NULL,'2026-11-04 16:18:25');
/*!40000 ALTER TABLE `pos_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `review_text` text NOT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reviews_product` (`product_id`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_review_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
INSERT INTO `product_reviews` VALUES (1,1,4,5,'Phenomenal Phone & Build Quality!','The Natural Titanium feels extraordinarily light compared to 14 Pro Max. The 5x camera lens is crazy sharp.',1,'approved','2026-11-05 16:18:25'),(2,5,4,5,'Best GaN Charger Ever','Charges my iPhone and MacBook together without heating up. Super portable size.',1,'approved','2026-11-05 16:18:25');
/*!40000 ALTER TABLE `product_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `sku` varchar(100) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `variant_name` varchar(200) NOT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes`)),
  `additional_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `idx_variant_product` (`product_id`),
  CONSTRAINT `fk_variant_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (1,1,'IPH15PM-256-NAT','8901234567911','256GB - Natural Titanium','{\"storage\": \"256GB\", \"color\": \"Natural Titanium\"}',0.00,'iphone15_natural.jpg',1,'2026-11-05 16:18:25'),(2,1,'IPH15PM-512-NAT','8901234567912','512GB - Natural Titanium','{\"storage\": \"512GB\", \"color\": \"Natural Titanium\"}',20000.00,'iphone15_natural.jpg',1,'2026-11-05 16:18:25'),(3,1,'IPH15PM-256-BLK','8901234567913','256GB - Black Titanium','{\"storage\": \"256GB\", \"color\": \"Black Titanium\"}',0.00,'iphone15_black.jpg',1,'2026-11-05 16:18:25'),(4,2,'SMS24U-256-GRY','8901234567914','256GB - Titanium Gray','{\"storage\": \"256GB\", \"color\": \"Titanium Gray\"}',0.00,'s24_gray.jpg',1,'2026-11-05 16:18:25'),(5,2,'SMS24U-512-GRY','8901234567915','512GB - Titanium Gray','{\"storage\": \"512GB\", \"color\": \"Titanium Gray\"}',10000.00,'s24_gray.jpg',1,'2026-11-05 16:18:25'),(6,3,'OP12-256-BLK','8901234567916','256GB / 12GB - Silky Black','{\"storage\": \"256GB\", \"ram\": \"12GB\", \"color\": \"Silky Black\"}',0.00,'op12_black.jpg',1,'2026-11-05 16:18:25'),(7,3,'OP12-512-GRN','8901234567917','512GB / 16GB - Flowy Emerald','{\"storage\": \"512GB\", \"ram\": \"16GB\", \"color\": \"Flowy Emerald\"}',5000.00,'op12_emerald.jpg',1,'2026-11-05 16:18:25');
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `brand_id` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `short_desc` varchar(500) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `thumbnail` varchar(255) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `has_variants` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `warranty_months` int(10) unsigned DEFAULT 12,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `idx_prod_category` (`category_id`),
  KEY `idx_prod_brand` (`brand_id`),
  KEY `idx_prod_featured` (`is_featured`),
  KEY `idx_prod_active` (`is_active`),
  KEY `idx_prod_active_feat_id` (`is_active`,`is_featured`,`id`),
  KEY `idx_prod_active_cat_id` (`is_active`,`category_id`,`id`),
  KEY `idx_prod_active_brand_id` (`is_active`,`brand_id`,`id`),
  KEY `idx_prod_active_price` (`is_active`,`base_price`),
  CONSTRAINT `fk_prod_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,1,'Apple iPhone 15 Pro Max','apple-iphone-15-pro-max','IPH15PM-BASE','8901234567890','A3106','Forged in titanium, Super Retina XDR display with ProMotion, A17 Pro Bionic chip, and 5x optical zoom camera.','<p>iPhone 15 Pro Max is the first iPhone to feature an aerospace-grade titanium design, using the same alloy that spacecraft use for missions to Mars. The A17 Pro chip brings next-level gaming performance and camera capabilities.</p>','{\"display\": \"6.7-inch Super Retina XDR OLED\", \"processor\": \"A17 Pro\", \"camera\": \"48MP Main + 12MP Ultra-wide + 12MP 5x Telephoto\", \"battery\": \"4422 mAh\", \"os\": \"iOS 17\"}','iphone15promax.jpg',NULL,159900.00,149900.00,125000.00,1,1,1,12,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(2,1,2,'Samsung Galaxy S24 Ultra 5G','samsung-galaxy-s24-ultra','SMS24U-BASE','8901234567891','SM-S928B','Galaxy AI is here. Titanium exterior, 200MP Quad Telephoto camera with 100x Space Zoom, and built-in S Pen.','<p>Meet Galaxy S24 Ultra, the ultimate form of Galaxy Ultra with a new titanium exterior and a 6.8-inch flat display. Powered by Snapdragon 8 Gen 3 for Galaxy with integrated AI search and live translate.</p>','{\"display\": \"6.8-inch Dynamic AMOLED 2X, 120Hz\", \"processor\": \"Snapdragon 8 Gen 3 for Galaxy\", \"camera\": \"200MP + 50MP + 12MP + 10MP\", \"battery\": \"5000 mAh 45W Fast Charging\", \"spen\": \"Integrated S Pen\"}','s24ultra.jpg',NULL,134999.00,129999.00,110000.00,1,1,1,12,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(3,1,4,'OnePlus 12 5G','oneplus-12-5g','OP12-BASE','8901234567892','CPH2573','Snapdragon 8 Gen 3, 4th Gen Hasselblad Camera, 2K 120Hz ProXDR display with 5400 nits peak brightness.','<p>The OnePlus 12 redefines smooth performance with cutting-edge hardware, revolutionary Trinity Engine, and 100W SUPERVOOC ultra-fast flash charging that hits 100% in 26 minutes.</p>','{\"display\": \"6.82-inch 2K ProXDR 120Hz AMOLED\", \"processor\": \"Snapdragon 8 Gen 3\", \"camera\": \"50MP Sony LYT-808 + 64MP 3x Periscope + 48MP UW\", \"battery\": \"5400 mAh with 100W Wired + 50W Wireless\"}','oneplus12.jpg',NULL,69999.00,64999.00,54000.00,1,1,1,12,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(4,1,3,'Google Pixel 8 Pro','google-pixel-8-pro','GP8P-BASE','8901234567893','GC3VE','Google Tensor G3, advanced Google AI, Pro camera system with Best Take and Magic Editor.','<p>Pixel 8 Pro is engineered by Google with state-of-the-art photography features, real-time audio translation, temperature sensor, and guaranteed 7 years of Android OS and security updates.</p>','{\"display\": \"6.7-inch Super Actua LTPO OLED 120Hz\", \"processor\": \"Google Tensor G3\", \"camera\": \"50MP Octa PD + 48MP UW + 48MP 5x Telephoto\", \"battery\": \"5050 mAh\"}','pixel8pro.jpg',NULL,106999.00,98999.00,85000.00,0,1,1,12,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(5,2,8,'Anker Prime 67W GaN Wall Charger','anker-prime-67w-gan-charger','ANK-GAN-67W','8901234567894','A2669','3-Port ultra-compact high-speed wall charger with PowerIQ 4.0 for smartphones and laptops.','<p>Charge 3 devices simultaneously at ultra-fast speeds. Powered by Anker GaNPrime technology with active temperature monitoring 3 million times per day.</p>','{\"ports\": \"2x USB-C + 1x USB-A\", \"output\": \"67W Max Total\", \"technology\": \"GaNPrime + ActiveShield 2.0\"}','anker_67w.jpg',NULL,3999.00,3499.00,2200.00,0,1,1,24,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(6,2,1,'Apple 20W USB-C Power Adapter','apple-20w-usb-c-power-adapter','APL-20W-PWR','8901234567895','MHJE3HN/A','Original Apple 20W USB-C power adapter offering fast, efficient charging at home or in the office.','<p>Compatible with any USB-C enabled device. Pair with iPhone 8 or later for fast charging—50% battery in around 30 minutes.</p>','{\"wattage\": \"20W\", \"connector\": \"USB-C\", \"compatibility\": \"iPhone, iPad, AirPods\"}','apple_20w.jpg',NULL,1900.00,1699.00,1200.00,0,0,1,12,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(7,3,7,'boAt Airdopes 141 ANC TWS Earbuds','boat-airdopes-141-anc-tws','BOAT-AD-141','8901234567896','AD141ANC','Active Noise Cancellation up to 32dB, 42 hours playback, ENx quad mics, and ASAP Charge.','<p>Block out unwanted ambient rumble with up to 32dB ANC. Enjoy crystal clear calls with quad-mic environmental noise cancellation and beast low-latency gaming mode.</p>','{\"driver\": \"10mm dynamic drivers\", \"anc\": \"Up to 32dB Active Noise Cancellation\", \"battery\": \"42 Hours total with case\", \"ip_rating\": \"IPX5 Water Resistant\"}','boat_airdopes141.jpg',NULL,2990.00,1699.00,950.00,0,1,1,12,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(8,4,7,'boAt Stone 352 Bluetooth Speaker','boat-stone-352-portable-speaker','BOAT-ST-352','8901234567897','Stone352','10W RMS immersive stereo sound, IPX7 waterproof, 12 hours playback with Type-C charging.','<p>Take your music anywhere with rugged IPX7 waterproof engineering. Features TWS pairing to connect two Stone 352 units for punchy stereo sound.</p>','{\"output\": \"10W RMS\", \"battery\": \"Up to 12 Hours\", \"connectivity\": \"Bluetooth 5.0, AUX, TF Card\", \"waterproof\": \"IPX7\"}','boat_stone352.jpg',NULL,3490.00,1499.00,800.00,0,0,1,12,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(9,5,2,'Samsung Galaxy Watch 6 Classic (47mm)','samsung-galaxy-watch-6-classic','SM-W6-47MM','8901234567898','SM-R960','Iconic rotating bezel, sapphire crystal glass, advanced sleep coaching, ECG, and blood pressure monitoring.','<p>The smartwatch that knows you best. Timeless design with a 15% thinner rotating bezel and larger screen, packed with body composition analysis and cardiac health sensors.</p>','{\"display\": \"1.5-inch Super AMOLED Sapphire\", \"bezel\": \"Physical Rotating Bezel\", \"sensors\": \"BioActive Sensor (Optical Heart + ECG + BIA)\", \"battery\": \"425 mAh\"}','galaxy_watch6.jpg',NULL,36999.00,29999.00,24000.00,0,1,1,12,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(10,6,9,'Spigen Tough Armor Case for iPhone 15 Pro','spigen-tough-armor-iphone-15-pro','SPG-TA-IP15P','8901234567899','ACS06540','Extreme dual-layer drop protection with Air Cushion Technology and reinforced kickstand.','<p>Built to withstand anything thrown at it. Engineered with impact foam interior, shock-absorbent TPU, and durable polycarbonate shell. Certified Mil-grade protection.</p>','{\"material\": \"TPU + Polycarbonate + Extreme Impact Foam\", \"features\": \"Built-in Kickstand, Air Cushion Corner Pockets\", \"wireless_charging\": \"Compatible\"}','spigen_tougharmor.jpg',NULL,2499.00,1899.00,750.00,0,0,1,24,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(11,7,9,'Spigen Glas.tR EZ Fit Tempered Glass (2-Pack)','spigen-glastr-ezfit-tempered-glass','SPG-GLAS-EZ','8901234567900','AGL06580','9H hardness tempered glass with auto-alignment installation tray for bubble-free application.','<p>Includes an innovative auto-alignment installation tray that makes applying screen protectors effortless and perfectly centered without bubbles or dust.</p>','{\"hardness\": \"9H Hardness\", \"coating\": \"Oleophobic anti-fingerprint\", \"package\": \"2 Pieces with Auto-Align Tray\"}','spigen_glass.jpg',NULL,1699.00,1299.00,400.00,0,0,1,6,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(12,8,8,'Anker 325 Power Bank 20000mAh (PowerCore)','anker-325-powerbank-20000mah','ANK-PB-20000','8901234567901','A1286','Ultra-high 20000mAh capacity power bank with PowerIQ high-speed charging and dual output.','<p>Massive 20,000mAh capacity provides over 4 full charges for iPhone 14 or Galaxy S23. MultiProtect safety system ensures complete protection for you and your devices.</p>','{\"capacity\": \"20,000 mAh / 74Wh\", \"inputs\": \"USB-C + Micro-USB\", \"outputs\": \"2x USB-A PowerIQ Ports\", \"safety\": \"MultiProtect Surge and Temp Control\"}','anker_20000.jpg',NULL,3299.00,2499.00,1500.00,0,1,1,18,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(13,9,1,'Original iPhone 15 Pro OLED Screen Assembly','original-iphone-15-pro-oled-assembly','REP-DISP-IP15P','8901234567902','OEM-DISP-15P','Genuine OEM Super Retina XDR 120Hz ProMotion display module with digitizer and ambient flex.','<p>Brand new original display replacement part for certified iPhone 15 Pro screen repairs. True Tone and ProMotion calibrated.</p>','{\"type\": \"Super Retina XDR OLED\", \"resolution\": \"2556 x 1179 pixels at 460 ppi\", \"quality\": \"Original OEM Grade\"}','spare_iphone15_screen.jpg',NULL,19500.00,19500.00,14000.00,0,0,1,6,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(14,9,2,'Original Samsung S24 Ultra 5000mAh Battery','original-samsung-s24-ultra-battery','REP-BAT-S24U','8901234567903','EB-BS928ABY','Original Samsung factory replacement lithium-ion polymer battery cell with adhesive pull tabs.','<p>Genuine Samsung replacement battery providing original battery life and peak 45W fast charging performance.</p>','{\"capacity\": \"5000 mAh (Typical)\", \"voltage\": \"3.88V\", \"part_number\": \"GH82-32865A\"}','spare_s24_battery.jpg',NULL,3200.00,3200.00,1800.00,0,0,1,6,'2026-11-05 16:18:25','2026-11-05 16:18:25');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `total_cost` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pi_purchase` (`purchase_id`),
  KEY `fk_pi_product` (`product_id`),
  KEY `fk_pi_variant` (`variant_id`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_pi_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `purchase_number` varchar(50) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','partial','pending') DEFAULT 'paid',
  `status` enum('draft','received','cancelled') NOT NULL DEFAULT 'draft',
  `received_at` timestamp NULL DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_number` (`purchase_number`),
  KEY `idx_purchase_supplier` (`supplier_id`),
  KEY `idx_purchase_status` (`status`),
  KEY `fk_purchase_receiver` (`received_by`),
  KEY `fk_purchase_creator` (`created_by`),
  CONSTRAINT `fk_purchase_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_purchase_receiver` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `refunds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `gateway_refund_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','processed','failed') NOT NULL DEFAULT 'pending',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_refund_payment` (`payment_id`),
  KEY `fk_ref_order` (`order_id`),
  KEY `fk_ref_creator` (`created_by`),
  CONSTRAINT `fk_ref_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ref_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ref_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_estimates`
--

DROP TABLE IF EXISTS `repair_estimates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_estimates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repair_id` int(10) unsigned NOT NULL,
  `diagnostic_notes` text NOT NULL,
  `spare_parts_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `labour_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_estimate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','approved','rejected') NOT NULL DEFAULT 'draft',
  `sent_at` timestamp NULL DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_re_repair` (`repair_id`),
  CONSTRAINT `fk_re_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_estimates`
--

LOCK TABLES `repair_estimates` WRITE;
/*!40000 ALTER TABLE `repair_estimates` DISABLE KEYS */;
INSERT INTO `repair_estimates` VALUES (1,1,'Front OLED shattered; touch digitizer defective. Replaced with OEM 120Hz display module and restored water-resistant gasket.',19500.00,2500.00,500.00,1000.00,22500.00,'approved','2026-11-02 16:18:25','2026-11-03 16:18:25','2026-11-05 16:18:25'),(2,3,'Requires OEM OLED Display Replacement',4500.00,1500.00,200.00,1080.00,6880.00,'approved','2026-11-08 15:46:44','2026-11-08 15:46:44','2026-11-08 15:46:44'),(3,4,'Requires OEM OLED Display Replacement',4500.00,1500.00,200.00,1080.00,6880.00,'approved','2026-11-08 15:56:58','2026-11-08 15:56:58','2026-11-08 15:56:58'),(4,5,'Requires OEM OLED Display Replacement',4500.00,1500.00,200.00,1080.00,6880.00,'approved','2026-11-08 15:57:15','2026-11-08 15:57:15','2026-11-08 15:57:15'),(5,6,'Requires OEM OLED Display Replacement',4500.00,1500.00,200.00,1080.00,6880.00,'approved','2026-11-08 15:58:35','2026-11-08 15:58:35','2026-11-08 15:58:35'),(6,7,'Requires OEM OLED Display Replacement',4500.00,1500.00,200.00,1080.00,6880.00,'approved','2026-11-08 16:41:25','2026-11-08 16:41:25','2026-11-08 16:41:25');
/*!40000 ALTER TABLE `repair_estimates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_history`
--

DROP TABLE IF EXISTS `repair_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `repair_id` int(10) unsigned NOT NULL,
  `status_from` varchar(50) DEFAULT NULL,
  `status_to` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rh_repair` (`repair_id`),
  KEY `fk_rh_user` (`updated_by`),
  CONSTRAINT `fk_rh_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rh_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_history`
--

LOCK TABLES `repair_history` WRITE;
/*!40000 ALTER TABLE `repair_history` DISABLE KEYS */;
INSERT INTO `repair_history` VALUES (1,3,NULL,'received','Device repair ticket submitted by customer.',NULL,4,'2026-11-08 15:46:43'),(2,3,'received','received','Assigned to Vikram Sharma',NULL,1,'2026-11-08 15:46:43'),(3,3,'received','estimate_sent','Diagnostic estimate sent to customer',NULL,2,'2026-11-08 15:46:44'),(4,3,'estimate_sent','in_progress','Customer approved the estimate',NULL,4,'2026-11-08 15:46:44'),(5,3,'in_progress','completed','Repair completed and QA passed',NULL,2,'2026-11-08 15:46:44'),(6,4,NULL,'received','Device repair ticket submitted by customer.',NULL,4,'2026-11-08 15:56:58'),(7,4,'received','received','Assigned to Vikram Sharma',NULL,1,'2026-11-08 15:56:58'),(8,4,'received','estimate_sent','Diagnostic estimate sent to customer',NULL,2,'2026-11-08 15:56:58'),(9,4,'estimate_sent','in_progress','Customer approved the estimate',NULL,4,'2026-11-08 15:56:58'),(10,4,'in_progress','completed','Repair completed and QA passed',NULL,2,'2026-11-08 15:56:58'),(11,5,NULL,'received','Device repair ticket submitted by customer.',NULL,4,'2026-11-08 15:57:15'),(12,5,'received','received','Assigned to Vikram Sharma',NULL,1,'2026-11-08 15:57:15'),(13,5,'received','estimate_sent','Diagnostic estimate sent to customer',NULL,2,'2026-11-08 15:57:15'),(14,5,'estimate_sent','estimate_approved','Customer approved the estimate',NULL,4,'2026-11-08 15:57:15'),(15,5,'in_progress','completed','Repair completed and QA passed',NULL,2,'2026-11-08 15:57:15'),(16,6,NULL,'received','Device repair ticket submitted by customer.',NULL,4,'2026-11-08 15:58:35'),(17,6,'received','received','Assigned to Vikram Sharma',NULL,1,'2026-11-08 15:58:35'),(18,6,'received','estimate_sent','Diagnostic estimate sent to customer',NULL,2,'2026-11-08 15:58:35'),(19,6,'estimate_sent','estimate_approved','Customer approved the estimate',NULL,4,'2026-11-08 15:58:35'),(20,6,'in_progress','completed','Repair completed and QA passed',NULL,2,'2026-11-08 15:58:35'),(21,7,NULL,'received','Device repair ticket submitted by customer.',NULL,4,'2026-11-08 16:41:25'),(22,7,'received','received','Assigned to Vikram Sharma',NULL,1,'2026-11-08 16:41:25'),(23,7,'received','estimate_sent','Diagnostic estimate sent to customer',NULL,2,'2026-11-08 16:41:25'),(24,7,'estimate_sent','estimate_approved','Customer approved the estimate',NULL,4,'2026-11-08 16:41:25'),(25,7,'in_progress','completed','Repair completed and QA passed',NULL,2,'2026-11-08 16:41:25');
/*!40000 ALTER TABLE `repair_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_parts`
--

DROP TABLE IF EXISTS `repair_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repair_parts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repair_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `part_name` varchar(200) NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `added_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rp_repair` (`repair_id`),
  KEY `fk_rp_prod` (`product_id`),
  KEY `fk_rp_variant` (`variant_id`),
  KEY `fk_rp_user` (`added_by`),
  CONSTRAINT `fk_rp_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rp_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_user` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rp_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_parts`
--

LOCK TABLES `repair_parts` WRITE;
/*!40000 ALTER TABLE `repair_parts` DISABLE KEYS */;
INSERT INTO `repair_parts` VALUES (1,1,13,NULL,'Original iPhone 15 Pro OLED Screen Assembly',1,14000.00,19500.00,2,'2026-11-05 16:18:25'),(2,3,4,NULL,'Google Pixel 8 Pro',1,74899.30,106999.00,2,'2026-11-08 15:46:44'),(3,4,4,NULL,'Google Pixel 8 Pro',1,74899.30,106999.00,2,'2026-11-08 15:56:58'),(4,5,4,NULL,'Google Pixel 8 Pro',1,74899.30,106999.00,2,'2026-11-08 15:57:15'),(5,6,4,NULL,'Google Pixel 8 Pro',1,74899.30,106999.00,2,'2026-11-08 15:58:35'),(6,7,4,NULL,'Google Pixel 8 Pro',1,74899.30,106999.00,2,'2026-11-08 16:41:25');
/*!40000 ALTER TABLE `repair_parts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repairs`
--

DROP TABLE IF EXISTS `repairs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repairs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repair_ticket_number` varchar(50) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(191) NOT NULL,
  `device_brand` varchar(100) NOT NULL,
  `device_model` varchar(100) NOT NULL,
  `device_imei_serial` varchar(100) DEFAULT NULL,
  `device_password_pin` varchar(50) DEFAULT NULL,
  `issue_type` enum('screen_damage','battery_replacement','charging_port','water_damage','camera_issue','speaker_mic','motherboard_repair','software_os','other') NOT NULL,
  `issue_description` text NOT NULL,
  `condition_notes` varchar(255) DEFAULT NULL,
  `device_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_images`)),
  `service_mode` enum('pickup','shop_visit') NOT NULL DEFAULT 'shop_visit',
  `pickup_address` text DEFAULT NULL,
  `assigned_technician_id` int(10) unsigned DEFAULT NULL,
  `estimated_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `advance_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `final_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('received','diagnosis_pending','estimate_sent','estimate_approved','in_repair','waiting_parts','qa_testing','ready_for_delivery','completed','cancelled') NOT NULL DEFAULT 'received',
  `customer_approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `customer_approval_at` timestamp NULL DEFAULT NULL,
  `priority` enum('normal','urgent') NOT NULL DEFAULT 'normal',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_ticket_number` (`repair_ticket_number`),
  KEY `idx_repair_user` (`user_id`),
  KEY `idx_repair_status` (`status`),
  KEY `idx_repair_tech` (`assigned_technician_id`),
  KEY `idx_repairs_created_at` (`created_at`),
  KEY `idx_repairs_status_created` (`status`,`created_at`),
  CONSTRAINT `fk_rep_tech` FOREIGN KEY (`assigned_technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repairs`
--

LOCK TABLES `repairs` WRITE;
/*!40000 ALTER TABLE `repairs` DISABLE KEYS */;
INSERT INTO `repairs` VALUES (1,'REP-2026-8001',4,'John Doe','9876543213','john@example.com','Apple','iPhone 15 Pro','356789102938475',NULL,'screen_damage','Accidental drop cracked the front glass. Display shows vertical lines.',NULL,NULL,'shop_visit',NULL,2,22500.00,2000.00,22500.00,0.00,20500.00,'in_repair','approved',NULL,'urgent',NULL,'2026-11-02 16:18:25','2026-11-05 16:18:25'),(2,'REP-2026-8002',5,'Priya Patel','9876543214','priya@example.com','Samsung','Galaxy S24 Ultra','359871234567890',NULL,'battery_replacement','Battery draining fast from 80% to 15% within 3 hours. Needs original replacement.',NULL,NULL,'pickup',NULL,2,4500.00,0.00,4500.00,0.00,4500.00,'diagnosis_pending','pending',NULL,'normal',NULL,'2026-11-04 16:18:25','2026-11-05 16:18:25'),(3,'REP-202611-44A3C',4,'John Doe','9876543213','john@example.com','Apple','iPhone 14 Pro','356789012345678',NULL,'','Screen cracked after drop, touch partially working','Minor scratches on back glass',NULL,'shop_visit',NULL,2,6880.00,0.00,6880.00,0.00,6880.00,'completed','approved',NULL,'',NULL,'2026-11-08 15:46:43','2026-11-08 15:46:44'),(4,'REP-202611-8048E',4,'John Doe','9876543213','john@example.com','Apple','iPhone 14 Pro','356789012345678',NULL,'','Screen cracked after drop, touch partially working','Minor scratches on back glass',NULL,'shop_visit',NULL,2,6880.00,0.00,6880.00,0.00,6880.00,'completed','approved',NULL,'',NULL,'2026-11-08 15:56:58','2026-11-08 15:56:58'),(5,'REP-202611-E9448',4,'John Doe','9876543213','john@example.com','Apple','iPhone 14 Pro','356789012345678',NULL,'','Screen cracked after drop, touch partially working','Minor scratches on back glass',NULL,'shop_visit',NULL,2,6880.00,0.00,6880.00,0.00,6880.00,'completed','approved',NULL,'',NULL,'2026-11-08 15:57:15','2026-11-08 15:57:15'),(6,'REP-202611-43665',4,'John Doe','9876543213','john@example.com','Apple','iPhone 14 Pro','356789012345678',NULL,'','Screen cracked after drop, touch partially working','Minor scratches on back glass',NULL,'shop_visit',NULL,2,6880.00,0.00,6880.00,0.00,6880.00,'completed','approved',NULL,'',NULL,'2026-11-08 15:58:35','2026-11-08 15:58:35'),(7,'REP-202611-2BF15',4,'John Doe','9876543213','john@example.com','Apple','iPhone 14 Pro','356789012345678',NULL,'','Screen cracked after drop, touch partially working','Minor scratches on back glass',NULL,'shop_visit',NULL,2,6880.00,0.00,6880.00,0.00,6880.00,'completed','approved',NULL,'',NULL,'2026-11-08 16:41:25','2026-11-08 16:41:25');
/*!40000 ALTER TABLE `repairs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,1),(2,1,2),(3,1,3),(4,1,4),(5,1,5),(6,1,6),(7,1,7),(8,1,8),(9,1,9),(10,1,10),(11,1,11),(12,2,7);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Super Administrator','Full access to ERP, financial reports, settings, and staff management','2026-11-05 16:18:25'),(2,'technician','Repair Technician','Inspect, diagnose, update repair tickets, and allocate parts','2026-11-05 16:18:25'),(3,'customer','Registered Customer','Browse store, place orders, book device repairs, track tickets','2026-11-05 16:18:25');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `is_autoload` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_group` (`setting_group`,`is_autoload`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'general','site_name','Adkine Mobile Shopee',1,'2026-11-06 17:56:35'),(2,'general','site_tagline','Smartphones, Certified Chip-Level Repairs & Genuine Accessories',1,'2026-11-06 17:56:35'),(3,'general','site_email','support@apexmobilehub.com',1,'2026-11-05 16:18:25'),(4,'general','site_phone','+91 98765 43210',1,'2026-11-05 16:18:25'),(5,'general','store_address','Showroom 101, Electronics City Tech Park, Bengaluru, Karnataka 560100',1,'2026-11-05 16:18:25'),(6,'general','store_gstin','29AABCA1234A1Z5',1,'2026-11-05 16:18:25'),(7,'general','currency_code','INR',1,'2026-11-05 16:18:25'),(8,'general','currency_symbol','₹',1,'2026-11-05 16:18:25'),(9,'tax','gst_rate_percent','18.00',1,'2026-11-05 16:18:25'),(10,'tax','cgst_rate_percent','9.00',1,'2026-11-05 16:18:25'),(11,'tax','sgst_rate_percent','9.00',1,'2026-11-05 16:18:25'),(12,'payment','razorpay_key_id','rzp_test_YourKeyHere',1,'2026-11-05 16:18:25'),(13,'payment','razorpay_key_secret','YourSecretKeyHere',0,'2026-11-05 16:18:25'),(14,'mail','smtp_host','smtp.gmail.com',1,'2026-11-05 16:18:25'),(15,'mail','smtp_port','587',1,'2026-11-05 16:18:25'),(16,'mail','smtp_encryption','tls',1,'2026-11-05 16:18:25'),(17,'mail','smtp_username','alerts@apexmobilehub.com',0,'2026-11-05 16:18:25'),(18,'mail','smtp_password','',0,'2026-11-05 16:18:25'),(19,'mail','mail_from_name','Adkine Mobile Shopee',1,'2026-11-06 17:56:35'),(20,'repair','diagnosis_fee','350.00',1,'2026-11-05 16:18:25'),(21,'repair','pickup_charge','150.00',1,'2026-11-05 16:18:25');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `movement_type` enum('purchase_in','pos_sale','online_order','repair_use','damage_out','adjustment_add','adjustment_sub','return_in') NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost_per_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_id` varchar(100) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sm_prod` (`product_id`),
  KEY `idx_sm_variant` (`variant_id`),
  KEY `idx_sm_type` (`movement_type`),
  KEY `fk_sm_user` (`created_by`),
  CONSTRAINT `fk_sm_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sm_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,1,1,'purchase_in',12,125000.00,'PO-INIT-001','purchase','Opening ERP stock initialization',1,'2026-11-05 16:18:25'),(2,1,2,'purchase_in',8,140000.00,'PO-INIT-001','purchase','Opening ERP stock initialization',1,'2026-11-05 16:18:25'),(3,2,4,'purchase_in',14,110000.00,'PO-INIT-001','purchase','Opening ERP stock initialization',1,'2026-11-05 16:18:25'),(4,5,NULL,'purchase_in',35,2200.00,'PO-INIT-002','purchase','Opening ERP stock initialization',1,'2026-11-05 16:18:25'),(5,13,NULL,'purchase_in',4,14000.00,'PO-INIT-003','purchase','Initial spare parts allocation',1,'2026-11-05 16:18:25'),(6,4,NULL,'online_order',-3,85000.00,'ORD-202611-580B0','system','Order fulfillment #ORD-202611-580B0',4,'2026-11-08 15:44:38'),(7,4,NULL,'',3,106999.00,'ORD-202611-580B0','system','Restocked on cancellation',4,'2026-11-08 15:44:38'),(8,4,NULL,'',-1,85000.00,'3','system','Allocated for repair #3',2,'2026-11-08 15:46:44'),(9,1,NULL,'',5,1000.00,'PO-QA-1794153105','system','Received PO PO-QA-1794153105',1,'2026-11-08 15:51:45'),(10,1,NULL,'',-5,125000.00,'PO-QA-1794153105','system','Reverting QA PO stock',1,'2026-11-08 15:51:45'),(11,4,NULL,'online_order',-3,85000.00,'ORD-202611-6A368','system','Order fulfillment #ORD-202611-6A368',4,'2026-11-08 15:56:40'),(12,4,NULL,'',3,106999.00,'ORD-202611-6A368','system','Restocked on cancellation',4,'2026-11-08 15:56:41'),(13,4,NULL,'',-1,85000.00,'4','system','Allocated for repair #4',2,'2026-11-08 15:56:58'),(14,4,NULL,'',-1,85000.00,'5','system','Allocated for repair #5',2,'2026-11-08 15:57:15'),(15,5,NULL,'online_order',-3,2200.00,'ORD-202611-46C15','system','Order fulfillment #ORD-202611-46C15',4,'2026-11-08 15:58:35'),(16,5,NULL,'',3,3999.00,'ORD-202611-46C15','system','Restocked on cancellation',4,'2026-11-08 15:58:35'),(17,4,NULL,'',-1,85000.00,'6','system','Allocated for repair #6',2,'2026-11-08 15:58:35'),(18,1,NULL,'',5,1000.00,'PO-QA-1794153516','system','Received PO PO-QA-1794153516',1,'2026-11-08 15:58:36'),(19,1,NULL,'',-5,125000.00,'PO-QA-1794153516','system','Reverting QA PO stock',1,'2026-11-08 15:58:36'),(20,5,NULL,'online_order',-3,2200.00,'ORD-202611-71D5A','system','Order fulfillment #ORD-202611-71D5A',4,'2026-11-08 16:41:24'),(21,5,NULL,'',3,3999.00,'ORD-202611-71D5A','system','Restocked on cancellation',4,'2026-11-08 16:41:25'),(22,4,NULL,'',-1,85000.00,'7','system','Allocated for repair #7',2,'2026-11-08 16:41:25'),(23,1,NULL,'',5,1000.00,'PO-QA-1794156085','system','Received PO PO-QA-1794156085',1,'2026-11-08 16:41:25'),(24,1,NULL,'',-5,125000.00,'PO-QA-1794156085','system','Reverting QA PO stock',1,'2026-11-08 16:41:25');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `address` text DEFAULT NULL,
  `gstin` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Sanjay Gupta','Apex Global Electronics Wholesalers','orders@apexwholesalers.in','+91 9820011223','Shop 42, Electronic Market, Lamington Road, Mumbai 400007','27AABCU9603R1ZM',1,'2026-11-05 16:18:25'),(2,'Ramesh Nair','QuickFix OEM Spare Parts Hub','supplies@quickfixspares.com','+91 9845099887','Plot 18, Industrial Estate, Peenya, Bengaluru 560058','29AAACQ1234P1Z4',1,'2026-11-05 16:18:25'),(3,'Kavita Joshi','Prime Mobile Accessories Direct','wholesale@primeaccessories.in','+91 9811223344','Building 12, Nehru Place, New Delhi 110019','07AABCP5678M1Z8',1,'2026-11-05 16:18:25');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_otps`
--

DROP TABLE IF EXISTS `user_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_otps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `otp_code` varchar(255) NOT NULL,
  `type` enum('registration','password_reset','login') NOT NULL,
  `attempts` tinyint(3) unsigned DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_otps_lookup` (`email`,`type`,`is_used`),
  KEY `fk_user_otps_user` (`user_id`),
  CONSTRAINT `fk_user_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_otps`
--

LOCK TABLES `user_otps` WRITE;
/*!40000 ALTER TABLE `user_otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','pending_otp','suspended') DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_status` (`status`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Store Administrator','admin@mobileshop.com','9876543210','$2y$10$/Mib3OwL1Md5kK2Cd3fwiuzkZ/0dFCXNgcKm.Pby0YCT5SeUj6DVO','active','2026-11-05 16:18:25',NULL,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(2,2,'Vikram Sharma (Technician)','tech@mobileshop.com','9876543211','$2y$10$QqhxqR4SBJ3QncZn9QnmCuGeYMfRiq9jbUYtE2xibiImrl//4yoEy','active','2026-11-05 16:18:25',NULL,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(4,3,'John Doe','john@example.com','9876543213','$2y$10$63rIOT00.p8pCbZyNPJ20.rh37SYItxKSpwGnRyCNm6bhkGqXE6Eu','active','2026-11-05 16:18:25',NULL,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(5,3,'Priya Patel','priya@example.com','9876543214','$2y$10$63rIOT00.p8pCbZyNPJ20.rh37SYItxKSpwGnRyCNm6bhkGqXE6Eu','active','2026-11-05 16:18:25',NULL,'2026-11-05 16:18:25','2026-11-05 16:18:25'),(6,3,'Sonu Radhakrishnan Bhosle','sbhosle1011@gmail.com','+918080987767','$2y$10$ZhLxYM127LbXrSmXqmo5cudO661F1mZ3Z4x.c9VZb.F4cCcq3pPD6','active','2026-11-06 16:36:15',NULL,'2026-11-06 16:36:15','2026-11-06 17:50:49');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_events`
--

DROP TABLE IF EXISTS `webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webhook_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gateway` varchar(50) NOT NULL DEFAULT 'razorpay',
  `event_id` varchar(100) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `payload` longtext NOT NULL,
  `status` enum('processed','ignored','failed') NOT NULL DEFAULT 'processed',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_id` (`event_id`),
  KEY `idx_we_gateway_type` (`gateway`,`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_events`
--

LOCK TABLES `webhook_events` WRITE;
/*!40000 ALTER TABLE `webhook_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'mobileshop2'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-08 22:19:38
