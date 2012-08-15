-- phpMyAdmin SQL Dump
-- version 2.11.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 26, 2009 at 01:48 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `gsc`
--

-- --------------------------------------------------------

--
-- Table structure for table `scrapbook_group`
--

CREATE TABLE `scrapbook_group` (
  `idgroup` int(11) NOT NULL auto_increment,
  `idscrapbook` int(11) NOT NULL,
  `idjournal` int(11) unsigned NOT NULL,
  `submitdate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`idgroup`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scrapbook_image`
--

CREATE TABLE `scrapbook_image` (
  `identry` int(11) NOT NULL auto_increment,
  `idscrapbook` int(11) NOT NULL,
  `idimage` int(11) unsigned NOT NULL,
  `submittedby` varchar(15) collate latin1_general_ci NOT NULL,
  `submitdate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `comment` tinytext collate latin1_general_ci NOT NULL,
  `source` tinytext collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`identry`),
  KEY `idgallery` (`idscrapbook`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scrapbook_main`
--

CREATE TABLE `scrapbook_main` (
  `idscrapbook` int(11) NOT NULL auto_increment,
  `description` varchar(40) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`idscrapbook`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scrapbook_toc`
--

CREATE TABLE `scrapbook_toc` (
  `idgroup` int(11) NOT NULL,
  `idimage` int(11) unsigned NOT NULL,
  `tocpos` int(11) NOT NULL,
  KEY `idgroup` (`idgroup`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `table_cols`
--

CREATE TABLE `table_cols` (
  `idcol` int(11) NOT NULL auto_increment,
  `idtable` int(11) NOT NULL,
  `name` varchar(60) collate latin1_general_ci NOT NULL,
  `coltype` varchar(80) collate latin1_general_ci NOT NULL,
  `description` varchar(80) collate latin1_general_ci NOT NULL,
  `heading` varchar(30) collate latin1_general_ci NOT NULL,
  `defaultval` varchar(30) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`idcol`),
  KEY `idtable` (`idtable`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `table_def`
--

CREATE TABLE `table_def` (
  `idtable` int(11) NOT NULL auto_increment,
  `title` varchar(20) collate latin1_general_ci NOT NULL,
  `description` varchar(80) collate latin1_general_ci NOT NULL,
  `filename` varchar(20) collate latin1_general_ci NOT NULL,
  `lastedit` int(11) NOT NULL,
  PRIMARY KEY  (`idtable`),
  KEY `filename` (`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `table_ints`
--

CREATE TABLE `table_ints` (
  `rownum` int(11) NOT NULL,
  `col` int(11) NOT NULL,
  `celldata` int(11) NOT NULL,
  KEY `row` (`rownum`),
  KEY `col` (`col`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `table_strings`
--

CREATE TABLE `table_strings` (
  `rownum` int(11) NOT NULL,
  `col` int(11) NOT NULL,
  `celldata` mediumtext collate latin1_general_ci NOT NULL,
  KEY `idtable` (`rownum`,`col`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `_filedates`
--

CREATE TABLE `_filedates` (
  `filename` varchar(50) collate latin1_general_ci NOT NULL,
  `lastedit` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `_imagefiles`
--

CREATE TABLE `_imagefiles` (
  `filenum` int(10) unsigned NOT NULL auto_increment,
  `idimage` int(10) unsigned NOT NULL,
  `height` int(10) unsigned NOT NULL,
  `width` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`filenum`),
  KEY `idimage` (`idimage`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `_images`
--

CREATE TABLE `_images` (
  `idimage` int(10) NOT NULL auto_increment,
  `alt` varchar(60) collate latin1_general_ci NOT NULL,
  `filename` varchar(50) collate latin1_general_ci NOT NULL,
  `type` enum('png','jpg','gif') collate latin1_general_ci NOT NULL,
  `width` smallint(6) NOT NULL,
  `height` smallint(6) NOT NULL,
  PRIMARY KEY  (`idimage`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- Table structure for table `_mst`
--

CREATE TABLE `_mst` (
  `page` varchar(30) collate latin1_general_ci NOT NULL,
  `longname` varchar(80) collate latin1_general_ci NOT NULL,
  `shortname` varchar(50) collate latin1_general_ci NOT NULL,
  `contained` tinyint(1) NOT NULL,
  `html` varchar(240) collate latin1_general_ci NOT NULL,
  `keyword` varchar(25) collate latin1_general_ci NOT NULL,
  `pseudoclass` varchar(15) collate latin1_general_ci NOT NULL,
  `assignment` varchar(132) collate latin1_general_ci NOT NULL,
  KEY `shortname` (`shortname`,`html`,`keyword`,`assignment`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `_pos`
--

CREATE TABLE `_pos` (
  `posid` bigint(20) unsigned NOT NULL auto_increment,
  `session` varchar(45) collate latin1_general_ci NOT NULL,
  `type` varchar(20) collate latin1_general_ci NOT NULL,
  `rev` tinyint(4) NOT NULL,
  `count` smallint(4) NOT NULL,
  `lasthit` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `timeout` int(11) NOT NULL,
  PRIMARY KEY  (`posid`),
  KEY `session` (`session`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `_posdata`
--

CREATE TABLE `_posdata` (
  `posindex` bigint(20) unsigned NOT NULL,
  `sequence` smallint(5) unsigned NOT NULL,
  `data` varchar(254) collate latin1_general_ci NOT NULL,
  KEY `posindex` (`posindex`),
  KEY `sequence` (`sequence`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `_text`
--

CREATE TABLE `_text` (
  `textid` int(10) unsigned NOT NULL auto_increment,
  `iscreole` tinyint(1) NOT NULL,
  `source` varchar(16) collate latin1_general_ci NOT NULL,
  `lastedit` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `text` mediumtext collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`textid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1774 ;
