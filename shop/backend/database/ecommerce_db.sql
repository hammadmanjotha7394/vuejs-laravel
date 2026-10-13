-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ecommerce_db
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `properties_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties_json`)),
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_action_index` (`action`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `line1` varchar(255) NOT NULL,
  `line2` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'US',
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_user_id_foreign` (`user_id`),
  CONSTRAINT `addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `back_in_stock_subscriptions`
--

DROP TABLE IF EXISTS `back_in_stock_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `back_in_stock_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `back_in_stock_subscriptions_product_id_foreign` (`product_id`),
  KEY `back_in_stock_subscriptions_variant_id_foreign` (`variant_id`),
  KEY `back_in_stock_subscriptions_email_index` (`email`),
  CONSTRAINT `back_in_stock_subscriptions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `back_in_stock_subscriptions_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `back_in_stock_subscriptions`
--

LOCK TABLES `back_in_stock_subscriptions` WRITE;
/*!40000 ALTER TABLE `back_in_stock_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `back_in_stock_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Kanso Studio','kanso-studio',NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(2,'Atelier Nord','atelier-nord',NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(3,'Hygge Living','hygge-living',NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(4,'Verdant Craft','verdant-craft',NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(5,'Common Thread','common-thread',NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
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
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_items_cart_id_foreign` (`cart_id`),
  KEY `cart_items_product_id_foreign` (`product_id`),
  KEY `cart_items_variant_id_foreign` (`variant_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `coupon_code` varchar(255) DEFAULT NULL,
  `abandoned_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carts_token_unique` (`token`),
  KEY `carts_user_id_foreign` (`user_id`),
  CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  KEY `categories_sort_order_index` (`sort_order`),
  KEY `categories_is_active_index` (`is_active`),
  KEY `categories_is_featured_index` (`is_featured`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Apparel','apparel','Mindfully tailored organic garments made for comfort and daily longevity.','https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=600&q=80','Shirt',1,1,1,'Apparel - Mindfully Sourced Collection','Mindfully tailored organic garments made for comfort and daily longevity.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(2,1,'Tops & Shirts','tops-shirts','Crisp linen and organic combed cotton essentials.','https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=600&q=80','Shirt',1,1,0,'Tops & Shirts - Boutique','Crisp linen and organic combed cotton essentials.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(3,1,'Trousers & Pants','trousers-pants','Pleated chinos, relaxed denim, and drawstring trousers.','https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=600&q=80','Shirt',2,1,0,'Trousers & Pants - Boutique','Pleated chinos, relaxed denim, and drawstring trousers.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(4,1,'Knitwear','knitwear','Warm merino wool and alpaca cardigans.','https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=600&q=80','Shirt',3,1,0,'Knitwear - Boutique','Warm merino wool and alpaca cardigans.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(5,1,'Outerwear','outerwear','Weather-ready overshirts and minimalist trench coats.','https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=600&q=80','Shirt',4,1,0,'Outerwear - Boutique','Weather-ready overshirts and minimalist trench coats.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(6,NULL,'Home & Living','home-living','Tactile ceramic objects, warm lighting, and calm home accents.','https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=600&q=80','Home',2,1,1,'Home & Living - Mindfully Sourced Collection','Tactile ceramic objects, warm lighting, and calm home accents.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(7,6,'Ceramics & Vases','ceramics-vases','Wheel-thrown stoneware and artisanal decorative vessels.','https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=600&q=80','Home',1,1,0,'Ceramics & Vases - Boutique','Wheel-thrown stoneware and artisanal decorative vessels.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(8,6,'Lighting','lighting','Dimmable warm glow lamps and brass sconces.','https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=600&q=80','Home',2,1,0,'Lighting - Boutique','Dimmable warm glow lamps and brass sconces.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(9,6,'Bedding','bedding','Pre-washed French flax linen duvet sets.','https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=600&q=80','Home',3,1,0,'Bedding - Boutique','Pre-washed French flax linen duvet sets.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(10,6,'Throw Pillows & Blankets','throw-pillows-blankets','Handwoven waffle cotton throws and wool cushions.','https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=600&q=80','Home',4,1,0,'Throw Pillows & Blankets - Boutique','Handwoven waffle cotton throws and wool cushions.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(11,NULL,'Bags & Leather','bags-leather','Vegetable-tanned leather goods crafted to age gracefully with patina.','https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=600&q=80','Briefcase',3,1,1,'Bags & Leather - Mindfully Sourced Collection','Vegetable-tanned leather goods crafted to age gracefully with patina.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(12,11,'Tote Bags','tote-bags','Heavyweight organic canvas and full-grain leather totes.','https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=600&q=80','Briefcase',1,1,0,'Tote Bags - Boutique','Heavyweight organic canvas and full-grain leather totes.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(13,11,'Backpacks & Rucksacks','backpacks-rucksacks','Structured commuter backpacks with padded laptop sleeves.','https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=600&q=80','Briefcase',2,1,0,'Backpacks & Rucksacks - Boutique','Structured commuter backpacks with padded laptop sleeves.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(14,11,'Wallets & Cardholders','wallets-cardholders','Slim bifold wallets and tactile pebble-grain card sleeves.','https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=600&q=80','Briefcase',3,1,0,'Wallets & Cardholders - Boutique','Slim bifold wallets and tactile pebble-grain card sleeves.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(15,NULL,'Footwear','footwear','Ergonomic soles and understated silhouettes designed for city walking.','https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=600&q=80','Footprints',4,1,1,'Footwear - Mindfully Sourced Collection','Ergonomic soles and understated silhouettes designed for city walking.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(16,15,'Everyday Sneakers','everyday-sneakers','Minimal low-tops with natural rubber cupsoles.','https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=600&q=80','Footprints',1,1,0,'Everyday Sneakers - Boutique','Minimal low-tops with natural rubber cupsoles.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(17,15,'Leather Loafers','leather-loafers','Hand-stitched penny loafers with cushioned insoles.','https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=600&q=80','Footprints',2,1,0,'Leather Loafers - Boutique','Hand-stitched penny loafers with cushioned insoles.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(18,15,'Mules & Slides','mules-slides','Slip-on leather mules for effortless indoor and outdoor ease.','https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=600&q=80','Footprints',3,1,0,'Mules & Slides - Boutique','Slip-on leather mules for effortless indoor and outdoor ease.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(19,NULL,'Kitchen & Dining','kitchen-dining','Functional culinary tools and durable handcrafted tableware.','https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80','Utensils',5,1,1,'Kitchen & Dining - Mindfully Sourced Collection','Functional culinary tools and durable handcrafted tableware.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(20,19,'Drinkware & Mugs','drinkware-mugs','Hand-glazed ceramic mugs and heat-resistant borosilicate glasses.','https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80','Utensils',1,1,0,'Drinkware & Mugs - Boutique','Hand-glazed ceramic mugs and heat-resistant borosilicate glasses.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(21,19,'Dinner Plates & Bowls','dinner-plates-bowls','Matte stoneware sets designed for daily gatherings.','https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80','Utensils',2,1,0,'Dinner Plates & Bowls - Boutique','Matte stoneware sets designed for daily gatherings.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(22,19,'Table Linens','table-linens','Stonewashed linen napkins and neutral table runners.','https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80','Utensils',3,1,0,'Table Linens - Boutique','Stonewashed linen napkins and neutral table runners.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(23,NULL,'Wellness & Care','wellness-care','Plant-powered self-care goods, clean scents, and bath rituals.','https://images.unsplash.com/photo-1608248597359-59752b047514?auto=format&fit=crop&w=600&q=80','Sparkles',6,1,1,'Wellness & Care - Mindfully Sourced Collection','Plant-powered self-care goods, clean scents, and bath rituals.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(24,23,'Botanical Candles','botanical-candles','Soy-wax candles infused with cedarwood, fig, and vetiver.','https://images.unsplash.com/photo-1608248597359-59752b047514?auto=format&fit=crop&w=600&q=80','Sparkles',1,1,0,'Botanical Candles - Boutique','Soy-wax candles infused with cedarwood, fig, and vetiver.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(25,23,'Organic Soaps & Washes','organic-soaps-washes','Cold-processed soaps with natural botanical oils.','https://images.unsplash.com/photo-1608248597359-59752b047514?auto=format&fit=crop&w=600&q=80','Sparkles',2,1,0,'Organic Soaps & Washes - Boutique','Cold-processed soaps with natural botanical oils.','2026-10-13 00:55:32','2026-10-13 00:55:32'),(26,23,'Room & Linen Sprays','room-linen-sprays','Subtle essential oil mist to refresh living spaces.','https://images.unsplash.com/photo-1608248597359-59752b047514?auto=format&fit=crop&w=600&q=80','Sparkles',3,1,0,'Room & Linen Sprays - Boutique','Subtle essential oil mist to refresh living spaces.','2026-10-13 00:55:33','2026-10-13 00:55:33'),(27,NULL,'Workspace & Paper','workspace-paper','Tools for focused thought, writing, and mindful desk organization.','https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?auto=format&fit=crop&w=600&q=80','BookOpen',7,1,1,'Workspace & Paper - Mindfully Sourced Collection','Tools for focused thought, writing, and mindful desk organization.','2026-10-13 00:55:33','2026-10-13 00:55:33'),(28,27,'Notebooks & Journals','notebooks-journals','Thread-bound notebooks with fountain-pen-friendly archival paper.','https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?auto=format&fit=crop&w=600&q=80','BookOpen',1,1,0,'Notebooks & Journals - Boutique','Thread-bound notebooks with fountain-pen-friendly archival paper.','2026-10-13 00:55:33','2026-10-13 00:55:33'),(29,27,'Writing Instruments','writing-instruments','Precision rollerball pens and solid brass mechanical pencils.','https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?auto=format&fit=crop&w=600&q=80','BookOpen',2,1,0,'Writing Instruments - Boutique','Precision rollerball pens and solid brass mechanical pencils.','2026-10-13 00:55:33','2026-10-13 00:55:33'),(30,27,'Desk Organizers','desk-organizers','Solid oak trays and magnetic cable holders.','https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?auto=format&fit=crop&w=600&q=80','BookOpen',3,1,0,'Desk Organizers - Boutique','Solid oak trays and magnetic cable holders.','2026-10-13 00:55:33','2026-10-13 00:55:33'),(31,NULL,'Travel & Utility','travel-utility','Compact and weather-resistant gear built for quiet weekend getaways.','https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80','Compass',8,1,1,'Travel & Utility - Mindfully Sourced Collection','Compact and weather-resistant gear built for quiet weekend getaways.','2026-10-13 00:55:33','2026-10-13 00:55:33'),(32,31,'Weekender Duffels','weekender-duffels','Water-repellent duffels with dedicated footwear compartments.','https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80','Compass',1,1,0,'Weekender Duffels - Boutique','Water-repellent duffels with dedicated footwear compartments.','2026-10-13 00:55:33','2026-10-13 00:55:33'),(33,31,'Insulated Flasks','insulated-flasks','Double-walled stainless steel thermal bottles.','https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80','Compass',2,1,0,'Insulated Flasks - Boutique','Double-walled stainless steel thermal bottles.','2026-10-13 00:55:33','2026-10-13 00:55:33'),(34,31,'Dopp Kits & Pouches','dopp-kits-pouches','Spill-resistant toiletry organizers with brass zippers.','https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80','Compass',3,1,0,'Dopp Kits & Pouches - Boutique','Spill-resistant toiletry organizers with brass zippers.','2026-10-13 00:55:33','2026-10-13 00:55:33');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_conversations`
--

DROP TABLE IF EXISTS `chat_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_token` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `visitor_name` varchar(255) DEFAULT NULL,
  `visitor_email` varchar(255) DEFAULT NULL,
  `current_page` varchar(500) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `assigned_admin_id` bigint(20) unsigned DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `visitor_last_seen_at` timestamp NULL DEFAULT NULL,
  `unread_admin_count` int(11) NOT NULL DEFAULT 0,
  `unread_visitor_count` int(11) NOT NULL DEFAULT 0,
  `internal_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_conversations_user_id_foreign` (`user_id`),
  KEY `chat_conversations_assigned_admin_id_foreign` (`assigned_admin_id`),
  KEY `chat_conversations_visitor_token_index` (`visitor_token`),
  KEY `chat_conversations_status_index` (`status`),
  KEY `chat_conversations_last_message_at_index` (`last_message_at`),
  CONSTRAINT `chat_conversations_assigned_admin_id_foreign` FOREIGN KEY (`assigned_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chat_conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_conversations`
--

LOCK TABLES `chat_conversations` WRITE;
/*!40000 ALTER TABLE `chat_conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `sender_type` enum('visitor','admin') NOT NULL,
  `sender_id` bigint(20) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_conversation_id_foreign` (`conversation_id`),
  KEY `chat_messages_sender_id_foreign` (`sender_id`),
  KEY `chat_messages_sender_type_index` (`sender_type`),
  CONSTRAINT `chat_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_messages_email_index` (`email`),
  KEY `contact_messages_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `type` enum('percent','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_subtotal` decimal(10,2) DEFAULT NULL,
  `min_qty` int(10) unsigned DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `per_user_limit` int(11) NOT NULL DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`),
  KEY `coupons_expires_at_index` (`expires_at`),
  KEY `coupons_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'WELCOME10','percent',10.00,50.00,NULL,500,12,1,'2026-10-03 00:55:36','2027-10-13 00:55:36',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(2,'SPRING15','fixed',15.00,100.00,NULL,200,8,1,'2026-10-08 00:55:36','2027-04-13 00:55:36',1,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `to` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext DEFAULT NULL,
  `template_key` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'sent',
  `error` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_logs_to_index` (`to`),
  KEY `email_logs_template_key_index` (`template_key`),
  KEY `email_logs_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES (1,'welcome','Welcome to Boutique','<div style=\"font-family:sans-serif;line-height:1.6;color:#111827;\"><h2>Welcome, {{customer_name}}</h2><p>Thank you for joining our community of mindful living. Your account is ready.</p></div>',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(2,'order_confirmation','Order Confirmation #{{order_number}}','<div style=\"font-family:sans-serif;line-height:1.6;color:#111827;\"><h2>Thank you for your order, {{customer_name}}!</h2><p>We are preparing your items with care. Your order number is <strong>#{{order_number}}</strong>.</p><p>Total: {{order_total}}</p></div>',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(3,'order_shipped','Your order #{{order_number}} is on its way','<div style=\"font-family:sans-serif;line-height:1.6;color:#111827;\"><h2>Your package has shipped</h2><p>Track your shipment here: <a href=\"{{tracking_link}}\">{{tracking_number}}</a></p></div>',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(4,'password_reset','Reset Your Boutique Password','<div style=\"font-family:sans-serif;line-height:1.6;color:#111827;\"><h2>Password Reset Request</h2><p>Click below to securely reset your password:</p><p><a href=\"{{reset_url}}\">Reset Password</a></p></div>',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(5,'new_order_admin','New Order Alert #{{order_number}}','<div style=\"font-family:sans-serif;line-height:1.6;color:#111827;\"><h2>New Order Received</h2><p>Order #{{order_number}} from {{customer_email}} totaling {{order_total}}.</p></div>',1,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
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
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
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
-- Table structure for table `hero_slides`
--

DROP TABLE IF EXISTS `hero_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hero_slides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `button_text` varchar(255) DEFAULT NULL,
  `button_url` varchar(255) DEFAULT NULL,
  `image_desktop` varchar(255) NOT NULL,
  `image_mobile` varchar(255) DEFAULT NULL,
  `text_position` varchar(20) NOT NULL DEFAULT 'left',
  `overlay_opacity` decimal(3,2) NOT NULL DEFAULT 0.20,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hero_slides_sort_order_index` (`sort_order`),
  KEY `hero_slides_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hero_slides`
--

LOCK TABLES `hero_slides` WRITE;
/*!40000 ALTER TABLE `hero_slides` DISABLE KEYS */;
INSERT INTO `hero_slides` VALUES (1,'Mindfully Crafted Goods','Minimalist apparel and everyday homeware designed to outlast fleeting trends.','Explore Collection','/shop','https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1600&q=80','https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=800&q=80','left',0.25,1,1,NULL,NULL,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(2,'Pure Organic Linen','Breathable, tactile textures tailored for effortless daily comfort.','Shop Apparel','/category/apparel','https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1600&q=80','https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=800&q=80','left',0.25,2,1,NULL,NULL,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(3,'Tactile Ceramic Objects','Wheel-thrown stoneware and artisanal lighting to ground your living sanctuary.','Discover Homeware','/category/home-living','https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=1600&q=80','https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=800&q=80','center',0.20,3,1,NULL,NULL,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `hero_slides` ENABLE KEYS */;
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
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `label` varchar(255) NOT NULL,
  `type` varchar(30) NOT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `featured_title` varchar(255) DEFAULT NULL,
  `featured_button_text` varchar(255) DEFAULT NULL,
  `featured_button_url` varchar(255) DEFAULT NULL,
  `badge_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `column_index` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_menu_id_foreign` (`menu_id`),
  KEY `menu_items_parent_id_foreign` (`parent_id`),
  KEY `menu_items_sort_order_index` (`sort_order`),
  CONSTRAINT `menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,1,NULL,'Apparel','category',NULL,'/category/apparel',0,NULL,NULL,NULL,NULL,'New',1,1,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(2,1,1,'Tops & Shirts','category',NULL,'/category/tops-shirts',0,NULL,NULL,NULL,NULL,NULL,1,1,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(3,1,1,'Trousers & Pants','category',NULL,'/category/trousers-pants',0,NULL,NULL,NULL,NULL,NULL,2,1,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(4,1,NULL,'Home & Living','category',NULL,'/category/home-living',0,NULL,NULL,NULL,NULL,NULL,2,2,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(5,1,NULL,'Bags & Leather','category',NULL,'/category/bags-leather',0,NULL,NULL,NULL,NULL,NULL,3,3,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(6,1,NULL,'About','page',NULL,'/about',0,NULL,NULL,NULL,NULL,NULL,4,4,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(7,2,NULL,'About Us','page',NULL,'/about',0,NULL,NULL,NULL,NULL,NULL,1,0,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(8,2,NULL,'FAQ','page',NULL,'/faq',0,NULL,NULL,NULL,NULL,NULL,2,0,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(9,2,NULL,'Shipping & Delivery','page',NULL,'/shipping',0,NULL,NULL,NULL,NULL,NULL,3,0,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(10,2,NULL,'Returns & Exchanges','page',NULL,'/returns',0,NULL,NULL,NULL,NULL,NULL,4,0,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(11,2,NULL,'Privacy Policy','page',NULL,'/privacy',0,NULL,NULL,NULL,NULL,NULL,5,0,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(12,2,NULL,'Terms of Service','page',NULL,'/terms',0,NULL,NULL,NULL,NULL,NULL,6,0,1,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES (1,'Main Navigation','main',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(2,'Footer Navigation','footer',1,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_12_013645_create_personal_access_tokens_table',1),(5,'2026_10_12_013650_create_permission_tables',1),(6,'2026_10_12_020001_create_settings_table',1),(7,'2026_10_12_020002_create_categories_table',1),(8,'2026_10_12_020003_create_brands_table',1),(9,'2026_10_12_020004_create_tags_table',1),(10,'2026_10_12_020005_create_products_table',1),(11,'2026_10_12_020006_create_product_options_and_variants_tables',1),(12,'2026_10_12_020007_create_product_images_table',1),(13,'2026_10_12_020008_create_product_sections_and_specs_tables',1),(14,'2026_10_12_020009_create_reviews_table',1),(15,'2026_10_12_020010_create_carts_and_wishlists_tables',1),(16,'2026_10_12_020011_create_addresses_table',1),(17,'2026_10_12_020012_create_shipping_and_coupons_tables',1),(18,'2026_10_12_020013_create_orders_tables',1),(19,'2026_10_12_020014_create_marketing_and_cms_tables',1),(20,'2026_10_12_020015_create_communication_and_logs_tables',1),(21,'2026_10_12_092430_add_featured_card_fields_to_menu_items_table',1),(22,'2026_10_12_093542_add_advanced_fields_to_products_and_images_tables',1),(23,'2026_10_12_133615_add_coupon_code_to_carts_table',1),(24,'2026_10_13_080001_add_admin_notes_and_refund_amount_columns',1),(25,'2026_10_13_100001_add_tracking_counts_to_popups_table',1),(26,'2026_10_13_110001_add_photos_and_reply_to_reviews_table',1),(27,'2026_10_13_120001_add_context_and_notes_to_chat_conversations_table',1),(28,'2026_10_13_130001_add_body_html_to_email_logs_table',1),(29,'2026_10_13_140001_create_step21_extra_features_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',2),(3,'App\\Models\\User',3),(6,'App\\Models\\User',4);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_subscribers_email_unique` (`email`),
  KEY `newsletter_subscribers_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `name_snapshot` varchar(255) NOT NULL,
  `sku_snapshot` varchar(255) DEFAULT NULL,
  `image_snapshot` varchar(255) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_variant_id_foreign` (`variant_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
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
  `order_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_status_history_order_id_foreign` (`order_id`),
  KEY `order_status_history_changed_by_foreign` (`changed_by`),
  CONSTRAINT `order_status_history_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_status_history_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_history`
--

LOCK TABLES `order_status_history` WRITE;
/*!40000 ALTER TABLE `order_status_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `status` enum('pending','paid','processing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `payment_status` varchar(255) NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `discount_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL,
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `coupon_code` varchar(255) DEFAULT NULL,
  `shipping_address_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`shipping_address_json`)),
  `billing_address_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`billing_address_json`)),
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `placed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_guest_email_index` (`guest_email`),
  KEY `orders_status_index` (`status`),
  KEY `orders_payment_status_index` (`payment_status`),
  KEY `orders_stripe_session_id_index` (`stripe_session_id`),
  KEY `orders_stripe_payment_intent_id_index` (`stripe_payment_intent_id`),
  KEY `orders_placed_at_index` (`placed_at`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_is_published_index` (`is_published`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'About Our Studio','about','<h2>Mindful Design for Daily Living</h2><p>Founded on principles of calm proportion, durable utility, and sustainable sourcing, Boutique collaborates directly with independent artisans across Europe and Japan. We prioritize timeless materials over seasonal cycles.</p>',1,'About Our Studio | Boutique','Discover our philosophy of thoughtful craftsmanship, honest materials, and sustainable design.','2026-10-13 00:55:36','2026-10-13 00:55:36'),(2,'Frequently Asked Questions','faq','<h2>Common Questions</h2><p><strong>Where do you manufacture?</strong> Our ceramic, linen, and leather goods are made in small workshops in Portugal, Spain, and Japan.</p><p><strong>How long does shipping take?</strong> Domestic orders arrive within 2–4 business days with carbon-neutral transit.</p>',1,'FAQ | Boutique Customer Care','Answers to questions regarding shipping, returns, sustainability, and product care.','2026-10-13 00:55:36','2026-10-13 00:55:36'),(3,'Shipping & Delivery','shipping','<h2>Domestic & International Shipping</h2><p>All items are carefully packaged in 100% recyclable, plastic-free boxes. Orders placed before 1 PM EST are dispatched the same business day.</p>',1,'Shipping & Delivery Information | Boutique','Reliable, carbon-neutral shipping rates and estimated delivery timelines.','2026-10-13 00:55:36','2026-10-13 00:55:36'),(4,'Returns & Guarantee','returns','<h2>Hassle-Free 30-Day Returns</h2><p>If you are not thoroughly satisfied with your purchase, you may initiate a return within 30 days of receipt in original, unworn condition.</p>',1,'Returns & Guarantee Policy | Boutique','Simple 30-day returns and store warranty details.','2026-10-13 00:55:36','2026-10-13 00:55:36'),(5,'Privacy Policy','privacy','<h2>Your Privacy Matters</h2><p>We respect your personal data and never sell or distribute your contact details to third parties. We strictly use cookies to enable secure sessions and optimize website speed.</p>',1,'Privacy Policy | Boutique','Our commitment to data privacy, secure cookie handling, and encrypted payment processing.','2026-10-13 00:55:36','2026-10-13 00:55:36'),(6,'Terms of Service','terms','<h2>Terms & Conditions</h2><p>By using Boutique, you agree to fair use, honest interactions, and standard commercial conditions under governing consumer protections.</p>',1,'Terms of Service | Boutique','Store terms of service, payment guarantees, and customer rights.','2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
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
  `created_at` timestamp NULL DEFAULT NULL,
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
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'products.view','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(2,'products.create','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(3,'products.update','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(4,'products.delete','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(5,'categories.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(6,'brands.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(7,'orders.view','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(8,'orders.update','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(9,'orders.refund','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(10,'finance.view','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(11,'reports.view','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(12,'settings.view','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(13,'settings.update','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(14,'stripe.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(15,'mail.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(16,'users.view','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(17,'users.create','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(18,'users.update','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(19,'roles.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(20,'reviews.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(21,'chat.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(22,'popups.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(23,'hero.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(24,'menus.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(25,'pages.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(26,'coupons.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(27,'shipping.manage','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(28,'activity.view','web','2026-10-13 00:55:31','2026-10-13 00:55:31');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `popups`
--

DROP TABLE IF EXISTS `popups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `popups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('image','content','newsletter','announcement') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `button_text` varchar(255) DEFAULT NULL,
  `button_url` varchar(255) DEFAULT NULL,
  `trigger` varchar(30) NOT NULL DEFAULT 'delay',
  `trigger_value` int(11) NOT NULL DEFAULT 5,
  `show_on_pages_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`show_on_pages_json`)),
  `frequency` varchar(30) NOT NULL DEFAULT 'once_per_session',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `views_count` int(10) unsigned NOT NULL DEFAULT 0,
  `clicks_count` int(10) unsigned NOT NULL DEFAULT 0,
  `signups_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `popups_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `popups`
--

LOCK TABLES `popups` WRITE;
/*!40000 ALTER TABLE `popups` DISABLE KEYS */;
INSERT INTO `popups` VALUES (1,'First Order Privilege','newsletter','Enjoy 10% off your first order','Subscribe to receive quiet reflections on sustainable craft, private preview invitations, and complimentary shipping vouchers.','https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=600&q=80','Subscribe','#','delay',6,NULL,'once_per_session',NULL,NULL,1,1,0,0,0,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `popups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `product_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `product_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `product_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_categories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,2),(2,2),(3,3),(4,4),(5,5),(6,7),(7,7),(8,8),(9,9),(10,10),(11,12),(12,13),(13,14),(14,14),(15,16),(16,17),(17,18),(18,20),(19,21),(20,22),(21,21),(22,24),(23,26),(24,25),(25,28),(26,29),(27,30),(28,32),(29,33),(30,34),(31,2),(32,3),(33,4),(34,7),(35,28),(36,10),(37,30),(38,20),(39,2),(40,12);
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_details_sections`
--

DROP TABLE IF EXISTS `product_details_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_details_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_open_by_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_details_sections_product_id_foreign` (`product_id`),
  CONSTRAINT `product_details_sections_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_details_sections`
--

LOCK TABLES `product_details_sections` WRITE;
/*!40000 ALTER TABLE `product_details_sections` DISABLE KEYS */;
INSERT INTO `product_details_sections` VALUES (1,1,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(2,1,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(3,1,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(4,2,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(5,2,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(6,2,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(7,3,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(8,3,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(9,3,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(10,4,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(11,4,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(12,4,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(13,5,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(14,5,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(15,5,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(16,6,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(17,6,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(18,6,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(19,7,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(20,7,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(21,7,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(22,8,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(23,8,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(24,8,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(25,9,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(26,9,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(27,9,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(28,10,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(29,10,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(30,10,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(31,11,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(32,11,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(33,11,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(34,12,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(35,12,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(36,12,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(37,13,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(38,13,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(39,13,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(40,14,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(41,14,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(42,14,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(43,15,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(44,15,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(45,15,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(46,16,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(47,16,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(48,16,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(49,17,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(50,17,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(51,17,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(52,18,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(53,18,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(54,18,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(55,19,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(56,19,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(57,19,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(58,20,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(59,20,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(60,20,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(61,21,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(62,21,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(63,21,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(64,22,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(65,22,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(66,22,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(67,23,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(68,23,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(69,23,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(70,24,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(71,24,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(72,24,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(73,25,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(74,25,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(75,25,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(76,26,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(77,26,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(78,26,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(79,27,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(80,27,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(81,27,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(82,28,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(83,28,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(84,28,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(85,29,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(86,29,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(87,29,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(88,30,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(89,30,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(90,30,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(91,31,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(92,31,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(93,31,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(94,32,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(95,32,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(96,32,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(97,33,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(98,33,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(99,33,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(100,34,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(101,34,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(102,34,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(103,35,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(104,35,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(105,35,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(106,36,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(107,36,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(108,36,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(109,37,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(110,37,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(111,37,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(112,38,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(113,38,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(114,38,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(115,39,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(116,39,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(117,39,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(118,40,'Materials & Craft','<p>Ethically harvested raw materials, certified non-toxic treatments, and artisan assembly ensuring decades of dependable utility.</p>',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(119,40,'Dimensions & Care','<p>Wipe clean with a damp, lint-free cloth or gentle hand wash. Avoid abrasive cleaners and prolonged direct sunlight.</p>',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(120,40,'Shipping & Returns','<p>Ships in plastic-free recycled paper packaging within 2 business days. 30-day hassle-free return guarantee.</p>',3,0,'2026-10-13 00:55:35','2026-10-13 00:55:35');
/*!40000 ALTER TABLE `product_details_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `variant_id` bigint(20) unsigned DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `thumb_path` varchar(255) DEFAULT NULL,
  `medium_path` varchar(255) DEFAULT NULL,
  `large_path` varchar(255) DEFAULT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  KEY `product_images_variant_id_foreign` (`variant_id`),
  KEY `product_images_sort_order_index` (`sort_order`),
  KEY `product_images_is_primary_index` (`is_primary`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_images_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,1,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=800&q=80','Organic French Linen Overshirt primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(2,1,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Organic French Linen Overshirt detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(3,2,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80','Heavyweight Pima Cotton Tee primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(4,2,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Heavyweight Pima Cotton Tee detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(5,3,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=800&q=80','Relaxed Pleated Chino Trouser primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(6,3,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Relaxed Pleated Chino Trouser detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(7,4,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?auto=format&fit=crop&w=800&q=80','Merino Wool Ribbed Cardigan primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(8,4,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Merino Wool Ribbed Cardigan detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(9,5,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1544022613-e87ca75a784a?auto=format&fit=crop&w=800&q=80','Waterproof City Mac Coat primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(10,5,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Waterproof City Mac Coat detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(11,6,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&w=800&q=80','Wheel-Thrown Stoneware Vase primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(12,6,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Wheel-Thrown Stoneware Vase detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(13,7,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1485955900006-10f4d324d411?auto=format&fit=crop&w=800&q=80','Matte Terracotta Fluted Planter primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(14,7,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Matte Terracotta Fluted Planter detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(15,8,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80','Brass Dimmer Table Lamp primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(16,8,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Brass Dimmer Table Lamp detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(17,9,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=800&q=80','Washed Belgian Linen Duvet Set primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(18,9,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Washed Belgian Linen Duvet Set detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(19,10,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=800&q=80','Waffle Weave Wool Throw Blanket primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(20,10,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Waffle Weave Wool Throw Blanket detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(21,11,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80','Vegetable-Tanned Daily Tote primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(22,11,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Vegetable-Tanned Daily Tote detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(23,12,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80','Waxed Canvas Commuter Rucksack primary view',1,1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(24,12,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Waxed Canvas Commuter Rucksack detail view',2,0,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(25,13,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=800&q=80','Pebbled Leather Bifold Wallet primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(26,13,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Pebbled Leather Bifold Wallet detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(27,14,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=800&q=80','Minimalist Card Sleeve primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(28,14,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Minimalist Card Sleeve detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(29,15,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1560769629-975ec94e6a86?auto=format&fit=crop&w=800&q=80','Court Low-Top Leather Sneaker primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(30,15,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Court Low-Top Leather Sneaker detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(31,16,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1533867617858-e7b97e060509?auto=format&fit=crop&w=800&q=80','Suede Hand-Stitched Penny Loafer primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(32,16,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Suede Hand-Stitched Penny Loafer detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(33,17,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?auto=format&fit=crop&w=800&q=80','Shearling-Lined Suede Mules primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(34,17,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Shearling-Lined Suede Mules detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(35,18,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=800&q=80','Speckled Ceramic Coffee Mug (Set of 2) primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(36,18,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Speckled Ceramic Coffee Mug (Set of 2) detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(37,19,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1574343635070-5b6d5108f9d0?auto=format&fit=crop&w=800&q=80','Handmade Stoneware Pasta Bowls (Set of 4) primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(38,19,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Handmade Stoneware Pasta Bowls (Set of 4) detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(39,20,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80','Stonewashed French Linen Napkins (Set of 4) primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(40,20,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Stonewashed French Linen Napkins (Set of 4) detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(41,21,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1584990347449-a6e4d0b1626f?auto=format&fit=crop&w=800&q=80','Cast Iron Skillet with Walnut Handle primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(42,21,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Cast Iron Skillet with Walnut Handle detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(43,22,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=800&q=80','Cedar & Wild Fig Botanical Candle primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(44,22,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Cedar & Wild Fig Botanical Candle detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(45,23,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1616949755610-8c9bbc08f138?auto=format&fit=crop&w=800&q=80','Hinoki Cypress Essential Oil Mist primary view',1,1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(46,23,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Hinoki Cypress Essential Oil Mist detail view',2,0,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(47,24,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1607006314645-0d4d7b578c77?auto=format&fit=crop&w=800&q=80','Cold-Pressed Botanical Bar Soap Trio primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(48,24,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Cold-Pressed Botanical Bar Soap Trio detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(49,25,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=800&q=80','Layflat Thread-Bound Daily Journal primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(50,25,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Layflat Thread-Bound Daily Journal detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(51,26,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?auto=format&fit=crop&w=800&q=80','Solid Brass Precision Ballpoint Pen primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(52,26,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Solid Brass Precision Ballpoint Pen detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(53,27,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=800&q=80','Solid Oak Modular Desk Organiser Tray primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(54,27,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Solid Oak Modular Desk Organiser Tray detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(55,28,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80','Overnight Canvas Weekender Duffle primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(56,28,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Overnight Canvas Weekender Duffle detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(57,29,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=800&q=80','Vacuum Insulated Steel Travel Tumbler (500ml) primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(58,29,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Vacuum Insulated Steel Travel Tumbler (500ml) detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(59,30,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80','Waterproof Zip Dopp Kit primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(60,30,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Waterproof Zip Dopp Kit detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(61,31,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=800&q=80','Organic Cotton Oxford Button-Down primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(62,31,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Organic Cotton Oxford Button-Down detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(63,32,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?auto=format&fit=crop&w=800&q=80','Textured Linen Summer Shorts primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(64,32,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Textured Linen Summer Shorts detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(65,33,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?auto=format&fit=crop&w=800&q=80','Chunky Cashmere Beanie Hat primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(66,33,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Chunky Cashmere Beanie Hat detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(67,34,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1563861826100-9cb868fdbe1c?auto=format&fit=crop&w=800&q=80','Scandi Minimalist Wall Clock primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(68,34,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Scandi Minimalist Wall Clock detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(69,35,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1532012164546-f432f2e3777f?auto=format&fit=crop&w=800&q=80','Archival Linen Photo Album primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(70,35,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Archival Linen Photo Album detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(71,36,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=800&q=80','Handwoven Seagrass Storage Basket primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(72,36,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Handwoven Seagrass Storage Basket detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(73,37,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80','Ergonomic Wooden Desk Riser primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(74,37,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Ergonomic Wooden Desk Riser detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(75,38,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1517668808822-9ebb02f2a0e6?auto=format&fit=crop&w=800&q=80','Matte Black Pour-Over Kettle (800ml) primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(76,38,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Matte Black Pour-Over Kettle (800ml) detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(77,39,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=800&q=80','French Terry Everyday Sweatshirt primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(78,39,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','French Terry Everyday Sweatshirt detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(79,40,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=800&q=80','Canvas Crossbody Utility Pouch primary view',1,1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(80,40,NULL,NULL,NULL,NULL,NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80','Canvas Crossbody Utility Pouch detail view',2,0,'2026-10-13 00:55:35','2026-10-13 00:55:35');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_option_types`
--

DROP TABLE IF EXISTS `product_option_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_option_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_option_types_product_id_foreign` (`product_id`),
  CONSTRAINT `product_option_types_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_option_types`
--

LOCK TABLES `product_option_types` WRITE;
/*!40000 ALTER TABLE `product_option_types` DISABLE KEYS */;
INSERT INTO `product_option_types` VALUES (1,1,'Color',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(2,2,'Color',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(3,3,'Color',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(4,4,'Color',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(5,5,'Color',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(6,9,'Color',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(7,11,'Color',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(8,12,'Color',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(9,13,'Color',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(10,14,'Color',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(11,15,'Color',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(12,16,'Color',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(13,17,'Color',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(14,18,'Color',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(15,20,'Color',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(16,25,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(17,28,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(18,29,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(19,30,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(20,31,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(21,32,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(22,33,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(23,39,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(24,40,'Color',1,'2026-10-13 00:55:35','2026-10-13 00:55:35');
/*!40000 ALTER TABLE `product_option_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_option_values`
--

DROP TABLE IF EXISTS `product_option_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_option_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_type_id` bigint(20) unsigned NOT NULL,
  `value` varchar(255) NOT NULL,
  `color_hex` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_option_values_option_type_id_foreign` (`option_type_id`),
  CONSTRAINT `product_option_values_option_type_id_foreign` FOREIGN KEY (`option_type_id`) REFERENCES `product_option_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_option_values`
--

LOCK TABLES `product_option_values` WRITE;
/*!40000 ALTER TABLE `product_option_values` DISABLE KEYS */;
INSERT INTO `product_option_values` VALUES (1,1,'Sage Green','#2F5D50','2026-10-13 00:55:33','2026-10-13 00:55:33'),(2,1,'Warm Sand','#D4C5B9','2026-10-13 00:55:33','2026-10-13 00:55:33'),(3,2,'Sage Green','#2F5D50','2026-10-13 00:55:33','2026-10-13 00:55:33'),(4,2,'Warm Sand','#D4C5B9','2026-10-13 00:55:33','2026-10-13 00:55:33'),(5,3,'Sage Green','#2F5D50','2026-10-13 00:55:33','2026-10-13 00:55:33'),(6,3,'Warm Sand','#D4C5B9','2026-10-13 00:55:33','2026-10-13 00:55:33'),(7,4,'Sage Green','#2F5D50','2026-10-13 00:55:33','2026-10-13 00:55:33'),(8,4,'Warm Sand','#D4C5B9','2026-10-13 00:55:33','2026-10-13 00:55:33'),(9,5,'Sage Green','#2F5D50','2026-10-13 00:55:33','2026-10-13 00:55:33'),(10,5,'Warm Sand','#D4C5B9','2026-10-13 00:55:33','2026-10-13 00:55:33'),(11,6,'Sage Green','#2F5D50','2026-10-13 00:55:33','2026-10-13 00:55:33'),(12,6,'Warm Sand','#D4C5B9','2026-10-13 00:55:33','2026-10-13 00:55:33'),(13,7,'Sage Green','#2F5D50','2026-10-13 00:55:33','2026-10-13 00:55:33'),(14,7,'Warm Sand','#D4C5B9','2026-10-13 00:55:33','2026-10-13 00:55:33'),(15,8,'Sage Green','#2F5D50','2026-10-13 00:55:33','2026-10-13 00:55:33'),(16,8,'Warm Sand','#D4C5B9','2026-10-13 00:55:33','2026-10-13 00:55:33'),(17,9,'Sage Green','#2F5D50','2026-10-13 00:55:34','2026-10-13 00:55:34'),(18,9,'Warm Sand','#D4C5B9','2026-10-13 00:55:34','2026-10-13 00:55:34'),(19,10,'Sage Green','#2F5D50','2026-10-13 00:55:34','2026-10-13 00:55:34'),(20,10,'Warm Sand','#D4C5B9','2026-10-13 00:55:34','2026-10-13 00:55:34'),(21,11,'Sage Green','#2F5D50','2026-10-13 00:55:34','2026-10-13 00:55:34'),(22,11,'Warm Sand','#D4C5B9','2026-10-13 00:55:34','2026-10-13 00:55:34'),(23,12,'Sage Green','#2F5D50','2026-10-13 00:55:34','2026-10-13 00:55:34'),(24,12,'Warm Sand','#D4C5B9','2026-10-13 00:55:34','2026-10-13 00:55:34'),(25,13,'Sage Green','#2F5D50','2026-10-13 00:55:34','2026-10-13 00:55:34'),(26,13,'Warm Sand','#D4C5B9','2026-10-13 00:55:34','2026-10-13 00:55:34'),(27,14,'Sage Green','#2F5D50','2026-10-13 00:55:34','2026-10-13 00:55:34'),(28,14,'Warm Sand','#D4C5B9','2026-10-13 00:55:34','2026-10-13 00:55:34'),(29,15,'Sage Green','#2F5D50','2026-10-13 00:55:34','2026-10-13 00:55:34'),(30,15,'Warm Sand','#D4C5B9','2026-10-13 00:55:34','2026-10-13 00:55:34'),(31,16,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(32,16,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35'),(33,17,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(34,17,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35'),(35,18,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(36,18,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35'),(37,19,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(38,19,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35'),(39,20,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(40,20,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35'),(41,21,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(42,21,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35'),(43,22,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(44,22,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35'),(45,23,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(46,23,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35'),(47,24,'Sage Green','#2F5D50','2026-10-13 00:55:35','2026-10-13 00:55:35'),(48,24,'Warm Sand','#D4C5B9','2026-10-13 00:55:35','2026-10-13 00:55:35');
/*!40000 ALTER TABLE `product_option_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_specs`
--

DROP TABLE IF EXISTS `product_specs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_specs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_specs_product_id_foreign` (`product_id`),
  CONSTRAINT `product_specs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_specs`
--

LOCK TABLES `product_specs` WRITE;
/*!40000 ALTER TABLE `product_specs` DISABLE KEYS */;
INSERT INTO `product_specs` VALUES (1,1,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(2,1,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(3,1,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(4,2,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(5,2,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(6,2,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(7,3,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(8,3,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(9,3,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(10,4,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(11,4,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(12,4,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(13,5,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(14,5,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(15,5,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(16,6,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(17,6,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(18,6,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(19,7,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(20,7,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(21,7,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(22,8,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(23,8,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(24,8,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(25,9,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(26,9,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(27,9,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(28,10,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(29,10,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(30,10,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(31,11,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(32,11,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(33,11,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(34,12,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(35,12,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(36,12,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:33','2026-10-13 00:55:33'),(37,13,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(38,13,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(39,13,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(40,14,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(41,14,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(42,14,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(43,15,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(44,15,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(45,15,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(46,16,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(47,16,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(48,16,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(49,17,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(50,17,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(51,17,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(52,18,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(53,18,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(54,18,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(55,19,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(56,19,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(57,19,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(58,20,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(59,20,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(60,20,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(61,21,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(62,21,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(63,21,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(64,22,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(65,22,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(66,22,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:34','2026-10-13 00:55:34'),(67,23,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(68,23,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(69,23,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(70,24,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(71,24,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(72,24,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(73,25,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(74,25,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(75,25,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(76,26,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(77,26,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(78,26,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(79,27,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(80,27,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(81,27,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(82,28,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(83,28,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(84,28,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(85,29,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(86,29,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(87,29,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(88,30,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(89,30,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(90,30,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(91,31,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(92,31,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(93,31,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(94,32,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(95,32,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(96,32,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(97,33,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(98,33,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(99,33,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(100,34,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(101,34,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(102,34,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(103,35,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(104,35,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(105,35,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(106,36,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(107,36,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(108,36,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(109,37,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(110,37,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(111,37,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(112,38,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(113,38,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(114,38,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(115,39,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(116,39,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(117,39,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(118,40,'Origin','Handcrafted in Portugal',1,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(119,40,'Material','Certified sustainable organic blend',2,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(120,40,'Packaging','100% recyclable post-consumer box',3,'2026-10-13 00:55:35','2026-10-13 00:55:35');
/*!40000 ALTER TABLE `product_specs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_tag`
--

DROP TABLE IF EXISTS `product_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_tag` (
  `product_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`tag_id`),
  KEY `product_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `product_tag_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_tag`
--

LOCK TABLES `product_tag` WRITE;
/*!40000 ALTER TABLE `product_tag` DISABLE KEYS */;
INSERT INTO `product_tag` VALUES (1,1),(1,6),(2,1),(2,2),(3,1),(3,4),(4,1),(4,2),(4,6),(5,1),(5,5),(5,7),(6,1),(6,4),(6,5),(7,1),(7,4),(8,3),(8,5),(8,7),(9,3),(9,4),(9,7),(10,4),(10,5),(10,8),(11,2),(11,3),(12,2),(12,6),(13,1),(13,3),(13,6),(14,2),(14,5),(15,1),(15,5),(15,7),(16,1),(16,2),(17,2),(17,5),(17,6),(18,5),(18,6),(18,8),(19,1),(19,3),(20,1),(20,7),(21,1),(21,2),(21,3),(22,4),(22,7),(22,8),(23,1),(23,6),(23,7),(24,2),(24,5),(25,7),(25,8),(26,2),(26,4),(26,5),(27,1),(27,5),(28,4),(28,5),(29,2),(29,5),(30,1),(30,2),(30,6),(31,5),(31,7),(32,6),(32,7),(33,4),(33,5),(33,8),(34,1),(34,2),(35,2),(35,3),(35,7),(36,2),(36,4),(37,1),(37,4),(37,6),(38,2),(38,6),(39,1),(39,5),(39,8),(40,1),(40,3),(40,4);
/*!40000 ALTER TABLE `product_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variant_option_values`
--

DROP TABLE IF EXISTS `product_variant_option_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variant_option_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` bigint(20) unsigned NOT NULL,
  `option_value_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `variant_option_unique` (`variant_id`,`option_value_id`),
  KEY `product_variant_option_values_option_value_id_foreign` (`option_value_id`),
  CONSTRAINT `product_variant_option_values_option_value_id_foreign` FOREIGN KEY (`option_value_id`) REFERENCES `product_option_values` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_variant_option_values_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variant_option_values`
--

LOCK TABLES `product_variant_option_values` WRITE;
/*!40000 ALTER TABLE `product_variant_option_values` DISABLE KEYS */;
INSERT INTO `product_variant_option_values` VALUES (1,1,1,NULL,NULL),(2,2,2,NULL,NULL),(3,3,3,NULL,NULL),(4,4,4,NULL,NULL),(5,5,5,NULL,NULL),(6,6,6,NULL,NULL),(7,7,7,NULL,NULL),(8,8,8,NULL,NULL),(9,9,9,NULL,NULL),(10,10,10,NULL,NULL),(11,11,11,NULL,NULL),(12,12,12,NULL,NULL),(13,13,13,NULL,NULL),(14,14,14,NULL,NULL),(15,15,15,NULL,NULL),(16,16,16,NULL,NULL),(17,17,17,NULL,NULL),(18,18,18,NULL,NULL),(19,19,19,NULL,NULL),(20,20,20,NULL,NULL),(21,21,21,NULL,NULL),(22,22,22,NULL,NULL),(23,23,23,NULL,NULL),(24,24,24,NULL,NULL),(25,25,25,NULL,NULL),(26,26,26,NULL,NULL),(27,27,27,NULL,NULL),(28,28,28,NULL,NULL),(29,29,29,NULL,NULL),(30,30,30,NULL,NULL),(31,31,31,NULL,NULL),(32,32,32,NULL,NULL),(33,33,33,NULL,NULL),(34,34,34,NULL,NULL),(35,35,35,NULL,NULL),(36,36,36,NULL,NULL),(37,37,37,NULL,NULL),(38,38,38,NULL,NULL),(39,39,39,NULL,NULL),(40,40,40,NULL,NULL),(41,41,41,NULL,NULL),(42,42,42,NULL,NULL),(43,43,43,NULL,NULL),(44,44,44,NULL,NULL),(45,45,45,NULL,NULL),(46,46,46,NULL,NULL),(47,47,47,NULL,NULL),(48,48,48,NULL,NULL);
/*!40000 ALTER TABLE `product_variant_option_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `image_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `option_values_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`option_values_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `product_variants_product_id_foreign` (`product_id`),
  KEY `product_variants_is_active_index` (`is_active`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (1,1,'PRD-001-SAGE',110.00,135.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(2,1,'PRD-001-SAND',110.00,135.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(3,2,'PRD-002-SAGE',42.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(4,2,'PRD-002-SAND',42.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(5,3,'PRD-003-SAGE',128.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(6,3,'PRD-003-SAND',128.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(7,4,'PRD-004-SAGE',165.00,195.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(8,4,'PRD-004-SAND',165.00,195.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(9,5,'PRD-005-SAGE',240.00,280.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(10,5,'PRD-005-SAND',240.00,280.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(11,9,'PRD-009-SAGE',210.00,245.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(12,9,'PRD-009-SAND',210.00,245.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(13,11,'PRD-011-SAGE',185.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(14,11,'PRD-011-SAND',185.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(15,12,'PRD-012-SAGE',175.00,210.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(16,12,'PRD-012-SAND',175.00,210.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:33','2026-10-13 00:55:33'),(17,13,'PRD-013-SAGE',65.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(18,13,'PRD-013-SAND',65.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(19,14,'PRD-014-SAGE',38.00,45.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(20,14,'PRD-014-SAND',38.00,45.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(21,15,'PRD-015-SAGE',135.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(22,15,'PRD-015-SAND',135.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(23,16,'PRD-016-SAGE',195.00,225.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(24,16,'PRD-016-SAND',195.00,225.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(25,17,'PRD-017-SAGE',115.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(26,17,'PRD-017-SAND',115.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(27,18,'PRD-018-SAGE',46.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(28,18,'PRD-018-SAND',46.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(29,20,'PRD-020-SAGE',36.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(30,20,'PRD-020-SAND',36.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:34','2026-10-13 00:55:34'),(31,25,'PRD-025-SAGE',28.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(32,25,'PRD-025-SAND',28.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(33,28,'PRD-028-SAGE',160.00,190.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(34,28,'PRD-028-SAND',160.00,190.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(35,29,'PRD-029-SAGE',38.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(36,29,'PRD-029-SAND',38.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(37,30,'PRD-030-SAGE',48.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(38,30,'PRD-030-SAND',48.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(39,31,'PRD-031-SAGE',95.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(40,31,'PRD-031-SAND',95.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(41,32,'PRD-032-SAGE',78.00,90.00,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(42,32,'PRD-032-SAND',78.00,90.00,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(43,33,'PRD-033-SAGE',65.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(44,33,'PRD-033-SAND',65.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(45,39,'PRD-039-SAGE',85.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(46,39,'PRD-039-SAND',85.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(47,40,'PRD-040-SAGE',55.00,NULL,18,NULL,1,'{\"Color\":\"Sage Green\"}','2026-10-13 00:55:35','2026-10-13 00:55:35'),(48,40,'PRD-040-SAND',55.00,NULL,22,NULL,1,'{\"Color\":\"Warm Sand\"}','2026-10-13 00:55:35','2026-10-13 00:55:35');
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `short_description` text DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'active',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `base_price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `track_stock` tinyint(1) NOT NULL DEFAULT 1,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `allow_backorder` tinyint(1) NOT NULL DEFAULT 0,
  `weight` decimal(8,2) DEFAULT NULL,
  `length` decimal(8,2) DEFAULT NULL,
  `width` decimal(8,2) DEFAULT NULL,
  `height` decimal(8,2) DEFAULT NULL,
  `tax_class` varchar(255) NOT NULL DEFAULT 'standard',
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_social_image` varchar(255) DEFAULT NULL,
  `related_products_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_products_json`)),
  `frequently_bought_together_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`frequently_bought_together_json`)),
  `video_url` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `video_poster` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_brand_id_foreign` (`brand_id`),
  KEY `products_status_index` (`status`),
  KEY `products_is_featured_index` (`is_featured`),
  KEY `products_base_price_index` (`base_price`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,2,5,'Organic French Linen Overshirt','organic-french-linen-overshirt-prd-001','PRD-001','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',1,110.00,135.00,49.50,1,23,5,0,0.65,23.00,26.00,5.00,'standard','Organic French Linen Overshirt | Boutique Essentials','Shop Organic French Linen Overshirt. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(2,2,5,'Heavyweight Pima Cotton Tee','heavyweight-pima-cotton-tee-prd-002','PRD-002','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',1,42.00,NULL,18.90,1,52,5,0,1.45,19.00,24.00,10.00,'standard','Heavyweight Pima Cotton Tee | Boutique Essentials','Shop Heavyweight Pima Cotton Tee. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(3,3,2,'Relaxed Pleated Chino Trouser','relaxed-pleated-chino-trouser-prd-003','PRD-003','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',1,128.00,NULL,57.60,1,42,5,0,1.09,39.00,16.00,17.00,'standard','Relaxed Pleated Chino Trouser | Boutique Essentials','Shop Relaxed Pleated Chino Trouser. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(4,4,2,'Merino Wool Ribbed Cardigan','merino-wool-ribbed-cardigan-prd-004','PRD-004','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',1,165.00,195.00,74.25,1,59,5,0,1.41,33.00,19.00,13.00,'standard','Merino Wool Ribbed Cardigan | Boutique Essentials','Shop Merino Wool Ribbed Cardigan. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(5,5,1,'Waterproof City Mac Coat','waterproof-city-mac-coat-prd-005','PRD-005','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',1,240.00,280.00,108.00,1,48,5,0,1.05,35.00,29.00,17.00,'standard','Waterproof City Mac Coat | Boutique Essentials','Shop Waterproof City Mac Coat. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(6,7,4,'Wheel-Thrown Stoneware Vase','wheel-thrown-stoneware-vase-prd-006','PRD-006','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',1,64.00,NULL,28.80,1,29,5,0,1.06,39.00,26.00,9.00,'standard','Wheel-Thrown Stoneware Vase | Boutique Essentials','Shop Wheel-Thrown Stoneware Vase. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(7,7,4,'Matte Terracotta Fluted Planter','matte-terracotta-fluted-planter-prd-007','PRD-007','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',1,48.00,58.00,21.60,1,21,5,0,0.72,35.00,25.00,17.00,'standard','Matte Terracotta Fluted Planter | Boutique Essentials','Shop Matte Terracotta Fluted Planter. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(8,8,1,'Brass Dimmer Table Lamp','brass-dimmer-table-lamp-prd-008','PRD-008','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',1,145.00,NULL,65.25,1,40,5,0,1.11,18.00,29.00,11.00,'standard','Brass Dimmer Table Lamp | Boutique Essentials','Shop Brass Dimmer Table Lamp. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(9,9,3,'Washed Belgian Linen Duvet Set','washed-belgian-linen-duvet-set-prd-009','PRD-009','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,210.00,245.00,94.50,1,50,5,0,0.99,24.00,10.00,18.00,'standard','Washed Belgian Linen Duvet Set | Boutique Essentials','Shop Washed Belgian Linen Duvet Set. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(10,10,3,'Waffle Weave Wool Throw Blanket','waffle-weave-wool-throw-blanket-prd-010','PRD-010','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,88.00,NULL,39.60,1,57,5,0,0.85,20.00,19.00,5.00,'standard','Waffle Weave Wool Throw Blanket | Boutique Essentials','Shop Waffle Weave Wool Throw Blanket. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(11,12,2,'Vegetable-Tanned Daily Tote','vegetable-tanned-daily-tote-prd-011','PRD-011','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,185.00,NULL,83.25,1,31,5,0,1.15,34.00,22.00,17.00,'standard','Vegetable-Tanned Daily Tote | Boutique Essentials','Shop Vegetable-Tanned Daily Tote. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(12,13,1,'Waxed Canvas Commuter Rucksack','waxed-canvas-commuter-rucksack-prd-012','PRD-012','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,175.00,210.00,78.75,1,17,5,0,1.29,21.00,16.00,20.00,'standard','Waxed Canvas Commuter Rucksack | Boutique Essentials','Shop Waxed Canvas Commuter Rucksack. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:33','2026-10-13 00:55:33',NULL),(13,14,2,'Pebbled Leather Bifold Wallet','pebbled-leather-bifold-wallet-prd-013','PRD-013','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,65.00,NULL,29.25,1,30,5,0,0.39,31.00,25.00,15.00,'standard','Pebbled Leather Bifold Wallet | Boutique Essentials','Shop Pebbled Leather Bifold Wallet. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(14,14,5,'Minimalist Card Sleeve','minimalist-card-sleeve-prd-014','PRD-014','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,38.00,45.00,17.10,1,22,5,0,1.37,38.00,24.00,6.00,'standard','Minimalist Card Sleeve | Boutique Essentials','Shop Minimalist Card Sleeve. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(15,16,5,'Court Low-Top Leather Sneaker','court-low-top-leather-sneaker-prd-015','PRD-015','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,135.00,NULL,60.75,1,33,5,0,0.82,40.00,29.00,17.00,'standard','Court Low-Top Leather Sneaker | Boutique Essentials','Shop Court Low-Top Leather Sneaker. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(16,17,2,'Suede Hand-Stitched Penny Loafer','suede-hand-stitched-penny-loafer-prd-016','PRD-016','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,195.00,225.00,87.75,1,56,5,0,0.83,38.00,18.00,16.00,'standard','Suede Hand-Stitched Penny Loafer | Boutique Essentials','Shop Suede Hand-Stitched Penny Loafer. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(17,18,3,'Shearling-Lined Suede Mules','shearling-lined-suede-mules-prd-017','PRD-017','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,115.00,NULL,51.75,1,32,5,0,0.64,35.00,16.00,16.00,'standard','Shearling-Lined Suede Mules | Boutique Essentials','Shop Shearling-Lined Suede Mules. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(18,20,4,'Speckled Ceramic Coffee Mug (Set of 2)','speckled-ceramic-coffee-mug-set-of-2-prd-018','PRD-018','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,46.00,NULL,20.70,1,40,5,0,1.09,21.00,11.00,12.00,'standard','Speckled Ceramic Coffee Mug (Set of 2) | Boutique Essentials','Shop Speckled Ceramic Coffee Mug (Set of 2). Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(19,21,4,'Handmade Stoneware Pasta Bowls (Set of 4)','handmade-stoneware-pasta-bowls-set-of-4-prd-019','PRD-019','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,92.00,110.00,41.40,1,15,5,0,1.15,21.00,15.00,20.00,'standard','Handmade Stoneware Pasta Bowls (Set of 4) | Boutique Essentials','Shop Handmade Stoneware Pasta Bowls (Set of 4). Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(20,22,3,'Stonewashed French Linen Napkins (Set of 4)','stonewashed-french-linen-napkins-set-of-4-prd-020','PRD-020','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,36.00,NULL,16.20,1,59,5,0,0.68,26.00,28.00,7.00,'standard','Stonewashed French Linen Napkins (Set of 4) | Boutique Essentials','Shop Stonewashed French Linen Napkins (Set of 4). Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(21,21,1,'Cast Iron Skillet with Walnut Handle','cast-iron-skillet-with-walnut-handle-prd-021','PRD-021','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,125.00,NULL,56.25,1,46,5,0,0.65,32.00,12.00,18.00,'standard','Cast Iron Skillet with Walnut Handle | Boutique Essentials','Shop Cast Iron Skillet with Walnut Handle. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(22,24,4,'Cedar & Wild Fig Botanical Candle','cedar-wild-fig-botanical-candle-prd-022','PRD-022','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,34.00,NULL,15.30,1,40,5,0,0.84,21.00,27.00,15.00,'standard','Cedar & Wild Fig Botanical Candle | Boutique Essentials','Shop Cedar & Wild Fig Botanical Candle. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(23,26,1,'Hinoki Cypress Essential Oil Mist','hinoki-cypress-essential-oil-mist-prd-023','PRD-023','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,28.00,NULL,12.60,1,46,5,0,0.47,33.00,17.00,12.00,'standard','Hinoki Cypress Essential Oil Mist | Boutique Essentials','Shop Hinoki Cypress Essential Oil Mist. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:34','2026-10-13 00:55:34',NULL),(24,25,4,'Cold-Pressed Botanical Bar Soap Trio','cold-pressed-botanical-bar-soap-trio-prd-024','PRD-024','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,26.00,30.00,11.70,1,15,5,0,1.10,37.00,10.00,16.00,'standard','Cold-Pressed Botanical Bar Soap Trio | Boutique Essentials','Shop Cold-Pressed Botanical Bar Soap Trio. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(25,28,1,'Layflat Thread-Bound Daily Journal','layflat-thread-bound-daily-journal-prd-025','PRD-025','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,28.00,NULL,12.60,1,25,5,0,0.84,15.00,17.00,8.00,'standard','Layflat Thread-Bound Daily Journal | Boutique Essentials','Shop Layflat Thread-Bound Daily Journal. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(26,29,1,'Solid Brass Precision Ballpoint Pen','solid-brass-precision-ballpoint-pen-prd-026','PRD-026','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,58.00,68.00,26.10,1,48,5,0,1.47,17.00,12.00,18.00,'standard','Solid Brass Precision Ballpoint Pen | Boutique Essentials','Shop Solid Brass Precision Ballpoint Pen. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(27,30,2,'Solid Oak Modular Desk Organiser Tray','solid-oak-modular-desk-organiser-tray-prd-027','PRD-027','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,74.00,NULL,33.30,1,41,5,0,1.39,17.00,30.00,11.00,'standard','Solid Oak Modular Desk Organiser Tray | Boutique Essentials','Shop Solid Oak Modular Desk Organiser Tray. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(28,32,5,'Overnight Canvas Weekender Duffle','overnight-canvas-weekender-duffle-prd-028','PRD-028','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,160.00,190.00,72.00,1,55,5,0,0.37,28.00,29.00,9.00,'standard','Overnight Canvas Weekender Duffle | Boutique Essentials','Shop Overnight Canvas Weekender Duffle. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(29,33,1,'Vacuum Insulated Steel Travel Tumbler (500ml)','vacuum-insulated-steel-travel-tumbler-500ml-prd-029','PRD-029','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,38.00,NULL,17.10,1,29,5,0,0.84,25.00,28.00,19.00,'standard','Vacuum Insulated Steel Travel Tumbler (500ml) | Boutique Essentials','Shop Vacuum Insulated Steel Travel Tumbler (500ml). Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(30,34,2,'Waterproof Zip Dopp Kit','waterproof-zip-dopp-kit-prd-030','PRD-030','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,48.00,NULL,21.60,1,59,5,0,1.26,25.00,17.00,19.00,'standard','Waterproof Zip Dopp Kit | Boutique Essentials','Shop Waterproof Zip Dopp Kit. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(31,2,5,'Organic Cotton Oxford Button-Down','organic-cotton-oxford-button-down-prd-031','PRD-031','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,95.00,NULL,42.75,1,31,5,0,0.61,15.00,25.00,9.00,'standard','Organic Cotton Oxford Button-Down | Boutique Essentials','Shop Organic Cotton Oxford Button-Down. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(32,3,5,'Textured Linen Summer Shorts','textured-linen-summer-shorts-prd-032','PRD-032','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,78.00,90.00,35.10,1,22,5,0,1.00,24.00,18.00,20.00,'standard','Textured Linen Summer Shorts | Boutique Essentials','Shop Textured Linen Summer Shorts. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(33,4,2,'Chunky Cashmere Beanie Hat','chunky-cashmere-beanie-hat-prd-033','PRD-033','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,65.00,NULL,29.25,1,43,5,0,0.45,35.00,24.00,14.00,'standard','Chunky Cashmere Beanie Hat | Boutique Essentials','Shop Chunky Cashmere Beanie Hat. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(34,7,1,'Scandi Minimalist Wall Clock','scandi-minimalist-wall-clock-prd-034','PRD-034','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,82.00,NULL,36.90,1,23,5,0,0.31,32.00,10.00,18.00,'standard','Scandi Minimalist Wall Clock | Boutique Essentials','Shop Scandi Minimalist Wall Clock. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(35,28,3,'Archival Linen Photo Album','archival-linen-photo-album-prd-035','PRD-035','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,52.00,NULL,23.40,1,39,5,0,0.36,26.00,20.00,18.00,'standard','Archival Linen Photo Album | Boutique Essentials','Shop Archival Linen Photo Album. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(36,10,3,'Handwoven Seagrass Storage Basket','handwoven-seagrass-storage-basket-prd-036','PRD-036','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,45.00,55.00,20.25,1,55,5,0,1.45,29.00,23.00,14.00,'standard','Handwoven Seagrass Storage Basket | Boutique Essentials','Shop Handwoven Seagrass Storage Basket. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(37,30,2,'Ergonomic Wooden Desk Riser','ergonomic-wooden-desk-riser-prd-037','PRD-037','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,110.00,NULL,49.50,1,41,5,0,1.42,15.00,25.00,11.00,'standard','Ergonomic Wooden Desk Riser | Boutique Essentials','Shop Ergonomic Wooden Desk Riser. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(38,20,1,'Matte Black Pour-Over Kettle (800ml)','matte-black-pour-over-kettle-800ml-prd-038','PRD-038','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,68.00,80.00,30.60,1,29,5,0,0.48,37.00,10.00,9.00,'standard','Matte Black Pour-Over Kettle (800ml) | Boutique Essentials','Shop Matte Black Pour-Over Kettle (800ml). Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(39,2,5,'French Terry Everyday Sweatshirt','french-terry-everyday-sweatshirt-prd-039','PRD-039','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,85.00,NULL,38.25,1,19,5,0,1.44,37.00,15.00,15.00,'standard','French Terry Everyday Sweatshirt | Boutique Essentials','Shop French Terry Everyday Sweatshirt. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL),(40,12,1,'Canvas Crossbody Utility Pouch','canvas-crossbody-utility-pouch-prd-040','PRD-040','A thoughtfully created piece with refined proportions and premium tactile finishes.','<p>Crafted to elevate your daily routine with enduring quality, simplicity, and natural materials. Each piece is designed for balance and durability.</p><p>Features clean seams, robust build, and an honest tactile feel that develops character over time.</p>','active',0,55.00,NULL,24.75,1,18,5,0,1.37,32.00,17.00,14.00,'standard','Canvas Crossbody Utility Pouch | Boutique Essentials','Shop Canvas Crossbody Utility Pouch. Thoughtfully sourced, minimal aesthetic, and prompt domestic delivery.',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 00:55:35','2026-10-13 00:55:35',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `author_name` varchar(255) NOT NULL,
  `author_avatar` varchar(255) DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `photos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos_json`)),
  `admin_reply` text DEFAULT NULL,
  `admin_replied_at` timestamp NULL DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `is_admin_created` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `helpful_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_product_id_foreign` (`product_id`),
  KEY `reviews_user_id_foreign` (`user_id`),
  KEY `reviews_created_by_admin_id_foreign` (`created_by_admin_id`),
  KEY `reviews_rating_index` (`rating`),
  KEY `reviews_is_approved_index` (`is_approved`),
  KEY `reviews_review_date_index` (`review_date`),
  CONSTRAINT `reviews_created_by_admin_id_foreign` FOREIGN KEY (`created_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,4,'Claire Henderson',NULL,4,'Exceptional build quality','The tactile feel and finish exceeded my expectations. Arrived in plastic-free packaging within three days.',NULL,NULL,NULL,1,0,NULL,'2026-09-01 00:55:35',11,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(2,2,4,'Marcus Thorne',NULL,4,'A calm and beautiful daily essential','Exactly as described. Subtle color, clean seams, and feels substantial in hand.',NULL,NULL,NULL,1,0,NULL,'2026-10-07 00:55:35',5,'2026-10-13 00:55:35','2026-10-13 00:55:35'),(3,3,4,'Avery Brooks',NULL,4,'Worth every penny','You can genuinely tell the difference in craftsmanship compared to generic department store alternatives.',NULL,NULL,NULL,1,0,NULL,'2026-09-28 00:55:36',5,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(4,4,4,'Elena Vance',NULL,4,'Understated elegance','Minimalist design that fits into our home effortlessly. Very happy with this purchase.',NULL,NULL,NULL,1,0,NULL,'2026-09-05 00:55:36',12,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(5,5,4,'Julian Hayes',NULL,5,'Sturdy and well-proportioned','Delivered promptly and looks gorgeous on the counter. Would happily recommend.',NULL,NULL,NULL,1,0,NULL,'2026-09-22 00:55:36',11,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(6,6,4,'Sora Tanaka',NULL,4,'Thoughtful packaging and great texture','The natural material feels soft yet durable. Has held up beautifully after everyday use.',NULL,NULL,NULL,1,0,NULL,'2026-09-21 00:55:36',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(7,7,4,'David Miller',NULL,5,'My new favourite piece','Subtle styling and great attention to detail. Excellent customer care as well.',NULL,NULL,NULL,1,0,NULL,'2026-09-24 00:55:36',6,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(8,8,4,'Hannah Lindqvist',NULL,5,'High quality and prompt shipping','Great experience ordering here. Everything was seamless from checkout to unboxing.',NULL,NULL,NULL,1,0,NULL,'2026-09-24 00:55:36',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(9,9,4,'Claire Henderson',NULL,4,'Exceptional build quality','The tactile feel and finish exceeded my expectations. Arrived in plastic-free packaging within three days.',NULL,NULL,NULL,1,0,NULL,'2026-09-18 00:55:36',2,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(10,10,4,'Marcus Thorne',NULL,4,'A calm and beautiful daily essential','Exactly as described. Subtle color, clean seams, and feels substantial in hand.',NULL,NULL,NULL,1,0,NULL,'2026-09-26 00:55:36',10,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(11,11,4,'Avery Brooks',NULL,5,'Worth every penny','You can genuinely tell the difference in craftsmanship compared to generic department store alternatives.',NULL,NULL,NULL,1,0,NULL,'2026-09-20 00:55:36',12,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(12,12,4,'Elena Vance',NULL,5,'Understated elegance','Minimalist design that fits into our home effortlessly. Very happy with this purchase.',NULL,NULL,NULL,1,0,NULL,'2026-09-16 00:55:36',3,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(13,13,4,'Julian Hayes',NULL,5,'Sturdy and well-proportioned','Delivered promptly and looks gorgeous on the counter. Would happily recommend.',NULL,NULL,NULL,1,0,NULL,'2026-09-24 00:55:36',14,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(14,14,4,'Sora Tanaka',NULL,4,'Thoughtful packaging and great texture','The natural material feels soft yet durable. Has held up beautifully after everyday use.',NULL,NULL,NULL,1,0,NULL,'2026-08-29 00:55:36',4,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(15,15,4,'David Miller',NULL,4,'My new favourite piece','Subtle styling and great attention to detail. Excellent customer care as well.',NULL,NULL,NULL,1,0,NULL,'2026-09-17 00:55:36',7,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(16,16,4,'Hannah Lindqvist',NULL,5,'High quality and prompt shipping','Great experience ordering here. Everything was seamless from checkout to unboxing.',NULL,NULL,NULL,1,0,NULL,'2026-10-12 00:55:36',6,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(17,17,4,'Claire Henderson',NULL,5,'Exceptional build quality','The tactile feel and finish exceeded my expectations. Arrived in plastic-free packaging within three days.',NULL,NULL,NULL,1,0,NULL,'2026-10-12 00:55:36',1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(18,18,4,'Marcus Thorne',NULL,4,'A calm and beautiful daily essential','Exactly as described. Subtle color, clean seams, and feels substantial in hand.',NULL,NULL,NULL,1,0,NULL,'2026-09-14 00:55:36',10,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(19,19,4,'Avery Brooks',NULL,4,'Worth every penny','You can genuinely tell the difference in craftsmanship compared to generic department store alternatives.',NULL,NULL,NULL,1,0,NULL,'2026-09-24 00:55:36',3,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(20,20,4,'Elena Vance',NULL,5,'Understated elegance','Minimalist design that fits into our home effortlessly. Very happy with this purchase.',NULL,NULL,NULL,1,0,NULL,'2026-10-09 00:55:36',12,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(1,2),(2,1),(2,2),(3,1),(3,2),(4,1),(4,2),(5,1),(5,2),(6,1),(6,2),(7,1),(7,3),(7,4),(7,5),(8,1),(8,3),(9,1),(9,5),(10,1),(10,5),(11,1),(11,5),(12,1),(13,1),(14,1),(15,1),(16,1),(16,3),(17,1),(18,1),(19,1),(20,1),(20,2),(20,4),(21,1),(21,4),(22,1),(23,1),(23,2),(24,1),(25,1),(26,1),(26,3),(27,1),(27,3),(28,1);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
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
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'super_admin','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(2,'product_manager','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(3,'order_manager','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(4,'support_agent','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(5,'finance_manager','web','2026-10-13 00:55:31','2026-10-13 00:55:31'),(6,'customer','web','2026-10-13 00:55:31','2026-10-13 00:55:31');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
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
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','Boutique','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(2,'site_tagline','Thoughtfully crafted essentials for living well','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(3,'logo_url',NULL,'general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(4,'favicon_url',NULL,'general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(5,'brand_color','#2F5D50','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(6,'support_email','support@shop.test','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(7,'support_phone','+1 (555) 012-3456','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(8,'store_address','123 Artisan Way, Suite 400, Portland, OR 97201','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(9,'currency_code','USD','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(10,'currency_symbol','$','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(11,'timezone','UTC','general',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(12,'announcement_enabled','1','announcement',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(13,'announcement_text','Complimentary domestic shipping on orders over $75 · Easy 30-day returns','announcement',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(14,'announcement_link','/shop','announcement',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(15,'announcement_bg','#2F5D50','announcement',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(16,'announcement_text_color','#FFFFFF','announcement',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(17,'announcement_dismissible','1','announcement',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(18,'announcement_start_date',NULL,'announcement',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(19,'announcement_end_date',NULL,'announcement',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(20,'social_instagram','https://instagram.com/boutique','social',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(21,'social_facebook','https://facebook.com/boutique','social',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(22,'social_tiktok','https://tiktok.com/@boutique','social',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(23,'social_youtube','https://youtube.com/@boutique','social',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(24,'social_x','https://x.com/boutique','social',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(25,'social_whatsapp','+15550123456','social',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(26,'footer_tagline','Timeless essentials designed with calm simplicity and lasting durability.','footer',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(27,'footer_columns','[{\"title\":\"Shop\",\"links\":[{\"label\":\"New Arrivals\",\"url\":\"\\/shop\"},{\"label\":\"Best Sellers\",\"url\":\"\\/shop\"},{\"label\":\"Apparel\",\"url\":\"\\/category\\/apparel\"},{\"label\":\"Home & Living\",\"url\":\"\\/category\\/home-living\"}]},{\"title\":\"Support\",\"links\":[{\"label\":\"Shipping & Delivery\",\"url\":\"\\/page\\/shipping\"},{\"label\":\"Returns Policy\",\"url\":\"\\/page\\/returns\"},{\"label\":\"Help & FAQs\",\"url\":\"\\/page\\/faq\"},{\"label\":\"Contact Us\",\"url\":\"\\/contact\"}]},{\"title\":\"Company\",\"links\":[{\"label\":\"Our Story\",\"url\":\"\\/page\\/about\"},{\"label\":\"Sustainability\",\"url\":\"\\/page\\/about\"},{\"label\":\"Privacy Policy\",\"url\":\"\\/page\\/privacy\"},{\"label\":\"Terms of Service\",\"url\":\"\\/page\\/terms\"}]}]','footer',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(28,'payment_icons_enabled','1','footer',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(29,'footer_copyright','© 2026 Boutique Inc. All rights reserved.','footer',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(30,'stripe_enabled','0','stripe',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(31,'stripe_mode','test','stripe',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(32,'stripe_currency','usd','stripe',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(33,'stripe_publishable_key','pk_test_sample_pub_key_123','stripe',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(34,'stripe_secret_key','eyJpdiI6IkdEeDhyS3BFVHRTTTNWelhDcW8vOVE9PSIsInZhbHVlIjoiWFdSK0tKdUZ6TnFwTVQ5Q25rZ1JHRzdzQ2drRnAwT3dicGpSL2ZkK2puRT0iLCJtYWMiOiJlMTI0NzY3YmE5OWZlZjc4NWVmMmVkOWYxNzRjNjRlMDc5MzE2NGMzZGM4YmM1MzI0NTFmNWMxNzJlYjI5MmQ1IiwidGFnIjoiIn0=','stripe',1,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(35,'stripe_webhook_secret','eyJpdiI6IkkrVnBCcjZXUWhBTzBpSGRzc0NsSnc9PSIsInZhbHVlIjoiSVZvUW8zd3B3ZDNJN2wzTW9MVDZyK0dFTnBMektxTXMxWXlFa3lKeThUVT0iLCJtYWMiOiJjNjI2NjQxMDAzNWQ4NGNmM2FjZmUzZmIyZDQyMTY1ZTQzODAzZjZlMmJiYTA1ZjVlODliNTFmODM5ZTBjZmNkIiwidGFnIjoiIn0=','stripe',1,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(36,'mail_host','smtp.hostinger.com','mail',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(37,'mail_port','465','mail',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(38,'mail_encryption','ssl','mail',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(39,'mail_username','support@shop.test','mail',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(40,'mail_password','eyJpdiI6InNQOGdoSWsrd0kwUlpEMkU4RHNEdlE9PSIsInZhbHVlIjoiVDVuYURlNS92Rjg3eVlQWGRBK0hNTmpiVW4vOWNWTnNOUFVZMlZDaVhxdz0iLCJtYWMiOiI2NjI2OGIxNzVmZGE2ODU0OGNlNTg3ZTUzOGI3ZWRlYmJlNTU0ZmJiNmMzMDkyZWNiZDljYTczNGEwYTBjZTVhIiwidGFnIjoiIn0=','mail',1,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(41,'mail_from_name','Boutique Store','mail',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(42,'mail_from_address','support@shop.test','mail',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(43,'mail_reply_to','support@shop.test','mail',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(44,'default_shipping_fee','8.00','shipping',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(45,'free_shipping_threshold','75.00','shipping',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(46,'tax_rate_percent','7.5','shipping',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(47,'prices_include_tax','0','shipping',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(48,'seo_title','Boutique - Calming Modern Essentials','seo',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(49,'seo_description','Curated collection of everyday apparel, ceramic homeware, leather goods and sustainable living accessories.','seo',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(50,'seo_social_image',NULL,'seo',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(51,'google_analytics_id',NULL,'seo',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(52,'meta_pixel_id',NULL,'seo',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(53,'custom_head_scripts',NULL,'seo',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(54,'custom_body_scripts',NULL,'seo',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(55,'maintenance_mode','0','store',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(56,'maintenance_message','We are currently performing scheduled maintenance. Please check back shortly.','store',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(57,'guest_checkout','1','store',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(58,'review_moderation','1','store',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(59,'require_email_verification','0','store',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(60,'low_stock_threshold','5','store',0,'2026-10-13 00:55:32','2026-10-13 00:55:32'),(61,'low_stock_email','admin@shop.test','store',0,'2026-10-13 00:55:32','2026-10-13 00:55:32');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_rates`
--

DROP TABLE IF EXISTS `shipping_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shipping_zone_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `free_above_subtotal` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipping_rates_shipping_zone_id_foreign` (`shipping_zone_id`),
  CONSTRAINT `shipping_rates_shipping_zone_id_foreign` FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_rates`
--

LOCK TABLES `shipping_rates` WRITE;
/*!40000 ALTER TABLE `shipping_rates` DISABLE KEYS */;
INSERT INTO `shipping_rates` VALUES (1,1,'Standard Ground (2-4 Days)',8.00,75.00,1,'2026-10-13 00:55:36','2026-10-13 00:55:36'),(2,1,'Express Priority (1-2 Days)',18.00,NULL,1,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `shipping_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_zones`
--

DROP TABLE IF EXISTS `shipping_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_zones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `countries_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`countries_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_zones`
--

LOCK TABLES `shipping_zones` WRITE;
/*!40000 ALTER TABLE `shipping_zones` DISABLE KEYS */;
INSERT INTO `shipping_zones` VALUES (1,'Domestic (United States)','[\"US\"]',1,'2026-10-13 00:55:36','2026-10-13 00:55:36');
/*!40000 ALTER TABLE `shipping_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'Bestseller','bestseller','2026-10-13 00:55:33','2026-10-13 00:55:33'),(2,'New Arrival','new-arrival','2026-10-13 00:55:33','2026-10-13 00:55:33'),(3,'Organic','organic','2026-10-13 00:55:33','2026-10-13 00:55:33'),(4,'Handmade','handmade','2026-10-13 00:55:33','2026-10-13 00:55:33'),(5,'Sustainable','sustainable','2026-10-13 00:55:33','2026-10-13 00:55:33'),(6,'Minimalist','minimalist','2026-10-13 00:55:33','2026-10-13 00:55:33'),(7,'Water-resistant','water-resistant','2026-10-13 00:55:33','2026-10-13 00:55:33'),(8,'Gift Idea','gift-idea','2026-10-13 00:55:33','2026-10-13 00:55:33');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `url_redirects`
--

DROP TABLE IF EXISTS `url_redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `url_redirects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_path` varchar(255) NOT NULL,
  `target_path` varchar(255) NOT NULL,
  `status_code` smallint(6) NOT NULL DEFAULT 301,
  `hits_count` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url_redirects_source_path_unique` (`source_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `url_redirects`
--

LOCK TABLES `url_redirects` WRITE;
/*!40000 ALTER TABLE `url_redirects` DISABLE KEYS */;
/*!40000 ALTER TABLE `url_redirects` ENABLE KEYS */;
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
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Administrator','admin@shop.test','+1 (555) 010-0001','2026-10-13 00:55:31','$2y$12$.pB7KcXObVAzD0tz9IV6MevP5wlqIrCgGa6FoutxBo93wBScgftKi',NULL,1,NULL,NULL,NULL,'2026-10-13 00:55:31','2026-10-13 00:55:31',NULL),(2,'Sarah (Product Lead)','product@shop.test',NULL,'2026-10-13 00:55:32','$2y$12$q5Fh4Eg/BLycZntuMlJR4ORhaFSCWfyhqEvRNzlJaIcetQZ7NrLXG',NULL,1,NULL,NULL,NULL,'2026-10-13 00:55:32','2026-10-13 00:55:32',NULL),(3,'Oliver (Order Manager)','order@shop.test',NULL,'2026-10-13 00:55:32','$2y$12$SdHnbh2OixEhzOgzlugG.OKaHQf3u0vg/fue/tAZ4qrCHEjNgxaXe',NULL,1,NULL,NULL,NULL,'2026-10-13 00:55:32','2026-10-13 00:55:32',NULL),(4,'Emma Watson','customer@shop.test','+1 (555) 019-8833','2026-10-13 00:55:32','$2y$12$ET64J1yyRJbYRSVHr0OnRuFZ3uSMxJcQFNyIhgPWJDQwMZG071lPi',NULL,1,NULL,NULL,NULL,'2026-10-13 00:55:32','2026-10-13 00:55:32',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlists_user_id_product_id_unique` (`user_id`,`product_id`),
  KEY `wishlists_product_id_foreign` (`product_id`),
  CONSTRAINT `wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-13 10:55:36
