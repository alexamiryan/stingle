DROP TABLE IF EXISTS `security_requests_log`;
CREATE TABLE IF NOT EXISTS `security_requests_log` (
  `id` int(11) NOT NULL auto_increment,
  `ip` varchar(16) NOT NULL,
  `count` int(11) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MEMORY  DEFAULT CHARSET=latin1;