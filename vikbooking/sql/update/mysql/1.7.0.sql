ALTER TABLE `#__vikbooking_prices` ADD COLUMN `derived_id` int(10) DEFAULT 0;
ALTER TABLE `#__vikbooking_prices` ADD COLUMN `derived_data` varchar(256) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `#__vikbooking_shortenurls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sequence` varchar(64) NOT NULL,
  `redirect_uri` varchar(512) NOT NULL,
  `visits` int(10) NOT NULL DEFAULT 0,
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__vikbooking_payschedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idorder` int(10) NOT NULL,
  `fordt` datetime NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `created_on` datetime DEFAULT NULL,
  `created_by` varchar(32) DEFAULT NULL,
  `logs` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;