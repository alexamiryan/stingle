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
-- Table structure for table `GeoIPBlocks`
--

DROP TABLE IF EXISTS `GeoIPBlocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GeoIPBlocks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `startIpNum` int(10) unsigned NOT NULL,
  `endIpNum` int(10) unsigned NOT NULL,
  `locId` int(10) unsigned NOT NULL,
  `index_geo` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `startIpNum` (`startIpNum`),
  KEY `endIpNum` (`endIpNum`),
  KEY `index_geo` (`index_geo`),
  KEY `locId` (`locId`),
  CONSTRAINT `GeoIPBlocks_ibfk_1` FOREIGN KEY (`locId`) REFERENCES `GeoIPLocation` (`locId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5748953 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `GeoIPLocation`
--

DROP TABLE IF EXISTS `GeoIPLocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `GeoIPLocation` (
  `locId` int(10) unsigned NOT NULL,
  `country` char(2) default NULL,
  `region` varchar(64) default NULL,
  `city` varchar(128) default NULL,
  `postal_code` varchar(32) default NULL,
  `latitude` decimal(8,4) default NULL,
  `longitude` decimal(8,4) default NULL,
  `metro_code` varchar(8) default NULL,
  `area_code` varchar(8) default NULL,
  PRIMARY KEY  (`locId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-02-05 19:27:46
