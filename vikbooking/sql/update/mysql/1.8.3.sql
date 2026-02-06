ALTER TABLE `#__vikbooking_customers` CHANGE `pin` `pin` varchar(16) NOT NULL DEFAULT '0';
ALTER TABLE `#__vikbooking_customers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;