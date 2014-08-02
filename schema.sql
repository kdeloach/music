
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

CREATE DATABASE `kevinxn_music`;
USE 'kevinxn_music';

--
-- Table structure for table `music`
--

DROP TABLE IF EXISTS `music`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `music` (
  `id` int(11) NOT NULL auto_increment,
  `folder` text,
  `artist` text,
  `album` text,
  `track` text,
  `title` text character set utf8,
  `dateAdded` datetime default NULL,
  `dateUpdated` datetime default NULL,
  `hash` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unique_hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `music_combine`
--

DROP TABLE IF EXISTS `music_combine`;
/*!50001 DROP VIEW IF EXISTS `music_combine`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `music_combine` (
  `id` int(11),
  `folder` longtext,
  `artist` longtext,
  `album` longtext,
  `track` longtext,
  `title` longtext,
  `dateAdded` datetime,
  `dateUpdated` datetime,
  `hash` varchar(32)
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `music_override`
--

DROP TABLE IF EXISTS `music_override`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `music_override` (
  `id` int(11) NOT NULL auto_increment,
  `folder` text,
  `artist` text,
  `album` text,
  `track` text,
  `title` text character set utf8,
  `dateAdded` datetime default NULL,
  `dateUpdated` datetime default NULL,
  `hash` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unique_hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `music_combine`
--

/*!50001 DROP TABLE IF EXISTS `music_combine`*/;
/*!50001 DROP VIEW IF EXISTS `music_combine`*/;
/*!50001 CREATE ALGORITHM=UNDEFINED DEFINER=`kevinxn_default`@`localhost` SQL SECURITY INVOKER VIEW `music_combine` AS select `m`.`id` AS `id`,(case when isnull(`mo`.`folder`) then `m`.`folder` else `mo`.`folder` end) AS `folder`,(case when isnull(`mo`.`artist`) then `m`.`artist` else `mo`.`artist` end) AS `artist`,(case when isnull(`mo`.`album`) then `m`.`album` else `mo`.`album` end) AS `album`,(case when isnull(`mo`.`track`) then `m`.`track` else `mo`.`track` end) AS `track`,(case when isnull(`mo`.`title`) then `m`.`title` else `mo`.`title` end) AS `title`,`m`.`dateAdded` AS `dateAdded`,`mo`.`dateAdded` AS `dateUpdated`,`m`.`hash` AS `hash` from (`music` `m` left join `music_override` `mo` on((`mo`.`hash` = `m`.`hash`))) */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-03-17 19:20:46
