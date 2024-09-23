CREATE TABLE IF NOT EXISTS `#__vikbooking_notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `signature` varchar(32) NOT NULL,
  `group` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `title` varchar(64) NOT NULL,
  `summary` varchar(256) DEFAULT NULL,
  `cta_data` varchar(256) DEFAULT NULL,
  `idorder` int(10) unsigned DEFAULT NULL,
  `idorderota` varchar(128) DEFAULT NULL,
  `channel` varchar(64) DEFAULT NULL,
  `createdon` DATETIME NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci AUTO_INCREMENT=1 ;