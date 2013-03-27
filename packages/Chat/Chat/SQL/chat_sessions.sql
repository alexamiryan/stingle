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
-- Table structure for table `chat_sessions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `chat_sessions` (
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Chat Sessions';

--
-- Table structure for table `chat_sessions_log`. Trigger should be created to fill this table.
--

CREATE TABLE IF NOT EXISTS `chat_sessions_log` (
  `id` int(10) unsigned NOT NULL auto_increment COMMENT 'primary id',
  `user1_id` int(10) unsigned NOT NULL,
  `user2_id` int(10) unsigned NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `user1_id` (`user1_id`,`user2_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8  ;


/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
--
--delimiter //
--CREATE    
--   TRIGGER chat_sessions_log AFTER DELETE
--    ON chat_sessions FOR EACH ROW BEGIN
--	IF OLD.inviter_user_id > OLD.invited_user_id THEN INSERT INTO chat_sessions_log SET user1_id = OLD.inviter_user_id, user2_id = OLD.invited_user_id,`datetime` = NOW() ON DUPLICATE KEY UPDATE `datetime` = NOW() ;
--	ELSE INSERT INTO chat_sessions_log SET user1_id = OLD.invited_user_id, user2_id = OLD.inviter_user_id,`datetime` = NOW() ON DUPLICATE KEY UPDATE`datetime` = NOW() ;
--	END IF;
--END//

--delimiter ;
---

-- Dump completed on 2013-02-06 17:08:16
