-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 03, 2025 at 11:31 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qcm_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `qcms`
--

CREATE TABLE `qcms` (
  `id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `date_examen` date NOT NULL,
  `duree_min` int(11) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 0,
  `tentative_max` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `auteur_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qcms`
--

INSERT INTO `qcms` (`id`, `titre`, `date_examen`, `duree_min`, `visible`, `tentative_max`, `auteur_id`, `created_at`) VALUES
(6, 'Session 2.4', '2025-02-20', 24, 3, 1, 3, '2025-05-02 16:29:34'),
(17, 'NFA021', '2025-05-23', 12, 1, 3, 1, '2025-05-03 20:44:02'),
(18, 'NFA042', '2025-05-23', 12, 1, 3, 1, '2025-05-03 20:44:13');

-- --------------------------------------------------------

--
-- Table structure for table `qcm_answers`
--

CREATE TABLE `qcm_answers` (
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qcm_answers`
--

INSERT INTO `qcm_answers` (`attempt_id`, `question_id`, `selected`) VALUES
(13, 20, '[\"A\"]'),
(13, 21, '[\"C\"]'),
(13, 22, '[\"C\"]'),
(14, 17, '[\"B\"]'),
(14, 18, '[\"C\"]'),
(14, 19, '[\"C\"]'),
(15, 20, '[\"B\"]'),
(15, 21, '[\"B\"]'),
(15, 22, '[\"A\"]'),
(16, 20, '[\"B\"]'),
(16, 21, '[\"B\"]'),
(16, 22, '[\"B\"]'),
(17, 17, '[\"B\"]'),
(17, 18, '[\"B\"]'),
(17, 19, '[\"C\"]');

-- --------------------------------------------------------

--
-- Table structure for table `qcm_attempts`
--

CREATE TABLE `qcm_attempts` (
  `id` int(11) NOT NULL,
  `qcm_id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `finished` tinyint(4) NOT NULL DEFAULT 0,
  `good` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qcm_attempts`
--

INSERT INTO `qcm_attempts` (`id`, `qcm_id`, `eleve_id`, `start_time`, `end_time`, `finished`, `good`, `total`) VALUES
(13, 18, 2, '2025-05-03 22:44:23', '2025-05-03 22:44:31', 1, 1, 3),
(14, 17, 2, '2025-05-03 22:44:44', '2025-05-03 22:44:52', 1, 1, 3),
(15, 18, 2, '2025-05-03 22:45:19', '2025-05-03 22:53:16', 1, 0, 3),
(16, 18, 2, '2025-05-03 22:54:38', '2025-05-03 22:54:43', 1, 1, 3),
(17, 17, 2, '2025-05-03 22:54:50', '2025-05-03 22:54:56', 1, 0, 3),
(18, 17, 2, '2025-05-03 22:57:29', '2025-05-03 23:11:27', 1, 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `qcm_questions`
--

CREATE TABLE `qcm_questions` (
  `qcm_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `ordre` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qcm_questions`
--

INSERT INTO `qcm_questions` (`qcm_id`, `question_id`, `ordre`) VALUES
(17, 17, 2),
(17, 18, 3),
(17, 19, 1),
(18, 20, 3),
(18, 21, 2),
(18, 22, 1);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `theme_id` int(11) NOT NULL,
  `auteur_id` int(11) NOT NULL,
  `texte_question` text NOT NULL,
  `is_multiple` tinyint(1) NOT NULL DEFAULT 0,
  `reponses` longtext NOT NULL,
  `bonne_reponse` varchar(1) DEFAULT NULL,
  `subtheme_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `theme_id`, `auteur_id`, `texte_question`, `is_multiple`, `reponses`, `bonne_reponse`, `subtheme_id`) VALUES
(17, 18, 1, 'az\'eezrazer', 1, '[{\"label\":\"A\",\"texte\":\"t\\\"\'taz\'\\\"tazert\",\"correct\":false},{\"label\":\"B\",\"texte\":\"\'teryhzrteyzert\",\"correct\":true},{\"label\":\"C\",\"texte\":\"zertezrttze\'t\",\"correct\":true}]', NULL, 19),
(18, 18, 1, 'zert\'(tze\'(t\'t', 0, '[{\"label\":\"A\",\"texte\":\"ze\'tze\'tzertgsdfg\",\"correct\":false},{\"label\":\"B\",\"texte\":\"zertzte\'te\'tzsd\",\"correct\":false},{\"label\":\"C\",\"texte\":\"ztertze\'t\'etze\'tz\",\"correct\":true},{\"label\":\"D\",\"texte\":\"z\'te\'tze\'tze\'t\",\"correct\":false}]', NULL, 20),
(19, 18, 1, 'zert\'(tze\'(t\'tertzert', 1, '[{\"label\":\"A\",\"texte\":\"ze\'tze\'tzertgsdfg\",\"correct\":false},{\"label\":\"B\",\"texte\":\"zertzte\'te\'tzsd\",\"correct\":true},{\"label\":\"C\",\"texte\":\"ztertze\'t\'etze\'tz\",\"correct\":false},{\"label\":\"D\",\"texte\":\"z\'te\'tze\'tze\'t\",\"correct\":false}]', NULL, 21),
(20, 19, 1, 'zert\'(tze\'(t\'tertzert', 1, '[{\"label\":\"A\",\"texte\":\"ze\'tze\'tzertgsdfg\",\"correct\":true},{\"label\":\"B\",\"texte\":\"zertzte\'te\'tzsdzerg\",\"correct\":true},{\"label\":\"C\",\"texte\":\"ztertze\'t\'etze\'tz\",\"correct\":true},{\"label\":\"D\",\"texte\":\"z\'te\'tze\'tze\'t\",\"correct\":true}]', NULL, 22),
(21, 19, 1, 'zert\'(tze\'(t\'tertzert', 1, '[{\"label\":\"A\",\"texte\":\"ze\'tze\'tzertgsdfg\",\"correct\":false},{\"label\":\"B\",\"texte\":\"zertzte\'te\'tzsdzerg\",\"correct\":false},{\"label\":\"C\",\"texte\":\"ztertze\'t\'etze\'tz\",\"correct\":true},{\"label\":\"D\",\"texte\":\"z\'te\'tze\'tze\'t\",\"correct\":false}]', NULL, 22),
(22, 19, 1, 'zert\'(tze\'(t\'tertzert', 1, '[{\"label\":\"A\",\"texte\":\"ze\'tze\'tzertgsdfg\",\"correct\":false},{\"label\":\"B\",\"texte\":\"zertzte\'te\'tzsdzerg\",\"correct\":true},{\"label\":\"C\",\"texte\":\"ztertze\'t\'etze\'tz\",\"correct\":false},{\"label\":\"D\",\"texte\":\"z\'te\'tze\'tze\'t\",\"correct\":false}]', NULL, 22);

-- --------------------------------------------------------

--
-- Table structure for table `soumissions`
--

CREATE TABLE `soumissions` (
  `id` int(11) NOT NULL,
  `qcm_id` int(11) NOT NULL,
  `eleve_id` int(11) NOT NULL,
  `date_soumission` datetime NOT NULL,
  `note` float DEFAULT 0,
  `reponses` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subthemes`
--

CREATE TABLE `subthemes` (
  `id` int(11) NOT NULL,
  `theme_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subthemes`
--

INSERT INTO `subthemes` (`id`, `theme_id`, `nom`) VALUES
(19, 18, 'Session 1'),
(20, 18, 'Session 2'),
(21, 18, 'Session 3'),
(22, 19, 'Session 1'),
(23, 19, 'Session 2'),
(24, 19, 'Session 3');

-- --------------------------------------------------------

--
-- Table structure for table `themes`
--

CREATE TABLE `themes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `themes`
--

INSERT INTO `themes` (`id`, `nom`) VALUES
(18, 'NFA021'),
(19, 'NFA042');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `statut` enum('prof','eleve') NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nom`, `mot_de_passe`, `statut`, `email`) VALUES
(1, 'NGO', '$2y$10$kKXoa9RITesPVrdQqtz5NueOkE/sGmPUyqLpCBIgAkLjeJ/zG/WlK', 'prof', 'thanhlong1793@gmail.com'),
(2, 'Thanh Long', '$2y$10$hDaN0bBXN/BQtfOC3wPH8uVxQ5yB9PdfvO6N1Z9899fhIN5oOyEzu', 'eleve', 'thanhlong1193@live.com'),
(3, 'NGUYEN', '$2y$10$Uxc6KL2diDnUJFmlS1rvo.fgnJ4XhMhbGEutbV3FVhNOSpjD4eTWe', 'prof', 'thanhlong1111@gmail.com'),
(4, 'Thanh', '$2y$10$OOK4TR0Aqxrlzoa6vOmb8.iv8Xw0BfGV.P5SdW8238rv/hHXdctX.', 'prof', 'asterix_long@yahoo.fr');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `qcms`
--
ALTER TABLE `qcms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qcm_answers`
--
ALTER TABLE `qcm_answers`
  ADD PRIMARY KEY (`attempt_id`,`question_id`),
  ADD KEY `fk_answer_question` (`question_id`);

--
-- Indexes for table `qcm_attempts`
--
ALTER TABLE `qcm_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `eleve_id` (`eleve_id`),
  ADD KEY `fk_attempt_qcm` (`qcm_id`);

--
-- Indexes for table `qcm_questions`
--
ALTER TABLE `qcm_questions`
  ADD PRIMARY KEY (`qcm_id`,`question_id`),
  ADD KEY `fk_qcm_questions_question` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `theme_id` (`theme_id`),
  ADD KEY `auteur_id` (`auteur_id`),
  ADD KEY `subtheme_id` (`subtheme_id`);

--
-- Indexes for table `soumissions`
--
ALTER TABLE `soumissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qcm_id` (`qcm_id`),
  ADD KEY `eleve_id` (`eleve_id`);

--
-- Indexes for table `subthemes`
--
ALTER TABLE `subthemes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `theme_id` (`theme_id`);

--
-- Indexes for table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `qcms`
--
ALTER TABLE `qcms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `qcm_attempts`
--
ALTER TABLE `qcm_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `soumissions`
--
ALTER TABLE `soumissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subthemes`
--
ALTER TABLE `subthemes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `themes`
--
ALTER TABLE `themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `qcm_answers`
--
ALTER TABLE `qcm_answers`
  ADD CONSTRAINT `fk_answer_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `qcm_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qcm_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `qcm_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qcm_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qcm_attempts`
--
ALTER TABLE `qcm_attempts`
  ADD CONSTRAINT `fk_attempt_qcm` FOREIGN KEY (`qcm_id`) REFERENCES `qcms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qcm_attempts_ibfk_1` FOREIGN KEY (`qcm_id`) REFERENCES `qcms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qcm_attempts_ibfk_2` FOREIGN KEY (`eleve_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qcm_questions`
--
ALTER TABLE `qcm_questions`
  ADD CONSTRAINT `fk_qcm_questions_qcm` FOREIGN KEY (`qcm_id`) REFERENCES `qcms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qcm_questions_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qcm_questions_ibfk_1` FOREIGN KEY (`qcm_id`) REFERENCES `qcms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qcm_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`),
  ADD CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `questions_ibfk_3` FOREIGN KEY (`subtheme_id`) REFERENCES `subthemes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `soumissions`
--
ALTER TABLE `soumissions`
  ADD CONSTRAINT `soumissions_ibfk_1` FOREIGN KEY (`qcm_id`) REFERENCES `qcms` (`id`),
  ADD CONSTRAINT `soumissions_ibfk_2` FOREIGN KEY (`eleve_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `subthemes`
--
ALTER TABLE `subthemes`
  ADD CONSTRAINT `subthemes_ibfk_1` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
