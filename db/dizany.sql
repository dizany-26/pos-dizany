-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-03-2026 a las 06:01:58
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dizany`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(31, 'ACEITE'),
(43, 'GALLETA'),
(44, 'CERVEZA'),
(45, 'BEBIDAS'),
(46, 'SAL');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ruc` varchar(255) DEFAULT NULL,
  `dni` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `direccion`, `telefono`, `ruc`, `dni`) VALUES
(19, 'DILSER', 'PACAYZAPA', '958196510', NULL, '76363332'),
(20, 'ANALY', 'PACAYZAPA', '935965841', NULL, '71885485'),
(21, 'VICTOR', 'GOICOCHEA CARRANZA', '936584598', NULL, '27419354'),
(22, 'CELINDA', 'MOYOBAMBA', NULL, NULL, '45950469'),
(23, 'ELENA', 'GOZEN', NULL, NULL, '00829710'),
(24, 'SILVIA', NULL, NULL, NULL, '71885486'),
(25, 'JOSE', 'JLJL', '985456222', NULL, '98762315'),
(26, 'DEYVIS', NULL, NULL, NULL, '71609740'),
(27, 'DIANA CORDOBA', 'KKK', NULL, NULL, '71558985'),
(28, 'EVER', NULL, NULL, NULL, '20152025'),
(29, 'FABIAN', NULL, NULL, NULL, '92946268'),
(30, 'IZAN', NULL, NULL, NULL, '96358488'),
(31, 'SILVESTRE', 'DDDD', NULL, NULL, '27266552'),
(32, 'ELICIA', 'KKKKK', NULL, NULL, '85848584'),
(33, 'GERMAN', NULL, NULL, NULL, '45254525'),
(34, 'NEVADA ENTRETENIMIENTOS S.A.C.', 'JR. PARRA DEL RIEGO NRO. 367 DPTO. 603', NULL, '20530811001', NULL),
(35, 'CMAC PIURA S.A.C.', 'JR. AYACUCHO NRO. 353  CENTRO PIURA.', NULL, '20113604248', NULL),
(36, 'PACO RAUL VARGAS ROJAS', 'No disponible', NULL, NULL, '00821525'),
(37, 'GRUPO DELTRON S.A.', 'CAL. RAUL REBAGLIATI NRO. 170 URB. SANTA CATALINA', NULL, '20212331377', NULL),
(38, 'HOMECENTERS PERUANOS S.A.', 'AV. AVIACION NRO. 2405', NULL, '20536557858', NULL),
(39, 'HIPERMERCADOS TOTTUS S.A', 'AV. ANGAMOS ESTE NRO. 1805 INT. P10', NULL, '20508565934', NULL),
(40, 'LUDITH. FARRO SILVA', 'No disponible', NULL, NULL, '76560561'),
(41, 'INTENTIONS ENGINEERING LEADERSHIP SERVICES SOCIEDAD ANONIMA CERRADA- INTENTIONS ENGINEERING LEADERS', 'AV. SAN MARTIN NRO. 625 DPTO. 701', NULL, '20606422793', NULL),
(42, 'DEYVIS GOICOCHEA VASQUEZ', 'No disponible', NULL, NULL, '70166160');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `nombre_empresa` varchar(100) DEFAULT NULL,
  `ruc` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `moneda` varchar(10) DEFAULT NULL,
  `igv` decimal(5,2) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `tema` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `nombre_empresa`, `ruc`, `logo`, `moneda`, `igv`, `direccion`, `telefono`, `correo`, `tema`) VALUES
