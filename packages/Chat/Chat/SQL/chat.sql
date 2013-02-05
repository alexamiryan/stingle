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
-- Table structure for table `chat_invitations`
--

DROP TABLE IF EXISTS `chat_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_invitations` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sender_user_id` int(10) unsigned NOT NULL,
  `receiver_user_id` int(10) unsigned NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `invitation_message` varchar(256) NOT NULL,
  `status` tinyint(4) NOT NULL default '0' COMMENT '-2 - Declined, -1 - Canceled, 0 - New, 1 - Accepted',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `sender_user_id` (`sender_user_id`,`receiver_user_id`),
  KEY `receiver_user_id` (`receiver_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=985 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sender_user_id` int(10) unsigned default NULL,
  `receiver_user_id` int(10) unsigned NOT NULL,
  `datetime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `message` varchar(512) NOT NULL,
  `is_system` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `sender_receiver` (`sender_user_id`,`receiver_user_id`),
  KEY `receiver_user_id` (`receiver_user_id`),
  KEY `datetime` (`datetime`),
  KEY `receiver_sender_system` (`receiver_user_id`,`sender_user_id`,`is_system`)
) ENGINE=MyISAM AUTO_INCREMENT=726 DEFAULT CHARSET=utf8 COMMENT='Chat Messages';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_sessions`
--

DROP TABLE IF EXISTS `chat_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_sessions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `inviter_user_id` int(10) unsigned NOT NULL,
  `invited_user_id` int(10) unsigned NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `closed` tinyint(4) NOT NULL COMMENT '0 - No, 1 - Yes',
  `closed_by` int(10) unsigned NOT NULL,
  `closed_reason` tinyint(4) default NULL,
  `closed_date` timestamp NULL default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `inviter_user_id` (`inviter_user_id`,`invited_user_id`),
  KEY `invited_user_id` (`invited_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COMMENT='Chat Sessions';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-02-05 18:34:27
