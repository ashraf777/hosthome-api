ALTER TABLE `bookings` ADD `guest_portal_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `status`;
ALTER TABLE `bookings` ADD UNIQUE KEY `bookings_guest_portal_token_unique` (`guest_portal_token`);

-- Generate a unique UUID for all existing bookings
UPDATE `bookings` SET `guest_portal_token` = UUID() WHERE `guest_portal_token` IS NULL;
