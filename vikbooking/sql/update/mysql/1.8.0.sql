ALTER TABLE `#__vikbooking_orders` ADD COLUMN `tot_damage_dep` decimal(12,2) DEFAULT NULL AFTER `tot_fees`;

ALTER TABLE `#__vikbooking_operators` ADD COLUMN `work_days_week` varchar(256) DEFAULT NULL;
ALTER TABLE `#__vikbooking_operators` ADD COLUMN `work_days_exceptions` text DEFAULT NULL;

ALTER TABLE `#__vikbooking_customers_orders` ADD COLUMN `identity` varchar(256) DEFAULT NULL;
ALTER TABLE `#__vikbooking_customers_orders` ADD COLUMN `verification_data` varchar(1024) DEFAULT NULL;

ALTER TABLE `#__vikbooking_notifications` ADD COLUMN `avatar` varchar(256) DEFAULT NULL AFTER `summary`;

CREATE TABLE IF NOT EXISTS `#__vikbooking_tm_areas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `icon` varchar(128) NOT NULL,
  `instanceof` varchar(64) NOT NULL,
  `settings` varchar(1024) DEFAULT NULL,
  `tags` varchar(128) DEFAULT NULL,
  `status_enums` varchar(512) DEFAULT NULL,
  `display` tinyint(1) NOT NULL DEFAULT 1,
  `comments` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__vikbooking_tm_tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_area` int(10) unsigned DEFAULT NULL,
  `status_enum` varchar(32) DEFAULT NULL,
  `scheduler` varchar(32) DEFAULT NULL,
  `title` varchar(128) NOT NULL,
  `notes` text DEFAULT NULL,
  `tags` varchar(128) DEFAULT NULL,
  `id_order` int(10) unsigned DEFAULT NULL,
  `id_room` int(10) unsigned DEFAULT NULL,
  `room_index` int(5) unsigned DEFAULT NULL,
  `dueon` datetime NOT NULL,
  `createdon` datetime NOT NULL,
  `modifiedon` datetime DEFAULT NULL,
  `beganon` datetime DEFAULT NULL,
  `finishedon` datetime DEFAULT NULL,
  `beganby` int(10) unsigned DEFAULT NULL,
  `finishedby` int(10) unsigned DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0 COMMENT 'do not display in list when archived',
  `workstartedon` datetime DEFAULT NULL,
  `realduration` int(10) DEFAULT 0,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `ft_title_notes` (`title`, `notes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__vikbooking_tm_task_assignees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_task` int(10) unsigned NOT NULL,
  `id_operator` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__vikbooking_tm_task_colortags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `color` varchar(32) DEFAULT NULL,
  `hex` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__vikbooking_record_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `context` varchar(16) NOT NULL COMMENT 'the context alias identifier',
  `id_context` int(10) UNSIGNED NOT NULL COMMENT 'the foreign ID to link the context',
  `id_user` int(10) unsigned DEFAULT 0 COMMENT 'the CMS user/operator ID',
  `username` varchar(128) NOT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__vikbooking_record_history_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_change` int(10) unsigned NOT NULL,
  `event` varchar(64) NOT NULL,
  `payload` blob DEFAULT NULL COMMENT 'serialized VBOHistoryDetector interface',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__vikbooking_chat_messages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `context` varchar(16) NOT NULL COMMENT 'the context alias identifier',
  `id_context` int(10) UNSIGNED NOT NULL COMMENT 'the foreign ID to link the context',
  `sender_name` varchar(128) NOT NULL COMMENT 'the name of the sender',
  `id_sender` int(10) UNSIGNED DEFAULT 0 COMMENT 'the sender ID (0 for admin)',
  `message` text DEFAULT NULL,
  `attachments` blob DEFAULT NULL COMMENT 'serialized array of attachments',
  `createdon` datetime NOT NULL,
  `createdby` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `#__vikbooking_chat_messages_unread` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_message` int(10) UNSIGNED NOT NULL,
  `id_sender` int(10) UNSIGNED DEFAULT 0 COMMENT '0 for admin',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;