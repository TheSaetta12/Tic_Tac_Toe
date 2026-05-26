-- phpMyAdmin SQL Dump
-- version 5.2.3deb1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Mag 26, 2026 alle 12:01
-- Versione del server: 11.8.3-MariaDB-1+b1 from Debian
-- Versione PHP: 8.4.11

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
-- Struttura della tabella `partite`
--

CREATE TABLE `partite` (
  `partite_id` int(11) NOT NULL,
  `partite_vinte` int(3) NOT NULL,
  `partite_perse` int(3) NOT NULL,
  `partite_pareggiate` int(3) NOT NULL,
  `utenti_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dump dei dati per la tabella `partite`
--

INSERT INTO `partite` (`partite_id`, `partite_vinte`, `partite_perse`, `partite_pareggiate`, `utenti_id`) VALUES
(1, 15, 8, 7, 7);

-- --------------------------------------------------------

--
-- Struttura della tabella `tentativi_accesso`
--

CREATE TABLE `tentativi_accesso` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `tentativo_timestamp` timestamp NULL DEFAULT current_timestamp(),
  `esito` varchar(20) DEFAULT NULL,
  `username_coinvolto` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dump dei dati per la tabella `tentativi_accesso`
--

INSERT INTO `tentativi_accesso` (`id`, `ip_address`, `tentativo_timestamp`, `esito`, `username_coinvolto`) VALUES
(1, '127.0.0.1', '2026-05-26 11:52:12', 'attesa_2fa', 'Pietro'),
(2, '127.0.0.1', '2026-05-26 11:52:22', 'successo', 'Pietro');

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

CREATE TABLE `utenti` (
  `utenti_id` int(11) NOT NULL,
  `utenti_username` varchar(64) NOT NULL,
  `utenti_password` varchar(64) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`utenti_id`, `utenti_username`, `utenti_password`, `otp_code`, `otp_expiry`) VALUES
(1, 'Guido', '1234', NULL, NULL),
(5, 'Tommaso', '4321', NULL, NULL),
(7, 'Pietro', '5555', NULL, NULL),
(9, 'pincopallino', 'boh', NULL, NULL),
(17, 'simone', 'Simone1234!', NULL, NULL);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `partite`
--
ALTER TABLE `partite`
  ADD PRIMARY KEY (`partite_id`);

--
-- Indici per le tabelle `tentativi_accesso`
--
ALTER TABLE `tentativi_accesso`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`utenti_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `partite`
--
ALTER TABLE `partite`
  MODIFY `partite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `tentativi_accesso`
--
ALTER TABLE `tentativi_accesso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `utenti_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
