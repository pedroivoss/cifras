-- MySQL dump 10.13  Distrib 26.10.0, for Linux (aarch64)
--
-- Host: localhost    Database: cifra
-- ------------------------------------------------------
-- Server version	26.10.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`) USING BTREE,
  KEY `cache_expiration_index` (`expiration`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('cifra-ivo-company-cache-356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1793162697),('cifra-ivo-company-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1793162697;',1793162697),('cifra-ivo-company-cache-fea5ceb5aeb91e69401d722b868ec32dfd0f9960','i:1;',1793160202),('cifra-ivo-company-cache-fea5ceb5aeb91e69401d722b868ec32dfd0f9960:timer','i:1793160202;',1793160202);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`) USING BTREE,
  KEY `cache_locks_expiration_index` (`expiration`) USING BTREE
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
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`) USING BTREE,
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`) USING BTREE
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
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `jobs_queue_index` (`queue`) USING BTREE
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_23_000001_create_songs_table',1),(5,'2026_10_23_000002_create_tags_table',1),(6,'2026_10_23_000003_create_playlists_table',1),(7,'2026_10_27_100200_create_song_versions_table',2),(8,'2026_10_27_222523_add_mass_booklet_fields_to_playlists',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`) USING BTREE
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
-- Table structure for table `playlist_items`
--

DROP TABLE IF EXISTS `playlist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `playlist_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `playlist_id` bigint unsigned NOT NULL,
  `kind` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'song',
  `song_id` bigint unsigned DEFAULT NULL,
  `song_version_id` bigint unsigned DEFAULT NULL,
  `position` int unsigned NOT NULL DEFAULT '0',
  `semitones` smallint NOT NULL DEFAULT '0',
  `capo` tinyint unsigned DEFAULT NULL,
  `notes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `playlist_items_playlist_id_position_index` (`playlist_id`,`position`) USING BTREE,
  KEY `playlist_items_song_id_index` (`song_id`) USING BTREE,
  KEY `playlist_items_song_version_id_foreign` (`song_version_id`) USING BTREE,
  CONSTRAINT `playlist_items_playlist_id_foreign` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `playlist_items_song_id_foreign` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `playlist_items_song_version_id_foreign` FOREIGN KEY (`song_version_id`) REFERENCES `song_versions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `playlist_items`
--

