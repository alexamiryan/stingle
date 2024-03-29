-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql-server
-- Generation Time: Sep 07, 2022 at 12:14 AM
-- Server version: 5.7.35
-- PHP Version: 7.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `stingle_api`
--

-- --------------------------------------------------------

--
-- Table structure for table `db_migrations`
--

CREATE TABLE IF NOT EXISTS `db_migrations` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `table_name` varchar(128) NOT NULL,
    `version` int(11) NOT NULL DEFAULT '1',
    `plugin_name` varchar(256) NOT NULL,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `table_plugin` (`table_name`,`plugin_name`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
COMMIT;
