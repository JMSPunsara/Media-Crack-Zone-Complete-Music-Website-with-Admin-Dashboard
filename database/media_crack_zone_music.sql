-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 12, 2025 at 07:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `media_crack_zone_music`
--

-- --------------------------------------------------------

--
-- Table structure for table `downloads`
--

CREATE TABLE `downloads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `track_id`, `created_at`) VALUES
(9, 2, 1, '2025-07-15 16:49:06'),
(10, 2, 2, '2025-07-15 16:49:38');

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `name`, `code`, `created_at`) VALUES
(1, 'English', 'en', '2025-07-14 13:54:18'),
(2, 'Hindi', 'hi', '2025-07-14 13:54:18'),
(3, 'Spanish', 'es', '2025-07-14 13:54:18'),
(4, 'French', 'fr', '2025-07-14 13:54:18'),
(5, 'German', 'de', '2025-07-14 13:54:18'),
(6, 'Italian', 'it', '2025-07-14 13:54:18'),
(7, 'Portuguese', 'pt', '2025-07-14 13:54:18'),
(8, 'Arabic', 'ar', '2025-07-14 13:54:18'),
(9, 'Chinese', 'zh', '2025-07-14 13:54:18'),
(10, 'Japanese', 'ja', '2025-07-14 13:54:18'),
(11, 'Korean', 'ko', '2025-07-14 13:54:18'),
(12, 'Russian', 'ru', '2025-07-14 13:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `moods`
--

CREATE TABLE `moods` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3498db',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moods`
--