LOCK TABLES `playlist_items` WRITE;
/*!40000 ALTER TABLE `playlist_items` DISABLE KEYS */;
INSERT INTO `playlist_items` VALUES (16,1,'song',13,21,1,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:12:33','2026-10-28 01:44:25'),(17,1,'song',14,22,2,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:13:24','2026-10-28 01:44:25'),(18,1,'song',16,24,4,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:15:04','2026-10-28 01:44:25'),(19,1,'song',15,23,3,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:15:13','2026-10-28 01:44:25'),(20,1,'song',17,25,5,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:16:29','2026-10-28 01:44:25'),(22,1,'song',18,26,7,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:19:35','2026-10-28 01:44:25'),(23,1,'song',19,27,9,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:19:35','2026-10-28 01:44:25'),(24,1,'song',20,28,6,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:19:46','2026-10-28 01:44:25'),(25,1,'song',21,29,10,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:20:40','2026-10-28 01:44:25'),(26,1,'song',23,31,13,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:21:53','2026-10-28 01:44:25'),(27,1,'song',24,32,8,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:23:11','2026-10-28 01:44:25'),(28,1,'song',22,30,11,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:24:00','2026-10-28 01:44:25'),(29,1,'song',25,33,12,0,NULL,NULL,NULL,NULL,NULL,'2026-10-27 19:24:36','2026-10-28 01:44:25');
/*!40000 ALTER TABLE `playlist_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `playlists`
--

DROP TABLE IF EXISTS `playlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `playlists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `event_date` date DEFAULT NULL,
  `cover_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_quote` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `share_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `allow_public_pdf` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `playlists_share_token_unique` (`share_token`) USING BTREE,
  KEY `playlists_user_id_updated_at_index` (`user_id`,`updated_at`) USING BTREE,
  CONSTRAINT `playlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `playlists`
--

LOCK TABLES `playlists` WRITE;
/*!40000 ALTER TABLE `playlists` DISABLE KEYS */;
INSERT INTO `playlists` VALUES (1,1,'Missa Todos os Santos','Repertório de exemplo','2026-11-01',NULL,NULL,NULL,'0fe549ca361ba3690b721254f6e87d636c2a220e',0,0,'2026-10-27 02:27:51','2026-10-28 01:44:25');
/*!40000 ALTER TABLE `playlists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `sessions_user_id_index` (`user_id`) USING BTREE,
  KEY `sessions_last_activity_index` (`last_activity`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('1gKezxCs8CsE6l2QIIHPqy8OqeSP0WE42fHHnexU',1,'192.168.65.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJnOXpQb3UybEtaTHZtNEZmMVBWVW1JeEdzQWdNYlZmbmxFY3plcW5HIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2l2b2NvbXBhbnkubG9jYWw6ODAwMFwvc3JjXC9jaWZyYXNcL3BsYXlsaXN0c1wvMVwvcGRmP2NvdmVyPTAmZm9udF9zaXplPTExJmlubGluZT0xJmx5cmljcz0xJm1hcmdpbj0xNSZub3Rlcz0wJm9yaWVudGF0aW9uPXBvcnRyYWl0JnBhZ2VfYnJlYWs9MSZ0b2M9MCIsInJvdXRlIjoicGxheWxpc3RzLnBkZiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6ImMxYjZjOGNjMjg5MjNjN2RmZTFjMzJiMGY3NjI5M2Q1YjI2NTc4OWE2NGNiNjc3Y2NiZTdlMWZhYmJkY2JhMmEifQ==',1793162665),('cLxTfRriwHQ9NfyfszTgrw3FzPM497S3QOhmxoyY',1,'192.168.65.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJGcDJJeFNicXltV0NQV0l5Z0dEc2RqRmlteFhNUnVPdmhiNnhDZzF6IiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2l2b2NvbXBhbnkubG9jYWw6ODAwMFwvc3JjXC9jaWZyYXNcL3BsYXlsaXN0c1wvMVwvZWRpdCIsInJvdXRlIjoicGxheWxpc3RzLmVkaXQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiJjMWI2YzhjYzI4OTIzYzdkZmUxYzMyYjBmNzYyOTNkNWIyNjU3ODlhNjRjYjY3N2NjYmU3ZTFmYWJiZGNiYTJhIn0=',1793160143),('UNhxNrDNyxn4HA6yqrGLyxCJffOfzfzE8L3oruHW',1,'192.168.65.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI1VFdLUFkxWGhWcFpqY04zVlJTWExBNlA2aUFoRk5GWllheHY3N29OIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2l2b2NvbXBhbnkubG9jYWw6ODAwMFwvc3JjXC9jaWZyYXNcL3BsYXlsaXN0c1wvMVwvcGRmP2NvdmVyPTAmZm9udF9zaXplPTExJmx5cmljcz0xJm1hcmdpbj0xNSZub3Rlcz0wJm9yaWVudGF0aW9uPXBvcnRyYWl0JnBhZ2VfYnJlYWs9MSZ0b2M9MCIsInJvdXRlIjoicGxheWxpc3RzLnBkZiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6ImMxYjZjOGNjMjg5MjNjN2RmZTFjMzJiMGY3NjI5M2Q1YjI2NTc4OWE2NGNiNjc3Y2NiZTdlMWZhYmJkY2JhMmEifQ==',1793139892),('XwYRuC3SweDZxnOvbu51KzbaXse9U69hEspkceXE',1,'192.168.65.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJQdnB6NkhTZThoS2JzRk13eHNVRkVlV0g4cVJnTnNOT0V5ZlVuUkRhIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2l2b2NvbXBhbnkubG9jYWw6ODAwMFwvc3JjXC9jaWZyYXNcL3BsYXlsaXN0c1wvMVwvZG9jeD9jb3Zlcj0wJmZvbnRfc2l6ZT0xMSZseXJpY3M9MSZtYXJnaW49MTUmbm90ZXM9MCZvcmllbnRhdGlvbj1wb3J0cmFpdCZwYWdlX2JyZWFrPTEmdG9jPTAiLCJyb3V0ZSI6InBsYXlsaXN0cy5kb2N4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsInBhc3N3b3JkX2hhc2hfd2ViIjoiYzFiNmM4Y2MyODkyM2M3ZGZlMWMzMmIwZjc2MjkzZDViMjY1Nzg5YTY0Y2I2NzdjY2JlN2UxZmFiYmRjYmEyYSJ9',1793138283);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `song_tag`
--

DROP TABLE IF EXISTS `song_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `song_tag` (
  `song_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`song_id`,`tag_id`) USING BTREE,
  KEY `song_tag_tag_id_index` (`tag_id`) USING BTREE,
  CONSTRAINT `song_tag_song_id_foreign` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `song_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `song_tag`
--

LOCK TABLES `song_tag` WRITE;
/*!40000 ALTER TABLE `song_tag` DISABLE KEYS */;
INSERT INTO `song_tag` VALUES (13,1),(14,2),(15,3),(16,5),(17,6),(20,7),(21,8),(22,8),(23,10),(21,11),(22,11),(24,13),(25,13),(23,17),(18,18),(19,19);
/*!40000 ALTER TABLE `song_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `song_versions`
--

DROP TABLE IF EXISTS `song_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `song_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `song_id` bigint unsigned NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Principal',
  `original_key` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capo` tinyint unsigned NOT NULL DEFAULT '0',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `song_versions_song_id_position_index` (`song_id`,`position`) USING BTREE,
  CONSTRAINT `song_versions_song_id_foreign` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `song_versions`
--

LOCK TABLES `song_versions` WRITE;
/*!40000 ALTER TABLE `song_versions` DISABLE KEYS */;
INSERT INTO `song_versions` VALUES (21,13,'Principal','A',0,'  A        Bm       D  E\nCelebrando a festa dos Santos\n     D          A       E\nNo Senhor se alegram os seus\n    A       D        F#m  E\nRejubilam conosco os na - jos\n      Bm  A/C#  D  E     A\nGlorifi___cam o Filho de Deus!\n   F          G             A\nÓ justos, alegrai-vos no Senhor!\n    F          G             A\nAos retos fica bem glorificá-lo\n        Dm        G    E     Am\nFeliz o povo cujo Deus é o Senhor\n      F           Dm             E\nE a nação que escolheu por sua herança!\n    A        Bm       D  E\nCelebrando a festa dos Santos\n     D          A       E\nNo Senhor se alegram os seus\n    A       D        F#m  E\nRejubilam conosco os na - jos\n      Bm  A/C#  D  E     A\nGlorifi___cam o Filho de Deus!\n    F         G           A\nFormou o coração de cada um\n    F             G           A\nPor todos os seus atos se interessa\n        Dm              G\nMas o Senhor, aos que o temem\nE             Am\nPousa o seu  olhar\n    F             Dm           E\nEsperando em seu amor, confiam nEle!\n\n    A        Bm       D  E\nCelebrando a festa dos Santos\n     D          A       E\nNo Senhor se alegram os seus\n    A       D        F#m  E\nRejubilam conosco os na - jos\n      Bm  A/C#  D  E     A\nGlorifi___cam o Filho de Deus!\n',1,'2026-10-27 19:11:28','2026-10-27 19:11:28'),(22,14,'Principal',NULL,0,'   Am         G       F            Am         G  E7\nSenhor, que vieste salvar, os corações arrependidos.\nDm C/E  F     Dm   F     E7\nTende   pie – da - de de nós\n\nDm      F      G         Am\nSenhor, tende piedade de nós\n\n   Am         G       F        Am   G  E7\nÓ cristo, que vieste chamar os pecadores.\nDm C/E  F     Dm   F     E7\nTende   pie – da - de de nós\n\nDm      F      G         Am\nCristo, tende piedade de nós\n\n   Am         G       F     Am    G  E7\nSenhor, que intercedeis por nós junto do pai.\nDm C/E  F     Dm   F     E7\nTende   pie – da - de de nós\n\nDm      F      G         Am\nSenhor, tende piedade de nós\n',1,'2026-10-27 19:13:22','2026-10-27 19:13:22'),(23,15,'Principal','C',0,'C                  A#\nGlória a Deus nas alturas\n   F                                C\nE paz na Terra aos homens por Ele amados\n              A#\nSenhor Deus, Rei dos Céus\n F                 C\nDeus Pai Todo Poderoso\n            Gm   C              Gm   C\nNós vos louvamos, nós vos bendizemos\n            Gm   C                Gm   C\nNós vos adoramos, nós vos glorificamos\n                A#\nNós vos damos graças\n     F             C\nPor vossa imensa glória\n    C          A#     F        C\nSenhor Jesus Cristo Filho Unigênito\n                 A#\nSenhor Deus, Cordeiro de Deus\n F             C\nFilho de Deus Pai\n Gm                  F\nVós, que tirais o pecado do mundo,\n A#                C\nTende piedade de nós\n Gm                  F\nVós, que tirais o pecado do mundo,\nA#                C\nAcolhei a nossa súplica\n Gm                  F\nVós, que estais à direita do Pai,\n A#                C\nTende piedade de nós\n\n               Gm  C              Gm  C\nSó Vós sois o Santo, só Vós, o Senhor\n             A#               C\nSó vós, o Altíssimo, Jesus Cristo\n D              G\nCom o Espírito Santo\n                   F     C\nNa glória de Deus Pai. Amém!\n                   F     C\nNa glória de Deus Pai. Amém!\n                   F     C\nNa glória de Deus Pai. Amém!\n                   F     C\nNa glória de Deus Pai. Amém!\n',1,'2026-10-27 19:14:08','2026-10-27 19:14:08'),(24,16,'Principal','A',0,'A     C#m  F#m    C#m     D  Bm       E\nAleluia,  aleluia, alelui__a, aleluia\nA     C#m  F#m    C#m     D  Bm       E\nAleluia,  aleluia, alelui__a, aleluia\n\n\nF#m\n   O nosso cordeiro pascal\n        C#m\nFoi imolado\n      Bm\nCelebremos, pois a festa\n                     E\nNa sinceridade e verdade\n\n\nA     C#m  F#m    C#m     D  Bm       E\nAleluia,  aleluia, alelui__a, aleluia\nA     C#m  F#m    C#m     D  Bm       E  A\nAleluia,  aleluia, alelui__a, aleluia\n',1,'2026-10-27 19:15:01','2026-10-27 19:15:01'),(25,17,'Principal','Dm',0,'[Intro] Dm  A#  F  C\n        Dm  A#  F  C\n\nDm                   A#\n   Mão na Terra e o coração\n  F         B4\nAlém deste céu\nDm                   A#\n   E a semente que brota é\n    F              C\nUm germe de eternidade\n        Dm         A#          F    C\nVai brotando, crescendo, esperando\n     Dm       A#         F  C\nÉ a vida que vem despontar\n         A#      F          Am      F      C\nE este trigo maduro, a colheita o recolherá\n\n[Refrão]\n               F       G  A#\nEstar em Tuas mãos, ó Pai\n              F  C\nE a vida ofertar\n    Dm               A#  F  Am\nNo pão e no vinho a Ti\n     F          C\nE o céu se abrirá\n               F     G     A#\nEstar em Tuas mãos, Senhor\n               F  C\nE a vida entregar\n   Dm               A#  F  Am\nA minha oblação em Ti\n\n F       C            Dm   A#\nSe perderá, frutificará\n  F       C    Dm      A#    F       C\nFrutificará, frutificará, frutificará!\n\n[Segunda Parte]\nDm                A#            F        B4\n   Da videira a flor não restará, passará\nDm               A#          F        C\n   E o fruto da terra surgirá, brotará\n      Dm       A#          F   C\nPela força do vento, da chuva\n      Dm           A#        F  C\nE do Sol que traz vida e calor\n      A#        F            Am      F     C\nCada dia, crescendo e aprendendo a recomeçar\n\n[Refrão Final]\n               F       G  A#\nEstar em Tuas mãos, ó Pai\n              F  C\nE a vida ofertar\n    Dm                 A#  F  Am\nNo pão e no vinho a Ti\n     F          C\nE o céu se abrirá\n               F     G     A#\nEstar em Tuas mãos, Senhor\n               F  C\nE a vida entregar\n   Dm               A#  F  Am\nA minha oblação em Ti\n F       C\nSe perderá\n               F       G  A#\nEstar em Tuas mãos, ó Pai\n              F  C\nE a vida ofertar\n    Dm               A#  F  Am\nNo pão e no vinho a Ti\n     F          C\nE o céu se abrirá\n\n               F     G     A#\nEstar em Tuas mãos, Senhor\n               F  C\nE a vida entregar\n   Dm               A#  F  Am\nA minha oblação em Ti\n F       C            Dm   A#\nSe perderá, frutificará\n  F       C    Dm      A#    F       C\nFrutificará, frutificará, frutificará!\n',1,'2026-10-27 19:16:20','2026-10-27 19:16:20'),(26,18,'Principal','D',0,'D    A/C#   Bm  F#m\n  Amém A__mém\nG   A   D   A7\nA      mém\nD    A/C#   Bm  F#m\n  Amém A__mém\nG     A  D\nA      mém\n',1,'2026-10-27 19:17:49','2026-10-27 19:17:49'),(27,19,'Principal','E',0,'             E         G#m7               A9\nCordeiro de Deus que tirais o pecado do mundo\n         F#m7       B       E  B/D#\nTende piedade, piedade de nós\n             E         G#m7               A9\nCordeiro de Deus que tirais o pecado do mundo\n         F#m7       B       E  B/D#\nTende piedade, piedade de nós\n             E         G#m7               A9\nCordeiro de Deus que tirais o pecado do mundo\n          F#m7    B  B7  E\nDai-nos a paz, a vossa a paz\n',1,'2026-10-27 19:18:32','2026-10-27 19:18:32'),(28,20,'Principal','C',0,'    C  C4  C       G\nSanto, Santo, Santo\n        F         Dm\nSenhor Deus do universo\n        F          G\nSenhor Deus do universo\n           C                      G\nO céu e a terra proclamam vossa glória\n   F    Dm        G\nHosa.....na nas alturas\n               C                 G\nBendito o que vem em nome do Senhor\n   F    Dm        G\nHosa.....na nas alturas\n    C  C4  C       G\nSanto, Santo, Santo\n        F         Dm\nSenhor Deus do universo\n        F          G\nSenhor Deus do universo\n    C\nSanto\n',1,'2026-10-27 19:19:17','2026-10-27 19:19:17'),(29,21,'Principal','G',0,'G\nAlma de cristo, santificai-nos\nEm7\nCorpo de cristo, salvai-nos\nC                               G\nSangue de cristo, inebriai-nos\nD                   Em\nÁgua do lado de cristo, lavai-nos\n    D               Em          Am7\nPaixão de cristo,confortai-nos\n           C           G\nÓ bom Jesus, ouvi-nos\nC           G\nAdoramus te\nC           G       D\nLaudamos te, domine\nC           G\nAdoramus te\nC           G       D      G\nLaudamos te, domine\nG\nEm vossas chagas, escondei-nos\nEm7\nDo inimigo, defendei-nos\nC                         C              G\nNão permitais que nos separemos de vós\nD                      C\nNa hora da nossa morte, chamai-nos\nD                  Em –  D – G      Am7\nPara louvar-vos com vossos santos\n        C       G\nPela eternidade\n\nC         G\nAdoramus te\nC         G      D Em D\nLaudamos te, domine\nC         G\nAdoramus te\nC         G       D\nLaudamos te, domine\nFinal:| C | G |\n',1,'2026-10-27 19:20:38','2026-10-27 19:20:38'),(30,22,'Principal','C',0,'     C\nQue bom, Senhor\n             Am    F\nIr ao Teu encontro\n               G           C\nPoder chegar e adentrar a Tua casa\n    Am        Em\nSentar-me contigo\n   F                  G4\nE partilhar da mesma mesa\n[Refrão]\n C    Em\nTe olhar\n Am   Em\nTe tocar\n\n   F             G4            C    G/B\nE Te dizer: Meu Deus, como és lindo\n Am   Em\nTe olhar\n Am   Em\nTe tocar\n   F             G4            C\nE Te dizer: Meu Deus, como és lindo\n[Segunda Parte]\n   C\nÓ meu Senhor\n Em             Am  F\nSei que não sou nada\n                G             C\nSem merecer, fizeste em mim Tua morada\nG/B      Am      Em\n     Mas, ao receber-Te\n    F          G\nPerfeita comunhão se cria\n[Refrão 2]\n C      Em\nSou em Ti\nAm     Em\nÉs em mim\n     F              G             C\nMinh\'alma diz: Meu Deus, como és lindo\nG/B  Am     Em\n     Sou em Ti\nAm     Em\nÉs em mim\n     F              G             C    G\nMinh\'alma diz: Meu Deus, como és lindo\n[Final] C  Em  Am  Em\n        F  G  C  G\n        C  Em  Am  Em\n        F  G  C  G\n        C  Em  Am  Em\n        F  G  C  G\n        C  Em  Am  Em\n        F  G  C  G\n\n        C  Em  Am  Em\n        F  G  C  G  C\n',1,'2026-10-27 19:21:12','2026-10-27 19:21:12'),(31,23,'Principal','C',0,'Dm\nNasceu de novo em nosso meio a esperança\n       C                         G\nDe um dia contemplar a face do Senhor\n             Dm\nQue tem seu trono lá no céu\n                      C  G\nOnde a alegria não termina\n         A#                   C\nO nosso sonho é viver a santidade\n    Dm\nAdorando em espírito e em verdade\n          A#                     C\nPerseverança e oração no dia a dia\n       Dm                      A7\nSustentados pela graça que nos guia\nAté Jesus voltar\n Dm           F             C\n(Vem galera, vamo pra cima, acredita)\nG\nSantos ou nada\n Dm           F             C\n(Vem galera, vamo pra cima, não vacila)\nG\nAté Jesus voltar\nDm           F              C\n(Vem galera, vamo pra cima, acredita)\nG\nSantos ou nada\n Dm           F             C          G\n(Vem galera, vamo pra cima, não vacila)\n\nA#    Gm            Dm    F\n  Ou santos ou nada,   ou santos ou nada\nAté Jesus voltar\n Dm           F             C\n(Vem galera, vamo pra cima, acredita)\n',1,'2026-10-27 19:21:50','2026-10-27 19:21:50'),(32,24,'Principal','Bb',0,' D               Bm          G   Em           A7\nQuero te dar a   paz do meu Senhor com muito amor!\n D               Bm          G   Em           A7\nQuero te dar a   paz do meu Senhor com muito amor!\n\n\n     D                      Bm\nNa flor vejo manifestar o poder da criação,\n          G                Em           A7\nNos teus lábios eu vejo estar o sorriso de um irmão\n\nD                      Bm\nToda vez que te abraço e aperto a tua mão,\n    G                Em              A7\nSinto forte o poder do Amor dentro do meu coração!\n D                               Bm\nDeus é Pai e nos protege, Cristo é o Filho e Salvação\n          G            Em          A7\nSanto Espírito Consolador, na Trindade somos irmãos!\n\n\n D                         Bm\nToda vez que te abraço e aperto a tua mão\n          G                Em      A7\nSinto forte o poder do Amor dentro do meu coração!\n',1,'2026-10-27 19:22:29','2026-10-27 19:23:09'),(33,25,'Principal','C',0,'     C         G7\nParabéns pra você\n       F G7    C\nNesta data querida\n        C7    F   Fm\nMuitas felicidades\n       C G7     C\nMuitos anos de vida\n    C\n\"Fulano(a)\" será abençoado(a)\n             G7\nPorque o Senhor\n                     C (C7)\nVai derramar o Seu amor\n    F        G7\nDerrama, Senhor\n    C        Am\nDerrama, Senhor\n   Dm        G7             C (C7)\nDerrama sobre ele(a) o Seu amor\n',1,'2026-10-27 19:24:35','2026-10-27 19:24:35');
/*!40000 ALTER TABLE `song_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `songs`
--

DROP TABLE IF EXISTS `songs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `songs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `artist` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `source_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `songs_user_id_title_index` (`user_id`,`title`) USING BTREE,
  KEY `songs_user_id_artist_index` (`user_id`,`artist`) USING BTREE,
  KEY `songs_user_id_created_at_index` (`user_id`,`created_at`) USING BTREE,
  CONSTRAINT `songs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `songs`
--

LOCK TABLES `songs` WRITE;
/*!40000 ALTER TABLE `songs` DISABLE KEYS */;
INSERT INTO `songs` VALUES (13,1,'Celebrando a Festa Dos Santos','Marcus Vinícius Lima',NULL,'arquivo',NULL,NULL,'2026-10-27 19:11:28','2026-10-27 19:11:28'),(14,1,'Kyrie Eleison (JMJ) Adaptado','JMJ',NULL,'arquivo',NULL,NULL,'2026-10-27 19:13:22','2026-10-27 19:13:22'),(15,1,'Glória','Eliana Ribeiro',NULL,'arquivo',NULL,NULL,'2026-10-27 19:14:08','2026-10-27 19:14:08'),(16,1,'Aleluia','Capella',NULL,'arquivo',NULL,NULL,'2026-10-27 19:15:01','2026-10-27 19:15:01'),(17,1,'Estar Em Tuas Mãos','Comunidade Católica Shalom',NULL,'arquivo',NULL,NULL,'2026-10-27 19:16:20','2026-10-27 19:16:20'),(18,1,'Amém','Músicas Católicas',NULL,'arquivo',NULL,NULL,'2026-10-27 19:17:49','2026-10-27 19:17:49'),(19,1,'Cordeiro de Deus','Ministério Amor e Adoração',NULL,'arquivo',NULL,NULL,'2026-10-27 19:18:32','2026-10-27 19:18:32'),(20,1,'Santo','Frei Gilson',NULL,'arquivo',NULL,NULL,'2026-10-27 19:19:17','2026-10-27 19:19:17'),(21,1,'Alma de Cristo','Comunidade Católica Shalom',NULL,'arquivo',NULL,NULL,'2026-10-27 19:20:38','2026-10-27 19:20:38'),(22,1,'Como És Lindo','Vida Reluz',NULL,'arquivo',NULL,NULL,'2026-10-27 19:21:12','2026-10-27 19:21:12'),(23,1,'Até Jesus Voltar','Pitter Di Laura',NULL,'arquivo',NULL,NULL,'2026-10-27 19:21:50','2026-10-27 19:21:50'),(24,1,'Quero Te Dar a Paz','Músicas Católicas',NULL,'arquivo',NULL,NULL,'2026-10-27 19:22:29','2026-10-27 19:23:41'),(25,1,'Parabéns Pra Você',NULL,NULL,'arquivo',NULL,NULL,'2026-10-27 19:24:35','2026-10-27 19:24:35');
/*!40000 ALTER TABLE `songs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `tags_user_id_slug_unique` (`user_id`,`slug`) USING BTREE,
  CONSTRAINT `tags_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,1,'Canto de Entrada','canto-de-entrada',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(2,1,'Ato Penitencial','ato-penitencial',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(3,1,'Glória','gloria',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(4,1,'Salmo','salmo',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(5,1,'Aclamação','aclamacao',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(6,1,'Ofertório','ofertorio',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(7,1,'Santo','santo',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(8,1,'Comunhão','comunhao',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(10,1,'Canto Final','canto-final',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(11,1,'Adoração','adoracao',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(13,1,'Outros','outros',NULL,'2026-10-27 02:27:51','2026-10-27 02:27:51'),(14,1,'Natal','natal','#6366f1','2026-10-27 02:36:53','2026-10-27 02:36:53'),(17,1,'Animação/Louvor','animacaolouvor','#e69119','2026-10-27 18:37:11','2026-10-27 18:37:43'),(18,1,'Amém','amem',NULL,'2026-10-27 19:17:49','2026-10-27 19:17:49'),(19,1,'Cordeiro','cordeiro',NULL,'2026-10-27 19:18:32','2026-10-27 19:18:32');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `users_email_unique` (`email`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Pedro Ivo','admin@cifra.local',NULL,'$2y$12$uem9GyIBHV5JCY9oLoyXC.eQlLH5s.u//bb7JEJf9QQQHOyfE8txO',NULL,'2026-10-27 02:27:51','2026-10-26 23:42:58');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-02 23:37:50
