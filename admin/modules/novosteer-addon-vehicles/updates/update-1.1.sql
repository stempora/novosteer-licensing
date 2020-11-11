ALTER TABLE `site_plugin_novosteer_vehicles_export`
ADD `color_id` int NOT NULL AFTER `trim_id`;

ALTER TABLE `site_plugin_novosteer_vehicles_import`
ADD `color_id` int NOT NULL AFTER `feed_id`;