INSERT INTO `moods` (`id`, `name`, `description`, `color`, `created_at`) VALUES
(1, 'Happy', 'Upbeat and joyful music', '#f39c12', '2025-07-14 13:54:18'),
(2, 'Sad', 'Melancholic and emotional tracks', '#3498db', '2025-07-14 13:54:18'),
(3, 'Energetic', 'High energy and motivational', '#e74c3c', '2025-07-14 13:54:18'),
(4, 'Relaxing', 'Calm and peaceful music', '#2ecc71', '2025-07-14 13:54:18'),
(5, 'Romantic', 'Love songs and romantic ballads', '#e91e63', '2025-07-14 13:54:18'),
(6, 'Party', 'Dance and party music', '#9b59b6', '2025-07-14 13:54:18'),
(7, 'Focus', 'Music for concentration and work', '#34495e', '2025-07-14 13:54:18'),
(8, 'Workout', 'High tempo fitness music', '#ff5722', '2025-07-14 13:54:18'),
(9, 'Classical', 'Classical and orchestral pieces', '#795548', '2025-07-14 13:54:18'),
(10, 'Jazz', 'Jazz and blues music', '#607d8b', '2025-07-14 13:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlists`
--

INSERT INTO `playlists` (`id`, `user_id`, `name`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 2, 'Test01', 'Hindi', 0, '2025-07-15 06:14:39', '2025-07-15 06:18:32');

-- --------------------------------------------------------

--
-- Table structure for table `playlist_tracks`
--

CREATE TABLE `playlist_tracks` (
  `id` int(11) NOT NULL,
  `playlist_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `position` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlist_tracks`
--

INSERT INTO `playlist_tracks` (`id`, `playlist_id`, `track_id`, `position`, `created_at`) VALUES
(1, 1, 1, 1, '2025-07-15 06:17:51'),
(2, 1, 2, 2, '2025-07-15 06:18:32');

-- --------------------------------------------------------

--
-- Table structure for table `play_history`
--

CREATE TABLE `play_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `track_id` int(11) NOT NULL,
  `played_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `play_history`
--

INSERT INTO `play_history` (`id`, `user_id`, `track_id`, `played_at`) VALUES
(1, 2, 1, '2025-07-14 16:27:13'),
(2, 2, 1, '2025-07-14 16:27:13'),
(3, 2, 1, '2025-07-14 16:28:34'),
(4, 2, 1, '2025-07-14 16:29:49'),
(5, 2, 1, '2025-07-14 16:39:26'),
(6, 2, 1, '2025-07-14 16:39:43'),
(7, 3, 1, '2025-07-14 16:52:43'),
(8, 3, 2, '2025-07-14 16:52:48'),
(9, 3, 2, '2025-07-14 17:14:01'),
(10, 2, 1, '2025-07-15 02:35:33'),
(11, 2, 2, '2025-07-15 02:38:08'),
(12, 2, 1, '2025-07-15 06:12:13'),
(13, 2, 2, '2025-07-15 06:13:40'),
(14, 2, 1, '2025-07-15 06:13:46'),
(15, 2, 2, '2025-07-15 06:13:52'),
(16, 3, 2, '2025-07-15 06:39:19'),
(17, 3, 2, '2025-07-15 06:39:27'),
(18, 2, 1, '2025-07-15 07:26:08'),
(19, 2, 1, '2025-07-15 16:30:43'),
(20, 2, 1, '2025-07-15 17:03:02'),
(21, 2, 2, '2025-07-15 17:03:12'),
(22, 2, 2, '2025-07-15 17:15:42'),
(23, 2, 1, '2025-07-15 17:15:44'),
(24, 2, 2, '2025-07-15 17:15:49'),
(25, 2, 1, '2025-07-15 17:15:51'),
(26, 2, 2, '2025-07-15 17:16:15'),
(27, 2, 1, '2025-07-15 17:16:23');

-- --------------------------------------------------------

--
-- Table structure for table `tracks`
--

CREATE TABLE `tracks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist` varchar(255) NOT NULL,
  `album` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `cover_image` varchar(500) DEFAULT 'default-cover.jpg',
  `duration` int(11) DEFAULT 0,
  `language_id` int(11) DEFAULT NULL,
  `mood_id` int(11) DEFAULT NULL,
  `plays_count` int(11) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `downloads_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracks`
--

INSERT INTO `tracks` (`id`, `title`, `artist`, `album`, `file_path`, `cover_image`, `duration`, `language_id`, `mood_id`, `plays_count`, `uploaded_by`, `created_at`, `updated_at`, `downloads_count`) VALUES
(1, 'Akhiyaan Gulaab', 'Mitraz', 'Mitraz', '68752e2a82069.mp3', '68752e2a82170.png', 266, 2, 5, 32, 2, '2025-07-14 16:19:54', '2025-07-15 17:16:23', 0),
(2, '&quot;Tum Hi Ho Song&quot; Aashiqui 2 Full Song | Aditya Roy Kapur, Shraddha Kapoor New Song 2024 Love Songs', 'Arijit Singh', '', '687535d089fc8.mp3', '687535d08a2f4.jpg', 268, 2, 5, 23, 2, '2025-07-14 16:52:32', '2025-07-15 17:16:15', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `is_admin` tinyint(1) DEFAULT 0,
  `status` enum('active','suspended','banned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `email_verified`, `password`, `full_name`, `avatar`, `preferences`, `is_admin`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', 'admin@mediacrackzone.com', 1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'default-avatar.png', NULL, 1, 'active', '2025-07-14 13:54:18', '2025-07-14 15:57:51', NULL),
(2, 'ajith', '123@gmail.com', 1, '$2y$10$11A4IGBzIFKF2X7iBv7iUe6D9N4lKaaASPjom.3EV9nbAyXCjPZdC', 'Ajith Kumara', 'default-avatar.png', NULL, 1, 'active', '2025-07-14 13:56:37', '2025-07-14 15:57:51', '2025-06-30 15:57:51'),
(3, 'PunsaraJMS', '123456@gmail.com', 1, '$2y$10$JPBbST7H9Wt4T7dF8M4Tke9E7nX4Xc0GZb4isWoX3uZV.5awizp46', 'J M Shashin Punsara', 'avatar_3_1752504036.jpg', NULL, 0, 'active', '2025-07-14 14:19:37', '2025-07-14 16:02:06', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `downloads`
--
ALTER TABLE `downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_downloads` (`user_id`),
  ADD KEY `idx_track_downloads` (`track_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`track_id`),
  ADD KEY `track_id` (`track_id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `moods`
--
ALTER TABLE `moods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `playlist_tracks`
--
ALTER TABLE `playlist_tracks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `playlist_id` (`playlist_id`),
  ADD KEY `track_id` (`track_id`);

--
-- Indexes for table `play_history`
--
ALTER TABLE `play_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_played_at` (`user_id`,`played_at`),
  ADD KEY `idx_track_played_at` (`track_id`,`played_at`);

--
-- Indexes for table `tracks`
--
ALTER TABLE `tracks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `language_id` (`language_id`),
  ADD KEY `mood_id` (`mood_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `downloads`
--
ALTER TABLE `downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `moods`
--
ALTER TABLE `moods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `playlist_tracks`
--
ALTER TABLE `playlist_tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `play_history`
--
ALTER TABLE `play_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `tracks`
--
ALTER TABLE `tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `downloads`
--
ALTER TABLE `downloads`
  ADD CONSTRAINT `downloads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `downloads_ibfk_2` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlist_tracks`
--
ALTER TABLE `playlist_tracks`
  ADD CONSTRAINT `playlist_tracks_ibfk_1` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_tracks_ibfk_2` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `play_history`
--
ALTER TABLE `play_history`
  ADD CONSTRAINT `play_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `play_history_ibfk_2` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tracks`
--
ALTER TABLE `tracks`
  ADD CONSTRAINT `tracks_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tracks_ibfk_2` FOREIGN KEY (`mood_id`) REFERENCES `moods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tracks_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
