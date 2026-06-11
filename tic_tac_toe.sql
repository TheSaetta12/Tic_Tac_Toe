-- phpMyAdmin SQL Dump
-- version 5.2.3deb1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 11, 2026 at 01:08 PM
-- Server version: 11.8.3-MariaDB-1+b1 from Debian
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tic_tac_toe`
--

-- --------------------------------------------------------

--
-- Table structure for table `partite`
--

CREATE TABLE `partite` (
  `partite_id` int(11) NOT NULL,
  `partite_vinte` int(3) NOT NULL,
  `partite_perse` int(3) NOT NULL,
  `partite_pareggiate` int(3) NOT NULL,
  `utenti_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `partite`
--

INSERT INTO `partite` (`partite_id`, `partite_vinte`, `partite_perse`, `partite_pareggiate`, `utenti_id`) VALUES
(1, 18, 27, 10, 7),
(2, 0, 0, 0, 20);

-- --------------------------------------------------------

--
-- Table structure for table `tentativi_accesso`
--

CREATE TABLE `tentativi_accesso` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `tentativo_timestamp` timestamp NULL DEFAULT current_timestamp(),
  `esito` varchar(50) NOT NULL,
  `username_coinvolto` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `tentativi_accesso`
--

INSERT INTO `tentativi_accesso` (`id`, `ip_address`, `tentativo_timestamp`, `esito`, `username_coinvolto`) VALUES
(1, '127.0.0.1', '2026-06-11 12:39:25', 'fallimento_otp', 'Pietro'),
(2, '127.0.0.1', '2026-06-11 12:39:34', 'fallimento', 'Pietro'),
(3, '127.0.0.1', '2026-06-11 12:40:15', 'attesa_2fa', 'Leon'),
(4, '127.0.0.1', '2026-06-11 12:40:21', 'fallimento_otp', 'Leon'),
(5, '127.0.0.1', '2026-06-11 12:48:30', 'attesa_2fa', 'Pietro'),
(6, '127.0.0.1', '2026-06-11 12:48:33', 'fallimento_otp', 'Pietro'),
(7, '127.0.0.1', '2026-06-11 12:48:37', 'fallimento_otp', 'Pietro'),
(8, '127.0.0.1', '2026-06-11 12:49:06', 'attesa_2fa', 'Pietro'),
(9, '127.0.0.1', '2026-06-11 12:57:22', 'attesa_2fa', 'Pietro'),
(10, '127.0.0.1', '2026-06-11 12:57:27', 'successo', 'Pietro'),
(11, '127.0.0.1', '2026-06-11 12:57:35', 'attesa_2fa', 'Pietro'),
(12, '127.0.0.1', '2026-06-11 12:57:38', 'fallimento_otp', 'Pietro'),
(13, '127.0.0.1', '2026-06-11 12:57:44', 'successo', 'Pietro'),
(14, '127.0.0.1', '2026-06-11 12:59:09', 'fallimento', 'Leon'),
(15, '127.0.0.1', '2026-06-11 12:59:14', 'fallimento', 'Leon'),
(16, '127.0.0.1', '2026-06-11 12:59:19', 'fallimento', 'Leon'),
(17, '127.0.0.1', '2026-06-11 12:59:24', 'fallimento', 'Leon'),
(18, '127.0.0.1', '2026-06-11 12:59:28', 'fallimento', 'Leon'),
(19, '127.0.0.1', '2026-06-11 13:07:30', 'fallimento', 'Leon');

-- --------------------------------------------------------

--
-- Table structure for table `utenti`
--

CREATE TABLE `utenti` (
  `utenti_id` int(11) NOT NULL,
  `utenti_username` varchar(64) NOT NULL,
  `utenti_password` varchar(64) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utenti`
--

INSERT INTO `utenti` (`utenti_id`, `utenti_username`, `utenti_password`, `otp_code`, `otp_expiry`) VALUES
(1, 'Guido', '1234', NULL, NULL),
(5, 'Tommaso', '4321', NULL, NULL),
(7, 'Pietro', '5555', NULL, NULL),
(9, 'pincopallino', 'boh', NULL, NULL),
(17, 'simone', 'Simone1234!', NULL, NULL),
(18, 'Albert', 'WEsker97@', NULL, NULL),
(19, 'Leon', 'Kennedy12@', '260540', '2026-06-11 12:50:15'),
(20, 'Alex', 'Keny123@', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `partite`
--
ALTER TABLE `partite`
  ADD PRIMARY KEY (`partite_id`);

--
-- Indexes for table `tentativi_accesso`
--
ALTER TABLE `tentativi_accesso`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`utenti_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `partite`
--
ALTER TABLE `partite`
  MODIFY `partite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tentativi_accesso`
--
ALTER TABLE `tentativi_accesso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `utenti`
--
ALTER TABLE `utenti`
  MODIFY `utenti_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
