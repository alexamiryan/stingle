-- phpMyAdmin SQL Dump
-- version 4.4.15.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 17, 2016 at 03:09 PM
-- Server version: 5.6.28
-- PHP Version: 5.6.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `edesirs`
--

-- --------------------------------------------------------

--
-- Table structure for table `wum_google_auth_map`
--

DROP TABLE IF EXISTS `wum_google_auth_map`;
CREATE TABLE IF NOT EXISTS `wum_google_auth_map` (
  `id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `secret` varchar(32) NOT NULL,
  `enabled` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wum_google_auth_map`
--
ALTER TABLE `wum_google_auth_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_enabled` (`user_id`,`enabled`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `wum_google_auth_map`
--
ALTER TABLE `wum_google_auth_map`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `wum_google_auth_map`
--
ALTER TABLE `wum_google_auth_map`
  ADD CONSTRAINT `wum_google_auth_map_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wum_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

