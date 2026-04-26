CREATE TABLE `message_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `offset_hours` int(11) NOT NULL DEFAULT 0,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `guest_id` bigint(20) unsigned DEFAULT NULL,
  `message_template_id` bigint(20) unsigned DEFAULT NULL,
  `direction` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outbound',
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `external_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `messages_external_message_id_unique` (`external_message_id`),
  KEY `messages_booking_id_foreign` (`booking_id`),
  KEY `messages_guest_id_foreign` (`guest_id`),
  KEY `messages_message_template_id_foreign` (`message_template_id`),
  CONSTRAINT `messages_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_guest_id_foreign` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_message_template_id_foreign` FOREIGN KEY (`message_template_id`) REFERENCES `message_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
