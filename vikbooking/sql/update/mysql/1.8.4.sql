ALTER TABLE `#__vikbooking_seasons` ADD COLUMN `createdon` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `promofinalprice`;
ALTER TABLE `#__vikbooking_seasons` ADD COLUMN `modifiedon` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `createdon`;
ALTER TABLE `#__vikbooking_tm_tasks` ADD COLUMN `ai` tinyint(1) DEFAULT 0;

CREATE TABLE IF NOT EXISTS `#__vikbooking_door_access_integrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider_alias` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `gentype` varchar(16) DEFAULT NULL,
  `genperiod` varchar(16) DEFAULT NULL,
  `settings` varchar(2048) DEFAULT NULL,
  `devices` mediumblob DEFAULT NULL,
  `data` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;