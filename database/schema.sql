-- Schema derived from the supplied hotel database. No account or guest data.
SET NAMES utf8mb4;

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `hours` int(11) DEFAULT NULL,
  `extra_bed` decimal(10,2) DEFAULT NULL,
  `food` decimal(10,2) DEFAULT NULL,
  `damages` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_name` varchar(50) DEFAULT NULL,
  `room_type` varchar(100) NOT NULL DEFAULT 'Standard',
  `room_rate` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','customer') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `payment_attempts` (
  `id` bigint unsigned NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `payment_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_attempts_booking_token_unique` (`booking_token`),
  ADD UNIQUE KEY `payment_attempts_checkout_unique` (`provider_checkout_id`),
  ADD UNIQUE KEY `payment_attempts_reservation_unique` (`reservation_id`),
  ADD KEY `payment_attempts_room_status_expiry` (`room_id`,`status`,`expires_at`),
  ADD KEY `payment_attempts_customer_status` (`customer_id`,`status`);

ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_attempts`
  MODIFY `id` bigint unsigned NOT NULL AUTO_INCREMENT;
