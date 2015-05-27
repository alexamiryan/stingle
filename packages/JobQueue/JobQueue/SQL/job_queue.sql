CREATE TABLE IF NOT EXISTS `job_queue` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `properties` text NOT NULL,
  `start_date` timestamp NOT NULL,
  `status` enum('0','1','2','3') NOT NULL DEFAULT '0' COMMENT '0 - new, 1 - in proccess, 2 - completed, 3 - failed',
  `log_message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `job_queue`
  ADD PRIMARY KEY (`id`), ADD KEY `status` (`status`);

ALTER TABLE `job_queue`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;
  
CREATE TABLE IF NOT EXISTS `job_queue_archive` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `properties` text NOT NULL,
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('0','1','2','3') NOT NULL DEFAULT '0' COMMENT '0 - new, 1 - in proccess, 2 - completed, 3 - failed',
  `log_message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `job_queue_archive`
  ADD PRIMARY KEY (`id`), ADD KEY `status` (`status`);

ALTER TABLE `job_queue_archive`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;  
  