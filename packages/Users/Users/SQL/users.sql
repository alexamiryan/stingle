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
-- Table structure for table `wum_users`
--

DROP TABLE IF EXISTS `wum_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wum_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `enabled` tinyint(1) NOT NULL default '1' COMMENT 'Is User Enabled',
  `creation_date` date NOT NULL COMMENT 'User creation date',
  `creation_time` time NOT NULL COMMENT 'User creation time',
  `login` varchar(64) NOT NULL COMMENT 'Username',
  `password` varchar(128) NOT NULL COMMENT 'Salted hashed password',
  `salt` varchar(128) NOT NULL,
  `last_login_ip` varchar(64) default NULL,
  `last_login_date` timestamp NULL default NULL COMMENT 'Last login date',
  `email` varchar(250) default NULL COMMENT 'Email address',
  `email_confirmed` tinyint(1) unsigned NOT NULL default '0' COMMENT 'Shows user confirmed his email or not',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uni_login` (`login`),
  KEY `ind_email` (`email`),
  KEY `ind_enable` USING BTREE (`enabled`),
  KEY `last_login_date` (`last_login_date`),
  KEY `creation_date` (`creation_date`),
  KEY `creation_time` (`creation_time`)
) ENGINE=InnoDB AUTO_INCREMENT=261014 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wum_groups`
--

DROP TABLE IF EXISTS `wum_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wum_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL,
  `description` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wum_permissions`
--

DROP TABLE IF EXISTS `wum_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wum_permissions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL,
  `description` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wum_groups_permissions`
--

DROP TABLE IF EXISTS `wum_groups_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wum_groups_permissions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `group_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `args` varchar(256) default NULL,
  PRIMARY KEY  (`id`),
  KEY `fk_rg` (`group_id`),
  KEY `fk_rp` (`permission_id`),
  CONSTRAINT `wum_groups_permissions_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `wum_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wum_groups_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `wum_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wum_users_groups`
--

DROP TABLE IF EXISTS `wum_users_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wum_users_groups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `is_primary` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `fk_ru` (`user_id`),
  KEY `fk_rg` (`group_id`),
  KEY `common` USING BTREE (`group_id`,`user_id`,`is_primary`),
  KEY `is_prim` (`is_primary`),
  CONSTRAINT `wum_users_groups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wum_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wum_users_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `wum_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=480901 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wum_users_permissions`
--

DROP TABLE IF EXISTS `wum_users_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wum_users_permissions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `args` varchar(256) default NULL,
  PRIMARY KEY  (`id`),
  KEY `fk_ru` (`user_id`),
  KEY `fk_rp` (`permission_id`),
  CONSTRAINT `wum_users_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wum_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wum_users_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `wum_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wum_users_properties`
--

DROP TABLE IF EXISTS `wum_users_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wum_users_properties` (
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`user_id`),
  CONSTRAINT `wum_users_properties_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wum_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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

-- Dump completed on 2013-02-05 18:59:44
