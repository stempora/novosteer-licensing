
SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `site_plugin_novosteer_addon_banners`;
CREATE TABLE `site_plugin_novosteer_addon_banners` (
  `banner_id` int(11) NOT NULL AUTO_INCREMENT,
  `banner_note` text NOT NULL,
  `banner_type` int(1) NOT NULL,
  `banner_order` int(11) NOT NULL,
  `banner_code` varchar(32) NOT NULL,
  `banner_hash` varchar(32) NOT NULL,
  `banner_status` int(11) NOT NULL,
  `banner_url_type` int(1) NOT NULL,
  `banner_url` text NOT NULL,
  `banner_years` text NOT NULL,
  `banner_brands` text NOT NULL,
  `banner_models` text NOT NULL,
  `banner_trims` text NOT NULL,
  `banner_clicks` int(11) NOT NULL,
  `banner_image` int(11) NOT NULL,
  `banner_image_type` varchar(10) NOT NULL,
  `banner_image_date` int(11) NOT NULL,
  PRIMARY KEY (`banner_id`),
  KEY `banner_order` (`banner_order`),
  KEY `banner_type` (`banner_type`),
  KEY `banner_status` (`banner_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
