-- Run this once against the live hotel database before enabling QRPh online payment.
-- Existing reservations and accounts are not changed.
CREATE TABLE `payment_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_token` char(64) NOT NULL,
  `room_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `nights` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `provider` varchar(32) NOT NULL DEFAULT 'paymongo',
  `provider_checkout_id` varchar(100) DEFAULT NULL,
  `provider_checkout_url` varchar(2048) DEFAULT NULL,
  `status` enum('pending','paid','failed','expired','cancelled') NOT NULL DEFAULT 'pending',
  `reservation_id` int(11) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `provider_payload` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_attempts_booking_token_unique` (`booking_token`),
  UNIQUE KEY `payment_attempts_checkout_unique` (`provider_checkout_id`),
  UNIQUE KEY `payment_attempts_reservation_unique` (`reservation_id`),
  KEY `payment_attempts_room_status_expiry` (`room_id`,`status`,`expires_at`),
  KEY `payment_attempts_customer_status` (`customer_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
