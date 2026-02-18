-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-02-2026 a las 23:09:10
-- Versión del servidor: 5.6.17
-- Versión de PHP: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `john`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_anacategoria`
--

CREATE TABLE IF NOT EXISTS `dom_anacategoria` (
  `name` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL,
  `anacategoria_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`anacategoria_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=27 ;

--
-- Volcado de datos para la tabla `dom_anacategoria`
--

INSERT INTO `dom_anacategoria` (`name`, `order`, `deleted`, `anacategoria_id`) VALUES
('Hematologia', 1, 0, 2),
('Coagulograma', 2, 0, 3),
('Heces', 22, 0, 4),
('Perfil de hierro', 3, 0, 5),
('Quimica Sanguinea', 4, 0, 6),
('Electrolitos', 5, 0, 7),
('Perfil Lipidico', 6, 0, 8),
('Perfil Hepatico', 7, 0, 9),
('Perfil Cardiaco', 8, 0, 10),
('Serologia', 9, 0, 11),
('Marcadores Tumorales', 10, 0, 12),
('Hormonas', 11, 0, 13),
('Perfil Tiroideo', 12, 0, 14),
('Elisas', 13, 0, 15),
('Elisa Hepatitis', 14, 0, 16),
('Elisas Inmunologia', 15, 0, 17),
('Radioinmunoensayo', 16, 0, 18),
('Nefelometria', 17, 0, 19),
('Liquidos Biologicos', 18, 0, 20),
('Pruebas Gastricas', 19, 0, 21),
('Bactereologia', 20, 0, 22),
('Orina', 21, 0, 23),
('Panel de Alergias', 23, 0, 24),
('Otros', 24, 0, 25),
('PERFIL BASICO', 50, 0, 26);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_app_config`
--

