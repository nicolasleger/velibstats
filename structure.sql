-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Client :  localhost:3306
-- Généré le :  Jeu 11 Janvier 2018 à 07:58
-- Version du serveur :  10.1.25-MariaDB-
-- Version de PHP :  7.0.22-0ubuntu0.17.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `c6velib`
--

-- --------------------------------------------------------

--
-- Structure de la table `resumeStatus`
--

CREATE TABLE `resumeStatus` (
  `id` int(11) NOT NULL,
  `code` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `duree` int(4) NOT NULL,
  `nbBikeMin` int(3) NOT NULL,
  `nbBikeMax` int(3) NOT NULL,
  `nbBikeMoyenne` decimal(5,2) NOT NULL,
  `nbBikePris` int(3) NOT NULL,
  `nbBikeRendu` int(3) NOT NULL,
  `nbEBikeMin` int(3) NOT NULL,
  `nbEBikeMax` int(3) NOT NULL,
  `nbEBikeMoyenne` decimal(5,2) NOT NULL,
  `nbEBikePris` int(3) NOT NULL,
  `nbEBikeRendu` int(3) NOT NULL,
  `nbFreeEDockMin` int(3) NOT NULL,
  `nbFreeEDockMax` int(3) NOT NULL,
  `nbFreeEDockMoyenne` decimal(5,2) NOT NULL,
  `nbEDock` int(3) NOT NULL,
  `nbBikeOverflowMin` int(3) NOT NULL,
  `nbBikeOverflowMax` int(3) NOT NULL,
  `nbBikeOverflowMoyenne` decimal(5,2) NOT NULL,
  `nbEBikeOverflowMin` int(3) NOT NULL,
  `nbEBikeOverflowMax` int(3) NOT NULL,
  `nbEBikeOverflowMoyenne` decimal(5,2) NOT NULL,
  `maxBikeOverflow` int(3) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Structure de la table `stations`
--

CREATE TABLE `stations` (
  `code` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `latitude` decimal(16,14) NOT NULL,
  `longitude` decimal(16,14) NOT NULL,
  `type` varchar(256) NOT NULL,
  `dateOuverture` date NOT NULL,
  `adresse` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `status`
--

CREATE TABLE `status` (
  `id` int(11) NOT NULL,
  `code` int(11) NOT NULL,
  `idConso` int(11) NOT NULL,
  `state` varchar(32) NOT NULL,
  `nbBike` int(3) NOT NULL,
  `nbEBike` int(3) NOT NULL,
  `nbFreeEDock` int(3) NOT NULL,
  `nbEDock` int(3) NOT NULL,
  `nbBikeOverflow` int(3) NOT NULL,
  `nbEBikeOverflow` int(3) NOT NULL,
  `maxBikeOverflow` int(3) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `statusConso`
--

CREATE TABLE `statusConso` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nbStation` int(11) DEFAULT NULL,
  `nbBike` int(11) DEFAULT NULL,
  `nbEbike` int(11) DEFAULT NULL,
  `nbFreeEDock` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `resumeStatus`
--
ALTER TABLE `resumeStatus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`),
  ADD KEY `duree` (`duree`);

--
-- Index pour la table `stations`
--
ALTER TABLE `stations`
  ADD PRIMARY KEY (`code`);

--
-- Index pour la table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `station` (`code`);

--
-- Index pour la table `statusConso`
--
ALTER TABLE `statusConso`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `resumeStatus`
--
ALTER TABLE `resumeStatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `status`
--
ALTER TABLE `status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `statusConso`
--
ALTER TABLE `statusConso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
