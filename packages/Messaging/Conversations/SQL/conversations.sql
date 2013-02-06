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
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uuid` int(11) unsigned NOT NULL COMMENT 'UUID of conversation',
  `user_id` int(10) unsigned NOT NULL COMMENT 'User ID',
  `interlocutor_id` int(10) unsigned NOT NULL COMMENT 'Interlocutor ID',
  `last_msg_date` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'Last message date in conversation',
  `read` tinyint(1) unsigned NOT NULL default '1' COMMENT 'Is conversation read by User',
  `unread_count` int(11) NOT NULL default '0',
  `trashed` tinyint(1) unsigned NOT NULL default '0' COMMENT 'Is conversation trashed by User. 0 - Not Trashed, 1 - Trashed, 2 - Deleted',
  `fetch_from` int(10) unsigned default NULL COMMENT 'If conversation was deleted previously, this is a id of last conversation that was deleted. If new message appears in this conversation then website should show messages starting from this message ID. ',
  `has_attachment` tinyint(1) NOT NULL default '0' COMMENT 'Is conversation contains attachment',
  PRIMARY KEY  (`id`),
  KEY `uuid` (`uuid`),
  KEY `user_interlocutor` (`user_id`,`interlocutor_id`),
  KEY `user_id` (`user_id`),
  KEY `interlocutor_id` (`interlocutor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=307 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conversation_attachments`
--

DROP TABLE IF EXISTS `conversation_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_attachments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `message_id` int(10) unsigned default NULL,
  `system_filename` varchar(64) NOT NULL,
  `filename` varchar(256) NOT NULL,
  `mime_type` varchar(64) NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `conversation_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `conversation_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=279 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conversation_messages`
--

DROP TABLE IF EXISTS `conversation_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_messages` (
  `id` int(10) unsigned NOT NULL auto_increment COMMENT 'ID of the message',
  `uuid` int(11) unsigned NOT NULL COMMENT 'UUID of conversation',
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'Message sent datetime',
  `sender_id` int(10) unsigned NOT NULL COMMENT 'User ID of sender',
  `receiver_id` int(10) unsigned NOT NULL COMMENT 'User ID of receiver',
  `message` text NOT NULL COMMENT 'Message body',
  `read` tinyint(1) NOT NULL default '0' COMMENT 'Read status of receiver',
  `deleted` int(10) NOT NULL default '0',
  `has_attachment` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `uuid` (`uuid`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `deleted` (`deleted`),
  CONSTRAINT `conversation_messages_ibfk_3` FOREIGN KEY (`uuid`) REFERENCES `conversations` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8208 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-02-05 18:38:28
