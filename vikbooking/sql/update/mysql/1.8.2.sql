ALTER TABLE `#__vikbooking_rooms` COLLATE 'utf8mb4_general_ci';

ALTER TABLE `#__vikbooking_rooms` CHANGE `name` `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
ALTER TABLE `#__vikbooking_rooms` CHANGE `info` `info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `#__vikbooking_rooms` CHANGE `smalldesc` `smalldesc` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

ALTER TABLE `#__vikbooking_rooms` CHANGE `img` `img` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `#__vikbooking_rooms` CHANGE `idcat` `idcat` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `#__vikbooking_rooms` CHANGE `idcarat` `idcarat` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `#__vikbooking_rooms` CHANGE `idopt` `idopt` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `#__vikbooking_rooms` CHANGE `params` `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `#__vikbooking_rooms` CHANGE `alias` `alias` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

ALTER TABLE `#__vikbooking_rooms` CHANGE `moreimgs` `moreimgs` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `#__vikbooking_rooms` CHANGE `imgcaptions` `imgcaptions` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL;

ALTER TABLE `#__vikbooking_ordersrooms` ADD COLUMN `cust_cpolicy_id` int(10) DEFAULT NULL AFTER `cust_idiva`;