ALTER TABLE `#__vikbooking_tm_tasks`
ADD COLUMN `reference` varchar(32) DEFAULT NULL COMMENT 'used to link the task to a foreign service';