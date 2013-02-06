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
-- Table structure for table `wgps_config`
--

DROP TABLE IF EXISTS `wgps_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wgps_config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `node_id` int(10) unsigned NOT NULL,
  `type_id` tinyint(3) unsigned default NULL,
  `field_id` tinyint(3) unsigned NOT NULL,
  `action` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `node` (`node_id`),
  KEY `field_id` (`field_id`),
  KEY `type_id` (`type_id`),
  CONSTRAINT `wgps_config_ibfk_4` FOREIGN KEY (`field_id`) REFERENCES `wgps_cust_fields` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wgps_cust_fields`
--

DROP TABLE IF EXISTS `wgps_cust_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wgps_cust_fields` (
  `id` tinyint(3) unsigned NOT NULL auto_increment,
  `const_name` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `wgps_cust_fields_ibfk_1` (`const_name`),
  CONSTRAINT `wgps_cust_fields_ibfk_1` FOREIGN KEY (`const_name`) REFERENCES `lm_constants` (`key`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wgps_cust_save`
--

DROP TABLE IF EXISTS `wgps_cust_save`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wgps_cust_save` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `field_id` tinyint(3) unsigned NOT NULL,
  `text` varchar(256) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `field_id` (`field_id`)
) ENGINE=InnoDB AUTO_INCREMENT=137194 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wgps_country_iso`
--

DROP TABLE IF EXISTS `wgps_country_iso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wgps_country_iso` (
  `gps_id` int(10) unsigned NOT NULL,
  `iso2` char(2) default NULL,
  `iso3` char(3) default NULL,
  `name` varchar(64) character set utf8 default NULL,
  PRIMARY KEY  (`gps_id`),
  CONSTRAINT `wgps_country_iso_ibfk_1` FOREIGN KEY (`gps_id`) REFERENCES `wgps_tree` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wgps_labels`
--

DROP TABLE IF EXISTS `wgps_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wgps_labels` (
  `country_id` int(10) unsigned NOT NULL COMMENT 'Country node id',
  `type` varchar(32) character set utf8 NOT NULL COMMENT 'Wgps field type',
  `constant` varchar(32) character set utf8 NOT NULL COMMENT 'Constant name',
  UNIQUE KEY `country_id` (`country_id`,`type`),
  KEY `type` (`type`),
  KEY `constant` (`constant`),
  CONSTRAINT `wgps_labels_ibfk_2` FOREIGN KEY (`type`) REFERENCES `wgps_types` (`type`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wgps_labels_ibfk_4` FOREIGN KEY (`country_id`) REFERENCES `wgps_tree` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wgps_labels_ibfk_5` FOREIGN KEY (`constant`) REFERENCES `lm_constants` (`key`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Labels of wgps fields';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wgps_tree`
--

DROP TABLE IF EXISTS `wgps_tree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wgps_tree` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type_id` tinyint(3) unsigned NOT NULL,
  `lat` float default NULL,
  `lng` float default NULL,
  PRIMARY KEY  (`id`),
  KEY `parent` (`parent_id`),
  KEY `parent_name` (`parent_id`,`name`),
  KEY `type_id` (`type_id`),
  KEY `name` (`name`,`type_id`),
  KEY `lat` (`lat`,`lng`)
) ENGINE=InnoDB AUTO_INCREMENT=4091218 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wgps_types`
--

DROP TABLE IF EXISTS `wgps_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wgps_types` (
  `id` tinyint(3) unsigned NOT NULL auto_increment,
  `type` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `const` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wgps_zip_codes`
--

DROP TABLE IF EXISTS `wgps_zip_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wgps_zip_codes` (
  `zip` varchar(1024) NOT NULL,
  `country_id` int(10) unsigned NOT NULL,
  `gps_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `gps_id` (`gps_id`,`zip`(255)),
  KEY `zip` (`zip`(255)),
  KEY `country_id` (`country_id`,`zip`(255))
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

-- Dump completed on 2013-02-05 19:25:49
