ALTER TABLE `#__vikbooking_door_access_integrations`
ADD COLUMN `ota_sync` tinyint(1) NOT NULL DEFAULT 0 AFTER `genperiod`;

ALTER TABLE `#__vikbooking_orderhistory` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `#__vikbooking_res_alteration_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idorder` int(10) NOT NULL,
  `ota_alteration_id` varchar(64) DEFAULT NULL,
  `ota_reservation_id` varchar(64) DEFAULT NULL,
  `channel` varchar(64) DEFAULT NULL,
  `initiated_by` varchar(16) NOT NULL DEFAULT 'guest',
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `created_on` datetime NOT NULL,
  `updated_on` datetime DEFAULT NULL,
  `data` mediumblob DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;