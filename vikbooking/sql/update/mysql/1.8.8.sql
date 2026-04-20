CREATE TABLE IF NOT EXISTS `#__vikbooking_quotations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(64) NOT NULL,
  `name` varchar(128) NOT NULL,
  `subject` varchar(128) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `idcustomer` int(10) DEFAULT NULL,
  `first_name` varchar(64) NOT NULL,
  `last_name` varchar(64) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `country_3_code` char(3) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `created_by` varchar(32) DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `preferred` tinyint(1) NOT NULL DEFAULT 0,
  `sent` tinyint(1) NOT NULL DEFAULT 0,
  `viewed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci AUTO_INCREMENT=1 ;

ALTER TABLE `#__vikbooking_orders` ADD COLUMN `idquote` int(10) DEFAULT NULL AFTER `canc_fee`;

CREATE TABLE IF NOT EXISTS `#__vikbooking_chat_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `phone` varchar(256) DEFAULT NULL COMMENT 'whatsapp identifier',
  `id_user` int(10) unsigned DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `logout` DATETIME DEFAULT NULL,
  `metadata` varchar(2048) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

ALTER TABLE `#__vikbooking_chat_messages`
ADD COLUMN `ref_id` varchar(128) DEFAULT NULL COMMENT 'Reference ID for external resources';

ALTER TABLE `#__vikbooking_chat_messages_unread`
MODIFY `id_sender` int(10) DEFAULT 0 COMMENT 'the sender ID (0 for admin, -1 for guest, 1+ for operators)';