CREATE TABLE IF NOT EXISTS `dom_app_config` (
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_app_config`
--

INSERT INTO `dom_app_config` (`key`, `value`) VALUES
('address', 'Olivera Solutions. Guadalquivir #100, entre Pio Hermosa y Pedro Alvarez'),
('company', 'VEINT'),
('currency_side', '0'),
('currency_symbol', '$. '),
('custom10_name', ''),
('custom1_name', ''),
('custom2_name', ''),
('custom3_name', ''),
('custom4_name', ''),
('custom5_name', ''),
('custom6_name', ''),
('custom7_name', ''),
('custom8_name', ''),
('custom9_name', ''),
('default_tax_1_name', 'Impuesto de Ventas 1'),
('default_tax_1_rate', ''),
('default_tax_2_name', 'Impuesto de Ventas 2'),
('default_tax_2_rate', ''),
('default_tax_rate', '8'),
('email', 'info@solutions.com'),
('fax', ''),
('language', 'es'),
('phone', '+1 213 514 5356'),
('print_after_sale', 'print_after_sale'),
('return_policy', 'Se hace la devolución del producto en caso de:\nxxxxxxxxxx\nxxxxxxxxxx'),
('timezone', 'America/Bogota'),
('website', 'http://www.olivera-solutions.com/');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_customers`
--

CREATE TABLE IF NOT EXISTS `dom_customers` (
  `person_id` int(10) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `taxable` int(1) NOT NULL DEFAULT '1',
  `deleted` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`person_id`),
  UNIQUE KEY `account_number` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_customers`
--

INSERT INTO `dom_customers` (`person_id`, `account_number`, `taxable`, `deleted`) VALUES
(5272327, NULL, 0, 1),
(546, NULL, 0, 1),
(53413130, NULL, 1, 1),
(53413131, NULL, 1, 1),
(53413132, NULL, 1, 1),
(53413133, NULL, 1, 1),
(53413134, NULL, 1, 1),
(53413135, NULL, 1, 1),
(53413136, NULL, 1, 1),
(53413137, NULL, 1, 1),
(1256, NULL, 1, 1),
(423423, NULL, 1, 1),
(987689, NULL, 1, 1),
(958389, NULL, 1, 0),
(895627, NULL, 1, 0),
(745965, NULL, 1, 1),
(53413143, NULL, 1, 1),
(53413144, NULL, 1, 1),
(53413145, NULL, 1, 1),
(53413146, NULL, 1, 1),
(53413147, NULL, 1, 0),
(53413148, NULL, 1, 0),
(53413149, NULL, 0, 1),
(53413150, NULL, 0, 1),
(53413151, NULL, 0, 1),
(53413152, NULL, 1, 1),
(53413153, NULL, 1, 1),
(53413154, NULL, 1, 1);

--
-- Disparadores `dom_customers`
--
DROP TRIGGER IF EXISTS `customers_delete`;
DELIMITER //
CREATE TRIGGER `customers_delete` AFTER DELETE ON `dom_customers`
 FOR EACH ROW DELETE FROM dom_people
    WHERE dom_people.person_id = OLD.person_id
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_doctors`
--

CREATE TABLE IF NOT EXISTS `dom_doctors` (
  `name` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `gender` int(5) NOT NULL,
  `speciality` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `deleted` tinyint(4) NOT NULL,
  `comments` text NOT NULL,
  `doctor_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`doctor_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=61 ;

--
-- Volcado de datos para la tabla `dom_doctors`
--

INSERT INTO `dom_doctors` (`name`, `phone_number`, `gender`, `speciality`, `address`, `deleted`, `comments`, `doctor_id`) VALUES
('chapatin', '70356723', 1, 'gineco', '123', 0, '', 59),
('test', '123', 1, 't', 'asdas', 0, '', 60);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_employees`
--

CREATE TABLE IF NOT EXISTS `dom_employees` (
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `person_id` int(10) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`person_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_employees`
--

INSERT INTO `dom_employees` (`username`, `password`, `person_id`, `deleted`) VALUES
('12345678', '25d55ad283aa400af464c76d713c07ad', 53413141, 1),
('123456789', '25d55ad283aa400af464c76d713c07ad', 53413142, 1),
('admin', '250e52ba37a2c5aba18de6bedea76657', 1, 0),
('pedro', '25d55ad283aa400af464c76d713c07ad', 53413139, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_estado`
--

CREATE TABLE IF NOT EXISTS `dom_estado` (
  `estado` varchar(255) NOT NULL,
  `estado_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`estado_id`),
  KEY `estado_id` (`estado_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Volcado de datos para la tabla `dom_estado`
--

INSERT INTO `dom_estado` (`estado`, `estado_id`) VALUES
('Reportado', 1),
('Entregado', 2),
('Anulado', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_formulas`
--

CREATE TABLE IF NOT EXISTS `dom_formulas` (
  `formulas_id` int(10) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `nombre_fun` varchar(255) NOT NULL,
  PRIMARY KEY (`formulas_id`),
  KEY `formulas_id` (`formulas_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Volcado de datos para la tabla `dom_formulas`
--

INSERT INTO `dom_formulas` (`formulas_id`, `nombre`, `nombre_fun`) VALUES
(1, 'ninguno', 'ninguno'),
(2, 'Formula Leucocitos', 'build_leuco'),
(3, 'Formula Eritrocitos', 'build_eritr'),
(4, 'Formula Hemoglobina', 'build_hemog'),
(5, 'Formula Plaquetas', 'build_plaqu'),
(6, 'Formula HCM', 'build_hcm'),
(7, 'Formula VCM', 'build_vcm'),
(8, 'Formula CHCM', 'build_chcm');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_giftcards`
--

CREATE TABLE IF NOT EXISTS `dom_giftcards` (
  `giftcard_id` int(11) NOT NULL AUTO_INCREMENT,
  `giftcard_number` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `value` decimal(15,2) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT '0',
  `person_id` int(11) NOT NULL,
  PRIMARY KEY (`giftcard_id`),
  UNIQUE KEY `giftcard_number` (`giftcard_number`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

--
-- Volcado de datos para la tabla `dom_giftcards`
--

INSERT INTO `dom_giftcards` (`giftcard_id`, `giftcard_number`, `value`, `deleted`, `person_id`) VALUES
(1, '2', '200.00', 0, 0),
(2, '0005623', '100.00', 0, 423423),
(3, '3', '1156.00', 1, 5272327);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_inventory`
--

CREATE TABLE IF NOT EXISTS `dom_inventory` (
  `trans_id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_items` int(11) NOT NULL DEFAULT '0',
  `trans_user` int(11) NOT NULL DEFAULT '0',
  `trans_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trans_comment` text NOT NULL,
  `trans_inventory` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trans_id`),
  KEY `dom_inventory_ibfk_1` (`trans_items`),
  KEY `dom_inventory_ibfk_2` (`trans_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=103 ;

--
-- Volcado de datos para la tabla `dom_inventory`
--

INSERT INTO `dom_inventory` (`trans_id`, `trans_items`, `trans_user`, `trans_date`, `trans_comment`, `trans_inventory`) VALUES
(1, 1, 1, '2014-08-15 00:45:13', 'Edición Manual de Cantidad', 10),
(2, 1, 1, '2014-08-15 00:46:08', 'POS 1', -1),
(3, 2, 1, '2014-08-15 14:50:24', 'Qty CSV Imported', 10),
(4, 3, 1, '2014-08-15 14:50:24', 'Qty CSV Imported', 10),
(5, 4, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(6, 5, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(7, 6, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(8, 7, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(9, 8, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(10, 9, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(11, 10, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(12, 11, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(13, 12, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(14, 13, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(15, 14, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(16, 15, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(17, 16, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(18, 17, 1, '2014-08-15 14:50:25', 'Qty CSV Imported', 10),
(19, 18, 1, '2014-08-15 14:50:26', 'Qty CSV Imported', 10),
(20, 19, 1, '2014-08-15 14:50:26', 'Qty CSV Imported', 10),
(21, 20, 1, '2014-08-15 14:50:26', 'Qty CSV Imported', 10),
(22, 21, 1, '2014-08-15 14:50:26', 'Qty CSV Imported', 10),
(23, 22, 1, '2014-08-15 14:50:26', 'Qty CSV Imported', 10),
(24, 23, 1, '2014-08-15 14:50:26', 'Qty CSV Imported', 10),
(25, 24, 1, '2014-08-15 14:50:26', 'Qty CSV Imported', 10),
(26, 3, 1, '2014-08-15 14:52:34', 'POS 2', -5),
(27, 7, 1, '2014-08-15 14:52:34', 'POS 2', -1),
(28, 1, 1, '2014-08-18 14:08:16', 'Edición Manual de Cantidad', -7),
(29, 1, 1, '2014-08-18 14:08:44', 'Edición Manual de Cantidad', 8),
(30, 1, 1, '2014-08-18 14:09:08', 'Edición Manual de Cantidad', -5),
(31, 25, 1, '2014-08-18 14:13:25', 'Qty CSV Imported', 10),
(32, 26, 1, '2014-08-18 14:13:25', 'Qty CSV Imported', 10),
(33, 27, 1, '2014-08-18 14:13:25', 'Qty CSV Imported', 10),
(34, 28, 1, '2014-08-18 14:13:25', 'Qty CSV Imported', 10),
(35, 29, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(36, 30, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(37, 31, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(38, 32, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(39, 33, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(40, 34, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(41, 35, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(42, 36, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(43, 37, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(44, 38, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(45, 39, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(46, 40, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(47, 41, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(48, 42, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(49, 43, 1, '2014-08-18 14:13:26', 'Qty CSV Imported', 10),
(50, 25, 1, '2014-08-18 14:14:53', 'Edición Manual de Cantidad', 0),
(51, 25, 1, '2014-08-18 14:15:14', 'Edición Manual de Cantidad', -9),
(52, 3, 1, '2014-08-18 14:22:57', 'POS 3', -5),
(53, 8, 1, '2014-08-18 14:22:57', 'POS 3', -2),
(54, 29, 1, '2014-08-18 14:23:40', 'POS 4', -1),
(55, 33, 1, '2014-08-18 14:24:31', 'POS 5', -1),
(56, 26, 1, '2014-08-18 14:27:46', 'POS 6', -1),
(57, 32, 1, '2014-08-18 14:27:46', 'POS 6', -2),
(58, 30, 1, '2014-08-18 14:33:14', 'POS 7', -1),
(59, 28, 1, '2014-12-04 13:45:07', 'POS 8', -1),
(61, 27, 1, '2015-07-01 21:27:07', 'POS 9', -1),
(62, 25, 1, '2015-07-02 14:57:06', 'Edición Manual de Cantidad', 0),
(63, 25, 1, '2015-07-02 14:57:10', 'Edición Manual de Cantidad', 0),
(64, 25, 1, '2015-07-02 14:57:20', 'Edición Manual de Cantidad', 0),
(65, 25, 1, '2015-07-02 14:57:21', 'Edición Manual de Cantidad', 0),
(76, 57, 1, '2015-07-02 17:19:54', 'Qty CSV Imported', 10),
(77, 57, 1, '2015-07-02 16:20:45', 'Edición Manual de Cantidad', 0),
(78, 57, 1, '2015-07-02 16:42:15', 'Edición Manual de Cantidad', 0),
(79, 57, 1, '2015-07-02 16:42:21', 'Edición Manual de Cantidad', 0),
(80, 57, 1, '2015-07-02 16:48:42', 'se agrego 12', 12),
(81, 57, 1, '2015-07-02 16:49:01', 'asd', 2),
(82, 57, 1, '2015-07-02 16:50:03', 'asd', 3),
(83, 57, 1, '2015-07-02 16:50:13', 'resto 7', -7),
(84, 26, 1, '2015-07-02 17:25:39', 'Edición Manual de Cantidad', 0),
(85, 26, 1, '2015-07-02 17:28:16', 'Edición Manual de Cantidad', 0),
(86, 29, 1, '2015-07-02 19:22:14', 'Edición Manual de Cantidad', 0),
(87, 26, 1, '2015-07-03 03:16:57', 'RECV 1', 1),
(88, 26, 1, '2015-07-03 03:17:57', 'RECV 2', 1),
(89, 26, 1, '2015-07-03 03:20:18', 'RECV 3', -1),
(90, 29, 1, '2015-07-03 03:23:29', 'RECV 4', 1),
(91, 29, 1, '2015-07-03 05:03:32', 'POS 10', -1),
(92, 31, 1, '2015-07-03 05:03:32', 'POS 10', -1),
(93, 30, 1, '2015-07-03 10:22:40', 'POS 11', -2),
(94, 59, 1, '2025-04-17 12:24:18', 'Edición Manual de Cantidad', 100),
(95, 59, 1, '2025-04-17 12:24:37', 'Edición Manual de Cantidad', 0),
(96, 59, 1, '2025-04-17 12:27:36', 'Edición Manual de Cantidad', 0),
(97, 59, 1, '2025-04-17 12:29:01', 'Edición Manual de Cantidad', 0),
(98, 59, 1, '2025-04-17 12:29:13', 'Edición Manual de Cantidad', 0),
(99, 59, 1, '2025-04-17 12:30:10', 'Edición Manual de Cantidad', 0),
(100, 59, 1, '2025-04-17 12:40:06', 'Edición Manual de Cantidad', 0),
(101, 59, 1, '2025-04-21 12:52:19', 'Edición Manual de Cantidad', 0),
(102, 59, 1, '2025-04-21 12:52:39', 'Edición Manual de Cantidad', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_items`
--

CREATE TABLE IF NOT EXISTS `dom_items` (
  `fecha` date NOT NULL,
  `fecha_ven` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `forma` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `item_number` varchar(255) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `cost_price` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reorder_level` decimal(15,2) NOT NULL DEFAULT '0.00',
  `location` varchar(255) NOT NULL,
  `item_id` int(10) NOT NULL AUTO_INCREMENT,
  `allow_alt_description` tinyint(1) NOT NULL,
  `is_serialized` tinyint(1) NOT NULL,
  `deleted` int(1) NOT NULL DEFAULT '0',
  `custom1` varchar(25) NOT NULL,
  `custom2` varchar(25) NOT NULL,
  `custom3` varchar(25) NOT NULL,
  `custom4` varchar(25) NOT NULL,
  `custom5` varchar(25) NOT NULL,
  `custom6` varchar(25) NOT NULL,
  `custom7` varchar(25) NOT NULL,
  `custom8` varchar(25) NOT NULL,
  `custom9` varchar(25) NOT NULL,
  `custom10` varchar(25) NOT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `item_number` (`item_number`),
  KEY `dom_items_ibfk_1` (`supplier_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=60 ;

--
-- Volcado de datos para la tabla `dom_items`
--

INSERT INTO `dom_items` (`fecha`, `fecha_ven`, `name`, `forma`, `category`, `supplier_id`, `item_number`, `description`, `cost_price`, `unit_price`, `quantity`, `reorder_level`, `location`, `item_id`, `allow_alt_description`, `is_serialized`, `deleted`, `custom1`, `custom2`, `custom3`, `custom4`, `custom5`, `custom6`, `custom7`, `custom8`, `custom9`, `custom10`) VALUES
('0000-00-00', '0000-00-00', 'Articulo 01', '', 'Golosinas', NULL, NULL, '', '20.00', '20.00', '5.00', '2.00', '', 1, 0, 0, 1, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'),
('0000-00-00', '0000-00-00', 'Producto 01', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 2, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 02', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '0.00', '0.00', '', 3, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 03', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 4, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 04', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 5, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 05', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 6, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 06', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '9.00', '0.00', '', 7, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 07', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '8.00', '0.00', '', 8, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 08', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 9, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 09', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 10, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 10', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 11, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 11', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 12, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 12', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 13, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 13', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 14, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 14', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 15, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 15', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 16, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 16', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 17, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 17', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 18, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 18', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 19, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 19', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 20, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 20', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 21, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 21', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 22, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 22', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 23, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 23', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '10.00', '0.00', '', 24, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto golo', '', 'Golosinas', NULL, NULL, '', '10.00', '12.00', '1.00', '2.00', '', 25, 0, 0, 1, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'),
('0000-00-00', '0000-00-00', 'Producto 02', '', 'Golosinas', NULL, 'p-002', '', '10.00', '12.00', '10.00', '2.00', '', 26, 0, 0, 0, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'),
('0000-00-00', '0000-00-00', 'Producto 03', '', 'Golosinas', NULL, 'p-003', '', '10.00', '12.00', '9.00', '2.00', '', 27, 0, 0, 0, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 04', '', 'Golosinas', NULL, 'p-004', '', '10.00', '12.00', '9.00', '2.00', '', 28, 0, 0, 0, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 05', '', 'Golosinas', NULL, 'p-005', '', '10.00', '12.00', '9.00', '2.00', '', 29, 0, 0, 0, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'),
('0000-00-00', '0000-00-00', 'Producto 06', '', 'Golosinas', NULL, 'p-006', '', '10.00', '12.00', '7.00', '2.00', '', 30, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 07', '', 'Golosinas', NULL, 'p-007', '', '10.00', '12.00', '9.00', '2.00', '', 31, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 08', '', 'Golosinas', NULL, 'p-008', '', '10.00', '12.00', '8.00', '2.00', '', 32, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 09', '', 'Golosinas', NULL, 'p-009', '', '10.00', '12.00', '9.00', '2.00', '', 33, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 10', '', 'Golosinas', NULL, 'p-010', '', '10.00', '12.00', '10.00', '2.00', '', 34, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 11', '', 'Golosinas', NULL, 'p-011', '', '10.00', '12.00', '10.00', '2.00', '', 35, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 12', '', 'Golosinas', NULL, 'p-012', '', '10.00', '12.00', '10.00', '2.00', '', 36, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 13', '', 'Golosinas', NULL, 'p-013', '', '10.00', '12.00', '10.00', '2.00', '', 37, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 14', '', 'Golosinas', NULL, 'p-014', '', '10.00', '12.00', '10.00', '2.00', '', 38, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 15', '', 'Golosinas', NULL, 'p-015', '', '10.00', '12.00', '10.00', '2.00', '', 39, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 16', '', 'Golosinas', NULL, 'p-016', '', '10.00', '12.00', '10.00', '2.00', '', 40, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 17', '', 'Golosinas', NULL, 'p-017', '', '10.00', '12.00', '10.00', '2.00', '', 41, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 18', '', 'Golosinas', NULL, 'p-018', '', '10.00', '12.00', '10.00', '2.00', '', 42, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Producto 19', '', 'Golosinas', NULL, 'p-019', '', '10.00', '12.00', '10.00', '2.00', '', 43, 0, 0, 1, '', '', '', '', '', '', '', '', '', ''),
('0000-00-00', '0000-00-00', 'Apple iMac', '', 'Computers', NULL, '33333333', 'Best Computer evers', '800.00', '1200.00', '20.00', '1.00', 'Earth', 57, 1, 0, 1, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'),
('2010-12-12', '2025-01-01', 'uno', 'quimicas', 'Quimicos', NULL, '123123', 'esta es una prueba de sonido', '2.00', '0.00', '100.00', '10.00', 'asd', 59, 0, 0, 0, 'Kortan', 'Juan PAblo Rosas', '12312', 'asdas213123codigo', '100ml', '0', '0', '0', '0', '0');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_items_taxes`
--

CREATE TABLE IF NOT EXISTS `dom_items_taxes` (
  `item_id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `percent` decimal(15,2) NOT NULL,
  PRIMARY KEY (`item_id`,`name`,`percent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_items_taxes`
--

INSERT INTO `dom_items_taxes` (`item_id`, `name`, `percent`) VALUES
(57, 'Tax 1', '9.00'),
(57, 'Tax 2', '15.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_item_kits`
--

CREATE TABLE IF NOT EXISTS `dom_item_kits` (
  `item_kit_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`item_kit_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

--
-- Volcado de datos para la tabla `dom_item_kits`
--

INSERT INTO `dom_item_kits` (`item_kit_id`, `name`, `description`) VALUES
(21, 'Productos', 'Contiene producto 04 y 06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_item_kit_items`
--

CREATE TABLE IF NOT EXISTS `dom_item_kit_items` (
  `item_kit_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  PRIMARY KEY (`item_kit_id`,`item_id`,`quantity`),
  KEY `dom_item_kit_items_ibfk_2` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_item_kit_items`
--

INSERT INTO `dom_item_kit_items` (`item_kit_id`, `item_id`, `quantity`) VALUES
(21, 28, '5.00'),
(21, 30, '1.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_manuals`
--

CREATE TABLE IF NOT EXISTS `dom_manuals` (
  `prianacategoria_id` varchar(255) NOT NULL,
  `tittle` varchar(255) NOT NULL,
  `manual` text NOT NULL,
  `deleted` tinyint(4) NOT NULL,
  `manuals_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`manuals_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Volcado de datos para la tabla `dom_manuals`
--

INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`, `manuals_id`) VALUES
('44', 'Toma de muestra', '<p>Para examen de glicemia usar el tubo rojo sin aditivo, con 1 ml maximo 2 ml.</p>', 0, 6),
('44', 'Manejo de Statfax para glicemia Human', '<p>Una vez tomada la muestra.</p><p><b>Paso1.</b></p><p>dejar coagular 30 min. y centrifugar para obtener el suero.</p><p><b>Paso 2.</b></p><p>suero guardar en un ependorff, de acuerdo al volumen obtenido y rotular debidament con el codigo que nos dio el sistema y la fecha.</p><p><b>Paso 3.</b></p><p>Tenemos el reativo de Human, se usa 1 ml de reactivo + 10 ul de muestra dejar incubar a 37 grados por 5 minutos.</p><p><b>Paso 4.</b></p><p>Registrar los blancos y los valores obtenido en el statfax</p>', 0, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_modules`
--

CREATE TABLE IF NOT EXISTS `dom_modules` (
  `name_lang_key` varchar(255) NOT NULL,
  `desc_lang_key` varchar(255) NOT NULL,
  `sort` int(10) NOT NULL,
  `module_id` varchar(255) NOT NULL,
  PRIMARY KEY (`module_id`),
  UNIQUE KEY `desc_lang_key` (`desc_lang_key`),
  UNIQUE KEY `name_lang_key` (`name_lang_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_modules`
--

INSERT INTO `dom_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
('module_config', 'module_config_desc', 100, 'config'),
('module_customers', 'module_customers_desc', 10, 'customers'),
('module_doctors', 'module_doctors_desc', 12, 'doctors'),
('module_employees', 'module_employees_desc', 80, 'employees'),
('module_giftcards', 'module_giftcards_desc', 90, 'giftcards'),
('module_items', 'module_items_desc', 20, 'items'),
('module_item_kits', 'module_item_kits_desc', 30, 'item_kits'),
('module_labotests', 'module_labotests_desc', 14, 'labotests'),
('module_receivings', 'module_receivings_desc', 60, 'receivings'),
('module_registers', 'module_registers_desc', 18, 'registers'),
('module_reports', 'module_reports_desc', 50, 'reports'),
('module_sales', 'module_sales_desc', 70, 'sales'),
('module_suppliers', 'module_suppliers_desc', 40, 'suppliers'),
('module_toquotes', 'module_toquote_desc', 16, 'toquotes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_opciones`
--

CREATE TABLE IF NOT EXISTS `dom_opciones` (
  `opciones` varchar(255) NOT NULL,
  `tabla` varchar(255) NOT NULL,
  `opciones_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`opciones_id`),
  KEY `opciones_id` (`opciones_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Volcado de datos para la tabla `dom_opciones`
--

INSERT INTO `dom_opciones` (`opciones`, `tabla`, `opciones_id`) VALUES
('Positivo', 'opcpositivo', 1),
('Reactivo', 'opcreactivo', 2),
('Texto', '', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_opcpositivo`
--

CREATE TABLE IF NOT EXISTS `dom_opcpositivo` (
  `opcpositivo` varchar(255) NOT NULL,
  `opcpositivo_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`opcpositivo_id`),
  KEY `opcpositivo_id` (`opcpositivo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Volcado de datos para la tabla `dom_opcpositivo`
--

INSERT INTO `dom_opcpositivo` (`opcpositivo`, `opcpositivo_id`) VALUES
('Positivo', 1),
('Negativo', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_opcreactivo`
--

CREATE TABLE IF NOT EXISTS `dom_opcreactivo` (
  `opcreactivo` varchar(255) NOT NULL,
  `opcreactivo_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`opcreactivo_id`),
  KEY `opcreactivo_id` (`opcreactivo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Volcado de datos para la tabla `dom_opcreactivo`
--

INSERT INTO `dom_opcreactivo` (`opcreactivo`, `opcreactivo_id`) VALUES
('Reactivo', 1),
('No Reactivo', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_pago`
--

CREATE TABLE IF NOT EXISTS `dom_pago` (
  `registro_id` int(10) NOT NULL,
  `total_reco` varchar(255) NOT NULL,
  `total` varchar(255) NOT NULL,
  `monto_pagar` varchar(255) NOT NULL,
  `tipopago` varchar(255) NOT NULL,
  `saldo` varchar(255) NOT NULL,
  `comentarios` varchar(255) NOT NULL,
  `pago_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`pago_id`),
  KEY `pago_id` (`pago_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=41 ;

--
-- Volcado de datos para la tabla `dom_pago`
--

INSERT INTO `dom_pago` (`registro_id`, `total_reco`, `total`, `monto_pagar`, `tipopago`, `saldo`, `comentarios`, `pago_id`) VALUES
(0, '75.00', '500', '400', '1', '100.00', 'test', 1),
(0, '75.00', '500', '400', '1', '100.00', 'test', 2),
(0, '75.00', '500', '400', '1', '100.00', 'test', 3),
(0, '75.00', '500', '400', '1', '100.00', 'test', 4),
(0, '75.00', '500', '400', '1', '100.00', 'test', 5),
(0, '75.00', '500', '400', '1', '100.00', 'test', 6),
(7, '75.00', '500', '400', '1', '100.00', 'test', 7),
(8, '75.00', '500', '400', '1', '100.00', 'test', 8),
(9, '90.00', '90', '90', '1', '0.00', '', 9),
(10, '90.00', '90', '90', '1', '0.00', '', 10),
(11, '90.00', '90', '90', '1', '0.00', '', 11),
(12, '90.00', '90', '90', '1', '0.00', '', 12),
(13, '90.00', '90', '90', '1', '0.00', '', 13),
(14, '90.00', '90', '90', '1', '0.00', '', 14),
(15, '60.00', '60.00', '60', '1', '0.00', '', 15),
(16, '60.00', '60.00', '60', '1', '0.00', '', 16),
(17, '60.00', '60.00', '60', '1', '0.00', '', 17),
(18, '60.00', '60.00', '60', '1', '0.00', '', 18),
(19, '60.00', '60.00', '60', '1', '0.00', '', 19),
(20, '60.00', '60', '60', '1', '0.00', '', 20),
(21, '60.00', '60.00', '60', '1', '0.00', '', 21),
(22, '60.00', '60.00', '60', '1', '0.00', '', 22),
(23, '60.00', '60.00', '50', '1', '10.00', '', 23),
(24, '25.00', '25.00', '20', '1', '5.00', '', 24),
(25, '340.00', '350', '350', '1', '0.00', '', 25),
(26, '440.00', '400', '200', '1', '200.00', 'aun debe y es prepotente la paciente.', 26),
(27, '395.00', '400', '400', '1', '0.00', '', 27),
(28, '', '', '', '', '', '', 28),
(29, '', '', '', '', '', '', 29),
(30, '300.00', '300', '300', '1', '0.00', '', 30),
(31, '120.00', '100', '90', '1', '10.00', '', 31),
(32, '200.00', '200', '200', '1', '0.00', 'con factura', 32),
(33, '60.00', '60', '60', '1', '0.00', '', 33),
(34, '60.00', '', '', '', '', '', 34),
(35, '60.00', '60', '100', '1', '-40.00', '', 35),
(36, '60.00', '', '', '', '', '', 36),
(37, '60.00', '', '', '', '', '', 37),
(38, '', '', '', '', '', '', 38),
(39, '60.00', '', '', '', '', '', 39),
(40, '60.00', '', '', '', '', '', 40);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_people`
--

CREATE TABLE IF NOT EXISTS `dom_people` (
  `ci` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name_fa` varchar(255) NOT NULL,
  `last_name_mom` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `birthday` date NOT NULL,
  `gender` int(5) NOT NULL,
  `comments` text NOT NULL,
  `person_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`person_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=53413155 ;

--
-- Volcado de datos para la tabla `dom_people`
--

INSERT INTO `dom_people` (`ci`, `first_name`, `last_name_fa`, `last_name_mom`, `phone_number`, `email`, `birthday`, `gender`, `comments`, `person_id`) VALUES
('', 'Diego', 'Olivera', '', '591 70356723', 'info@olivera-solutions.com', '0000-00-00', 2, '', 1),
('', 'diego', 'asdasd', '', '4540629', 'asdasdas@asd.com', '0000-00-00', 2, 'asde asda ', 546),
('', 'Jorge', 'Chun', '', '', '', '0000-00-00', 2, '', 1256),
('', 'Ramiro', 'Gyan', '', '', '', '0000-00-00', 2, '', 423423),
('', 'jorge', 'nitales', '', '', '', '0000-00-00', 2, '', 745965),
('', 'Julio', 'Torrico', '', '', '', '0000-00-00', 2, '', 895627),
('', 'Comando', 'Vorter', '', '', '', '0000-00-00', 2, '', 958389),
('', 'ghsd', 'qweqwe', '', '', '', '0000-00-00', 2, '', 987689),
('', 'ferterasd', 'lasdoka13123', '', '', '', '0000-00-00', 2, '', 5272327),
('', 'Bob', 'Smith', '', '585-555-1111', 'bsmith@nowhere.com', '0000-00-00', 2, 'Awesome guy', 53413130),
('', 'Bob', 'Smith', '', '585-555-1111', 'bsmith@nowhere.com', '0000-00-00', 2, 'Awesome guy', 53413131),
('', 'Bob', 'Smith', '', '585-555-1111', 'bsmith@nowhere.com', '0000-00-00', 2, 'Awesome guy', 53413132),
('', 'Bob', 'Smith', '', '585-555-1111', 'bsmith@nowhere.com', '0000-00-00', 2, 'Awesome guy', 53413133),
('', 'Bob', 'Smith', '', '585-555-1111', 'bsmith@nowhere.com', '0000-00-00', 2, 'Awesome guy', 53413134),
('', 'Bob', 'Smith', '', '585-555-1111', 'bsmith@nowhere.com', '0000-00-00', 2, 'Awesome guy', 53413135),
('', 'Bob', 'Smith', '', '585-555-1111', 'bsmith@nowhere.com', '0000-00-00', 2, 'Awesome guy', 53413136),
('', 'Bob', 'Smith', '', '585-555-1111', 'bsmith@nowhere.com', '0000-00-00', 2, 'Awesome guy', 53413137),
('', 'Fernando', 'Lopez', '', '1 596 2653', 'supplier@hot.com', '0000-00-00', 2, '', 53413138),
('', 'pedro ', 'daza', '', '', '', '0000-00-00', 2, '', 53413139),
('', 'juan', 'romero', '', '', '', '0000-00-00', 2, '', 53413141),
('', 'carlos', 'carvajal', '', '', '', '0000-00-00', 2, '', 53413142),
('', 'john', '', '', '', '0', '0000-00-00', 2, '', 53413143),
('', 'john', '', '', '', '0', '0000-00-00', 2, '', 53413144),
('23423', 'john', 'test', 'test', '89798', 'asd@dasd.com', '1985-01-01', 2, 'asdasd', 53413145),
('897897', 'Stephani', 'Torrico', 'torrico', '4540629', 'asdas@asd.com', '2001-03-03', 2, '', 53413146),
('7938834', 'Pilar', 'Montanio', 'Ledezma', '70772745', 'stephanymontano19@gmail.com', '2001-03-03', 1, 'no pago sus cuentas', 53413147),
('9869868', 'prueba', 'test', 'test', '21312', 'ASD@ASD.COM', '0000-00-00', 1, '', 53413148),
('0', '0', '0', '0', '123455', '0', '0000-00-00', 2, 'este no sabe nada', 53413149),
('0', '0', '0', '0', '0', '0', '0000-00-00', 2, '0', 53413150),
('0', '0', '0', '0', '0', '0', '0000-00-00', 2, '0', 53413151),
('', 'asdasd', '', '', '', '', '0000-00-00', 0, '', 53413152),
('', '', '', '', '', '', '0000-00-00', 0, '', 53413153),
('', '', '', '', '', '', '0000-00-00', 0, '', 53413154);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_permissions`
--

CREATE TABLE IF NOT EXISTS `dom_permissions` (
  `module_id` varchar(255) NOT NULL,
  `person_id` int(10) NOT NULL,
  PRIMARY KEY (`module_id`,`person_id`),
  KEY `person_id` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_permissions`
--

INSERT INTO `dom_permissions` (`module_id`, `person_id`) VALUES
('config', 1),
('customers', 1),
('doctors', 1),
('employees', 1),
('labotests', 1),
('registers', 1),
('reports', 1),
('toquotes', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_poblacion`
--

CREATE TABLE IF NOT EXISTS `dom_poblacion` (
  `name` varchar(255) NOT NULL,
  `id_poblacion` int(11) NOT NULL,
  PRIMARY KEY (`id_poblacion`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `dom_poblacion`
--

INSERT INTO `dom_poblacion` (`name`, `id_poblacion`) VALUES
('Ni&ntilde;os', 0),
('Masculino', 1),
('Femenino', 2),
('Todos', 3),
('Recien Nacido', 4),
('Lactante', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_prianacategoria`
--

CREATE TABLE IF NOT EXISTS `dom_prianacategoria` (
  `name` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `cost` int(11) NOT NULL,
  `cost_deriv` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL,
  `anacategoria_id` int(10) NOT NULL,
  `compleja` tinyint(4) NOT NULL,
  `prianacategoria_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`prianacategoria_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=230 ;

--
-- Volcado de datos para la tabla `dom_prianacategoria`
--

INSERT INTO `dom_prianacategoria` (`name`, `order`, `cost`, `cost_deriv`, `deleted`, `anacategoria_id`, `compleja`, `prianacategoria_id`) VALUES
('Plaquetas', 3, 30, 25, 0, 2, 0, 1),
('Hemograma', 1, 60, 35, 0, 2, 1, 2),
('Hemoglobina', 5, 20, 10, 0, 2, 0, 3),
('Tiempo de Sangria', 1, 25, 20, 0, 3, 0, 4),
('Rotavirus', 11, 120, 80, 0, 4, 0, 5),
('Seriado x 3', 2, 80, 55, 0, 4, 0, 6),
('Fibrinogeno', 5, 120, 100, 0, 3, 0, 7),
('Gota Gruesa', 10, 40, 30, 0, 2, 0, 13),
('D-Dimeros', 12, 150, 120, 0, 2, 0, 14),
('Eritrosedimentacion', 2, 15, 15, 0, 2, 0, 15),
('Indices Hematimetricos', 4, 20, 10, 0, 2, 0, 16),
('Hematocrito', 6, 20, 10, 0, 2, 0, 17),
('Reticulocitos', 7, 60, 50, 0, 2, 0, 18),
('Coombs Indirecto', 8, 80, 60, 0, 2, 0, 19),
('Coombs Directo', 9, 80, 60, 0, 2, 0, 20),
('Grupo Sanguineo', 11, 30, 20, 0, 2, 0, 21),
('Tiempo de Coagulacion', 2, 25, 20, 0, 3, 0, 22),
('Tiempo de Protrombina -INR', 3, 60, 45, 0, 3, 0, 23),
('APTT', 4, 60, 45, 0, 3, 0, 24),
('Hierro Serico', 1, 120, 95, 0, 5, 0, 25),
('Transferrina - TIBC', 2, 100, 85, 0, 5, 0, 26),
('% de Saturacion', 3, 60, 40, 0, 5, 0, 27),
('Ferritina', 4, 150, 120, 0, 5, 0, 28),
('Amilasa', 1, 80, 65, 0, 6, 0, 29),
('Acido Urico', 2, 30, 20, 0, 6, 0, 30),
('Albumina', 3, 90, 75, 0, 6, 0, 31),
('Proteinas', 4, 90, 75, 0, 6, 0, 32),
('Globulina', 5, 30, 20, 0, 6, 0, 33),
('R A/G', 6, 70, 50, 0, 6, 0, 34),
('Bilirrubina (D,I,T)', 7, 80, 45, 0, 6, 0, 35),
('Creatinina', 8, 30, 20, 0, 6, 0, 36),
('Colinesterasa', 9, 120, 80, 0, 6, 0, 37),
('Colesterol', 10, 30, 20, 1, 6, 0, 38),
('Fosfatasa Alcalina', 11, 60, 50, 1, 6, 0, 39),
('Fosfatasa Acida', 12, 150, 100, 0, 6, 0, 40),
('GPT', 13, 30, 25, 1, 6, 0, 41),
('GOT', 14, 30, 25, 1, 6, 0, 42),
('GGT', 15, 90, 70, 1, 6, 0, 43),
('Glicemia', 16, 30, 20, 0, 6, 0, 44),
('Gluc. Post. Prandial', 17, 30, 20, 0, 6, 0, 45),
('Tolerancia de Glucosa', 18, 260, 200, 0, 6, 0, 46),
('Hb. Glicosilada', 19, 150, 110, 0, 6, 0, 47),
('LDH', 20, 90, 70, 0, 6, 0, 48),
('Trigliceridos', 21, 30, 20, 1, 6, 0, 49),
('Urea', 22, 30, 20, 0, 6, 0, 50),
('Nitrogeno Ureico', 23, 30, 20, 0, 6, 0, 51),
('Potasio', 1, 50, 30, 0, 7, 0, 52),
('Cloro', 2, 50, 30, 0, 7, 0, 53),
('Sodio', 3, 50, 30, 0, 7, 0, 54),
('Magnesio', 4, 50, 30, 0, 7, 0, 55),
('Fosforo', 5, 60, 50, 0, 7, 0, 56),
('Calcio', 6, 50, 30, 0, 7, 0, 57),
('Trigliceridos', 1, 30, 20, 0, 8, 0, 58),
('Colesterol', 2, 30, 20, 0, 8, 0, 59),
('C-HDL', 3, 30, 20, 0, 8, 0, 60),
('C-LDL', 4, 30, 20, 0, 8, 0, 61),
('VLDL', 5, 30, 20, 0, 8, 0, 62),
('GPT/ALT', 1, 30, 25, 0, 9, 0, 63),
('GOT/AST', 2, 30, 25, 0, 9, 0, 64),
('F. Alcalina', 3, 60, 50, 0, 9, 0, 65),
('GGT', 4, 90, 70, 0, 9, 0, 66),
('T. de Protrombina - INR', 5, 60, 45, 0, 9, 0, 67),
('CK', 1, 120, 95, 0, 10, 0, 68),
('CK-MB', 2, 120, 95, 0, 10, 0, 69),
('LDH', 3, 90, 70, 0, 10, 0, 70),
('GOT', 4, 30, 25, 0, 10, 0, 71),
('Troponina', 5, 120, 100, 0, 10, 0, 72),
('Fibrinogeno', 6, 120, 100, 0, 10, 0, 73),
('Perfil Lipidico', 7, 150, 100, 0, 10, 0, 74),
('ASTO', 1, 90, 65, 0, 11, 0, 75),
('Mononucelosis (Latex)', 2, 90, 65, 0, 11, 0, 76),
('Celulas LE (Latex)', 3, 80, 50, 0, 11, 0, 77),
('Brucella Abortus (Latex)', 4, 80, 50, 0, 11, 0, 78),
('Proteus (Latex)', 5, 80, 50, 0, 11, 0, 79),
('PCR (Latex)', 6, 90, 65, 0, 11, 0, 80),
('Latex RA (Latex)', 7, 90, 65, 0, 11, 0, 81),
('VDRL-RPR', 8, 60, 45, 0, 11, 0, 82),
('Reaccion de Widal', 9, 60, 45, 0, 11, 0, 83),
('Alfa Feto Proteina', 1, 150, 85, 0, 12, 0, 84),
('Beta 2-Microglobulina', 2, 150, 95, 0, 12, 0, 85),
('LDH', 3, 90, 70, 0, 12, 0, 86),
('CA 15-3', 4, 180, 90, 0, 12, 0, 87),
('CA 19-9', 5, 150, 90, 0, 12, 0, 88),
('CA 125 ', 6, 180, 90, 0, 12, 0, 89),
('CEA', 7, 150, 85, 0, 12, 0, 90),
('PSA Total', 8, 150, 85, 0, 12, 0, 91),
('PSA Libre', 9, 150, 85, 0, 12, 0, 92),
('hCG Cuantitativa', 10, 120, 100, 0, 12, 0, 93),
('GGT', 12, 90, 70, 0, 12, 0, 94),
('ACTH', 1, 220, 180, 0, 13, 0, 95),
('Cortisol AM.', 2, 150, 85, 0, 13, 0, 96),
('Estradiol', 3, 150, 85, 0, 13, 0, 97),
('FSH', 4, 150, 85, 0, 13, 0, 98),
('LH', 5, 150, 85, 0, 13, 0, 99),
('hCG Cuantitativa', 6, 120, 100, 0, 13, 0, 100),
('Insulina Basal am', 7, 150, 85, 0, 13, 0, 101),
('Insulina Post. Estimulo pm', 8, 150, 85, 0, 13, 0, 102),
('Progesterona', 9, 150, 90, 0, 13, 0, 103),
('Prolactina', 10, 150, 70, 0, 13, 0, 104),
('Testosterona Total', 11, 150, 90, 0, 13, 0, 105),
('H. de Crecimiento', 12, 150, 100, 0, 13, 0, 106),
('T3', 1, 150, 85, 0, 14, 0, 107),
('T4', 2, 150, 85, 0, 14, 0, 108),
('TSH', 3, 150, 85, 0, 14, 0, 109),
('TSH Neonatal', 4, 150, 85, 0, 14, 0, 110),
('T4 Libre', 5, 150, 85, 0, 14, 0, 111),
('CHAGAS', 1, 100, 80, 0, 15, 0, 112),
('Toxos. Ig G', 2, 150, 85, 0, 15, 0, 113),
('Toxos. Ig M', 3, 150, 85, 0, 15, 0, 114),
('Cisticercosis', 4, 150, 150, 0, 15, 0, 115),
('Clamidya Ig G', 5, 150, 85, 0, 15, 0, 116),
('Clamidya Ig M', 6, 150, 85, 0, 15, 0, 117),
('Herpes 1 Ig G', 7, 150, 85, 0, 15, 0, 118),
('Herpes 1 Ig M', 8, 150, 85, 0, 15, 0, 119),
('HIV 1 y 2', 9, 120, 100, 0, 15, 0, 120),
('H. Pilory IgG', 10, 150, 85, 0, 15, 0, 121),
('H. Pilory IgA', 11, 150, 85, 0, 15, 0, 122),
('H. Pilory IgM', 12, 150, 85, 0, 15, 0, 123),
('IgE', 13, 150, 90, 0, 15, 0, 124),
('HAV IgM ', 1, 150, 110, 0, 16, 0, 125),
('HVC Hepatitis C', 2, 120, 85, 0, 16, 0, 126),
('AgsHB', 3, 120, 90, 0, 16, 0, 127),
('ANA', 1, 150, 110, 0, 17, 0, 128),
('ANTI-DNA', 2, 150, 90, 0, 17, 0, 129),
('ANTI CCP', 3, 150, 90, 0, 17, 0, 130),
('ANCA P', 4, 150, 120, 0, 17, 0, 131),
('ANCA C', 5, 150, 120, 0, 17, 0, 132),
('ANA PROFILE', 6, 800, 550, 0, 17, 0, 133),
('Vitamina D', 7, 250, 200, 0, 17, 0, 134),
('C3', 1, 150, 110, 0, 18, 0, 135),
('C4', 2, 150, 110, 0, 18, 0, 136),
('IgG', 3, 150, 110, 0, 18, 0, 137),
('IgM', 4, 150, 110, 0, 18, 0, 138),
('IgA', 5, 150, 110, 0, 18, 0, 139),
('C3', 1, 150, 110, 0, 19, 0, 140),
('C4', 2, 150, 110, 0, 19, 0, 141),
('IgG', 3, 150, 110, 0, 19, 0, 142),
('IgM', 4, 150, 110, 0, 19, 0, 143),
('IgA', 5, 150, 110, 0, 19, 0, 144),
('CI Inactivador', 6, 150, 110, 0, 19, 0, 145),
('Citoquimicos LCR', 1, 350, 300, 0, 20, 0, 146),
('Liq. Pleural', 2, 350, 300, 0, 20, 0, 147),
('Liq. Sinovial', 3, 350, 300, 0, 20, 0, 148),
('Liq. Ascitico ', 4, 350, 300, 0, 20, 0, 149),
('Liq. Pericardico ', 5, 350, 300, 0, 20, 0, 150),
('Espermograma ', 6, 300, 250, 0, 20, 0, 151),
('Antigliadina IgM', 1, 250, 200, 0, 21, 0, 152),
('Antigliadina IgA', 2, 250, 200, 0, 21, 0, 153),
('Antiendomisio IgA', 3, 250, 200, 0, 21, 0, 154),
('Antiendomisio IgM', 4, 250, 200, 0, 21, 0, 155),
('Antitransglutaminasa IgA', 5, 230, 180, 0, 21, 0, 156),
('Antitransglutaminasa IgM', 6, 230, 180, 0, 21, 0, 157),
('Citologia Nasal ', 1, 60, 30, 0, 22, 0, 158),
('T. Gram ', 2, 40, 35, 0, 22, 0, 159),
('T. de Zielh Nielsen', 3, 40, 35, 0, 22, 0, 160),
('Ex. Directo', 4, 30, 15, 0, 22, 0, 161),
('Urocultivo', 5, 150, 120, 0, 22, 0, 162),
('Coprocultivo', 6, 150, 120, 0, 22, 0, 163),
('Hemocultivo X3', 7, 600, 500, 0, 22, 0, 164),
('Espermocultivo', 8, 150, 120, 0, 22, 0, 165),
('H. Faringeo cultivo', 9, 150, 120, 0, 22, 0, 166),
('S. Faringea cultivo', 10, 150, 120, 0, 22, 0, 167),
('Baciloscopia', 11, 40, 35, 0, 22, 0, 168),
('ADA', 12, 200, 200, 0, 22, 0, 169),
('BK Seriado', 13, 90, 60, 0, 22, 0, 170),
('Parcial de Orina', 1, 30, 20, 0, 23, 0, 171),
('Proteinuria 24Hr', 2, 80, 65, 0, 23, 0, 172),
('Creatinuria 24Hr', 3, 80, 65, 0, 23, 0, 173),
('hCG', 4, 50, 40, 0, 23, 0, 174),
('Amilasuria', 5, 80, 65, 0, 23, 0, 175),
('Clearence de Creatinina', 6, 80, 65, 0, 23, 0, 176),
('Na, K, Cl', 7, 90, 65, 1, 23, 0, 177),
('Coproparasitologico', 1, 30, 20, 0, 4, 0, 178),
('Lienteria', 3, 30, 25, 0, 4, 0, 179),
('Sangre Oculta', 4, 120, 90, 0, 4, 0, 180),
('Ag. H. Pylori', 5, 150, 110, 0, 4, 0, 181),
('PH y azucares reductores', 6, 80, 60, 0, 4, 0, 182),
('Moco Fecal', 7, 30, 30, 0, 4, 0, 183),
('Test. de Graham/Oixurus', 8, 50, 30, 0, 4, 0, 184),
('Elisa Giardia', 9, 150, 85, 0, 4, 0, 185),
('Elisa Amebas', 10, 150, 85, 0, 4, 0, 186),
('Adenovirus', 12, 120, 80, 0, 4, 0, 187),
('Respiratorio / Alimenticio', 1, 1350, 1100, 0, 24, 0, 188),
('Respiratorio', 2, 1350, 1100, 0, 24, 0, 189),
('Alimenticio', 3, 1350, 1100, 0, 24, 0, 190),
('Pediatrico', 4, 1350, 1100, 0, 24, 0, 191),
('De Granulacion de Basofilos (Sangre)', 1, 150, 110, 0, 25, 0, 192),
('De Granulacion de Basofilos (Orina)', 2, 150, 110, 0, 25, 0, 193),
('Celulas LE', 13, 60, 50, 0, 2, 0, 202),
('Troponina', 14, 120, 100, 0, 2, 0, 203),
('Cultivo de hongos', 14, 150, 120, 0, 22, 0, 204),
('Cultivo p/TB', 15, 150, 150, 0, 22, 0, 205),
('Cultivos de liquidos', 16, 150, 120, 0, 22, 0, 206),
('Rotavirus Inmunocromatografia', 14, 120, 100, 0, 15, 0, 207),
('Amebas/Giardia/Criptoscopidium', 13, 200, 150, 0, 4, 0, 208),
('Lipasa', 24, 180, 120, 0, 6, 0, 209),
('Perfil de Hierro', 5, 300, 220, 0, 5, 0, 210),
('Gasometria', 3, 400, 400, 0, 25, 0, 211),
('Amonio', 5, 300, 300, 0, 25, 0, 212),
('Citomegalovirus IgG Elisa', 15, 150, 85, 0, 15, 0, 213),
('Citomegalovirus IgM Elisa ', 16, 150, 85, 0, 15, 0, 214),
('Herpes ll (2) IgM', 17, 150, 85, 0, 15, 0, 215),
('Herpes ll(2) IgG', 18, 150, 85, 0, 15, 0, 216),
('Cortisol PM', 13, 150, 85, 0, 13, 0, 217),
('HAV IgG', 4, 150, 110, 0, 16, 0, 218),
('HbcAg', 5, 120, 110, 0, 16, 0, 219),
('HbsAg imunocromatografia', 6, 120, 85, 0, 16, 0, 220),
('Testosterona Libre', 14, 150, 90, 0, 13, 0, 221),
('Alfa Feto Proteína AFP', 15, 150, 85, 0, 13, 0, 222),
('Rubeola IgG', 8, 150, 85, 0, 17, 0, 223),
('Rubeola IgM', 9, 150, 85, 0, 17, 0, 224),
('hCG', 10, 50, 35, 0, 11, 0, 225),
('BUN', 18, 0, 0, 0, 6, 0, 226),
('17 OH-Progesterona', 10, 200, 110, 0, 25, 0, 227),
('Hemograma', 0, 60, 30, 0, 26, 0, 228),
('Glicemia', 2, 30, 15, 0, 26, 0, 229);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_priresultados`
--

CREATE TABLE IF NOT EXISTS `dom_priresultados` (
  `prianacategoria_id` int(11) NOT NULL,
  `id_poblacion` int(11) NOT NULL,
  `valor_min` varchar(11) NOT NULL,
  `valor_max` varchar(11) NOT NULL,
  `umedida` varchar(255) NOT NULL,
  `formulas_id` int(25) NOT NULL,
  `opcion_id` int(10) NOT NULL,
  `deleted` tinyint(4) NOT NULL,
  `priresultados_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`priresultados_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=74 ;

--
-- Volcado de datos para la tabla `dom_priresultados`
--

INSERT INTO `dom_priresultados` (`prianacategoria_id`, `id_poblacion`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`, `priresultados_id`) VALUES
(12, 0, '100', '200', '', 1, 3, 0, 1),
(12, 1, '200', '400', '', 1, 3, 0, 3),
(2, 0, '', '', '', 1, 3, 1, 5),
(200, 0, '10', '20', '', 1, 3, 0, 6),
(200, 1, '40', '90', '', 1, 3, 0, 7),
(200, 2, '80', '70', '', 1, 3, 1, 8),
(122, 0, '', '', '', 1, 3, 0, 9),
(111, 0, '', '', '', 1, 3, 0, 10),
(111, 0, '', '', '', 1, 3, 0, 11),
(111, 0, '', '', '', 1, 3, 0, 12),
(129, 0, '', '', '', 1, 3, 0, 13),
(3, 0, '34', '90', '', 1, 3, 1, 14),
(3, 2, '20', '90', '', 1, 3, 1, 15),
(25, 0, '40', '90', '', 1, 3, 0, 16),
(25, 1, '10', '80', 'g/dl', 1, 3, 0, 17),
(4, 1, '3', '5', 'Minutos', 1, 3, 0, 18),
(1, 3, '150000', '400000', 'mm³', 1, 3, 0, 19),
(63, 3, '0', '37', 'U/L', 1, 3, 0, 20),
(64, 3, '0', '37', 'U/L', 1, 3, 0, 21),
(41, 3, '0', '37', 'U/L', 1, 3, 0, 22),
(42, 3, '0', '37', 'U/L', 1, 3, 0, 23),
(71, 3, '0', '37', 'U/L', 1, 3, 0, 24),
(36, 1, '0.9', '1.5', 'mg/dl', 1, 3, 0, 25),
(36, 2, '0.7', '1.4', 'mg/dl', 1, 3, 0, 26),
(43, 3, '5', '35', 'U/L', 1, 3, 0, 27),
(66, 3, '5', '35', 'U/L', 1, 3, 0, 28),
(137, 3, '700', '1600', 'mg/dl', 1, 3, 0, 29),
(142, 3, '700', '1600', 'mg/dl', 1, 3, 0, 30),
(138, 3, '40', '230', 'mg/dl', 1, 3, 0, 31),
(143, 3, '40', '230', 'mg/dl', 1, 3, 0, 32),
(124, 3, '0', '100', 'UI/ml', 1, 3, 0, 33),
(139, 3, '70', '400', 'mg/dl', 1, 3, 0, 34),
(94, 3, '5', '35', 'U/L', 1, 3, 0, 35),
(144, 3, '40', '400', 'mg/dl', 1, 3, 0, 36),
(192, 3, '0', '20', '% Negativo', 1, 3, 0, 37),
(192, 3, '21', '25', '% Dudoso', 1, 3, 0, 38),
(192, 3, '26', '50', '% Positivo', 1, 3, 1, 39),
(192, 3, '26', '50', '% Positivo (+)', 1, 3, 0, 40),
(192, 3, '51', '75', '% Positivo (++)', 1, 3, 0, 41),
(192, 3, '76', '500', '% Positivo (+++)', 1, 3, 0, 43),
(59, 3, '150', '260', 'mg/dl', 1, 3, 0, 44),
(38, 3, '150', '260', 'mg/dl', 1, 3, 0, 45),
(58, 0, '30', '200', 'mg/dl', 1, 3, 1, 46),
(58, 3, '30', '200', 'mg/dl', 1, 3, 0, 47),
(49, 3, '30', '200', 'mg/dl', 1, 3, 0, 48),
(60, 1, '45', '', 'mg/dl', 1, 3, 0, 49),
(60, 2, '55', '', 'mg/dl', 1, 3, 0, 50),
(61, 3, '60', '180', 'mg/dl', 1, 3, 0, 51),
(62, 3, '25', '50', '%', 1, 3, 0, 52),
(39, 3, '0', '306', 'U/L', 1, 3, 0, 53),
(39, 0, '0', '400', 'U/L', 1, 3, 0, 54),
(65, 3, '0', '306', 'U/L', 1, 3, 0, 55),
(65, 0, '0', '400', 'U/L', 1, 3, 0, 56),
(35, 3, '0.1', '1.2', 'mg/dl', 1, 3, 0, 57),
(35, 4, '0.1', '12', 'mg/dl', 1, 3, 0, 58),
(44, 3, '70', '115', 'mg/dl', 1, 3, 0, 59),
(80, 3, '0', '3', 'mg/l', 1, 3, 0, 60),
(15, 1, '15', '', 'mm', 1, 3, 0, 61),
(15, 2, '20', '', 'mm', 1, 3, 0, 62),
(50, 3, '20', '40', 'mg/dl', 1, 3, 0, 63),
(23, 3, '12', '14', 'Segundos', 1, 3, 0, 64),
(52, 3, '3.5', '5.3', 'mEq/L', 1, 3, 0, 65),
(54, 3, '135', '145', 'mEq/L', 1, 3, 0, 66),
(53, 3, '98', '108', 'mEq/L', 1, 3, 0, 67),
(226, 3, '9', '18', 'mg/dl', 1, 3, 0, 68),
(22, 3, '', '12', 'Minutos ', 1, 2, 0, 69),
(57, 3, '8.1', '10.5', 'mg/dl', 1, 3, 0, 70),
(227, 3, '80', '90', 'g/ml', 1, 1, 1, 71),
(4, 2, '90', '120', 'g/ml', 1, 1, 0, 72),
(227, 2, '100', '200', 'Iu', 1, 3, 0, 73);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_receivings`
--

CREATE TABLE IF NOT EXISTS `dom_receivings` (
  `receiving_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `supplier_id` int(10) DEFAULT NULL,
  `employee_id` int(10) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `receiving_id` int(10) NOT NULL AUTO_INCREMENT,
  `payment_type` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`receiving_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Volcado de datos para la tabla `dom_receivings`
--

INSERT INTO `dom_receivings` (`receiving_time`, `supplier_id`, `employee_id`, `comment`, `receiving_id`, `payment_type`) VALUES
('2015-07-03 04:16:57', NULL, 1, 'pago', 1, 'Efectivo'),
('2015-07-03 04:17:57', 53413138, 1, 'pagado con 100', 2, 'Efectivo'),
('2015-07-03 04:20:18', NULL, 1, '', 3, 'Efectivo'),
('2015-07-03 04:23:29', NULL, 1, '', 4, 'Efectivo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_receivings_items`
--

CREATE TABLE IF NOT EXISTS `dom_receivings_items` (
  `receiving_id` int(10) NOT NULL DEFAULT '0',
  `item_id` int(10) NOT NULL DEFAULT '0',
  `description` varchar(30) DEFAULT NULL,
  `serialnumber` varchar(30) DEFAULT NULL,
  `line` int(3) NOT NULL,
  `quantity_purchased` decimal(15,2) NOT NULL DEFAULT '0.00',
  `item_cost_price` decimal(15,2) NOT NULL,
  `item_unit_price` decimal(15,2) NOT NULL,
  `discount_percent` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`receiving_id`,`item_id`,`line`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_receivings_items`
--

INSERT INTO `dom_receivings_items` (`receiving_id`, `item_id`, `description`, `serialnumber`, `line`, `quantity_purchased`, `item_cost_price`, `item_unit_price`, `discount_percent`) VALUES
(1, 26, '', '', 1, '1.00', '10.00', '10.00', '0.00'),
(2, 26, '', '', 1, '1.00', '10.00', '10.00', '0.00'),
(3, 26, '', '', 1, '-1.00', '10.00', '10.00', '0.00'),
(4, 29, '', '', 1, '1.00', '10.00', '10.00', '0.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_registro`
--

CREATE TABLE IF NOT EXISTS `dom_registro` (
  `person_id` int(10) NOT NULL,
  `doctor_id` int(10) NOT NULL,
  `pruebas` varchar(255) NOT NULL,
  `ingreso` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_session` int(25) NOT NULL,
  `registro_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`registro_id`),
  KEY `registro_id` (`registro_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=41 ;

--
-- Volcado de datos para la tabla `dom_registro`
--

INSERT INTO `dom_registro` (`person_id`, `doctor_id`, `pruebas`, `ingreso`, `id_session`, `registro_id`) VALUES
(53413146, 59, '2,26,36,52,82', '2025-10-07 09:23:28', 1, 30),
(53413146, 66, '2,82', '2025-10-08 09:37:56', 1, 31),
(53413146, 59, '227', '2025-10-08 09:54:04', 1, 32),
(53413146, 66, '2', '2025-10-08 12:26:38', 1, 33),
(53413146, 66, '2', '2025-10-08 12:44:50', 1, 34),
(53413146, 66, '2', '2025-10-10 09:21:01', 1, 35),
(53413146, 59, '2', '2025-10-11 08:28:39', 1, 36),
(0, 0, '2', '2025-10-11 08:52:57', 1, 37),
(53413148, 59, '2', '2025-10-11 08:53:17', 1, 38),
(53413146, 59, '2', '2025-10-11 10:43:23', 1, 39),
(53413146, 59, '2', '2025-10-11 11:00:02', 1, 40);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_regvalues`
--

CREATE TABLE IF NOT EXISTS `dom_regvalues` (
  `regvalues` varchar(255) NOT NULL,
  `registro_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `id_session` int(25) NOT NULL,
  `regvalues_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`regvalues_id`),
  KEY `regvalues_id` (`regvalues_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=198 ;

--
-- Volcado de datos para la tabla `dom_regvalues`
--

INSERT INTO `dom_regvalues` (`regvalues`, `registro_id`, `name`, `id_session`, `regvalues_id`) VALUES
('23', '30', 'c_2', 1, 81),
('162', '30', 'c_4', 1, 82),
('5', '30', 'c_6', 1, 83),
('44.5', '30', 'c_8', 1, 84),
('49', '30', 'c_10', 1, 85),
('1', '30', 'c_11', 1, 86),
('', '30', 'c_12', 1, 87),
('', '30', 'c_13', 1, 88),
('', '30', 'c_14', 1, 89),
('', '30', 'c_16', 1, 90),
('', '30', 'c_19', 1, 91),
('103', '30', 'c_21', 1, 92),
('', '30', 'noc_36', 1, 93),
('', '30', 'noc_52', 1, 94),
('', '31', 'c_2', 1, 95),
('', '31', 'c_4', 1, 96),
('', '31', 'c_6', 1, 97),
('', '31', 'c_8', 1, 98),
('', '31', 'c_10', 1, 99),
('', '31', 'c_11', 1, 100),
('', '31', 'c_12', 1, 101),
('', '31', 'c_13', 1, 102),
('', '31', 'c_14', 1, 103),
('', '31', 'c_16', 1, 104),
('', '31', 'c_19', 1, 105),
('', '31', 'c_21', 1, 106),
('', '32', 'noc_71', 1, 107),
('70', '32', 'noc_227', 1, 108),
('', '33', 'c_2', 1, 109),
('128', '33', 'c_4', 1, 110),
('', '33', 'c_6', 1, 111),
('38.9', '33', 'c_8', 1, 112),
('53', '33', 'c_10', 1, 113),
('43', '33', 'c_11', 1, 114),
('1', '33', 'c_12', 1, 115),
('2', '33', 'c_13', 1, 116),
('', '33', 'c_14', 1, 117),
('', '33', 'c_16', 1, 118),
('', '33', 'c_19', 1, 119),
('85', '33', 'c_21', 1, 120),
('', '34', 'c_2', 1, 121),
('152', '34', 'c_4', 1, 122),
('', '34', 'c_6', 1, 123),
('47.1', '34', 'c_8', 1, 124),
('69', '34', 'c_10', 1, 125),
('24', '34', 'c_11', 1, 126),
('1', '34', 'c_12', 1, 127),
('4', '34', 'c_13', 1, 128),
('', '34', 'c_14', 1, 129),
('', '34', 'c_16', 1, 130),
('', '34', 'c_19', 1, 131),
('', '34', 'c_21', 1, 132),
('', '35', 'c_2', 1, 133),
('', '35', 'c_4', 1, 134),
('90', '35', 'c_6', 1, 135),
('29', '35', 'c_8', 1, 136),
('67', '35', 'c_10', 1, 137),
('65', '35', 'c_11', 1, 138),
('', '35', 'c_12', 1, 139),
('', '35', 'c_13', 1, 140),
('', '35', 'c_14', 1, 141),
('', '35', 'c_16', 1, 142),
('', '35', 'c_19', 1, 143),
('', '35', 'c_21', 1, 144),
('', '35', 'c_23', 1, 145),
('', '36', 'c_2', 1, 146),
('156', '36', 'c_4', 1, 147),
('', '36', 'c_6', 1, 148),
('43.6', '36', 'c_8', 1, 149),
('55', '36', 'c_10', 1, 150),
('40', '36', 'c_11', 1, 151),
('3', '36', 'c_12', 1, 152),
('2', '36', 'c_13', 1, 153),
('', '36', 'c_14', 1, 154),
('', '36', 'c_16', 1, 155),
('', '36', 'c_19', 1, 156),
('111', '36', 'c_21', 1, 157),
('', '36', 'c_23', 1, 158),
('', '38', 'c_3', 1, 159),
('156', '38', 'c_4', 1, 160),
('', '38', 'c_5', 1, 161),
('43.6', '38', 'c_7', 1, 162),
('57', '38', 'c_10', 1, 163),
('36', '38', 'c_11', 1, 164),
('3', '38', 'c_12', 1, 165),
('3', '38', 'c_13', 1, 166),
('', '38', 'c_14', 1, 167),
('', '38', 'c_16', 1, 168),
('', '38', 'c_19', 1, 169),
('111', '38', 'c_21', 1, 170),
('1', '38', 'c_23', 1, 171),
('', '39', 'c_2', 1, 172),
('201', '39', 'c_4', 1, 173),
('', '39', 'c_6', 1, 174),
('36.4', '39', 'c_8', 1, 175),
('49', '39', 'c_10', 1, 176),
('47', '39', 'c_11', 1, 177),
('2', '39', 'c_12', 1, 178),
('2', '39', 'c_13', 1, 179),
('', '39', 'c_14', 1, 180),
('', '39', 'c_16', 1, 181),
('', '39', 'c_19', 1, 182),
('293', '39', 'c_21', 1, 183),
('', '39', 'c_23', 1, 184),
('', '40', 'c_2', 1, 185),
('86', '40', 'c_4', 1, 186),
('', '40', 'c_6', 1, 187),
('39.2', '40', 'c_8', 1, 188),
('31', '40', 'c_10', 1, 189),
('63', '40', 'c_11', 1, 190),
('3', '40', 'c_12', 1, 191),
('2', '40', 'c_13', 1, 192),
('', '40', 'c_14', 1, 193),
('', '40', 'c_16', 1, 194),
('', '40', 'c_19', 1, 195),
('67', '40', 'c_21', 1, 196),
('1', '40', 'c_23', 1, 197);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_resulanalisis`
--

CREATE TABLE IF NOT EXISTS `dom_resulanalisis` (
  `padre` varchar(255) NOT NULL,
  `hijo` varchar(255) NOT NULL,
  `analisis` varchar(255) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `unidad` varchar(255) NOT NULL,
  `minimo` varchar(255) NOT NULL,
  `maximo` varchar(255) NOT NULL,
  `registro_id` varchar(255) NOT NULL,
  `id_session` int(25) NOT NULL,
  `estado_id` int(10) NOT NULL,
  `resulanalisis_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`resulanalisis_id`),
  KEY `resulanalisis_id` (`resulanalisis_id`),
  KEY `fk_estado_resulanalisis` (`estado_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=118 ;

--
-- Volcado de datos para la tabla `dom_resulanalisis`
--

INSERT INTO `dom_resulanalisis` (`padre`, `hijo`, `analisis`, `valor`, `unidad`, `minimo`, `maximo`, `registro_id`, `id_session`, `estado_id`, `resulanalisis_id`) VALUES
('Hematologia', 'Hemograma', 'Eritrocitos', '4796000', 'mm³', '4000000', '5500000', '36', 1, 1, 105),
('Hematologia', 'Hemograma', 'Leucocitos ', '7800', 'mm³', '4500', '9500', '36', 1, 1, 106),
('Hematologia', 'Hemograma', 'Hemoglobina ', '14.4', 'g%', '12.3', '14', '36', 1, 1, 107),
('Hematologia', 'Hemograma', 'Hematocrito ', '43.6', '%', '37', '42', '36', 1, 1, 108),
('Hematologia', 'Hemograma', 'Segmentados ', '55', '%', '55', '65', '36', 1, 1, 109),
('Hematologia', 'Hemograma', 'Linfocitos ', '40', '%', '25', '35', '36', 1, 1, 110),
('Hematologia', 'Hemograma', 'Monocitos ', '3', '%', '3', '8', '36', 1, 1, 111),
('Hematologia', 'Hemograma', 'Eosinofilos', '2', '%', '1', '3', '36', 1, 1, 112),
('Hematologia', 'Hemograma', 'HCM', '30.0', 'pg', '32', '36', '36', 1, 1, 113),
('Hematologia', 'Hemograma', 'VCM', '90.9', 'U³', '82', '92', '36', 1, 1, 114),
('Hematologia', 'Hemograma', 'CHCM', '33.0', '%', '27', '33', '36', 1, 1, 115),
('Hematologia', 'Hemograma', 'Plaquetas ', '444000', 'mm³', '150000', '400000', '36', 1, 1, 116),
('Hematologia', 'Hemograma', 'Cayados', '', '%', '0', '4', '36', 1, 1, 117);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sales`
--

CREATE TABLE IF NOT EXISTS `dom_sales` (
  `sale_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` int(10) DEFAULT NULL,
  `employee_id` int(10) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `sale_id` int(10) NOT NULL AUTO_INCREMENT,
  `payment_type` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`sale_id`),
  KEY `customer_id` (`customer_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

--
-- Volcado de datos para la tabla `dom_sales`
--

INSERT INTO `dom_sales` (`sale_time`, `customer_id`, `employee_id`, `comment`, `sale_id`, `payment_type`) VALUES
('2014-08-15 00:46:08', NULL, 1, '', 1, 'Efectivo: $20.00<br />'),
('2014-08-15 14:52:34', NULL, 1, '', 2, 'Efectivo: S/.72.00<br />'),
('2014-08-18 14:22:57', NULL, 1, '0', 3, 'Efectivo: S/.83.76<br />'),
('2014-08-18 14:23:40', NULL, 1, '0', 4, 'Efectivo: S/.0.00<br />'),
('2014-08-18 14:24:31', NULL, 1, '', 5, 'Efectivo: S/.0.00<br />'),
('2014-08-18 14:27:46', NULL, 1, '0', 6, 'Efectivo: S/.36.00<br />'),
('2014-08-18 14:33:13', NULL, 1, '0', 7, 'Efectivo: S/.12.00<br />'),
('2014-12-04 13:45:07', NULL, 1, '0', 8, 'Efectivo: S/.62.00<br />'),
('2015-07-01 21:27:07', 53413137, 1, 'se realizo compra', 9, 'Efectivo: S/.20.00<br />'),
('2015-07-03 05:03:32', 1256, 1, '0', 10, 'Efectivo: Bs. 90.00<br />'),
('2015-07-03 10:22:40', 423423, 1, 'das', 11, 'Efectivo: Bs. 45.00<br />');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sales_items`
--

CREATE TABLE IF NOT EXISTS `dom_sales_items` (
  `sale_id` int(10) NOT NULL DEFAULT '0',
  `item_id` int(10) NOT NULL DEFAULT '0',
  `description` varchar(30) DEFAULT NULL,
  `serialnumber` varchar(30) DEFAULT NULL,
  `line` int(3) NOT NULL DEFAULT '0',
  `quantity_purchased` decimal(15,2) NOT NULL DEFAULT '0.00',
  `item_cost_price` decimal(15,2) NOT NULL,
  `item_unit_price` decimal(15,2) NOT NULL,
  `discount_percent` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`sale_id`,`item_id`,`line`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_sales_items`
--

INSERT INTO `dom_sales_items` (`sale_id`, `item_id`, `description`, `serialnumber`, `line`, `quantity_purchased`, `item_cost_price`, `item_unit_price`, `discount_percent`) VALUES
(1, 1, '', '', 1, '1.00', '20.00', '20.00', '0.00'),
(2, 3, '', '', 1, '5.00', '10.00', '12.00', '0.00'),
(2, 7, '', '', 2, '1.00', '10.00', '12.00', '0.00'),
(3, 3, '', '', 1, '5.00', '10.00', '12.00', '0.00'),
(3, 8, '0', '0', 3, '2.00', '10.00', '12.00', '1.00'),
(4, 29, '', '', 1, '1.00', '0.00', '0.00', '0.00'),
(5, 33, '', '', 1, '1.00', '0.00', '0.00', '0.00'),
(6, 26, '', '', 1, '1.00', '10.00', '12.00', '0.00'),
(6, 32, '0', '0', 2, '2.00', '10.00', '12.00', '0.00'),
(7, 30, '', '', 1, '1.00', '10.00', '12.00', '0.00'),
(8, 28, '', '', 1, '1.00', '10.00', '12.00', '0.00'),
(9, 27, '', '', 1, '1.00', '10.00', '12.00', '0.00'),
(10, 29, '', '', 1, '1.00', '10.00', '12.00', '0.00'),
(10, 31, '', '', 2, '1.00', '10.00', '12.00', '0.00'),
(11, 30, '0', '0', 1, '2.00', '10.00', '12.00', '0.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sales_items_taxes`
--

CREATE TABLE IF NOT EXISTS `dom_sales_items_taxes` (
  `sale_id` int(10) NOT NULL,
  `item_id` int(10) NOT NULL,
  `line` int(3) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `percent` decimal(15,2) NOT NULL,
  PRIMARY KEY (`sale_id`,`item_id`,`line`,`name`,`percent`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sales_payments`
--

CREATE TABLE IF NOT EXISTS `dom_sales_payments` (
  `sale_id` int(10) NOT NULL,
  `payment_type` varchar(40) NOT NULL,
  `payment_amount` decimal(15,2) NOT NULL,
  PRIMARY KEY (`sale_id`,`payment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_sales_payments`
--

INSERT INTO `dom_sales_payments` (`sale_id`, `payment_type`, `payment_amount`) VALUES
(1, 'Efectivo', '20.00'),
(2, 'Efectivo', '72.00'),
(3, 'Efectivo', '83.76'),
(4, 'Efectivo', '0.00'),
(5, 'Efectivo', '0.00'),
(6, 'Efectivo', '36.00'),
(7, 'Efectivo', '12.00'),
(8, 'Efectivo', '62.00'),
(9, 'Efectivo', '20.00'),
(10, 'Efectivo', '90.00'),
(11, 'Efectivo', '45.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sales_suspended`
--

CREATE TABLE IF NOT EXISTS `dom_sales_suspended` (
  `sale_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` int(10) DEFAULT NULL,
  `employee_id` int(10) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `sale_id` int(10) NOT NULL AUTO_INCREMENT,
  `payment_type` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`sale_id`),
  KEY `customer_id` (`customer_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Volcado de datos para la tabla `dom_sales_suspended`
--

INSERT INTO `dom_sales_suspended` (`sale_time`, `customer_id`, `employee_id`, `comment`, `sale_id`, `payment_type`) VALUES
('2015-07-03 10:23:02', 423423, 1, '', 2, 'Efectivo: Bs. 32.00<br />');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sales_suspended_items`
--

CREATE TABLE IF NOT EXISTS `dom_sales_suspended_items` (
  `sale_id` int(10) NOT NULL DEFAULT '0',
  `item_id` int(10) NOT NULL DEFAULT '0',
  `description` varchar(30) DEFAULT NULL,
  `serialnumber` varchar(30) DEFAULT NULL,
  `line` int(3) NOT NULL DEFAULT '0',
  `quantity_purchased` decimal(15,2) NOT NULL DEFAULT '0.00',
  `item_cost_price` decimal(15,2) NOT NULL,
  `item_unit_price` decimal(15,2) NOT NULL,
  `discount_percent` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`sale_id`,`item_id`,`line`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_sales_suspended_items`
--

INSERT INTO `dom_sales_suspended_items` (`sale_id`, `item_id`, `description`, `serialnumber`, `line`, `quantity_purchased`, `item_cost_price`, `item_unit_price`, `discount_percent`) VALUES
(2, 31, '', '', 1, '1.00', '10.00', '12.00', '0.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sales_suspended_items_taxes`
--

CREATE TABLE IF NOT EXISTS `dom_sales_suspended_items_taxes` (
  `sale_id` int(10) NOT NULL,
  `item_id` int(10) NOT NULL,
  `line` int(3) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `percent` decimal(15,2) NOT NULL,
  PRIMARY KEY (`sale_id`,`item_id`,`line`,`name`,`percent`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sales_suspended_payments`
--

CREATE TABLE IF NOT EXISTS `dom_sales_suspended_payments` (
  `sale_id` int(10) NOT NULL,
  `payment_type` varchar(40) NOT NULL,
  `payment_amount` decimal(15,2) NOT NULL,
  PRIMARY KEY (`sale_id`,`payment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_sales_suspended_payments`
--

INSERT INTO `dom_sales_suspended_payments` (`sale_id`, `payment_type`, `payment_amount`) VALUES
(2, 'Efectivo', '32.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_secanacategoria`
--

CREATE TABLE IF NOT EXISTS `dom_secanacategoria` (
  `prianacategoria_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `valor_min` varchar(255) NOT NULL,
  `valor_max` varchar(255) NOT NULL,
  `umedida` varchar(255) NOT NULL,
  `formulas_id` int(25) NOT NULL,
  `opcion_id` int(10) NOT NULL,
  `deleted` tinyint(4) NOT NULL,
  `secanacategoria_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`secanacategoria_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24 ;

--
-- Volcado de datos para la tabla `dom_secanacategoria`
--

INSERT INTO `dom_secanacategoria` (`prianacategoria_id`, `nombre`, `paciente_id`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`, `secanacategoria_id`) VALUES
(2, 'Eritrocitos', 2, '4000000', '5500000', 'mm³', 3, 3, 0, 2),
(2, 'Eritrocitos ', 1, '4500000', '6000000', 'mm³', 3, 3, 0, 3),
(2, 'Leucocitos ', 3, '4500', '9500', 'mm³', 2, 3, 0, 4),
(2, 'Hemoglobina ', 1, '14', '16', 'g%', 4, 3, 0, 5),
(2, 'Hemoglobina ', 2, '12.3', '14', 'g%', 4, 3, 0, 6),
(2, 'Hematocrito ', 1, '42', '50', '%', 1, 3, 0, 7),
(2, 'Hematocrito ', 2, '37', '42', '%', 1, 3, 0, 8),
(2, 'Hematocrito ', 4, '50', '65', '%', 1, 3, 0, 9),
(2, 'Segmentados ', 3, '55', '65', '%', 1, 3, 0, 10),
(2, 'Linfocitos ', 3, '25', '35', '%', 1, 3, 0, 11),
(2, 'Monocitos ', 3, '3', '8', '%', 1, 3, 0, 12),
(2, 'Eosinofilos', 3, '1', '3', '%', 1, 3, 0, 13),
(2, 'HCM', 3, '32', '36', 'pg', 6, 3, 0, 14),
(2, 'HCM', 4, '23', '34', 'pg', 6, 3, 0, 15),
(2, 'VCM', 3, '82', '92', 'U³', 7, 3, 0, 16),
(2, 'VCM', 0, '80', '95', 'U³', 7, 3, 0, 17),
(2, 'VCM', 4, '105', '105', 'U³', 7, 3, 0, 18),
(2, 'CHCM', 3, '27', '33', '%', 8, 3, 0, 19),
(2, 'CHCM', 4, '27', '33', '%', 8, 3, 0, 20),
(2, 'Plaquetas ', 3, '150000', '400000', 'mm³', 5, 3, 0, 21),
(227, 'feree', 2, '90', '80', 'g/ul', 1, 2, 1, 22),
(2, 'Cayados', 3, '0', '4', '%', 1, 3, 0, 23);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_sessions`
--

CREATE TABLE IF NOT EXISTS `dom_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `user_agent` varchar(120) NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` text,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_sessions`
--

INSERT INTO `dom_sessions` (`session_id`, `ip_address`, `user_agent`, `last_activity`, `user_data`) VALUES
('13218b21a5ea8dc742c8861e447242e6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 1753882919, 'a:5:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";s:8:"cartRecv";a:0:{}s:9:"recv_mode";s:7:"receive";s:8:"supplier";i:-1;}'),
('293ceea33dfdbc52447b8dfc70701bf1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1761220706, ''),
('4c574d442af29ac1f3b8ad8841248012', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 1754666237, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('5764495860577235bc9dc38ef3465707', '192.168.1.203', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1759848511, ''),
('73fe60838fe0feac29bf46ea4bb908c0', '192.168.1.210', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 1761220432, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('7a8790cbea66bd991b9846c46ee6a46e', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1761743437, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('9ee9fda26910fdb9b594b3a97a1b8f8b', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 1754050815, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('abe917a3ebdc69f8145831cd6688d6d2', '192.168.1.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', 1754666061, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('b5bdfff86e13c77bc64bb3b062fed1e3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 1771020181, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('b6c4e65a0813e086b23287e1bc4d8f2e', '192.168.1.216', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Mobile/15', 1761220807, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('d2f5568278cc0f8d0bb5ef3720502861', '192.168.1.210', 'Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', 1761739834, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('fd9b4aa2253925c0b70b8ec238b3aeff', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 1758109340, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}'),
('fec62b755a3d650acae15471c4621e4b', '192.168.1.210', 'Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', 1761744313, 'a:2:{s:9:"user_data";s:0:"";s:9:"person_id";s:1:"1";}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_suppliers`
--

CREATE TABLE IF NOT EXISTS `dom_suppliers` (
  `person_id` int(10) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `deleted` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`person_id`),
  UNIQUE KEY `account_number` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `dom_suppliers`
--

INSERT INTO `dom_suppliers` (`person_id`, `company_name`, `account_number`, `deleted`) VALUES
(53413138, 'Suplier', NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_tipopago`
--

CREATE TABLE IF NOT EXISTS `dom_tipopago` (
  `tipopago` varchar(255) NOT NULL,
  `tipopago_id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`tipopago_id`),
  KEY `tipopago_id` (`tipopago_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Volcado de datos para la tabla `dom_tipopago`
--

INSERT INTO `dom_tipopago` (`tipopago`, `tipopago_id`) VALUES
('Efectivo', 1),
('QR', 2),
('Transferencia', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dom_toquotelogs`
--

CREATE TABLE IF NOT EXISTS `dom_toquotelogs` (
  `toquotelogs_id` int(10) NOT NULL AUTO_INCREMENT,
  `person_id` varchar(255) NOT NULL,
  `cotizo` varchar(255) NOT NULL,
  `costo` int(25) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`toquotelogs_id`),
  KEY `toquotelogs_id` (`toquotelogs_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

--
-- Volcado de datos para la tabla `dom_toquotelogs`
--

INSERT INTO `dom_toquotelogs` (`toquotelogs_id`, `person_id`, `cotizo`, `costo`, `fecha`) VALUES
(1, '1', '', 0, '2025-09-01 16:39:11'),
(2, '1', '', 0, '2025-09-01 16:39:39'),
(3, '1', 'Hemograma,Eritrosedimentacion', 75, '2025-09-01 17:00:26'),
(4, '1', 'Hemograma,Eritrosedimentacion,Plaquetas,Indices Hematimetricos', 125, '2025-09-01 17:00:54'),
(5, '1', '', 0, '2025-09-17 16:19:22'),
(6, '1', 'Hemograma,Amilasa,Acido Urico,Bilirrubina (D,I,T),Creatinina,Glicemia,Urea,Trigliceridos,Colesterol,C-HDL,C-LDL,VLDL,GPT/ALT,GOT/AST,F. Alcalina,GGT,Reaccion de Widal,H. Pilory IgG,H. Pilory IgM,Parcial de Orina,Coproparasitologico', 1120, '2025-10-07 12:22:43'),
(7, '1', 'Hemograma,PCR (Latex),Reaccion de Widal,HAV IgM,HAV IgG', 510, '2025-10-07 15:32:36'),
(8, '1', 'Espermograma', 300, '2025-10-10 15:11:55'),
(9, '1', 'Estradiol,FSH,LH,Progesterona,Testosterona Total', 750, '2025-10-10 15:12:35'),
(10, '1', 'Hemograma,Creatinina,Glicemia,Trigliceridos,Colesterol,PCR (Latex),Latex RA (Latex)', 360, '2025-10-17 16:41:24'),
(11, '1', 'Coombs Indirecto,Tiempo de Protrombina -INR', 140, '2025-10-23 11:55:45'),
(12, '1', 'Coproparasitologico,Ag. H. Pylori,Pediatrico,De Granulacion de Basofilos (Sangre)', 1680, '2025-10-23 11:58:44'),
(13, '1', 'Hierro Serico,Transferrina - TIBC,Ferritina,Glicemia,Tolerancia de Glucosa,Hb. Glicosilada,Trigliceridos,Colesterol,C-HDL,GPT/ALT,GOT/AST,Insulina Basal am', 1110, '2025-10-23 12:57:58'),
(14, '1', 'Hierro Serico,Transferrina - TIBC,Ferritina,Glicemia,Tolerancia de Glucosa,Hb. Glicosilada,Trigliceridos,Colesterol,C-HDL,C-LDL,GPT/ALT,GOT/AST,Insulina Basal am', 1140, '2025-10-23 12:59:12'),
(15, '1', 'Hemograma,Eritrosedimentacion,Plaquetas', 105, '2025-10-29 12:40:04'),
(16, '1', 'Hemograma,Eritrosedimentacion', 75, '2025-10-29 14:17:48'),
(17, '1', 'Hemograma,Eritrosedimentacion,Ferritina,Perfil de Hierro', 525, '2025-10-29 14:18:20');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `dom_customers`
--
ALTER TABLE `dom_customers`
  ADD CONSTRAINT `dom_customers_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `dom_people` (`person_id`);

--
-- Filtros para la tabla `dom_employees`
--
ALTER TABLE `dom_employees`
  ADD CONSTRAINT `dom_employees_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `dom_people` (`person_id`);

--
-- Filtros para la tabla `dom_inventory`
--
ALTER TABLE `dom_inventory`
  ADD CONSTRAINT `dom_inventory_ibfk_1` FOREIGN KEY (`trans_items`) REFERENCES `dom_items` (`item_id`),
  ADD CONSTRAINT `dom_inventory_ibfk_2` FOREIGN KEY (`trans_user`) REFERENCES `dom_employees` (`person_id`);

--
-- Filtros para la tabla `dom_items`
--
ALTER TABLE `dom_items`
  ADD CONSTRAINT `dom_items_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `dom_suppliers` (`person_id`);

--
-- Filtros para la tabla `dom_items_taxes`
--
ALTER TABLE `dom_items_taxes`
  ADD CONSTRAINT `dom_items_taxes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `dom_items` (`item_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `dom_item_kit_items`
--
ALTER TABLE `dom_item_kit_items`
  ADD CONSTRAINT `dom_item_kit_items_ibfk_1` FOREIGN KEY (`item_kit_id`) REFERENCES `dom_item_kits` (`item_kit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dom_item_kit_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `dom_items` (`item_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `dom_permissions`
--
ALTER TABLE `dom_permissions`
  ADD CONSTRAINT `dom_permissions_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `dom_employees` (`person_id`),
  ADD CONSTRAINT `dom_permissions_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `dom_modules` (`module_id`);

--
-- Filtros para la tabla `dom_receivings`
--
ALTER TABLE `dom_receivings`
  ADD CONSTRAINT `dom_receivings_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `dom_employees` (`person_id`),
  ADD CONSTRAINT `dom_receivings_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `dom_suppliers` (`person_id`);

--
-- Filtros para la tabla `dom_receivings_items`
--
ALTER TABLE `dom_receivings_items`
  ADD CONSTRAINT `dom_receivings_items_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `dom_items` (`item_id`),
  ADD CONSTRAINT `dom_receivings_items_ibfk_2` FOREIGN KEY (`receiving_id`) REFERENCES `dom_receivings` (`receiving_id`);

--
-- Filtros para la tabla `dom_resulanalisis`
--
ALTER TABLE `dom_resulanalisis`
  ADD CONSTRAINT `fk_estado_resulanalisis` FOREIGN KEY (`estado_id`) REFERENCES `dom_estado` (`estado_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `dom_sales`
--
ALTER TABLE `dom_sales`
  ADD CONSTRAINT `dom_sales_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `dom_employees` (`person_id`),
  ADD CONSTRAINT `dom_sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `dom_customers` (`person_id`);

--
-- Filtros para la tabla `dom_sales_items`
--
ALTER TABLE `dom_sales_items`
  ADD CONSTRAINT `dom_sales_items_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `dom_items` (`item_id`),
  ADD CONSTRAINT `dom_sales_items_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `dom_sales` (`sale_id`);

--
-- Filtros para la tabla `dom_sales_items_taxes`
--
ALTER TABLE `dom_sales_items_taxes`
  ADD CONSTRAINT `dom_sales_items_taxes_ibfk_1` FOREIGN KEY (`sale_id`, `item_id`, `line`) REFERENCES `dom_sales_items` (`sale_id`, `item_id`, `line`),
  ADD CONSTRAINT `dom_sales_items_taxes_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `dom_items` (`item_id`);

--
-- Filtros para la tabla `dom_sales_payments`
--
ALTER TABLE `dom_sales_payments`
  ADD CONSTRAINT `dom_sales_payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `dom_sales` (`sale_id`);

--
-- Filtros para la tabla `dom_sales_suspended`
--
ALTER TABLE `dom_sales_suspended`
  ADD CONSTRAINT `dom_sales_suspended_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `dom_employees` (`person_id`),
  ADD CONSTRAINT `dom_sales_suspended_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `dom_customers` (`person_id`);

--
-- Filtros para la tabla `dom_sales_suspended_items`
--
ALTER TABLE `dom_sales_suspended_items`
  ADD CONSTRAINT `dom_sales_suspended_items_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `dom_items` (`item_id`),
  ADD CONSTRAINT `dom_sales_suspended_items_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `dom_sales_suspended` (`sale_id`);

--
-- Filtros para la tabla `dom_sales_suspended_items_taxes`
--
ALTER TABLE `dom_sales_suspended_items_taxes`
  ADD CONSTRAINT `dom_sales_suspended_items_taxes_ibfk_1` FOREIGN KEY (`sale_id`, `item_id`, `line`) REFERENCES `dom_sales_suspended_items` (`sale_id`, `item_id`, `line`),
  ADD CONSTRAINT `dom_sales_suspended_items_taxes_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `dom_items` (`item_id`);

--
-- Filtros para la tabla `dom_sales_suspended_payments`
--
ALTER TABLE `dom_sales_suspended_payments`
  ADD CONSTRAINT `dom_sales_suspended_payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `dom_sales_suspended` (`sale_id`);

--
-- Filtros para la tabla `dom_suppliers`
--
ALTER TABLE `dom_suppliers`
  ADD CONSTRAINT `dom_suppliers_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `dom_people` (`person_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
