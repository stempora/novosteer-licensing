-- Adminer 4.2.1 MySQL dump
SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `site_plugin_novosteer_addon_autobrands_colors`;
CREATE TABLE `site_plugin_novosteer_addon_autobrands_colors` (
  `color_id` int(11) NOT NULL AUTO_INCREMENT,
  `color_status` int(1) NOT NULL,
  `color_name` varchar(100) NOT NULL,
  `color_name_generic` varchar(100) NOT NULL,
  `color_code` varchar(10) NOT NULL,
  `color_hex` varchar(10) NOT NULL,
  `alert_color` int(1) NOT NULL,
  PRIMARY KEY (`color_id`),
  KEY `color_name` (`color_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `site_plugin_novosteer_addon_autobrands_vehicles`;
CREATE TABLE `site_plugin_novosteer_addon_autobrands_vehicles` (
  `vehicle_id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_status` int(1) NOT NULL,
  `vehicle_year` int(11) NOT NULL,
  `vehicle_default` int(1) NOT NULL,
  `model_id` int(11) NOT NULL,
  `trim_id` int(11) DEFAULT NULL,
  `color_id` int(11) DEFAULT NULL,
  `vehicle_image` int(1) NOT NULL,
  `vehicle_image_type` varchar(10) NOT NULL DEFAULT 'png',
  `vehicle_image_date` int(11) NOT NULL,
  PRIMARY KEY (`vehicle_id`),
  KEY `model_id` (`model_id`),
  KEY `color_id` (`color_id`),
  KEY `trim_id` (`trim_id`),
  KEY `vehicle_status` (`vehicle_status`),
  KEY `vehicle_default` (`vehicle_default`),
  CONSTRAINT `novosteer_addon_autobrands_vehicles_ibfk_2` FOREIGN KEY (`model_id`) REFERENCES `site_plugin_novosteer_addon_autobrands_models` (`model_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
