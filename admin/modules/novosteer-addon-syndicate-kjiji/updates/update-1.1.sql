-- Adminer 4.2.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `site_plugin_novosteer_addon_syndicate_kjiji_groups`;
CREATE TABLE `site_plugin_novosteer_addon_syndicate_kjiji_groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `group_server` varchar(255) NOT NULL,
  `group_port` int(11) NOT NULL,
  `group_username` varchar(255) NOT NULL,
  `group_password` varchar(255) NOT NULL,
  `group_passive` int(1) NOT NULL,
  `group_ssl` int(1) NOT NULL,
  `group_path` text NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- 2021-01-29 09:05:02