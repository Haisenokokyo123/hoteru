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

ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
