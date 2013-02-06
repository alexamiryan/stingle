-- MySQL dump 10.13  Distrib 5.5.29, for Linux (x86_64)
--
-- Host: 192.168.0.1    Database: edesirs_new_admin
-- ------------------------------------------------------
-- Server version	5.0.95-log

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
-- Not dumping tablespaces as no INFORMATION_SCHEMA.FILES table on this server
--

--
-- Table structure for table `site_pages_info`
--

DROP TABLE IF EXISTS `site_pages_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_pages_info` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `lang_id` int(10) unsigned default NULL,
  `host_id` tinyint(2) unsigned default NULL,
  `module` varchar(64) default NULL,
  `page` varchar(64) default NULL,
  `title` varchar(128) character set utf8 NOT NULL,
  `meta_keywords` varchar(200) character set utf8 NOT NULL,
  `meta_description` varchar(255) character set utf8 NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `lang_id_2` (`lang_id`,`host_id`,`module`,`page`),
  KEY `lang_id` (`lang_id`),
  KEY `host_id` (`host_id`),
  CONSTRAINT `site_pages_info_ibfk_3` FOREIGN KEY (`lang_id`) REFERENCES `lm_languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `site_pages_info_ibfk_4` FOREIGN KEY (`host_id`) REFERENCES `hosts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-02-05 18:49:33