(1, 'DIZANY', '10763633328', 'uploads/logos/1769127060_DA.png', 'S/', 0.00, 'AV. MARGINAL - PACAYZAPA', '958196510', 'admin@dizany.com', 'claro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_catalogo`
--

CREATE TABLE `configuracion_catalogo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre_empresa` varchar(150) NOT NULL,
  `rubro` varchar(150) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `color_principal` varchar(20) DEFAULT NULL,
  `mensaje_bienvenida` text DEFAULT NULL,
  `texto_boton_whatsapp` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_catalogo`
--

INSERT INTO `configuracion_catalogo` (`id`, `nombre_empresa`, `rubro`, `logo`, `telefono`, `correo`, `direccion`, `color_principal`, `mensaje_bienvenida`, `texto_boton_whatsapp`, `created_at`, `updated_at`) VALUES
(1, 'DIZANY', 'LICORERIA', '1771376477.png', '958196510', 'admin@dizany.com', 'AV. MARGINAL KM105', NULL, 'Bienvenidos a su tienda DIZANY', 'Comprar por WhatsApp', NULL, '2026-02-18 01:01:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_lote_ventas`
--

CREATE TABLE `detalle_lote_ventas` (
  `id` int(11) NOT NULL,
  `detalle_venta_id` int(11) NOT NULL,
  `lote_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `precio_lote` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_lote_ventas`
--

INSERT INTO `detalle_lote_ventas` (`id`, `detalle_venta_id`, `lote_id`, `cantidad`, `fecha_vencimiento`, `precio_lote`, `created_at`, `updated_at`) VALUES
(18, 91, 60, 20, '2026-12-30', 30.00, '2026-02-10 02:28:52', '2026-02-10 02:28:52'),
(19, 92, 62, 20, '2026-02-19', 35.00, '2026-02-10 02:42:01', '2026-02-10 02:42:01'),
(20, 93, 63, 15, '2026-09-30', 36.00, '2026-02-10 02:42:20', '2026-02-10 02:42:20'),
(21, 94, 59, 12, '2026-08-26', 2.00, '2026-02-10 02:43:15', '2026-02-10 02:43:15'),
(22, 95, 60, 38, '2026-12-30', 3.00, '2026-02-10 02:43:15', '2026-02-10 02:43:15'),
(23, 96, 60, 2, '2026-12-30', 3.00, '2026-02-10 04:20:43', '2026-02-10 04:20:43'),
(24, 97, 59, 2, '2026-08-26', 2.00, '2026-02-13 03:29:05', '2026-02-13 03:29:05'),
(25, 98, 64, 4, '2026-02-26', 15.00, '2026-02-13 03:32:53', '2026-02-13 03:32:53'),
(26, 99, 64, 3, '2026-02-26', 15.00, '2026-02-13 03:42:35', '2026-02-13 03:42:35'),
(27, 100, 62, 2, '2026-02-19', 35.00, '2026-02-13 03:48:37', '2026-02-13 03:48:37'),
(28, 101, 64, 1, '2026-02-26', 15.00, '2026-02-13 03:48:37', '2026-02-13 03:48:37'),
(29, 102, 62, 1, '2026-02-19', 35.00, '2026-02-13 04:05:11', '2026-02-13 04:05:11'),
(30, 103, 66, 1, '2026-03-15', 2.50, '2026-02-28 01:09:00', '2026-02-28 01:09:00'),
(31, 104, 66, 1, '2026-03-15', 2.50, '2026-02-28 01:35:09', '2026-02-28 01:35:09'),
(32, 105, 65, 1, '2026-07-29', 1.50, '2026-02-28 01:35:41', '2026-02-28 01:35:41'),
(33, 106, 66, 3, '2026-03-15', 2.50, '2026-02-28 01:35:41', '2026-02-28 01:35:41'),
(34, 107, 66, 1, '2026-03-15', 2.50, '2026-03-07 01:34:46', '2026-03-07 01:34:46'),
(35, 108, 65, 1, '2026-07-29', 1.50, '2026-03-07 01:34:46', '2026-03-07 01:34:46'),
(36, 109, 66, 1, '2026-03-15', 2.50, '2026-03-07 01:35:24', '2026-03-07 01:35:24'),
(37, 110, 65, 1, '2026-07-29', 1.50, '2026-03-07 01:35:45', '2026-03-07 01:35:45'),
(38, 111, 66, 1, '2026-03-15', 2.50, '2026-03-07 01:35:45', '2026-03-07 01:35:45'),
(39, 112, 67, 1, '2026-09-30', 25.00, '2026-03-07 03:19:09', '2026-03-07 03:19:09'),
(40, 113, 65, 1, '2026-07-29', 1.50, '2026-03-07 03:19:09', '2026-03-07 03:19:09'),
(41, 114, 66, 1, '2026-03-15', 2.50, '2026-03-07 03:19:09', '2026-03-07 03:19:09'),
(42, 115, 68, 1, '2026-03-25', 1.50, '2026-03-07 03:19:09', '2026-03-07 03:19:09'),
(43, 116, 65, 1, '2026-07-29', 1.50, '2026-03-07 03:29:54', '2026-03-07 03:29:54'),
(44, 117, 66, 1, '2026-03-15', 2.50, '2026-03-07 03:29:54', '2026-03-07 03:29:54'),
(45, 118, 67, 1, '2026-09-30', 25.00, '2026-03-07 03:29:54', '2026-03-07 03:29:54'),
(46, 119, 66, 1, '2026-03-15', 2.50, '2026-03-07 03:53:28', '2026-03-07 03:53:28'),
(47, 120, 65, 1, '2026-07-29', 1.50, '2026-03-07 03:53:28', '2026-03-07 03:53:28'),
(48, 121, 67, 1, '2026-09-30', 25.00, '2026-03-07 03:53:28', '2026-03-07 03:53:28'),
(49, 122, 68, 1, '2026-03-25', 1.50, '2026-03-07 03:53:28', '2026-03-07 03:53:28'),
(50, 123, 66, 1, '2026-03-15', 2.50, '2026-03-07 03:59:21', '2026-03-07 03:59:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `presentacion` enum('unidad','paquete','caja') NOT NULL DEFAULT 'unidad',
  `cantidad` int(11) NOT NULL,
  `unidades_afectadas` int(11) NOT NULL DEFAULT 1,
  `precio_presentacion` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ganancia` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `venta_id`, `producto_id`, `presentacion`, `cantidad`, `unidades_afectadas`, `precio_presentacion`, `precio_unitario`, `subtotal`, `ganancia`, `activo`) VALUES
(91, 56, 40, 'paquete', 1, 20, 30.00, 1.50, 30.00, 0.00, 1),
(92, 57, 37, 'unidad', 1, 20, 35.00, 1.75, 35.00, -265.00, 1),
(93, 58, 37, 'unidad', 1, 15, 36.00, 2.40, 36.00, -189.00, 1),
(94, 59, 40, 'unidad', 1, 12, 2.00, 0.17, 2.00, -10.00, 1),
(95, 59, 40, 'unidad', 1, 38, 3.00, 0.08, 3.00, -54.00, 1),
(96, 62, 40, 'unidad', 1, 2, 3.00, 1.50, 3.00, 0.00, 1),
(97, 64, 40, 'unidad', 1, 2, 2.00, 1.00, 2.00, 0.00, 1),
(98, 66, 39, 'unidad', 4, 4, 15.00, 3.75, 60.00, 20.00, 1),
(99, 71, 39, 'unidad', 3, 3, 15.00, 5.00, 45.00, 15.00, 1),
(100, 74, 37, 'unidad', 2, 2, 35.00, 17.50, 70.00, 40.00, 1),
(101, 74, 39, 'unidad', 1, 1, 15.00, 15.00, 15.00, 5.00, 1),
(102, 82, 37, 'unidad', 1, 1, 35.00, 35.00, 35.00, 20.00, 1),
(103, 84, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1),
(104, 85, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1),
(105, 86, 38, 'unidad', 1, 1, 1.50, 1.50, 1.50, 0.50, 1),
(106, 86, 39, 'unidad', 3, 3, 2.50, 0.83, 7.50, 4.50, 1),
(107, 87, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1),
(108, 87, 38, 'unidad', 1, 1, 1.50, 1.50, 1.50, 0.50, 1),
(109, 88, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1),
(110, 89, 38, 'unidad', 1, 1, 1.50, 1.50, 1.50, 0.50, 1),
(111, 89, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1),
(112, 90, 37, 'unidad', 1, 1, 25.00, 25.00, 25.00, 15.00, 1),
(113, 90, 38, 'unidad', 1, 1, 1.50, 1.50, 1.50, 0.50, 1),
(114, 90, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1),
(115, 90, 40, 'unidad', 1, 1, 1.50, 1.50, 1.50, 0.50, 1),
(116, 91, 38, 'unidad', 1, 1, 1.50, 1.50, 1.50, 0.50, 1),
(117, 91, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1),
(118, 91, 37, 'unidad', 1, 1, 25.00, 25.00, 25.00, 15.00, 1),
(119, 92, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1),
(120, 92, 38, 'unidad', 1, 1, 1.50, 1.50, 1.50, 0.50, 1),
(121, 92, 37, 'unidad', 1, 1, 25.00, 25.00, 25.00, 15.00, 1),
(122, 92, 40, 'unidad', 1, 1, 1.50, 1.50, 1.50, 0.50, 1),
(123, 93, 39, 'unidad', 1, 1, 2.50, 2.50, 2.50, 1.50, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `numero_factura` varchar(20) NOT NULL,
  `fecha_emision` date NOT NULL,
  `ruc_emisor` varchar(11) NOT NULL,
  `razon_social_emisor` varchar(100) NOT NULL,
  `direccion_emisor` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos`
--

CREATE TABLE `gastos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `metodo_pago` varchar(50) DEFAULT 'Efectivo',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estado` enum('activo','anulado') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gastos`
--

INSERT INTO `gastos` (`id`, `usuario_id`, `descripcion`, `monto`, `fecha`, `metodo_pago`, `created_at`, `updated_at`, `estado`) VALUES
(17, 4, 'pago de luz', 56.00, '2026-01-09 20:41:00', 'efectivo', '2026-01-09 20:41:12', '2026-01-09 20:44:43', 'anulado'),
(18, 4, 'pago de luz', 15.00, '2026-02-27 21:48:00', 'yape', '2026-02-27 21:49:13', '2026-02-27 21:49:13', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lotes`
--

CREATE TABLE `lotes` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `numero_lote` int(11) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `codigo_comprobante` varchar(100) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `stock_inicial` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_unidad` decimal(10,3) DEFAULT NULL,
  `precio_paquete` decimal(10,3) DEFAULT NULL,
  `precio_caja` decimal(10,3) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `lotes`
--

INSERT INTO `lotes` (`id`, `producto_id`, `numero_lote`, `proveedor_id`, `codigo_comprobante`, `fecha_ingreso`, `fecha_vencimiento`, `stock_inicial`, `stock_actual`, `precio_compra`, `precio_unidad`, `precio_paquete`, `precio_caja`, `activo`, `created_at`, `updated_at`) VALUES
(59, 40, 1, NULL, 'E-001', '2026-02-09', '2026-08-26', 12, 0, 1.00, 2.000, 24.000, NULL, 1, '2026-02-10 02:19:16', '2026-02-13 03:29:05'),
(60, 40, 2, NULL, 'E-002', '2026-02-09', '2026-12-30', 60, 0, 1.50, 3.000, 30.000, NULL, 1, '2026-02-10 02:20:49', '2026-02-10 04:20:43'),
(62, 37, 1, NULL, 'e-2b', '2026-02-09', '2026-02-19', 20, 0, 15.00, 35.000, NULL, NULL, 1, '2026-02-10 02:33:38', '2026-02-13 04:05:11'),
(63, 37, 2, NULL, 'e-2c', '2026-02-09', '2026-09-30', 15, 0, 15.00, 36.000, NULL, NULL, 1, '2026-02-10 02:34:50', '2026-02-10 02:42:20'),
(64, 39, 1, NULL, 'E-001', '2026-02-12', '2026-02-26', 5, 0, 10.00, 15.000, NULL, NULL, 1, '2026-02-13 03:31:32', '2026-02-13 03:48:37'),
(65, 38, 1, NULL, 'e-2c0', '2026-02-12', '2026-07-29', 50, 44, 1.00, 1.500, 20.000, 40.000, 1, '2026-02-13 04:37:22', '2026-03-07 03:53:28'),
(66, 39, 2, 3, 'E-002', '2026-02-26', '2026-03-15', 24, 12, 1.00, 2.500, 25.000, NULL, 1, '2026-02-27 03:19:42', '2026-03-07 03:59:21'),
(67, 37, 3, 3, 'E-002', '2026-03-06', '2026-09-30', 20, 17, 10.00, 25.000, NULL, NULL, 1, '2026-03-07 03:17:32', '2026-03-07 03:53:28'),
(68, 40, 3, 4, 'e-2c', '2026-03-06', '2026-03-25', 50, 48, 1.00, 1.500, 28.000, NULL, 1, '2026-03-07 03:18:15', '2026-03-07 03:53:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lote_movimientos`
--

CREATE TABLE `lote_movimientos` (
  `id` int(11) NOT NULL,
  `lote_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` enum('ingreso','venta','ajuste','edicion') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_antes` int(11) NOT NULL,
  `stock_despues` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `lote_movimientos`
--

INSERT INTO `lote_movimientos` (`id`, `lote_id`, `usuario_id`, `tipo`, `cantidad`, `stock_antes`, `stock_despues`, `motivo`, `creado_en`) VALUES
(5, 59, 4, 'ajuste', 2, 0, 2, 'error_registro', '2026-02-10 04:24:57'),
(6, 64, 4, 'ajuste', 3, 1, 4, 'error_registro', '2026-02-13 03:41:39'),
(7, 62, 4, 'ajuste', 2, 0, 2, 'merma', '2026-02-13 03:47:30'),
(8, 62, 4, 'ajuste', 1, 0, 1, 'error_registro', '2026-02-13 04:00:24'),
(9, 65, 4, 'ajuste', 4, 50, 54, 'error_registro', '2026-02-18 02:06:28'),
(10, 65, 4, 'ajuste', 1, 54, 55, 'error_registro', '2026-02-18 02:37:37'),
(11, 65, 4, 'ajuste', 5, 55, 50, 'merma', '2026-02-18 02:40:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `marcas`
--

INSERT INTO `marcas` (`id`, `nombre`, `descripcion`) VALUES
(11, 'BORGOÑA', NULL),
(12, 'SAN MATEO', NULL),
(13, 'PILSEN', NULL),
(14, 'VOLT', NULL),
(15, 'COCA COLA', NULL),
(16, 'GOLDEN', NULL),
(17, 'PRIMOR', NULL),
(18, 'COCHADO', NULL),
(19, 'GLORIA', NULL),
(20, 'MIKASA', NULL),
(21, 'VACA', NULL),
(22, 'SAN LUIS', NULL),
(23, 'SODA', NULL),
(24, 'CRISTAL', NULL),
(25, 'DELMER', NULL),
(26, 'TONDERO', NULL),
(27, 'CAPRI', NULL),
(28, 'SODA V', NULL),
(29, 'VOLTS', NULL),
(30, 'MARINA', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(6, '2025_12_09_190818_add_timestamps_to_productos_table', 1),
(7, '2025_12_19_003109_add_indexes_to_movimientos_table', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

CREATE TABLE `movimientos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `hora` time DEFAULT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL COMMENT 'Ingreso = dinero que entra, Egreso = dinero que sale',
  `subtipo` varchar(50) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` varchar(20) DEFAULT NULL,
  `estado` enum('pagado','pendiente','anulado') NOT NULL DEFAULT 'pagado',
  `referencia_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID relacionado: venta_id, gasto_id, etc',
  `referencia_tipo` varchar(50) DEFAULT NULL COMMENT 'venta, gasto, ajuste, compra',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos`
--

INSERT INTO `movimientos` (`id`, `fecha`, `hora`, `tipo`, `subtipo`, `concepto`, `monto`, `metodo_pago`, `estado`, `referencia_id`, `referencia_tipo`, `created_at`, `updated_at`) VALUES
(157, '2026-03-06', NULL, 'ingreso', 'venta', 'Venta boleta B001-000014', 4.00, 'plin', 'pagado', 87, 'venta', '2026-03-07 01:34:48', '2026-03-07 01:34:48'),
(158, '2026-03-06', NULL, 'ingreso', 'venta', 'Venta pendiente boleta B001-000015', 2.50, 'efectivo', 'pagado', 88, 'venta', '2026-03-07 01:35:26', '2026-03-10 03:42:58'),
(159, '2026-03-06', NULL, 'ingreso', 'venta', 'Adelanto venta boleta B001-000016', 2.00, 'yape', 'pagado', 89, 'venta', '2026-03-07 01:35:47', '2026-03-07 01:35:47'),
(160, '2026-03-06', NULL, 'ingreso', 'venta', 'Saldo venta boleta B001-000016', 2.00, 'credito', 'pendiente', 89, 'venta', '2026-03-07 01:35:47', '2026-03-07 01:35:47'),
(161, '2026-03-06', NULL, 'ingreso', 'venta', 'Venta boleta B001-000017', 30.50, 'yape', 'pagado', 90, 'venta', '2026-03-07 03:19:11', '2026-03-07 03:19:11'),
(162, '2026-03-06', NULL, 'ingreso', 'venta', 'Adelanto venta nota_venta NV01-000001', 20.00, 'yape', 'pagado', 91, 'venta', '2026-03-07 03:29:56', '2026-03-07 03:29:56'),
(163, '2026-03-06', NULL, 'ingreso', 'venta', 'Saldo venta nota_venta NV01-000001', 9.00, 'credito', 'pendiente', 91, 'venta', '2026-03-07 03:29:56', '2026-03-07 03:29:56'),
(164, '2026-03-06', NULL, 'ingreso', 'venta', 'Venta boleta B001-000018', 30.50, 'transferencia', 'pagado', 92, 'venta', '2026-03-07 03:53:30', '2026-03-07 03:53:30'),
(165, '2026-03-06', NULL, 'ingreso', 'venta', 'Venta boleta B001-000019', 2.50, 'transferencia', 'pagado', 93, 'venta', '2026-03-07 03:59:23', '2026-03-07 03:59:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_venta`
--

CREATE TABLE `pagos_venta` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_venta`
--

INSERT INTO `pagos_venta` (`id`, `venta_id`, `usuario_id`, `monto`, `metodo_pago`, `fecha_pago`, `created_at`, `updated_at`) VALUES
(112, 56, 4, 30.00, 'yape', '2026-02-09 21:28:52', '2026-02-10 02:28:52', '2026-02-10 02:28:52'),
(113, 57, 4, 35.00, 'yape', '2026-02-09 21:42:01', '2026-02-10 02:42:01', '2026-02-10 02:42:01'),
(114, 58, 9, 36.00, 'yape', '2026-02-09 21:42:20', '2026-02-10 02:42:20', '2026-02-10 02:42:20'),
(115, 59, 4, 5.00, 'yape', '2026-02-09 21:43:15', '2026-02-10 02:43:15', '2026-02-10 02:43:15'),
(116, 62, 9, 3.00, 'yape', '2026-02-09 23:20:43', '2026-02-10 04:20:43', '2026-02-10 04:20:43'),
(117, 64, 9, 2.00, 'yape', '2026-02-12 22:29:05', '2026-02-13 03:29:05', '2026-02-13 03:29:05'),
(118, 66, 9, 60.00, 'yape', '2026-02-12 22:32:53', '2026-02-13 03:32:53', '2026-02-13 03:32:53'),
(119, 71, 9, 45.00, 'yape', '2026-02-12 22:42:35', '2026-02-13 03:42:35', '2026-02-13 03:42:35'),
(120, 74, 9, 85.00, 'efectivo', '2026-02-12 22:48:37', '2026-02-13 03:48:37', '2026-02-13 03:48:37'),
(121, 82, 9, 35.00, 'transferencia', '2026-02-12 23:05:11', '2026-02-13 04:05:11', '2026-02-13 04:05:11'),
(122, 84, 4, 2.50, 'plin', '2026-02-27 20:09:00', '2026-02-28 01:09:00', '2026-02-28 01:09:00'),
(123, 86, 4, 5.00, 'yape', '2026-02-27 20:35:41', '2026-02-28 01:35:41', '2026-02-28 01:35:41'),
(124, 87, 4, 4.00, 'plin', '2026-03-06 20:34:46', '2026-03-07 01:34:46', '2026-03-07 01:34:46'),
(125, 89, 4, 2.00, 'yape', '2026-03-06 20:35:45', '2026-03-07 01:35:45', '2026-03-07 01:35:45'),
(126, 90, 4, 30.50, 'yape', '2026-03-06 22:19:09', '2026-03-07 03:19:09', '2026-03-07 03:19:09'),
(127, 91, 4, 20.00, 'yape', '2026-03-06 22:29:54', '2026-03-07 03:29:54', '2026-03-07 03:29:54'),
(128, 92, 4, 30.50, 'transferencia', '2026-03-06 22:53:28', '2026-03-07 03:53:28', '2026-03-07 03:53:28'),
(129, 93, 4, 2.50, 'transferencia', '2026-03-06 22:59:21', '2026-03-07 03:59:21', '2026-03-07 03:59:21'),
(130, 88, 4, 2.50, 'efectivo', '2026-03-09 22:42:58', '2026-03-10 03:42:58', '2026-03-10 03:42:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `unidades_por_paquete` int(11) DEFAULT NULL,
  `paquetes_por_caja` int(11) DEFAULT NULL,
  `unidades_por_caja` int(11) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `maneja_vencimiento` tinyint(1) NOT NULL DEFAULT 0,
  `categoria_id` int(11) NOT NULL,
  `marca_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `visible_en_catalogo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo_barras`, `nombre`, `slug`, `descripcion`, `unidades_por_paquete`, `paquetes_por_caja`, `unidades_por_caja`, `ubicacion`, `imagen`, `maneja_vencimiento`, `categoria_id`, `marca_id`, `activo`, `visible_en_catalogo`, `created_at`, `updated_at`) VALUES
(37, '0000000001', 'TONDERO', 'tondero', '20LT', NULL, NULL, NULL, 'P1', 'tondero-1768874193.jpeg', 1, 31, 26, 1, 1, '2026-01-20 01:56:33', '2026-01-22 02:39:14'),
(38, '0000000002', 'SODA', 'soda', 'saladitas', 6, 10, NULL, 'p3', 'soda-1769050739.webp', 1, 43, 28, 1, 1, '2026-01-22 01:06:02', '2026-01-22 02:58:59'),
(39, '0000000003', 'SPORADE', 'sporade', 'ENERGISANTE', 12, NULL, NULL, 'p1', 'sporade-1769045970.webp', 1, 45, 21, 1, 1, '2026-01-22 01:39:30', '2026-02-26 02:59:27'),
(40, '0000000004', 'SAL YODADA', 'sal', 'SALADA 6', 20, 5, NULL, 'p3', 'sal-1769046562.jpeg', 1, 46, 30, 1, 1, '2026-01-22 01:49:22', '2026-02-27 01:52:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_documento` enum('RUC','DNI','OTRO') NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `numero_documento` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `tipo_documento`, `contacto`, `telefono`, `email`, `direccion`, `estado`, `numero_documento`, `created_at`, `updated_at`) VALUES
(3, 'nevada', 'RUC', NULL, '999999999', NULL, 'moyo', 1, '20121025101', '2026-02-18 03:27:13', '2026-02-25 02:44:39'),
(4, 'recreativos fargo sac', 'RUC', NULL, '958196510', NULL, NULL, 1, '20620368535', '2026-02-18 03:43:26', '2026-02-25 02:44:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Empleado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `clave` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `email`, `clave`, `rol_id`, `created_at`, `updated_at`) VALUES
(4, 'Dilser', 'admin', 'dilser95@gmail.com', '$2y$12$zMKx97A4EIhk68ImMCWQXeMQnHCXmL1MiT3dmMoVqm.0JwoyLtq8K', 1, '2025-06-12 23:36:29', '2025-06-12 23:36:29'),
(9, 'any', 'any25', 'analy@gmail.com', '$2y$12$wUYveWN3vGhhOQRw4d342O43K2CFrMWnEgtpJSgtP6c/b45uLUwTS', 2, NULL, NULL),
(10, 'Deyvis', 'deyvis26', 'deyvis@gmail.com', '$2y$12$SW6HpI9BvESVYJIZua6xwOtH1SIAExZAK1eSLgjKb3WKLz3TTulOe', 2, NULL, NULL),
(12, 'SODA', 'soda', 'soda95@gmail.com', '$2y$12$funNOhP3s2Q9GKlNsn/rq.IvSA/6Sb.st2HBDCWWFgHNWUyY2IqxC', 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `tipo_comprobante` varchar(255) DEFAULT NULL,
  `serie` varchar(10) DEFAULT NULL,
  `correlativo` int(11) DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(20) DEFAULT 'Pagada',
  `estado_sunat` varchar(20) DEFAULT 'pendiente',
  `hash` varchar(255) DEFAULT NULL,
  `xml_url` text DEFAULT NULL,
  `pdf_url` text DEFAULT NULL,
  `cdr_url` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `op_gravadas` decimal(10,2) DEFAULT 0.00,
  `igv` decimal(10,2) DEFAULT 0.00,
  `saldo` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `cliente_id`, `usuario_id`, `fecha`, `tipo_comprobante`, `serie`, `correlativo`, `metodo_pago`, `total`, `estado`, `estado_sunat`, `hash`, `xml_url`, `pdf_url`, `cdr_url`, `activo`, `op_gravadas`, `igv`, `saldo`) VALUES
(56, 34, 4, '2026-02-09 21:28:52', 'boleta', 'B001', 1, 'yape', 30.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 30.00, 0.00, 0.00),
(57, 20, 4, '2026-02-09 21:42:00', 'boleta', 'B001', 2, 'yape', 35.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 35.00, 0.00, 0.00),
(58, 19, 9, '2026-02-09 21:42:21', 'boleta', 'B001', 3, 'yape', 36.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 36.00, 0.00, 0.00),
(59, 20, 4, '2026-02-09 21:43:14', 'boleta', 'B001', 4, 'yape', 5.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 5.00, 0.00, 0.00),
(62, 19, 9, '2026-02-09 23:20:44', 'boleta', 'B001', 5, 'yape', 3.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 3.00, 0.00, 0.00),
(64, 23, 9, '2026-02-12 22:29:05', 'boleta', 'B001', 6, 'yape', 2.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 2.00, 0.00, 0.00),
(66, 20, 9, '2026-02-12 22:32:52', 'boleta', 'B001', 7, 'yape', 60.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 60.00, 0.00, 0.00),
(71, 19, 9, '2026-02-12 22:42:35', 'boleta', 'B001', 8, 'yape', 45.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 45.00, 0.00, 0.00),
(74, 19, 9, '2026-02-12 22:48:36', 'boleta', 'B001', 9, 'efectivo', 85.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 85.00, 0.00, 0.00),
(82, 20, 9, '2026-02-12 23:05:11', 'boleta', 'B001', 10, 'transferencia', 35.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 35.00, 0.00, 0.00),
(84, 41, 4, '2026-02-27 20:08:59', 'boleta', 'B001', 11, 'plin', 2.50, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 2.50, 0.00, 0.00),
(85, 20, 4, '2026-02-27 20:35:08', 'boleta', 'B001', 12, NULL, 2.50, 'pendiente', 'pendiente', NULL, NULL, NULL, NULL, 1, 2.50, 0.00, 2.50),
(86, 34, 4, '2026-02-27 20:35:40', 'boleta', 'B001', 13, 'yape', 9.00, 'credito', 'pendiente', NULL, NULL, NULL, NULL, 1, 9.00, 0.00, 4.00),
(87, 20, 4, '2026-03-06 20:34:45', 'boleta', 'B001', 14, 'plin', 4.00, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 4.00, 0.00, 0.00),
(88, 34, 4, '2026-03-06 20:35:23', 'boleta', 'B001', 15, 'efectivo', 2.50, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 2.50, 0.00, 0.00),
(89, 19, 4, '2026-03-06 20:35:44', 'boleta', 'B001', 16, 'yape', 4.00, 'credito', 'pendiente', NULL, NULL, NULL, NULL, 1, 4.00, 0.00, 2.00),
(90, 22, 4, '2026-03-06 22:19:08', 'boleta', 'B001', 17, 'yape', 30.50, 'pagado', 'pendiente', NULL, NULL, NULL, NULL, 1, 30.50, 0.00, 0.00),
(91, 42, 4, '2026-03-06 22:29:53', 'nota_venta', 'NV01', 1, 'yape', 29.00, 'credito', 'pendiente', NULL, NULL, NULL, NULL, 1, 29.00, 0.00, 9.00),
(92, 35, 4, '2026-03-06 22:53:26', 'boleta', 'B001', 18, 'transferencia', 30.50, 'pagado', 'pendiente', NULL, NULL, 'http://localhost:8000/comprobantes/B001-000018.pdf', NULL, 1, 30.50, 0.00, 0.00),
(93, 22, 4, '2026-03-06 22:59:20', 'boleta', 'B001', 19, 'transferencia', 2.50, 'pagado', 'pendiente', NULL, NULL, 'http://localhost:8000/comprobantes/B001-000019.pdf', NULL, 1, 2.50, 0.00, 0.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ruc` (`ruc`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion_catalogo`
--
ALTER TABLE `configuracion_catalogo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalle_lote_ventas`
--
ALTER TABLE `detalle_lote_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_detalle_venta` (`detalle_venta_id`),
  ADD KEY `idx_lote` (`lote_id`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD KEY `venta_id` (`venta_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_producto_lote` (`producto_id`,`numero_lote`),
  ADD KEY `idx_lotes_fifo` (`producto_id`,`activo`,`stock_actual`,`fecha_ingreso`);

--
-- Indices de la tabla `lote_movimientos`
--
ALTER TABLE `lote_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lote_mov_lote` (`lote_id`),
  ADD KEY `fk_lote_mov_usuario` (`usuario_id`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_movimientos_fecha` (`fecha`),
  ADD KEY `idx_movimientos_tipo_estado` (`tipo`,`estado`),
  ADD KEY `idx_movimientos_metodo_pago` (`metodo_pago`);

--
-- Indices de la tabla `pagos_venta`
--
ALTER TABLE `pagos_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pagos_venta_venta` (`venta_id`),
  ADD KEY `idx_pagos_venta_usuario` (`usuario_id`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_barras` (`codigo_barras`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `fk_marca_producto` (`marca_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `configuracion_catalogo`
--
ALTER TABLE `configuracion_catalogo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalle_lote_ventas`
--
ALTER TABLE `detalle_lote_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gastos`
--
ALTER TABLE `gastos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `lotes`
--
ALTER TABLE `lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT de la tabla `lote_movimientos`
--
ALTER TABLE `lote_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT de la tabla `pagos_venta`
--
ALTER TABLE `pagos_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_lote_ventas`
--
ALTER TABLE `detalle_lote_ventas`
  ADD CONSTRAINT `fk_dlv_detalle_venta` FOREIGN KEY (`detalle_venta_id`) REFERENCES `detalle_ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dlv_lote` FOREIGN KEY (`lote_id`) REFERENCES `lotes` (`id`);

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`);

--
-- Filtros para la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD CONSTRAINT `fk_gastos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD CONSTRAINT `fk_lotes_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lote_movimientos`
--
ALTER TABLE `lote_movimientos`
  ADD CONSTRAINT `fk_lote_mov_lote` FOREIGN KEY (`lote_id`) REFERENCES `lotes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lote_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pagos_venta`
--
ALTER TABLE `pagos_venta`
  ADD CONSTRAINT `fk_pagos_venta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_pagos_venta_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_marca_producto` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`),
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
