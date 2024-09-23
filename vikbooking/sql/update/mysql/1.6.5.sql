ALTER TABLE `#__vikbooking_reminders` ADD COLUMN `displayed` tinyint(1) DEFAULT 0 AFTER `completed`;

ALTER TABLE `#__vikbooking_reminders` ADD COLUMN `important` tinyint(1) DEFAULT 0 AFTER `displayed`;