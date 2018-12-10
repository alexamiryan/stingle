-- MySQL dump 10.13  Distrib 5.6.40, for Linux (x86_64)
--
-- Host: localhost    Database: edesirs
-- ------------------------------------------------------
-- Server version       5.6.40-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `email_stats`
--

DROP TABLE IF EXISTS `email_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email_id` varchar(32) NOT NULL,
  `email` varchar(256) NOT NULL,
  `from` varchar(128) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_clicked` tinyint(1) NOT NULL DEFAULT '0',
  `is_activated` tinyint(1) NOT NULL DEFAULT '0',
  `is_unsubscribed` tinyint(1) NOT NULL DEFAULT '0',
  `is_bounced_soft` tinyint(1) NOT NULL DEFAULT '0',
  `is_bounced_hard` tinyint(1) NOT NULL DEFAULT '0',
  `is_bounced_block` tinyint(1) NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_read` datetime DEFAULT NULL,
  `date_clicked` datetime DEFAULT NULL,
  `date_activated` datetime DEFAULT NULL,
  `date_unsubscribed` datetime DEFAULT NULL,
  `date_bounced` datetime DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`),
  KEY `email` (`email`(255)),
  KEY `is_read` (`is_read`),
  KEY `is_clicked` (`is_clicked`),
  KEY `date` (`date`),
  KEY `type` (`type`),
  KEY `email_id` (`email_id`) USING BTREE,
  KEY `is_unsubscribed` (`is_unsubscribed`),
  KEY `is_bounced_hard` (`is_bounced_hard`),
  KEY `is_bounced_block` (`is_bounced_block`),
  KEY `is_activated` (`is_activated`),
  KEY `is_bounced_soft` (`is_bounced_soft`) USING BTREE,
  KEY `from` (`from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-12-10  1:50:51
