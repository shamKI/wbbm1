-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jan 09, 2017 at 04:54 PM
-- Server version: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `wbbm`
--
CREATE DATABASE IF NOT EXISTS `wbbm` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `wbbm`;

-- --------------------------------------------------------

--
-- Table structure for table `tbl01_user`
--

CREATE TABLE IF NOT EXISTS `tbl01_user` (
  `nic` varchar(15) NOT NULL COMMENT 'user id',
  `first_name` varchar(75) NOT NULL,
  `last_name` varchar(75) NOT NULL,
  `password` varchar(50) NOT NULL,
  `designation` varchar(25) NOT NULL,
  `user_role` enum('admin','manager','operator','super-admin') NOT NULL DEFAULT 'operator',
  `email` varchar(250) DEFAULT NULL,
  `user_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:active, 0:inactive',
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`nic`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl01_user`
--

INSERT INTO `tbl01_user` (`nic`, `first_name`, `last_name`, `password`, `designation`, `user_role`, `email`, `user_status`, `last_login`) VALUES
('000000000V', 'virtual', 'admin', '$1$DM/.QO0.$GldGeTgzfH1oOoY2OE2HE1', 'Super Administration', 'super-admin', NULL, 1, '2017-01-08 20:51:57'),
('778601830V', 'Shamila', 'Koralage', '$1$0A1.HE..$mIvtUUO8Q8fxfF3I4my5g.', 'Manager', 'admin', 'indunil10@gmail.com', 1, '2016-12-10 08:50:29'),
('792755259V', 'R', 'Pradeep', '$1$oy5.RT4.$CggL9h31iun85iIfvbAoT1', 'Branch Manager', 'operator', 'ruckshanweerasinghe@yahoo.com', 1, NULL),
('838301940V', 'K', 'Sutharshini', '$1$4n/.5G2.$czxnsyNt9V8MJMz1HrMjI.', 'Accountant', 'manager', 'wisdom8318@yahoo.com', 1, NULL),
('874801968V', 'Tharangi', 'Perera', '$1$Gx1.X60.$03YT7.jSTYY1kIIEuQoIl0', 'Computer Operator', 'operator', 'sudami9@gmail.com', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl02_log`
--

CREATE TABLE IF NOT EXISTS `tbl02_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('admin','user','stock','reports','purchase','sales','financial') NOT NULL DEFAULT 'admin',
  `description` varchar(200) NOT NULL,
  `user_id` varchar(15) NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=130 ;

--
-- Dumping data for table `tbl02_log`
--

INSERT INTO `tbl02_log` (`id`, `category`, `description`, `user_id`, `date_added`) VALUES
(1, 'admin', 'Add user: 778601830V/Manager/admin.', '000000000V', '2016-08-09 23:43:02'),
(2, 'admin', 'Add user: 838301940v/Accountant/manager.', '000000000V', '2016-08-09 23:43:42'),
(3, 'admin', 'Add user: 792755259V/Branch Manager/manager.', '000000000V', '2016-08-09 23:44:22'),
(4, 'admin', 'Add user: 874801968v/Computer Operator/operator.', '000000000V', '2016-08-09 23:44:52'),
(5, 'stock', 'Add category: CLICK START - Grade - 1.', '000000000V', '2016-08-10 23:46:54'),
(7, 'stock', 'Add category: EDEXEL - Grade 10.', '000000000V', '2016-08-10 23:47:47'),
(8, 'stock', 'Add category: EDEXEL - Grade 9.', '000000000V', '2016-08-10 23:48:15'),
(9, 'stock', 'Add category: EDEXEL IGCSE - O\\L.', '000000000V', '2016-08-10 23:48:34'),
(10, 'stock', 'Add category: EDEXEL - Grade 8.', '000000000V', '2016-08-10 23:49:03'),
(11, 'stock', 'Add category: EDEXEL - Grade 7.', '000000000V', '2016-08-10 23:49:19'),
(12, 'stock', 'Add category: CAMBRIDGE - Grade 10.', '000000000V', '2016-08-10 23:49:46'),
(13, 'stock', 'Add category: CAMBRIDGE - Grade 9.', '000000000V', '2016-08-10 23:49:59'),
(14, 'stock', 'Add category: CAMBRIDGE - Grade 8.', '000000000V', '2016-08-10 23:50:09'),
(15, 'stock', 'Add category: CAMBRIDGE - Grade 7.', '000000000V', '2016-11-10 23:50:22'),
(16, 'stock', 'Add category: CAMBRIDGE - Grade 6.', '000000000V', '2016-11-10 23:50:33'),
(17, 'stock', 'Add category: CAMBRIDGE - A/L - Maths.', '000000000V', '2016-11-10 23:51:00'),
(18, 'stock', 'Add category: CAMBRIDGE - A/L - Science.', '000000000V', '2016-11-10 23:51:16'),
(19, 'stock', 'Add category: CAMBRIDGE - A/L - Commerce.', '000000000V', '2016-11-10 23:51:31'),
(20, 'stock', 'Add category: EDEXEL - Grade 6.', '000000000V', '2016-11-10 23:52:10'),
(21, 'stock', 'Add category: EDEXEL IGCSE - A\\L.', '000000000V', '2016-11-10 23:52:31'),
(22, 'stock', 'Add agent: Jeya Book Centre - Pettah.', '000000000V', '2016-11-10 23:53:46'),
(23, 'stock', 'Add agent: Lake House Bookshop.', '000000000V', '2016-11-10 23:56:41'),
(24, 'stock', 'Add agent: M. D. Gunasena & Co (Pvt) Ltd.', '000000000V', '2016-11-10 23:58:23'),
(25, 'stock', 'Add agent: Makeen Bookshop.', '000000000V', '2016-11-10 23:59:29'),
(26, 'stock', 'Add agent: Expographic Books Pvt Ltd.', '000000000V', '2016-11-11 00:01:12'),
(27, 'stock', 'Add agent: Sarasavi the bookshop.', '000000000V', '2016-11-11 00:02:00'),
(28, 'stock', 'Add agent: Vijitha Yapa Bookshop.', '000000000V', '2016-11-11 00:02:47'),
(29, 'stock', 'Add dealer: Emma Bookshop.', '000000000V', '2016-11-11 00:04:46'),
(30, 'stock', 'Add dealer: Ferno Bookshop.', '000000000V', '2016-11-11 00:06:15'),
(31, 'stock', 'Add dealer: Trinty Bookshop.', '000000000V', '2016-11-11 00:07:55'),
(32, 'stock', 'Add dealer: Yashoda Bookshop.', '000000000V', '2016-11-11 00:09:42'),
(35, 'stock', 'Add category: CLICK START  - Grade - 2.', '000000000V', '2016-11-11 00:12:48'),
(36, 'stock', 'Add category: CLICK START  - Grade - 3.', '000000000V', '2016-11-11 00:13:07'),
(37, 'stock', 'Add category: CLICK START  - Grade - 4.', '000000000V', '2016-11-11 00:13:26'),
(40, 'stock', 'Add dealer: Lyceum International School - wattala.', '000000000V', '2016-11-11 09:22:28'),
(41, 'stock', 'Add dealer: Lyceum International School - kandana.', '000000000V', '2016-11-11 09:23:50'),
(42, 'stock', 'Add dealer: OKI International School - Wattala.', '000000000V', '2016-11-11 09:29:00'),
(43, 'stock', 'Add dealer: OKI International School - Kandana Branch.', '000000000V', '2016-11-11 09:29:51'),
(44, 'stock', 'Add dealer: OKI International School -Kiribathgoda Branch.', '000000000V', '2016-11-11 09:32:24'),
(45, 'stock', 'Add dealer: Atamie International School - wattala.', '000000000V', '2016-11-11 09:33:33'),
(46, 'stock', 'Add dealer: Atamie International School - Jaela.', '000000000V', '2016-11-11 09:34:21'),
(47, 'stock', 'Add dealer: WISE International School.', '000000000V', '2016-11-11 09:35:29'),
(48, 'stock', 'Add dealer: Seventh day Adventist High School - Kandana.', '000000000V', '2016-11-11 09:36:58'),
(49, 'purchase', 'Add Purchase: 1.', '000000000V', '2016-12-07 18:48:46'),
(50, 'purchase', 'Complete Purchase: 1.', '000000000V', '2016-12-07 22:36:37'),
(55, 'stock', 'Discontinue book: 6.', '000000000V', '2016-12-09 13:20:38'),
(58, 'stock', 'Edit book: 7', '000000000V', '2016-12-09 13:27:22'),
(59, 'stock', 'Edit book: 7', '000000000V', '2016-12-09 13:27:54'),
(60, 'stock', 'Edit book: 8', '000000000V', '2016-12-09 13:28:42'),
(61, 'stock', 'Edit book: 5', '000000000V', '2016-12-09 13:29:34'),
(62, 'stock', 'Edit book: 5', '000000000V', '2016-12-09 13:30:05'),
(69, 'purchase', 'Add Purchase: 2.', '000000000V', '2016-12-09 16:41:31'),
(70, 'purchase', 'Cancel Purchase : 2.', '000000000V', '2016-12-09 16:42:06'),
(71, 'purchase', 'Add Purchase: 3.', '000000000V', '2016-12-09 16:43:03'),
(72, 'purchase', 'Complete Purchase: 3.', '000000000V', '2016-12-09 16:44:04'),
(73, 'purchase', 'Add Purchase: 4.', '000000000V', '2016-12-09 16:48:14'),
(74, 'purchase', 'Add Purchase: 5.', '000000000V', '2016-12-09 16:49:45'),
(75, 'purchase', 'Complete Purchase: 4.', '000000000V', '2016-12-09 16:52:26'),
(76, 'purchase', 'Add Purchase: 6.', '000000000V', '2016-12-09 16:54:52'),
(77, 'purchase', 'Add requisiton: 1.', '000000000V', '2016-12-09 16:56:02'),
(78, 'purchase', 'Add requisiton: 2.', '000000000V', '2016-12-09 16:57:36'),
(79, 'purchase', 'Add requisiton: 3.', '000000000V', '2016-12-09 16:58:27'),
(80, 'sales', 'Add sales: 1.', '000000000V', '2016-12-09 18:27:13'),
(81, 'sales', 'Add sales: 2.', '000000000V', '2016-12-09 18:32:24'),
(82, 'sales', 'Add sales: 3.', '000000000V', '2016-12-09 18:33:31'),
(83, 'sales', 'Add sales: 4.', '000000000V', '2016-12-09 18:36:08'),
(84, 'sales', 'Add sales: 5.', '000000000V', '2016-12-09 18:41:30'),
(85, 'financial', 'Pay credit:Jeya Book Centre - Pettah', '000000000V', '2016-12-09 18:43:43'),
(86, 'purchase', 'Add Purchase: 7.', '000000000V', '2016-12-09 19:50:47'),
(87, 'purchase', 'Add Purchase: 8.', '000000000V', '2016-12-09 19:52:15'),
(88, 'purchase', 'Add Purchase: 9.', '000000000V', '2016-12-09 19:54:17'),
(89, 'purchase', 'Complete Purchase: 5.', '000000000V', '2016-12-09 19:57:28'),
(90, 'purchase', 'Complete Purchase: 6.', '000000000V', '2016-12-09 19:58:03'),
(91, 'sales', 'Add sales: 6.', '000000000V', '2016-12-09 19:59:44'),
(92, 'sales', 'Add sales returns for sales: 2', '000000000V', '2016-12-09 20:00:15'),
(93, 'sales', 'Add sales returns for sales: 5', '000000000V', '2016-12-09 20:01:23'),
(94, 'purchase', 'Complete Purchase: 7.', '000000000V', '2016-12-09 20:02:14'),
(95, 'purchase', 'Add Purchase return: 1.', '000000000V', '2016-12-09 20:16:21'),
(96, 'purchase', 'Complete purchase return: 1.', '000000000V', '2016-12-09 20:17:18'),
(97, 'sales', 'Complete sales return: 1.', '000000000V', '2016-12-09 20:19:17'),
(98, 'sales', 'Add sales returns for sales: 5', '000000000V', '2016-12-09 20:21:32'),
(99, 'purchase', 'Add Purchase: 10.', '000000000V', '2016-12-09 20:27:45'),
(100, 'purchase', 'Add Purchase: 11.', '000000000V', '2016-12-09 20:29:00'),
(101, 'sales', 'Add sales: 7.', '000000000V', '2016-12-09 20:31:03'),
(102, 'sales', 'Add sales: 8.', '000000000V', '2016-12-09 20:32:13'),
(103, 'sales', 'Add sales returns for sales: 8', '000000000V', '2016-12-09 20:32:49'),
(104, 'purchase', 'Add requisiton: 4.', '000000000V', '2016-12-09 22:23:50'),
(105, 'purchase', 'Add requisiton: 5.', '000000000V', '2016-12-09 22:24:30'),
(106, 'purchase', 'Add requisiton: 6.', '000000000V', '2016-12-09 22:24:54'),
(107, 'financial', 'Receive Debit:Trinty Bookshop', '000000000V', '2016-12-09 22:36:40'),
(108, 'admin', 'Reset password: 778601830V.', '000000000V', '2016-12-10 08:48:00'),
(109, 'admin', 'Edit user: 792755259V/Branch Manager/operator.', '000000000V', '2016-12-10 08:48:33'),
(110, 'admin', 'Generate data backup.', '000000000V', '2016-12-10 08:49:43'),
(111, 'stock', 'Add category: EDEXEL - Grade 1.', '000000000V', '2016-12-10 08:51:55'),
(112, 'stock', 'Add book: 15.', '000000000V', '2016-12-10 08:53:50'),
(113, 'purchase', 'Add requisiton: 7.', '000000000V', '2016-12-10 08:56:13'),
(114, 'purchase', 'Add Purchase: 12.', '000000000V', '2016-12-10 08:58:02'),
(115, 'sales', 'Add sales: 9.', '000000000V', '2016-12-10 09:00:38'),
(116, 'reports', 'Generate Stock Report.', '000000000V', '2016-12-10 09:01:46'),
(117, 'reports', 'Generate purchase Report.', '000000000V', '2016-12-10 09:03:02'),
(118, 'financial', 'Receive Debit:Atamie International School - Wattala', '000000000V', '2016-12-10 09:07:04'),
(119, 'sales', 'Add sales: 10.', '000000000V', '2016-12-10 09:25:21'),
(120, 'purchase', 'Add Purchase: 13.', '000000000V', '2016-12-10 09:28:31'),
(121, 'purchase', 'Add Purchase: 14.', '000000000V', '2016-12-10 09:30:18'),
(122, 'purchase', 'Complete Purchase: 14.', '000000000V', '2016-12-10 09:31:34'),
(123, 'purchase', 'Add Purchase: 15.', '000000000V', '2016-12-10 09:36:21'),
(124, 'purchase', 'Complete Purchase: 15.', '000000000V', '2016-12-10 09:37:47'),
(125, 'sales', 'Add sales: 11.', '000000000V', '2016-12-18 09:54:03'),
(126, 'purchase', 'Add Purchase: 16.', '000000000V', '2017-01-08 20:52:46'),
(127, 'purchase', 'Add Purchase return: 2.', '000000000V', '2017-01-08 20:54:11'),
(128, 'reports', 'Generate Stock Report.', '000000000V', '2017-01-08 20:55:11'),
(129, 'reports', 'Generate sales Report.', '000000000V', '2017-01-08 20:56:11');

-- --------------------------------------------------------

--
-- Table structure for table `tbl03_category`
--

CREATE TABLE IF NOT EXISTS `tbl03_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `category_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:active 0: inactive',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;

--
-- Dumping data for table `tbl03_category`
--

INSERT INTO `tbl03_category` (`id`, `category_name`, `category_status`) VALUES
(1, 'click start - grade - 1', 1),
(2, 'cambridge -o/l', 1),
(3, 'edexel - grade 10', 1),
(4, 'edexel - grade 9', 1),
(5, 'edexel igcse - o\\l', 1),
(6, 'edexel - grade 8', 0),
(7, 'edexel - grade 7', 1),
(8, 'cambridge - grade 10', 1),
(9, 'cambridge - grade 9', 1),
(10, 'cambridge - grade 8', 1),
(11, 'cambridge - grade 7', 1),
(12, 'cambridge - grade 6', 1),
(13, 'cambridge - a/l - maths', 1),
(14, 'cambridge - a/l - science', 1),
(15, 'cambridge - a/l - commerce', 1),
(16, 'edexel - grade 6', 1),
(17, 'edexel igcse - a\\l', 1),
(18, 'click start  - grade - 2', 1),
(19, 'click start  - grade - 3', 1),
(20, 'click start  - grade - 4', 1),
(21, 'edexel - grade 1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl04_agent`
--

CREATE TABLE IF NOT EXISTS `tbl04_agent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(150) NOT NULL,
  `contact` varchar(200) DEFAULT NULL,
  `credit_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `agent_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:current 0:past',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `tbl04_agent`
--

INSERT INTO `tbl04_agent` (`id`, `agent_name`, `contact`, `credit_amount`, `agent_status`) VALUES
(1, 'Jeya Book Centre - Pettah', 'Peoples Park Complex, \r\nColombo 11. \r\nTelephone: 011 7400708-11, 011 2438227\r\nFax: 0112332939 \r\n', '13761.00', 1),
(2, 'Lake House Bookshop', 'No.100, Sir. Chittampalam A. Gardiner Mawatha, Colombo 02. \r\nTel: -(0094)-011-4734137/8 \r\n(0094)-011-4979646\r\nFax : (0094)-011-2430582\r\nE-mail : info@lakehousebookshop.com\r\n', '0.00', 1),
(3, 'M. D. Gunasena & Co (pvt) Ltd', ' No.20, San Sebastian Hill,\r\n Colombo 12.\r\nTel : +94-112-323 126 | +94-714-755 442\r\nFax : +94-112-436 528 | \r\nEmail : corpsales@mdgunasena.com\r\n', '0.00', 1),
(4, 'Makeen Bookshop', '50/1, Sir James Peries Mawatha, \r\nColombo 2.\r\nT.P: +94 11 3135764\r\nFax: +94 11 2302940\r\n', '84675.00', 1),
(5, 'Expographic Books Pvt Ltd', '53 3/2, 2nd Floor\r\nMunsoor Building,\r\nMain Street,\r\nColombo 11.\r\nTel: 0112332698, 0114899513\r\nFax: 0112438724\r\ne-mail: expg.pettah@expo-graphic.com\r\n', '11250.00', 1),
(6, 'Sarasavi The Bookshop', '2B Samudradevi Mawatha, Nugegoda\r\nTP :- (+94) 11 2820820\r\nFax :- (+94) 11 2814926\r\n', '41548.00', 1),
(7, 'Vijitha Yapa Bookshop', '130, S de S Jayasinghe Mw,\r\n Kohuwala.\r\nTP :-+94112810714\r\n', '33283.25', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl05_dealer`
--

CREATE TABLE IF NOT EXISTS `tbl05_dealer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dealer_name` varchar(150) NOT NULL,
  `contact` varchar(200) DEFAULT NULL,
  `discount` int(11) NOT NULL DEFAULT '0',
  `debit_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `dealer_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:currnet 0:past',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

--
-- Dumping data for table `tbl05_dealer`
--

INSERT INTO `tbl05_dealer` (`id`, `dealer_name`, `contact`, `discount`, `debit_amount`, `dealer_status`) VALUES
(1, 'Emma Bookshop', 'No.120, Main Street,\r\nKadana.\r\nTel: 0112231897', 0, '0.00', 1),
(2, 'Ferno Bookshop', 'No. 85, Colombo Road, \r\nJa Ela. \r\nTel:0112245789', 0, '0.00', 1),
(3, 'Trinty Bookshop', '65, Negombo Road, \r\nMattumagala.\r\nTel. 0112963456', 0, '164.00', 1),
(4, 'Yashoda Bookshop', 'Mahabage Junction,\r\nMahabage.\r\nTel: 0114587866', 0, '0.00', 1),
(5, 'Lyceum International School - Wattala', '32, Royal Pearl Gardens Road,  Hendala, Wattala.\r\nT.P : (+94)11 2981022\r\n(+94)11 4813808\r\n E-mail : lyceum_lw@lyceum.lk', 0, '0.00', 1),
(6, 'Lyceum International School - Kandana', '93, Negombo Road, Kandana, \r\nT.P : (+94)11 2230532\r\n E-mail : lyceum_lk@lyceum.lk', 0, '0.00', 1),
(7, 'Oki International School - Wattala', 'No. 80, Old Negombo Road,\r\nWattala.\r\n', 0, '0.00', 1),
(8, 'Oki International School - Kandana Branch', 'No. 23/3,\r\nChurch Road,\r\nKandana\r\nTel: 0112244200 / 0112228211 \r\nFax: 011- 2244424\r\nEmail: oki.morawatte@gmail.com / ', 0, '0.00', 1),
(9, 'Oki International School -kiribathgoda Branch', 'No. 516, New Hunupitiya Road,\r\nKiribathgoda.', 0, '18400.00', 1),
(10, 'Atamie International School - Wattala', '139, Aweriwatte Road, Wattala.\r\nTel: 0112 933 910\r\nEmail: info@atamieschool.lk', 0, '180.00', 1),
(11, 'Atamie International School - Jaela', '45, Minuwangoda Road,\r\n Ekala.\r\nTel: 011 303 6111 / 011 2 244 656\r\nEmail: jla@atamieschool.lk', 0, '3680.00', 1),
(12, 'Wise International School', '170 c, Negombo Rd, \r\nKandana.\r\nPhone: 011 2 237393', 0, '0.00', 1),
(13, 'Seventh Day Adventist High School - Kandana', ' Pahtheleon Mawatha, Rilaulla Kandana.\r\nTel: 011 2 237090', 0, '0.00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl06_book`
--

CREATE TABLE IF NOT EXISTS `tbl06_book` (
  `sku` int(11) NOT NULL AUTO_INCREMENT,
  `isbn` varchar(15) NOT NULL,
  `book_title` varchar(200) NOT NULL,
  `author` varchar(200) NOT NULL,
  `publisher` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `list_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `purchase_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_stock` int(11) NOT NULL DEFAULT '0',
  `max_stock` int(11) NOT NULL DEFAULT '0',
  `cur_stock` int(11) NOT NULL DEFAULT '0',
  `discount` int(11) NOT NULL DEFAULT '0',
  `date_added` date DEFAULT NULL,
  `date_discontinued` date DEFAULT NULL,
  `last_sold` date DEFAULT NULL,
  `is_ordered` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:no 1:yes',
  `book_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:continue 0:discontinue',
  PRIMARY KEY (`sku`),
  KEY `category_id` (`category_id`,`agent_id`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

--
-- Dumping data for table `tbl06_book`
--

INSERT INTO `tbl06_book` (`sku`, `isbn`, `book_title`, `author`, `publisher`, `category_id`, `agent_id`, `list_price`, `purchase_price`, `min_stock`, `max_stock`, `cur_stock`, `discount`, `date_added`, `date_discontinued`, `last_sold`, `is_ordered`, `book_status`) VALUES
(1, '1111555596789', 'Click Start Computer Science For Schools', 'Cambridge', 'Cambridge', 1, 5, '545.00', '450.00', 10, 25, 10, 0, '2016-11-11', NULL, '2016-12-09', 1, 1),
(2, '122333300', 'Click Start Computer Science For Schools', 'Cambridge', 'Cambridge', 18, 5, '545.00', '450.00', 10, 25, 5, 0, '2016-11-11', NULL, '2016-12-09', 1, 1),
(3, '97804350123', 'Cambridge Biology O/l', 'Cambridge', 'Cambridge', 2, 1, '3680.00', '2750.00', 5, 15, 1, 0, '2016-11-11', NULL, '2016-12-09', 1, 1),
(4, '97804350123', 'Cambridge Biology O/l', 'Cambridge', 'Cambridge', 2, 6, '3680.00', '2750.00', 10, 25, 0, 0, '2016-08-07', NULL, '2016-12-10', 1, 1),
(5, '9780435012', 'Cambridge Eoconomics O/l', 'Cambridge', 'Cambridge', 15, 4, '5160.00', '4650.00', 10, 25, 10, 0, '2016-08-30', NULL, '2016-12-18', 0, 1),
(6, '1223338963', 'Edexel Igcse O/l', 'Edexel', 'Edexel', 5, 4, '5.00', '4750.00', 5, 15, 0, 0, '2016-08-29', '2016-12-09', NULL, 0, 0),
(7, '1223338963', 'Edexel Igcse Chemistry', 'Edexel', 'Edexel', 5, 5, '5160.00', '4750.00', 10, 25, 0, 0, '2016-08-09', NULL, NULL, 0, 1),
(8, '1325689637894', 'Edexel Gce In Applied Ict A2', 'Edexel', 'Edexel', 17, 4, '6375.00', '5775.00', 5, 25, 5, 5, '2016-09-09', NULL, NULL, 1, 1),
(9, '5697415620', 'Edexel Biology For As', 'Edexel', 'Edexel', 5, 1, '3150.00', '2750.00', 12, 25, 0, 0, '2016-08-05', NULL, NULL, 1, 1),
(10, '4563458974', 'Cambridge Core Mathematics For Cam Igcse', 'Cambridge', 'Cambridge', 15, 6, '2082.00', '1756.00', 10, 25, 1, 0, '2016-08-20', NULL, '2016-12-09', 1, 1),
(11, '4125745623', 'Cambridge Advanced Learning Dictionary 3rd Edition', 'Cambridge', 'Cambridge', 9, 7, '1897.00', '1625.00', 5, 15, 10, 0, '2016-08-09', NULL, NULL, 0, 1),
(12, '2345168913', 'Cambridge Active Spelling - 6', 'Cambridge', 'Cambridge', 12, 7, '381.00', '245.00', 10, 20, 3, 0, '2016-08-11', NULL, '2016-12-09', 1, 1),
(13, '1452367896', 'Cambridge Advanced Chemistry', 'Cambridge', 'Cambridge', 14, 6, '2625.00', '1950.00', 10, 20, 0, 0, '2016-08-10', NULL, NULL, 1, 1),
(14, '2013400678', 'Cambridge Commerce', 'Cambridge', 'Cambridge', 2, 7, '2275.00', '1756.00', 10, 30, 10, 5, '2016-12-09', NULL, NULL, 1, 1),
(15, '4561237895', 'Aaaaaaaa', 'Cambridge', 'Cambridge', 2, 1, '2275.00', '1756.00', 5, 15, 6, 0, '2016-12-10', NULL, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl07_sales`
--

CREATE TABLE IF NOT EXISTS `tbl07_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'taken as invoice number',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'amount before discounted',
  `discount` int(11) NOT NULL DEFAULT '0',
  `total_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `date_sold` date NOT NULL,
  `user_sold` varchar(15) NOT NULL,
  `dealer_id` int(11) DEFAULT NULL,
  `Tax` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_sold` (`user_sold`,`dealer_id`),
  KEY `dealer_id` (`dealer_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

--
-- Dumping data for table `tbl07_sales`
--

INSERT INTO `tbl07_sales` (`id`, `total_amount`, `discount`, `total_paid`, `date_sold`, `user_sold`, `dealer_id`, `Tax`) VALUES
(1, '10410.00', 10, '9369.00', '2016-08-15', '000000000V', NULL, 0),
(2, '7360.00', 5, '6992.00', '2016-08-06', '000000000V', NULL, 0),
(3, '3680.00', 0, '3500.00', '2016-09-12', '000000000V', 10, 0),
(4, '2725.00', 3, '2643.25', '2016-09-26', '000000000V', NULL, 0),
(5, '18400.00', 0, '0.00', '2016-09-20', '000000000V', 9, 0),
(6, '762.00', 0, '762.00', '2016-12-09', '000000000V', NULL, 0),
(7, '2725.00', 0, '2725.00', '2016-12-09', '000000000V', NULL, 0),
(8, '4164.00', 0, '4000.00', '2016-12-09', '000000000V', 3, 0),
(9, '7360.00', 5, '6992.00', '2016-12-10', '000000000V', NULL, 0),
(10, '3680.00', 0, '0.00', '2016-12-10', '000000000V', 11, 0),
(11, '10320.00', 10, '9288.00', '2016-12-18', '000000000V', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl08_sales_book`
--

CREATE TABLE IF NOT EXISTS `tbl08_sales_book` (
  `sales_book_id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_id` int(11) NOT NULL,
  `sales_sku` int(11) NOT NULL,
  `discount` int(11) NOT NULL DEFAULT '0',
  `list_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'price before discounted',
  `quantity_sold` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sales_book_id`),
  KEY `sales_id` (`sales_id`),
  KEY `sales_sku` (`sales_sku`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

--
-- Dumping data for table `tbl08_sales_book`
--

INSERT INTO `tbl08_sales_book` (`sales_book_id`, `sales_id`, `sales_sku`, `discount`, `list_price`, `quantity_sold`) VALUES
(1, 1, 10, 0, '2082.00', 5),
(2, 2, 4, 0, '3680.00', 2),
(3, 3, 3, 0, '3680.00', 1),
(4, 4, 2, 0, '545.00', 5),
(5, 5, 4, 0, '3680.00', 5),
(6, 6, 12, 0, '381.00', 2),
(7, 7, 1, 0, '545.00', 5),
(8, 8, 10, 0, '2082.00', 2),
(9, 9, 4, 0, '3680.00', 2),
(10, 10, 4, 0, '3680.00', 1),
(11, 11, 5, 0, '5160.00', 2);

-- --------------------------------------------------------

--
-- Table structure for table `tbl09_sales_return`
--

CREATE TABLE IF NOT EXISTS `tbl09_sales_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'use in return notice id',
  `sales_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `return_sku` int(11) NOT NULL,
  `quantity_returned` int(11) NOT NULL DEFAULT '0',
  `quantity_given` int(11) NOT NULL DEFAULT '0' COMMENT 'on time dispatch',
  `return_order_id` int(11) DEFAULT NULL,
  `user_added` varchar(15) NOT NULL,
  `date_added` date NOT NULL,
  `date_com_can` date DEFAULT NULL,
  `return_status` enum('pending','ordered','received','completed','canceled') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `sales_id` (`sales_id`,`agent_id`,`return_sku`,`return_order_id`,`user_added`),
  KEY `return_isbn` (`return_sku`),
  KEY `return_order_id` (`return_order_id`),
  KEY `user_added` (`user_added`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `tbl09_sales_return`
--

INSERT INTO `tbl09_sales_return` (`id`, `sales_id`, `agent_id`, `return_sku`, `quantity_returned`, `quantity_given`, `return_order_id`, `user_added`, `date_added`, `date_com_can`, `return_status`) VALUES
(1, 2, 6, 4, 1, 0, NULL, '000000000V', '2016-10-11', '2016-12-09', 'completed'),
(2, 5, 6, 4, 4, 0, NULL, '000000000V', '2016-10-25', NULL, 'received'),
(3, 5, 6, 4, 1, 0, NULL, '000000000V', '2016-12-09', NULL, 'ordered'),
(4, 8, 6, 10, 1, 0, NULL, '000000000V', '2016-12-09', NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `tbl10_requisition`
--

CREATE TABLE IF NOT EXISTS `tbl10_requisition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `note` varchar(200) NOT NULL,
  `user_added` varchar(15) NOT NULL,
  `date_added` date NOT NULL,
  `date_com_can` date DEFAULT NULL,
  `requisition_status` enum('pending','completed','canceled') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `user_added` (`user_added`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `tbl10_requisition`
--

INSERT INTO `tbl10_requisition` (`id`, `note`, `user_added`, `date_added`, `date_com_can`, `requisition_status`) VALUES
(1, 'NHM - 6 ASSESSMENT BOOK ', '000000000V', '2016-08-09', NULL, 'pending'),
(2, 'CAMBRIDGE CHECKPOINT MATHEMATICS 3 W/B N/W', '000000000V', '2016-08-22', NULL, 'pending'),
(3, 'CAMBRIDGE CORE MATHEMATICS FOR CAM IGCSE', '000000000V', '2016-09-01', NULL, 'pending'),
(4, 'CAMBRIDGE ACTIVE BOOK - C', '000000000V', '2016-10-09', NULL, 'pending'),
(5, 'NHM - 8 ENGLISH WORKBOOK BOOK ', '000000000V', '2016-11-19', NULL, 'pending'),
(6, 'ENGLISH RADIANT WAY - GRADE 3', '000000000V', '2016-12-09', NULL, 'pending'),
(7, 'hgghgjg', '000000000V', '2016-12-10', NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `tbl11_purchase`
--

CREATE TABLE IF NOT EXISTS `tbl11_purchase` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `user_ordered` varchar(15) NOT NULL,
  `date_ordered` date NOT NULL,
  `date_com_can` date DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `discount` int(11) NOT NULL DEFAULT '0',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `purchase_status` enum('ordered','completed','canceled') NOT NULL DEFAULT 'ordered',
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`,`user_ordered`),
  KEY `user_ordered` (`user_ordered`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

--
-- Dumping data for table `tbl11_purchase`
--

INSERT INTO `tbl11_purchase` (`id`, `agent_id`, `user_ordered`, `date_ordered`, `date_com_can`, `invoice_no`, `discount`, `total_amount`, `total_paid`, `purchase_status`) VALUES
(1, 1, '000000000V', '2016-08-08', '2016-08-18', '5445454', 5, '5500.00', '2000.00', 'completed'),
(2, 4, '000000000V', '2016-12-09', '2016-12-09', NULL, 0, '0.00', '0.00', 'canceled'),
(3, 6, '000000000V', '2016-08-09', '2016-08-09', '4568219', 0, '41548.00', '0.00', 'completed'),
(4, 5, '000000000V', '2016-08-15', '2016-09-15', '3457890', 0, '11250.00', '0.00', 'completed'),
(5, 7, '000000000V', '2016-08-16', '2016-12-09', '5487890', 5, '18785.00', '0.00', 'completed'),
(6, 4, '000000000V', '2016-09-27', '2016-12-09', '5632418', 0, '84675.00', '0.00', 'completed'),
(7, 7, '000000000V', '2016-10-18', '2016-12-09', '2341568', 5, '16250.00', '0.00', 'completed'),
(8, 6, '000000000V', '2016-12-09', NULL, NULL, 0, '0.00', '0.00', 'ordered'),
(9, 1, '000000000V', '2016-12-09', NULL, NULL, 0, '0.00', '0.00', 'ordered'),
(10, 7, '000000000V', '2016-12-09', NULL, NULL, 0, '0.00', '0.00', 'ordered'),
(11, 4, '000000000V', '2016-12-09', NULL, NULL, 0, '0.00', '0.00', 'ordered'),
(12, 7, '000000000V', '2016-12-10', NULL, NULL, 0, '0.00', '0.00', 'ordered'),
(13, 5, '000000000V', '2016-12-10', NULL, NULL, 0, '0.00', '0.00', 'ordered'),
(14, 1, '000000000V', '2016-12-10', '2016-12-10', '2341568', 0, '3512.00', '0.00', 'completed'),
(15, 1, '000000000V', '2016-12-10', '2016-12-10', '4568219', 0, '7024.00', '0.00', 'completed'),
(16, 5, '000000000V', '2017-01-08', NULL, NULL, 0, '0.00', '0.00', 'ordered');

-- --------------------------------------------------------

--
-- Table structure for table `tbl12_purchase_book`
--

CREATE TABLE IF NOT EXISTS `tbl12_purchase_book` (
  `purchase_book_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `purchase_sku` int(11) NOT NULL,
  `quantity_ordered` int(11) NOT NULL DEFAULT '0',
  `price_ordered` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity_purchased` int(11) NOT NULL DEFAULT '0',
  `price_purchased` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`purchase_book_id`),
  KEY `purchase_isbn` (`purchase_sku`),
  KEY `purchase_id` (`purchase_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=24 ;

--
-- Dumping data for table `tbl12_purchase_book`
--

INSERT INTO `tbl12_purchase_book` (`purchase_book_id`, `purchase_id`, `purchase_sku`, `quantity_ordered`, `price_ordered`, `quantity_purchased`, `price_purchased`) VALUES
(1, 1, 3, 2, '2750.00', 2, '2750.00'),
(2, 2, 5, 12, '4650.00', 0, '0.00'),
(3, 3, 4, 10, '2750.00', 10, '2750.00'),
(4, 3, 10, 8, '1756.00', 8, '1756.00'),
(5, 4, 1, 15, '450.00', 15, '450.00'),
(6, 4, 2, 10, '450.00', 10, '450.00'),
(7, 5, 12, 5, '245.00', 5, '245.00'),
(8, 5, 14, 10, '1756.00', 10, '1756.00'),
(9, 6, 5, 12, '4650.00', 12, '4650.00'),
(10, 6, 8, 5, '5775.00', 5, '5775.00'),
(11, 7, 11, 10, '1625.00', 10, '1625.00'),
(12, 8, 13, 15, '1950.00', 0, '0.00'),
(13, 8, 4, 10, '2750.00', 0, '0.00'),
(14, 8, 10, 5, '1756.00', 0, '0.00'),
(15, 9, 3, 4, '2750.00', 0, '0.00'),
(16, 9, 9, 5, '2750.00', 0, '0.00'),
(17, 10, 12, 7, '245.00', 0, '0.00'),
(18, 11, 8, 3, '5775.00', 0, '0.00'),
(19, 12, 14, 2, '1756.00', 0, '0.00'),
(20, 13, 1, 2, '450.00', 0, '0.00'),
(21, 14, 15, 2, '1756.00', 2, '1756.00'),
(22, 15, 15, 4, '1756.00', 4, '1756.00'),
(23, 16, 2, 5, '450.00', 0, '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `tbl13_purchase_return`
--

CREATE TABLE IF NOT EXISTS `tbl13_purchase_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `user_ordered` varchar(15) NOT NULL,
  `date_ordered` date NOT NULL,
  `date_com_can` date DEFAULT NULL,
  `purchase_return_status` enum('ordered','completed','canceled') NOT NULL DEFAULT 'ordered',
  PRIMARY KEY (`id`),
  KEY `user_ordered` (`user_ordered`),
  KEY `agent_id` (`agent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `tbl13_purchase_return`
--

INSERT INTO `tbl13_purchase_return` (`id`, `agent_id`, `user_ordered`, `date_ordered`, `date_com_can`, `purchase_return_status`) VALUES
(1, 6, '000000000V', '2016-12-09', '2016-12-09', 'completed'),
(2, 6, '000000000V', '2017-01-08', NULL, 'ordered');

-- --------------------------------------------------------

--
-- Table structure for table `tbl14_purchase_return_sales_return`
--

CREATE TABLE IF NOT EXISTS `tbl14_purchase_return_sales_return` (
  `purchase_return_id` int(11) NOT NULL,
  `sales_return_id` int(11) NOT NULL,
  PRIMARY KEY (`purchase_return_id`,`sales_return_id`),
  KEY `return_id` (`sales_return_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl14_purchase_return_sales_return`
--

INSERT INTO `tbl14_purchase_return_sales_return` (`purchase_return_id`, `sales_return_id`) VALUES
(1, 1),
(1, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `tbl15_financial`
--

CREATE TABLE IF NOT EXISTS `tbl15_financial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_category` enum('sales','credit','debit') NOT NULL DEFAULT 'sales',
  `description` varchar(200) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `creditor_debtor_id` int(11) DEFAULT NULL,
  `income` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:income 0:expenditure',
  `cheque_no` varchar(10) DEFAULT NULL,
  `date_made` datetime NOT NULL,
  `user_made` varchar(15) NOT NULL,
  `trans_status` enum('pending','completed','canceled') NOT NULL DEFAULT 'completed',
  PRIMARY KEY (`id`),
  KEY `user_made` (`user_made`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `tbl15_financial`
--

INSERT INTO `tbl15_financial` (`id`, `trans_category`, `description`, `amount`, `creditor_debtor_id`, `income`, `cheque_no`, `date_made`, `user_made`, `trans_status`) VALUES
(1, 'sales', 'Add retail sales: 1', '9369.00', NULL, 1, NULL, '2016-10-19 18:27:13', '000000000V', 'completed'),
(2, 'sales', 'Add retail sales: 2', '6992.00', NULL, 1, NULL, '2016-09-23 18:32:24', '000000000V', 'completed'),
(3, 'sales', 'Add retail sales: 4', '2643.25', NULL, 1, NULL, '2016-11-14 18:36:08', '000000000V', 'completed'),
(4, 'credit', 'Pay credit : Jeya Book Centre - Pettah', '2000.00', 1, 0, '563421', '2016-12-09 18:43:42', '000000000V', 'pending'),
(5, 'sales', 'Add retail sales: 6', '762.00', NULL, 1, NULL, '2016-09-17 19:59:44', '000000000V', 'completed'),
(6, 'sales', 'Add retail sales: 7', '2725.00', NULL, 1, NULL, '2016-12-09 20:31:03', '000000000V', 'completed'),
(7, 'debit', 'Receive debit : Trinty Bookshop', '4000.00', 3, 1, '789562', '2016-12-09 22:36:40', '000000000V', 'pending'),
(8, 'sales', 'Add retail sales: 9', '6992.00', NULL, 1, NULL, '2016-12-10 09:00:38', '000000000V', 'completed'),
(9, 'debit', 'Receive debit : Atamie International School - Wattala', '3500.00', 10, 1, '124586', '2016-12-10 09:07:04', '000000000V', 'pending'),
(10, 'sales', 'Add retail sales: 11', '9288.00', NULL, 1, NULL, '2016-12-18 09:54:03', '000000000V', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `tbl16_payment`
--

CREATE TABLE IF NOT EXISTS `tbl16_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_id` int(11) NOT NULL,
  `is_purchase` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:purchase 0:sales',
  `sales_purchase_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trans_id` (`trans_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `tbl16_payment`
--

INSERT INTO `tbl16_payment` (`id`, `trans_id`, `is_purchase`, `sales_purchase_id`, `amount`) VALUES
(1, 4, 1, 1, '2000.00'),
(2, 7, 0, 8, '4000.00'),
(3, 9, 0, 3, '3500.00');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl02_log`
--
ALTER TABLE `tbl02_log`
  ADD CONSTRAINT `tbl02_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl01_user` (`nic`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl06_book`
--
ALTER TABLE `tbl06_book`
  ADD CONSTRAINT `tbl06_book_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `tbl03_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl06_book_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `tbl04_agent` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl07_sales`
--
ALTER TABLE `tbl07_sales`
  ADD CONSTRAINT `tbl07_sales_ibfk_1` FOREIGN KEY (`user_sold`) REFERENCES `tbl01_user` (`nic`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl07_sales_ibfk_2` FOREIGN KEY (`dealer_id`) REFERENCES `tbl05_dealer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl08_sales_book`
--
ALTER TABLE `tbl08_sales_book`
  ADD CONSTRAINT `tbl08_sales_book_ibfk_1` FOREIGN KEY (`sales_id`) REFERENCES `tbl07_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl08_sales_book_ibfk_2` FOREIGN KEY (`sales_sku`) REFERENCES `tbl06_book` (`sku`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl09_sales_return`
--
ALTER TABLE `tbl09_sales_return`
  ADD CONSTRAINT `tbl09_sales_return_ibfk_1` FOREIGN KEY (`sales_id`) REFERENCES `tbl07_sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl09_sales_return_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `tbl04_agent` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl09_sales_return_ibfk_3` FOREIGN KEY (`return_order_id`) REFERENCES `tbl13_purchase_return` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl09_sales_return_ibfk_4` FOREIGN KEY (`return_sku`) REFERENCES `tbl06_book` (`sku`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl10_requisition`
--
ALTER TABLE `tbl10_requisition`
  ADD CONSTRAINT `tbl10_requisition_ibfk_1` FOREIGN KEY (`user_added`) REFERENCES `tbl01_user` (`nic`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl11_purchase`
--
ALTER TABLE `tbl11_purchase`
  ADD CONSTRAINT `tbl11_purchase_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `tbl04_agent` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl12_purchase_book`
--
ALTER TABLE `tbl12_purchase_book`
  ADD CONSTRAINT `tbl12_purchase_book_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `tbl11_purchase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl12_purchase_book_ibfk_2` FOREIGN KEY (`purchase_sku`) REFERENCES `tbl06_book` (`sku`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl13_purchase_return`
--
ALTER TABLE `tbl13_purchase_return`
  ADD CONSTRAINT `tbl13_purchase_return_ibfk_1` FOREIGN KEY (`user_ordered`) REFERENCES `tbl01_user` (`nic`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl13_purchase_return_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `tbl04_agent` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl14_purchase_return_sales_return`
--
ALTER TABLE `tbl14_purchase_return_sales_return`
  ADD CONSTRAINT `tbl14_purchase_return_sales_return_ibfk_1` FOREIGN KEY (`purchase_return_id`) REFERENCES `tbl13_purchase_return` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl14_purchase_return_sales_return_ibfk_2` FOREIGN KEY (`sales_return_id`) REFERENCES `tbl09_sales_return` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl15_financial`
--
ALTER TABLE `tbl15_financial`
  ADD CONSTRAINT `tbl15_financial_ibfk_1` FOREIGN KEY (`user_made`) REFERENCES `tbl01_user` (`nic`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl16_payment`
--
ALTER TABLE `tbl16_payment`
  ADD CONSTRAINT `tbl16_payment_ibfk_1` FOREIGN KEY (`trans_id`) REFERENCES `tbl15_financial` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
