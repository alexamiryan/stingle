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
-- Table structure for table `texts`
--

DROP TABLE IF EXISTS `texts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `texts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `group_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `group_name` (`group_id`,`name`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `texts_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `texts_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `texts_aliases`
--

DROP TABLE IF EXISTS `texts_aliases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `texts_aliases` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `value_id` int(10) unsigned NOT NULL,
  `host_language` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `value_id_hostlang` (`value_id`,`host_language`),
  KEY `host_language` (`host_language`),
  CONSTRAINT `texts_aliases_ibfk_1` FOREIGN KEY (`value_id`) REFERENCES `texts_values` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `texts_aliases_ibfk_2` FOREIGN KEY (`host_language`) REFERENCES `host_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2976 DEFAULT CHARSET=latin1 COMMENT='Texts aliases';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `texts_groups`
--

DROP TABLE IF EXISTS `texts_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `texts_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(256) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `texts_values`
--

DROP TABLE IF EXISTS `texts_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `texts_values` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `text_id` int(10) unsigned NOT NULL,
  `value` text character set utf8 NOT NULL,
  `host_language` int(10) unsigned NOT NULL COMMENT 'host, language pair',
  `display` tinyint(1) NOT NULL default '1' COMMENT 'display or not this text',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `text_id` (`text_id`,`host_language`),
  KEY `host_language` (`host_language`),
  CONSTRAINT `texts_values_ibfk_1` FOREIGN KEY (`text_id`) REFERENCES `texts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `texts_values_ibfk_2` FOREIGN KEY (`host_language`) REFERENCES `host_language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=538 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-02-05 18:50:41
