ALTER TABLE `site_plugin_novosteer_addon_autobrands_models`
ADD `alert_model` int(11) NOT NULL;

ALTER TABLE `site_plugin_novosteer_addon_autobrands_trims`
ADD `alert_trim` int(1) NOT NULL;

ALTER TABLE `site_plugin_novosteer_addon_autobrands_brands`
ADD `alert_brand` int(1) NOT NULL;