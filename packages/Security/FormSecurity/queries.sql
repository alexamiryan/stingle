-- MySQL dump 10.13  Distrib 5.5.11, for Linux (x86_64)
--
-- Host: localhost    Database: edesirs
-- ------------------------------------------------------
-- Server version	5.5.11

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
-- Table structure for table `security_users`
--

DROP TABLE IF EXISTS `security_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `request_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `counter_last_reset_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `counter` int(11) NOT NULL DEFAULT '1',
  `blockstatus` tinyint(1) NOT NULL DEFAULT '0',
  `unblocktime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `label` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_users`
--

LOCK TABLES `security_users` WRITE;
/*!40000 ALTER TABLE `security_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_sessions`
--

DROP TABLE IF EXISTS `security_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(40) DEFAULT NULL,
  `remote_ip` varchar(32) DEFAULT NULL,
  `request_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `counter_last_reset_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `counter` int(11) NOT NULL DEFAULT '1',
  `blockstatus` tinyint(1) NOT NULL DEFAULT '0',
  `unblocktime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `label` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_sessions`
--

LOCK TABLES `security_sessions` WRITE;
/*!40000 ALTER TABLE `security_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_hosts`
--

DROP TABLE IF EXISTS `security_hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_hosts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `remote_ip` varchar(32) DEFAULT NULL,
  `forwarded_ip` varchar(32) DEFAULT NULL,
  `request_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `counter_last_reset_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `counter` int(11) NOT NULL DEFAULT '1',
  `blockstatus` tinyint(1) NOT NULL DEFAULT '0',
  `unblocktime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `label` varchar(25) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_hosts`
--

LOCK TABLES `security_hosts` WRITE;
/*!40000 ALTER TABLE `security_hosts` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_hosts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-04-26 13:29:06
