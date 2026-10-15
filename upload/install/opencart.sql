-- --------------------------------------------------------
--
-- Database: `opencart`
--

-- --------------------------------------------------------
--
-- Table structure for table `oc_address`
--

DROP TABLE IF EXISTS `oc_address`;
CREATE TABLE `oc_address` (
  `address_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `firstname` varchar(32) NOT NULL,
  `lastname` varchar(32) NOT NULL,
  `company` varchar(40) NOT NULL,
  `address_1` varchar(128) NOT NULL,
  `address_2` varchar(128) NOT NULL,
  `city` varchar(128) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `country_id` int(11) NOT NULL DEFAULT '0',
  `zone_id` int(11) NOT NULL DEFAULT '0',
  `custom_field` text NOT NULL,
  PRIMARY KEY (`address_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
--
-- Table structure for table `oc_api`
--

DROP TABLE IF EXISTS `oc_api`;
CREATE TABLE `oc_api` (
  `api_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `key` text NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`api_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_api_ip`
--

DROP TABLE IF EXISTS `oc_api_ip`;
CREATE TABLE `oc_api_ip` (
  `api_ip_id` int(11) NOT NULL AUTO_INCREMENT,
  `api_id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  PRIMARY KEY (`api_ip_id`),
  KEY `api_id_ip` (`api_id`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_api_session`
--

DROP TABLE IF EXISTS `oc_api_session`;
CREATE TABLE `oc_api_session` (
  `api_session_id` int(11) NOT NULL AUTO_INCREMENT,
  `api_id` int(11) NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`api_session_id`),
  KEY `session_id` (`session_id`),
  KEY `date_modified` (`date_modified`),
  KEY `api_id_ip` (`api_id`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_attribute`
--

DROP TABLE IF EXISTS `oc_attribute`;
CREATE TABLE `oc_attribute` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_group_id` int(11) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_attribute`
--

INSERT INTO `oc_attribute` (`attribute_id`, `attribute_group_id`, `sort_order`) VALUES
(1, 6, 1),
(2, 6, 5),
(3, 6, 3),
(4, 3, 1),
(5, 3, 2),
(6, 3, 3),
(7, 3, 4),
(8, 3, 5),
(9, 3, 6),
(10, 3, 7),
(11, 3, 8);

-- --------------------------------------------------------
--
-- Table structure for table `oc_attribute_description`
--

DROP TABLE IF EXISTS `oc_attribute_description`;
CREATE TABLE `oc_attribute_description` (
  `attribute_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`attribute_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_attribute_description`
--

INSERT INTO `oc_attribute_description` (`attribute_id`, `language_id`, `name`) VALUES
(1, 1, 'Опис'),
(2, 1, 'Кількість ядер'),
(4, 1, 'Дисплей'),
(5, 1, 'Інтерфейс'),
(6, 1, 'Акумулятор'),
(7, 1, 'Камера'),
(8, 1, 'Колір'),
(9, 1, 'Комплектація'),
(10, 1, 'Підключення'),
(11, 1, 'Гарантія'),
(3, 1, 'Тактова частота'),
(3, 2, 'Clockspeed'),
(1, 2, 'Description'),
(2, 2, 'No. of Cores'),
(4, 2, 'Display'),
(5, 2, 'Interface'),
(6, 2, 'Battery'),
(7, 2, 'Camera'),
(8, 2, 'Color'),
(9, 2, 'Package'),
(10, 2, 'Connectivity'),
(11, 2, 'Warranty');

-- --------------------------------------------------------
--
-- Table structure for table `oc_attribute_group`
--

DROP TABLE IF EXISTS `oc_attribute_group`;
CREATE TABLE `oc_attribute_group` (
  `attribute_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`attribute_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_attribute_group`
--

INSERT INTO `oc_attribute_group` (`attribute_group_id`, `sort_order`) VALUES
(3, 2),
(4, 1),
(5, 3),
(6, 4);

-- --------------------------------------------------------
--
-- Table structure for table `oc_attribute_group_description`
--

DROP TABLE IF EXISTS `oc_attribute_group_description`;
CREATE TABLE `oc_attribute_group_description` (
  `attribute_group_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`attribute_group_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_attribute_group_description`
--

INSERT INTO `oc_attribute_group_description` (`attribute_group_id`, `language_id`, `name`) VALUES
(3, 1, 'Пам''ять'),
(4, 1, 'Технічні характеристики'),
(5, 1, 'Материнська плата'),
(6, 1, 'Процесор'),
(3, 2, 'Memory'),
(5, 2, 'Motherboard'),
(6, 2, 'Processor'),
(4, 2, 'Technical');

-- --------------------------------------------------------
--
-- Table structure for table `oc_banner`
--

DROP TABLE IF EXISTS `oc_banner`;
CREATE TABLE `oc_banner` (
  `banner_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`banner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_banner`
--

INSERT INTO `oc_banner` (`banner_id`, `name`, `status`) VALUES
(6, 'Товари HP', 1),
(7, 'Слайдшоу головної сторінки', 1),
(8, 'Виробники', 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_banner_image`
--

DROP TABLE IF EXISTS `oc_banner_image`;
CREATE TABLE `oc_banner_image` (
  `banner_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `banner_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(64) NOT NULL,
  `link` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`banner_image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_banner_image`
--

INSERT INTO `oc_banner_image` (`banner_image_id`, `banner_id`, `language_id`, `title`, `link`, `image`, `sort_order`) VALUES
(100, 7, 1, 'MacBookAir', '', 'catalog/demo/banners/MacBookAir.webp', 1),
(103, 6, 1, 'Банер HP', 'index.php?route=product/manufacturer/info&amp;manufacturer_id=7', 'catalog/demo/compaq_presario.webp', 0),
(113, 8, 1, 'Disney', '', 'catalog/demo/manufacturer/disney.webp', 0),
(112, 8, 1, 'Dell', '', 'catalog/demo/manufacturer/dell.webp', 0),
(111, 8, 1, 'Harley Davidson', '', 'catalog/demo/manufacturer/harley.webp', 0),
(110, 8, 1, 'Canon', '', 'catalog/demo/manufacturer/canon.webp', 0),
(109, 8, 1, 'Burger King', '', 'catalog/demo/manufacturer/burgerking.webp', 0),
(108, 8, 1, 'Coca Cola', '', 'catalog/demo/manufacturer/cocacola.webp', 0),
(107, 8, 1, 'Sony', '', 'catalog/demo/manufacturer/sony.webp', 0),
(99, 7, 1, 'iPhone 6', 'index.php?route=product/product&amp;path=57&amp;product_id=49', 'catalog/demo/banners/iPhone6.webp', 0),
(127, 7, 1, 'Samsung', 'index.php?route=product/manufacturer/info&amp;manufacturer_id=12', 'catalog/demo/banners/Samsung.webp', 2),
(128, 7, 1, 'Hewlett-Packard', 'index.php?route=product/manufacturer/info&amp;manufacturer_id=7', 'catalog/demo/banners/HewlettPackard.webp', 3),
(106, 8, 1, 'RedBull', '', 'catalog/demo/manufacturer/redbull.webp', 0),
(105, 8, 1, 'NFL', '', 'catalog/demo/manufacturer/nfl.webp', 0),
(101, 7, 2, 'iPhone 6', 'index.php?route=product/product&amp;path=57&amp;product_id=49', 'catalog/demo/banners/iPhone6.webp', 0),
(102, 7, 2, 'MacBookAir', '', 'catalog/demo/banners/MacBookAir.webp', 1),
(129, 7, 2, 'Samsung', 'index.php?route=product/manufacturer/info&amp;manufacturer_id=12', 'catalog/demo/banners/Samsung.webp', 2),
(130, 7, 2, 'Hewlett-Packard', 'index.php?route=product/manufacturer/info&amp;manufacturer_id=7', 'catalog/demo/banners/HewlettPackard.webp', 3),
(104, 6, 2, 'HP Banner', 'index.php?route=product/manufacturer/info&amp;manufacturer_id=7', 'catalog/demo/compaq_presario.webp', 0),
(114, 8, 1, 'Starbucks', '', 'catalog/demo/manufacturer/starbucks.webp', 0),
(115, 8, 1, 'Nintendo', '', 'catalog/demo/manufacturer/nintendo.webp', 0),
(116, 8, 2, 'NFL', '', 'catalog/demo/manufacturer/nfl.webp', 0),
(117, 8, 2, 'RedBull', '', 'catalog/demo/manufacturer/redbull.webp', 0),
(118, 8, 2, 'Sony', '', 'catalog/demo/manufacturer/sony.webp', 0),
(119, 8, 2, 'Coca Cola', '', 'catalog/demo/manufacturer/cocacola.webp', 0),
(120, 8, 2, 'Burger King', '', 'catalog/demo/manufacturer/burgerking.webp', 0),
(121, 8, 2, 'Canon', '', 'catalog/demo/manufacturer/canon.webp', 0),
(122, 8, 2, 'Harley Davidson', '', 'catalog/demo/manufacturer/harley.webp', 0),
(123, 8, 2, 'Dell', '', 'catalog/demo/manufacturer/dell.webp', 0),
(124, 8, 2, 'Disney', '', 'catalog/demo/manufacturer/disney.webp', 0),
(125, 8, 2, 'Starbucks', '', 'catalog/demo/manufacturer/starbucks.webp', 0),
(126, 8, 2, 'Nintendo', '', 'catalog/demo/manufacturer/nintendo.webp', 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_cart`
--

DROP TABLE IF EXISTS `oc_cart`;
CREATE TABLE `oc_cart` (
  `cart_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `api_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `product_id` int(11) NOT NULL,
  `recurring_id` int(11) NOT NULL,
  `option` text NOT NULL,
  `quantity` int(5) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `cart_id` (`api_id`,`customer_id`,`session_id`,`product_id`,`recurring_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_category`
--

DROP TABLE IF EXISTS `oc_category`;
CREATE TABLE `oc_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `top` tinyint(1) NOT NULL,
  `column` int(3) NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `noindex` tinyint(1) NOT NULL DEFAULT '1',
  `google_product_category_id` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_category`
--

INSERT INTO `oc_category` (`category_id`, `image`, `parent_id`, `top`, `column`, `sort_order`, `status`, `date_added`, `date_modified`, `noindex`, `google_product_category_id`) VALUES
(25, 'catalog/demo/apple_cinema_30.webp', 0, 1, 2, 3, 1, '2009-01-31 01:04:25', '2026-09-01 12:00:00', 1, '285'),
(27, 'catalog/demo/imac_1.webp', 20, 0, 0, 2, 1, '2009-01-31 01:55:34', '2026-09-01 12:00:00', 1, '325'),
(20, 'catalog/demo/compaq_presario.webp', 0, 1, 2, 1, 1, '2009-01-05 21:49:43', '2026-09-01 12:00:00', 1, '325'),
(24, 'catalog/demo/iphone_1.webp', 0, 1, 1, 5, 1, '2009-01-20 02:36:26', '2026-09-01 12:00:00', 1, '267'),
(18, 'catalog/demo/hp_2.webp', 0, 1, 0, 2, 1, '2009-01-05 21:49:15', '2026-09-01 12:00:00', 1, '328'),
(17, 'catalog/demo/imac_2.webp', 0, 1, 1, 4, 1, '2009-01-03 21:08:57', '2026-09-01 12:00:00', 1, '313'),
(28, 'catalog/demo/apple_cinema_30.webp', 25, 0, 0, 1, 1, '2009-02-02 13:11:12', '2026-09-01 12:00:00', 1, '305'),
(26, 'catalog/demo/compaq_presario.webp', 20, 0, 0, 1, 1, '2009-01-31 01:55:14', '2026-09-01 12:00:00', 1, '325'),
(29, 'catalog/demo/htc_touch_hd_1.webp', 25, 0, 0, 1, 1, '2009-02-02 13:11:37', '2026-09-01 12:00:00', 1, '304'),
(30, 'catalog/demo/hp_1.webp', 25, 0, 0, 1, 1, '2009-02-02 13:11:59', '2026-09-01 12:00:00', 1, '500106'),
(31, 'catalog/demo/canon_eos_5d_1.webp', 25, 0, 0, 1, 1, '2009-02-03 14:17:24', '2026-09-01 12:00:00', 1, '306'),
(32, 'catalog/demo/iphone_3.webp', 25, 0, 0, 1, 1, '2009-02-03 14:17:34', '2026-09-01 12:00:00', 1, '312'),
(33, 'catalog/demo/canon_eos_5d_1.webp', 0, 1, 1, 6, 1, '2009-02-03 14:17:55', '2026-09-01 12:00:00', 1, '152'),
(34, 'catalog/demo/ipod_touch_4.webp', 0, 1, 5, 7, 1, '2009-02-03 14:18:11', '2026-09-01 12:00:00', 1, '233'),
(35, 'catalog/demo/ipod_nano_1.webp', 28, 0, 0, 0, 1, '2010-09-17 10:06:48', '2026-09-01 12:00:00', 1, '305'),
(36, 'catalog/demo/ipod_nano_2.webp', 28, 0, 0, 0, 1, '2010-09-17 10:07:13', '2026-09-01 12:00:00', 1, '305'),
(37, 'catalog/demo/ipod_nano_3.webp', 34, 0, 0, 0, 1, '2010-09-18 14:03:39', '2026-09-01 12:00:00', 1, ''),
(38, 'catalog/demo/ipod_nano_4.webp', 34, 0, 0, 0, 1, '2010-09-18 14:03:51', '2026-09-01 12:00:00', 1, ''),
(39, 'catalog/demo/ipod_nano_5.webp', 34, 0, 0, 0, 1, '2010-09-18 14:04:17', '2026-09-01 12:00:00', 1, ''),
(40, 'catalog/demo/ipod_shuffle_1.webp', 34, 0, 0, 0, 1, '2010-09-18 14:05:36', '2026-09-01 12:00:00', 1, ''),
(41, 'catalog/demo/ipod_shuffle_2.webp', 34, 0, 0, 0, 1, '2010-09-18 14:05:49', '2026-09-01 12:00:00', 1, ''),
(42, 'catalog/demo/ipod_shuffle_3.webp', 34, 0, 0, 0, 1, '2010-09-18 14:06:34', '2026-09-01 12:00:00', 1, ''),
(43, 'catalog/demo/ipod_shuffle_4.webp', 34, 0, 0, 0, 1, '2010-09-18 14:06:49', '2026-09-01 12:00:00', 1, '222'),
(44, 'catalog/demo/ipod_shuffle_5.webp', 34, 0, 0, 0, 1, '2010-09-21 15:39:21', '2026-09-01 12:00:00', 1, '232'),
(45, 'catalog/demo/sony_vaio_1.webp', 18, 0, 0, 0, 1, '2010-09-24 18:29:16', '2026-09-01 12:00:00', 1, '328'),
(46, 'catalog/demo/macbook_1.webp', 18, 0, 0, 0, 1, '2010-09-24 18:29:31', '2026-09-01 12:00:00', 1, '328'),
(47, 'catalog/demo/ipod_touch_1.webp', 34, 0, 0, 0, 1, '2010-11-07 11:13:16', '2026-09-01 12:00:00', 1, ''),
(48, 'catalog/demo/ipod_touch_2.webp', 34, 0, 0, 0, 1, '2010-11-07 11:13:33', '2026-09-01 12:00:00', 1, ''),
(49, 'catalog/demo/ipod_touch_3.webp', 34, 0, 0, 0, 1, '2010-11-07 11:14:04', '2026-09-01 12:00:00', 1, ''),
(50, 'catalog/demo/ipod_touch_4.webp', 34, 0, 0, 0, 1, '2010-11-07 11:14:23', '2026-09-01 12:00:00', 1, ''),
(51, 'catalog/demo/ipod_touch_5.webp', 34, 0, 0, 0, 1, '2010-11-07 11:14:38', '2026-09-01 12:00:00', 1, ''),
(52, 'catalog/demo/ipod_touch_6.webp', 34, 0, 0, 0, 1, '2010-11-07 11:16:09', '2026-09-01 12:00:00', 1, ''),
(53, 'catalog/demo/ipod_touch_7.webp', 34, 0, 0, 0, 1, '2010-11-07 11:28:53', '2026-09-01 12:00:00', 1, '232'),
(54, 'catalog/demo/ipod_classic_1.webp', 34, 0, 0, 0, 1, '2010-11-07 11:29:16', '2026-09-01 12:00:00', 1, '232'),
(55, 'catalog/demo/ipod_classic_2.webp', 34, 0, 0, 0, 1, '2010-11-08 10:31:32', '2026-09-01 12:00:00', 1, ''),
(56, 'catalog/demo/ipod_classic_3.webp', 34, 0, 0, 0, 1, '2010-11-08 10:31:50', '2026-09-01 12:00:00', 1, ''),
(57, 'catalog/demo/samsung_tab_1.webp', 0, 1, 2, 3, 1, '2011-04-26 08:53:16', '2026-09-01 12:00:00', 1, '4745'),
(58, 'catalog/demo/samsung_tab_2.webp', 57, 0, 0, 0, 1, '2011-05-08 13:44:16', '2026-09-01 12:00:00', 1, '4745');

-- --------------------------------------------------------
--
-- Table structure for table `oc_category_description`
--

DROP TABLE IF EXISTS `oc_category_description`;
CREATE TABLE `oc_category_description` (
  `category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_description` varchar(255) NOT NULL,
  `meta_keyword` varchar(255) NOT NULL,
  `meta_h1` varchar(255) NOT NULL,
  PRIMARY KEY (`category_id`,`language_id`),
  KEY `name` (`name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_category_description`
--

INSERT INTO `oc_category_description` (`category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES
(28, 1, 'Монітори', '&lt;h2 id=&quot;overview&quot;&gt;Монітори&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Компоненти» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Монітори', 'Демонстраційна категорія «Монітори» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Монітори'),
(32, 1, 'Веб-камери', '&lt;h2 id=&quot;overview&quot;&gt;Веб-камери&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Компоненти» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Веб-камери', 'Демонстраційна категорія «Веб-камери» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Веб-камери'),
(31, 1, 'Сканери', '&lt;h2 id=&quot;overview&quot;&gt;Сканери&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Компоненти» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Сканери', 'Демонстраційна категорія «Сканери» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Сканери'),
(30, 1, 'Принтери', '&lt;h2 id=&quot;overview&quot;&gt;Принтери&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Компоненти» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Принтери', 'Демонстраційна категорія «Принтери» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Принтери'),
(29, 1, 'Мишки', '&lt;h2 id=&quot;overview&quot;&gt;Мишки&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Компоненти» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Мишки', 'Демонстраційна категорія «Мишки» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Мишки'),
(27, 1, 'Mac', '&lt;h2 id=&quot;overview&quot;&gt;Mac&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Комп''ютери» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Mac', 'Демонстраційна категорія «Mac» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Mac'),
(26, 1, 'PC', '&lt;h2 id=&quot;overview&quot;&gt;PC&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Комп''ютери» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'PC', 'Демонстраційна категорія «PC» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'PC'),
(17, 1, 'Програмне забезпечення', '&lt;h2 id=&quot;overview&quot;&gt;Програмне забезпечення&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Програмне забезпечення', 'Демонстраційна категорія «Програмне забезпечення» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Програмне забезпечення'),
(25, 1, 'Компоненти', '&lt;h2 id=&quot;overview&quot;&gt;Компоненти&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Компоненти', 'Демонстраційна категорія «Компоненти» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Компоненти'),
(24, 1, 'Телефони та PDA', '&lt;h2 id=&quot;overview&quot;&gt;Телефони та PDA&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Телефони та PDA', 'Демонстраційна категорія «Телефони та PDA» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Телефони та PDA'),
(20, 1, 'Комп''ютери', '&lt;h2 id=&quot;overview&quot;&gt;Комп''ютери&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Комп''ютери', 'Демонстраційна категорія «Комп''ютери» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Комп''ютери'),
(35, 1, 'LED-монітори', '&lt;h2 id=&quot;overview&quot;&gt;LED-монітори&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Монітори» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'LED-монітори', 'Демонстраційна категорія «LED-монітори» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'LED-монітори'),
(36, 1, '4K-монітори', '&lt;h2 id=&quot;overview&quot;&gt;4K-монітори&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Монітори» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', '4K-монітори', 'Демонстраційна категорія «4K-монітори» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', '4K-монітори'),
(37, 1, 'Спортивні MP3-плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Спортивні MP3-плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Спортивні MP3-плеєри', 'Демонстраційна категорія «Спортивні MP3-плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Спортивні MP3-плеєри'),
(38, 1, 'Сенсорні MP3-плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Сенсорні MP3-плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Сенсорні MP3-плеєри', 'Демонстраційна категорія «Сенсорні MP3-плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Сенсорні MP3-плеєри'),
(39, 1, 'Компактні MP3-плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Компактні MP3-плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Компактні MP3-плеєри', 'Демонстраційна категорія «Компактні MP3-плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Компактні MP3-плеєри'),
(40, 1, 'Міні-плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Міні-плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Міні-плеєри', 'Демонстраційна категорія «Міні-плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Міні-плеєри'),
(41, 1, 'Кліп-плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Кліп-плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Кліп-плеєри', 'Демонстраційна категорія «Кліп-плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Кліп-плеєри'),
(42, 1, 'Мультимедійні плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Мультимедійні плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Мультимедійні плеєри', 'Демонстраційна категорія «Мультимедійні плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Мультимедійні плеєри'),
(43, 1, 'Домашнє аудіо', '&lt;h2 id=&quot;overview&quot;&gt;Домашнє аудіо&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Домашнє аудіо', 'Демонстраційна категорія «Домашнє аудіо» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Домашнє аудіо'),
(34, 1, 'MP3-плеєри', '&lt;h2 id=&quot;overview&quot;&gt;MP3-плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'MP3-плеєри', 'Демонстраційна категорія «MP3-плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'MP3-плеєри'),
(18, 1, 'Ноутбуки', '&lt;h2 id=&quot;overview&quot;&gt;Ноутбуки&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Ноутбуки', 'Демонстраційна категорія «Ноутбуки» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Ноутбуки'),
(44, 1, 'Аксесуари для плеєрів', '&lt;h2 id=&quot;overview&quot;&gt;Аксесуари для плеєрів&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Аксесуари для плеєрів', 'Демонстраційна категорія «Аксесуари для плеєрів» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Аксесуари для плеєрів'),
(45, 1, 'Windows', '&lt;h2 id=&quot;overview&quot;&gt;Windows&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Ноутбуки» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Windows', 'Демонстраційна категорія «Windows» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Windows'),
(46, 1, 'Mac', '&lt;h2 id=&quot;overview&quot;&gt;Mac&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Ноутбуки» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Mac', 'Демонстраційна категорія «Mac» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Mac'),
(47, 1, 'Портативні плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Портативні плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Портативні плеєри', 'Демонстраційна категорія «Портативні плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Портативні плеєри'),
(48, 1, 'Класичні плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Класичні плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Класичні плеєри', 'Демонстраційна категорія «Класичні плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Класичні плеєри'),
(49, 1, 'Відеоплеєри', '&lt;h2 id=&quot;overview&quot;&gt;Відеоплеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Відеоплеєри', 'Демонстраційна категорія «Відеоплеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Відеоплеєри'),
(50, 1, 'Дитячі плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Дитячі плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Дитячі плеєри', 'Демонстраційна категорія «Дитячі плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Дитячі плеєри'),
(51, 1, 'Фітнес-плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Фітнес-плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Фітнес-плеєри', 'Демонстраційна категорія «Фітнес-плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Фітнес-плеєри'),
(52, 1, 'Стрімінгові плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Стрімінгові плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Стрімінгові плеєри', 'Демонстраційна категорія «Стрімінгові плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Стрімінгові плеєри'),
(53, 1, 'Чохли для плеєрів', '&lt;h2 id=&quot;overview&quot;&gt;Чохли для плеєрів&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Чохли для плеєрів', 'Демонстраційна категорія «Чохли для плеєрів» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Чохли для плеєрів'),
(54, 1, 'Зарядні пристрої', '&lt;h2 id=&quot;overview&quot;&gt;Зарядні пристрої&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Зарядні пристрої', 'Демонстраційна категорія «Зарядні пристрої» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Зарядні пристрої'),
(55, 1, 'Преміум-плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Преміум-плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Преміум-плеєри', 'Демонстраційна категорія «Преміум-плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Преміум-плеєри'),
(56, 1, 'Hi-Fi плеєри', '&lt;h2 id=&quot;overview&quot;&gt;Hi-Fi плеєри&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «MP3-плеєри» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Hi-Fi плеєри', 'Демонстраційна категорія «Hi-Fi плеєри» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Hi-Fi плеєри'),
(57, 1, 'Планшети', '&lt;h2 id=&quot;overview&quot;&gt;Планшети&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Планшети', 'Демонстраційна категорія «Планшети» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Планшети'),
(58, 1, 'Офісні планшети', '&lt;h2 id=&quot;overview&quot;&gt;Офісні планшети&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;p&gt;Категорія входить до розділу «Планшети» і допомагає наочно показати, як працює багаторівнева навігація магазину.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Офісні планшети', 'Демонстраційна категорія «Офісні планшети» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Офісні планшети'),
(33, 1, 'Камери', '&lt;h2 id=&quot;overview&quot;&gt;Камери&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Що перевірити&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Демо-режим&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Цей розділ підготовлено як презентаційний шаблон для демонстрації структури каталогу, зображень категорій, підкатегорій, фільтрів, сортування та адаптивної вітрини.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що варто перевірити на сторінці&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;картинки категорій і підкатегорій;&lt;/li&gt;&lt;li&gt;хлібні крихти, сортування, пагінацію та перемикання виду;&lt;/li&gt;&lt;li&gt;короткі описи, ціни, наявність і картки товарів;&lt;/li&gt;&lt;li&gt;роботу на смартфоні та планшеті. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Демо-режим&lt;/h3&gt;&lt;p&gt;Наповнення цієї категорії є демонстраційним. Ви можете легко замінити назви, товари, фільтри та тексти на реальний асортимент магазину в Україні.&lt;/p&gt;', 'Камери', 'Демонстраційна категорія «Камери» у CodeCart PRO Demo Store: зручна навігація, адаптивні картки товарів і готова структура каталогу.', 'демо, категорія, codecart, каталог', 'Камери'),
(20, 2, 'Desktops', '&lt;h2 id=&quot;overview&quot;&gt;Desktops&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Desktops', 'Demo category “Desktops” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Desktops'),
(18, 2, 'Laptops &amp; Notebooks', '&lt;h2 id=&quot;overview&quot;&gt;Laptops &amp;amp; Notebooks&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Laptops &amp; Notebooks', 'Demo category “Laptops &amp; Notebooks” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Laptops &amp; Notebooks'),
(25, 2, 'Components', '&lt;h2 id=&quot;overview&quot;&gt;Components&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Components', 'Demo category “Components” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Components'),
(29, 2, 'Mice and Trackballs', '&lt;h2 id=&quot;overview&quot;&gt;Mice and Trackballs&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Components” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Mice and Trackballs', 'Demo category “Mice and Trackballs” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Mice and Trackballs'),
(28, 2, 'Monitors', '&lt;h2 id=&quot;overview&quot;&gt;Monitors&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Components” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Monitors', 'Demo category “Monitors” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Monitors'),
(35, 2, 'LED Monitors', '&lt;h2 id=&quot;overview&quot;&gt;LED Monitors&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Monitors” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'LED Monitors', 'Demo category “LED Monitors” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'LED Monitors'),
(36, 2, '4K Monitors', '&lt;h2 id=&quot;overview&quot;&gt;4K Monitors&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Monitors” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', '4K Monitors', 'Demo category “4K Monitors” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', '4K Monitors'),
(30, 2, 'Printers', '&lt;h2 id=&quot;overview&quot;&gt;Printers&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Components” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Printers', 'Demo category “Printers” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Printers'),
(31, 2, 'Scanners', '&lt;h2 id=&quot;overview&quot;&gt;Scanners&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Components” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Scanners', 'Demo category “Scanners” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Scanners'),
(32, 2, 'Web Cameras', '&lt;h2 id=&quot;overview&quot;&gt;Web Cameras&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Components” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Web Cameras', 'Demo category “Web Cameras” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Web Cameras'),
(57, 2, 'Tablets', '&lt;h2 id=&quot;overview&quot;&gt;Tablets&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Tablets', 'Demo category “Tablets” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Tablets'),
(17, 2, 'Software', '&lt;h2 id=&quot;overview&quot;&gt;Software&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Software', 'Demo category “Software” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Software'),
(24, 2, 'Phones &amp; PDAs', '&lt;h2 id=&quot;overview&quot;&gt;Phones &amp;amp; PDAs&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Phones &amp; PDAs', 'Demo category “Phones &amp; PDAs” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Phones &amp; PDAs'),
(34, 2, 'MP3 Players', '&lt;h2 id=&quot;overview&quot;&gt;MP3 Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'MP3 Players', 'Demo category “MP3 Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'MP3 Players'),
(33, 2, 'Cameras', '&lt;h2 id=&quot;overview&quot;&gt;Cameras&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Cameras', 'Demo category “Cameras” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Cameras'),
(46, 2, 'Macs', '&lt;h2 id=&quot;overview&quot;&gt;Macs&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Laptops” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Macs', 'Demo category “Macs” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Macs'),
(45, 2, 'Windows', '&lt;h2 id=&quot;overview&quot;&gt;Windows&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Laptops” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Windows', 'Demo category “Windows” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Windows'),
(43, 2, 'Home Audio', '&lt;h2 id=&quot;overview&quot;&gt;Home Audio&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Home Audio', 'Demo category “Home Audio” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Home Audio'),
(44, 2, 'Player Accessories', '&lt;h2 id=&quot;overview&quot;&gt;Player Accessories&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Player Accessories', 'Demo category “Player Accessories” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Player Accessories'),
(27, 2, 'Mac', '&lt;h2 id=&quot;overview&quot;&gt;Mac&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Computers” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Mac', 'Demo category “Mac” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Mac'),
(26, 2, 'PC', '&lt;h2 id=&quot;overview&quot;&gt;PC&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Computers” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'PC', 'Demo category “PC” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'PC'),
(47, 2, 'Portable Players', '&lt;h2 id=&quot;overview&quot;&gt;Portable Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Portable Players', 'Demo category “Portable Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Portable Players'),
(48, 2, 'Classic Players', '&lt;h2 id=&quot;overview&quot;&gt;Classic Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Classic Players', 'Demo category “Classic Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Classic Players'),
(49, 2, 'Video Players', '&lt;h2 id=&quot;overview&quot;&gt;Video Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Video Players', 'Demo category “Video Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Video Players'),
(50, 2, 'Kids Players', '&lt;h2 id=&quot;overview&quot;&gt;Kids Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Kids Players', 'Demo category “Kids Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Kids Players'),
(51, 2, 'Fitness Players', '&lt;h2 id=&quot;overview&quot;&gt;Fitness Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Fitness Players', 'Demo category “Fitness Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Fitness Players'),
(52, 2, 'Streaming Players', '&lt;h2 id=&quot;overview&quot;&gt;Streaming Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Streaming Players', 'Demo category “Streaming Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Streaming Players'),
(58, 2, 'Office Tablets', '&lt;h2 id=&quot;overview&quot;&gt;Office Tablets&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “Tablets” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Office Tablets', 'Demo category “Office Tablets” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Office Tablets'),
(53, 2, 'Player Cases', '&lt;h2 id=&quot;overview&quot;&gt;Player Cases&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Player Cases', 'Demo category “Player Cases” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Player Cases'),
(54, 2, 'Chargers', '&lt;h2 id=&quot;overview&quot;&gt;Chargers&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Chargers', 'Demo category “Chargers” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Chargers'),
(55, 2, 'Premium Players', '&lt;h2 id=&quot;overview&quot;&gt;Premium Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Premium Players', 'Demo category “Premium Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Premium Players'),
(56, 2, 'Hi-Fi Players', '&lt;h2 id=&quot;overview&quot;&gt;Hi-Fi Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Hi-Fi Players', 'Demo category “Hi-Fi Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Hi-Fi Players'),
(38, 2, 'Touch MP3 Players', '&lt;h2 id=&quot;overview&quot;&gt;Touch MP3 Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Touch MP3 Players', 'Demo category “Touch MP3 Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Touch MP3 Players'),
(37, 2, 'Sports MP3 Players', '&lt;h2 id=&quot;overview&quot;&gt;Sports MP3 Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Sports MP3 Players', 'Demo category “Sports MP3 Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Sports MP3 Players'),
(39, 2, 'Compact MP3 Players', '&lt;h2 id=&quot;overview&quot;&gt;Compact MP3 Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Compact MP3 Players', 'Demo category “Compact MP3 Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Compact MP3 Players'),
(40, 2, 'Mini Players', '&lt;h2 id=&quot;overview&quot;&gt;Mini Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Mini Players', 'Demo category “Mini Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Mini Players'),
(41, 2, 'Clip Players', '&lt;h2 id=&quot;overview&quot;&gt;Clip Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Clip Players', 'Demo category “Clip Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Clip Players'),
(42, 2, 'Multimedia Players', '&lt;h2 id=&quot;overview&quot;&gt;Multimedia Players&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;What to test&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Demo mode&lt;/a&gt;&lt;/p&gt;&lt;p&gt;This section is prepared as a presentation template to demonstrate category structure, category images, subcategories, filters, sorting and a responsive storefront.&lt;/p&gt;&lt;p&gt;This category belongs to the “MP3 Players” section and helps demonstrate a multi-level navigation structure.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What to test on this page&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;category and subcategory images;&lt;/li&gt;&lt;li&gt;breadcrumbs, sorting, pagination and view switching;&lt;/li&gt;&lt;li&gt;short descriptions, pricing, stock status and product cards;&lt;/li&gt;&lt;li&gt;mobile and tablet behavior. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Demo mode&lt;/h3&gt;&lt;p&gt;The content in this category is demonstrational and can be replaced with your real assortment, filters and copy.&lt;/p&gt;', 'Multimedia Players', 'Demo category “Multimedia Players” in CodeCart PRO Demo Store with responsive product cards and a ready catalog structure.', 'demo, category, codecart, catalog', 'Multimedia Players');

-- --------------------------------------------------------
--
-- Table structure for table `oc_category_filter`
--

DROP TABLE IF EXISTS `oc_category_filter`;
CREATE TABLE `oc_category_filter` (
  `category_id` int(11) NOT NULL,
  `filter_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`filter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_category_path`
--

DROP TABLE IF EXISTS `oc_category_path`;
CREATE TABLE `oc_category_path` (
  `category_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`path_id`),
  KEY `path_id` (`path_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_category_path`
--

INSERT INTO `oc_category_path` (`category_id`, `path_id`, `level`) VALUES
(25, 25, 0),
(28, 25, 0),
(28, 28, 1),
(35, 25, 0),
(35, 28, 1),
(35, 35, 2),
(36, 25, 0),
(36, 28, 1),
(36, 36, 2),
(29, 25, 0),
(29, 29, 1),
(30, 25, 0),
(30, 30, 1),
(31, 25, 0),
(31, 31, 1),
(32, 25, 0),
(32, 32, 1),
(20, 20, 0),
(27, 20, 0),
(27, 27, 1),
(26, 20, 0),
(26, 26, 1),
(24, 24, 0),
(18, 18, 0),
(45, 18, 0),
(45, 45, 1),
(46, 18, 0),
(46, 46, 1),
(17, 17, 0),
(33, 33, 0),
(34, 34, 0),
(37, 34, 0),
(37, 37, 1),
(38, 34, 0),
(38, 38, 1),
(39, 34, 0),
(39, 39, 1),
(40, 34, 0),
(40, 40, 1),
(41, 34, 0),
(41, 41, 1),
(42, 34, 0),
(42, 42, 1),
(43, 34, 0),
(43, 43, 1),
(44, 34, 0),
(44, 44, 1),
(47, 34, 0),
(47, 47, 1),
(48, 34, 0),
(48, 48, 1),
(49, 34, 0),
(49, 49, 1),
(50, 34, 0),
(50, 50, 1),
(51, 34, 0),
(51, 51, 1),
(52, 34, 0),
(52, 52, 1),
(58, 57, 0),
(58, 58, 1),
(53, 34, 0),
(53, 53, 1),
(54, 34, 0),
(54, 54, 1),
(55, 34, 0),
(55, 55, 1),
(56, 34, 0),
(56, 56, 1),
(57, 57, 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_googleshopping_category`
--

DROP TABLE IF EXISTS `oc_googleshopping_category`;
CREATE TABLE `oc_googleshopping_category` (
  `google_product_category` varchar(10) NOT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`google_product_category`,`store_id`),
  KEY `category_id_store_id` (`category_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_category_to_layout`
--

DROP TABLE IF EXISTS `oc_category_to_layout`;
CREATE TABLE `oc_category_to_layout` (
  `category_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `layout_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_category_to_store`
--

DROP TABLE IF EXISTS `oc_category_to_store`;
CREATE TABLE `oc_category_to_store` (
  `category_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`store_id`),
  KEY `store_category` (`store_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_category_to_store`
--

INSERT INTO `oc_category_to_store` (`category_id`, `store_id`) VALUES
(17, 0),
(18, 0),
(20, 0),
(24, 0),
(25, 0),
(26, 0),
(27, 0),
(28, 0),
(29, 0),
(30, 0),
(31, 0),
(32, 0),
(33, 0),
(34, 0),
(35, 0),
(36, 0),
(37, 0),
(38, 0),
(39, 0),
(40, 0),
(41, 0),
(42, 0),
(43, 0),
(44, 0),
(45, 0),
(46, 0),
(47, 0),
(48, 0),
(49, 0),
(50, 0),
(51, 0),
(52, 0),
(53, 0),
(54, 0),
(55, 0),
(56, 0),
(57, 0),
(58, 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_country`
--

DROP TABLE IF EXISTS `oc_country`;
CREATE TABLE `oc_country` (
  `country_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `iso_code_2` varchar(2) NOT NULL,
  `iso_code_3` varchar(3) NOT NULL,
  `address_format` text NOT NULL,
  `postcode_required` tinyint(1) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_country`
--

INSERT INTO `oc_country` (`country_id`, `name`, `iso_code_2`, `iso_code_3`, `address_format`, `postcode_required`, `status`) VALUES
(1, 'Afghanistan', 'AF', 'AFG', '', 0, 1),
(2, 'Albania', 'AL', 'ALB', '', 0, 1),
(3, 'Algeria', 'DZ', 'DZA', '', 0, 1),
(4, 'American Samoa', 'AS', 'ASM', '', 0, 1),
(5, 'Andorra', 'AD', 'AND', '', 0, 1),
(6, 'Angola', 'AO', 'AGO', '', 0, 1),
(7, 'Anguilla', 'AI', 'AIA', '', 0, 1),
(8, 'Antarctica', 'AQ', 'ATA', '', 0, 1),
(9, 'Antigua and Barbuda', 'AG', 'ATG', '', 0, 1),
(10, 'Argentina', 'AR', 'ARG', '', 0, 1),
(11, 'Armenia', 'AM', 'ARM', '', 0, 1),
(12, 'Aruba', 'AW', 'ABW', '', 0, 1),
(13, 'Australia', 'AU', 'AUS', '', 0, 1),
(14, 'Austria', 'AT', 'AUT', '', 0, 1),
(15, 'Azerbaijan', 'AZ', 'AZE', '', 0, 1),
(16, 'Bahamas', 'BS', 'BHS', '', 0, 1),
(17, 'Bahrain', 'BH', 'BHR', '', 0, 1),
(18, 'Bangladesh', 'BD', 'BGD', '', 0, 1),
(19, 'Barbados', 'BB', 'BRB', '', 0, 1),
(20, 'Belarus', 'BY', 'BLR', '', 0, 1),
(21, 'Belgium', 'BE', 'BEL', '{firstname} {lastname}\r\n{company}\r\n{address_1}\r\n{address_2}\r\n{postcode} {city}\r\n{country}', 0, 1),
(22, 'Belize', 'BZ', 'BLZ', '', 0, 1),
(23, 'Benin', 'BJ', 'BEN', '', 0, 1),
(24, 'Bermuda', 'BM', 'BMU', '', 0, 1),
(25, 'Bhutan', 'BT', 'BTN', '', 0, 1),
(26, 'Bolivia', 'BO', 'BOL', '', 0, 1),
(27, 'Bosnia and Herzegovina', 'BA', 'BIH', '', 0, 1),
(28, 'Botswana', 'BW', 'BWA', '', 0, 1),
(29, 'Bouvet Island', 'BV', 'BVT', '', 0, 1),
(30, 'Brazil', 'BR', 'BRA', '', 0, 1),
(31, 'British Indian Ocean Territory', 'IO', 'IOT', '', 0, 1),
(32, 'Brunei Darussalam', 'BN', 'BRN', '', 0, 1),
(33, 'Bulgaria', 'BG', 'BGR', '', 0, 1),
(34, 'Burkina Faso', 'BF', 'BFA', '', 0, 1),
(35, 'Burundi', 'BI', 'BDI', '', 0, 1),
(36, 'Cambodia', 'KH', 'KHM', '', 0, 1),
(37, 'Cameroon', 'CM', 'CMR', '', 0, 1),
(38, 'Canada', 'CA', 'CAN', '', 0, 1),
(39, 'Cape Verde', 'CV', 'CPV', '', 0, 1),
(40, 'Cayman Islands', 'KY', 'CYM', '', 0, 1),
(41, 'Central African Republic', 'CF', 'CAF', '', 0, 1),
(42, 'Chad', 'TD', 'TCD', '', 0, 1),
(43, 'Chile', 'CL', 'CHL', '', 0, 1),
(44, 'China', 'CN', 'CHN', '', 0, 1),
(45, 'Christmas Island', 'CX', 'CXR', '', 0, 1),
(46, 'Cocos (Keeling) Islands', 'CC', 'CCK', '', 0, 1),
(47, 'Colombia', 'CO', 'COL', '', 0, 1),
(48, 'Comoros', 'KM', 'COM', '', 0, 1),
(49, 'Congo', 'CG', 'COG', '', 0, 1),
(50, 'Cook Islands', 'CK', 'COK', '', 0, 1),
(51, 'Costa Rica', 'CR', 'CRI', '', 0, 1),
(52, 'Cote D''Ivoire', 'CI', 'CIV', '', 0, 1),
(53, 'Croatia', 'HR', 'HRV', '', 0, 1),
(54, 'Cuba', 'CU', 'CUB', '', 0, 1),
(55, 'Cyprus', 'CY', 'CYP', '', 0, 1),
(56, 'Czech Republic', 'CZ', 'CZE', '', 0, 1),
(57, 'Denmark', 'DK', 'DNK', '', 0, 1),
(58, 'Djibouti', 'DJ', 'DJI', '', 0, 1),
(59, 'Dominica', 'DM', 'DMA', '', 0, 1),
(60, 'Dominican Republic', 'DO', 'DOM', '', 0, 1),
(61, 'East Timor', 'TL', 'TLS', '', 0, 1),
(62, 'Ecuador', 'EC', 'ECU', '', 0, 1),
(63, 'Egypt', 'EG', 'EGY', '', 0, 1),
(64, 'El Salvador', 'SV', 'SLV', '', 0, 1),
(65, 'Equatorial Guinea', 'GQ', 'GNQ', '', 0, 1),
(66, 'Eritrea', 'ER', 'ERI', '', 0, 1),
(67, 'Estonia', 'EE', 'EST', '', 0, 1),
(68, 'Ethiopia', 'ET', 'ETH', '', 0, 1),
(69, 'Falkland Islands (Malvinas)', 'FK', 'FLK', '', 0, 1),
(70, 'Faroe Islands', 'FO', 'FRO', '', 0, 1),
(71, 'Fiji', 'FJ', 'FJI', '', 0, 1),
(72, 'Finland', 'FI', 'FIN', '', 0, 1),
(74, 'France, Metropolitan', 'FR', 'FRA', '{firstname} {lastname}\r\n{company}\r\n{address_1}\r\n{address_2}\r\n{postcode} {city}\r\n{country}', 1, 1),
(75, 'French Guiana', 'GF', 'GUF', '', 0, 1),
(76, 'French Polynesia', 'PF', 'PYF', '', 0, 1),
(77, 'French Southern Territories', 'TF', 'ATF', '', 0, 1),
(78, 'Gabon', 'GA', 'GAB', '', 0, 1),
(79, 'Gambia', 'GM', 'GMB', '', 0, 1),
(80, 'Georgia', 'GE', 'GEO', '', 0, 1),
(81, 'Germany', 'DE', 'DEU', '{company}\r\n{firstname} {lastname}\r\n{address_1}\r\n{address_2}\r\n{postcode} {city}\r\n{country}', 1, 1),
(82, 'Ghana', 'GH', 'GHA', '', 0, 1),
(83, 'Gibraltar', 'GI', 'GIB', '', 0, 1),
(84, 'Greece', 'GR', 'GRC', '', 0, 1),
(85, 'Greenland', 'GL', 'GRL', '', 0, 1),
(86, 'Grenada', 'GD', 'GRD', '', 0, 1),
(87, 'Guadeloupe', 'GP', 'GLP', '', 0, 1),
(88, 'Guam', 'GU', 'GUM', '', 0, 1),
(89, 'Guatemala', 'GT', 'GTM', '', 0, 1),
(90, 'Guinea', 'GN', 'GIN', '', 0, 1),
(91, 'Guinea-Bissau', 'GW', 'GNB', '', 0, 1),
(92, 'Guyana', 'GY', 'GUY', '', 0, 1),
(93, 'Haiti', 'HT', 'HTI', '', 0, 1),
(94, 'Heard and Mc Donald Islands', 'HM', 'HMD', '', 0, 1),
(95, 'Honduras', 'HN', 'HND', '', 0, 1),
(96, 'Hong Kong', 'HK', 'HKG', '', 0, 1),
(97, 'Hungary', 'HU', 'HUN', '', 0, 1),
(98, 'Iceland', 'IS', 'ISL', '', 0, 1),
(99, 'India', 'IN', 'IND', '', 0, 1),
(100, 'Indonesia', 'ID', 'IDN', '', 0, 1),
(101, 'Iran (Islamic Republic of)', 'IR', 'IRN', '', 0, 1),
(102, 'Iraq', 'IQ', 'IRQ', '', 0, 1),
(103, 'Ireland', 'IE', 'IRL', '', 0, 1),
(104, 'Israel', 'IL', 'ISR', '', 0, 1),
(105, 'Italy', 'IT', 'ITA', '', 0, 1),
(106, 'Jamaica', 'JM', 'JAM', '', 0, 1),
(107, 'Japan', 'JP', 'JPN', '', 0, 1),
(108, 'Jordan', 'JO', 'JOR', '', 0, 1),
(109, 'Kazakhstan', 'KZ', 'KAZ', '', 0, 1),
(110, 'Kenya', 'KE', 'KEN', '', 0, 1),
(111, 'Kiribati', 'KI', 'KIR', '', 0, 1),
(112, 'North Korea', 'KP', 'PRK', '', 0, 1),
(113, 'South Korea', 'KR', 'KOR', '', 0, 1),
(114, 'Kuwait', 'KW', 'KWT', '', 0, 1),
(115, 'Kyrgyzstan', 'KG', 'KGZ', '', 0, 1),
(116, 'Lao People''s Democratic Republic', 'LA', 'LAO', '', 0, 1),
(117, 'Latvia', 'LV', 'LVA', '', 0, 1),
(118, 'Lebanon', 'LB', 'LBN', '', 0, 1),
(119, 'Lesotho', 'LS', 'LSO', '', 0, 1),
(120, 'Liberia', 'LR', 'LBR', '', 0, 1),
(121, 'Libyan Arab Jamahiriya', 'LY', 'LBY', '', 0, 1),
(122, 'Liechtenstein', 'LI', 'LIE', '', 0, 1),
(123, 'Lithuania', 'LT', 'LTU', '', 0, 1),
(124, 'Luxembourg', 'LU', 'LUX', '', 0, 1),
(125, 'Macau', 'MO', 'MAC', '', 0, 1),
(126, 'FYROM', 'MK', 'MKD', '', 0, 1),
(127, 'Madagascar', 'MG', 'MDG', '', 0, 1),
(128, 'Malawi', 'MW', 'MWI', '', 0, 1),
(129, 'Malaysia', 'MY', 'MYS', '', 0, 1),
(130, 'Maldives', 'MV', 'MDV', '', 0, 1),
(131, 'Mali', 'ML', 'MLI', '', 0, 1),
(132, 'Malta', 'MT', 'MLT', '', 0, 1),
(133, 'Marshall Islands', 'MH', 'MHL', '', 0, 1),
(134, 'Martinique', 'MQ', 'MTQ', '', 0, 1),
(135, 'Mauritania', 'MR', 'MRT', '', 0, 1),
(136, 'Mauritius', 'MU', 'MUS', '', 0, 1),
(137, 'Mayotte', 'YT', 'MYT', '', 0, 1),
(138, 'Mexico', 'MX', 'MEX', '', 0, 1),
(139, 'Micronesia, Federated States of', 'FM', 'FSM', '', 0, 1),
(140, 'Moldova, Republic of', 'MD', 'MDA', '', 0, 1),
(141, 'Monaco', 'MC', 'MCO', '', 0, 1),
(142, 'Mongolia', 'MN', 'MNG', '', 0, 1),
(143, 'Montserrat', 'MS', 'MSR', '', 0, 1),
(144, 'Morocco', 'MA', 'MAR', '', 0, 1),
(145, 'Mozambique', 'MZ', 'MOZ', '', 0, 1),
(146, 'Myanmar', 'MM', 'MMR', '', 0, 1),
(147, 'Namibia', 'NA', 'NAM', '', 0, 1),
(148, 'Nauru', 'NR', 'NRU', '', 0, 1),
(149, 'Nepal', 'NP', 'NPL', '', 0, 1),
(150, 'Netherlands', 'NL', 'NLD', '', 0, 1),
(151, 'Netherlands Antilles', 'AN', 'ANT', '', 0, 1),
(152, 'New Caledonia', 'NC', 'NCL', '', 0, 1),
(153, 'New Zealand', 'NZ', 'NZL', '', 0, 1),
(154, 'Nicaragua', 'NI', 'NIC', '', 0, 1),
(155, 'Niger', 'NE', 'NER', '', 0, 1),
(156, 'Nigeria', 'NG', 'NGA', '', 0, 1),
(157, 'Niue', 'NU', 'NIU', '', 0, 1),
(158, 'Norfolk Island', 'NF', 'NFK', '', 0, 1),
(159, 'Northern Mariana Islands', 'MP', 'MNP', '', 0, 1),
(160, 'Norway', 'NO', 'NOR', '', 0, 1),
(161, 'Oman', 'OM', 'OMN', '', 0, 1),
(162, 'Pakistan', 'PK', 'PAK', '', 0, 1),
(163, 'Palau', 'PW', 'PLW', '', 0, 1),
(164, 'Panama', 'PA', 'PAN', '', 0, 1),
(165, 'Papua New Guinea', 'PG', 'PNG', '', 0, 1),
(166, 'Paraguay', 'PY', 'PRY', '', 0, 1),
(167, 'Peru', 'PE', 'PER', '', 0, 1),
(168, 'Philippines', 'PH', 'PHL', '', 0, 1),
(169, 'Pitcairn', 'PN', 'PCN', '', 0, 1),
(170, 'Poland', 'PL', 'POL', '', 0, 1),
(171, 'Portugal', 'PT', 'PRT', '', 0, 1),
(172, 'Puerto Rico', 'PR', 'PRI', '', 0, 1),
(173, 'Qatar', 'QA', 'QAT', '', 0, 1),
(174, 'Reunion', 'RE', 'REU', '', 0, 1),
(175, 'Romania', 'RO', 'ROM', '', 0, 1),
(176, 'Russia is a terrorist state', 'RU', 'RUS', '', 0, 1),
(177, 'Rwanda', 'RW', 'RWA', '', 0, 1),
(178, 'Saint Kitts and Nevis', 'KN', 'KNA', '', 0, 1),
(179, 'Saint Lucia', 'LC', 'LCA', '', 0, 1),
(180, 'Saint Vincent and the Grenadines', 'VC', 'VCT', '', 0, 1),
(181, 'Samoa', 'WS', 'WSM', '', 0, 1),
(182, 'San Marino', 'SM', 'SMR', '', 0, 1),
(183, 'Sao Tome and Principe', 'ST', 'STP', '', 0, 1),
(184, 'Saudi Arabia', 'SA', 'SAU', '', 0, 1),
(185, 'Senegal', 'SN', 'SEN', '', 0, 1),
(186, 'Seychelles', 'SC', 'SYC', '', 0, 1),
(187, 'Sierra Leone', 'SL', 'SLE', '', 0, 1),
(188, 'Singapore', 'SG', 'SGP', '', 0, 1),
(189, 'Slovak Republic', 'SK', 'SVK', '{firstname} {lastname}\r\n{company}\r\n{address_1}\r\n{address_2}\r\n{city} {postcode}\r\n{zone}\r\n{country}', 0, 1),
(190, 'Slovenia', 'SI', 'SVN', '', 0, 1),
(191, 'Solomon Islands', 'SB', 'SLB', '', 0, 1),
(192, 'Somalia', 'SO', 'SOM', '', 0, 1),
(193, 'South Africa', 'ZA', 'ZAF', '', 0, 1),
(194, 'South Georgia &amp; South Sandwich Islands', 'GS', 'SGS', '', 0, 1),
(195, 'Spain', 'ES', 'ESP', '', 0, 1),
(196, 'Sri Lanka', 'LK', 'LKA', '', 0, 1),
(197, 'St. Helena', 'SH', 'SHN', '', 0, 1),
(198, 'St. Pierre and Miquelon', 'PM', 'SPM', '', 0, 1),
(199, 'Sudan', 'SD', 'SDN', '', 0, 1),
(200, 'Suriname', 'SR', 'SUR', '', 0, 1),
(201, 'Svalbard and Jan Mayen Islands', 'SJ', 'SJM', '', 0, 1),
(202, 'Swaziland', 'SZ', 'SWZ', '', 0, 1),
(203, 'Sweden', 'SE', 'SWE', '{company}\r\n{firstname} {lastname}\r\n{address_1}\r\n{address_2}\r\n{postcode} {city}\r\n{country}', 1, 1),
(204, 'Switzerland', 'CH', 'CHE', '', 0, 1),
(205, 'Syrian Arab Republic', 'SY', 'SYR', '', 0, 1),
(206, 'Taiwan', 'TW', 'TWN', '', 0, 1),
(207, 'Таджикистан', 'TJ', 'TJK', '', 0, 1),
(208, 'Tanzania, United Republic of', 'TZ', 'TZA', '', 0, 1),
(209, 'Thailand', 'TH', 'THA', '', 0, 1),
(210, 'Togo', 'TG', 'TGO', '', 0, 1),
(211, 'Tokelau', 'TK', 'TKL', '', 0, 1),
(212, 'Tonga', 'TO', 'TON', '', 0, 1),
(213, 'Trinidad and Tobago', 'TT', 'TTO', '', 0, 1),
(214, 'Tunisia', 'TN', 'TUN', '', 0, 1),
(215, 'Turkey', 'TR', 'TUR', '', 0, 1),
(216, 'Туркменистан', 'TM', 'TKM', '', 0, 1),
(217, 'Turks and Caicos Islands', 'TC', 'TCA', '', 0, 1),
(218, 'Tuvalu', 'TV', 'TUV', '', 0, 1),
(219, 'Uganda', 'UG', 'UGA', '', 0, 1),
(220, 'Україна', 'UA', 'UKR', '', 0, 1),
(221, 'United Arab Emirates', 'AE', 'ARE', '', 0, 1),
(222, 'United Kingdom', 'GB', 'GBR', '', 1, 1),
(223, 'United States', 'US', 'USA', '{firstname} {lastname}\r\n{company}\r\n{address_1}\r\n{address_2}\r\n{city}, {zone} {postcode}\r\n{country}', 0, 1),
(224, 'United States Minor Outlying Islands', 'UM', 'UMI', '', 0, 1),
(225, 'Uruguay', 'UY', 'URY', '', 0, 1),
(226, 'Uzbekistan', 'UZ', 'UZB', '', 0, 1),
(227, 'Vanuatu', 'VU', 'VUT', '', 0, 1),
(228, 'Vatican City State (Holy See)', 'VA', 'VAT', '', 0, 1),
(229, 'Venezuela', 'VE', 'VEN', '', 0, 1),
(230, 'Viet Nam', 'VN', 'VNM', '', 0, 1),
(231, 'Virgin Islands (British)', 'VG', 'VGB', '', 0, 1),
(232, 'Virgin Islands (U.S.)', 'VI', 'VIR', '', 0, 1),
(233, 'Wallis and Futuna Islands', 'WF', 'WLF', '', 0, 1),
(234, 'Western Sahara', 'EH', 'ESH', '', 0, 1),
(235, 'Yemen', 'YE', 'YEM', '', 0, 1),
(237, 'Democratic Republic of Congo', 'CD', 'COD', '', 0, 1),
(238, 'Zambia', 'ZM', 'ZMB', '', 0, 1),
(239, 'Zimbabwe', 'ZW', 'ZWE', '', 0, 1),
(242, 'Montenegro', 'ME', 'MNE', '', 0, 1),
(243, 'Serbia', 'RS', 'SRB', '', 0, 1),
(244, 'Aaland Islands', 'AX', 'ALA', '', 0, 1),
(245, 'Bonaire, Sint Eustatius and Saba', 'BQ', 'BES', '', 0, 1),
(246, 'Curacao', 'CW', 'CUW', '', 0, 1),
(247, 'Palestinian Territory, Occupied', 'PS', 'PSE', '', 0, 1),
(248, 'South Sudan', 'SS', 'SSD', '', 0, 1),
(249, 'St. Barthelemy', 'BL', 'BLM', '', 0, 1),
(250, 'St. Martin (French part)', 'MF', 'MAF', '', 0, 1),
(251, 'Canary Islands', 'IC', 'ICA', '', 0, 1),
(252, 'Ascension Island (British)', 'AC', 'ASC', '', 0, 1),
(253, 'Kosovo, Republic of', 'XK', 'UNK', '', 0, 1),
(254, 'Isle of Man', 'IM', 'IMN', '', 0, 1),
(255, 'Tristan da Cunha', 'TA', 'SHN', '', 0, 1),
(256, 'Guernsey', 'GG', 'GGY', '', 0, 1),
(257, 'Jersey', 'JE', 'JEY', '', 0, 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_coupon`
--

DROP TABLE IF EXISTS `oc_coupon`;
CREATE TABLE `oc_coupon` (
  `coupon_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` char(1) NOT NULL,
  `discount` decimal(15,4) NOT NULL,
  `logged` tinyint(1) NOT NULL,
  `shipping` tinyint(1) NOT NULL,
  `total` decimal(15,4) NOT NULL,
  `date_start` date NOT NULL DEFAULT '1970-01-01',
  `date_end` date NOT NULL DEFAULT '9999-12-31',
  `uses_total` int(11) NOT NULL,
  `uses_customer` varchar(11) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_coupon`
--

INSERT INTO `oc_coupon` (`coupon_id`, `name`, `code`, `type`, `discount`, `logged`, `shipping`, `total`, `date_start`, `date_end`, `uses_total`, `uses_customer`, `status`, `date_added`) VALUES
(4, 'Знижка 10%', '2222', 'P', '10.0000', 0, 0, '0.0000', '2014-01-01', '2020-01-01', 10, '10', 0, '2009-01-27 13:55:03'),
(5, 'Безкоштовна доставка', '3333', 'P', '0.0000', 0, 1, '100.0000', '2014-01-01', '2014-02-01', 10, '10', 0, '2009-03-14 21:13:53'),
(6, 'Знижка 10 ₴', '1111', 'F', '10.0000', 0, 0, '10.0000', '2014-01-01', '2020-01-01', 100000, '10000', 0, '2009-03-14 21:15:18');

-- --------------------------------------------------------
--
-- Table structure for table `oc_coupon_category`
--

DROP TABLE IF EXISTS `oc_coupon_category`;
CREATE TABLE `oc_coupon_category` (
  `coupon_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`coupon_id`,`category_id`),
  KEY `category_id` (`category_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_coupon_customer_group`
--

DROP TABLE IF EXISTS `oc_coupon_customer_group`;
CREATE TABLE `oc_coupon_customer_group` (
  `coupon_id` int(11) NOT NULL,
  `customer_group_id` int(11) NOT NULL,
  PRIMARY KEY (`coupon_id`,`customer_group_id`),
  KEY `customer_group_id` (`customer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_coupon_history`
--

DROP TABLE IF EXISTS `oc_coupon_history`;
CREATE TABLE `oc_coupon_history` (
  `coupon_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`coupon_history_id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_coupon_product`
--

DROP TABLE IF EXISTS `oc_coupon_product`;
CREATE TABLE `oc_coupon_product` (
  `coupon_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY (`coupon_product_id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `product_id` (`product_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_currency`
--

DROP TABLE IF EXISTS `oc_currency`;
CREATE TABLE `oc_currency` (
  `currency_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(32) NOT NULL,
  `code` varchar(3) NOT NULL,
  `symbol_left` varchar(12) NOT NULL,
  `symbol_right` varchar(12) NOT NULL,
  `decimal_place` char(1) NOT NULL,
  `value` double(15,8) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_currency`
--

INSERT INTO `oc_currency` (`currency_id`, `title`, `code`, `symbol_left`, `symbol_right`, `decimal_place`, `value`, `status`, `date_modified`) VALUES
(1, 'Гривня', 'UAH', '', ' ₴', '2', 1.00000000, 1, '2023-03-27 22:00:00'),
(2, 'US Dollar', 'USD', '$', '', '2', 0.02707687, 1, '2023-03-27 22:00:00'),
(3, 'Euro', 'EUR', '', '€', '2', 0.02512334, 1, '2023-03-27 22:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer`
--

DROP TABLE IF EXISTS `oc_customer`;
CREATE TABLE `oc_customer` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_group_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `language_id` int(11) NOT NULL,
  `firstname` varchar(32) NOT NULL,
  `lastname` varchar(32) NOT NULL,
  `email` varchar(96) NOT NULL,
  `telephone` varchar(32) NOT NULL,
  `fax` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(9) NOT NULL,
  `cart` text,
  `wishlist` text,
  `newsletter` tinyint(1) NOT NULL DEFAULT '0',
  `address_id` int(11) NOT NULL DEFAULT '0',
  `custom_field` text NOT NULL,
  `ip` varchar(45) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `safe` tinyint(1) NOT NULL,
  `token` text NOT NULL,
  `code` varchar(40) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_id`),
  KEY `email` (`email`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_activity`
--

DROP TABLE IF EXISTS `oc_customer_activity`;
CREATE TABLE `oc_customer_activity` (
  `customer_activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `key` varchar(64) NOT NULL,
  `data` text NOT NULL,
  `ip` varchar(45) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_activity_id`),
  KEY `customer_date` (`customer_id`,`date_added`),
  KEY `date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_affiliate`
--

DROP TABLE IF EXISTS `oc_customer_affiliate`;
CREATE TABLE `oc_customer_affiliate` (
  `customer_id` int(11) NOT NULL,
  `company` varchar(40) NOT NULL,
  `website` varchar(255) NOT NULL,
  `tracking` varchar(64) NOT NULL,
  `commission` decimal(4,2) NOT NULL DEFAULT '0.00',
  `tax` varchar(64) NOT NULL,
  `payment` varchar(6) NOT NULL,
  `cheque` varchar(100) NOT NULL,
  `paypal` varchar(64) NOT NULL,
  `bank_name` varchar(64) NOT NULL,
  `bank_branch_number` varchar(64) NOT NULL,
  `bank_swift_code` varchar(64) NOT NULL,
  `bank_account_name` varchar(64) NOT NULL,
  `bank_account_number` varchar(64) NOT NULL,
  `custom_field` text NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_approval`
--

DROP TABLE IF EXISTS `oc_customer_approval`;
CREATE TABLE `oc_customer_approval` (
  `customer_approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `type` varchar(9) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_approval_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_group`
--

DROP TABLE IF EXISTS `oc_customer_group`;
CREATE TABLE `oc_customer_group` (
  `customer_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `approval` int(1) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`customer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_customer_group`
--

INSERT INTO `oc_customer_group` (`customer_group_id`, `approval`, `sort_order`) VALUES
(1, 0, 1),
(2, 0, 2);

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_group_description`
--

DROP TABLE IF EXISTS `oc_customer_group_description`;
CREATE TABLE `oc_customer_group_description` (
  `customer_group_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`customer_group_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_customer_group_description`
--

INSERT INTO `oc_customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 'Приватна особа', 'Покупець-фізична особа. Для швидкого оформлення достатньо імені, прізвища, телефону та способу доставки.'),
(1, 2, 'Private person', 'Individual customer. Quick checkout requires only contact details and the selected delivery method.'),
(2, 1, 'Компанія', 'Юридична особа або ФОП. У швидкому оформленні доступне додаткове поле для реквізитів.'),
(2, 2, 'Company', 'Business customer. Quick checkout can collect company requisites in an additional account field.');

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_history`
--

DROP TABLE IF EXISTS `oc_customer_history`;
CREATE TABLE `oc_customer_history` (
  `customer_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_login`
--

DROP TABLE IF EXISTS `oc_customer_login`;
CREATE TABLE `oc_customer_login` (
  `customer_login_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(96) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `total` int(4) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`customer_login_id`),
  KEY `email` (`email`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_ip`
--

DROP TABLE IF EXISTS `oc_customer_ip`;
CREATE TABLE `oc_customer_ip` (
  `customer_ip_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_ip_id`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_online`
--

DROP TABLE IF EXISTS `oc_customer_online`;
CREATE TABLE `oc_customer_online` (
  `visitor_key` char(64) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `referer` text NOT NULL,
  `user_agent` varchar(512) NOT NULL DEFAULT '',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`visitor_key`),
  KEY `ip` (`ip`),
  KEY `date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_reward`
--

DROP TABLE IF EXISTS `oc_customer_reward`;
CREATE TABLE `oc_customer_reward` (
  `customer_reward_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `order_id` int(11) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `points` int(8) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_reward_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_transaction`
--

DROP TABLE IF EXISTS `oc_customer_transaction`;
CREATE TABLE `oc_customer_transaction` (
  `customer_transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_transaction_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_search`
--

DROP TABLE IF EXISTS `oc_customer_search`;
CREATE TABLE `oc_customer_search` (
  `customer_search_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `category_id` int(11),
  `sub_category` tinyint(1) NOT NULL,
  `description` tinyint(1) NOT NULL,
  `products` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_search_id`),
  KEY `date_added` (`date_added`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_customer_wishlist`
--

DROP TABLE IF EXISTS `oc_customer_wishlist`;
CREATE TABLE `oc_customer_wishlist` (
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`customer_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_custom_field`
--

DROP TABLE IF EXISTS `oc_custom_field`;
CREATE TABLE `oc_custom_field` (
  `custom_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL,
  `value` text NOT NULL,
  `validation` varchar(255) NOT NULL,
  `location` varchar(10) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`custom_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Demo checkout field: company requisites. It is assigned only to the Company customer group.
--

INSERT INTO `oc_custom_field` (`custom_field_id`, `type`, `value`, `validation`, `location`, `status`, `sort_order`) VALUES
(1, 'textarea', '', '', 'account', 1, 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_custom_field_customer_group`
--

DROP TABLE IF EXISTS `oc_custom_field_customer_group`;
CREATE TABLE `oc_custom_field_customer_group` (
  `custom_field_id` int(11) NOT NULL,
  `customer_group_id` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL,
  PRIMARY KEY (`custom_field_id`,`customer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oc_custom_field_customer_group` (`custom_field_id`, `customer_group_id`, `required`) VALUES
(1, 2, 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_custom_field_description`
--

DROP TABLE IF EXISTS `oc_custom_field_description`;
CREATE TABLE `oc_custom_field_description` (
  `custom_field_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`custom_field_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oc_custom_field_description` (`custom_field_id`, `language_id`, `name`) VALUES
(1, 1, 'Реквізити'),
(1, 2, 'Company requisites');

-- --------------------------------------------------------
--
-- Table structure for table `oc_custom_field_value`
--

DROP TABLE IF EXISTS `oc_custom_field_value`;
CREATE TABLE `oc_custom_field_value` (
  `custom_field_value_id` int(11) NOT NULL AUTO_INCREMENT,
  `custom_field_id` int(11) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`custom_field_value_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_custom_field_value_description`
--

DROP TABLE IF EXISTS `oc_custom_field_value_description`;
CREATE TABLE `oc_custom_field_value_description` (
  `custom_field_value_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `custom_field_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`custom_field_value_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_download`
--

DROP TABLE IF EXISTS `oc_download`;
CREATE TABLE `oc_download` (
  `download_id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(160) NOT NULL,
  `mask` varchar(128) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`download_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_download_description`
--

DROP TABLE IF EXISTS `oc_download_description`;
CREATE TABLE `oc_download_description` (
  `download_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`download_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_event`
--

DROP TABLE IF EXISTS `oc_event`;
CREATE TABLE `oc_event` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `trigger` text NOT NULL,
  `action` text NOT NULL,
  `status` tinyint(1) NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_event`
--

INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(1, 'activity_customer_add', 'catalog/model/account/customer/addCustomer/after', 'event/activity/addCustomer', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(2, 'activity_customer_edit', 'catalog/model/account/customer/editCustomer/after', 'event/activity/editCustomer', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(3, 'activity_customer_password', 'catalog/model/account/customer/editPassword/after', 'event/activity/editPassword', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(4, 'activity_customer_forgotten', 'catalog/model/account/customer/editCode/after', 'event/activity/forgotten', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(5, 'activity_transaction', 'catalog/model/account/customer/addTransaction/after', 'event/activity/addTransaction', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(6, 'activity_customer_login', 'catalog/model/account/customer/deleteLoginAttempts/after', 'event/activity/login', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(7, 'activity_address_add', 'catalog/model/account/address/addAddress/after', 'event/activity/addAddress', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(8, 'activity_address_edit', 'catalog/model/account/address/editAddress/after', 'event/activity/editAddress', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(9, 'activity_address_delete', 'catalog/model/account/address/deleteAddress/after', 'event/activity/deleteAddress', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(10, 'activity_affiliate_add', 'catalog/model/account/customer/addAffiliate/after', 'event/activity/addAffiliate', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(11, 'activity_affiliate_edit', 'catalog/model/account/customer/editAffiliate/after', 'event/activity/editAffiliate', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(12, 'activity_order_add', 'catalog/model/checkout/order/addOrderHistory/after', 'event/activity/addOrderHistory', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(13, 'activity_return_add', 'catalog/model/account/return/addReturn/after', 'event/activity/addReturn', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(14, 'mail_transaction', 'catalog/model/account/customer/addTransaction/after', 'mail/transaction', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(15, 'mail_forgotten', 'catalog/model/account/customer/editCode/after', 'mail/forgotten', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(16, 'mail_customer_add', 'catalog/model/account/customer/addCustomer/after', 'mail/register', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(17, 'mail_customer_alert', 'catalog/model/account/customer/addCustomer/after', 'mail/register/alert', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(18, 'mail_affiliate_add', 'catalog/model/account/customer/addAffiliate/after', 'mail/affiliate', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(19, 'mail_affiliate_alert', 'catalog/model/account/customer/addAffiliate/after', 'mail/affiliate/alert', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(20, 'mail_voucher', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/total/voucher/send', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(21, 'mail_order_add', 'catalog/model/checkout/order/addOrderHistory/after', 'mail/order', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(22, 'mail_order_alert', 'catalog/model/checkout/order/addOrderHistory/after', 'mail/order/alert', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(23, 'statistics_review_add', 'catalog/model/catalog/review/addReview/after', 'event/statistics/addReview', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(24, 'statistics_return_add', 'catalog/model/account/return/addReturn/after', 'event/statistics/addReturn', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(25, 'statistics_order_history', 'catalog/model/checkout/order/addOrderHistory/after', 'event/statistics/addOrderHistory', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(26, 'admin_mail_affiliate_approve', 'admin/model/customer/customer_approval/approveAffiliate/after', 'mail/affiliate/approve', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(27, 'admin_mail_affiliate_deny', 'admin/model/customer/customer_approval/denyAffiliate/after', 'mail/affiliate/deny', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(28, 'admin_mail_customer_approve', 'admin/model/customer/customer_approval/approveCustomer/after', 'mail/customer/approve', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(29, 'admin_mail_customer_deny', 'admin/model/customer/customer_approval/denyCustomer/after', 'mail/customer/deny', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(30, 'admin_mail_reward', 'admin/model/customer/customer/addReward/after', 'mail/reward', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(31, 'admin_mail_transaction', 'admin/model/customer/customer/addTransaction/after', 'mail/transaction', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(32, 'admin_mail_return', 'admin/model/sale/return/addReturnHistory/after', 'mail/return', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(33, 'admin_mail_forgotten', 'admin/model/user/user/editCode/after', 'mail/forgotten', 1);
INSERT INTO `oc_event` (`event_id`, `code`, `trigger`, `action`, `status`) VALUES
(34, 'codecart_structured_data', 'catalog/view/common/header/after', 'event/codecart/injectStructuredData', 1),
(35, 'codecart_cookie_consent', 'catalog/view/common/footer/after', 'event/codecart/injectCookieConsent', 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_extension`
--

DROP TABLE IF EXISTS `oc_extension`;
CREATE TABLE `oc_extension` (
  `extension_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL,
  `code` varchar(32) NOT NULL,
  PRIMARY KEY (`extension_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_extension`
--

INSERT INTO `oc_extension` (`extension_id`, `type`, `code`) VALUES
(1, 'payment', 'cod'),
(2, 'total', 'shipping'),
(3, 'total', 'sub_total'),
(4, 'total', 'tax'),
(5, 'total', 'total'),
(6, 'module', 'banner'),
(7, 'module', 'carousel'),
(8, 'total', 'credit'),
(9, 'shipping', 'flat'),
(10, 'total', 'handling'),
(11, 'total', 'low_order_fee'),
(12, 'total', 'coupon'),
(13, 'module', 'category'),
(14, 'module', 'account'),
(15, 'total', 'reward'),
(16, 'total', 'voucher'),
(17, 'payment', 'free_checkout'),
(18, 'module', 'featured'),
(19, 'module', 'slideshow'),
(69, 'theme', 'codecart'),
(21, 'dashboard', 'activity'),
(22, 'dashboard', 'sale'),
(23, 'dashboard', 'recent'),
(24, 'dashboard', 'order'),
(25, 'dashboard', 'online'),
(26, 'dashboard', 'map'),
(27, 'dashboard', 'customer'),
(28, 'dashboard', 'chart'),
(29, 'report', 'sale_coupon'),
(31, 'report', 'customer_search'),
(32, 'report', 'customer_transaction'),
(33, 'report', 'product_purchased'),
(34, 'report', 'product_viewed'),
(35, 'report', 'sale_return'),
(36, 'report', 'sale_order'),
(37, 'report', 'sale_shipping'),
(38, 'report', 'sale_tax'),
(39, 'report', 'customer_activity'),
(40, 'report', 'customer_order'),
(41, 'report', 'customer_reward'),
(43, 'module', 'blog_latest'),
(44, 'module', 'blog_featured'),
(45, 'module', 'blog_category'),
(46, 'module', 'featured_article'),
(47, 'module', 'featured_product'),
(49, 'currency', 'ecb'),
(50, 'currency', 'nbu'),
(51, 'dashboard', 'domovyk'),
(52, 'dashboard', 'codecart_health'),
(53, 'shipping', 'carrier_choice'),
(54, 'module', 'category_wall'),
(55, 'module', 'latest'),
(56, 'module', 'bestseller'),
(57, 'module', 'special'),
(58, 'module', 'popular'),
(59, 'module', 'recently_viewed'),
(60, 'module', 'manufacturer_wall'),
(61, 'module', 'filter'),
(62, 'module', 'html'),
(63, 'module', 'information'),
(64, 'module', 'store'),
(65, 'module', 'codecart_form'),
(66, 'module', 'google_login'),
(67, 'payment', 'bank_transfer'),
(68, 'payment', 'liqpay');

-- --------------------------------------------------------
--
-- Table structure for table `oc_extension_install`
--

DROP TABLE IF EXISTS `oc_extension_install`;
CREATE TABLE `oc_extension_install` (
  `extension_install_id` int(11) NOT NULL AUTO_INCREMENT,
  `extension_download_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`extension_install_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_extension_path`
--

DROP TABLE IF EXISTS `oc_extension_path`;
CREATE TABLE `oc_extension_path` (
  `extension_path_id` int(11) NOT NULL AUTO_INCREMENT,
  `extension_install_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`extension_path_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_filter`
--

DROP TABLE IF EXISTS `oc_filter`;
CREATE TABLE `oc_filter` (
  `filter_id` int(11) NOT NULL AUTO_INCREMENT,
  `filter_group_id` int(11) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`filter_id`),
  KEY `filter_group_id` (`filter_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_filter_description`
--

DROP TABLE IF EXISTS `oc_filter_description`;
CREATE TABLE `oc_filter_description` (
  `filter_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `filter_group_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`filter_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_filter_group`
--

DROP TABLE IF EXISTS `oc_filter_group`;
CREATE TABLE `oc_filter_group` (
  `filter_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`filter_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_filter_group_description`
--

DROP TABLE IF EXISTS `oc_filter_group_description`;
CREATE TABLE `oc_filter_group_description` (
  `filter_group_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`filter_group_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_geo_zone`
--

DROP TABLE IF EXISTS `oc_geo_zone`;
CREATE TABLE `oc_geo_zone` (
  `geo_zone_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `description` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`geo_zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_geo_zone`
--

INSERT INTO `oc_geo_zone` (`geo_zone_id`, `name`, `description`, `date_modified`, `date_added`) VALUES
(3, 'Україна — ПДВ', 'Україна: зона оподаткування ПДВ', '2026-09-01 10:00:00', '2026-09-01 10:00:00'),
(4, 'Україна — доставка', 'Вся територія України для доставки', '2026-09-01 10:00:00', '2026-09-01 10:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `oc_information`
--

DROP TABLE IF EXISTS `oc_information`;
CREATE TABLE `oc_information` (
  `information_id` int(11) NOT NULL AUTO_INCREMENT,
  `bottom` int(1) NOT NULL DEFAULT '0',
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `noindex` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`information_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_information`
--

INSERT INTO `oc_information` (`information_id`, `bottom`, `sort_order`, `status`) VALUES
(3, 1, 3, 1),
(4, 1, 1, 1),
(5, 1, 4, 1),
(6, 1, 2, 1),
(7, 1, 5, 1),
(8, 1, 6, 1),
(9, 1, 7, 1),
(10, 1, 8, 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_information_description`
--

DROP TABLE IF EXISTS `oc_information_description`;
CREATE TABLE `oc_information_description` (
  `information_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(64) NOT NULL,
  `description` mediumtext NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_description` varchar(255) NOT NULL,
  `meta_keyword` varchar(255) NOT NULL,
  `meta_h1` varchar(255) NOT NULL,
  PRIMARY KEY (`information_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_information_description`
--

INSERT INTO `oc_information_description` (`information_id`, `language_id`, `title`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES
(4, 1, 'Про магазин', '&lt;h2 id=&quot;overview&quot;&gt;Про магазин&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Деталі&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Важливо&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Про наш магазин&lt;/h2&gt;&lt;p&gt;Ласкаво просимо! Ми створили цей магазин, щоб покупець міг швидко знайти потрібний товар, порівняти варіанти та без зайвих кроків оформити замовлення. 🛒&lt;/p&gt;&lt;p&gt;У каталозі можна використовувати категорії, фільтри, характеристики, опції товарів, список бажань і порівняння. Для кожної картки доступні фото, опис, ціна, наявність і пов’язана інформація.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Це демонстраційний шаблон.&lt;/strong&gt; Перед запуском магазину замініть цей текст на реальну інформацію про компанію, контакти, досвід, гарантії та переваги.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;Що варто додати перед запуском&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;реальні реквізити компанії;&lt;/li&gt;&lt;li&gt;актуальні умови для України;&lt;/li&gt;&lt;li&gt;контактні дані, графік роботи та відповідальних осіб;&lt;/li&gt;&lt;li&gt;посилання на пов''язані сторінки магазину. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Важливо&lt;/h3&gt;&lt;p&gt;Ця сторінка є шаблоном. Вона створена, щоб магазин не виглядав порожнім і водночас мав правильну базову структуру для презентації та подальшого наповнення.&lt;/p&gt;', 'Про магазин', 'Демонстраційний шаблон сторінки про магазин: асортимент, сервіс, зручне замовлення та ключові переваги.', '', 'Про магазин'),
(5, 1, 'Умови оформлення замовлення', '&lt;h2 id=&quot;overview&quot;&gt;Умови оформлення замовлення&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Деталі&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Важливо&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Умови оформлення замовлення&lt;/h2&gt;&lt;p&gt;Оберіть товар, необхідні опції та кількість, додайте його до кошика й перейдіть до оформлення. Після підтвердження система сформує замовлення та покаже доступні способи доставки й оплати.&lt;/p&gt;&lt;ul&gt;&lt;li&gt;ціна та наявність уточнюються на момент оформлення;&lt;/li&gt;&lt;li&gt;контактні дані мають бути актуальними;&lt;/li&gt;&lt;li&gt;для окремих товарів можуть діяти спеціальні умови, мінімальна кількість або попереднє замовлення;&lt;/li&gt;&lt;li&gt;після оформлення покупець отримує номер замовлення.&lt;/li&gt;&lt;/ul&gt;&lt;p&gt;✅ Перед запуском магазину адаптуйте цей шаблон до фактичних правил вашої компанії.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;Що варто додати перед запуском&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;реальні реквізити компанії;&lt;/li&gt;&lt;li&gt;актуальні умови для України;&lt;/li&gt;&lt;li&gt;контактні дані, графік роботи та відповідальних осіб;&lt;/li&gt;&lt;li&gt;посилання на пов''язані сторінки магазину. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Важливо&lt;/h3&gt;&lt;p&gt;Ця сторінка є шаблоном. Вона створена, щоб магазин не виглядав порожнім і водночас мав правильну базову структуру для презентації та подальшого наповнення.&lt;/p&gt;', 'Умови оформлення замовлення', 'Шаблон умов оформлення замовлення для інтернет-магазину.', '', 'Умови оформлення замовлення'),
(3, 1, 'Угода користувача', '&lt;h2 id=&quot;overview&quot;&gt;Угода користувача&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Деталі&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Важливо&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Угода користувача&lt;/h2&gt;&lt;p&gt;Використовуючи сайт, відвідувач погоджується користуватися його функціями законно, не намагатися порушувати роботу сервісу та надавати коректні дані під час оформлення замовлення або реєстрації.&lt;/p&gt;&lt;p&gt;Інформація про товари, ціни та наявність може оновлюватися. Остаточні умови покупки визначаються під час підтвердження замовлення відповідно до правил продавця.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Шаблон:&lt;/strong&gt; цей текст не є готовою юридичною угодою. Перед комерційним запуском додайте реквізити продавця, порядок оплати, повернення, відповідальність сторін і вимоги законодавства вашої країни.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;Що варто додати перед запуском&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;реальні реквізити компанії;&lt;/li&gt;&lt;li&gt;актуальні умови для України;&lt;/li&gt;&lt;li&gt;контактні дані, графік роботи та відповідальних осіб;&lt;/li&gt;&lt;li&gt;посилання на пов''язані сторінки магазину. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Важливо&lt;/h3&gt;&lt;p&gt;Ця сторінка є шаблоном. Вона створена, щоб магазин не виглядав порожнім і водночас мав правильну базову структуру для презентації та подальшого наповнення.&lt;/p&gt;', 'Угода користувача', 'Базовий шаблон угоди користувача для демонстраційного інтернет-магазину.', '', 'Угода користувача'),
(6, 1, 'Інформація про доставку', '&lt;h2 id=&quot;overview&quot;&gt;Інформація про доставку&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Деталі&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Важливо&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Доставка замовлень&lt;/h2&gt;&lt;p&gt;Магазин може підтримувати кілька способів доставки: кур’єрську службу, поштового оператора, самовивіз або індивідуальний тариф. 🚚 Доступні варіанти та вартість показуються під час оформлення замовлення.&lt;/p&gt;&lt;p&gt;Термін відправлення залежить від наявності товару, часу оформлення та обраного способу доставки. Після передання посилки перевізнику покупцеві може бути надано номер для відстеження.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Шаблон:&lt;/strong&gt; вкажіть своїх перевізників, географію доставки, строки, тарифи та правила безкоштовної доставки.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;Що варто додати перед запуском&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;реальні реквізити компанії;&lt;/li&gt;&lt;li&gt;актуальні умови для України;&lt;/li&gt;&lt;li&gt;контактні дані, графік роботи та відповідальних осіб;&lt;/li&gt;&lt;li&gt;посилання на пов''язані сторінки магазину. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Важливо&lt;/h3&gt;&lt;p&gt;Ця сторінка є шаблоном. Вона створена, щоб магазин не виглядав порожнім і водночас мав правильну базову структуру для презентації та подальшого наповнення.&lt;/p&gt;', 'Інформація про доставку', 'Шаблон сторінки доставки: способи, строки, тарифи та відстеження замовлення.', '', 'Доставка'),
(7, 1, 'Політика конфіденційності', '&lt;h2 id=&quot;overview&quot;&gt;Політика конфіденційності&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Деталі&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Важливо&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Політика конфіденційності&lt;/h2&gt;&lt;p&gt;Ми обробляємо персональні дані лише в обсязі, необхідному для роботи магазину, оформлення та виконання замовлень, підтримки клієнтів, безпеки й виконання законних вимог.&lt;/p&gt;&lt;p&gt;До таких даних можуть належати ім’я, контактні дані, адреса доставки, інформація про замовлення та технічні дані, необхідні для роботи сайту. Дані зберігаються лише стільки, скільки це потрібно для зазначених цілей або передбачено законом.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Шаблон:&lt;/strong&gt; перед запуском додайте реквізити оператора даних, контакти, правові підстави, строки зберігання та порядок реалізації прав користувача.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;Що варто додати перед запуском&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;реальні реквізити компанії;&lt;/li&gt;&lt;li&gt;актуальні умови для України;&lt;/li&gt;&lt;li&gt;контактні дані, графік роботи та відповідальних осіб;&lt;/li&gt;&lt;li&gt;посилання на пов''язані сторінки магазину. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Важливо&lt;/h3&gt;&lt;p&gt;Ця сторінка є шаблоном. Вона створена, щоб магазин не виглядав порожнім і водночас мав правильну базову структуру для презентації та подальшого наповнення.&lt;/p&gt;', 'Політика конфіденційності', 'Базовий шаблон політики конфіденційності для інтернет-магазину.', '', 'Політика конфіденційності'),
(8, 1, 'Політика використання cookies', '&lt;h2 id=&quot;overview&quot;&gt;Політика використання cookies&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Деталі&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Важливо&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Політика використання cookies&lt;/h2&gt;&lt;p&gt;Сайт використовує cookies та подібні технології для роботи кошика, авторизації, збереження налаштувань, безпеки та, якщо дозволено, аналітики.&lt;/p&gt;&lt;p&gt;Необхідні cookies забезпечують базові функції магазину. Необов’язкові категорії використовуються лише відповідно до налаштувань згоди.&lt;/p&gt;&lt;p&gt;🍪 Перед запуском вкажіть фактичні сервіси аналітики, реклами та сторонні cookies, які використовує ваш магазин.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;Що варто додати перед запуском&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;реальні реквізити компанії;&lt;/li&gt;&lt;li&gt;актуальні умови для України;&lt;/li&gt;&lt;li&gt;контактні дані, графік роботи та відповідальних осіб;&lt;/li&gt;&lt;li&gt;посилання на пов''язані сторінки магазину. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Важливо&lt;/h3&gt;&lt;p&gt;Ця сторінка є шаблоном. Вона створена, щоб магазин не виглядав порожнім і водночас мав правильну базову структуру для презентації та подальшого наповнення.&lt;/p&gt;', 'Політика використання cookies', 'Шаблон політики cookies для інтернет-магазину.', '', 'Політика cookies'),
(9, 1, 'Оплата', '&lt;h2 id=&quot;overview&quot;&gt;Оплата&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Деталі&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Важливо&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Способи оплати&lt;/h2&gt;&lt;p&gt;Під час оформлення замовлення покупець бачить доступні для його регіону та кошика способи оплати. Це може бути оплата карткою, банківський переказ, післяплата або інший підключений платіжний метод. 💳&lt;/p&gt;&lt;p&gt;Не передавайте дані банківської картки менеджеру в повідомленнях або електронною поштою. Для онлайн-оплати використовуйте захищену сторінку платіжного провайдера.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Шаблон:&lt;/strong&gt; залиште лише ті способи оплати, які реально підключені у вашому магазині.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;Що варто додати перед запуском&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;реальні реквізити компанії;&lt;/li&gt;&lt;li&gt;актуальні умови для України;&lt;/li&gt;&lt;li&gt;контактні дані, графік роботи та відповідальних осіб;&lt;/li&gt;&lt;li&gt;посилання на пов''язані сторінки магазину. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Важливо&lt;/h3&gt;&lt;p&gt;Ця сторінка є шаблоном. Вона створена, щоб магазин не виглядав порожнім і водночас мав правильну базову структуру для презентації та подальшого наповнення.&lt;/p&gt;', 'Оплата', 'Шаблон сторінки про доступні способи оплати в інтернет-магазині.', '', 'Оплата'),
(10, 1, 'Гарантія та повернення', '&lt;h2 id=&quot;overview&quot;&gt;Гарантія та повернення&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Деталі&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Важливо&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Гарантія та повернення&lt;/h2&gt;&lt;p&gt;Умови гарантії та повернення залежать від типу товару, виробника та законодавства країни продажу. Зберігайте документи про покупку та, за можливості, комплектність і упаковку товару.&lt;/p&gt;&lt;p&gt;Якщо виникла проблема, зверніться до магазину із номером замовлення та коротким описом ситуації. Ми підкажемо подальші дії. ↩️&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Шаблон:&lt;/strong&gt; перед запуском обов’язково вкажіть фактичні строки, винятки, адресу повернення та процедуру розгляду звернень.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;Що варто додати перед запуском&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;реальні реквізити компанії;&lt;/li&gt;&lt;li&gt;актуальні умови для України;&lt;/li&gt;&lt;li&gt;контактні дані, графік роботи та відповідальних осіб;&lt;/li&gt;&lt;li&gt;посилання на пов''язані сторінки магазину. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Важливо&lt;/h3&gt;&lt;p&gt;Ця сторінка є шаблоном. Вона створена, щоб магазин не виглядав порожнім і водночас мав правильну базову структуру для презентації та подальшого наповнення.&lt;/p&gt;', 'Гарантія та повернення', 'Шаблон умов гарантії, обміну та повернення товарів.', '', 'Гарантія та повернення'),
(4, 2, 'About Us', '&lt;h2 id=&quot;overview&quot;&gt;About Us&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Details&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Important&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;About Our Store&lt;/h2&gt;&lt;p&gt;Welcome! This store is designed to help customers find products quickly, compare alternatives and complete an order without unnecessary steps. 🛒&lt;/p&gt;&lt;p&gt;The catalog can use categories, filters, specifications, product options, wish lists and comparisons. Product pages can show images, descriptions, pricing, stock status and related information.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;This is a demo template.&lt;/strong&gt; Before launch, replace it with real company details, contacts, experience, service terms and customer benefits.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;What to add before launch&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;real company details;&lt;/li&gt;&lt;li&gt;actual terms for your target market;&lt;/li&gt;&lt;li&gt;contact details, working hours and responsible contacts;&lt;/li&gt;&lt;li&gt;links to related store pages. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Important&lt;/h3&gt;&lt;p&gt;This page is a template. It keeps the storefront from looking empty and provides a good presentation-ready structure for later customization.&lt;/p&gt;', 'About Us', 'Demo About Us template presenting the store, catalog and customer experience.', '', 'About Us'),
(5, 2, 'Ordering Terms', '&lt;h2 id=&quot;overview&quot;&gt;Ordering Terms&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Details&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Important&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Ordering Terms&lt;/h2&gt;&lt;p&gt;Select a product, required options and quantity, add it to the cart and continue to checkout. After confirmation, the store creates the order and displays available delivery and payment methods.&lt;/p&gt;&lt;ul&gt;&lt;li&gt;price and stock are confirmed at checkout;&lt;/li&gt;&lt;li&gt;contact information must be accurate;&lt;/li&gt;&lt;li&gt;some items may have special terms, minimum quantities or pre-order rules;&lt;/li&gt;&lt;li&gt;the customer receives an order number after checkout.&lt;/li&gt;&lt;/ul&gt;&lt;p&gt;✅ Before launch, adapt this template to your real business rules.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;What to add before launch&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;real company details;&lt;/li&gt;&lt;li&gt;actual terms for your target market;&lt;/li&gt;&lt;li&gt;contact details, working hours and responsible contacts;&lt;/li&gt;&lt;li&gt;links to related store pages. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Important&lt;/h3&gt;&lt;p&gt;This page is a template. It keeps the storefront from looking empty and provides a good presentation-ready structure for later customization.&lt;/p&gt;', 'Ordering Terms', 'Demo template for online store ordering terms.', '', 'Ordering Terms'),
(3, 2, 'User Agreement', '&lt;h2 id=&quot;overview&quot;&gt;User Agreement&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Details&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Important&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;User Agreement&lt;/h2&gt;&lt;p&gt;By using this website, visitors agree to use its functions lawfully, avoid actions that may disrupt the service and provide accurate information when placing an order or creating an account.&lt;/p&gt;&lt;p&gt;Product information, prices and availability may change. Final purchase terms are determined when the order is confirmed under the seller’s applicable rules.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Template:&lt;/strong&gt; this is not a finished legal agreement. Add the seller’s legal details, payment and return procedures, liability terms and requirements applicable in your country.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;What to add before launch&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;real company details;&lt;/li&gt;&lt;li&gt;actual terms for your target market;&lt;/li&gt;&lt;li&gt;contact details, working hours and responsible contacts;&lt;/li&gt;&lt;li&gt;links to related store pages. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Important&lt;/h3&gt;&lt;p&gt;This page is a template. It keeps the storefront from looking empty and provides a good presentation-ready structure for later customization.&lt;/p&gt;', 'User Agreement', 'Basic demo user agreement template for an online store.', '', 'User Agreement'),
(6, 2, 'Delivery Information', '&lt;h2 id=&quot;overview&quot;&gt;Delivery Information&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Details&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Important&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Delivery Information&lt;/h2&gt;&lt;p&gt;The store can support several delivery methods, such as courier services, parcel carriers, pickup or custom rates. 🚚 Available options and costs are shown during checkout.&lt;/p&gt;&lt;p&gt;Dispatch time depends on stock availability, order time and the selected delivery method. After handover to the carrier, the customer may receive a tracking number.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Template:&lt;/strong&gt; specify your actual carriers, delivery regions, lead times, rates and free-shipping rules.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;What to add before launch&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;real company details;&lt;/li&gt;&lt;li&gt;actual terms for your target market;&lt;/li&gt;&lt;li&gt;contact details, working hours and responsible contacts;&lt;/li&gt;&lt;li&gt;links to related store pages. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Important&lt;/h3&gt;&lt;p&gt;This page is a template. It keeps the storefront from looking empty and provides a good presentation-ready structure for later customization.&lt;/p&gt;', 'Delivery Information', 'Demo delivery page template covering methods, timing, rates and tracking.', '', 'Delivery Information'),
(7, 2, 'Privacy Policy', '&lt;h2 id=&quot;overview&quot;&gt;Privacy Policy&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Details&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Important&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Privacy Policy&lt;/h2&gt;&lt;p&gt;We process personal data only to the extent needed to operate the store, place and fulfil orders, provide customer support, protect the service and meet legal requirements.&lt;/p&gt;&lt;p&gt;This may include contact, delivery, order and technical information. Data is retained only for as long as required for those purposes or by law.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Template:&lt;/strong&gt; before launch, add the data controller details, contact information, legal bases, retention periods and procedures for user rights.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;What to add before launch&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;real company details;&lt;/li&gt;&lt;li&gt;actual terms for your target market;&lt;/li&gt;&lt;li&gt;contact details, working hours and responsible contacts;&lt;/li&gt;&lt;li&gt;links to related store pages. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Important&lt;/h3&gt;&lt;p&gt;This page is a template. It keeps the storefront from looking empty and provides a good presentation-ready structure for later customization.&lt;/p&gt;', 'Privacy Policy', 'Basic privacy policy template for an online store.', '', 'Privacy Policy'),
(8, 2, 'Cookie Policy', '&lt;h2 id=&quot;overview&quot;&gt;Cookie Policy&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Details&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Important&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Cookie Policy&lt;/h2&gt;&lt;p&gt;This website uses cookies and similar technologies to operate the cart, sign-in, preferences, security and, where permitted, analytics.&lt;/p&gt;&lt;p&gt;Necessary cookies support essential store functions. Optional categories are used only according to consent settings.&lt;/p&gt;&lt;p&gt;🍪 Before launch, list the analytics, advertising and third-party cookie services actually used by your store.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;What to add before launch&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;real company details;&lt;/li&gt;&lt;li&gt;actual terms for your target market;&lt;/li&gt;&lt;li&gt;contact details, working hours and responsible contacts;&lt;/li&gt;&lt;li&gt;links to related store pages. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Important&lt;/h3&gt;&lt;p&gt;This page is a template. It keeps the storefront from looking empty and provides a good presentation-ready structure for later customization.&lt;/p&gt;', 'Cookie Policy', 'Demo cookie policy template for an online store.', '', 'Cookie Policy'),
(9, 2, 'Payment', '&lt;h2 id=&quot;overview&quot;&gt;Payment&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Details&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Important&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Payment Methods&lt;/h2&gt;&lt;p&gt;During checkout, customers see the payment methods available for their region and order. These may include card payment, bank transfer, cash on delivery or another configured payment option. 💳&lt;/p&gt;&lt;p&gt;Never send bank card details to a store manager by message or email. Online card payments should be completed on the secure page of the payment provider.&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Template:&lt;/strong&gt; keep only the payment methods that are actually enabled in your store.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;What to add before launch&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;real company details;&lt;/li&gt;&lt;li&gt;actual terms for your target market;&lt;/li&gt;&lt;li&gt;contact details, working hours and responsible contacts;&lt;/li&gt;&lt;li&gt;links to related store pages. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Important&lt;/h3&gt;&lt;p&gt;This page is a template. It keeps the storefront from looking empty and provides a good presentation-ready structure for later customization.&lt;/p&gt;', 'Payment', 'Demo page template describing payment methods for an online store.', '', 'Payment'),
(10, 2, 'Warranty & Returns', '&lt;h2 id=&quot;overview&quot;&gt;Warranty &amp; Returns&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#details&quot;&gt;Details&lt;/a&gt; · &lt;a href=&quot;#important&quot;&gt;Important&lt;/a&gt;&lt;/p&gt;&lt;h2&gt;Warranty &amp; Returns&lt;/h2&gt;&lt;p&gt;Warranty and return conditions depend on the product type, manufacturer and the laws that apply in the country of sale. Keep purchase documents and, where practical, the original contents and packaging.&lt;/p&gt;&lt;p&gt;If there is a problem, contact the store with the order number and a short description of the issue. We will explain the next steps. ↩️&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Template:&lt;/strong&gt; before launch, specify the real return period, exclusions, return address and claim procedure.&lt;/p&gt;&lt;h3 id=&quot;details&quot;&gt;What to add before launch&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;real company details;&lt;/li&gt;&lt;li&gt;actual terms for your target market;&lt;/li&gt;&lt;li&gt;contact details, working hours and responsible contacts;&lt;/li&gt;&lt;li&gt;links to related store pages. ✅&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;important&quot;&gt;Important&lt;/h3&gt;&lt;p&gt;This page is a template. It keeps the storefront from looking empty and provides a good presentation-ready structure for later customization.&lt;/p&gt;', 'Warranty & Returns', 'Demo template for warranty, exchange and return conditions.', '', 'Warranty & Returns');

-- --------------------------------------------------------
--
-- Table structure for table `oc_information_to_layout`
--

DROP TABLE IF EXISTS `oc_information_to_layout`;
CREATE TABLE `oc_information_to_layout` (
  `information_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `layout_id` int(11) NOT NULL,
  PRIMARY KEY (`information_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_information_to_store`
--

DROP TABLE IF EXISTS `oc_information_to_store`;
CREATE TABLE `oc_information_to_store` (
  `information_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`information_id`,`store_id`),
  KEY `store_information` (`store_id`,`information_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_information_to_store`
--

INSERT INTO `oc_information_to_store` (`information_id`, `store_id`) VALUES
(3, 0),
(4, 0),
(5, 0),
(6, 0),
(7, 0),
(8, 0),
(9, 0),
(10, 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_language`
--

DROP TABLE IF EXISTS `oc_language`;
CREATE TABLE `oc_language` (
  `language_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `code` varchar(5) NOT NULL,
  `locale` varchar(255) NOT NULL,
  `image` varchar(64) NOT NULL DEFAULT '',
  `directory` varchar(32) NOT NULL DEFAULT '',
  `url_prefix` varchar(32) NOT NULL DEFAULT '',
  `sort_order` int(3) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`language_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_language`
--

INSERT INTO `oc_language` (`language_id`, `name`, `code`, `locale`, `image`, `directory`, `url_prefix`, `sort_order`, `status`) VALUES
(1, 'Українська', 'uk-ua', 'uk_UA.UTF-8,uk_UA,uk-ua,uk,ukrainian', 'uk-ua.png', 'uk-ua', '', 1, 1),
(2, 'English', 'en-gb', 'en-US,en_US.UTF-8,en_US,en-gb,english', 'en-gb.png', 'en-gb', 'en', 2, 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_layout`
--

DROP TABLE IF EXISTS `oc_layout`;
CREATE TABLE `oc_layout` (
  `layout_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`layout_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_layout`
--

INSERT INTO `oc_layout` (`layout_id`, `name`) VALUES
(1, 'Головна'),
(2, 'Товар'),
(3, 'Категорія'),
(4, 'За замовчуванням'),
(5, 'Список виробників'),
(6, 'Обліковий запис'),
(7, 'Оформлення замовлення'),
(8, 'Контакти'),
(9, 'Карта сайту'),
(10, 'Партнерська програма'),
(11, 'Інформація (статті)'),
(12, 'Порівняння товарів'),
(13, 'Пошук'),
(14, 'Блог'),
(15, 'Категорії Блогу'),
(16, 'Статті Блогу'),
(17, 'Сторінка виробника'),
(18, 'Акції');

-- --------------------------------------------------------
--
-- Table structure for table `oc_layout_module`
--

DROP TABLE IF EXISTS `oc_layout_module`;
CREATE TABLE `oc_layout_module` (
  `layout_module_id` int(11) NOT NULL AUTO_INCREMENT,
  `layout_id` int(11) NOT NULL,
  `code` varchar(64) NOT NULL,
  `position` varchar(14) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`layout_module_id`),
  KEY `layout_position` (`layout_id`,`position`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_layout_module`
--

INSERT INTO `oc_layout_module` (`layout_module_id`, `layout_id`, `code`, `position`, `sort_order`) VALUES
(69, 10, 'account', 'column_right', 1),
(68, 6, 'account', 'column_right', 1),
(94, 1, 'manufacturer_wall.38', 'content_top', 6),
(66, 1, 'slideshow.27', 'content_top', 0),
(65, 1, 'featured.28', 'content_top', 3),
(83, 3, 'banner.30', 'column_left', 1),
(82, 3, 'category', 'column_left', 0),
(74, 14, 'blog_category', 'column_left', 0),
(75, 14, 'blog_featured.33', 'column_left', 1),
(76, 14, 'blog_latest.32', 'content_bottom', 0),
(77, 15, 'blog_category', 'column_left', 0),
(78, 15, 'blog_latest.32', 'column_left', 1),
(79, 15, 'blog_featured.33', 'content_bottom', 0),
(80, 16, 'blog_category', 'column_left', 0),
(81, 16, 'blog_featured.33', 'column_left', 1),
(84, 3, 'featured_article.34', 'column_left', 2),
(85, 3, 'featured_product.35', 'column_left', 3),
(86, 17, 'featured_article.34', 'column_left', 0),
(87, 17, 'featured_product.35', 'column_left', 1),
(88, 2, 'featured_article.34', 'content_bottom', 0),
(89, 2, 'featured_product.35', 'content_bottom', 1),
(90, 1, 'html.36', 'content_top', 2),
(91, 1, 'category_wall.37', 'content_top', 1),
(92, 1, 'latest.39', 'content_top', 4),
(93, 1, 'popular.40', 'content_top', 5),
(95, 1, 'blog_latest.32', 'content_bottom', 0),
(97, 2, 'recently_viewed.43', 'content_bottom', 2),
(98, 3, 'codecart_form.46', 'content_bottom', 2),
(100, 8, 'codecart_form.44', 'content_bottom', 0),
(101, 18, 'featured_product.35', 'content_bottom', 0),
(102, 18, 'featured_article.34', 'content_bottom', 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_layout_route`
--

DROP TABLE IF EXISTS `oc_layout_route`;
CREATE TABLE `oc_layout_route` (
  `layout_route_id` int(11) NOT NULL AUTO_INCREMENT,
  `layout_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `route` varchar(64) NOT NULL,
  PRIMARY KEY (`layout_route_id`),
  KEY `store_route` (`store_id`,`route`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_layout_route`
--

INSERT INTO `oc_layout_route` (`layout_route_id`, `layout_id`, `store_id`, `route`) VALUES
(38, 6, 0, 'account/%'),
(17, 10, 0, 'affiliate/%'),
(44, 3, 0, 'product/category'),
(42, 1, 0, 'common/home'),
(20, 2, 0, 'product/product'),
(24, 11, 0, 'information/information'),
(23, 7, 0, 'checkout/%'),
(31, 8, 0, 'information/contact'),
(32, 9, 0, 'information/sitemap'),
(34, 4, 0, ''),
(45, 5, 0, 'product/manufacturer'),
(52, 12, 0, 'product/compare'),
(53, 13, 0, 'product/search'),
(57, 14, 0, 'blog/latest'),
(58, 15, 0, 'blog/category'),
(56, 16, 0, 'blog/article'),
(63, 17, 0, 'product/manufacturer/info'),
(64, 18, 0, 'product/special');

-- --------------------------------------------------------
--
-- Table structure for table `oc_length_class`
--

DROP TABLE IF EXISTS `oc_length_class`;
CREATE TABLE `oc_length_class` (
  `length_class_id` int(11) NOT NULL AUTO_INCREMENT,
  `value` decimal(15,8) NOT NULL,
  PRIMARY KEY (`length_class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_length_class`
--

INSERT INTO `oc_length_class` (`length_class_id`, `value`) VALUES
(1, '1.00000000'),
(2, '10.00000000'),
(3, '0.39370000');

-- --------------------------------------------------------
--
-- Table structure for table `oc_length_class_description`
--

DROP TABLE IF EXISTS `oc_length_class_description`;
CREATE TABLE `oc_length_class_description` (
  `length_class_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(32) NOT NULL,
  `unit` varchar(4) NOT NULL,
  PRIMARY KEY (`length_class_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_length_class_description`
--

INSERT INTO `oc_length_class_description` (`length_class_id`, `language_id`, `title`, `unit`) VALUES
(1, 1, 'Сантиметр', 'см'),
(1, 2, 'Centimeter', 'cm'),
(2, 1, 'Міліметр', 'мм'),
(2, 2, 'Millimeter', 'mm'),
(3, 1, 'Дюйм', 'in'),
(3, 2, 'Inch', 'in');

-- --------------------------------------------------------
--
-- Table structure for table `oc_location`
--

DROP TABLE IF EXISTS `oc_location`;
CREATE TABLE `oc_location` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `address` text NOT NULL,
  `telephone` varchar(32) NOT NULL,
  `fax` varchar(32) NOT NULL,
  `geocode` varchar(32) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `open` text NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY (`location_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_manufacturer`
--

DROP TABLE IF EXISTS `oc_manufacturer`;
CREATE TABLE `oc_manufacturer` (
  `manufacturer_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(3) NOT NULL,
`noindex` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`manufacturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_manufacturer`
--

INSERT INTO `oc_manufacturer` (`manufacturer_id`, `name`, `image`, `sort_order`, `noindex`) VALUES
(5, 'HTC', 'catalog/demo/manufacturer/htc.webp', 0, 1),
(6, 'Palm', 'catalog/demo/palm_logo.webp', 0, 1),
(7, 'Hewlett-Packard', 'catalog/demo/manufacturer/hp.webp', 0, 1),
(8, 'Apple', 'catalog/demo/apple_logo.webp', 1, 1),
(9, 'Canon', 'catalog/demo/canon_logo.webp', 0, 1),
(10, 'Sony', 'catalog/demo/sony_logo.webp', 0, 1),
(11, 'Dell', 'catalog/demo/manufacturer/dell.webp', 0, 1),
(12, 'Samsung', 'catalog/demo/manufacturer/samsung.webp', 0, 1),
(13, 'Nintendo', 'catalog/demo/manufacturer/nintendo.webp', 0, 1),
(14, 'Nikon', 'catalog/demo/nikon_d300_1.webp', 5, 1),
(15, 'CodeCart PRO', 'catalog/codecartpro.webp', 99, 1);

-- --------------------------------------------------------

--
-- Table structure for table `oc_manufacturer_description`
--

DROP TABLE IF EXISTS `oc_manufacturer_description`;
CREATE TABLE `oc_manufacturer_description` (
`manufacturer_id` int(11) NOT NULL DEFAULT '0',
`language_id` int(11) NOT NULL DEFAULT '0',
`description` text NOT NULL,
`description3` text NOT NULL,
`meta_description` varchar(255) NOT NULL,
`meta_keyword` varchar(255) NOT NULL,
`meta_title` varchar(255) NOT NULL,
`meta_h1` varchar(255) NOT NULL,
PRIMARY KEY (`manufacturer_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_manufacturer_description`
--

INSERT INTO `oc_manufacturer_description` (`manufacturer_id`, `language_id`, `description`, `description3`, `meta_description`, `meta_keyword`, `meta_title`, `meta_h1`) VALUES
(5, 1, '&lt;h2&gt;HTC&lt;/h2&gt;&lt;p&gt;Демонстраційна сторінка виробника HTC. Тут магазин може показувати коротку інформацію про бренд, пов’язаний асортимент і товари виробника. Перед запуском замініть цей текст на офіційний опис бренду або власну довідкову інформацію.&lt;/p&gt;', '', 'HTC — товари виробника', '', 'HTC — товари виробника', 'HTC'),
(5, 2, '&lt;h2&gt;HTC&lt;/h2&gt;&lt;p&gt;Demo manufacturer page for HTC. A real store can use this area for a short brand introduction, related assortment and manufacturer products. Replace this text with official or store-approved brand information before launch.&lt;/p&gt;', '', 'HTC manufacturer products', '', 'HTC manufacturer products', 'HTC'),
(6, 1, '&lt;h2&gt;Palm&lt;/h2&gt;&lt;p&gt;Демонстраційна сторінка виробника Palm. Тут магазин може показувати коротку інформацію про бренд, пов’язаний асортимент і товари виробника. Перед запуском замініть цей текст на офіційний опис бренду або власну довідкову інформацію.&lt;/p&gt;', '', 'Palm — товари виробника', '', 'Palm — товари виробника', 'Palm'),
(6, 2, '&lt;h2&gt;Palm&lt;/h2&gt;&lt;p&gt;Demo manufacturer page for Palm. A real store can use this area for a short brand introduction, related assortment and manufacturer products. Replace this text with official or store-approved brand information before launch.&lt;/p&gt;', '', 'Palm manufacturer products', '', 'Palm manufacturer products', 'Palm'),
(7, 1, '&lt;h2&gt;Hewlett-Packard&lt;/h2&gt;&lt;p&gt;Демонстраційна сторінка виробника Hewlett-Packard. Тут магазин може показувати коротку інформацію про бренд, пов’язаний асортимент і товари виробника. Перед запуском замініть цей текст на офіційний опис бренду або власну довідкову інформацію.&lt;/p&gt;', '', 'Hewlett-Packard — товари виробника', '', 'Hewlett-Packard — товари виробника', 'Hewlett-Packard'),
(7, 2, '&lt;h2&gt;Hewlett-Packard&lt;/h2&gt;&lt;p&gt;Demo manufacturer page for Hewlett-Packard. A real store can use this area for a short brand introduction, related assortment and manufacturer products. Replace this text with official or store-approved brand information before launch.&lt;/p&gt;', '', 'Hewlett-Packard manufacturer products', '', 'Hewlett-Packard manufacturer products', 'Hewlett-Packard'),
(8, 1, '&lt;h2&gt;Apple&lt;/h2&gt;&lt;p&gt;Демонстраційна сторінка виробника Apple. Тут магазин може показувати коротку інформацію про бренд, пов’язаний асортимент і товари виробника. Перед запуском замініть цей текст на офіційний опис бренду або власну довідкову інформацію.&lt;/p&gt;', '', 'Apple — товари виробника', '', 'Apple — товари виробника', 'Apple'),
(8, 2, '&lt;h2&gt;Apple&lt;/h2&gt;&lt;p&gt;Demo manufacturer page for Apple. A real store can use this area for a short brand introduction, related assortment and manufacturer products. Replace this text with official or store-approved brand information before launch.&lt;/p&gt;', '', 'Apple manufacturer products', '', 'Apple manufacturer products', 'Apple'),
(9, 1, '&lt;h2&gt;Canon&lt;/h2&gt;&lt;p&gt;Демонстраційна сторінка виробника Canon. Тут магазин може показувати коротку інформацію про бренд, пов’язаний асортимент і товари виробника. Перед запуском замініть цей текст на офіційний опис бренду або власну довідкову інформацію.&lt;/p&gt;', '', 'Canon — товари виробника', '', 'Canon — товари виробника', 'Canon'),
(9, 2, '&lt;h2&gt;Canon&lt;/h2&gt;&lt;p&gt;Demo manufacturer page for Canon. A real store can use this area for a short brand introduction, related assortment and manufacturer products. Replace this text with official or store-approved brand information before launch.&lt;/p&gt;', '', 'Canon manufacturer products', '', 'Canon manufacturer products', 'Canon'),
(10, 1, '&lt;h2&gt;Sony&lt;/h2&gt;&lt;p&gt;Демонстраційна сторінка виробника Sony. Тут магазин може показувати коротку інформацію про бренд, пов’язаний асортимент і товари виробника. Перед запуском замініть цей текст на офіційний опис бренду або власну довідкову інформацію.&lt;/p&gt;', '', 'Sony — товари виробника', '', 'Sony — товари виробника', 'Sony'),
(10, 2, '&lt;h2&gt;Sony&lt;/h2&gt;&lt;p&gt;Demo manufacturer page for Sony. A real store can use this area for a short brand introduction, related assortment and manufacturer products. Replace this text with official or store-approved brand information before launch.&lt;/p&gt;', '', 'Sony manufacturer products', '', 'Sony manufacturer products', 'Sony'),
(11, 1, '&lt;h2&gt;Dell&lt;/h2&gt;&lt;p&gt;Dell — демонстраційний виробник для перевірки сторінок брендів, фільтрації та пов’язаного асортименту. У робочому магазині замініть цей текст на погоджений опис бренду, гарантійні умови та актуальні категорії товарів.&lt;/p&gt;', '', 'Dell — товари виробника', '', 'Dell — товари виробника', 'Dell'),
(11, 2, '&lt;h2&gt;Dell&lt;/h2&gt;&lt;p&gt;Dell is included as a demo manufacturer for testing brand pages, filtering and related catalog content. Replace this copy with approved brand information, warranty notes and the relevant product ranges before launch.&lt;/p&gt;', '', 'Dell manufacturer products', '', 'Dell manufacturer products', 'Dell'),
(12, 1, '&lt;h2&gt;Samsung&lt;/h2&gt;&lt;p&gt;Samsung — демонстраційний виробник для перевірки сторінок брендів і товарів електроніки. У реальному магазині додайте офіційний опис, актуальний асортимент і умови гарантії.&lt;/p&gt;', '', 'Samsung — товари виробника', '', 'Samsung — товари виробника', 'Samsung'),
(12, 2, '&lt;h2&gt;Samsung&lt;/h2&gt;&lt;p&gt;Samsung is included as a demo manufacturer for testing brand and electronics catalog pages. Add approved brand copy, current product ranges and warranty information before launch.&lt;/p&gt;', '', 'Samsung manufacturer products', '', 'Samsung manufacturer products', 'Samsung'),
(13, 1, '&lt;h2&gt;Nintendo&lt;/h2&gt;&lt;p&gt;Nintendo — демонстраційний виробник для презентації сторінок брендів і мультимедійного асортименту. Перед запуском замініть демонстраційний текст на офіційну або погоджену інформацію.&lt;/p&gt;', '', 'Nintendo — товари виробника', '', 'Nintendo — товари виробника', 'Nintendo'),
(13, 2, '&lt;h2&gt;Nintendo&lt;/h2&gt;&lt;p&gt;Nintendo is included as a demo manufacturer for showcasing brand pages and multimedia catalog content. Replace this demo text with official or approved information before launch.&lt;/p&gt;', '', 'Nintendo manufacturer products', '', 'Nintendo manufacturer products', 'Nintendo'),
(14, 1, '&lt;h2&gt;Nikon&lt;/h2&gt;&lt;p&gt;Nikon — демонстраційний виробник для перевірки сторінок брендів і фототехніки. Перед запуском магазину замініть демонстраційний текст на погоджену інформацію бренду.&lt;/p&gt;', '', 'Nikon — товари виробника', '', 'Nikon — товари виробника', 'Nikon'),
(14, 2, '&lt;h2&gt;Nikon&lt;/h2&gt;&lt;p&gt;Nikon is included as a demo manufacturer for testing brand and camera catalog pages. Replace this demo copy with approved brand information before launch.&lt;/p&gt;', '', 'Nikon manufacturer products', '', 'Nikon manufacturer products', 'Nikon'),
(15, 1, '&lt;h2&gt;CodeCart PRO&lt;/h2&gt;&lt;p&gt;Службовий демонстраційний бренд для тестових товарів CodeCart PRO Demo Store. Не використовується як виробник у робочому магазині без окремого налаштування.&lt;/p&gt;', '', 'CodeCart PRO — демонстраційні товари', '', 'CodeCart PRO — демонстраційні товари', 'CodeCart PRO'),
(15, 2, '&lt;h2&gt;CodeCart PRO&lt;/h2&gt;&lt;p&gt;Internal demo brand used for test products in CodeCart PRO Demo Store. It is not intended to be used as a storefront manufacturer without explicit configuration.&lt;/p&gt;', '', 'CodeCart PRO demo products', '', 'CodeCart PRO demo products', 'CodeCart PRO');
-- --------------------------------------------------------

--
-- Table structure for table `oc_manufacturer_to_store`
--

DROP TABLE IF EXISTS `oc_manufacturer_to_store`;
CREATE TABLE `oc_manufacturer_to_store` (
  `manufacturer_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`manufacturer_id`,`store_id`),
  KEY `store_manufacturer` (`store_id`,`manufacturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_manufacturer_to_store`
--

INSERT INTO `oc_manufacturer_to_store` (`manufacturer_id`, `store_id`) VALUES
(5, 0),
(6, 0),
(7, 0),
(8, 0),
(9, 0),
(10, 0),
(11, 0),
(12, 0),
(13, 0),
(14, 0),
(15, 0);

-- --------------------------------------------------------

--
-- Table structure for table `oc_manufacturer_to_layout`
--

DROP TABLE IF EXISTS `oc_manufacturer_to_layout`;
CREATE TABLE `oc_manufacturer_to_layout` (
`manufacturer_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL,
`layout_id` int(11) NOT NULL,
PRIMARY KEY (`manufacturer_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oc_marketing`
--

DROP TABLE IF EXISTS `oc_marketing`;
CREATE TABLE `oc_marketing` (
  `marketing_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `description` text NOT NULL,
  `code` varchar(64) NOT NULL,
  `clicks` int(5) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`marketing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_modification`
--

DROP TABLE IF EXISTS `oc_modification`;
CREATE TABLE `oc_modification` (
  `modification_id` int(11) NOT NULL AUTO_INCREMENT,
  `extension_install_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `code` varchar(64) NOT NULL,
  `author` varchar(64) NOT NULL,
  `version` varchar(32) NOT NULL,
  `link` varchar(255) NOT NULL,
  `xml` mediumtext NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`modification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `oc_modification_backup`
--

DROP TABLE IF EXISTS `oc_modification_backup`;
CREATE TABLE `oc_modification_backup` (
`backup_id` int(11) NOT NULL AUTO_INCREMENT,
`modification_id` int(11) NOT NULL,
`code` varchar(64) NOT NULL,
`xml` mediumtext NOT NULL,
`date_added` datetime NOT NULL,
PRIMARY KEY (`backup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `oc_module`
--

DROP TABLE IF EXISTS `oc_module`;
CREATE TABLE `oc_module` (
  `module_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `code` varchar(32) NOT NULL,
  `setting` text NOT NULL,
  PRIMARY KEY (`module_id`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_module`
--

INSERT INTO `oc_module` (`module_id`, `name`, `code`, `setting`) VALUES
(30, 'Категорія', 'banner', '{"name":"Категорія","banner_id":"6","width":"182","height":"182","status":"1"}'),
(29, 'Головна сторінка', 'carousel', '{"name":"Головна сторінка","banner_id":"8","width":"130","height":"100","status":"1"}'),
(28, 'Головна сторінка', 'featured', '{"name":"Головна сторінка","product":["43","40","42","30"],"limit":"4","width":"200","height":"200","status":"1"}'),
(27, 'Головна сторінка', 'slideshow', '{"name":"Головна сторінка","banner_id":"7","width":"1140","height":"380","status":"1"}'),
(31, 'Банер 1', 'banner', '{"name":"Банер 1","banner_id":"6","width":"182","height":"182","status":"1"}'),
(32, 'Останні статті', 'blog_latest', '{"name":"Останні статті","limit":"4","width":"200","height":"200","status":"1"}'),
(33, 'Рекомендовані статті', 'blog_featured', '{"name":"Рекомендовані статті","article_name":"","article":["120","123","125","124"],"limit":"4","width":"200","height":"200","status":"1"}'),
(34, 'Рекомендовані статті у товарі, категорії та виробнику', 'featured_article', '{"name":"Рекомендовані статті у товарі, категорії та виробнику","limit":"4","width":"200","height":"200","display_mode":"carousel","columns_desktop":"4","columns_tablet":"3","columns_mobile":"2","autoplay":"0","autoplay_delay":"5000","show_arrows":"1","show_dots":"1","loop":"1","carousel_step":"item","status":"1"}'),
(35, 'Рекомендовані товари у товарі, категорії та виробнику', 'featured_product', '{"name":"Рекомендовані товари у товарі, категорії та виробнику","limit":"4","width":"200","height":"200","display_mode":"carousel","columns_desktop":"4","columns_tablet":"3","columns_mobile":"2","autoplay":"0","autoplay_delay":"5000","show_arrows":"1","show_dots":"1","loop":"1","carousel_step":"item","status":"1"}'),
(36, 'Головна — вступ', 'html', '{"name": "Головна — вступ", "module_description": {"1": {"title": "", "description": "<div class=\\"well\\" style=\\"margin-bottom:0;\\"><h2>CodeCart PRO Demo Store</h2><p>Готовий демонстраційний магазин для України: категорії з ілюстраціями, товари з опціями, блог, інформаційні сторінки, сучасна мобільна вітрина та продумане наповнення. 🇺🇦</p><p><strong>Що можна перевірити одразу:</strong> каталог, картки товарів, SEO-структуру, опції, блог, сторінки доставки та оплати, адаптивність і базові сценарії покупки.</p><p><a href=\\"index.php?route=product/category&path=20\\">Перейти в каталог</a> · <a href=\\"index.php?route=blog/latest\\">Відкрити блог</a> · <a href=\\"index.php?route=information/contact\\">Контакти</a></p></div>"}, "2": {"title": "", "description": "<div class=\\"well\\" style=\\"margin-bottom:0;\\"><h2>CodeCart PRO Demo Store</h2><p>A ready-made demo store for Ukraine with illustrated categories, option-rich products, a blog, information pages, a modern mobile storefront and presentation-ready content. 🇺🇦</p><p><strong>What you can test immediately:</strong> the catalog, product pages, SEO structure, options, blog, delivery and payment pages, responsiveness and core shopping flows.</p><p><a href=\\"index.php?route=product/category&path=20\\">Open catalog</a> · <a href=\\"index.php?route=blog/latest\\">Open blog</a> · <a href=\\"index.php?route=information/contact\\">Contact us</a></p></div>"}}, "status": "1"}'),
(37, 'Головна — категорії', 'category_wall', '{"name": "Головна — категорії", "heading": {"1": "Популярні категорії", "2": "Popular Categories"}, "source": "top", "limit": "8", "subcategory_limit": "4", "display_mode": "carousel", "columns_desktop": "4", "columns_tablet": "3", "columns_mobile": "2", "mobile_peek": "0", "autoplay": "1", "autoplay_delay": "4500", "show_arrows": "1", "show_dots": "1", "loop": "1", "carousel_step": "page", "width": "320", "height": "220", "width_mobile": "240", "height_mobile": "180", "image_fit": "cover", "image_mode": "image_text", "show_count": "1", "hide_empty": "0", "sort": "sort_order", "status": "1"}'),
(38, 'Головна — виробники', 'manufacturer_wall', '{"name": "Головна — виробники", "heading": {"1": "Бренди та виробники", "2": "Brands & Manufacturers"}, "source": "all", "limit": "12", "display_mode": "carousel", "columns_desktop": "6", "columns_tablet": "4", "columns_mobile": "2", "autoplay": "1", "autoplay_delay": "2000", "show_arrows": "1", "show_dots": "1", "loop": "1", "carousel_step": "item", "width": "180", "height": "90", "image_fit": "contain", "show_name": "1", "show_image": "1", "show_count": "0", "hide_empty": "0", "hide_without_image": "0", "show_heading": "1", "sort": "sort_order", "status": "1"}'),
(39, 'Головна — нові товари', 'latest', '{"name": "Головна — нові товари", "heading": {"1": "Нові товари", "2": "Latest Products"}, "limit": "6", "width": "220", "height": "220", "display_mode": "carousel", "columns_desktop": "3", "columns_tablet": "2", "columns_mobile": "2", "autoplay": "0", "show_arrows": "1", "show_dots": "1", "loop": "1", "carousel_step": "item", "status": "1"}'),
(40, 'Головна — популярні товари', 'popular', '{"name": "Головна — популярні товари", "heading": {"1": "Популярні товари", "2": "Popular Products"}, "limit": "6", "width": "220", "height": "220", "display_mode": "carousel", "columns_desktop": "3", "columns_tablet": "2", "columns_mobile": "2", "autoplay": "0", "show_arrows": "1", "show_dots": "1", "loop": "1", "carousel_step": "item", "status": "1"}'),
(42, 'Категорія — підкатегорії', 'category_wall', '{"name": "Категорія — підкатегорії", "heading": {"1": "Підкатегорії", "2": "Subcategories"}, "source": "current", "limit": "12", "subcategory_limit": "4", "display_mode": "grid", "display_mode_mobile": "inherit", "columns_desktop": "6", "columns_tablet": "3", "columns_mobile": "2", "mobile_peek": "0", "autoplay": "0", "show_arrows": "0", "show_dots": "0", "loop": "0", "carousel_step": "page", "width": "320", "height": "220", "width_mobile": "220", "height_mobile": "150", "image_fit": "contain", "image_mode": "image_text", "show_count": "1", "hide_empty": "0", "sort": "sort_order", "status": "1"}'),
(43, 'Товар — нещодавно переглянуті', 'recently_viewed', '{"name": "Товар — нещодавно переглянуті", "heading": {"1": "Ви нещодавно переглядали", "2": "Recently Viewed"}, "limit": "6", "width": "220", "height": "220", "display_mode": "carousel", "columns_desktop": "4", "columns_tablet": "2", "columns_mobile": "2", "autoplay": "0", "show_arrows": "1", "show_dots": "1", "loop": "1", "carousel_step": "item", "status": "1"}'),
(44, 'Форма — зворотний дзвінок', 'codecart_form', '{"name":"Форма — зворотний дзвінок","form_id":1,"mode":"inline","button_text":{"1":"Замовити дзвінок","2":"Request callback"},"status":1}'),
(45, 'Форма — питання про товар', 'codecart_form', '{"name":"Форма — питання про товар","form_id":2,"mode":"button","button_text":{"1":"Поставити питання","2":"Ask a question"},"status":1}'),
(46, 'Форма — допомога з підбором', 'codecart_form', '{"name":"Форма — допомога з підбором","form_id":3,"mode":"button","button_text":{"1":"Допомога з підбором","2":"Selection help"},"status":1}');

-- --------------------------------------------------------
--
-- Table structure for table `oc_option`
--

DROP TABLE IF EXISTS `oc_option`;
CREATE TABLE `oc_option` (
  `option_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_option`
--

INSERT INTO `oc_option` (`option_id`, `type`, `sort_order`) VALUES
(1, 'radio', 1),
(2, 'checkbox', 2),
(4, 'text', 3),
(5, 'select', 4),
(6, 'textarea', 5),
(7, 'file', 6),
(8, 'date', 7),
(9, 'time', 8),
(10, 'datetime', 9),
(11, 'select', 10),
(12, 'date', 11);

-- --------------------------------------------------------
--
-- Table structure for table `oc_option_description`
--

DROP TABLE IF EXISTS `oc_option_description`;
CREATE TABLE `oc_option_description` (
  `option_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`option_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_option_description`
--

INSERT INTO `oc_option_description` (`option_id`, `language_id`, `name`) VALUES
(1, 1, 'Перемикач'),
(2, 1, 'Прапорець'),
(4, 1, 'Текст'),
(6, 1, 'Текстова область'),
(8, 1, 'Дата'),
(7, 1, 'Файл'),
(5, 1, 'Список'),
(9, 1, 'Час'),
(10, 1, 'Дата та час'),
(12, 1, 'Дата доставки'),
(11, 1, 'Розмір'),
(2, 2, 'Checkbox'),
(8, 2, 'Date'),
(10, 2, 'Date &amp; Time'),
(12, 2, 'Delivery Date'),
(7, 2, 'File'),
(1, 2, 'Radio'),
(5, 2, 'Select'),
(11, 2, 'Size'),
(4, 2, 'Text'),
(6, 2, 'Textarea'),
(9, 2, 'Time');

-- --------------------------------------------------------
--
-- Table structure for table `oc_option_value`
--

DROP TABLE IF EXISTS `oc_option_value`;
CREATE TABLE `oc_option_value` (
  `option_value_id` int(11) NOT NULL AUTO_INCREMENT,
  `option_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`option_value_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_option_value`
--

INSERT INTO `oc_option_value` (`option_value_id`, `option_id`, `image`, `sort_order`) VALUES
(43, 1, '', 3),
(32, 1, '', 1),
(45, 2, '', 4),
(44, 2, '', 3),
(42, 5, '', 4),
(41, 5, '', 3),
(39, 5, '', 1),
(40, 5, '', 2),
(31, 1, '', 2),
(23, 2, '', 1),
(24, 2, '', 2),
(46, 11, '', 1),
(47, 11, '', 2),
(48, 11, '', 3);

-- --------------------------------------------------------
--
-- Table structure for table `oc_option_value_description`
--

DROP TABLE IF EXISTS `oc_option_value_description`;
CREATE TABLE `oc_option_value_description` (
  `option_value_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`option_value_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_option_value_description`
--

INSERT INTO `oc_option_value_description` (`option_value_id`, `language_id`, `option_id`, `name`) VALUES
(43, 1, 1, 'Великий'),
(43, 2, 1, 'Large'),
(32, 1, 1, 'Маленький'),
(32, 2, 1, 'Small'),
(45, 1, 2, 'Прапорець 4'),
(45, 2, 2, 'Checkbox 4'),
(44, 1, 2, 'Прапорець 3'),
(44, 2, 2, 'Checkbox 3'),
(31, 1, 1, 'Середній'),
(31, 2, 1, 'Medium'),
(42, 1, 5, 'Жовтий'),
(42, 2, 5, 'Yellow'),
(41, 1, 5, 'Зелений'),
(41, 2, 5, 'Green'),
(39, 1, 5, 'Червоний'),
(39, 2, 5, 'Red'),
(40, 1, 5, 'Синій'),
(40, 2, 5, 'Blue'),
(23, 1, 2, 'Прапорець 1'),
(23, 2, 2, 'Checkbox 1'),
(24, 1, 2, 'Прапорець 2'),
(24, 2, 2, 'Checkbox 2'),
(48, 1, 11, 'Великий'),
(48, 2, 11, 'Large'),
(47, 1, 11, 'Середній'),
(47, 2, 11, 'Medium'),
(46, 1, 11, 'Маленький'),
(46, 2, 11, 'Small');

-- --------------------------------------------------------
--
-- Table structure for table `oc_order`
--

DROP TABLE IF EXISTS `oc_order`;
CREATE TABLE `oc_order` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_no` int(11) NOT NULL DEFAULT '0',
  `invoice_prefix` varchar(26) NOT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `store_name` varchar(64) NOT NULL,
  `store_url` varchar(255) NOT NULL,
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `customer_group_id` int(11) NOT NULL DEFAULT '0',
  `firstname` varchar(32) NOT NULL,
  `lastname` varchar(32) NOT NULL,
  `email` varchar(96) NOT NULL,
  `telephone` varchar(32) NOT NULL,
  `fax` varchar(32) NOT NULL,
  `custom_field` text NOT NULL,
  `payment_firstname` varchar(32) NOT NULL,
  `payment_lastname` varchar(32) NOT NULL,
  `payment_company` varchar(60) NOT NULL,
  `payment_address_1` varchar(128) NOT NULL,
  `payment_address_2` varchar(128) NOT NULL,
  `payment_city` varchar(128) NOT NULL,
  `payment_postcode` varchar(10) NOT NULL,
  `payment_country` varchar(128) NOT NULL,
  `payment_country_id` int(11) NOT NULL,
  `payment_zone` varchar(128) NOT NULL,
  `payment_zone_id` int(11) NOT NULL,
  `payment_address_format` text NOT NULL,
  `payment_custom_field` text NOT NULL,
  `payment_method` varchar(128) NOT NULL,
  `payment_code` varchar(128) NOT NULL,
  `shipping_firstname` varchar(32) NOT NULL,
  `shipping_lastname` varchar(32) NOT NULL,
  `shipping_company` varchar(60) NOT NULL,
  `shipping_address_1` varchar(128) NOT NULL,
  `shipping_address_2` varchar(128) NOT NULL,
  `shipping_city` varchar(128) NOT NULL,
  `shipping_postcode` varchar(10) NOT NULL,
  `shipping_country` varchar(128) NOT NULL,
  `shipping_country_id` int(11) NOT NULL,
  `shipping_zone` varchar(128) NOT NULL,
  `shipping_zone_id` int(11) NOT NULL,
  `shipping_address_format` text NOT NULL,
  `shipping_custom_field` text NOT NULL,
  `shipping_method` varchar(128) NOT NULL,
  `shipping_code` varchar(128) NOT NULL,
  `comment` text NOT NULL,
  `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `order_status_id` int(11) NOT NULL DEFAULT '0',
  `affiliate_id` int(11) NOT NULL,
  `commission` decimal(15,4) NOT NULL,
  `marketing_id` int(11) NOT NULL,
  `tracking` varchar(64) NOT NULL,
  `language_id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `currency_value` decimal(15,8) NOT NULL DEFAULT '1.00000000',
  `ip` varchar(45) NOT NULL,
  `forwarded_ip` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `accept_language` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`order_id`),
  KEY `order_status_id` (`order_status_id`),
  KEY `customer_id` (`customer_id`),
  KEY `date_added` (`date_added`),
  KEY `date_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_history`
--

DROP TABLE IF EXISTS `oc_order_history`;
CREATE TABLE `oc_order_history` (
  `order_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `order_status_id` int(11) NOT NULL,
  `notify` tinyint(1) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`order_history_id`),
  KEY `order_date` (`order_id`,`date_added`),
  KEY `order_status_id` (`order_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_option`
--

DROP TABLE IF EXISTS `oc_order_option`;
CREATE TABLE `oc_order_option` (
  `order_option_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `order_product_id` int(11) NOT NULL,
  `product_option_id` int(11) NOT NULL,
  `product_option_value_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `type` varchar(32) NOT NULL,
  PRIMARY KEY (`order_option_id`),
  KEY `order_product` (`order_id`,`order_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_product`
--

DROP TABLE IF EXISTS `oc_order_product`;
CREATE TABLE `oc_order_product` (
  `order_product_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `model` varchar(64) NOT NULL,
  `sku` varchar(64) NOT NULL DEFAULT '',
  `quantity` int(4) NOT NULL,
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `tax` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `reward` int(8) NOT NULL,
  PRIMARY KEY (`order_product_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_order_download`;
CREATE TABLE `oc_order_download` (
  `order_download_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `order_product_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `download_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `filename` varchar(160) NOT NULL,
  `mask` varchar(128) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`order_download_id`),
  UNIQUE KEY `order_product_download` (`order_product_id`,`download_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `download_id` (`download_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_recurring`
--

DROP TABLE IF EXISTS `oc_order_recurring`;
CREATE TABLE `oc_order_recurring` (
  `order_recurring_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_quantity` int(11) NOT NULL,
  `recurring_id` int(11) NOT NULL,
  `recurring_name` varchar(255) NOT NULL,
  `recurring_description` varchar(255) NOT NULL,
  `recurring_frequency` varchar(25) NOT NULL,
  `recurring_cycle` smallint(6) NOT NULL,
  `recurring_duration` smallint(6) NOT NULL,
  `recurring_price` decimal(10,4) NOT NULL,
  `trial` tinyint(1) NOT NULL,
  `trial_frequency` varchar(25) NOT NULL,
  `trial_cycle` smallint(6) NOT NULL,
  `trial_duration` smallint(6) NOT NULL,
  `trial_price` decimal(10,4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`order_recurring_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_recurring_transaction`
--

DROP TABLE IF EXISTS `oc_order_recurring_transaction`;
CREATE TABLE `oc_order_recurring_transaction` (
  `order_recurring_transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_recurring_id` int(11) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(10,4) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`order_recurring_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_shipment`
--

DROP TABLE IF EXISTS `oc_order_shipment`;
CREATE TABLE `oc_order_shipment` (
  `order_shipment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `shipping_courier_id` varchar(255) NOT NULL DEFAULT '',
  `tracking_number` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`order_shipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_shipping_courier`
--

DROP TABLE IF EXISTS `oc_shipping_courier`;
CREATE TABLE `oc_shipping_courier` (
  `shipping_courier_id` int(11) NOT NULL,
  `shipping_courier_code` varchar(255) NOT NULL DEFAULT '',
  `shipping_courier_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`shipping_courier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_shipping_courier`
--

INSERT INTO `oc_shipping_courier` (`shipping_courier_id`, `shipping_courier_code`, `shipping_courier_name`) VALUES
  (1, 'dhl', 'DHL'),
  (2, 'fedex', 'Fedex'),
  (3, 'ups', 'UPS'),
  (4, 'royal-mail', 'Royal Mail'),
  (5, 'usps', 'United States Postal Service'),
  (6, 'auspost', 'Australia Post');

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_status`
--

DROP TABLE IF EXISTS `oc_order_status`;
CREATE TABLE `oc_order_status` (
  `order_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`order_status_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_order_status`
--

INSERT INTO `oc_order_status` (`order_status_id`, `language_id`, `name`) VALUES
(2, 1, 'В обробці'),
(3, 1, 'Доставлено'),
(7, 1, 'Скасовано'),
(5, 1, 'Завершено'),
(8, 1, 'Повернення'),
(9, 1, 'Скасування та анулювання'),
(10, 1, 'Помилкове'),
(11, 1, 'Відшкодоване'),
(12, 1, 'Змінене'),
(13, 1, 'Повне повернення'),
(1, 1, 'Очікування'),
(15, 1, 'Оброблене'),
(14, 1, 'Не актуальне'),
(2, 2, 'Processing'),
(8, 2, 'Denied'),
(11, 2, 'Refunded'),
(3, 2, 'Shipped'),
(10, 2, 'Failed'),
(1, 2, 'Pending'),
(9, 2, 'Canceled Reversal'),
(7, 2, 'Canceled'),
(12, 2, 'Reversed'),
(13, 2, 'Chargeback'),
(5, 2, 'Complete'),
(14, 2, 'Expired'),
(16, 1, 'Анульоване'),
(16, 2, 'Voided'),
(15, 2, 'Processed');

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_total`
--

DROP TABLE IF EXISTS `oc_order_total`;
CREATE TABLE `oc_order_total` (
  `order_total_id` int(10) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `code` varchar(32) NOT NULL,
  `title` varchar(255) NOT NULL,
  `value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`order_total_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_order_voucher`
--

DROP TABLE IF EXISTS `oc_order_voucher`;
CREATE TABLE `oc_order_voucher` (
  `order_voucher_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `code` varchar(32) NOT NULL,
  `from_name` varchar(64) NOT NULL,
  `from_email` varchar(96) NOT NULL,
  `to_name` varchar(64) NOT NULL,
  `to_email` varchar(96) NOT NULL,
  `voucher_theme_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  PRIMARY KEY (`order_voucher_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_product`
--

DROP TABLE IF EXISTS `oc_product`;
DROP TABLE IF EXISTS `oc_codecart_purchase_block`;
CREATE TABLE `oc_codecart_purchase_block` (
  `owner_type` varchar(8) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL DEFAULT '0',
  `mode` tinyint(1) NOT NULL DEFAULT '1',
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`owner_type`,`owner_id`,`language_id`),
  KEY `owner_lookup` (`owner_type`,`owner_id`),
  KEY `language_id` (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Demo presentation content for CodeCart PRO purchase blocks
--
INSERT INTO `oc_codecart_purchase_block` (`owner_type`,`owner_id`,`language_id`,`mode`,`data`) VALUES
('category',18,0,1,'[]'),
('category',18,1,1,'[{"type":"info","title":"Доставка ноутбуків по Україні","content":"<p><strong>🚚 Швидка доставка по Україні.</strong> У реальному магазині тут можна показати службу доставки, терміни, страхування та умови отримання.</p><p>Цей блок задано на рівні категорії та автоматично успадковується товарами — так демонструється повторне використання контенту без дублювання.</p>"}]'),
('category',18,2,1,'[{"type":"info","title":"Laptop delivery across Ukraine","content":"<p><strong>🚚 Fast delivery across Ukraine.</strong> A live store can use this block for carriers, delivery times, insurance and collection terms.</p><p>This block is configured at category level and inherited by products, demonstrating reusable content without duplication.</p>"}]'),
('product',40,0,1,'[]'),
('product',40,1,1,'[{"type":"info","title":"Доставка та оплата","content":"<p><strong>🚚 Нова пошта / кур’єр / самовивіз.</strong> Відправлення після підтвердження замовлення. 💳 Оплата карткою, переказом або при отриманні.</p>"},{"type":"colors","title":"Варіанти кольору","items":[{"label":"Graphite","value":"#374151"},{"label":"Blue","value":"#2563eb"},{"label":"Silver","value":"#d1d5db"}]}]'),
('product',40,2,1,'[{"type":"info","title":"Delivery and payment","content":"<p><strong>🚚 Nova Poshta / courier / pickup.</strong> Dispatch after order confirmation. 💳 Card, bank transfer or payment on delivery.</p>"},{"type":"colors","title":"Color options","items":[{"label":"Graphite","value":"#374151"},{"label":"Blue","value":"#2563eb"},{"label":"Silver","value":"#d1d5db"}]}]'),
('product',42,0,1,'[]'),
('product',42,1,1,'[{"type":"info","title":"Інформаційний блок","content":"<p><strong>ℹ️ Приклад контентного блока.</strong> Його можна використовувати для доставки, гарантії, важливих попереджень, інструкцій або будь-якої інформації без створення окремої вкладки.</p>"},{"type":"size_table","title":"Порівняння конфігурацій","columns":["Варіант","Комплектація","Гарантія"],"rows":[["Standard","Монітор + кабель","12 міс."],["Studio","Монітор + кабель + адаптер","24 міс."],["Business","2 монітори + комплект кабелів","24 міс."]]},{"type":"sizes","title":"Готові комплекти","items":[{"label":"Standard","value":"1 шт."},{"label":"Studio","value":"1 + аксесуари"},{"label":"Business","value":"2 шт."}]},{"type":"colors","title":"Кольорові варіанти","items":[{"label":"Silver","value":"#d1d5db"},{"label":"Space Gray","value":"#4b5563"},{"label":"White","value":"#f3f4f6"}]}]'),
('product',42,2,1,'[{"type":"info","title":"Information block","content":"<p><strong>ℹ️ Content block example.</strong> It can be used for delivery, warranty, warnings, instructions or any useful purchase information without creating a separate tab.</p>"},{"type":"size_table","title":"Configuration comparison","columns":["Option","Package","Warranty"],"rows":[["Standard","Display + cable","12 mo."],["Studio","Display + cable + adapter","24 mo."],["Business","2 displays + cable set","24 mo."]]},{"type":"sizes","title":"Ready packages","items":[{"label":"Standard","value":"1 pc."},{"label":"Studio","value":"1 + accessories"},{"label":"Business","value":"2 pcs."}]},{"type":"colors","title":"Color options","items":[{"label":"Silver","value":"#d1d5db"},{"label":"Space Gray","value":"#4b5563"},{"label":"White","value":"#f3f4f6"}]}]'),
('product',43,0,1,'[]'),
('product',43,1,1,'[{"type":"info","title":"Оплата, гарантія та сервіс","content":"<p>✅ Гарантія, консультація перед покупкою та післяпродажна підтримка можуть бути показані прямо біля кнопки покупки. Для реального магазину текст задається окремо для кожної мови.</p>"},{"type":"size_table","title":"Приклад конфігурацій","columns":["Конфігурація","Пам’ять","Призначення"],"rows":[["Base","8 GB","Навчання та офіс"],["Pro","16 GB","Робота та контент"],["Business","32 GB","Професійні задачі"]]}]'),
('product',43,2,1,'[{"type":"info","title":"Payment, warranty and service","content":"<p>✅ Warranty, pre-sale consultation and after-sales support can be shown directly near the purchase controls. A live store can provide separate text per language.</p>"},{"type":"size_table","title":"Configuration example","columns":["Configuration","Memory","Use case"],"rows":[["Base","8 GB","Study and office"],["Pro","16 GB","Work and content"],["Business","32 GB","Professional tasks"]]}]'),
('product',49,0,1,'[]'),
('product',49,1,1,'[{"type":"info","title":"Для бізнесу та навчання","content":"<p>📱 Приклад тематичного блока для сценарію використання. Тут можна показувати переваги, комплект поставки, сумісність, сервіс або коротку інструкцію.</p>"}]'),
('product',49,2,1,'[{"type":"info","title":"For business and education","content":"<p>📱 Example of a use-case information block. It can show benefits, package contents, compatibility, service terms or a short guide.</p>"}]');


DROP TABLE IF EXISTS `oc_codecart_form_description`;
DROP TABLE IF EXISTS `oc_codecart_form`;
CREATE TABLE `oc_codecart_form` (
  `form_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `kind` varchar(16) NOT NULL DEFAULT 'request',
  `recipient` varchar(255) NOT NULL DEFAULT '',
  `button_icon` varchar(64) NOT NULL DEFAULT 'fa-envelope-o',
  `button_bg` varchar(7) NOT NULL DEFAULT '#0b6fd3',
  `button_text_color` varchar(7) NOT NULL DEFAULT '#ffffff',
  `button_hover_bg` varchar(7) NOT NULL DEFAULT '#095eb4',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`form_id`),
  KEY `status_name` (`status`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `oc_codecart_form_description` (
  `form_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `submit_text` varchar(80) NOT NULL DEFAULT '',
  `success_text` varchar(255) NOT NULL DEFAULT '',
  `fields` mediumtext NOT NULL,
  PRIMARY KEY (`form_id`,`language_id`),
  KEY `language_id` (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Demo reusable forms for the presentation store
--
INSERT INTO `oc_codecart_form` (`form_id`,`name`,`kind`,`recipient`,`button_icon`,`button_bg`,`button_text_color`,`button_hover_bg`,`status`,`date_added`,`date_modified`) VALUES
(1,'Зворотний дзвінок','request','info@codecartpro.com','fa-phone','#0b6fd3','#ffffff','#095eb4',1,'2026-09-01 10:00:00','2026-09-01 10:00:00'),
(2,'Поставити питання','request','info@codecartpro.com','fa-question-circle','#0b6fd3','#ffffff','#095eb4',1,'2026-09-01 10:00:00','2026-09-01 10:00:00'),
(3,'Допомога з підбором','request','info@codecartpro.com','fa-sliders','#0b6fd3','#ffffff','#095eb4',1,'2026-09-01 10:00:00','2026-09-01 10:00:00');

INSERT INTO `oc_codecart_form_description` (`form_id`,`language_id`,`title`,`description`,`submit_text`,`success_text`,`fields`) VALUES
(1,1,'Замовити зворотний дзвінок','<p>Залиште контакт — менеджер зв’язується для консультації. Це демонстраційна форма CodeCart.</p>','Надіслати','Дякуємо! Запит прийнято. Менеджер зв’яжеться з вами.','[{"key":"name","type":"text","label":"Ваше ім’я","placeholder":"Ім’я","required":1},{"key":"phone","type":"tel","label":"Телефон","placeholder":"+380…","required":1},{"key":"time","type":"select","label":"Зручний час","placeholder":"","required":0,"options":["Якнайшвидше","09:00–12:00","12:00–15:00","15:00–18:00"]},{"key":"comment","type":"textarea","label":"Коментар","placeholder":"Що потрібно уточнити?","required":0}]'),
(1,2,'Request a callback','<p>Leave your contact details and a manager can call you back. This is a CodeCart PRO demo form.</p>','Send request','Thank you. Your request has been received.','[{"key":"name","type":"text","label":"Your name","placeholder":"Name","required":1},{"key":"phone","type":"tel","label":"Phone","placeholder":"+380…","required":1},{"key":"time","type":"select","label":"Preferred time","placeholder":"","required":0,"options":["As soon as possible","09:00–12:00","12:00–15:00","15:00–18:00"]},{"key":"comment","type":"textarea","label":"Comment","placeholder":"How can we help?","required":0}]'),
(2,1,'Поставити питання про товар','<p>Покажіть покупцю, що консультацію можна отримати прямо зі сторінки товару.</p>','Поставити питання','Повідомлення надіслано. Дякуємо за звернення.','[{"key":"name","type":"text","label":"Ваше ім’я","placeholder":"Ім’я","required":1},{"key":"email","type":"email","label":"E-mail","placeholder":"name@example.com","required":1},{"key":"question","type":"textarea","label":"Питання","placeholder":"Напишіть ваше питання","required":1},{"key":"consent","type":"checkbox","label":"Погоджуюся на обробку даних","placeholder":"","required":1}]'),
(2,2,'Ask a product question','<p>Demonstrates a consultation form directly on the product page.</p>','Ask a question','Your message has been sent. Thank you.','[{"key":"name","type":"text","label":"Your name","placeholder":"Name","required":1},{"key":"email","type":"email","label":"E-mail","placeholder":"name@example.com","required":1},{"key":"question","type":"textarea","label":"Question","placeholder":"Enter your question","required":1},{"key":"consent","type":"checkbox","label":"I agree to data processing","placeholder":"","required":1}]'),
(3,1,'Допомога з підбором товару','<p>Приклад форми для категорії: покупець описує задачу, бюджет і отримує допомогу з підбором.</p>','Отримати консультацію','Запит прийнято. Ми підготуємо рекомендацію.','[{"key":"name","type":"text","label":"Ваше ім’я","placeholder":"Ім’я","required":1},{"key":"contact","type":"text","label":"Телефон або E-mail","placeholder":"Контакт для відповіді","required":1},{"key":"budget","type":"select","label":"Бюджет","placeholder":"","required":0,"options":["До 5 000 ₴","5 000–15 000 ₴","15 000–30 000 ₴","Понад 30 000 ₴"]},{"key":"needs","type":"textarea","label":"Що потрібно підібрати","placeholder":"Опишіть задачу або вимоги","required":1}]'),
(3,2,'Product selection help','<p>A category-level form where a shopper can describe requirements and budget.</p>','Get advice','Request received. We will prepare a recommendation.','[{"key":"name","type":"text","label":"Your name","placeholder":"Name","required":1},{"key":"contact","type":"text","label":"Phone or e-mail","placeholder":"Contact details","required":1},{"key":"budget","type":"select","label":"Budget","placeholder":"","required":0,"options":["Up to 5,000 UAH","5,000–15,000 UAH","15,000–30,000 UAH","Over 30,000 UAH"]},{"key":"needs","type":"textarea","label":"What do you need?","placeholder":"Describe the task or requirements","required":1}]');

CREATE TABLE `oc_product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(64) NOT NULL,
  `sku` varchar(64) NOT NULL,
  `upc` varchar(12) NOT NULL,
  `ean` varchar(14) NOT NULL,
  `jan` varchar(13) NOT NULL,
  `isbn` varchar(17) NOT NULL,
  `mpn` varchar(64) NOT NULL,
  `google_product_category_id` varchar(64) NOT NULL DEFAULT '',
  `location` varchar(128) NOT NULL,
  `quantity` int(4) NOT NULL DEFAULT '0',
  `stock_status_id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `manufacturer_id` int(11) NOT NULL,
  `shipping` tinyint(1) NOT NULL DEFAULT '1',
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `points` int(8) NOT NULL DEFAULT '0',
  `tax_class_id` int(11) NOT NULL,
  `tax_display_mode` varchar(16) NOT NULL DEFAULT 'inherit',
  `date_available` date NOT NULL DEFAULT '1970-01-01',
  `weight` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  `weight_class_id` int(11) NOT NULL DEFAULT '0',
  `length` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  `width` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  `height` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  `length_class_id` int(11) NOT NULL DEFAULT '0',
  `subtract` tinyint(1) NOT NULL DEFAULT '1',
  `minimum` int(11) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `viewed` int(5) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `noindex` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product`
--

INSERT INTO `oc_product` (`product_id`, `model`, `sku`, `upc`, `ean`, `jan`, `isbn`, `mpn`, `location`, `quantity`, `stock_status_id`, `image`, `manufacturer_id`, `shipping`, `price`, `points`, `tax_class_id`, `date_available`, `weight`, `weight_class_id`, `length`, `width`, `height`, `length_class_id`, `subtract`, `minimum`, `sort_order`, `status`, `viewed`, `date_added`, `date_modified`, `noindex`) VALUES
(28, 'HTC-T8282', 'DEMO-HTC-T8282', '', '', '', '', 'T8282', '', 939, 7, 'catalog/demo/htc_touch_hd_1.webp', 5, 1, '100.0000', 200, 9, '2009-02-03', '146.40000000', 2, '0.00000000', '0.00000000', '0.00000000', 1, 1, 1, 0, 1, 0, '2009-02-03 16:06:50', '2026-09-01 12:00:00', 1),
(29, 'TREO-PRO', 'DEMO-PALM-TREO-PRO', '', '', '', '', 'TREO-PRO', '', 999, 6, 'catalog/demo/palm_treo_pro_1.webp', 6, 1, '279.9900', 0, 9, '2009-02-03', '133.00000000', 2, '0.00000000', '0.00000000', '0.00000000', 3, 1, 1, 0, 1, 0, '2009-02-03 16:42:17', '2026-09-01 12:00:00', 1),
(30, 'EOS-5D', 'DEMO-CANON-EOS5D', '', '', '', '', 'EOS-5D', '', 7, 6, 'catalog/demo/canon_eos_5d_1.webp', 9, 1, '100.0000', 0, 9, '2009-02-03', '0.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 1, 1, 1, 0, 1, 0, '2009-02-03 16:59:00', '2026-09-01 12:00:00', 1),
(31, 'D300', 'DEMO-NIKON-D300', '', '', '', '', 'D300', '', 1000, 6, 'catalog/demo/nikon_d300_1.webp', 14, 1, '80.0000', 0, 9, '2009-02-03', '0.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 3, 1, 1, 0, 1, 0, '2009-02-03 17:00:10', '2026-09-01 12:00:00', 1),
(32, 'IPOD-TOUCH', 'DEMO-APPLE-IPOD-TOUCH', '', '', '', '', 'IPOD-TOUCH-DEMO', '', 999, 6, 'catalog/demo/ipod_touch_1.webp', 8, 1, '100.0000', 0, 9, '2009-02-03', '5.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 1, 1, 1, 0, 1, 0, '2009-02-03 17:07:26', '2026-09-01 12:00:00', 1),
(33, '941BW', 'DEMO-SAMSUNG-941BW', '', '', '', '', '941BW', '', 1000, 6, 'catalog/demo/samsung_syncmaster_941bw.webp', 12, 1, '200.0000', 0, 9, '2009-02-03', '5.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 2, 1, 1, 0, 1, 0, '2009-02-03 17:08:31', '2026-09-01 12:00:00', 1),
(34, 'IPOD-SHUFFLE', 'DEMO-APPLE-IPOD-SHUFFLE', '', '', '', '', 'IPOD-SHUFFLE-DEMO', '', 1000, 6, 'catalog/demo/ipod_shuffle_1.webp', 8, 1, '100.0000', 0, 9, '2009-02-03', '5.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 2, 1, 1, 0, 1, 0, '2009-02-03 18:07:54', '2026-09-01 12:00:00', 1),
(35, 'DEMO-NOIMAGE', 'DEMO-NOIMAGE-35', '', '', '', '', 'CCP-DEMO-NOIMAGE-35', '', 1000, 5, '', 15, 0, '100.0000', 0, 9, '2009-02-03', '5.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 1, 1, 1, 0, 1, 0, '2009-02-03 18:08:31', '2026-09-01 12:00:00', 1),
(36, 'IPOD-NANO', 'DEMO-APPLE-IPOD-NANO', '', '', '', '', 'IPOD-NANO-DEMO', '', 994, 6, 'catalog/demo/ipod_nano_1.webp', 8, 0, '100.0000', 100, 9, '2009-02-03', '5.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 2, 1, 1, 0, 1, 0, '2009-02-03 18:09:19', '2026-09-01 12:00:00', 1),
(40, 'IPHONE', 'DEMO-APPLE-IPHONE', '', '', '', '', 'IPHONE-DEMO', '', 970, 5, 'catalog/demo/product/iphone.webp', 8, 1, '101.0000', 0, 9, '2009-02-03', '10.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 1, 1, 1, 0, 1, 0, '2009-02-03 21:07:12', '2026-09-01 12:00:00', 1),
(41, 'IMAC', 'DEMO-APPLE-IMAC', '', '', '', '', 'IMAC-DEMO', '', 977, 5, 'catalog/demo/imac_1.webp', 8, 1, '100.0000', 0, 9, '2009-02-03', '5.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 1, 1, 1, 0, 1, 0, '2009-02-03 21:07:26', '2026-09-01 12:00:00', 1),
(42, 'CINEMA-30', 'DEMO-APPLE-CINEMA30', '', '', '', '', 'CINEMA-30-DEMO', '', 990, 5, 'catalog/demo/product/thunderboltdisplay.webp', 8, 1, '100.0000', 400, 9, '2009-02-04', '12.50000000', 1, '1.00000000', '2.00000000', '3.00000000', 1, 1, 2, 0, 1, 1, '2009-02-03 21:07:37', '2026-09-01 12:00:00', 1),
(43, 'MACBOOK', 'DEMO-APPLE-MACBOOK', '', '', '', '', 'MACBOOK-DEMO', '', 929, 5, 'catalog/demo/product/macbook.webp', 8, 0, '500.0000', 0, 9, '2009-02-03', '0.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 2, 1, 1, 0, 1, 0, '2009-02-03 21:07:49', '2026-09-01 12:00:00', 1),
(44, 'MACBOOK-AIR', 'DEMO-APPLE-MBAIR', '', '', '', '', 'MACBOOK-AIR-DEMO', '', 1000, 5, 'catalog/demo/macbook_air_1.webp', 8, 1, '1000.0000', 0, 9, '2009-02-03', '0.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 2, 1, 1, 0, 1, 0, '2009-02-03 21:08:00', '2026-09-01 12:00:00', 1),
(45, 'MACBOOK-PRO', 'DEMO-APPLE-MBPRO', '', '', '', '', 'MACBOOK-PRO-DEMO', '', 998, 5, 'catalog/demo/macbook_pro_1.webp', 8, 1, '2000.0000', 0, 9, '2009-02-03', '0.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 2, 1, 1, 0, 1, 0, '2009-02-03 21:08:17', '2026-09-01 12:00:00', 1),
(46, 'VAIO', 'DEMO-SONY-VAIO', '', '', '', '', 'VAIO-DEMO', '', 1000, 5, 'catalog/demo/sony_vaio_1.webp', 10, 1, '1000.0000', 0, 9, '2009-02-03', '0.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 2, 1, 1, 0, 1, 0, '2009-02-03 21:08:29', '2026-09-01 12:00:00', 1),
(47, 'LP3065', 'DEMO-HP-LP3065', '', '', '', '', 'LP3065', '', 1000, 5, 'catalog/demo/hp_1.webp', 7, 1, '100.0000', 400, 9, '2009-02-03', '1.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 1, 0, 1, 0, 1, 0, '2009-02-03 21:08:40', '2026-09-01 12:00:00', 1),
(48, 'IPOD-CLASSIC', 'DEMO-APPLE-IPOD-CLASSIC', '', '', '', '', 'IPOD-CLASSIC-DEMO', 'DEMO-WAREHOUSE', 995, 5, 'catalog/demo/ipod_classic_1.webp', 8, 1, '100.0000', 0, 9, '2009-02-08', '1.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 2, 1, 1, 0, 1, 0, '2009-02-08 17:21:51', '2026-09-01 12:00:00', 1),
(49, 'GT-P7500', 'DEMO-SAMSUNG-GTAB101', '', '', '', '', 'GT-P7500', '', 50, 8, 'catalog/demo/product/samsungtab.webp', 12, 1, '199.9900', 0, 9, '2011-04-25', '0.00000000', 1, '0.00000000', '0.00000000', '0.00000000', 1, 1, 1, 1, 1, 0, '2011-04-26 08:57:34', '2026-09-01 12:00:00', 1);




-- --------------------------------------------------------
--
-- Table structure for table `oc_product_attribute`
--

DROP TABLE IF EXISTS `oc_product_attribute`;
CREATE TABLE `oc_product_attribute` (
  `product_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`product_id`,`attribute_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_attribute`
--

INSERT INTO `oc_product_attribute` (`product_id`, `attribute_id`, `language_id`, `text`) VALUES
(40, 4, 1, '6,1" Retina'),
(40, 4, 2, '6.1" Retina'),
(40, 10, 1, 'Wi‑Fi / 4G'),
(40, 10, 2, 'Wi‑Fi / 4G'),
(40, 11, 1, '12 міс.'),
(40, 11, 2, '12 mo.'),
(42, 4, 1, '27" IPS'),
(42, 4, 2, '27" IPS'),
(42, 5, 1, 'HDMI / DisplayPort'),
(42, 5, 2, 'HDMI / DisplayPort'),
(42, 11, 1, '12 міс.'),
(42, 11, 2, '12 mo.'),
(43, 2, 1, '8'),
(43, 2, 2, '8'),
(43, 3, 1, '3.1 GHz'),
(43, 3, 2, '3.1 GHz'),
(43, 4, 1, '16GB RAM'),
(43, 4, 2, '16GB RAM'),
(43, 10, 1, 'Wi‑Fi / USB‑C'),
(43, 10, 2, 'Wi‑Fi / USB‑C'),
(47, 2, 1, '4'),
(47, 2, 2, '4'),
(47, 4, 1, '16GB RAM'),
(47, 4, 2, '16GB RAM'),
(47, 10, 1, 'Wi‑Fi / Bluetooth'),
(47, 10, 2, 'Wi‑Fi / Bluetooth'),
(49, 4, 1, '10,1" IPS'),
(49, 4, 2, '10.1" IPS'),
(49, 6, 1, '7000 мА·год'),
(49, 6, 2, '7000 mAh'),
(49, 10, 1, 'Wi‑Fi / Bluetooth'),
(49, 10, 2, 'Wi‑Fi / Bluetooth'),
(30, 7, 1, '24 MP'),
(30, 7, 2, '24 MP'),
(30, 9, 1, 'Камера, ремінь, зарядний пристрій'),
(30, 9, 2, 'Camera, strap, charger'),
(30, 11, 1, '12 міс.'),
(30, 11, 2, '12 mo.');

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_description`
--

DROP TABLE IF EXISTS `oc_product_description`;
CREATE TABLE `oc_product_description` (
  `product_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `tag` text NOT NULL,
  `meta_title` varchar(255) NOT NULL,
  `meta_description` varchar(255) NOT NULL,
  `meta_keyword` varchar(255) NOT NULL,
`meta_h1` varchar(255) NOT NULL,
  PRIMARY KEY (`product_id`,`language_id`),
  KEY `name` (`name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_description`
--

INSERT INTO `oc_product_description` (`product_id`, `language_id`, `name`, `description`, `tag`, `meta_title`, `meta_description`, `meta_keyword`, `meta_h1`) VALUES
(28, 1, 'HTC Touch HD', '&lt;h2 id=&quot;overview&quot;&gt;HTC Touch HD&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;HTC Touch HD використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'HTC Touch HD — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'HTC Touch HD', 'HTC Touch HD'),
(28, 2, 'HTC Touch HD', '&lt;h2 id=&quot;overview&quot;&gt;HTC Touch HD&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;HTC Touch HD is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'HTC Touch HD — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'HTC Touch HD', 'HTC Touch HD'),
(29, 1, 'Palm Treo Pro', '&lt;h2 id=&quot;overview&quot;&gt;Palm Treo Pro&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;Palm Treo Pro використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'Palm Treo Pro — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'Palm Treo Pro', 'Palm Treo Pro'),
(29, 2, 'Palm Treo Pro', '&lt;h2 id=&quot;overview&quot;&gt;Palm Treo Pro&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;Palm Treo Pro is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'Palm Treo Pro — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'Palm Treo Pro', 'Palm Treo Pro'),
(30, 1, 'Canon EOS 5D', '&lt;h2 id=&quot;overview&quot;&gt;Canon EOS 5D&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Товар зі спеціальною ціною.&lt;/strong&gt; У цього товару налаштована спеціальна ціна, щоб наочно показати акційні бейджі, стару ціну та фінальний розрахунок. 🛒&lt;/p&gt;&lt;p&gt;Canon EOS 5D використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'Canon EOS 5D — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'Canon EOS 5D', 'Canon EOS 5D'),
(30, 2, 'Canon EOS 5D', '&lt;h2 id=&quot;overview&quot;&gt;Canon EOS 5D&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Product with a special price.&lt;/strong&gt; This product includes a configured special price to demonstrate sale badges, an old price and final pricing logic. 🛒&lt;/p&gt;&lt;p&gt;Canon EOS 5D is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'Canon EOS 5D — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'Canon EOS 5D', 'Canon EOS 5D'),
(31, 1, 'Nikon D300', '&lt;h2 id=&quot;overview&quot;&gt;Nikon D300&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;Nikon D300 використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'Nikon D300 — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'Nikon D300', 'Nikon D300'),
(31, 2, 'Nikon D300', '&lt;h2 id=&quot;overview&quot;&gt;Nikon D300&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;Nikon D300 is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'Nikon D300 — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'Nikon D300', 'Nikon D300'),
(32, 1, 'iPod Touch', '&lt;h2 id=&quot;overview&quot;&gt;iPod Touch&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;iPod Touch використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'iPod Touch — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'iPod Touch', 'iPod Touch'),
(32, 2, 'iPod Touch', '&lt;h2 id=&quot;overview&quot;&gt;iPod Touch&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;iPod Touch is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'iPod Touch — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'iPod Touch', 'iPod Touch'),
(33, 1, 'Samsung SyncMaster 941BW', '&lt;h2 id=&quot;overview&quot;&gt;Samsung SyncMaster 941BW&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;Samsung SyncMaster 941BW використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'Samsung SyncMaster 941BW — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'Samsung SyncMaster 941BW', 'Samsung SyncMaster 941BW'),
(33, 2, 'Samsung SyncMaster 941BW', '&lt;h2 id=&quot;overview&quot;&gt;Samsung SyncMaster 941BW&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;Samsung SyncMaster 941BW is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'Samsung SyncMaster 941BW — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'Samsung SyncMaster 941BW', 'Samsung SyncMaster 941BW'),
(34, 1, 'iPod Shuffle', '&lt;h2 id=&quot;overview&quot;&gt;iPod Shuffle&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;iPod Shuffle використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'iPod Shuffle — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'iPod Shuffle', 'iPod Shuffle'),
(34, 2, 'iPod Shuffle', '&lt;h2 id=&quot;overview&quot;&gt;iPod Shuffle&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;iPod Shuffle is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'iPod Shuffle — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'iPod Shuffle', 'iPod Shuffle'),
(35, 1, 'Демонстраційний товар без фото', '&lt;h2 id=&quot;overview&quot;&gt;Демонстраційний товар без фото&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Товар без основного зображення.&lt;/strong&gt; Ця картка залишена як демонстрація сценарію, коли у товару відсутнє основне фото. Так легко перевірити fallback-зображення, верстку та поведінку списків товарів. 🛒&lt;/p&gt;&lt;p&gt;Демонстраційний товар без фото використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'Демонстраційний товар без фото — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'Демонстраційний товар без фото', 'Демонстраційний товар без фото'),
(35, 2, 'Demo Product Without Image', '&lt;h2 id=&quot;overview&quot;&gt;Demo Product Without Image&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Product without a main image.&lt;/strong&gt; This card is intentionally left without a primary image to demonstrate image fallback behavior, layout stability and product-list rendering. 🛒&lt;/p&gt;&lt;p&gt;Demo Product Without Image is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'Demo Product Without Image — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'Demo Product Without Image', 'Demo Product Without Image'),
(36, 1, 'iPod Nano', '&lt;h2 id=&quot;overview&quot;&gt;iPod Nano&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;iPod Nano використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'iPod Nano — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'iPod Nano', 'iPod Nano'),
(36, 2, 'iPod Nano', '&lt;h2 id=&quot;overview&quot;&gt;iPod Nano&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;iPod Nano is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'iPod Nano — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'iPod Nano', 'iPod Nano'),
(40, 1, 'iPhone', '&lt;h2 id=&quot;overview&quot;&gt;iPhone&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;iPhone використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'iPhone — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'iPhone', 'iPhone'),
(40, 2, 'iPhone', '&lt;h2 id=&quot;overview&quot;&gt;iPhone&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;iPhone is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'iPhone — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'iPhone', 'iPhone'),
(41, 1, 'iMac', '&lt;h2 id=&quot;overview&quot;&gt;iMac&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;iMac використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'iMac — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'iMac', 'iMac'),
(41, 2, 'iMac', '&lt;h2 id=&quot;overview&quot;&gt;iMac&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;iMac is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'iMac — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'iMac', 'iMac'),
(42, 1, 'Apple Cinema 30&quot;', '&lt;h2 id=&quot;overview&quot;&gt;Apple Cinema 30&amp;quot;&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Демонстрація опцій і форм.&lt;/strong&gt; Цей товар спеціально підготовлений для перевірки всіх основних типів опцій: radio, checkbox, select, text, textarea, file, date, time та date &amp; time. 🛒&lt;/p&gt;&lt;p&gt;Apple Cinema 30&amp;quot; використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'Apple Cinema 30&quot; — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'Apple Cinema 30&quot;', 'Apple Cinema 30&quot;'),
(42, 2, 'Apple Cinema 30&quot;', '&lt;h2 id=&quot;overview&quot;&gt;Apple Cinema 30&amp;quot;&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Option and form showcase.&lt;/strong&gt; This product is intentionally prepared to demonstrate the main option types: radio, checkbox, select, text, textarea, file, date, time and date &amp; time. 🛒&lt;/p&gt;&lt;p&gt;Apple Cinema 30&amp;quot; is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'Apple Cinema 30&quot; — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'Apple Cinema 30&quot;', 'Apple Cinema 30&quot;'),
(43, 1, 'MacBook', '&lt;h2 id=&quot;overview&quot;&gt;MacBook&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;MacBook використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'MacBook — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'MacBook', 'MacBook'),
(43, 2, 'MacBook', '&lt;h2 id=&quot;overview&quot;&gt;MacBook&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;MacBook is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'MacBook — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'MacBook', 'MacBook'),
(44, 1, 'MacBook Air', '&lt;h2 id=&quot;overview&quot;&gt;MacBook Air&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;MacBook Air використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'MacBook Air — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'MacBook Air', 'MacBook Air'),
(44, 2, 'MacBook Air', '&lt;h2 id=&quot;overview&quot;&gt;MacBook Air&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;MacBook Air is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'MacBook Air — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'MacBook Air', 'MacBook Air'),
(45, 1, 'MacBook Pro', '&lt;h2 id=&quot;overview&quot;&gt;MacBook Pro&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;MacBook Pro використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'MacBook Pro — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'MacBook Pro', 'MacBook Pro'),
(45, 2, 'MacBook Pro', '&lt;h2 id=&quot;overview&quot;&gt;MacBook Pro&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;MacBook Pro is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'MacBook Pro — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'MacBook Pro', 'MacBook Pro'),
(46, 1, 'Sony VAIO', '&lt;h2 id=&quot;overview&quot;&gt;Sony VAIO&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;Sony VAIO використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'Sony VAIO — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'Sony VAIO', 'Sony VAIO'),
(46, 2, 'Sony VAIO', '&lt;h2 id=&quot;overview&quot;&gt;Sony VAIO&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;Sony VAIO is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'Sony VAIO — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'Sony VAIO', 'Sony VAIO'),
(47, 1, 'HP LP3065', '&lt;h2 id=&quot;overview&quot;&gt;HP LP3065&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;HP LP3065 використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'HP LP3065 — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'HP LP3065', 'HP LP3065'),
(47, 2, 'HP LP3065', '&lt;h2 id=&quot;overview&quot;&gt;HP LP3065&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;HP LP3065 is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'HP LP3065 — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'HP LP3065', 'HP LP3065'),
(48, 1, 'iPod Classic', '&lt;h2 id=&quot;overview&quot;&gt;iPod Classic&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Готова презентаційна картка товару.&lt;/strong&gt; Ця сторінка допомагає показати правильну структуру товару: галерею, ціну, наявність, опції, характеристики, вкладки та пов''язані елементи. 🛒&lt;/p&gt;&lt;p&gt;iPod Classic використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'iPod Classic — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'iPod Classic', 'iPod Classic'),
(48, 2, 'iPod Classic', '&lt;h2 id=&quot;overview&quot;&gt;iPod Classic&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Ready-made presentation product page.&lt;/strong&gt; This page is designed to demonstrate a proper product structure: gallery, price, stock, options, attributes, tabs and related content. 🛒&lt;/p&gt;&lt;p&gt;iPod Classic is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'iPod Classic — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'iPod Classic', 'iPod Classic'),
(49, 1, 'Samsung Galaxy Tab 10.1', '&lt;h2 id=&quot;overview&quot;&gt;Samsung Galaxy Tab 10.1&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Огляд&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;Що демонструє&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Доставка&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Демонстраційний планшет для мобільного каталогу.&lt;/strong&gt; Картка з акцентом на сучасний мобільний перегляд, додаткову галерею зображень і зручне порівняння характеристик. 🛒&lt;/p&gt;&lt;p&gt;Samsung Galaxy Tab 10.1 використовується як демонстраційний товар у CodeCart PRO Demo Store. Текст написаний як шаблон і легко замінюється на реальний контент магазину.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;Що варто показати покупцеві&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;якісні фото та додаткову галерею;&lt;/li&gt;&lt;li&gt;зрозумілу ціну, статус наявності й кнопку покупки;&lt;/li&gt;&lt;li&gt;характеристики, опції, пов’язані товари та вкладки;&lt;/li&gt;&lt;li&gt;адаптивний перегляд на смартфоні, планшеті та desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;Що демонструє ця картка&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Це не випадковий текст-заглушка, а готовий демонстраційний блок для презентації магазину клієнту або перед запуском проєкту.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;Презентаційну верстку опису з підзаголовками та списками.&lt;/li&gt;&lt;li&gt;Можливість додавати emoji, акценти й посилання-якорі.&lt;/li&gt;&lt;li&gt;Зручну підготовку контенту для товарів різного типу.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Доставка та оплата&lt;/h3&gt;&lt;p&gt;Для українського магазину доцільно показувати кілька способів доставки, короткі умови оплати, гарантії та підказку про уточнення наявності. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Порада:&lt;/em&gt; у реальному магазині цей шаблон можна адаптувати під бренд, додати технічні таблиці, PDF, відео та інструкції.&lt;/p&gt;', 'демо, товар, codecart, презентація, магазин', 'Samsung Galaxy Tab 10.1 — демонстраційний товар з продуманою карткою, структурованим описом і прикладом правильного контенту.', '', 'Samsung Galaxy Tab 10.1', 'Samsung Galaxy Tab 10.1'),
(49, 2, 'Samsung Galaxy Tab 10.1', '&lt;h2 id=&quot;overview&quot;&gt;Samsung Galaxy Tab 10.1&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#overview&quot;&gt;Overview&lt;/a&gt; · &lt;a href=&quot;#features&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#demo&quot;&gt;What it demonstrates&lt;/a&gt; · &lt;a href=&quot;#delivery&quot;&gt;Delivery&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Demo tablet for the mobile catalog.&lt;/strong&gt; This product emphasizes a mobile-friendly layout, an extra image gallery and convenient specification comparison. 🛒&lt;/p&gt;&lt;p&gt;Samsung Galaxy Tab 10.1 is used as a demo product in CodeCart PRO Demo Store. The copy is intentionally template-based and can be quickly replaced with real store content.&lt;/p&gt;&lt;h3 id=&quot;features&quot;&gt;What the customer should see&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;quality images and an additional gallery;&lt;/li&gt;&lt;li&gt;clear pricing, stock status and buy button;&lt;/li&gt;&lt;li&gt;attributes, options, related products and tabs;&lt;/li&gt;&lt;li&gt;responsive behavior on mobile, tablet and desktop. 📱&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;demo&quot;&gt;What this page demonstrates&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;This is not random filler text but a ready presentation block for showcasing the store before launch.&lt;/p&gt;&lt;/blockquote&gt;&lt;ol&gt;&lt;li&gt;A structured description layout with headings and lists.&lt;/li&gt;&lt;li&gt;The ability to add emoji, accents and anchor links.&lt;/li&gt;&lt;li&gt;A flexible content pattern for different product types.&lt;/li&gt;&lt;/ol&gt;&lt;h3 id=&quot;delivery&quot;&gt;Delivery and payment&lt;/h3&gt;&lt;p&gt;For a Ukrainian-focused store, it makes sense to show several delivery methods, concise payment notes, warranty terms and a stock-availability hint. 🚚 💳&lt;/p&gt;&lt;p&gt;&lt;em&gt;Tip:&lt;/em&gt; in a live store this template can be adapted for your brand and extended with technical tables, PDFs, videos or manuals.&lt;/p&gt;', 'demo, product, codecart, presentation, store', 'Samsung Galaxy Tab 10.1 — a demo product with a structured description, presentation-ready content and a practical storefront layout.', '', 'Samsung Galaxy Tab 10.1', 'Samsung Galaxy Tab 10.1');

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_discount`
--

DROP TABLE IF EXISTS `oc_product_discount`;
CREATE TABLE `oc_product_discount` (
  `product_discount_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_group_id` int(11) NOT NULL,
  `quantity` int(4) NOT NULL DEFAULT '0',
  `priority` int(5) NOT NULL DEFAULT '1',
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `date_start` date NOT NULL DEFAULT '1970-01-01',
  `date_end` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`product_discount_id`),
  KEY `product_group_qty` (`product_id`,`customer_group_id`,`quantity`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_discount`
--

INSERT INTO `oc_product_discount` (`product_discount_id`, `product_id`, `customer_group_id`, `quantity`, `priority`, `price`, `date_start`, `date_end`) VALUES
(440, 42, 1, 30, 1, '66.0000', '1970-01-01', '9999-12-31'),
(439, 42, 1, 20, 1, '77.0000', '1970-01-01', '9999-12-31'),
(438, 42, 1, 10, 1, '88.0000', '1970-01-01', '9999-12-31'),
(441, 42, 2, 30, 1, '66.0000', '1970-01-01', '9999-12-31'),
(442, 42, 2, 20, 1, '77.0000', '1970-01-01', '9999-12-31'),
(443, 42, 2, 10, 1, '88.0000', '1970-01-01', '9999-12-31');

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_filter`
--

DROP TABLE IF EXISTS `oc_product_filter`;
DROP TABLE IF EXISTS `oc_product_extra_tab`;
CREATE TABLE `oc_product_extra_tab` (
  `product_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `mode` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(128) NOT NULL DEFAULT '',
  `content` mediumtext NOT NULL,
  PRIMARY KEY (`product_id`,`language_id`),
  KEY `language_id` (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oc_product_filter` (
  `product_id` int(11) NOT NULL,
  `filter_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`filter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_image`
--

DROP TABLE IF EXISTS `oc_product_image`;
CREATE TABLE `oc_product_image` (
  `product_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_image_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_image`
--

INSERT INTO `oc_product_image` (`product_image_id`, `product_id`, `image`, `sort_order`) VALUES
(2345, 30, 'catalog/demo/canon_eos_5d_2.webp', 0),
(2321, 47, 'catalog/demo/hp_3.webp', 0),
(2035, 28, 'catalog/demo/htc_touch_hd_2.webp', 0),
(2351, 41, 'catalog/demo/imac_3.webp', 0),
(1982, 40, 'catalog/demo/iphone_6.webp', 0),
(2001, 36, 'catalog/demo/ipod_nano_5.webp', 0),
(2000, 36, 'catalog/demo/ipod_nano_4.webp', 0),
(2005, 34, 'catalog/demo/ipod_shuffle_5.webp', 0),
(2004, 34, 'catalog/demo/ipod_shuffle_4.webp', 0),
(2011, 32, 'catalog/demo/ipod_touch_7.webp', 0),
(2010, 32, 'catalog/demo/ipod_touch_6.webp', 0),
(2009, 32, 'catalog/demo/ipod_touch_5.webp', 0),
(1971, 43, 'catalog/demo/macbook_5.webp', 0),
(1970, 43, 'catalog/demo/macbook_4.webp', 0),
(1974, 44, 'catalog/demo/macbook_air_4.webp', 0),
(1973, 44, 'catalog/demo/macbook_air_2.webp', 0),
(1977, 45, 'catalog/demo/macbook_pro_2.webp', 0),
(1976, 45, 'catalog/demo/macbook_pro_3.webp', 0),
(1986, 31, 'catalog/demo/nikon_d300_3.webp', 0),
(1985, 31, 'catalog/demo/nikon_d300_2.webp', 0),
(1988, 29, 'catalog/demo/palm_treo_pro_3.webp', 0),
(1995, 46, 'catalog/demo/sony_vaio_5.webp', 0),
(1994, 46, 'catalog/demo/sony_vaio_4.webp', 0),
(1991, 48, 'catalog/demo/ipod_classic_4.webp', 0),
(1990, 48, 'catalog/demo/ipod_classic_3.webp', 0),
(1981, 40, 'catalog/demo/iphone_2.webp', 0),
(1980, 40, 'catalog/demo/iphone_5.webp', 0),
(2344, 30, 'catalog/demo/canon_eos_5d_3.webp', 0),
(2320, 47, 'catalog/demo/hp_2.webp', 0),
(2034, 28, 'catalog/demo/htc_touch_hd_3.webp', 0),
(2350, 41, 'catalog/demo/imac_2.webp', 0),
(1979, 40, 'catalog/demo/iphone_3.webp', 0),
(1978, 40, 'catalog/demo/iphone_4.webp', 0),
(1989, 48, 'catalog/demo/ipod_classic_2.webp', 0),
(1999, 36, 'catalog/demo/ipod_nano_2.webp', 0),
(1998, 36, 'catalog/demo/ipod_nano_3.webp', 0),
(2003, 34, 'catalog/demo/ipod_shuffle_2.webp', 0),
(2002, 34, 'catalog/demo/ipod_shuffle_3.webp', 0),
(2008, 32, 'catalog/demo/ipod_touch_2.webp', 0),
(2007, 32, 'catalog/demo/ipod_touch_3.webp', 0),
(2006, 32, 'catalog/demo/ipod_touch_4.webp', 0),
(1969, 43, 'catalog/demo/macbook_2.webp', 0),
(1968, 43, 'catalog/demo/macbook_3.webp', 0),
(1972, 44, 'catalog/demo/macbook_air_3.webp', 0),
(1975, 45, 'catalog/demo/macbook_pro_4.webp', 0),
(1984, 31, 'catalog/demo/nikon_d300_4.webp', 0),
(1983, 31, 'catalog/demo/nikon_d300_5.webp', 0),
(1987, 29, 'catalog/demo/palm_treo_pro_2.webp', 0),
(1993, 46, 'catalog/demo/sony_vaio_2.webp', 0),
(1992, 46, 'catalog/demo/sony_vaio_3.webp', 0),
(2327, 49, 'catalog/demo/samsung_tab_7.webp', 0),
(2326, 49, 'catalog/demo/samsung_tab_6.webp', 0),
(2325, 49, 'catalog/demo/samsung_tab_5.webp', 0),
(2324, 49, 'catalog/demo/samsung_tab_4.webp', 0),
(2323, 49, 'catalog/demo/samsung_tab_3.webp', 0),
(2322, 49, 'catalog/demo/samsung_tab_2.webp', 0),
(2317, 42, 'catalog/demo/canon_logo.webp', 0),
(2316, 42, 'catalog/demo/hp_1.webp', 0),
(2315, 42, 'catalog/demo/compaq_presario.webp', 0),
(2314, 42, 'catalog/demo/canon_eos_5d_1.webp', 0),
(2313, 42, 'catalog/demo/canon_eos_5d_2.webp', 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_option`
--

DROP TABLE IF EXISTS `oc_product_option`;
CREATE TABLE `oc_product_option` (
  `product_option_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `value` text NOT NULL,
  `required` tinyint(1) NOT NULL,
  PRIMARY KEY (`product_option_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_option`
--

INSERT INTO `oc_product_option` (`product_option_id`, `product_id`, `option_id`, `value`, `required`) VALUES
(224, 35, 11, '', 1),
(225, 47, 12, '2011-04-22', 1),
(223, 42, 2, '', 1),
(217, 42, 5, '', 1),
(209, 42, 6, '', 1),
(218, 42, 1, '', 1),
(208, 42, 4, 'test', 1),
(219, 42, 8, '2011-02-20', 1),
(222, 42, 7, '', 1),
(221, 42, 9, '22:25', 1),
(220, 42, 10, '2011-02-20 22:25', 1),
(226, 30, 5, '', 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_option_value`
--

DROP TABLE IF EXISTS `oc_product_option_value`;
CREATE TABLE `oc_product_option_value` (
  `product_option_value_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_option_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `option_value_id` int(11) NOT NULL,
  `quantity` int(3) NOT NULL,
  `subtract` tinyint(1) NOT NULL,
  `price` decimal(15,4) NOT NULL,
  `price_prefix` varchar(1) NOT NULL,
  `points` int(8) NOT NULL,
  `points_prefix` varchar(1) NOT NULL,
  `weight` decimal(15,8) NOT NULL,
  `weight_prefix` varchar(1) NOT NULL,
  PRIMARY KEY (`product_option_value_id`),
  KEY `product_id` (`product_id`),
  KEY `product_option_id` (`product_option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_option_value`
--

INSERT INTO `oc_product_option_value` (`product_option_value_id`, `product_option_id`, `product_id`, `option_id`, `option_value_id`, `quantity`, `subtract`, `price`, `price_prefix`, `points`, `points_prefix`, `weight`, `weight_prefix`) VALUES
(1, 217, 42, 5, 41, 100, 0, '1.0000', '+', 0, '+', '1.00000000', '+'),
(6, 218, 42, 1, 31, 146, 1, '20.0000', '+', 2, '-', '20.00000000', '+'),
(7, 218, 42, 1, 43, 300, 1, '30.0000', '+', 3, '+', '30.00000000', '+'),
(5, 218, 42, 1, 32, 96, 1, '10.0000', '+', 1, '+', '10.00000000', '+'),
(4, 217, 42, 5, 39, 92, 1, '4.0000', '+', 0, '+', '4.00000000', '+'),
(2, 217, 42, 5, 42, 200, 1, '2.0000', '+', 0, '+', '2.00000000', '+'),
(3, 217, 42, 5, 40, 300, 0, '3.0000', '+', 0, '+', '3.00000000', '+'),
(8, 223, 42, 2, 23, 48, 1, '10.0000', '+', 0, '+', '10.00000000', '+'),
(10, 223, 42, 2, 44, 2696, 1, '30.0000', '+', 0, '+', '30.00000000', '+'),
(9, 223, 42, 2, 24, 194, 1, '20.0000', '+', 0, '+', '20.00000000', '+'),
(11, 223, 42, 2, 45, 3998, 1, '40.0000', '+', 0, '+', '40.00000000', '+'),
(12, 224, 35, 11, 46, 0, 1, '5.0000', '+', 0, '+', '0.00000000', '+'),
(13, 224, 35, 11, 47, 10, 1, '10.0000', '+', 0, '+', '0.00000000', '+'),
(14, 224, 35, 11, 48, 15, 1, '15.0000', '+', 0, '+', '0.00000000', '+'),
(16, 226, 30, 5, 40, 5, 1, '0.0000', '+', 0, '+', '0.00000000', '+'),
(15, 226, 30, 5, 39, 2, 1, '0.0000', '+', 0, '+', '0.00000000', '+');

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_recurring`
--

DROP TABLE IF EXISTS `oc_product_recurring`;
CREATE TABLE `oc_product_recurring` (
  `product_id` int(11) NOT NULL,
  `recurring_id` int(11) NOT NULL,
  `customer_group_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`recurring_id`,`customer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_related`
--

DROP TABLE IF EXISTS `oc_product_related`;
CREATE TABLE `oc_product_related` (
  `product_id` int(11) NOT NULL,
  `related_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`related_id`),
  KEY `related_id` (`related_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_related`
--

INSERT INTO `oc_product_related` (`product_id`, `related_id`) VALUES
(40, 42),
(40, 49),
(41, 42),
(42, 40),
(42, 41),
(42, 49),
(43, 44),
(43, 45),
(44, 43),
(44, 45),
(45, 43),
(45, 44),
(49, 40),
(49, 42),
(30, 31),
(31, 30);

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_reward`
--

DROP TABLE IF EXISTS `oc_product_reward`;
CREATE TABLE `oc_product_reward` (
  `product_reward_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL DEFAULT '0',
  `customer_group_id` int(11) NOT NULL DEFAULT '0',
  `points` int(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_reward_id`),
  KEY `product_group` (`product_id`,`customer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_reward`
--

INSERT INTO `oc_product_reward` (`product_reward_id`, `product_id`, `customer_group_id`, `points`) VALUES
(515, 42, 1, 100),
(519, 47, 1, 300),
(379, 28, 1, 400),
(329, 43, 1, 600),
(339, 29, 1, 0),
(343, 48, 1, 0),
(335, 40, 1, 0),
(539, 30, 1, 200),
(331, 44, 1, 700),
(333, 45, 1, 800),
(337, 31, 1, 0),
(425, 35, 1, 0),
(345, 33, 1, 0),
(347, 46, 1, 0),
(545, 41, 1, 0),
(351, 36, 1, 0),
(353, 34, 1, 0),
(355, 32, 1, 0),
(521, 49, 1, 1000),
(616, 42, 2, 100),
(617, 47, 2, 300),
(618, 28, 2, 400),
(619, 43, 2, 600),
(620, 29, 2, 0),
(621, 48, 2, 0),
(622, 40, 2, 0),
(623, 30, 2, 200),
(624, 44, 2, 700),
(625, 45, 2, 800),
(626, 31, 2, 0),
(627, 35, 2, 0),
(628, 33, 2, 0),
(629, 46, 2, 0),
(630, 41, 2, 0),
(631, 36, 2, 0),
(632, 34, 2, 0),
(633, 32, 2, 0),
(634, 49, 2, 1000);

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_special`
--

DROP TABLE IF EXISTS `oc_product_special`;
CREATE TABLE `oc_product_special` (
  `product_special_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_group_id` int(11) NOT NULL,
  `priority` int(5) NOT NULL DEFAULT '1',
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `date_start` date NOT NULL DEFAULT '1970-01-01',
  `date_end` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`product_special_id`),
  KEY `product_group_priority` (`product_id`,`customer_group_id`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_special`
--

INSERT INTO `oc_product_special` (`product_special_id`, `product_id`, `customer_group_id`, `priority`, `price`, `date_start`, `date_end`) VALUES
(419, 42, 1, 1, '90.0000', '1970-01-01', '9999-12-31'),
(439, 30, 1, 2, '90.0000', '1970-01-01', '9999-12-31'),
(438, 30, 1, 1, '80.0000', '1970-01-01', '9999-12-31'),
(440, 42, 2, 1, '90.0000', '1970-01-01', '9999-12-31'),
(441, 30, 2, 2, '90.0000', '1970-01-01', '9999-12-31'),
(442, 30, 2, 1, '80.0000', '1970-01-01', '9999-12-31');

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_to_category`
--

DROP TABLE IF EXISTS `oc_product_to_category`;
CREATE TABLE `oc_product_to_category` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `main_category` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_to_category`
--

INSERT INTO `oc_product_to_category` (`product_id`, `category_id`, `main_category`) VALUES
(28, 24, 1),
(28, 20, 0),
(29, 24, 1),
(29, 20, 0),
(30, 33, 1),
(30, 20, 0),
(31, 33, 1),
(32, 34, 1),
(33, 28, 1),
(33, 20, 0),
(34, 34, 1),
(35, 20, 1),
(36, 34, 1),
(40, 24, 1),
(40, 20, 0),
(41, 27, 1),
(42, 28, 1),
(42, 20, 0),
(43, 20, 0),
(43, 18, 0),
(44, 20, 0),
(44, 18, 0),
(45, 18, 0),
(46, 20, 0),
(46, 18, 0),
(47, 20, 0),
(47, 18, 0),
(48, 34, 1),
(48, 20, 0),
(49, 57, 1),
(46, 45, 1),
(47, 45, 1),
(43, 46, 1),
(44, 46, 1),
(45, 46, 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_to_download`
--

DROP TABLE IF EXISTS `oc_product_to_download`;
CREATE TABLE `oc_product_to_download` (
  `product_id` int(11) NOT NULL,
  `download_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`download_id`),
  KEY `download_id` (`download_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_to_layout`
--

DROP TABLE IF EXISTS `oc_product_to_layout`;
CREATE TABLE `oc_product_to_layout` (
  `product_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `layout_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_product_to_store`
--

DROP TABLE IF EXISTS `oc_product_to_store`;
CREATE TABLE `oc_product_to_store` (
  `product_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_id`,`store_id`),
  KEY `store_product` (`store_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_to_store`
--

INSERT INTO `oc_product_to_store` (`product_id`, `store_id`) VALUES
(28, 0),
(29, 0),
(30, 0),
(31, 0),
(32, 0),
(33, 0),
(34, 0),
(35, 0),
(36, 0),
(40, 0),
(41, 0),
(42, 0),
(43, 0),
(44, 0),
(45, 0),
(46, 0),
(47, 0),
(48, 0),
(49, 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_recurring`
--

DROP TABLE IF EXISTS `oc_recurring`;
CREATE TABLE `oc_recurring` (
  `recurring_id` int(11) NOT NULL AUTO_INCREMENT,
  `price` decimal(10,4) NOT NULL,
  `frequency` enum('day','week','semi_month','month','year') NOT NULL,
  `duration` int(10) unsigned NOT NULL,
  `cycle` int(10) unsigned NOT NULL,
  `trial_status` tinyint(4) NOT NULL,
  `trial_price` decimal(10,4) NOT NULL,
  `trial_frequency` enum('day','week','semi_month','month','year') NOT NULL,
  `trial_duration` int(10) unsigned NOT NULL,
  `trial_cycle` int(10) unsigned NOT NULL,
  `status` tinyint(4) NOT NULL,
  `sort_order` int(11) NOT NULL,
  PRIMARY KEY (`recurring_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_recurring_description`
--

DROP TABLE IF EXISTS `oc_recurring_description`;
CREATE TABLE `oc_recurring_description` (
  `recurring_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`recurring_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_return`
--

DROP TABLE IF EXISTS `oc_return`;
CREATE TABLE `oc_return` (
  `return_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `firstname` varchar(32) NOT NULL,
  `lastname` varchar(32) NOT NULL,
  `email` varchar(96) NOT NULL,
  `telephone` varchar(32) NOT NULL,
  `product` varchar(255) NOT NULL,
  `model` varchar(64) NOT NULL,
  `quantity` int(4) NOT NULL,
  `opened` tinyint(1) NOT NULL,
  `return_reason_id` int(11) NOT NULL,
  `return_action_id` int(11) NOT NULL,
  `return_status_id` int(11) NOT NULL,
  `comment` text,
  `date_ordered` date NOT NULL DEFAULT '1970-01-01',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`return_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_return_action`
--

DROP TABLE IF EXISTS `oc_return_action`;
CREATE TABLE `oc_return_action` (
  `return_action_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`return_action_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_return_action`
--

INSERT INTO `oc_return_action` (`return_action_id`, `language_id`, `name`) VALUES
(1, 1, 'Відшкодовано'),
(2, 1, 'Повернення коштів'),
(3, 1, 'Відправлена заміна'),
(1, 2, 'Refunded'),
(3, 2, 'Replacement Sent'),
(2, 2, 'Credit Issued');

-- --------------------------------------------------------
--
-- Table structure for table `oc_return_history`
--

DROP TABLE IF EXISTS `oc_return_history`;
CREATE TABLE `oc_return_history` (
  `return_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `return_id` int(11) NOT NULL,
  `return_status_id` int(11) NOT NULL,
  `notify` tinyint(1) NOT NULL,
  `comment` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`return_history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_return_reason`
--

DROP TABLE IF EXISTS `oc_return_reason`;
CREATE TABLE `oc_return_reason` (
  `return_reason_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`return_reason_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_return_reason`
--

INSERT INTO `oc_return_reason` (`return_reason_id`, `language_id`, `name`) VALUES
(1, 1, 'Отримано/доставлено несправним (зламаним)'),
(1, 2, 'Dead On Arrival'),
(2, 1, 'Отримано не той (помилковий) товар'),
(2, 2, 'Received Wrong Item'),
(3, 1, 'Помилкове замовлення'),
(3, 2, 'Order Error'),
(4, 1, 'Несправний, будь ласка, вкажіть подробиці'),
(4, 2, 'Faulty, please supply details'),
(5, 1, 'Інше (інша причина), будь ласка, вкажіть/докладіть подробиці'),
(5, 2, 'Other, please supply details');

-- --------------------------------------------------------
--
-- Table structure for table `oc_return_status`
--

DROP TABLE IF EXISTS `oc_return_status`;
CREATE TABLE `oc_return_status` (
  `return_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`return_status_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_return_status`
--

INSERT INTO `oc_return_status` (`return_status_id`, `language_id`, `name`) VALUES
(1, 1, 'В очікуванні'),
(3, 1, 'Виконано'),
(2, 1, 'Очікування товару'),
(1, 2, 'Pending'),
(2, 2, 'Awaiting Products'),
(3, 2, 'Complete');

-- --------------------------------------------------------
--
-- Table structure for table `oc_review`
--

DROP TABLE IF EXISTS `oc_review`;
CREATE TABLE `oc_review` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `author` varchar(64) NOT NULL,
  `text` text NOT NULL,
  `rating` int(1) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`review_id`),
  KEY `product_status_date` (`product_id`,`status`,`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Dumping data for table `oc_review`
--

INSERT INTO `oc_review` (`review_id`, `product_id`, `customer_id`, `author`, `text`, `rating`, `status`, `date_added`, `date_modified`) VALUES
(1, 40, 0, 'Олена', 'Дуже вдалий демонстраційний товар. На його прикладі зручно показувати картку товару, фото та пов’язані блоки.', 5, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00'),
(2, 42, 0, 'Ігор', 'Сподобалося, що тут зібрано багато типів опцій. Добре видно, як система працює з різними формами.', 5, 1, '2026-09-01 10:05:00', '2026-09-01 10:05:00'),
(3, 49, 0, 'Наталія', 'Корисний приклад картки для мобільного каталогу. Галерея та опис виглядають акуратно.', 4, 1, '2026-09-01 10:10:00', '2026-09-01 10:10:00'),
(4, 30, 0, 'Олексій', 'Товар зі спеціальною ціною добре демонструє акційні елементи на сторінці.', 5, 1, '2026-09-01 10:15:00', '2026-09-01 10:15:00'),
(5, 40, 0, 'Emily', 'A clean demo product page with good structure and clear pricing presentation.', 5, 1, '2026-09-01 10:20:00', '2026-09-01 10:20:00'),
(6, 42, 0, 'James', 'Excellent example for testing multiple option types on one product page.', 5, 1, '2026-09-01 10:25:00', '2026-09-01 10:25:00');

-- --------------------------------------------------------
--
-- Table structure for table `oc_statistics`
--

DROP TABLE IF EXISTS `oc_statistics`;
CREATE TABLE `oc_statistics` (
  `statistics_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `value` decimal(15,4) NOT NULL,
  PRIMARY KEY (`statistics_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Dumping data for table `oc_statistics`
--

INSERT INTO `oc_statistics` (`statistics_id`, `code`, `value`) VALUES
(1, 'order_sale', 0),
(2, 'order_processing', 0),
(3, 'order_complete', 0),
(4, 'order_other', 0),
(5, 'returns', 0),
(6, 'product', 0),
(7, 'review', 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_session`
--

DROP TABLE IF EXISTS `oc_session`;
CREATE TABLE `oc_session` (
  `session_id` varchar(64) NOT NULL,
  `data` text NOT NULL,
  `expire` datetime NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_setting`
--

DROP TABLE IF EXISTS `oc_codecart_carrier_city`;
CREATE TABLE `oc_codecart_carrier_city` (
  `carrier_city_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `carrier_id` varchar(16) NOT NULL,
  `provider` varchar(24) NOT NULL,
  `external_id` varchar(128) NOT NULL,
  `name` varchar(191) NOT NULL,
  `region` varchar(191) NOT NULL DEFAULT '',
  `district` varchar(191) NOT NULL DEFAULT '',
  `search_name` varchar(255) NOT NULL DEFAULT '',
  `extra_json` text,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`carrier_city_id`),
  UNIQUE KEY `carrier_external` (`carrier_id`,`external_id`),
  KEY `carrier_name` (`carrier_id`,`name`),
  KEY `provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_order_delivery`;
CREATE TABLE `oc_codecart_order_delivery` (
  `order_id` int(11) NOT NULL,
  `carrier_id` varchar(16) NOT NULL DEFAULT '',
  `provider` varchar(24) NOT NULL DEFAULT '',
  `carrier_name` varchar(128) NOT NULL DEFAULT '',
  `city_external_id` varchar(128) NOT NULL DEFAULT '',
  `city_name` varchar(191) NOT NULL DEFAULT '',
  `branch_external_id` varchar(128) NOT NULL DEFAULT '',
  `branch_name` varchar(255) NOT NULL DEFAULT '',
  `branch_address` varchar(255) NOT NULL DEFAULT '',
  `postcode` varchar(32) NOT NULL DEFAULT '',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`order_id`),
  KEY `carrier_provider` (`carrier_id`,`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_google_identity`;
CREATE TABLE `oc_codecart_google_identity` (
  `google_identity_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `google_sub_hash` char(64) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`google_identity_id`),
  UNIQUE KEY `google_sub_hash` (`google_sub_hash`),
  UNIQUE KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_scheduler`;
CREATE TABLE `oc_codecart_scheduler` (
  `scheduler_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(128) NOT NULL,
  `route` varchar(255) NOT NULL,
  `args` mediumtext NOT NULL,
  `interval_seconds` int(11) NOT NULL DEFAULT '3600',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_last` datetime DEFAULT NULL,
  `date_next` datetime NOT NULL,
  `last_duration_ms` int(11) NOT NULL DEFAULT '0',
  `last_status` varchar(16) NOT NULL DEFAULT '',
  `last_message` varchar(1000) NOT NULL DEFAULT '',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`scheduler_id`),
  UNIQUE KEY `code` (`code`),
  KEY `due` (`status`,`date_next`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oc_codecart_scheduler` (`code`,`route`,`args`,`interval_seconds`,`status`,`date_last`,`date_next`,`last_duration_ms`,`last_status`,`last_message`,`date_added`,`date_modified`) VALUES
('core.cart.cleanup','cron/cart','{}',3600,1,NULL,NOW(),0,'','',NOW(),NOW()),
('core.currency.refresh','cron/currency','{}',86400,1,NULL,NOW(),0,'','',NOW(),NOW()),
('core.health.check','cron/health','{}',21600,1,NULL,NOW(),0,'','',NOW(),NOW()),
('core.security.cleanup','cron/security','{}',86400,1,NULL,NOW(),0,'','',NOW(),NOW()),
('core.stock.notify','cron/stock_notify','{}',300,1,NULL,NOW(),0,'','',NOW(),NOW()),
('core.mail.campaign','cron/mail_campaign','{}',60,1,NULL,NOW(),0,'','',NOW(),NOW()),
('core.queue.worker','cron/queue_worker','{}',60,1,NULL,NOW(),0,'','',NOW(),NOW());

DROP TABLE IF EXISTS `oc_codecart_queue`;
CREATE TABLE `oc_codecart_queue` (
  `queue_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(128) NOT NULL,
  `route` varchar(255) NOT NULL,
  `args` mediumtext NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '100',
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
  `max_attempts` smallint(5) unsigned NOT NULL DEFAULT '3',
  `available_at` datetime NOT NULL,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` varchar(96) DEFAULT NULL,
  `last_error` text,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`queue_id`),
  KEY `claim` (`status`,`available_at`,`priority`,`queue_id`),
  KEY `code_status` (`code`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_relation`;
CREATE TABLE `oc_codecart_relation` (
  `relation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_type` varchar(32) NOT NULL,
  `source_id` int(11) unsigned NOT NULL,
  `target_type` varchar(32) NOT NULL,
  `target_id` int(11) unsigned NOT NULL,
  `relation_type` varchar(32) NOT NULL DEFAULT 'related',
  `origin` varchar(16) NOT NULL DEFAULT 'rule',
  `score` smallint(5) unsigned NOT NULL DEFAULT '0',
  `reason` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`relation_id`),
  UNIQUE KEY `relation_unique` (`source_type`,`source_id`,`target_type`,`target_id`,`relation_type`,`origin`),
  KEY `source_lookup` (`source_type`,`source_id`,`relation_type`,`origin`,`status`,`score`),
  KEY `target_lookup` (`target_type`,`target_id`,`relation_type`,`status`),
  KEY `origin_status` (`origin`,`status`,`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_user_authorize`;
CREATE TABLE `oc_codecart_user_authorize` (
  `authorize_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` char(64) NOT NULL DEFAULT '',
  `code_hash` char(64) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(512) NOT NULL DEFAULT '',
  `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `date_added` datetime NOT NULL,
  `date_expire` datetime NOT NULL,
  `date_used` datetime DEFAULT NULL,
  PRIMARY KEY (`authorize_id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_status` (`user_id`,`status`),
  KEY `status_expire` (`status`,`date_expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_customer_authorize`;
CREATE TABLE `oc_codecart_customer_authorize` (
  `authorize_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `token` char(64) NOT NULL DEFAULT '',
  `code_hash` char(64) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(512) NOT NULL DEFAULT '',
  `attempts` smallint(5) unsigned NOT NULL DEFAULT '0',
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `date_added` datetime NOT NULL,
  `date_expire` datetime NOT NULL,
  `date_used` datetime DEFAULT NULL,
  PRIMARY KEY (`authorize_id`),
  UNIQUE KEY `token` (`token`),
  KEY `customer_status` (`customer_id`,`status`),
  KEY `status_expire` (`status`,`date_expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_idempotency`;
CREATE TABLE `oc_codecart_idempotency` (
  `idempotency_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(96) NOT NULL,
  `idempotency_key` char(64) NOT NULL,
  `fingerprint` char(64) NOT NULL DEFAULT '',
  `owner_token` char(64) NOT NULL DEFAULT '',
  `attempt_count` int(10) unsigned NOT NULL DEFAULT '1',
  `status` varchar(16) NOT NULL DEFAULT 'processing',
  `result` mediumtext NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `date_expire` datetime NOT NULL,
  PRIMARY KEY (`idempotency_id`),
  UNIQUE KEY `scope_key` (`scope`,`idempotency_key`),
  KEY `expire` (`date_expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_migration`;
CREATE TABLE `oc_codecart_migration` (
  `migration_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(96) NOT NULL,
  `migration` varchar(190) NOT NULL,
  `version` varchar(32) NOT NULL DEFAULT '',
  `checksum` char(64) NOT NULL DEFAULT '',
  `date_applied` datetime NOT NULL,
  PRIMARY KEY (`migration_id`),
  UNIQUE KEY `scope_migration` (`scope`,`migration`),
  KEY `scope_version` (`scope`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_notification`;
CREATE TABLE `oc_codecart_notification` (
  `notification_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(160) NOT NULL,
  `severity` varchar(16) NOT NULL DEFAULT 'warning',
  `title` varchar(190) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `route` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(16) NOT NULL DEFAULT 'unread',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`notification_id`),
  UNIQUE KEY `code` (`code`),
  KEY `status_severity` (`status`,`severity`),
  KEY `date_modified` (`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_gdpr_request`;
CREATE TABLE `oc_codecart_gdpr_request` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `email` varchar(96) NOT NULL DEFAULT '',
  `type` varchar(16) NOT NULL,
  `token` char(64) NOT NULL DEFAULT '',
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `date_added` datetime NOT NULL,
  `date_confirmed` datetime DEFAULT NULL,
  `date_processed` datetime DEFAULT NULL,
  `date_expire` datetime NOT NULL,
  PRIMARY KEY (`request_id`),
  KEY `customer_status` (`customer_id`,`status`),
  KEY `token_status` (`token`,`status`),
  KEY `status_added` (`status`,`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_gdpr_audit`;
CREATE TABLE `oc_codecart_gdpr_audit` (
  `audit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL DEFAULT '0',
  `actor_type` varchar(16) NOT NULL DEFAULT '',
  `actor_id` int(11) NOT NULL DEFAULT '0',
  `action` varchar(64) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `details` varchar(1000) NOT NULL DEFAULT '',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `request_id` (`request_id`),
  KEY `date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `oc_codecart_admin_login_attempt`;
CREATE TABLE `oc_codecart_admin_login_attempt` (
  `attempt_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(96) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`attempt_id`),
  KEY `username_ip_date` (`username`,`ip`,`date_added`),
  KEY `ip_date` (`ip`,`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_user_mfa`;
CREATE TABLE `oc_codecart_user_mfa` (
  `mfa_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(16) NOT NULL DEFAULT 'totp',
  `secret` text NOT NULL,
  `recovery_codes` mediumtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `last_counter` bigint(20) NOT NULL DEFAULT '-1',
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `date_used` datetime DEFAULT NULL,
  PRIMARY KEY (`mfa_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_codecart_security_audit`;
CREATE TABLE `oc_codecart_security_audit` (
  `audit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_type` varchar(24) NOT NULL DEFAULT '',
  `actor_id` int(11) NOT NULL DEFAULT '0',
  `username` varchar(96) NOT NULL DEFAULT '',
  `event` varchar(96) NOT NULL,
  `outcome` varchar(24) NOT NULL DEFAULT 'info',
  `severity` varchar(16) NOT NULL DEFAULT 'info',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(512) NOT NULL DEFAULT '',
  `context` mediumtext NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `event_date` (`event`,`date_added`),
  KEY `actor_date` (`actor_type`,`actor_id`,`date_added`),
  KEY `date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `oc_setting`;
CREATE TABLE `oc_setting` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `code` varchar(128) NOT NULL,
  `key` varchar(128) NOT NULL,
  `value` text NOT NULL,
  `serialized` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`setting_id`),
  KEY `store_code` (`store_id`,`code`),
  KEY `store_key` (`store_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_setting`
--

INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'config', 'config_robots', 'abot\r\ndbot\r\nebot\r\nhbot\r\nkbot\r\nlbot\r\nmbot\r\nnbot\r\nobot\r\npbot\r\nrbot\r\nsbot\r\ntbot\r\nvbot\r\nybot\r\nzbot\r\nbot.\r\nbot/\r\n_bot\r\n.bot\r\n/bot\r\n-bot\r\n:bot\r\n(bot\r\ncrawl\r\nslurp\r\nspider\r\nseek\r\naccoona\r\nacoon\r\nadressendeutschland\r\nah-ha.com\r\nahoy\r\naltavista\r\nananzi\r\nanthill\r\nappie\r\narachnophilia\r\narale\r\naraneo\r\naranha\r\narchitext\r\naretha\r\narks\r\nasterias\r\natlocal\r\natn\r\natomz\r\naugurfind\r\nbackrub\r\nbannana_bot\r\nbaypup\r\nbdfetch\r\nbig brother\r\nbiglotron\r\nbjaaland\r\nblackwidow\r\nblaiz\r\nblog\r\nblo.\r\nbloodhound\r\nboitho\r\nbooch\r\nbradley\r\nbutterfly\r\ncalif\r\ncassandra\r\nccubee\r\ncfetch\r\ncharlotte\r\nchurl\r\ncienciaficcion\r\ncmc\r\ncollective\r\ncomagent\r\ncombine\r\ncomputingsite\r\ncsci\r\ncurl\r\ncusco\r\ndaumoa\r\ndeepindex\r\ndelorie\r\ndepspid\r\ndeweb\r\ndie blinde kuh\r\ndigger\r\nditto\r\ndmoz\r\ndocomo\r\ndownload express\r\ndtaagent\r\ndwcp\r\nebiness\r\nebingbong\r\ne-collector\r\nejupiter\r\nemacs-w3 search engine\r\nesther\r\nevliya celebi\r\nezresult\r\nfalcon\r\nfelix ide\r\nferret\r\nfetchrover\r\nfido\r\nfindlinks\r\nfireball\r\nfish search\r\nfouineur\r\nfunnelweb\r\ngazz\r\ngcreep\r\ngenieknows\r\ngetterroboplus\r\ngeturl\r\nglx\r\ngoforit\r\ngolem\r\ngrabber\r\ngrapnel\r\ngralon\r\ngriffon\r\ngromit\r\ngrub\r\ngulliver\r\nhamahakki\r\nharvest\r\nhavindex\r\nhelix\r\nheritrix\r\nhku www octopus\r\nhomerweb\r\nhtdig\r\nhtml index\r\nhtml_analyzer\r\nhtmlgobble\r\nhubater\r\nhyper-decontextualizer\r\nia_archiver\r\nibm_planetwide\r\nichiro\r\niconsurf\r\niltrovatore\r\nimage.kapsi.net\r\nimagelock\r\nincywincy\r\nindexer\r\ninfobee\r\ninformant\r\ningrid\r\ninktomisearch.com\r\ninspector web\r\nintelliagent\r\ninternet shinchakubin\r\nip3000\r\niron33\r\nisraeli-search\r\nivia\r\njack\r\njakarta\r\njavabee\r\njetbot\r\njumpstation\r\nkatipo\r\nkdd-explorer\r\nkilroy\r\nknowledge\r\nkototoi\r\nkretrieve\r\nlabelgrabber\r\nlachesis\r\nlarbin\r\nlegs\r\nlibwww\r\nlinkalarm\r\nlink validator\r\nlinkscan\r\nlockon\r\nlwp\r\nlycos\r\nmagpie\r\nmantraagent\r\nmapoftheinternet\r\nmarvin/\r\nmattie\r\nmediafox\r\nmediapartners\r\nmercator\r\nmerzscope\r\nmicrosoft url control\r\nminirank\r\nmiva\r\nmj12\r\nmnogosearch\r\nmoget\r\nmonster\r\nmoose\r\nmotor\r\nmultitext\r\nmuncher\r\nmuscatferret\r\nmwd.search\r\nmyweb\r\nnajdi\r\nnameprotect\r\nnationaldirectory\r\nnazilla\r\nncsa beta\r\nnec-meshexplorer\r\nnederland.zoek\r\nnetcarta webmap engine\r\nnetmechanic\r\nnetresearchserver\r\nnetscoop\r\nnewscan-online\r\nnhse\r\nnokia6682/\r\nnomad\r\nnoyona\r\nnutch\r\nnzexplorer\r\nobjectssearch\r\noccam\r\nomni\r\nopen text\r\nopenfind\r\nopenintelligencedata\r\norb search\r\nosis-project\r\npack rat\r\npageboy\r\npagebull\r\npage_verifier\r\npanscient\r\nparasite\r\npartnersite\r\npatric\r\npear.\r\npegasus\r\nperegrinator\r\npgp key agent\r\nphantom\r\nphpdig\r\npicosearch\r\npiltdownman\r\npimptrain\r\npinpoint\r\npioneer\r\npiranha\r\nplumtreewebaccessor\r\npogodak\r\npoirot\r\npompos\r\npoppelsdorf\r\npoppi\r\npopular iconoclast\r\npsycheclone\r\npublisher\r\npython\r\nrambler\r\nraven search\r\nroach\r\nroad runner\r\nroadhouse\r\nrobbie\r\nrobofox\r\nrobozilla\r\nrules\r\nsalty\r\nsbider\r\nscooter\r\nscoutjet\r\nscrubby\r\nsearch.\r\nsearchprocess\r\nsemanticdiscovery\r\nsenrigan\r\nsg-scout\r\nshai''hulud\r\nshark\r\nshopwiki\r\nsidewinder\r\nsift\r\nsilk\r\nsimmany\r\nsite searcher\r\nsite valet\r\nsitetech-rover\r\nskymob.com\r\nsleek\r\nsmartwit\r\nsna-\r\nsnappy\r\nsnooper\r\nsohu\r\nspeedfind\r\nsphere\r\nsphider\r\nspinner\r\nspyder\r\nsteeler/\r\nsuke\r\nsuntek\r\nsupersnooper\r\nsurfnomore\r\nsven\r\nsygol\r\nszukacz\r\ntach black widow\r\ntarantula\r\ntempleton\r\n/teoma\r\nt-h-u-n-d-e-r-s-t-o-n-e\r\ntheophrastus\r\ntitan\r\ntitin\r\ntkwww\r\ntoutatis\r\nt-rex\r\ntutorgig\r\ntwiceler\r\ntwisted\r\nucsd\r\nudmsearch\r\nurl check\r\nupdated\r\nvagabondo\r\nvalkyrie\r\nverticrawl\r\nvictoria\r\nvision-search\r\nvolcano\r\nvoyager/\r\nvoyager-hc\r\nw3c_validator\r\nw3m2\r\nw3mir\r\nwalker\r\nwallpaper\r\nwanderer\r\nwauuu\r\nwavefire\r\nweb core\r\nweb hopper\r\nweb wombat\r\nwebbandit\r\nwebcatcher\r\nwebcopy\r\nwebfoot\r\nweblayers\r\nweblinker\r\nweblog monitor\r\nwebmirror\r\nwebmonkey\r\nwebquest\r\nwebreaper\r\nwebsitepulse\r\nwebsnarf\r\nwebstolperer\r\nwebvac\r\nwebwalk\r\nwebwatch\r\nwebwombat\r\nwebzinger\r\nwhizbang\r\nwhowhere\r\nwild ferret\r\nworldlight\r\nwwwc\r\nwwwster\r\nxenu\r\nxget\r\nxift\r\nxirq\r\nyanga\r\nyeti\r\nyodao\r\nzao\r\nzippp\r\nzyborg', 0),
(0, 'config', 'config_shared', '0', 0),
(0, 'config', 'config_secure', '0', 0),
(0, 'config', 'config_fraud_detection', '0', 0),
(0, 'config', 'config_ftp_status', '0', 0),
(0, 'config', 'config_ftp_root', '', 0),
(0, 'config', 'config_ftp_password', '', 0),
(0, 'config', 'config_ftp_username', '', 0),
(0, 'config', 'config_ftp_port', '21', 0),
(0, 'config', 'config_ftp_hostname', '', 0),
(0, 'config', 'config_meta_title', 'CodeCart PRO Demo Store — сучасний демонстраційний інтернет-магазин для України', 0),
(0, 'config', 'config_meta_description', 'Готовий демонстраційний магазин на CodeCart PRO 3.0.6.0: категорії, товари з опціями, блог, інформаційні сторінки, адаптивна вітрина та SEO-ready структура для України.', 0),
(0, 'config', 'config_meta_keyword', '', 0),
(0, 'config', 'config_theme', 'codecart', 0),
(0, 'codecart_core', 'codecart_install_origin', 'fresh', 0),
(0, 'config', 'config_layout_id', '4', 0),
(0, 'config', 'config_country_id', '220', 0),
(0, 'config', 'config_zone_id', '3491', 0),
(0, 'config', 'config_timezone', 'Europe/Kyiv', 0),
(0, 'config', 'config_language', 'uk-ua', 0),
(0, 'config', 'config_admin_language', 'uk-ua', 0),
(0, 'config', 'config_currency', 'UAH', 0),
(0, 'config', 'config_currency_auto', '1', 0),
(0, 'config', 'config_length_class_id', '1', 0),
(0, 'config', 'config_weight_class_id', '1', 0),
(0, 'config', 'config_product_count', '1', 0),
(0, 'config', 'config_limit_admin', '20', 0),
(0, 'config', 'config_limit_autocomplete', '5', 0),
(0, 'config', 'config_review_status', '1', 0),
(0, 'config', 'config_review_guest', '1', 0),
(0, 'config', 'config_voucher_min', '1', 0),
(0, 'config', 'config_voucher_max', '1000', 0),
(0, 'config', 'config_tax', '1', 0),
(0, 'config', 'config_tax_default', 'shipping', 0),
(0, 'config', 'config_tax_customer', 'shipping', 0),
(0, 'config', 'config_customer_online', '0', 0),
(0, 'config', 'config_customer_activity', '0', 0),
(0, 'config', 'config_customer_search', '0', 0),
(0, 'config', 'config_customer_group_id', '1', 0),
(0, 'config', 'config_customer_group_display', '["1","2"]', 1),
(0, 'config', 'config_customer_price', '0', 0),
(0, 'config', 'config_account_id', '3', 0),
(0, 'config', 'config_invoice_prefix', 'INV-2026-00', 0),
(0, 'config', 'config_api_id', '1', 0),
(0, 'config', 'config_cart_weight', '1', 0),
(0, 'config', 'config_checkout_guest', '1', 0),
(0, 'config', 'config_checkout_id', '5', 0),
(0, 'config', 'config_order_status_id', '1', 0),
(0, 'config', 'config_processing_status', '["5","1","2","12","3"]', 1),
(0, 'config', 'config_complete_status', '["5"]', 1),
(0, 'config', 'config_stock_display', '0', 0),
(0, 'config', 'config_stock_warning', '0', 0),
(0, 'config', 'config_stock_checkout', '0', 0),
(0, 'config', 'config_affiliate_approval', '0', 0),
(0, 'config', 'config_affiliate_auto', '0', 0),
(0, 'config', 'config_affiliate_commission', '5', 0),
(0, 'config', 'config_affiliate_id', '4', 0),
(0, 'config', 'config_return_id', '0', 0),
(0, 'config', 'config_return_status_id', '2', 0),
(0, 'config', 'config_logo', 'catalog/logo.webp', 0),
(0, 'config', 'config_icon', 'catalog/favicon.webp', 0),
(0, 'config', 'config_comment', 'Демонстраційний магазин для презентації можливостей CodeCart PRO 3.0.6.0. Перед комерційним запуском замініть демо-контакти, юридичні тексти та умови на реальні дані продавця.', 0),
(0, 'config', 'config_open', 'Пн-Пт 09:00-18:00, Сб 10:00-15:00', 0),
(0, 'config', 'config_image', '', 0),
(0, 'config', 'config_fax', '', 0),
(0, 'config', 'config_telephone', '+380 44 000-00-00', 0),
(0, 'config', 'config_email', 'info@codecartpro.com', 0),
(0, 'config', 'config_geocode', '', 0),
(0, 'config', 'config_map_url', 'https://www.google.com/maps?q=50.4501%2C30.5234&z=15&output=embed', 0),
(0, 'config', 'config_owner', 'CodeCart PRO', 0),
(0, 'config', 'config_address', 'вул. Хрещатик, 10, Київ, 01001, Україна', 0),
(0, 'config', 'config_name', 'CodeCart PRO Demo Store', 0),
(0, 'config', 'config_seo_url', '1', 0),
(0, 'config', 'config_file_max_size', '10485760', 0),
(0, 'config', 'config_file_ext_allowed', 'zip\r\ntxt\r\npng\r\njpe\r\njpeg\r\njpg\r\ngif\r\nbmp\r\nico\r\ntiff\r\ntif\r\nsvg\r\nsvgz\r\nwebp\r\nzip\r\nrar\r\nmsi\r\ncab\r\nmp3\r\nqt\r\nmov\r\npdf\r\npsd\r\nai\r\neps\r\nps\r\ndoc', 0),
(0, 'config', 'config_file_mime_allowed', 'text/plain\r\nimage/png\r\nimage/jpeg\r\nimage/gif\r\nimage/bmp\r\nimage/tiff\r\nimage/svg+xml\r\nimage/webp\r\napplication/zip\r\n&quot;application/zip&quot;\r\napplication/x-zip\r\n&quot;application/x-zip&quot;\r\napplication/x-zip-compressed\r\n&quot;application/x-zip-compressed&quot;\r\napplication/rar\r\n&quot;application/rar&quot;\r\napplication/x-rar\r\n&quot;application/x-rar&quot;\r\napplication/x-rar-compressed\r\n&quot;application/x-rar-compressed&quot;\r\napplication/octet-stream\r\n&quot;application/octet-stream&quot;\r\naudio/mpeg\r\nvideo/quicktime\r\napplication/pdf', 0),
(0, 'config', 'config_maintenance', '0', 0),
(0, 'config', 'config_password', '1', 0),
(0, 'config', 'config_encryption', '', 0),
(0, 'config', 'config_compression', '0', 0),
(0, 'config', 'config_error_display', '0', 0),
(0, 'config', 'config_error_log', '1', 0),
(0, 'config', 'config_error_filename', 'error.log', 0),
(0, 'config', 'config_google_analytics', '', 0),
(0, 'config', 'config_mail_engine', 'mail', 0),
(0, 'config', 'config_mail_parameter', '', 0),
(0, 'config', 'config_mail_smtp_hostname', '', 0),
(0, 'config', 'config_mail_smtp_username', '', 0),
(0, 'config', 'config_mail_smtp_password', '', 0),
(0, 'config', 'config_mail_smtp_port', '25', 0),
(0, 'config', 'config_mail_smtp_timeout', '5', 0),
(0, 'config', 'config_mail_alert_email', '', 0),
(0, 'config', 'config_mail_alert', '["order"]', 1),
(0, 'config', 'config_captcha', '', 0),
(0, 'config', 'config_captcha_page', '["review","return","contact"]', 1),
(0, 'config', 'config_login_attempts', '5', 0),
(0, 'config', 'config_noindex_status', '1', 0),
(0, 'payment_free_checkout', 'payment_free_checkout_status', '1', 0),
(0, 'payment_free_checkout', 'payment_free_checkout_order_status_id', '1', 0),
(0, 'payment_free_checkout', 'payment_free_checkout_sort_order', '1', 0),
(0, 'payment_cod', 'payment_cod_sort_order', '5', 0),
(0, 'payment_cod', 'payment_cod_total', '0.01', 0),
(0, 'payment_cod', 'payment_cod_order_status_id', '1', 0),
(0, 'payment_cod', 'payment_cod_geo_zone_id', '0', 0),
(0, 'payment_cod', 'payment_cod_status', '1', 0),
(0, 'shipping_flat', 'shipping_flat_sort_order', '1', 0),
(0, 'shipping_flat', 'shipping_flat_status', '1', 0),
(0, 'shipping_flat', 'shipping_flat_geo_zone_id', '0', 0),
(0, 'shipping_flat', 'shipping_flat_tax_class_id', '9', 0),
(0, 'shipping_flat', 'shipping_flat_cost', '5.00', 0),
(0, 'total_shipping', 'total_shipping_sort_order', '3', 0),
(0, 'total_sub_total', 'total_sub_total_sort_order', '1', 0),
(0, 'total_sub_total', 'total_sub_total_status', '1', 0),
(0, 'total_tax', 'total_tax_status', '1', 0),
(0, 'total_total', 'total_total_sort_order', '9', 0),
(0, 'total_total', 'total_total_status', '1', 0),
(0, 'total_tax', 'total_tax_sort_order', '5', 0),
(0, 'total_credit', 'total_credit_sort_order', '7', 0),
(0, 'total_credit', 'total_credit_status', '1', 0),
(0, 'total_reward', 'total_reward_sort_order', '2', 0),
(0, 'total_reward', 'total_reward_status', '1', 0),
(0, 'total_shipping', 'total_shipping_status', '1', 0),
(0, 'total_shipping', 'total_shipping_estimator', '1', 0),
(0, 'total_coupon', 'total_coupon_sort_order', '4', 0),
(0, 'total_coupon', 'total_coupon_status', '1', 0),
(0, 'total_voucher', 'total_voucher_sort_order', '8', 0),
(0, 'total_voucher', 'total_voucher_status', '1', 0),
(0, 'module_category', 'module_category_status', '1', 0),
(0, 'module_account', 'module_account_status', '1', 0),
(0, 'theme_default', 'theme_default_product_limit', '15', 0),
(0, 'theme_codecart', 'theme_codecart_product_limit', '15', 0),
(0, 'theme_default', 'theme_default_product_description_length', '120', 0),
(0, 'theme_codecart', 'theme_codecart_product_description_length', '120', 0),
(0, 'theme_default', 'theme_default_image_thumb_width', '640', 0),
(0, 'theme_codecart', 'theme_codecart_image_thumb_width', '640', 0),
(0, 'theme_default', 'theme_default_image_thumb_height', '480', 0),
(0, 'theme_codecart', 'theme_codecart_image_thumb_height', '480', 0),
(0, 'theme_default', 'theme_default_image_popup_width', '1200', 0),
(0, 'theme_codecart', 'theme_codecart_image_popup_width', '1200', 0),
(0, 'theme_default', 'theme_default_image_popup_height', '900', 0),
(0, 'theme_codecart', 'theme_codecart_image_popup_height', '900', 0),
(0, 'theme_default', 'theme_default_image_category_width', '320', 0),
(0, 'theme_codecart', 'theme_codecart_image_category_width', '320', 0),
(0, 'theme_default', 'theme_default_image_category_height', '240', 0),
(0, 'theme_codecart', 'theme_codecart_image_category_height', '240', 0),
(0, 'theme_default', 'theme_default_image_manufacturer_width', '180', 0),
(0, 'theme_codecart', 'theme_codecart_image_manufacturer_width', '180', 0),
(0, 'theme_default', 'theme_default_image_manufacturer_height', '100', 0),
(0, 'theme_codecart', 'theme_codecart_image_manufacturer_height', '100', 0),
(0, 'theme_default', 'theme_default_image_product_width', '600', 0),
(0, 'theme_codecart', 'theme_codecart_image_product_width', '600', 0),
(0, 'theme_default', 'theme_default_image_product_height', '600', 0),
(0, 'theme_codecart', 'theme_codecart_image_product_height', '600', 0),
(0, 'theme_default', 'theme_default_image_additional_width', '100', 0),
(0, 'theme_codecart', 'theme_codecart_image_additional_width', '100', 0),
(0, 'theme_default', 'theme_default_image_additional_height', '100', 0),
(0, 'theme_codecart', 'theme_codecart_image_additional_height', '100', 0),
(0, 'theme_default', 'theme_default_image_related_width', '260', 0),
(0, 'theme_codecart', 'theme_codecart_image_related_width', '260', 0),
(0, 'theme_default', 'theme_default_image_related_height', '260', 0),
(0, 'theme_codecart', 'theme_codecart_image_related_height', '260', 0),
(0, 'theme_default', 'theme_default_image_compare_width', '120', 0),
(0, 'theme_codecart', 'theme_codecart_image_compare_width', '120', 0),
(0, 'theme_default', 'theme_default_image_compare_height', '120', 0),
(0, 'theme_codecart', 'theme_codecart_image_compare_height', '120', 0),
(0, 'theme_default', 'theme_default_image_wishlist_width', '80', 0),
(0, 'theme_codecart', 'theme_codecart_image_wishlist_width', '80', 0),
(0, 'theme_default', 'theme_default_image_wishlist_height', '80', 0),
(0, 'theme_codecart', 'theme_codecart_image_wishlist_height', '80', 0),
(0, 'theme_default', 'theme_default_image_cart_height', '80', 0),
(0, 'theme_codecart', 'theme_codecart_image_cart_height', '80', 0),
(0, 'theme_default', 'theme_default_image_cart_width', '80', 0),
(0, 'theme_codecart', 'theme_codecart_image_cart_width', '80', 0),
(0, 'theme_default', 'theme_default_image_location_height', '50', 0),
(0, 'theme_codecart', 'theme_codecart_image_location_height', '50', 0),
(0, 'theme_default', 'theme_default_image_location_width', '268', 0),
(0, 'theme_codecart', 'theme_codecart_image_location_width', '268', 0),
(0, 'theme_default', 'theme_default_directory', 'default', 0),
(0, 'theme_codecart', 'theme_codecart_directory', 'codecart', 0),
(0, 'theme_default', 'theme_default_status', '1', 0),
(0, 'theme_codecart', 'theme_codecart_status', '1', 0),
(0, 'dashboard_activity', 'dashboard_activity_status', '1', 0),
(0, 'dashboard_activity', 'dashboard_activity_sort_order', '7', 0),
(0, 'dashboard_sale', 'dashboard_sale_status', '1', 0),
(0, 'dashboard_sale', 'dashboard_sale_width', '3', 0),
(0, 'dashboard_chart', 'dashboard_chart_status', '1', 0),
(0, 'dashboard_chart', 'dashboard_chart_width', '6', 0),
(0, 'dashboard_customer', 'dashboard_customer_status', '1', 0),
(0, 'dashboard_customer', 'dashboard_customer_width', '3', 0),
(0, 'dashboard_map', 'dashboard_map_status', '1', 0),
(0, 'dashboard_map', 'dashboard_map_width', '6', 0),
(0, 'dashboard_online', 'dashboard_online_status', '1', 0),
(0, 'dashboard_online', 'dashboard_online_width', '3', 0),
(0, 'dashboard_order', 'dashboard_order_sort_order', '1', 0),
(0, 'dashboard_order', 'dashboard_order_status', '1', 0),
(0, 'dashboard_order', 'dashboard_order_width', '3', 0),
(0, 'dashboard_sale', 'dashboard_sale_sort_order', '2', 0),
(0, 'dashboard_customer', 'dashboard_customer_sort_order', '3', 0),
(0, 'dashboard_online', 'dashboard_online_sort_order', '4', 0),
(0, 'dashboard_map', 'dashboard_map_sort_order', '5', 0),
(0, 'dashboard_chart', 'dashboard_chart_sort_order', '6', 0),
(0, 'dashboard_recent', 'dashboard_recent_status', '1', 0),
(0, 'dashboard_recent', 'dashboard_recent_sort_order', '8', 0),
(0, 'dashboard_activity', 'dashboard_activity_width', '4', 0),
(0, 'dashboard_recent', 'dashboard_recent_width', '8', 0),
(0, 'report_customer_activity', 'report_customer_activity_status', '1', 0),
(0, 'report_customer_activity', 'report_customer_activity_sort_order', '1', 0),
(0, 'report_customer_order', 'report_customer_order_status', '1', 0),
(0, 'report_customer_order', 'report_customer_order_sort_order', '2', 0),
(0, 'report_customer_reward', 'report_customer_reward_status', '1', 0),
(0, 'report_customer_reward', 'report_customer_reward_sort_order', '3', 0),
(0, 'report_customer_search', 'report_customer_search_sort_order', '3', 0),
(0, 'report_customer_search', 'report_customer_search_status', '1', 0),
(0, 'report_customer_transaction', 'report_customer_transaction_status', '1', 0),
(0, 'report_customer_transaction', 'report_customer_transaction_status_sort_order', '4', 0),
(0, 'report_sale_tax', 'report_sale_tax_status', '1', 0),
(0, 'report_sale_tax', 'report_sale_tax_sort_order', '5', 0),
(0, 'report_sale_shipping', 'report_sale_shipping_status', '1', 0),
(0, 'report_sale_shipping', 'report_sale_shipping_sort_order', '6', 0),
(0, 'report_sale_return', 'report_sale_return_status', '1', 0),
(0, 'report_sale_return', 'report_sale_return_sort_order', '7', 0),
(0, 'report_sale_order', 'report_sale_order_status', '1', 0),
(0, 'report_sale_order', 'report_sale_order_sort_order', '8', 0),
(0, 'report_sale_coupon', 'report_sale_coupon_status', '1', 0),
(0, 'report_sale_coupon', 'report_sale_coupon_sort_order', '9', 0),
(0, 'report_product_viewed', 'report_product_viewed_status', '1', 0),
(0, 'report_product_viewed', 'report_product_viewed_sort_order', '10', 0),
(0, 'report_product_purchased', 'report_product_purchased_status', '1', 0),
(0, 'report_product_purchased', 'report_product_purchased_sort_order', '11', 0),
(0, 'report_marketing', 'report_marketing_status', '1', 0),
(0, 'report_marketing', 'report_marketing_sort_order', '12', 0),
(0, 'developer', 'developer_theme', '1', 0),
(0, 'developer', 'developer_sass', '1', 0),
(0, 'configblog', 'configblog_name', 'Блог', 0),
(0, 'configblog', 'configblog_html_h1', 'Блог для інтернет-магазину на OpenCart', 0),
(0, 'configblog', 'configblog_meta_title', 'Блог для інтернет-магазину на OpenCart', 0),
(0, 'configblog', 'configblog_meta_description', 'Блог для інтернет-магазину на OpenCart', 0),
(0, 'configblog', 'configblog_meta_keyword', 'Блог для інтернет-магазину на OpenCart', 0),
(0, 'configblog', 'configblog_article_count', '1', 0),
(0, 'configblog', 'configblog_article_limit', '20', 0),
(0, 'configblog', 'configblog_article_description_length', '200', 0),
(0, 'configblog', 'configblog_limit_admin', '20', 0),
(0, 'configblog', 'configblog_blog_menu', '1', 0),
(0, 'configblog', 'configblog_article_download', '1', 0),
(0, 'configblog', 'configblog_review_status', '1', 0),
(0, 'configblog', 'configblog_review_guest', '1', 0),
(0, 'configblog', 'configblog_review_mail', '1', 0),
(0, 'configblog', 'configblog_image_category_width', '50', 0),
(0, 'configblog', 'configblog_image_category_height', '50', 0),
(0, 'configblog', 'configblog_image_article_width', '150', 0),
(0, 'configblog', 'configblog_image_article_height', '150', 0),
(0, 'configblog', 'configblog_image_related_width', '200', 0),
(0, 'configblog', 'configblog_image_related_height', '200', 0),
(0, 'config', 'config_currency_engine', 'nbu', 0),
(0, 'currency_nbu', 'currency_nbu_status', '1', 0),
(0, 'currency_ecb', 'currency_ecb_status', '1', 0),
(0, 'currency_fixer', 'currency_fixer_status', '0', 0),
(0, 'module_blog_category', 'module_blog_category_status', '1', 0),
(0, 'dashboard_domovyk', 'dashboard_domovyk_warning_funtions', 'diskfreespace\r\ndisk_total_space\r\ndisk_total_space\r\nfileperms\r\nfopen\r\nphpversion\r\nopendir\r\nposix_getpwuid\r\nposix_uname', 0),
(0, 'dashboard_domovyk', 'dashboard_domovyk_danger_funtions', 'exec\r\npassthru\r\nini_get\r\nini_get_all\r\nparse_ini_file\r\nphp_uname\r\nsystem\r\nshell_exec\r\nshow_source\r\npcntl_exec\r\npcntl_exec\r\nexpect_popen\r\nproc_open\r\npopen', 0),
(0, 'dashboard_domovyk', 'dashboard_domovyk_free_space_status', '0', 0),
(0, 'dashboard_domovyk', 'dashboard_domovyk_disk_free_space', '500', 0),
(0, 'dashboard_domovyk', 'dashboard_domovyk_cron', '{"logs":{"status":"1","size":"100","time":"30"},"cache":{"status":"0","size":"100","time":"30"},"imagescache":{"status":"0","size":"100","time":"30"}}', 1),
(0, 'dashboard_domovyk', 'dashboard_domovyk_sort_order', '10', 0),
(0, 'dashboard_domovyk', 'dashboard_domovyk_status', '1', 0),
(0, 'dashboard_domovyk', 'dashboard_domovyk_width', '12', 0),
(0, 'captcha_basic', 'captcha_basic_min_age_ms', '900', 0),
(0, 'captcha_basic', 'captcha_basic_mode', 'adaptive', 0),
(0, 'config', 'config_auto_seo_url', '1', 0),
(0, 'config', 'config_checkout_email_fallback', 'checkout@invalid.local', 0),
(0, 'config', 'config_checkout_field_address_1_mode', 'required', 0),
(0, 'config', 'config_checkout_field_address_2_mode', 'optional', 0),
(0, 'config', 'config_checkout_field_city_mode', 'required', 0),
(0, 'config', 'config_checkout_field_company_mode', 'optional', 0),
(0, 'config', 'config_checkout_field_country_mode', 'required', 0),
(0, 'config', 'config_checkout_field_email_mode', 'required', 0),
(0, 'config', 'config_checkout_field_firstname_mode', 'required', 0),
(0, 'config', 'config_checkout_field_lastname_mode', 'required', 0),
(0, 'config', 'config_checkout_field_postcode_mode', 'required', 0),
(0, 'config', 'config_checkout_field_telephone_mode', 'required', 0),
(0, 'config', 'config_checkout_field_zone_mode', 'required', 0),
(0, 'config', 'config_cookie_consent_accent_color', '#0b6fd3', 0),
(0, 'config', 'config_cookie_consent_custom_icon', '', 0),
(0, 'config', 'config_cookie_consent_days', '180', 0),
(0, 'config', 'config_cookie_consent_icon', 'shield_cookie_check', 0),
(0, 'config', 'config_cookie_consent_information_id', '8', 0),
(0, 'config', 'config_cookie_consent_privacy_information_id', '7', 0),
(0, 'config', 'config_cookie_consent_status', '0', 0),
(0, 'config', 'config_currency_trim_zeros', '0', 0),
(0, 'config', 'config_editor', 'summernote', 0),
(0, 'config', 'config_image_webp', '1', 0),
(0, 'config', 'config_image_webp_quality', '82', 0),
(0, 'config', 'config_image_avif', '0', 0),
(0, 'config', 'config_image_avif_quality', '72', 0),
(0, 'config', 'config_quick_checkout_status', '0', 0),
(0, 'config', 'config_seo_filter_allowlist', '', 0),
(0, 'config', 'config_seo_filter_index_mode', 'noindex', 0),
(0, 'config', 'config_seo_presentation_noindex', '1', 0),
(0, 'config', 'config_seo_pro', '0', 0),
(0, 'config', 'config_seo_url_cache', '0', 0),
(0, 'config', 'config_seopro_addslash', '0', 0),
(0, 'config', 'config_seopro_lowercase', '1', 0),
(0, 'config', 'config_tax_display', 'native', 0),
(0, 'currency_nbu', 'currency_nbu_include_disabled', '0', 0),
(0, 'currency_nbu', 'currency_nbu_missing_policy', 'skip', 0),
(0, 'currency_nbu', 'currency_nbu_source', 'auto', 0),
(0, 'currency_nbu', 'currency_nbu_timeout', '15', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_attention_status', '1', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_commerce_status', '1', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_low_stock_limit', '5', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_performance_status', '1', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_quick_actions', '["product_add","orders","cache","ocmod","diagnostics","scheduler","logs"]', 1),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_quick_status', '1', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_security_status', '1', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_seo_status', '1', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_sort_order', '11', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_status', '1', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_system_status', '1', 0),
(0, 'dashboard_codecart_health', 'dashboard_codecart_health_width', '12', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_bank1', '', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_bank2', '', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_total', '0', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_order_status_id', '1', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_geo_zone_id', '0', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_status', '0', 0),
(0, 'payment_bank_transfer', 'payment_bank_transfer_sort_order', '6', 0),
(0, 'payment_liqpay', 'payment_liqpay_public_key', '', 0),
(0, 'payment_liqpay', 'payment_liqpay_private_key', '', 0),
(0, 'payment_liqpay', 'payment_liqpay_sandbox', '0', 0),
(0, 'payment_liqpay', 'payment_liqpay_total', '0', 0),
(0, 'payment_liqpay', 'payment_liqpay_order_status_id', '1', 0),
(0, 'payment_liqpay', 'payment_liqpay_geo_zone_id', '0', 0),
(0, 'payment_liqpay', 'payment_liqpay_status', '0', 0),
(0, 'payment_liqpay', 'payment_liqpay_sort_order', '7', 0),
(0, 'shipping_carrier_choice', 'shipping_carrier_choice_carriers', '[{"id":"pickup","name":{"1":"Самовивіз","2":"Pickup"},"provider":"pickup","api_key":"","api_login":"","api_password":"","api_token":"","cost":"0.0000","status":1,"sort_order":0},{"id":"novaposhta","name":{"1":"Нова пошта","2":"Nova Poshta"},"provider":"nova_poshta","api_key":"","api_login":"","api_password":"","api_token":"","cost":"0.0000","status":1,"sort_order":1}]', 1),
(0, 'shipping_carrier_choice', 'shipping_carrier_choice_geo_zone_id', '0', 0),
(0, 'shipping_carrier_choice', 'shipping_carrier_choice_sort_order', '2', 0),
(0, 'shipping_carrier_choice', 'shipping_carrier_choice_status', '0', 0),
(0, 'shipping_carrier_choice', 'shipping_carrier_choice_tax_class_id', '0', 0),
(0, 'shipping_carrier_choice', 'shipping_carrier_choice_compact_checkout', '1', 0),
(0, 'module_google_login', 'module_google_login_status', '0', 0),
(0, 'module_google_login', 'module_google_login_client_id', '', 0),
(0, 'module_google_login', 'module_google_login_client_secret', '', 0),
(0, 'module_google_login', 'module_google_login_auto_register', '1', 0),
(0, 'module_google_login', 'module_google_login_show_login', '1', 0),
(0, 'module_google_login', 'module_google_login_show_checkout', '0', 0),
(0, 'module_google_login', 'module_google_login_sort_order', '0', 0),
(0, 'theme_default', 'theme_default_accent_color', '#0b6fd3', 0),
(0, 'theme_codecart', 'theme_codecart_accent_color', '#0b6fd3', 0),
(0, 'theme_default', 'theme_default_accent_hover', '#095eb4', 0),
(0, 'theme_codecart', 'theme_codecart_accent_hover', '#095eb4', 0),
(0, 'theme_default', 'theme_default_background_color', '#ffffff', 0),
(0, 'theme_codecart', 'theme_codecart_background_color', '#ffffff', 0),
(0, 'theme_default', 'theme_default_border_color', '#e5e9ef', 0),
(0, 'theme_codecart', 'theme_codecart_border_color', '#e5e9ef', 0),
(0, 'theme_default', 'theme_default_border_radius', '8', 0),
(0, 'theme_codecart', 'theme_codecart_border_radius', '8', 0),
(0, 'theme_default', 'theme_default_button_color', '#0b6fd3', 0),
(0, 'theme_codecart', 'theme_codecart_button_color', '#0b6fd3', 0),
(0, 'theme_default', 'theme_default_button_hover', '#095eb4', 0),
(0, 'theme_codecart', 'theme_codecart_button_hover', '#095eb4', 0),
(0, 'theme_default', 'theme_default_button_text_color', '#ffffff', 0),
(0, 'theme_codecart', 'theme_codecart_button_text_color', '#ffffff', 0),
(0, 'theme_default', 'theme_default_buy_button_color', '#0b6fd3', 0),
(0, 'theme_codecart', 'theme_codecart_buy_button_color', '#0b6fd3', 0),
(0, 'theme_default', 'theme_default_buy_button_hover', '#095eb4', 0),
(0, 'theme_codecart', 'theme_codecart_buy_button_hover', '#095eb4', 0),
(0, 'theme_default', 'theme_default_buy_button_text_color', '#ffffff', 0),
(0, 'theme_codecart', 'theme_codecart_buy_button_text_color', '#ffffff', 0),
(0, 'theme_default', 'theme_default_cart_button_color', '#0b6fd3', 0),
(0, 'theme_codecart', 'theme_codecart_cart_button_color', '#0b6fd3', 0),
(0, 'theme_default', 'theme_default_cart_button_hover', '#095eb4', 0),
(0, 'theme_codecart', 'theme_codecart_cart_button_hover', '#095eb4', 0),
(0, 'theme_default', 'theme_default_cart_button_text_color', '#ffffff', 0),
(0, 'theme_codecart', 'theme_codecart_cart_button_text_color', '#ffffff', 0),
(0, 'theme_default', 'theme_default_catalog_ajax_status', '0', 0),
(0, 'theme_codecart', 'theme_codecart_catalog_ajax_status', '0', 0),
(0, 'theme_default', 'theme_default_commerce_style', 'standard', 0),
(0, 'theme_codecart', 'theme_codecart_commerce_style', 'standard', 0),
(0, 'theme_default', 'theme_default_custom_css', '', 0),
(0, 'theme_codecart', 'theme_codecart_custom_css', '', 0),
(0, 'theme_default', 'theme_default_dark_mode_default', 'light', 0),
(0, 'theme_codecart', 'theme_codecart_dark_mode_default', 'light', 0),
(0, 'theme_default', 'theme_default_dark_mode_status', '1', 0),
(0, 'theme_codecart', 'theme_codecart_dark_mode_status', '1', 0),
(0, 'theme_default', 'theme_default_datetimepicker_theme', 'light', 0),
(0, 'theme_codecart', 'theme_codecart_datetimepicker_theme', 'light', 0),
(0, 'theme_default', 'theme_default_font_family', 'system', 0),
(0, 'theme_codecart', 'theme_codecart_font_family', 'system', 0),
(0, 'theme_default', 'theme_default_footer_background_color', '#303030', 0),
(0, 'theme_codecart', 'theme_codecart_footer_background_color', '#303030', 0),
(0, 'theme_default', 'theme_default_footer_heading_color', '#ffffff', 0),
(0, 'theme_codecart', 'theme_codecart_footer_heading_color', '#ffffff', 0),
(0, 'theme_default', 'theme_default_footer_link_color', '#cccccc', 0),
(0, 'theme_codecart', 'theme_codecart_footer_link_color', '#cccccc', 0),
(0, 'theme_default', 'theme_default_footer_text_color', '#e2e2e2', 0),
(0, 'theme_codecart', 'theme_codecart_footer_text_color', '#e2e2e2', 0),
(0, 'theme_default', 'theme_default_gallery_engine', 'photoswipe', 0),
(0, 'theme_codecart', 'theme_codecart_gallery_engine', 'photoswipe', 0),
(0, 'theme_default', 'theme_default_h1_color', '#273142', 0),
(0, 'theme_codecart', 'theme_codecart_h1_color', '#273142', 0),
(0, 'theme_default', 'theme_default_h2_color', '#273142', 0),
(0, 'theme_codecart', 'theme_codecart_h2_color', '#273142', 0),
(0, 'theme_default', 'theme_default_header_email_status', '1', 0),
(0, 'theme_codecart', 'theme_codecart_header_email_status', '1', 0),
(0, 'theme_default', 'theme_default_header_menu_mode', 'horizontal', 0),
(0, 'theme_codecart', 'theme_codecart_header_menu_mode', 'horizontal', 0),
(0, 'theme_default', 'theme_default_header_phone_status', '1', 0),
(0, 'theme_codecart', 'theme_codecart_header_phone_status', '1', 0),
(0, 'theme_default', 'theme_default_heading_color', '#273142', 0),
(0, 'theme_codecart', 'theme_codecart_heading_color', '#273142', 0),
(0, 'theme_default', 'theme_default_icon_mode', 'auto', 0),
(0, 'theme_codecart', 'theme_codecart_icon_mode', 'auto', 0),
(0, 'theme_default', 'theme_default_option_image_switch_status', '0', 0),
(0, 'theme_codecart', 'theme_codecart_option_image_switch_status', '0', 0),
(0, 'theme_default', 'theme_default_product_card_attribute_limit', '4', 0),
(0, 'theme_codecart', 'theme_codecart_product_card_attribute_limit', '4', 0),
(0, 'theme_default', 'theme_default_product_card_content', 'both', 0),
(0, 'theme_codecart', 'theme_codecart_product_card_content', 'both', 0),
(0, 'theme_default', 'theme_default_product_extra_tab_status', '0', 0),
(0, 'theme_codecart', 'theme_codecart_product_extra_tab_status', '0', 0),
(0, 'theme_default', 'theme_default_purchase_blocks_status', '1', 0),
(0, 'theme_codecart', 'theme_codecart_purchase_blocks_status', '1', 0),
(0, 'theme_default', 'theme_default_sale_price_color', '#d92d20', 0),
(0, 'theme_codecart', 'theme_codecart_sale_price_color', '#d92d20', 0),
(0, 'theme_default', 'theme_default_surface_color', '#f7f9fb', 0),
(0, 'theme_codecart', 'theme_codecart_surface_color', '#f7f9fb', 0),
(0, 'theme_default', 'theme_default_text_color', '#3d4652', 0),
(0, 'theme_codecart', 'theme_codecart_text_color', '#3d4652', 0);

-- --------------------------------------------------------
--
-- Table structure for table `oc_stock_status`
--

DROP TABLE IF EXISTS `oc_stock_status`;
CREATE TABLE `oc_stock_status` (
  `stock_status_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`stock_status_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_stock_status`
--

INSERT INTO `oc_stock_status` (`stock_status_id`, `language_id`, `name`) VALUES
(7, 1, 'В наявності'),
(8, 1, 'Під замовлення'),
(5, 1, 'Немає в наявності'),
(6, 1, 'Очікується через 2-3 дні'),
(7, 2, 'In Stock'),
(8, 2, 'Pre-Order'),
(5, 2, 'Out Of Stock'),
(6, 2, '2-3 Days');

-- --------------------------------------------------------
--
-- Table structure for table `oc_store`
--

DROP TABLE IF EXISTS `oc_store`;
CREATE TABLE `oc_store` (
  `store_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `url` varchar(255) NOT NULL,
  `ssl` varchar(255) NOT NULL,
  PRIMARY KEY (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_tax_class`
--

DROP TABLE IF EXISTS `oc_tax_class`;
CREATE TABLE `oc_tax_class` (
  `tax_class_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(32) NOT NULL,
  `description` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`tax_class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_tax_class`
--

INSERT INTO `oc_tax_class` (`tax_class_id`, `title`, `description`, `date_added`, `date_modified`) VALUES
(9, 'Оподатковувані товари', 'Товари, що оподатковуються', '2026-09-01 10:00:00', '2026-09-01 10:00:00'),
(10, 'Цифрові товари', 'Цифрові та завантажувані товари', '2026-09-01 10:00:00', '2026-09-01 10:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `oc_tax_rate`
--

DROP TABLE IF EXISTS `oc_tax_rate`;
CREATE TABLE `oc_tax_rate` (
  `tax_rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `geo_zone_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(32) NOT NULL,
  `rate` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `type` char(1) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`tax_rate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_tax_rate`
--

INSERT INTO `oc_tax_rate` (`tax_rate_id`, `geo_zone_id`, `name`, `rate`, `type`, `date_added`, `date_modified`) VALUES
(86, 3, 'ПДВ (20%)', '20.0000', 'P', '2026-09-01 10:00:00', '2026-09-01 10:00:00');

-- --------------------------------------------------------
--
-- Table structure for table `oc_tax_rate_to_customer_group`
--

DROP TABLE IF EXISTS `oc_tax_rate_to_customer_group`;
CREATE TABLE `oc_tax_rate_to_customer_group` (
  `tax_rate_id` int(11) NOT NULL,
  `customer_group_id` int(11) NOT NULL,
  PRIMARY KEY (`tax_rate_id`,`customer_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_tax_rate_to_customer_group`
--

INSERT INTO `oc_tax_rate_to_customer_group` (`tax_rate_id`, `customer_group_id`) VALUES
(86, 1),
(86, 2),
(87, 1),
(87, 2);

-- --------------------------------------------------------
--
-- Table structure for table `oc_tax_rule`
--

DROP TABLE IF EXISTS `oc_tax_rule`;
CREATE TABLE `oc_tax_rule` (
  `tax_rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_class_id` int(11) NOT NULL,
  `tax_rate_id` int(11) NOT NULL,
  `based` varchar(10) NOT NULL,
  `priority` int(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`tax_rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_tax_rule`
--

INSERT INTO `oc_tax_rule` (`tax_rule_id`, `tax_class_id`, `tax_rate_id`, `based`, `priority`) VALUES
(121, 10, 86, 'payment', 1),
(128, 9, 86, 'shipping', 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_theme`
--

DROP TABLE IF EXISTS `oc_theme`;
CREATE TABLE `oc_theme` (
  `theme_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `theme` varchar(64) NOT NULL,
  `route` varchar(64) NOT NULL,
  `code` mediumtext NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_translation`
--

DROP TABLE IF EXISTS `oc_translation`;
CREATE TABLE `oc_translation` (
  `translation_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `route` varchar(64) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`translation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_upload`
--

DROP TABLE IF EXISTS `oc_upload`;
CREATE TABLE `oc_upload` (
  `upload_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`upload_id`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_seo_url`
--

DROP TABLE IF EXISTS `oc_seo_url`;
CREATE TABLE `oc_seo_url` (
  `seo_url_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,  
  `query` varchar(255) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  PRIMARY KEY (`seo_url_id`),
  KEY `query_store_language` (`query`(191),`store_id`,`language_id`),
  KEY `keyword_store_language` (`keyword`(191),`store_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_seo_url`
--

INSERT INTO `oc_seo_url` (`seo_url_id`, `store_id`, `language_id`, `query`, `keyword`) VALUES
(601, 0, 1, 'account/voucher', 'vouchers'),
(602, 0, 1, 'account/wishlist', 'wishlist'),
(603, 0, 1, 'account/account', 'my-account'),
(604, 0, 1, 'checkout/cart', 'cart'),
(605, 0, 1, 'checkout/checkout', 'checkout'),
(606, 0, 1, 'account/login', 'login'),
(607, 0, 1, 'account/logout', 'logout'),
(608, 0, 1, 'account/order', 'order-history'),
(609, 0, 1, 'account/newsletter', 'newsletter'),
(610, 0, 1, 'product/special', 'specials'),
(611, 0, 1, 'affiliate/account', 'affiliates'),
(612, 0, 1, 'checkout/voucher', 'gift-vouchers'),
(613, 0, 1, 'product/manufacturer', 'brands'),
(614, 0, 1, 'information/contact', 'contact-us'),
(615, 0, 1, 'account/return/insert', 'request-return'),
(616, 0, 1, 'information/sitemap', 'sitemap'),
(617, 0, 1, 'account/forgotten', 'forgot-password'),
(618, 0, 1, 'account/download', 'downloads'),
(619, 0, 1, 'account/return', 'returns'),
(620, 0, 1, 'account/transaction', 'transactions'),
(621, 0, 1, 'account/register', 'create-account'),
(622, 0, 1, 'product/compare', 'compare-products'),
(623, 0, 1, 'product/search', 'search'),
(624, 0, 1, 'account/edit', 'edit-account'),
(625, 0, 1, 'account/password', 'change-password'),
(626, 0, 1, 'account/address', 'address-book'),
(627, 0, 1, 'account/reward', 'reward-points'),
(628, 0, 1, 'affiliate/edit', 'edit-affiliate-account'),
(629, 0, 1, 'affiliate/password', 'change-affiliate-password'),
(630, 0, 1, 'affiliate/payment', 'affiliate-payment-options'),
(631, 0, 1, 'affiliate/tracking', 'affiliate-tracking-code'),
(632, 0, 1, 'affiliate/transaction', 'affiliate-transactions'),
(633, 0, 1, 'affiliate/logout', 'affiliate-logout'),
(634, 0, 1, 'affiliate/forgotten', 'affiliate-forgot-password'),
(635, 0, 1, 'affiliate/register', 'create-affiliate-account'),
(636, 0, 1, 'affiliate/login', 'affiliate-login'),
(637, 0, 1, 'account/return/add', 'add-return'),
(958, 0, 1, 'product_id=48', 'ipod-classic'),
(856, 0, 1, 'category_id=20', 'desktops'),
(858, 0, 1, 'category_id=26', 'pc'),
(860, 0, 1, 'category_id=27', 'mac'),
(932, 0, 1, 'manufacturer_id=8', 'apple'),
(850, 0, 1, 'information_id=4', 'about_us'),
(946, 0, 1, 'product_id=42', 'apple_cinema_30'),
(892, 0, 1, 'category_id=34', 'mp3-players'),
(878, 0, 1, 'category_id=36', 'test2'),
(862, 0, 1, 'category_id=18', 'laptop-notebook'),
(864, 0, 1, 'category_id=46', 'macs'),
(866, 0, 1, 'category_id=45', 'windows'),
(868, 0, 1, 'category_id=25', 'component'),
(870, 0, 1, 'category_id=29', 'mouse'),
(874, 0, 1, 'category_id=28', 'monitor'),
(876, 0, 1, 'category_id=35', 'test1'),
(880, 0, 1, 'category_id=30', 'printer'),
(882, 0, 1, 'category_id=31', 'scanner'),
(872, 0, 1, 'category_id=32', 'web-camera'),
(983, 0, 2, 'category_id=57', 'tablets'),
(886, 0, 1, 'category_id=17', 'software'),
(888, 0, 1, 'category_id=24', 'smartphone'),
(890, 0, 1, 'category_id=33', 'camera'),
(900, 0, 1, 'category_id=43', 'test11'),
(902, 0, 1, 'category_id=44', 'test12'),
(904, 0, 1, 'category_id=47', 'test15'),
(906, 0, 1, 'category_id=48', 'test16'),
(908, 0, 1, 'category_id=49', 'test17'),
(910, 0, 1, 'category_id=50', 'test18'),
(912, 0, 1, 'category_id=51', 'test19'),
(896, 0, 1, 'category_id=52', 'test20'),
(894, 0, 1, 'category_id=58', 'test25'),
(914, 0, 1, 'category_id=53', 'test21'),
(916, 0, 1, 'category_id=54', 'test22'),
(918, 0, 1, 'category_id=55', 'test23'),
(920, 0, 1, 'category_id=56', 'test24'),
(922, 0, 1, 'category_id=38', 'test4'),
(924, 0, 1, 'category_id=37', 'test5'),
(926, 0, 1, 'category_id=39', 'test6'),
(928, 0, 1, 'category_id=40', 'test7'),
(930, 0, 1, 'category_id=41', 'test8'),
(898, 0, 1, 'category_id=42', 'test9'),
(948, 0, 1, 'product_id=30', 'canon-eos-5d'),
(950, 0, 1, 'product_id=47', 'hp-lp3065'),
(952, 0, 1, 'product_id=28', 'htc-touch-hd'),
(966, 0, 1, 'product_id=43', 'macbook'),
(968, 0, 1, 'product_id=44', 'macbook-air'),
(970, 0, 1, 'product_id=45', 'macbook-pro'),
(972, 0, 1, 'product_id=31', 'nikon-d300'),
(974, 0, 1, 'product_id=29', 'palm-treo-pro'),
(976, 0, 1, 'product_id=35', 'product-8'),
(978, 0, 1, 'product_id=49', 'samsung-galaxy-tab-10-1'),
(980, 0, 1, 'product_id=33', 'samsung-syncmaster-941bw'),
(944, 0, 1, 'product_id=46', 'sony-vaio'),
(954, 0, 1, 'product_id=41', 'imac'),
(960, 0, 1, 'product_id=36', 'ipod-nano'),
(962, 0, 1, 'product_id=34', 'ipod-shuffle'),
(964, 0, 1, 'product_id=32', 'ipod-touch'),
(934, 0, 1, 'manufacturer_id=9', 'canon'),
(938, 0, 1, 'manufacturer_id=5', 'htc'),
(936, 0, 1, 'manufacturer_id=7', 'hewlett-packard'),
(940, 0, 1, 'manufacturer_id=6', 'palm'),
(942, 0, 1, 'manufacturer_id=10', 'sony'),
(1022, 0, 1, 'manufacturer_id=11', 'dell'),
(1024, 0, 1, 'manufacturer_id=12', 'samsung'),
(1026, 0, 1, 'manufacturer_id=13', 'nintendo'),
(1028, 0, 1, 'manufacturer_id=14', 'nikon'),
(1030, 0, 1, 'manufacturer_id=15', 'codecart-pro'),
(848, 0, 1, 'information_id=6', 'delivery'),
(852, 0, 1, 'information_id=3', 'privacy'),
(854, 0, 1, 'information_id=5', 'terms'),
(957, 0, 2, 'product_id=40', 'iphone'),
(956, 0, 1, 'product_id=40', 'iphone'),
(849, 0, 2, 'information_id=6', 'delivery'),
(851, 0, 2, 'information_id=4', 'about_us'),
(853, 0, 2, 'information_id=3', 'privacy'),
(855, 0, 2, 'information_id=5', 'terms'),
(857, 0, 2, 'category_id=20', 'desktops'),
(859, 0, 2, 'category_id=26', 'pc'),
(861, 0, 2, 'category_id=27', 'mac'),
(863, 0, 2, 'category_id=18', 'laptop-notebook'),
(865, 0, 2, 'category_id=46', 'macs'),
(867, 0, 2, 'category_id=45', 'windows'),
(869, 0, 2, 'category_id=25', 'component'),
(871, 0, 2, 'category_id=29', 'mouse'),
(873, 0, 2, 'category_id=32', 'web-camera'),
(875, 0, 2, 'category_id=28', 'monitor'),
(877, 0, 2, 'category_id=35', 'test1'),
(879, 0, 2, 'category_id=36', 'test2'),
(881, 0, 2, 'category_id=30', 'printer'),
(883, 0, 2, 'category_id=31', 'scanner'),
(982, 0, 1, 'category_id=57', 'tablets'),
(887, 0, 2, 'category_id=17', 'software'),
(889, 0, 2, 'category_id=24', 'smartphone'),
(891, 0, 2, 'category_id=33', 'camera'),
(893, 0, 2, 'category_id=34', 'mp3-players'),
(895, 0, 2, 'category_id=58', 'test25'),
(897, 0, 2, 'category_id=52', 'test20'),
(899, 0, 2, 'category_id=42', 'test9'),
(901, 0, 2, 'category_id=43', 'test11'),
(903, 0, 2, 'category_id=44', 'test12'),
(905, 0, 2, 'category_id=47', 'test15'),
(907, 0, 2, 'category_id=48', 'test16'),
(909, 0, 2, 'category_id=49', 'test17'),
(911, 0, 2, 'category_id=50', 'test18'),
(913, 0, 2, 'category_id=51', 'test19'),
(915, 0, 2, 'category_id=53', 'test21'),
(917, 0, 2, 'category_id=54', 'test22'),
(919, 0, 2, 'category_id=55', 'test23'),
(921, 0, 2, 'category_id=56', 'test24'),
(923, 0, 2, 'category_id=38', 'test4'),
(925, 0, 2, 'category_id=37', 'test5'),
(927, 0, 2, 'category_id=39', 'test6'),
(929, 0, 2, 'category_id=40', 'test7'),
(931, 0, 2, 'category_id=41', 'test8'),
(933, 0, 2, 'manufacturer_id=8', 'apple'),
(935, 0, 2, 'manufacturer_id=9', 'canon'),
(937, 0, 2, 'manufacturer_id=7', 'hewlett-packard'),
(939, 0, 2, 'manufacturer_id=5', 'htc'),
(941, 0, 2, 'manufacturer_id=6', 'palm'),
(943, 0, 2, 'manufacturer_id=10', 'sony'),
(1023, 0, 2, 'manufacturer_id=11', 'dell'),
(1025, 0, 2, 'manufacturer_id=12', 'samsung'),
(1027, 0, 2, 'manufacturer_id=13', 'nintendo'),
(1029, 0, 2, 'manufacturer_id=14', 'nikon'),
(1031, 0, 2, 'manufacturer_id=15', 'codecart-pro'),
(945, 0, 2, 'product_id=46', 'sony-vaio'),
(947, 0, 2, 'product_id=42', 'apple_cinema_30'),
(949, 0, 2, 'product_id=30', 'canon-eos-5d'),
(951, 0, 2, 'product_id=47', 'hp-lp3065'),
(953, 0, 2, 'product_id=28', 'htc-touch-hd'),
(955, 0, 2, 'product_id=41', 'imac'),
(959, 0, 2, 'product_id=48', 'ipod-classic'),
(961, 0, 2, 'product_id=36', 'ipod-nano'),
(963, 0, 2, 'product_id=34', 'ipod-shuffle'),
(965, 0, 2, 'product_id=32', 'ipod-touch'),
(967, 0, 2, 'product_id=43', 'macbook'),
(969, 0, 2, 'product_id=44', 'macbook-air'),
(971, 0, 2, 'product_id=45', 'macbook-pro'),
(973, 0, 2, 'product_id=31', 'nikon-d300'),
(975, 0, 2, 'product_id=29', 'palm-treo-pro'),
(977, 0, 2, 'product_id=35', 'product-8'),
(979, 0, 2, 'product_id=49', 'samsung-galaxy-tab-10-1'),
(981, 0, 2, 'product_id=33', 'samsung-syncmaster-941bw'),
(984, 0, 2, 'account/account', 'my-account'),
(985, 0, 2, 'checkout/cart', 'cart'),
(986, 0, 2, 'checkout/checkout', 'checkout'),
(987, 0, 2, 'account/login', 'login'),
(988, 0, 2, 'account/logout', 'logout'),
(989, 0, 2, 'account/order', 'order-history'),
(990, 0, 2, 'account/newsletter', 'newsletter'),
(991, 0, 2, 'product/special', 'specials'),
(992, 0, 2, 'affiliate/account', 'affiliates'),
(993, 0, 2, 'checkout/voucher', 'gift-vouchers'),
(994, 0, 2, 'product/manufacturer', 'brands'),
(995, 0, 2, 'information/contact', 'contact-us'),
(996, 0, 2, 'account/return/insert', 'request-return'),
(997, 0, 2, 'information/sitemap', 'sitemap'),
(998, 0, 2, 'account/forgotten', 'forgot-password'),
(999, 0, 2, 'account/download', 'downloads'),
(1001, 0, 2, 'account/return', 'returns'),
(1002, 0, 2, 'account/transaction', 'transactions'),
(1003, 0, 2, 'account/register', 'create-account'),
(1004, 0, 2, 'product/compare', 'compare-products'),
(1005, 0, 2, 'product/search', 'search'),
(1006, 0, 2, 'account/edit', 'edit-account'),
(1007, 0, 2, 'account/password', 'change-password'),
(1008, 0, 2, 'account/address', 'address-book'),
(1009, 0, 2, 'account/reward', 'reward-points'),
(1010, 0, 2, 'affiliate/edit', 'edit-affiliate-account'),
(1011, 0, 2, 'affiliate/password', 'change-affiliate-password'),
(1012, 0, 2, 'affiliate/payment', 'affiliate-payment-options'),
(1013, 0, 2, 'affiliate/tracking', 'affiliate-tracking-code'),
(1014, 0, 2, 'affiliate/transaction', 'affiliate-transactions'),
(1015, 0, 2, 'affiliate/logout', 'affiliate-logout'),
(1016, 0, 2, 'affiliate/forgotten', 'affiliate-forgot-password'),
(1017, 0, 2, 'affiliate/register', 'create-affiliate-account'),
(1018, 0, 2, 'affiliate/login', 'affiliate-login'),
(1019, 0, 2, 'account/voucher', 'vouchers'),
(1020, 0, 2, 'account/wishlist', 'wishlist'),
(1021, 0, 2, 'account/return/add', 'add-return');

-- CodeCart: complete SEO URL coverage for bundled demo content.
INSERT INTO `oc_seo_url` (`store_id`, `language_id`, `query`, `keyword`) VALUES
(0, 1, 'information_id=7', 'polityka-konfidentsiynosti'),
(0, 1, 'information_id=8', 'polityka-vykorystannya-cookies'),
(0, 1, 'information_id=9', 'oplata'),
(0, 1, 'information_id=10', 'harantiya-ta-povernennya'),
(0, 2, 'information_id=7', 'privacy-policy'),
(0, 2, 'information_id=8', 'cookie-policy'),
(0, 2, 'information_id=9', 'payment'),
(0, 2, 'information_id=10', 'warranty-returns'),
(0, 1, 'article_id=120', 'codecart-pro-3-0-6-0-suchasna-osnova-dlya-internet-mahazynu'),
(0, 2, 'article_id=120', 'codecart-pro-3-0-6-0-a-modern-foundation-for-an-online-store'),
(0, 1, 'article_id=123', 'shvydka-vitryna-menshe-zayvoho-bilshe-korysti'),
(0, 2, 'article_id=123', 'a-faster-storefront-less-overhead-more-value'),
(0, 1, 'article_id=124', 'tovar-z-optsiyamy-yak-pokazaty-skladnyy-vybir-prosto'),
(0, 2, 'article_id=124', 'product-options-making-complex-choices-simple'),
(0, 1, 'article_id=125', 'mobilnyy-mahazyn-pereviryayemo-pokupky-zi-smartfona'),
(0, 2, 'article_id=125', 'mobile-commerce-testing-the-shopping-flow-on-a-phone'),
(0, 1, 'article_id=126', 'php-8-1-8-5-navishcho-mahazynu-suchasna-sumisnist'),
(0, 2, 'article_id=126', 'php-8-1-8-5-why-modern-compatibility-matters'),
(0, 1, 'article_id=127', 'seo-z-korobky-chysti-url-canonical-i-zrozumila-struktura'),
(0, 2, 'article_id=127', 'seo-foundation-clean-urls-canonical-and-clear-structure'),
(0, 1, 'article_id=128', 'webp-ta-avif-menshi-zobrazhennya-bez-zayvoho-navantazhennya'),
(0, 2, 'article_id=128', 'webp-and-avif-smaller-images-with-less-overhead'),
(0, 1, 'article_id=129', 'nadiyne-oformlennya-zamovlennya-podviynyy-klik-ne-povynen-stvoryuvaty-dubl'),
(0, 2, 'article_id=129', 'reliable-checkout-a-double-click-should-not-create-a-duplicate'),
(0, 1, 'article_id=130', 'sumisnist-iz-opencart-3-x-modernizatsiya-bez-rizkoho-rozryvu'),
(0, 2, 'article_id=130', 'opencart-3-x-compatibility-modernization-without-a-hard-break'),
(0, 1, 'article_id=131', 'pered-zapuskom-mahazynu-korotkyy-kontrolnyy-spysok'),
(0, 2, 'article_id=131', 'before-store-launch-a-short-checklist'),
(0, 1, 'blog_category_id=69', 'novyny'),
(0, 2, 'blog_category_id=69', 'news'),
(0, 1, 'blog_category_id=70', 'ohlyady'),
(0, 2, 'blog_category_id=70', 'reviews'),
(0, 1, 'blog_category_id=71', 'anonsy'),
(0, 2, 'blog_category_id=71', 'announcements');

-- --------------------------------------------------------
--
-- Table structure for table `oc_user`
--

DROP TABLE IF EXISTS `oc_user`;
CREATE TABLE `oc_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_group_id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(9) NOT NULL,
  `firstname` varchar(32) NOT NULL,
  `lastname` varchar(32) NOT NULL,
  `email` varchar(96) NOT NULL,
  `image` varchar(255) NOT NULL,
  `code` varchar(40) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_user_group`
--

DROP TABLE IF EXISTS `oc_user_group`;
CREATE TABLE `oc_user_group` (
  `user_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `permission` text NOT NULL,
  PRIMARY KEY (`user_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_user_group`
--

INSERT INTO `oc_user_group` (`user_group_id`, `name`, `permission`) VALUES
(1, 'Administrator', '{"access":[],"modify":[]}'),
(10, 'Demonstration', '');

-- --------------------------------------------------------
--
-- Table structure for table `oc_voucher`
--

DROP TABLE IF EXISTS `oc_voucher`;
CREATE TABLE `oc_voucher` (
  `voucher_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `code` varchar(32) NOT NULL,
  `from_name` varchar(64) NOT NULL,
  `from_email` varchar(96) NOT NULL,
  `to_name` varchar(64) NOT NULL,
  `to_email` varchar(96) NOT NULL,
  `voucher_theme_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`voucher_id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_voucher_history`
--

DROP TABLE IF EXISTS `oc_voucher_history`;
CREATE TABLE `oc_voucher_history` (
  `voucher_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`voucher_history_id`),
  KEY `voucher_id` (`voucher_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `oc_voucher_theme`
--

DROP TABLE IF EXISTS `oc_voucher_theme`;
CREATE TABLE `oc_voucher_theme` (
  `voucher_theme_id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`voucher_theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_voucher_theme`
--

INSERT INTO `oc_voucher_theme` (`voucher_theme_id`, `image`) VALUES
(8, 'catalog/demo/canon_eos_5d_2.webp'),
(7, 'catalog/demo/gift-voucher-birthday.webp'),
(6, 'catalog/demo/apple_logo.webp');

-- --------------------------------------------------------
--
-- Table structure for table `oc_voucher_theme_description`
--

DROP TABLE IF EXISTS `oc_voucher_theme_description`;
CREATE TABLE `oc_voucher_theme_description` (
  `voucher_theme_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`voucher_theme_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_voucher_theme_description`
--

INSERT INTO `oc_voucher_theme_description` (`voucher_theme_id`, `language_id`, `name`) VALUES
(6, 1, 'Новий Рік'),
(7, 1, 'День народження'),
(8, 1, 'Подарунок'),
(6, 2, 'Christmas'),
(7, 2, 'Birthday'),
(8, 2, 'General');

-- --------------------------------------------------------
--
-- Table structure for table `oc_weight_class`
--

DROP TABLE IF EXISTS `oc_weight_class`;
CREATE TABLE `oc_weight_class` (
  `weight_class_id` int(11) NOT NULL AUTO_INCREMENT,
  `value` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  PRIMARY KEY (`weight_class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_weight_class`
--

INSERT INTO `oc_weight_class` (`weight_class_id`, `value`) VALUES
(1, '1.00000000'),
(2, '1000.00000000'),
(5, '2.20460000'),
(6, '35.27400000');

-- --------------------------------------------------------
--
-- Table structure for table `oc_weight_class_description`
--

DROP TABLE IF EXISTS `oc_weight_class_description`;
CREATE TABLE `oc_weight_class_description` (
  `weight_class_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `title` varchar(32) NOT NULL,
  `unit` varchar(4) NOT NULL,
  PRIMARY KEY (`weight_class_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_weight_class_description`
--

INSERT INTO `oc_weight_class_description` (`weight_class_id`, `language_id`, `title`, `unit`) VALUES
(1, 1, 'Кілограми', 'кг'),
(1, 2, 'Kilogram', 'kg'),
(2, 1, 'Грами', 'г'),
(2, 2, 'Gram', 'g'),
(5, 1, 'Фунти', 'lb'),
(5, 2, 'Pound', 'lb'),
(6, 1, 'Унції', 'oz'),
(6, 2, 'Ounce', 'oz');

-- --------------------------------------------------------
--
-- Table structure for table `oc_zone`
--

DROP TABLE IF EXISTS `oc_zone`;
CREATE TABLE `oc_zone` (
  `zone_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `code` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_zone`
--

INSERT INTO `oc_zone` (`zone_id`, `country_id`, `name`, `code`, `status`) VALUES
(1, 1, 'Badakhshan', 'BDS', 1),
(2, 1, 'Badghis', 'BDG', 1),
(3, 1, 'Baghlan', 'BGL', 1),
(4, 1, 'Balkh', 'BAL', 1),
(5, 1, 'Bamian', 'BAM', 1),
(6, 1, 'Farah', 'FRA', 1),
(7, 1, 'Faryab', 'FYB', 1),
(8, 1, 'Ghazni', 'GHA', 1),
(9, 1, 'Ghowr', 'GHO', 1),
(10, 1, 'Helmand', 'HEL', 1),
(11, 1, 'Herat', 'HER', 1),
(12, 1, 'Jowzjan', 'JOW', 1),
(13, 1, 'Kabul', 'KAB', 1),
(14, 1, 'Kandahar', 'KAN', 1),
(15, 1, 'Kapisa', 'KAP', 1),
(16, 1, 'Khost', 'KHO', 1),
(17, 1, 'Konar', 'KNR', 1),
(18, 1, 'Kondoz', 'KDZ', 1),
(19, 1, 'Laghman', 'LAG', 1),
(20, 1, 'Lowgar', 'LOW', 1),
(21, 1, 'Nangrahar', 'NAN', 1),
(22, 1, 'Nimruz', 'NIM', 1),
(23, 1, 'Nurestan', 'NUR', 1),
(24, 1, 'Oruzgan', 'ORU', 1),
(25, 1, 'Paktia', 'PIA', 1),
(26, 1, 'Paktika', 'PKA', 1),
(27, 1, 'Parwan', 'PAR', 1),
(28, 1, 'Samangan', 'SAM', 1),
(29, 1, 'Sar-e Pol', 'SAR', 1),
(30, 1, 'Takhar', 'TAK', 1),
(31, 1, 'Wardak', 'WAR', 1),
(32, 1, 'Zabol', 'ZAB', 1),
(33, 2, 'Berat', 'BR', 1),
(34, 2, 'Bulqize', 'BU', 1),
(35, 2, 'Delvine', 'DL', 1),
(36, 2, 'Devoll', 'DV', 1),
(37, 2, 'Diber', 'DI', 1),
(38, 2, 'Durres', 'DR', 1),
(39, 2, 'Elbasan', 'EL', 1),
(40, 2, 'Kolonje', 'ER', 1),
(41, 2, 'Fier', 'FR', 1),
(42, 2, 'Gjirokaster', 'GJ', 1),
(43, 2, 'Gramsh', 'GR', 1),
(44, 2, 'Has', 'HA', 1),
(45, 2, 'Kavaje', 'KA', 1),
(46, 2, 'Kurbin', 'KB', 1),
(47, 2, 'Kucove', 'KC', 1),
(48, 2, 'Korce', 'KO', 1),
(49, 2, 'Kruje', 'KR', 1),
(50, 2, 'Kukes', 'KU', 1),
(51, 2, 'Librazhd', 'LB', 1),
(52, 2, 'Lezhe', 'LE', 1),
(53, 2, 'Lushnje', 'LU', 1),
(54, 2, 'Malesi e Madhe', 'MM', 1),
(55, 2, 'Mallakaster', 'MK', 1),
(56, 2, 'Mat', 'MT', 1),
(57, 2, 'Mirdite', 'MR', 1),
(58, 2, 'Peqin', 'PQ', 1),
(59, 2, 'Permet', 'PR', 1),
(60, 2, 'Pogradec', 'PG', 1),
(61, 2, 'Puke', 'PU', 1),
(62, 2, 'Shkoder', 'SH', 1),
(63, 2, 'Skrapar', 'SK', 1),
(64, 2, 'Sarande', 'SR', 1),
(65, 2, 'Tepelene', 'TE', 1),
(66, 2, 'Tropoje', 'TP', 1),
(67, 2, 'Tirane', 'TR', 1),
(68, 2, 'Vlore', 'VL', 1),
(69, 3, 'Adrar', 'ADR', 1),
(70, 3, 'Ain Defla', 'ADE', 1),
(71, 3, 'Ain Temouchent', 'ATE', 1),
(72, 3, 'Alger', 'ALG', 1),
(73, 3, 'Annaba', 'ANN', 1),
(74, 3, 'Batna', 'BAT', 1),
(75, 3, 'Bechar', 'BEC', 1),
(76, 3, 'Bejaia', 'BEJ', 1),
(77, 3, 'Biskra', 'BIS', 1),
(78, 3, 'Blida', 'BLI', 1),
(79, 3, 'Bordj Bou Arreridj', 'BBA', 1),
(80, 3, 'Bouira', 'BOA', 1),
(81, 3, 'Boumerdes', 'BMD', 1),
(82, 3, 'Chlef', 'CHL', 1),
(83, 3, 'Constantine', 'CON', 1),
(84, 3, 'Djelfa', 'DJE', 1),
(85, 3, 'El Bayadh', 'EBA', 1),
(86, 3, 'El Oued', 'EOU', 1),
(87, 3, 'El Tarf', 'ETA', 1),
(88, 3, 'Ghardaia', 'GHA', 1),
(89, 3, 'Guelma', 'GUE', 1),
(90, 3, 'Illizi', 'ILL', 1),
(91, 3, 'Jijel', 'JIJ', 1),
(92, 3, 'Khenchela', 'KHE', 1),
(93, 3, 'Laghouat', 'LAG', 1),
(94, 3, 'Muaskar', 'MUA', 1),
(95, 3, 'Medea', 'MED', 1),
(96, 3, 'Mila', 'MIL', 1),
(97, 3, 'Mostaganem', 'MOS', 1),
(98, 3, 'M''Sila', 'MSI', 1),
(99, 3, 'Naama', 'NAA', 1),
(100, 3, 'Oran', 'ORA', 1),
(101, 3, 'Ouargla', 'OUA', 1),
(102, 3, 'Oum el-Bouaghi', 'OEB', 1),
(103, 3, 'Relizane', 'REL', 1),
(104, 3, 'Saida', 'SAI', 1),
(105, 3, 'Setif', 'SET', 1),
(106, 3, 'Sidi Bel Abbes', 'SBA', 1),
(107, 3, 'Skikda', 'SKI', 1),
(108, 3, 'Souk Ahras', 'SAH', 1),
(109, 3, 'Tamanghasset', 'TAM', 1),
(110, 3, 'Tebessa', 'TEB', 1),
(111, 3, 'Tiaret', 'TIA', 1),
(112, 3, 'Tindouf', 'TIN', 1),
(113, 3, 'Tipaza', 'TIP', 1),
(114, 3, 'Tissemsilt', 'TIS', 1),
(115, 3, 'Tizi Ouzou', 'TOU', 1),
(116, 3, 'Tlemcen', 'TLE', 1),
(117, 4, 'Eastern', 'E', 1),
(118, 4, 'Manu''a', 'M', 1),
(119, 4, 'Rose Island', 'R', 1),
(120, 4, 'Swains Island', 'S', 1),
(121, 4, 'Western', 'W', 1),
(122, 5, 'Andorra la Vella', 'ALV', 1),
(123, 5, 'Canillo', 'CAN', 1),
(124, 5, 'Encamp', 'ENC', 1),
(125, 5, 'Escaldes-Engordany', 'ESE', 1),
(126, 5, 'La Massana', 'LMA', 1),
(127, 5, 'Ordino', 'ORD', 1),
(128, 5, 'Sant Julia de Loria', 'SJL', 1),
(129, 6, 'Bengo', 'BGO', 1),
(130, 6, 'Benguela', 'BGU', 1),
(131, 6, 'Bie', 'BIE', 1),
(132, 6, 'Cabinda', 'CAB', 1),
(133, 6, 'Cuando-Cubango', 'CCU', 1),
(134, 6, 'Cuanza Norte', 'CNO', 1),
(135, 6, 'Cuanza Sul', 'CUS', 1),
(136, 6, 'Cunene', 'CNN', 1),
(137, 6, 'Huambo', 'HUA', 1),
(138, 6, 'Huila', 'HUI', 1),
(139, 6, 'Luanda', 'LUA', 1),
(140, 6, 'Lunda Norte', 'LNO', 1),
(141, 6, 'Lunda Sul', 'LSU', 1),
(142, 6, 'Malange', 'MAL', 1),
(143, 6, 'Moxico', 'MOX', 1),
(144, 6, 'Namibe', 'NAM', 1),
(145, 6, 'Uige', 'UIG', 1),
(146, 6, 'Zaire', 'ZAI', 1),
(147, 9, 'Saint George', 'ASG', 1),
(148, 9, 'Saint John', 'ASJ', 1),
(149, 9, 'Saint Mary', 'ASM', 1),
(150, 9, 'Saint Paul', 'ASL', 1),
(151, 9, 'Saint Peter', 'ASR', 1),
(152, 9, 'Saint Philip', 'ASH', 1),
(153, 9, 'Barbuda', 'BAR', 1),
(154, 9, 'Redonda', 'RED', 1),
(155, 10, 'Antartida e Islas del Atlantico', 'AN', 1),
(156, 10, 'Buenos Aires', 'BA', 1),
(157, 10, 'Catamarca', 'CA', 1),
(158, 10, 'Chaco', 'CH', 1),
(159, 10, 'Chubut', 'CU', 1),
(160, 10, 'Cordoba', 'CO', 1),
(161, 10, 'Corrientes', 'CR', 1),
(162, 10, 'Distrito Federal', 'DF', 1),
(163, 10, 'Entre Rios', 'ER', 1),
(164, 10, 'Formosa', 'FO', 1),
(165, 10, 'Jujuy', 'JU', 1),
(166, 10, 'La Pampa', 'LP', 1),
(167, 10, 'La Rioja', 'LR', 1),
(168, 10, 'Mendoza', 'ME', 1),
(169, 10, 'Misiones', 'MI', 1),
(170, 10, 'Neuquen', 'NE', 1),
(171, 10, 'Rio Negro', 'RN', 1),
(172, 10, 'Salta', 'SA', 1),
(173, 10, 'San Juan', 'SJ', 1),
(174, 10, 'San Luis', 'SL', 1),
(175, 10, 'Santa Cruz', 'SC', 1),
(176, 10, 'Santa Fe', 'SF', 1),
(177, 10, 'Santiago del Estero', 'SD', 1),
(178, 10, 'Tierra del Fuego', 'TF', 1),
(179, 10, 'Tucuman', 'TU', 1),
(180, 11, 'Aragatsotn', 'AGT', 1),
(181, 11, 'Ararat', 'ARR', 1),
(182, 11, 'Armavir', 'ARM', 1),
(183, 11, 'Geghark''unik''', 'GEG', 1),
(184, 11, 'Kotayk''', 'KOT', 1),
(185, 11, 'Lorri', 'LOR', 1),
(186, 11, 'Shirak', 'SHI', 1),
(187, 11, 'Syunik''', 'SYU', 1),
(188, 11, 'Tavush', 'TAV', 1),
(189, 11, 'Vayots'' Dzor', 'VAY', 1),
(190, 11, 'Yerevan', 'YER', 1),
(191, 13, 'Australian Capital Territory', 'ACT', 1),
(192, 13, 'New South Wales', 'NSW', 1),
(193, 13, 'Northern Territory', 'NT', 1),
(194, 13, 'Queensland', 'QLD', 1),
(195, 13, 'South Australia', 'SA', 1),
(196, 13, 'Tasmania', 'TAS', 1),
(197, 13, 'Victoria', 'VIC', 1),
(198, 13, 'Western Australia', 'WA', 1),
(199, 14, 'Burgenland', 'BUR', 1),
(200, 14, 'Kärnten', 'KAR', 1),
(201, 14, 'Niederösterreich', 'NOS', 1),
(202, 14, 'Oberösterreich', 'OOS', 1),
(203, 14, 'Salzburg', 'SAL', 1),
(204, 14, 'Steiermark', 'STE', 1),
(205, 14, 'Tirol', 'TIR', 1),
(206, 14, 'Vorarlberg', 'VOR', 1),
(207, 14, 'Wien', 'WIE', 1),
(208, 15, 'Ali Bayramli', 'AB', 1),
(209, 15, 'Abseron', 'ABS', 1),
(210, 15, 'AgcabAdi', 'AGC', 1),
(211, 15, 'Agdam', 'AGM', 1),
(212, 15, 'Agdas', 'AGS', 1),
(213, 15, 'Agstafa', 'AGA', 1),
(214, 15, 'Agsu', 'AGU', 1),
(215, 15, 'Astara', 'AST', 1),
(216, 15, 'Baki', 'BA', 1),
(217, 15, 'BabAk', 'BAB', 1),
(218, 15, 'BalakAn', 'BAL', 1),
(219, 15, 'BArdA', 'BAR', 1),
(220, 15, 'Beylaqan', 'BEY', 1),
(221, 15, 'Bilasuvar', 'BIL', 1),
(222, 15, 'Cabrayil', 'CAB', 1),
(223, 15, 'Calilabab', 'CAL', 1),
(224, 15, 'Culfa', 'CUL', 1),
(225, 15, 'Daskasan', 'DAS', 1),
(226, 15, 'Davaci', 'DAV', 1),
(227, 15, 'Fuzuli', 'FUZ', 1),
(228, 15, 'Ganca', 'GA', 1),
(229, 15, 'Gadabay', 'GAD', 1),
(230, 15, 'Goranboy', 'GOR', 1),
(231, 15, 'Goycay', 'GOY', 1),
(232, 15, 'Haciqabul', 'HAC', 1),
(233, 15, 'Imisli', 'IMI', 1),
(234, 15, 'Ismayilli', 'ISM', 1),
(235, 15, 'Kalbacar', 'KAL', 1),
(236, 15, 'Kurdamir', 'KUR', 1),
(237, 15, 'Lankaran', 'LA', 1),
(238, 15, 'Lacin', 'LAC', 1),
(239, 15, 'Lankaran', 'LAN', 1),
(240, 15, 'Lerik', 'LER', 1),
(241, 15, 'Masalli', 'MAS', 1),
(242, 15, 'Mingacevir', 'MI', 1),
(243, 15, 'Naftalan', 'NA', 1),
(244, 15, 'Neftcala', 'NEF', 1),
(245, 15, 'Oguz', 'OGU', 1),
(246, 15, 'Ordubad', 'ORD', 1),
(247, 15, 'Qabala', 'QAB', 1),
(248, 15, 'Qax', 'QAX', 1),
(249, 15, 'Qazax', 'QAZ', 1),
(250, 15, 'Qobustan', 'QOB', 1),
(251, 15, 'Quba', 'QBA', 1),
(252, 15, 'Qubadli', 'QBI', 1),
(253, 15, 'Qusar', 'QUS', 1),
(254, 15, 'Saki', 'SA', 1),
(255, 15, 'Saatli', 'SAT', 1),
(256, 15, 'Sabirabad', 'SAB', 1),
(257, 15, 'Sadarak', 'SAD', 1),
(258, 15, 'Sahbuz', 'SAH', 1),
(259, 15, 'Saki', 'SAK', 1),
(260, 15, 'Salyan', 'SAL', 1),
(261, 15, 'Sumqayit', 'SM', 1),
(262, 15, 'Samaxi', 'SMI', 1),
(263, 15, 'Samkir', 'SKR', 1),
(264, 15, 'Samux', 'SMX', 1),
(265, 15, 'Sarur', 'SAR', 1),
(266, 15, 'Siyazan', 'SIY', 1),
(267, 15, 'Susa', 'SS', 1),
(268, 15, 'Susa', 'SUS', 1),
(269, 15, 'Tartar', 'TAR', 1),
(270, 15, 'Tovuz', 'TOV', 1),
(271, 15, 'Ucar', 'UCA', 1),
(272, 15, 'Xankandi', 'XA', 1),
(273, 15, 'Xacmaz', 'XAC', 1),
(274, 15, 'Xanlar', 'XAN', 1),
(275, 15, 'Xizi', 'XIZ', 1),
(276, 15, 'Xocali', 'XCI', 1),
(277, 15, 'Xocavand', 'XVD', 1),
(278, 15, 'Yardimli', 'YAR', 1),
(279, 15, 'Yevlax', 'YEV', 1),
(280, 15, 'Zangilan', 'ZAN', 1),
(281, 15, 'Zaqatala', 'ZAQ', 1),
(282, 15, 'Zardab', 'ZAR', 1),
(283, 15, 'Naxcivan', 'NX', 1),
(284, 16, 'Acklins', 'ACK', 1),
(285, 16, 'Berry Islands', 'BER', 1),
(286, 16, 'Bimini', 'BIM', 1),
(287, 16, 'Black Point', 'BLK', 1),
(288, 16, 'Cat Island', 'CAT', 1),
(289, 16, 'Central Abaco', 'CAB', 1),
(290, 16, 'Central Andros', 'CAN', 1),
(291, 16, 'Central Eleuthera', 'CEL', 1),
(292, 16, 'City of Freeport', 'FRE', 1),
(293, 16, 'Crooked Island', 'CRO', 1),
(294, 16, 'East Grand Bahama', 'EGB', 1),
(295, 16, 'Exuma', 'EXU', 1),
(296, 16, 'Grand Cay', 'GRD', 1),
(297, 16, 'Harbour Island', 'HAR', 1),
(298, 16, 'Hope Town', 'HOP', 1),
(299, 16, 'Inagua', 'INA', 1),
(300, 16, 'Long Island', 'LNG', 1),
(301, 16, 'Mangrove Cay', 'MAN', 1),
(302, 16, 'Mayaguana', 'MAY', 1),
(303, 16, 'Moore''s Island', 'MOO', 1),
(304, 16, 'North Abaco', 'NAB', 1),
(305, 16, 'North Andros', 'NAN', 1),
(306, 16, 'North Eleuthera', 'NEL', 1),
(307, 16, 'Ragged Island', 'RAG', 1),
(308, 16, 'Rum Cay', 'RUM', 1),
(309, 16, 'San Salvador', 'SAL', 1),
(310, 16, 'South Abaco', 'SAB', 1),
(311, 16, 'South Andros', 'SAN', 1),
(312, 16, 'South Eleuthera', 'SEL', 1),
(313, 16, 'Spanish Wells', 'SWE', 1),
(314, 16, 'West Grand Bahama', 'WGB', 1),
(315, 17, 'Capital', 'CAP', 1),
(316, 17, 'Central', 'CEN', 1),
(317, 17, 'Muharraq', 'MUH', 1),
(318, 17, 'Northern', 'NOR', 1),
(319, 17, 'Southern', 'SOU', 1),
(320, 18, 'Barisal', 'BAR', 1),
(321, 18, 'Chittagong', 'CHI', 1),
(322, 18, 'Dhaka', 'DHA', 1),
(323, 18, 'Khulna', 'KHU', 1),
(324, 18, 'Rajshahi', 'RAJ', 1),
(325, 18, 'Sylhet', 'SYL', 1),
(326, 19, 'Christ Church', 'CC', 1),
(327, 19, 'Saint Andrew', 'AND', 1),
(328, 19, 'Saint George', 'GEO', 1),
(329, 19, 'Saint James', 'JAM', 1),
(330, 19, 'Saint John', 'JOH', 1),
(331, 19, 'Saint Joseph', 'JOS', 1),
(332, 19, 'Saint Lucy', 'LUC', 1),
(333, 19, 'Saint Michael', 'MIC', 1),
(334, 19, 'Saint Peter', 'PET', 1),
(335, 19, 'Saint Philip', 'PHI', 1),
(336, 19, 'Saint Thomas', 'THO', 1),
(337, 20, 'Brestskaya (Brest)', 'BR', 1),
(338, 20, 'Homyel''skaya (Homyel'')', 'HO', 1),
(339, 20, 'Horad Minsk', 'HM', 1),
(340, 20, 'Hrodzyenskaya (Hrodna)', 'HR', 1),
(341, 20, 'Mahilyowskaya (Mahilyow)', 'MA', 1),
(342, 20, 'Minskaya', 'MI', 1),
(343, 20, 'Vitsyebskaya (Vitsyebsk)', 'VI', 1),
(344, 21, 'Antwerpen', 'VAN', 1),
(345, 21, 'Brabant Wallon', 'WBR', 1),
(346, 21, 'Hainaut', 'WHT', 1),
(347, 21, 'Liège', 'WLG', 1),
(348, 21, 'Limburg', 'VLI', 1),
(349, 21, 'Luxembourg', 'WLX', 1),
(350, 21, 'Namur', 'WNA', 1),
(351, 21, 'Oost-Vlaanderen', 'VOV', 1),
(352, 21, 'Vlaams Brabant', 'VBR', 1),
(353, 21, 'West-Vlaanderen', 'VWV', 1),
(354, 22, 'Belize', 'BZ', 1),
(355, 22, 'Cayo', 'CY', 1),
(356, 22, 'Corozal', 'CR', 1),
(357, 22, 'Orange Walk', 'OW', 1),
(358, 22, 'Stann Creek', 'SC', 1),
(359, 22, 'Toledo', 'TO', 1),
(360, 23, 'Alibori', 'AL', 1),
(361, 23, 'Atakora', 'AK', 1),
(362, 23, 'Atlantique', 'AQ', 1),
(363, 23, 'Borgou', 'BO', 1),
(364, 23, 'Collines', 'CO', 1),
(365, 23, 'Donga', 'DO', 1),
(366, 23, 'Kouffo', 'KO', 1),
(367, 23, 'Littoral', 'LI', 1),
(368, 23, 'Mono', 'MO', 1),
(369, 23, 'Oueme', 'OU', 1),
(370, 23, 'Plateau', 'PL', 1),
(371, 23, 'Zou', 'ZO', 1),
(372, 24, 'Devonshire', 'DS', 1),
(373, 24, 'Hamilton City', 'HC', 1),
(374, 24, 'Hamilton', 'HA', 1),
(375, 24, 'Paget', 'PG', 1),
(376, 24, 'Pembroke', 'PB', 1),
(377, 24, 'Saint George City', 'GC', 1),
(378, 24, 'Saint George''s', 'SG', 1),
(379, 24, 'Sandys', 'SA', 1),
(380, 24, 'Smith''s', 'SM', 1),
(381, 24, 'Southampton', 'SH', 1),
(382, 24, 'Warwick', 'WA', 1),
(383, 25, 'Bumthang', 'BUM', 1),
(384, 25, 'Chukha', 'CHU', 1),
(385, 25, 'Dagana', 'DAG', 1),
(386, 25, 'Gasa', 'GAS', 1),
(387, 25, 'Haa', 'HAA', 1),
(388, 25, 'Lhuntse', 'LHU', 1),
(389, 25, 'Mongar', 'MON', 1),
(390, 25, 'Paro', 'PAR', 1),
(391, 25, 'Pemagatshel', 'PEM', 1),
(392, 25, 'Punakha', 'PUN', 1),
(393, 25, 'Samdrup Jongkhar', 'SJO', 1),
(394, 25, 'Samtse', 'SAT', 1),
(395, 25, 'Sarpang', 'SAR', 1),
(396, 25, 'Thimphu', 'THI', 1),
(397, 25, 'Trashigang', 'TRG', 1),
(398, 25, 'Trashiyangste', 'TRY', 1),
(399, 25, 'Trongsa', 'TRO', 1),
(400, 25, 'Tsirang', 'TSI', 1),
(401, 25, 'Wangdue Phodrang', 'WPH', 1),
(402, 25, 'Zhemgang', 'ZHE', 1),
(403, 26, 'Beni', 'BEN', 1),
(404, 26, 'Chuquisaca', 'CHU', 1),
(405, 26, 'Cochabamba', 'COC', 1),
(406, 26, 'La Paz', 'LPZ', 1),
(407, 26, 'Oruro', 'ORU', 1),
(408, 26, 'Pando', 'PAN', 1),
(409, 26, 'Potosi', 'POT', 1),
(410, 26, 'Santa Cruz', 'SCZ', 1),
(411, 26, 'Tarija', 'TAR', 1),
(412, 27, 'Brcko district', 'BRO', 1),
(413, 27, 'Unsko-Sanski Kanton', 'FUS', 1),
(414, 27, 'Posavski Kanton', 'FPO', 1),
(415, 27, 'Tuzlanski Kanton', 'FTU', 1),
(416, 27, 'Zenicko-Dobojski Kanton', 'FZE', 1),
(417, 27, 'Bosanskopodrinjski Kanton', 'FBP', 1),
(418, 27, 'Srednjebosanski Kanton', 'FSB', 1),
(419, 27, 'Hercegovacko-neretvanski Kanton', 'FHN', 1),
(420, 27, 'Zapadnohercegovacka Zupanija', 'FZH', 1),
(421, 27, 'Kanton Sarajevo', 'FSA', 1),
(422, 27, 'Zapadnobosanska', 'FZA', 1),
(423, 27, 'Banja Luka', 'SBL', 1),
(424, 27, 'Doboj', 'SDO', 1),
(425, 27, 'Bijeljina', 'SBI', 1),
(426, 27, 'Vlasenica', 'SVL', 1),
(427, 27, 'Sarajevo-Romanija or Sokolac', 'SSR', 1),
(428, 27, 'Foca', 'SFO', 1),
(429, 27, 'Trebinje', 'STR', 1),
(430, 28, 'Central', 'CE', 1),
(431, 28, 'Ghanzi', 'GH', 1),
(432, 28, 'Kgalagadi', 'KD', 1),
(433, 28, 'Kgatleng', 'KT', 1),
(434, 28, 'Kweneng', 'KW', 1),
(435, 28, 'Ngamiland', 'NG', 1),
(436, 28, 'North East', 'NE', 1),
(437, 28, 'North West', 'NW', 1),
(438, 28, 'South East', 'SE', 1),
(439, 28, 'Southern', 'SO', 1),
(440, 30, 'Acre', 'AC', 1),
(441, 30, 'Alagoas', 'AL', 1),
(442, 30, 'Amapá', 'AP', 1),
(443, 30, 'Amazonas', 'AM', 1),
(444, 30, 'Bahia', 'BA', 1),
(445, 30, 'Ceará', 'CE', 1),
(446, 30, 'Distrito Federal', 'DF', 1),
(447, 30, 'Espírito Santo', 'ES', 1),
(448, 30, 'Goiás', 'GO', 1),
(449, 30, 'Maranhão', 'MA', 1),
(450, 30, 'Mato Grosso', 'MT', 1),
(451, 30, 'Mato Grosso do Sul', 'MS', 1),
(452, 30, 'Minas Gerais', 'MG', 1),
(453, 30, 'Pará', 'PA', 1),
(454, 30, 'Paraíba', 'PB', 1),
(455, 30, 'Paraná', 'PR', 1),
(456, 30, 'Pernambuco', 'PE', 1),
(457, 30, 'Piauí', 'PI', 1),
(458, 30, 'Rio de Janeiro', 'RJ', 1),
(459, 30, 'Rio Grande do Norte', 'RN', 1),
(460, 30, 'Rio Grande do Sul', 'RS', 1),
(461, 30, 'Rondônia', 'RO', 1),
(462, 30, 'Roraima', 'RR', 1),
(463, 30, 'Santa Catarina', 'SC', 1),
(464, 30, 'São Paulo', 'SP', 1),
(465, 30, 'Sergipe', 'SE', 1),
(466, 30, 'Tocantins', 'TO', 1),
(467, 31, 'Peros Banhos', 'PB', 1),
(468, 31, 'Salomon Islands', 'SI', 1),
(469, 31, 'Nelsons Island', 'NI', 1),
(470, 31, 'Three Brothers', 'TB', 1),
(471, 31, 'Eagle Islands', 'EA', 1),
(472, 31, 'Danger Island', 'DI', 1),
(473, 31, 'Egmont Islands', 'EG', 1),
(474, 31, 'Diego Garcia', 'DG', 1),
(475, 32, 'Belait', 'BEL', 1),
(476, 32, 'Brunei and Muara', 'BRM', 1),
(477, 32, 'Temburong', 'TEM', 1),
(478, 32, 'Tutong', 'TUT', 1),
(479, 33, 'Blagoevgrad', '', 1),
(480, 33, 'Burgas', '', 1),
(481, 33, 'Dobrich', '', 1),
(482, 33, 'Gabrovo', '', 1),
(483, 33, 'Haskovo', '', 1),
(484, 33, 'Kardjali', '', 1),
(485, 33, 'Kyustendil', '', 1),
(486, 33, 'Lovech', '', 1),
(487, 33, 'Montana', '', 1),
(488, 33, 'Pazardjik', '', 1),
(489, 33, 'Pernik', '', 1),
(490, 33, 'Pleven', '', 1),
(491, 33, 'Plovdiv', '', 1),
(492, 33, 'Razgrad', '', 1),
(493, 33, 'Shumen', '', 1),
(494, 33, 'Silistra', '', 1),
(495, 33, 'Sliven', '', 1),
(496, 33, 'Smolyan', '', 1),
(497, 33, 'Sofia', '', 1),
(498, 33, 'Sofia - town', '', 1),
(499, 33, 'Stara Zagora', '', 1),
(500, 33, 'Targovishte', '', 1),
(501, 33, 'Varna', '', 1),
(502, 33, 'Veliko Tarnovo', '', 1),
(503, 33, 'Vidin', '', 1),
(504, 33, 'Vratza', '', 1),
(505, 33, 'Yambol', '', 1),
(506, 34, 'Bale', 'BAL', 1),
(507, 34, 'Bam', 'BAM', 1),
(508, 34, 'Banwa', 'BAN', 1),
(509, 34, 'Bazega', 'BAZ', 1),
(510, 34, 'Bougouriba', 'BOR', 1),
(511, 34, 'Boulgou', 'BLG', 1),
(512, 34, 'Boulkiemde', 'BOK', 1),
(513, 34, 'Comoe', 'COM', 1),
(514, 34, 'Ganzourgou', 'GAN', 1),
(515, 34, 'Gnagna', 'GNA', 1),
(516, 34, 'Gourma', 'GOU', 1),
(517, 34, 'Houet', 'HOU', 1),
(518, 34, 'Ioba', 'IOA', 1),
(519, 34, 'Kadiogo', 'KAD', 1),
(520, 34, 'Kenedougou', 'KEN', 1),
(521, 34, 'Komondjari', 'KOD', 1),
(522, 34, 'Kompienga', 'KOP', 1),
(523, 34, 'Kossi', 'KOS', 1),
(524, 34, 'Koulpelogo', 'KOL', 1),
(525, 34, 'Kouritenga', 'KOT', 1),
(526, 34, 'Kourweogo', 'KOW', 1),
(527, 34, 'Leraba', 'LER', 1),
(528, 34, 'Loroum', 'LOR', 1),
(529, 34, 'Mouhoun', 'MOU', 1),
(530, 34, 'Nahouri', 'NAH', 1),
(531, 34, 'Namentenga', 'NAM', 1),
(532, 34, 'Nayala', 'NAY', 1),
(533, 34, 'Noumbiel', 'NOU', 1),
(534, 34, 'Oubritenga', 'OUB', 1),
(535, 34, 'Oudalan', 'OUD', 1),
(536, 34, 'Passore', 'PAS', 1),
(537, 34, 'Poni', 'PON', 1),
(538, 34, 'Sanguie', 'SAG', 1),
(539, 34, 'Sanmatenga', 'SAM', 1),
(540, 34, 'Seno', 'SEN', 1),
(541, 34, 'Sissili', 'SIS', 1),
(542, 34, 'Soum', 'SOM', 1),
(543, 34, 'Sourou', 'SOR', 1),
(544, 34, 'Tapoa', 'TAP', 1),
(545, 34, 'Tuy', 'TUY', 1),
(546, 34, 'Yagha', 'YAG', 1),
(547, 34, 'Yatenga', 'YAT', 1),
(548, 34, 'Ziro', 'ZIR', 1),
(549, 34, 'Zondoma', 'ZOD', 1),
(550, 34, 'Zoundweogo', 'ZOW', 1),
(551, 35, 'Bubanza', 'BB', 1),
(552, 35, 'Bujumbura', 'BJ', 1),
(553, 35, 'Bururi', 'BR', 1),
(554, 35, 'Cankuzo', 'CA', 1),
(555, 35, 'Cibitoke', 'CI', 1),
(556, 35, 'Gitega', 'GI', 1),
(557, 35, 'Karuzi', 'KR', 1),
(558, 35, 'Kayanza', 'KY', 1),
(559, 35, 'Kirundo', 'KI', 1),
(560, 35, 'Makamba', 'MA', 1),
(561, 35, 'Muramvya', 'MU', 1),
(562, 35, 'Muyinga', 'MY', 1),
(563, 35, 'Mwaro', 'MW', 1),
(564, 35, 'Ngozi', 'NG', 1),
(565, 35, 'Rutana', 'RT', 1),
(566, 35, 'Ruyigi', 'RY', 1),
(567, 36, 'Phnom Penh', 'PP', 1),
(568, 36, 'Preah Seihanu (Kompong Som or Sihanoukville)', 'PS', 1),
(569, 36, 'Pailin', 'PA', 1),
(570, 36, 'Keb', 'KB', 1),
(571, 36, 'Banteay Meanchey', 'BM', 1),
(572, 36, 'Battambang', 'BA', 1),
(573, 36, 'Kampong Cham', 'KM', 1),
(574, 36, 'Kampong Chhnang', 'KN', 1),
(575, 36, 'Kampong Speu', 'KU', 1),
(576, 36, 'Kampong Som', 'KO', 1),
(577, 36, 'Kampong Thom', 'KT', 1),
(578, 36, 'Kampot', 'KP', 1),
(579, 36, 'Kandal', 'KL', 1),
(580, 36, 'Kaoh Kong', 'KK', 1),
(581, 36, 'Kratie', 'KR', 1),
(582, 36, 'Mondul Kiri', 'MK', 1),
(583, 36, 'Oddar Meancheay', 'OM', 1),
(584, 36, 'Pursat', 'PU', 1),
(585, 36, 'Preah Vihear', 'PR', 1),
(586, 36, 'Prey Veng', 'PG', 1),
(587, 36, 'Ratanak Kiri', 'RK', 1),
(588, 36, 'Siemreap', 'SI', 1),
(589, 36, 'Stung Treng', 'ST', 1),
(590, 36, 'Svay Rieng', 'SR', 1),
(591, 36, 'Takeo', 'TK', 1),
(592, 37, 'Adamawa (Adamaoua)', 'ADA', 1),
(593, 37, 'Centre', 'CEN', 1),
(594, 37, 'East (Est)', 'EST', 1),
(595, 37, 'Extreme North (Extreme-Nord)', 'EXN', 1),
(596, 37, 'Littoral', 'LIT', 1),
(597, 37, 'North (Nord)', 'NOR', 1),
(598, 37, 'Northwest (Nord-Ouest)', 'NOT', 1),
(599, 37, 'West (Ouest)', 'OUE', 1),
(600, 37, 'South (Sud)', 'SUD', 1),
(601, 37, 'Southwest (Sud-Ouest).', 'SOU', 1),
(602, 38, 'Alberta', 'AB', 1),
(603, 38, 'British Columbia', 'BC', 1),
(604, 38, 'Manitoba', 'MB', 1),
(605, 38, 'New Brunswick', 'NB', 1),
(606, 38, 'Newfoundland and Labrador', 'NL', 1),
(607, 38, 'Northwest Territories', 'NT', 1),
(608, 38, 'Nova Scotia', 'NS', 1),
(609, 38, 'Nunavut', 'NU', 1),
(610, 38, 'Ontario', 'ON', 1),
(611, 38, 'Prince Edward Island', 'PE', 1),
(612, 38, 'Qu&eacute;bec', 'QC', 1),
(613, 38, 'Saskatchewan', 'SK', 1),
(614, 38, 'Yukon Territory', 'YT', 1),
(615, 39, 'Boa Vista', 'BV', 1),
(616, 39, 'Brava', 'BR', 1),
(617, 39, 'Calheta de Sao Miguel', 'CS', 1),
(618, 39, 'Maio', 'MA', 1),
(619, 39, 'Mosteiros', 'MO', 1),
(620, 39, 'Paul', 'PA', 1),
(621, 39, 'Porto Novo', 'PN', 1),
(622, 39, 'Praia', 'PR', 1),
(623, 39, 'Ribeira Grande', 'RG', 1),
(624, 39, 'Sal', 'SL', 1),
(625, 39, 'Santa Catarina', 'CA', 1),
(626, 39, 'Santa Cruz', 'CR', 1),
(627, 39, 'Sao Domingos', 'SD', 1),
(628, 39, 'Sao Filipe', 'SF', 1),
(629, 39, 'Sao Nicolau', 'SN', 1),
(630, 39, 'Sao Vicente', 'SV', 1),
(631, 39, 'Tarrafal', 'TA', 1),
(632, 40, 'Creek', 'CR', 1),
(633, 40, 'Eastern', 'EA', 1),
(634, 40, 'Midland', 'ML', 1),
(635, 40, 'South Town', 'ST', 1),
(636, 40, 'Spot Bay', 'SP', 1),
(637, 40, 'Stake Bay', 'SK', 1),
(638, 40, 'West End', 'WD', 1),
(639, 40, 'Western', 'WN', 1),
(640, 41, 'Bamingui-Bangoran', 'BBA', 1),
(641, 41, 'Basse-Kotto', 'BKO', 1),
(642, 41, 'Haute-Kotto', 'HKO', 1),
(643, 41, 'Haut-Mbomou', 'HMB', 1),
(644, 41, 'Kemo', 'KEM', 1),
(645, 41, 'Lobaye', 'LOB', 1),
(646, 41, 'Mambere-KadeÔ', 'MKD', 1),
(647, 41, 'Mbomou', 'MBO', 1),
(648, 41, 'Nana-Mambere', 'NMM', 1),
(649, 41, 'Ombella-M''Poko', 'OMP', 1),
(650, 41, 'Ouaka', 'OUK', 1),
(651, 41, 'Ouham', 'OUH', 1),
(652, 41, 'Ouham-Pende', 'OPE', 1),
(653, 41, 'Vakaga', 'VAK', 1),
(654, 41, 'Nana-Grebizi', 'NGR', 1),
(655, 41, 'Sangha-Mbaere', 'SMB', 1),
(656, 41, 'Bangui', 'BAN', 1),
(657, 42, 'Batha', 'BA', 1),
(658, 42, 'Biltine', 'BI', 1),
(659, 42, 'Borkou-Ennedi-Tibesti', 'BE', 1),
(660, 42, 'Chari-Baguirmi', 'CB', 1),
(661, 42, 'Guera', 'GU', 1),
(662, 42, 'Kanem', 'KA', 1),
(663, 42, 'Lac', 'LA', 1),
(664, 42, 'Logone Occidental', 'LC', 1),
(665, 42, 'Logone Oriental', 'LR', 1),
(666, 42, 'Mayo-Kebbi', 'MK', 1),
(667, 42, 'Moyen-Chari', 'MC', 1),
(668, 42, 'Ouaddai', 'OU', 1),
(669, 42, 'Salamat', 'SA', 1),
(670, 42, 'Tandjile', 'TA', 1),
(671, 43, 'Aisen del General Carlos Ibanez', 'AI', 1),
(672, 43, 'Antofagasta', 'AN', 1),
(673, 43, 'Araucania', 'AR', 1),
(674, 43, 'Atacama', 'AT', 1),
(675, 43, 'Bio-Bio', 'BI', 1),
(676, 43, 'Coquimbo', 'CO', 1),
(677, 43, 'Libertador General Bernardo O''Higgins', 'LI', 1),
(678, 43, 'Los Lagos', 'LL', 1),
(679, 43, 'Magallanes y de la Antartica Chilena', 'MA', 1),
(680, 43, 'Maule', 'ML', 1),
(681, 43, 'Region Metropolitana', 'RM', 1),
(682, 43, 'Tarapaca', 'TA', 1),
(683, 43, 'Valparaiso', 'VS', 1),
(684, 44, 'Anhui', 'AN', 1),
(685, 44, 'Beijing', 'BE', 1),
(686, 44, 'Chongqing', 'CH', 1),
(687, 44, 'Fujian', 'FU', 1),
(688, 44, 'Gansu', 'GA', 1),
(689, 44, 'Guangdong', 'GU', 1),
(690, 44, 'Guangxi', 'GX', 1),
(691, 44, 'Guizhou', 'GZ', 1),
(692, 44, 'Hainan', 'HA', 1),
(693, 44, 'Hebei', 'HB', 1),
(694, 44, 'Heilongjiang', 'HL', 1),
(695, 44, 'Henan', 'HE', 1),
(696, 44, 'Hong Kong', 'HK', 1),
(697, 44, 'Hubei', 'HU', 1),
(698, 44, 'Hunan', 'HN', 1),
(699, 44, 'Inner Mongolia', 'IM', 1),
(700, 44, 'Jiangsu', 'JI', 1),
(701, 44, 'Jiangxi', 'JX', 1),
(702, 44, 'Jilin', 'JL', 1),
(703, 44, 'Liaoning', 'LI', 1),
(704, 44, 'Macau', 'MA', 1),
(705, 44, 'Ningxia', 'NI', 1),
(706, 44, 'Shaanxi', 'SH', 1),
(707, 44, 'Shandong', 'SA', 1),
(708, 44, 'Shanghai', 'SG', 1),
(709, 44, 'Shanxi', 'SX', 1),
(710, 44, 'Sichuan', 'SI', 1),
(711, 44, 'Tianjin', 'TI', 1),
(712, 44, 'Xinjiang', 'XI', 1),
(713, 44, 'Yunnan', 'YU', 1),
(714, 44, 'Zhejiang', 'ZH', 1),
(715, 46, 'Direction Island', 'D', 1),
(716, 46, 'Home Island', 'H', 1),
(717, 46, 'Horsburgh Island', 'O', 1),
(718, 46, 'South Island', 'S', 1),
(719, 46, 'West Island', 'W', 1),
(720, 47, 'Amazonas', 'AMZ', 1),
(721, 47, 'Antioquia', 'ANT', 1),
(722, 47, 'Arauca', 'ARA', 1),
(723, 47, 'Atlantico', 'ATL', 1),
(724, 47, 'Bogota D.C.', 'BDC', 1),
(725, 47, 'Bolivar', 'BOL', 1),
(726, 47, 'Boyaca', 'BOY', 1),
(727, 47, 'Caldas', 'CAL', 1),
(728, 47, 'Caqueta', 'CAQ', 1),
(729, 47, 'Casanare', 'CAS', 1),
(730, 47, 'Cauca', 'CAU', 1),
(731, 47, 'Cesar', 'CES', 1),
(732, 47, 'Choco', 'CHO', 1),
(733, 47, 'Cordoba', 'COR', 1),
(734, 47, 'Cundinamarca', 'CAM', 1),
(735, 47, 'Guainia', 'GNA', 1),
(736, 47, 'Guajira', 'GJR', 1),
(737, 47, 'Guaviare', 'GVR', 1),
(738, 47, 'Huila', 'HUI', 1),
(739, 47, 'Magdalena', 'MAG', 1),
(740, 47, 'Meta', 'MET', 1),
(741, 47, 'Narino', 'NAR', 1),
(742, 47, 'Norte de Santander', 'NDS', 1),
(743, 47, 'Putumayo', 'PUT', 1),
(744, 47, 'Quindio', 'QUI', 1),
(745, 47, 'Risaralda', 'RIS', 1),
(746, 47, 'San Andres y Providencia', 'SAP', 1),
(747, 47, 'Santander', 'SAN', 1),
(748, 47, 'Sucre', 'SUC', 1),
(749, 47, 'Tolima', 'TOL', 1),
(750, 47, 'Valle del Cauca', 'VDC', 1),
(751, 47, 'Vaupes', 'VAU', 1),
(752, 47, 'Vichada', 'VIC', 1),
(753, 48, 'Grande Comore', 'G', 1),
(754, 48, 'Anjouan', 'A', 1),
(755, 48, 'Moheli', 'M', 1),
(756, 49, 'Bouenza', 'BO', 1),
(757, 49, 'Brazzaville', 'BR', 1),
(758, 49, 'Cuvette', 'CU', 1),
(759, 49, 'Cuvette-Ouest', 'CO', 1),
(760, 49, 'Kouilou', 'KO', 1),
(761, 49, 'Lekoumou', 'LE', 1),
(762, 49, 'Likouala', 'LI', 1),
(763, 49, 'Niari', 'NI', 1),
(764, 49, 'Plateaux', 'PL', 1),
(765, 49, 'Pool', 'PO', 1),
(766, 49, 'Sangha', 'SA', 1),
(767, 50, 'Pukapuka', 'PU', 1),
(768, 50, 'Rakahanga', 'RK', 1),
(769, 50, 'Manihiki', 'MK', 1),
(770, 50, 'Penrhyn', 'PE', 1),
(771, 50, 'Nassau Island', 'NI', 1),
(772, 50, 'Surwarrow', 'SU', 1),
(773, 50, 'Palmerston', 'PA', 1),
(774, 50, 'Aitutaki', 'AI', 1),
(775, 50, 'Manuae', 'MA', 1),
(776, 50, 'Takutea', 'TA', 1),
(777, 50, 'Mitiaro', 'MT', 1),
(778, 50, 'Atiu', 'AT', 1),
(779, 50, 'Mauke', 'MU', 1),
(780, 50, 'Rarotonga', 'RR', 1),
(781, 50, 'Mangaia', 'MG', 1),
(782, 51, 'Alajuela', 'AL', 1),
(783, 51, 'Cartago', 'CA', 1),
(784, 51, 'Guanacaste', 'GU', 1),
(785, 51, 'Heredia', 'HE', 1),
(786, 51, 'Limon', 'LI', 1),
(787, 51, 'Puntarenas', 'PU', 1),
(788, 51, 'San Jose', 'SJ', 1),
(789, 52, 'Abengourou', 'ABE', 1),
(790, 52, 'Abidjan', 'ABI', 1),
(791, 52, 'Aboisso', 'ABO', 1),
(792, 52, 'Adiake', 'ADI', 1),
(793, 52, 'Adzope', 'ADZ', 1),
(794, 52, 'Agboville', 'AGB', 1),
(795, 52, 'Agnibilekrou', 'AGN', 1),
(796, 52, 'Alepe', 'ALE', 1),
(797, 52, 'Bocanda', 'BOC', 1),
(798, 52, 'Bangolo', 'BAN', 1),
(799, 52, 'Beoumi', 'BEO', 1),
(800, 52, 'Biankouma', 'BIA', 1),
(801, 52, 'Bondoukou', 'BDK', 1),
(802, 52, 'Bongouanou', 'BGN', 1),
(803, 52, 'Bouafle', 'BFL', 1),
(804, 52, 'Bouake', 'BKE', 1),
(805, 52, 'Bouna', 'BNA', 1),
(806, 52, 'Boundiali', 'BDL', 1),
(807, 52, 'Dabakala', 'DKL', 1),
(808, 52, 'Dabou', 'DBU', 1),
(809, 52, 'Daloa', 'DAL', 1),
(810, 52, 'Danane', 'DAN', 1),
(811, 52, 'Daoukro', 'DAO', 1),
(812, 52, 'Dimbokro', 'DIM', 1),
(813, 52, 'Divo', 'DIV', 1),
(814, 52, 'Duekoue', 'DUE', 1),
(815, 52, 'Ferkessedougou', 'FER', 1),
(816, 52, 'Gagnoa', 'GAG', 1),
(817, 52, 'Grand-Bassam', 'GBA', 1),
(818, 52, 'Grand-Lahou', 'GLA', 1),
(819, 52, 'Guiglo', 'GUI', 1),
(820, 52, 'Issia', 'ISS', 1),
(821, 52, 'Jacqueville', 'JAC', 1),
(822, 52, 'Katiola', 'KAT', 1),
(823, 52, 'Korhogo', 'KOR', 1),
(824, 52, 'Lakota', 'LAK', 1),
(825, 52, 'Man', 'MAN', 1),
(826, 52, 'Mankono', 'MKN', 1),
(827, 52, 'Mbahiakro', 'MBA', 1),
(828, 52, 'Odienne', 'ODI', 1),
(829, 52, 'Oume', 'OUM', 1),
(830, 52, 'Sakassou', 'SAK', 1),
(831, 52, 'San-Pedro', 'SPE', 1),
(832, 52, 'Sassandra', 'SAS', 1),
(833, 52, 'Seguela', 'SEG', 1),
(834, 52, 'Sinfra', 'SIN', 1),
(835, 52, 'Soubre', 'SOU', 1),
(836, 52, 'Tabou', 'TAB', 1),
(837, 52, 'Tanda', 'TAN', 1),
(838, 52, 'Tiebissou', 'TIE', 1),
(839, 52, 'Tingrela', 'TIN', 1),
(840, 52, 'Tiassale', 'TIA', 1),
(841, 52, 'Touba', 'TBA', 1),
(842, 52, 'Toulepleu', 'TLP', 1),
(843, 52, 'Toumodi', 'TMD', 1),
(844, 52, 'Vavoua', 'VAV', 1),
(845, 52, 'Yamoussoukro', 'YAM', 1),
(846, 52, 'Zuenoula', 'ZUE', 1),
(847, 53, 'Bjelovarsko-bilogorska', 'BB', 1),
(848, 53, 'Grad Zagreb', 'GZ', 1),
(849, 53, 'Dubrovačko-neretvanska', 'DN', 1),
(850, 53, 'Istarska', 'IS', 1),
(851, 53, 'Karlovačka', 'KA', 1),
(852, 53, 'Koprivničko-križevačka', 'KK', 1),
(853, 53, 'Krapinsko-zagorska', 'KZ', 1),
(854, 53, 'Ličko-senjska', 'LS', 1),
(855, 53, 'Međimurska', 'ME', 1),
(856, 53, 'Osječko-baranjska', 'OB', 1),
(857, 53, 'Požeško-slavonska', 'PS', 1),
(858, 53, 'Primorsko-goranska', 'PG', 1),
(859, 53, 'Šibensko-kninska', 'SK', 1),
(860, 53, 'Sisačko-moslavačka', 'SM', 1),
(861, 53, 'Brodsko-posavska', 'BP', 1),
(862, 53, 'Splitsko-dalmatinska', 'SD', 1),
(863, 53, 'Varaždinska', 'VA', 1),
(864, 53, 'Virovitičko-podravska', 'VP', 1),
(865, 53, 'Vukovarsko-srijemska', 'VS', 1),
(866, 53, 'Zadarska', 'ZA', 1),
(867, 53, 'Zagrebačka', 'ZG', 1),
(868, 54, 'Camaguey', 'CA', 1),
(869, 54, 'Ciego de Avila', 'CD', 1),
(870, 54, 'Cienfuegos', 'CI', 1),
(871, 54, 'Ciudad de La Habana', 'CH', 1),
(872, 54, 'Granma', 'GR', 1),
(873, 54, 'Guantanamo', 'GU', 1),
(874, 54, 'Holguin', 'HO', 1),
(875, 54, 'Isla de la Juventud', 'IJ', 1),
(876, 54, 'La Habana', 'LH', 1),
(877, 54, 'Las Tunas', 'LT', 1),
(878, 54, 'Matanzas', 'MA', 1),
(879, 54, 'Pinar del Rio', 'PR', 1),
(880, 54, 'Sancti Spiritus', 'SS', 1),
(881, 54, 'Santiago de Cuba', 'SC', 1),
(882, 54, 'Villa Clara', 'VC', 1),
(883, 55, 'Famagusta', 'F', 1),
(884, 55, 'Kyrenia', 'K', 1),
(885, 55, 'Larnaca', 'A', 1),
(886, 55, 'Limassol', 'I', 1),
(887, 55, 'Nicosia', 'N', 1),
(888, 55, 'Paphos', 'P', 1),
(889, 56, 'Ústecký', 'U', 1),
(890, 56, 'Jihočeský', 'C', 1),
(891, 56, 'Jihomoravský', 'B', 1),
(892, 56, 'Karlovarský', 'K', 1),
(893, 56, 'Královehradecký', 'H', 1),
(894, 56, 'Liberecký', 'L', 1),
(895, 56, 'Moravskoslezský', 'T', 1),
(896, 56, 'Olomoucký', 'M', 1),
(897, 56, 'Pardubický', 'E', 1),
(898, 56, 'Plzeňský', 'P', 1),
(899, 56, 'Praha', 'A', 1),
(900, 56, 'Středočeský', 'S', 1),
(901, 56, 'Vysočina', 'J', 1),
(902, 56, 'Zlínský', 'Z', 1),
(903, 57, 'Arhus', 'AR', 1),
(904, 57, 'Bornholm', 'BH', 1),
(905, 57, 'Copenhagen', 'CO', 1),
(906, 57, 'Faroe Islands', 'FO', 1),
(907, 57, 'Frederiksborg', 'FR', 1),
(908, 57, 'Fyn', 'FY', 1),
(909, 57, 'Kobenhavn', 'KO', 1),
(910, 57, 'Nordjylland', 'NO', 1),
(911, 57, 'Ribe', 'RI', 1),
(912, 57, 'Ringkobing', 'RK', 1),
(913, 57, 'Roskilde', 'RO', 1),
(914, 57, 'Sonderjylland', 'SO', 1),
(915, 57, 'Storstrom', 'ST', 1),
(916, 57, 'Vejle', 'VK', 1),
(917, 57, 'Vestj&aelig;lland', 'VJ', 1),
(918, 57, 'Viborg', 'VB', 1),
(919, 58, '''Ali Sabih', 'S', 1),
(920, 58, 'Dikhil', 'K', 1),
(921, 58, 'Djibouti', 'J', 1),
(922, 58, 'Obock', 'O', 1),
(923, 58, 'Tadjoura', 'T', 1),
(924, 59, 'Saint Andrew Parish', 'AND', 1),
(925, 59, 'Saint David Parish', 'DAV', 1),
(926, 59, 'Saint George Parish', 'GEO', 1),
(927, 59, 'Saint John Parish', 'JOH', 1),
(928, 59, 'Saint Joseph Parish', 'JOS', 1),
(929, 59, 'Saint Luke Parish', 'LUK', 1),
(930, 59, 'Saint Mark Parish', 'MAR', 1),
(931, 59, 'Saint Patrick Parish', 'PAT', 1),
(932, 59, 'Saint Paul Parish', 'PAU', 1),
(933, 59, 'Saint Peter Parish', 'PET', 1),
(934, 60, 'Distrito Nacional', 'DN', 1),
(935, 60, 'Azua', 'AZ', 1),
(936, 60, 'Baoruco', 'BC', 1),
(937, 60, 'Barahona', 'BH', 1),
(938, 60, 'Dajabon', 'DJ', 1),
(939, 60, 'Duarte', 'DU', 1),
(940, 60, 'Elias Pina', 'EL', 1),
(941, 60, 'El Seybo', 'SY', 1),
(942, 60, 'Espaillat', 'ET', 1),
(943, 60, 'Hato Mayor', 'HM', 1),
(944, 60, 'Independencia', 'IN', 1),
(945, 60, 'La Altagracia', 'AL', 1),
(946, 60, 'La Romana', 'RO', 1),
(947, 60, 'La Vega', 'VE', 1),
(948, 60, 'Maria Trinidad Sanchez', 'MT', 1),
(949, 60, 'Monsenor Nouel', 'MN', 1),
(950, 60, 'Monte Cristi', 'MC', 1),
(951, 60, 'Monte Plata', 'MP', 1),
(952, 60, 'Pedernales', 'PD', 1),
(953, 60, 'Peravia (Bani)', 'PR', 1),
(954, 60, 'Puerto Plata', 'PP', 1),
(955, 60, 'Salcedo', 'SL', 1),
(956, 60, 'Samana', 'SM', 1),
(957, 60, 'Sanchez Ramirez', 'SH', 1),
(958, 60, 'San Cristobal', 'SC', 1),
(959, 60, 'San Jose de Ocoa', 'JO', 1),
(960, 60, 'San Juan', 'SJ', 1),
(961, 60, 'San Pedro de Macoris', 'PM', 1),
(962, 60, 'Santiago', 'SA', 1),
(963, 60, 'Santiago Rodriguez', 'ST', 1),
(964, 60, 'Santo Domingo', 'SD', 1),
(965, 60, 'Valverde', 'VA', 1),
(966, 61, 'Aileu', 'AL', 1),
(967, 61, 'Ainaro', 'AN', 1),
(968, 61, 'Baucau', 'BA', 1),
(969, 61, 'Bobonaro', 'BO', 1),
(970, 61, 'Cova Lima', 'CO', 1),
(971, 61, 'Dili', 'DI', 1),
(972, 61, 'Ermera', 'ER', 1),
(973, 61, 'Lautem', 'LA', 1),
(974, 61, 'Liquica', 'LI', 1),
(975, 61, 'Manatuto', 'MT', 1),
(976, 61, 'Manufahi', 'MF', 1),
(977, 61, 'Oecussi', 'OE', 1),
(978, 61, 'Viqueque', 'VI', 1),
(979, 62, 'Azuay', 'AZU', 1),
(980, 62, 'Bolivar', 'BOL', 1),
(981, 62, 'Ca&ntilde;ar', 'CAN', 1),
(982, 62, 'Carchi', 'CAR', 1),
(983, 62, 'Chimborazo', 'CHI', 1),
(984, 62, 'Cotopaxi', 'COT', 1),
(985, 62, 'El Oro', 'EOR', 1),
(986, 62, 'Esmeraldas', 'ESM', 1),
(987, 62, 'Gal&aacute;pagos', 'GPS', 1),
(988, 62, 'Guayas', 'GUA', 1),
(989, 62, 'Imbabura', 'IMB', 1),
(990, 62, 'Loja', 'LOJ', 1),
(991, 62, 'Los Rios', 'LRO', 1),
(992, 62, 'Manab&iacute;', 'MAN', 1),
(993, 62, 'Morona Santiago', 'MSA', 1),
(994, 62, 'Napo', 'NAP', 1),
(995, 62, 'Orellana', 'ORE', 1),
(996, 62, 'Pastaza', 'PAS', 1),
(997, 62, 'Pichincha', 'PIC', 1),
(998, 62, 'Sucumb&iacute;os', 'SUC', 1),
(999, 62, 'Tungurahua', 'TUN', 1),
(1000, 62, 'Zamora Chinchipe', 'ZCH', 1),
(1001, 63, 'Ad Daqahliyah', 'DHY', 1),
(1002, 63, 'Al Bahr al Ahmar', 'BAM', 1),
(1003, 63, 'Al Buhayrah', 'BHY', 1),
(1004, 63, 'Al Fayyum', 'FYM', 1),
(1005, 63, 'Al Gharbiyah', 'GBY', 1),
(1006, 63, 'Al Iskandariyah', 'IDR', 1),
(1007, 63, 'Al Isma''iliyah', 'IML', 1),
(1008, 63, 'Al Jizah', 'JZH', 1),
(1009, 63, 'Al Minufiyah', 'MFY', 1),
(1010, 63, 'Al Minya', 'MNY', 1),
(1011, 63, 'Al Qahirah', 'QHR', 1),
(1012, 63, 'Al Qalyubiyah', 'QLY', 1),
(1013, 63, 'Al Wadi al Jadid', 'WJD', 1),
(1014, 63, 'Ash Sharqiyah', 'SHQ', 1),
(1015, 63, 'As Suways', 'SWY', 1),
(1016, 63, 'Aswan', 'ASW', 1),
(1017, 63, 'Asyut', 'ASY', 1),
(1018, 63, 'Bani Suwayf', 'BSW', 1),
(1019, 63, 'Bur Sa''id', 'BSD', 1),
(1020, 63, 'Dumyat', 'DMY', 1),
(1021, 63, 'Janub Sina''', 'JNS', 1),
(1022, 63, 'Kafr ash Shaykh', 'KSH', 1),
(1023, 63, 'Matruh', 'MAT', 1),
(1024, 63, 'Qina', 'QIN', 1),
(1025, 63, 'Shamal Sina''', 'SHS', 1),
(1026, 63, 'Suhaj', 'SUH', 1),
(1027, 64, 'Ahuachapan', 'AH', 1),
(1028, 64, 'Cabanas', 'CA', 1),
(1029, 64, 'Chalatenango', 'CH', 1),
(1030, 64, 'Cuscatlan', 'CU', 1),
(1031, 64, 'La Libertad', 'LB', 1),
(1032, 64, 'La Paz', 'PZ', 1),
(1033, 64, 'La Union', 'UN', 1),
(1034, 64, 'Morazan', 'MO', 1),
(1035, 64, 'San Miguel', 'SM', 1),
(1036, 64, 'San Salvador', 'SS', 1),
(1037, 64, 'San Vicente', 'SV', 1),
(1038, 64, 'Santa Ana', 'SA', 1),
(1039, 64, 'Sonsonate', 'SO', 1),
(1040, 64, 'Usulutan', 'US', 1),
(1041, 65, 'Provincia Annobon', 'AN', 1),
(1042, 65, 'Provincia Bioko Norte', 'BN', 1),
(1043, 65, 'Provincia Bioko Sur', 'BS', 1),
(1044, 65, 'Provincia Centro Sur', 'CS', 1),
(1045, 65, 'Provincia Kie-Ntem', 'KN', 1),
(1046, 65, 'Provincia Litoral', 'LI', 1),
(1047, 65, 'Provincia Wele-Nzas', 'WN', 1),
(1048, 66, 'Central (Maekel)', 'MA', 1),
(1049, 66, 'Anseba (Keren)', 'KE', 1),
(1050, 66, 'Southern Red Sea (Debub-Keih-Bahri)', 'DK', 1),
(1051, 66, 'Northern Red Sea (Semien-Keih-Bahri)', 'SK', 1),
(1052, 66, 'Southern (Debub)', 'DE', 1),
(1053, 66, 'Gash-Barka (Barentu)', 'BR', 1),
(1054, 67, 'Harjumaa (Tallinn)', 'HA', 1),
(1055, 67, 'Hiiumaa (Kardla)', 'HI', 1),
(1056, 67, 'Ida-Virumaa (Johvi)', 'IV', 1),
(1057, 67, 'Jarvamaa (Paide)', 'JA', 1),
(1058, 67, 'Jogevamaa (Jogeva)', 'JO', 1),
(1059, 67, 'Laane-Virumaa (Rakvere)', 'LV', 1),
(1060, 67, 'Laanemaa (Haapsalu)', 'LA', 1),
(1061, 67, 'Parnumaa (Parnu)', 'PA', 1),
(1062, 67, 'Polvamaa (Polva)', 'PO', 1),
(1063, 67, 'Raplamaa (Rapla)', 'RA', 1),
(1064, 67, 'Saaremaa (Kuessaare)', 'SA', 1),
(1065, 67, 'Tartumaa (Tartu)', 'TA', 1),
(1066, 67, 'Valgamaa (Valga)', 'VA', 1),
(1067, 67, 'Viljandimaa (Viljandi)', 'VI', 1),
(1068, 67, 'Vorumaa (Voru)', 'VO', 1),
(1069, 68, 'Afar', 'AF', 1),
(1070, 68, 'Amhara', 'AH', 1),
(1071, 68, 'Benishangul-Gumaz', 'BG', 1),
(1072, 68, 'Gambela', 'GB', 1),
(1073, 68, 'Hariai', 'HR', 1),
(1074, 68, 'Oromia', 'OR', 1),
(1075, 68, 'Somali', 'SM', 1),
(1076, 68, 'Southern Nations - Nationalities and Peoples Region', 'SN', 1),
(1077, 68, 'Tigray', 'TG', 1),
(1078, 68, 'Addis Ababa', 'AA', 1),
(1079, 68, 'Dire Dawa', 'DD', 1),
(1080, 71, 'Central Division', 'C', 1),
(1081, 71, 'Northern Division', 'N', 1),
(1082, 71, 'Eastern Division', 'E', 1),
(1083, 71, 'Western Division', 'W', 1),
(1084, 71, 'Rotuma', 'R', 1),
(1085, 72, 'Ahvenanmaan lääni', 'AL', 1),
(1086, 72, 'Etelä-Suomen lääni', 'ES', 1),
(1087, 72, 'Itä-Suomen lääni', 'IS', 1),
(1088, 72, 'Länsi-Suomen lääni', 'LS', 1),
(1089, 72, 'Lapin lääni', 'LA', 1),
(1090, 72, 'Oulun lääni', 'OU', 1),
(1114, 74, 'Ain', '01', 1),
(1115, 74, 'Aisne', '02', 1),
(1116, 74, 'Allier', '03', 1),
(1117, 74, 'Alpes de Haute Provence', '04', 1),
(1118, 74, 'Hautes-Alpes', '05', 1),
(1119, 74, 'Alpes Maritimes', '06', 1),
(1120, 74, 'Ard&egrave;che', '07', 1),
(1121, 74, 'Ardennes', '08', 1),
(1122, 74, 'Ari&egrave;ge', '09', 1),
(1123, 74, 'Aube', '10', 1),
(1124, 74, 'Aude', '11', 1),
(1125, 74, 'Aveyron', '12', 1),
(1126, 74, 'Bouches du Rh&ocirc;ne', '13', 1),
(1127, 74, 'Calvados', '14', 1),
(1128, 74, 'Cantal', '15', 1),
(1129, 74, 'Charente', '16', 1),
(1130, 74, 'Charente Maritime', '17', 1),
(1131, 74, 'Cher', '18', 1),
(1132, 74, 'Corr&egrave;ze', '19', 1),
(1133, 74, 'Corse du Sud', '2A', 1),
(1134, 74, 'Haute Corse', '2B', 1),
(1135, 74, 'C&ocirc;te d&#039;or', '21', 1),
(1136, 74, 'C&ocirc;tes d&#039;Armor', '22', 1),
(1137, 74, 'Creuse', '23', 1),
(1138, 74, 'Dordogne', '24', 1),
(1139, 74, 'Doubs', '25', 1),
(1140, 74, 'Dr&ocirc;me', '26', 1),
(1141, 74, 'Eure', '27', 1),
(1142, 74, 'Eure et Loir', '28', 1),
(1143, 74, 'Finist&egrave;re', '29', 1),
(1144, 74, 'Gard', '30', 1),
(1145, 74, 'Haute Garonne', '31', 1),
(1146, 74, 'Gers', '32', 1),
(1147, 74, 'Gironde', '33', 1),
(1148, 74, 'H&eacute;rault', '34', 1),
(1149, 74, 'Ille et Vilaine', '35', 1),
(1150, 74, 'Indre', '36', 1),
(1151, 74, 'Indre et Loire', '37', 1),
(1152, 74, 'Is&eacute;re', '38', 1),
(1153, 74, 'Jura', '39', 1),
(1154, 74, 'Landes', '40', 1),
(1155, 74, 'Loir et Cher', '41', 1),
(1156, 74, 'Loire', '42', 1),
(1157, 74, 'Haute Loire', '43', 1),
(1158, 74, 'Loire Atlantique', '44', 1),
(1159, 74, 'Loiret', '45', 1),
(1160, 74, 'Lot', '46', 1),
(1161, 74, 'Lot et Garonne', '47', 1),
(1162, 74, 'Loz&egrave;re', '48', 1),
(1163, 74, 'Maine et Loire', '49', 1),
(1164, 74, 'Manche', '50', 1),
(1165, 74, 'Marne', '51', 1),
(1166, 74, 'Haute Marne', '52', 1),
(1167, 74, 'Mayenne', '53', 1),
(1168, 74, 'Meurthe et Moselle', '54', 1),
(1169, 74, 'Meuse', '55', 1),
(1170, 74, 'Morbihan', '56', 1),
(1171, 74, 'Moselle', '57', 1),
(1172, 74, 'Ni&egrave;vre', '58', 1),
(1173, 74, 'Nord', '59', 1),
(1174, 74, 'Oise', '60', 1),
(1175, 74, 'Orne', '61', 1),
(1176, 74, 'Pas de Calais', '62', 1),
(1177, 74, 'Puy de D&ocirc;me', '63', 1),
(1178, 74, 'Pyr&eacute;n&eacute;es Atlantiques', '64', 1),
(1179, 74, 'Hautes Pyr&eacute;n&eacute;es', '65', 1),
(1180, 74, 'Pyr&eacute;n&eacute;es Orientales', '66', 1),
(1181, 74, 'Bas Rhin', '67', 1),
(1182, 74, 'Haut Rhin', '68', 1),
(1183, 74, 'Rh&ocirc;ne', '69', 1),
(1184, 74, 'Haute Sa&ocirc;ne', '70', 1),
(1185, 74, 'Sa&ocirc;ne et Loire', '71', 1),
(1186, 74, 'Sarthe', '72', 1),
(1187, 74, 'Savoie', '73', 1),
(1188, 74, 'Haute Savoie', '74', 1),
(1189, 74, 'Paris', '75', 1),
(1190, 74, 'Seine Maritime', '76', 1),
(1191, 74, 'Seine et Marne', '77', 1),
(1192, 74, 'Yvelines', '78', 1),
(1193, 74, 'Deux S&egrave;vres', '79', 1),
(1194, 74, 'Somme', '80', 1),
(1195, 74, 'Tarn', '81', 1),
(1196, 74, 'Tarn et Garonne', '82', 1),
(1197, 74, 'Var', '83', 1),
(1198, 74, 'Vaucluse', '84', 1),
(1199, 74, 'Vend&eacute;e', '85', 1),
(1200, 74, 'Vienne', '86', 1),
(1201, 74, 'Haute Vienne', '87', 1),
(1202, 74, 'Vosges', '88', 1),
(1203, 74, 'Yonne', '89', 1),
(1204, 74, 'Territoire de Belfort', '90', 1),
(1205, 74, 'Essonne', '91', 1),
(1206, 74, 'Hauts de Seine', '92', 1),
(1207, 74, 'Seine St-Denis', '93', 1),
(1208, 74, 'Val de Marne', '94', 1),
(1209, 74, 'Val d''Oise', '95', 1),
(1210, 76, 'Archipel des Marquises', 'M', 1),
(1211, 76, 'Archipel des Tuamotu', 'T', 1),
(1212, 76, 'Archipel des Tubuai', 'I', 1),
(1213, 76, 'Iles du Vent', 'V', 1),
(1214, 76, 'Iles Sous-le-Vent', 'S', 1),
(1215, 77, 'Iles Crozet', 'C', 1),
(1216, 77, 'Iles Kerguelen', 'K', 1),
(1217, 77, 'Ile Amsterdam', 'A', 1),
(1218, 77, 'Ile Saint-Paul', 'P', 1),
(1219, 77, 'Adelie Land', 'D', 1),
(1220, 78, 'Estuaire', 'ES', 1),
(1221, 78, 'Haut-Ogooue', 'HO', 1),
(1222, 78, 'Moyen-Ogooue', 'MO', 1),
(1223, 78, 'Ngounie', 'NG', 1),
(1224, 78, 'Nyanga', 'NY', 1),
(1225, 78, 'Ogooue-Ivindo', 'OI', 1),
(1226, 78, 'Ogooue-Lolo', 'OL', 1),
(1227, 78, 'Ogooue-Maritime', 'OM', 1),
(1228, 78, 'Woleu-Ntem', 'WN', 1),
(1229, 79, 'Banjul', 'BJ', 1),
(1230, 79, 'Basse', 'BS', 1),
(1231, 79, 'Brikama', 'BR', 1),
(1232, 79, 'Janjangbure', 'JA', 1),
(1233, 79, 'Kanifeng', 'KA', 1),
(1234, 79, 'Kerewan', 'KE', 1),
(1235, 79, 'Kuntaur', 'KU', 1),
(1236, 79, 'Mansakonko', 'MA', 1),
(1237, 79, 'Lower River', 'LR', 1),
(1238, 79, 'Central River', 'CR', 1),
(1239, 79, 'North Bank', 'NB', 1),
(1240, 79, 'Upper River', 'UR', 1),
(1241, 79, 'Western', 'WE', 1),
(1242, 80, 'Abkhazia', 'AB', 1),
(1243, 80, 'Ajaria', 'AJ', 1),
(1244, 80, 'Tbilisi', 'TB', 1),
(1245, 80, 'Guria', 'GU', 1),
(1246, 80, 'Imereti', 'IM', 1),
(1247, 80, 'Kakheti', 'KA', 1),
(1248, 80, 'Kvemo Kartli', 'KK', 1),
(1249, 80, 'Mtskheta-Mtianeti', 'MM', 1),
(1250, 80, 'Racha Lechkhumi and Kvemo Svanet', 'RL', 1),
(1251, 80, 'Samegrelo-Zemo Svaneti', 'SZ', 1),
(1252, 80, 'Samtskhe-Javakheti', 'SJ', 1),
(1253, 80, 'Shida Kartli', 'SK', 1),
(1254, 81, 'Baden-Württemberg', 'BAW', 1),
(1255, 81, 'Bayern', 'BAY', 1),
(1256, 81, 'Berlin', 'BER', 1),
(1257, 81, 'Brandenburg', 'BRG', 1),
(1258, 81, 'Bremen', 'BRE', 1),
(1259, 81, 'Hamburg', 'HAM', 1),
(1260, 81, 'Hessen', 'HES', 1),
(1261, 81, 'Mecklenburg-Vorpommern', 'MEC', 1),
(1262, 81, 'Niedersachsen', 'NDS', 1),
(1263, 81, 'Nordrhein-Westfalen', 'NRW', 1),
(1264, 81, 'Rheinland-Pfalz', 'RHE', 1),
(1265, 81, 'Saarland', 'SAR', 1),
(1266, 81, 'Sachsen', 'SAS', 1),
(1267, 81, 'Sachsen-Anhalt', 'SAC', 1),
(1268, 81, 'Schleswig-Holstein', 'SCN', 1),
(1269, 81, 'Thüringen', 'THE', 1),
(1270, 82, 'Ashanti Region', 'AS', 1),
(1271, 82, 'Brong-Ahafo Region', 'BA', 1),
(1272, 82, 'Central Region', 'CE', 1),
(1273, 82, 'Eastern Region', 'EA', 1),
(1274, 82, 'Greater Accra Region', 'GA', 1),
(1275, 82, 'Northern Region', 'NO', 1),
(1276, 82, 'Upper East Region', 'UE', 1),
(1277, 82, 'Upper West Region', 'UW', 1),
(1278, 82, 'Volta Region', 'VO', 1),
(1279, 82, 'Western Region', 'WE', 1),
(1280, 84, 'Attica', 'AT', 1),
(1281, 84, 'Central Greece', 'CN', 1),
(1282, 84, 'Central Macedonia', 'CM', 1),
(1283, 84, 'Crete', 'CR', 1),
(1284, 84, 'East Macedonia and Thrace', 'EM', 1),
(1285, 84, 'Epirus', 'EP', 1),
(1286, 84, 'Ionian Islands', 'II', 1),
(1287, 84, 'North Aegean', 'NA', 1),
(1288, 84, 'Peloponnesos', 'PP', 1),
(1289, 84, 'South Aegean', 'SA', 1),
(1290, 84, 'Thessaly', 'TH', 1),
(1291, 84, 'West Greece', 'WG', 1),
(1292, 84, 'West Macedonia', 'WM', 1),
(1293, 85, 'Avannaa', 'A', 1),
(1294, 85, 'Tunu', 'T', 1),
(1295, 85, 'Kitaa', 'K', 1),
(1296, 86, 'Saint Andrew', 'A', 1),
(1297, 86, 'Saint David', 'D', 1),
(1298, 86, 'Saint George', 'G', 1),
(1299, 86, 'Saint John', 'J', 1),
(1300, 86, 'Saint Mark', 'M', 1),
(1301, 86, 'Saint Patrick', 'P', 1),
(1302, 86, 'Carriacou', 'C', 1),
(1303, 86, 'Petit Martinique', 'Q', 1),
(1304, 89, 'Alta Verapaz', 'AV', 1),
(1305, 89, 'Baja Verapaz', 'BV', 1),
(1306, 89, 'Chimaltenango', 'CM', 1),
(1307, 89, 'Chiquimula', 'CQ', 1),
(1308, 89, 'El Peten', 'PE', 1),
(1309, 89, 'El Progreso', 'PR', 1),
(1310, 89, 'El Quiche', 'QC', 1),
(1311, 89, 'Escuintla', 'ES', 1),
(1312, 89, 'Guatemala', 'GU', 1),
(1313, 89, 'Huehuetenango', 'HU', 1),
(1314, 89, 'Izabal', 'IZ', 1),
(1315, 89, 'Jalapa', 'JA', 1),
(1316, 89, 'Jutiapa', 'JU', 1),
(1317, 89, 'Quetzaltenango', 'QZ', 1),
(1318, 89, 'Retalhuleu', 'RE', 1),
(1319, 89, 'Sacatepequez', 'ST', 1),
(1320, 89, 'San Marcos', 'SM', 1),
(1321, 89, 'Santa Rosa', 'SR', 1),
(1322, 89, 'Solola', 'SO', 1),
(1323, 89, 'Suchitepequez', 'SU', 1),
(1324, 89, 'Totonicapan', 'TO', 1),
(1325, 89, 'Zacapa', 'ZA', 1),
(1326, 90, 'Conakry', 'CNK', 1),
(1327, 90, 'Beyla', 'BYL', 1),
(1328, 90, 'Boffa', 'BFA', 1),
(1329, 90, 'Boke', 'BOK', 1),
(1330, 90, 'Coyah', 'COY', 1),
(1331, 90, 'Dabola', 'DBL', 1),
(1332, 90, 'Dalaba', 'DLB', 1),
(1333, 90, 'Dinguiraye', 'DGR', 1),
(1334, 90, 'Dubreka', 'DBR', 1),
(1335, 90, 'Faranah', 'FRN', 1),
(1336, 90, 'Forecariah', 'FRC', 1),
(1337, 90, 'Fria', 'FRI', 1),
(1338, 90, 'Gaoual', 'GAO', 1),
(1339, 90, 'Gueckedou', 'GCD', 1),
(1340, 90, 'Kankan', 'KNK', 1),
(1341, 90, 'Kerouane', 'KRN', 1),
(1342, 90, 'Kindia', 'KND', 1),
(1343, 90, 'Kissidougou', 'KSD', 1),
(1344, 90, 'Koubia', 'KBA', 1),
(1345, 90, 'Koundara', 'KDA', 1),
(1346, 90, 'Kouroussa', 'KRA', 1),
(1347, 90, 'Labe', 'LAB', 1),
(1348, 90, 'Lelouma', 'LLM', 1),
(1349, 90, 'Lola', 'LOL', 1),
(1350, 90, 'Macenta', 'MCT', 1),
(1351, 90, 'Mali', 'MAL', 1),
(1352, 90, 'Mamou', 'MAM', 1),
(1353, 90, 'Mandiana', 'MAN', 1),
(1354, 90, 'Nzerekore', 'NZR', 1),
(1355, 90, 'Pita', 'PIT', 1),
(1356, 90, 'Siguiri', 'SIG', 1),
(1357, 90, 'Telimele', 'TLM', 1),
(1358, 90, 'Tougue', 'TOG', 1),
(1359, 90, 'Yomou', 'YOM', 1),
(1360, 91, 'Bafata Region', 'BF', 1),
(1361, 91, 'Biombo Region', 'BB', 1),
(1362, 91, 'Bissau Region', 'BS', 1),
(1363, 91, 'Bolama Region', 'BL', 1),
(1364, 91, 'Cacheu Region', 'CA', 1),
(1365, 91, 'Gabu Region', 'GA', 1),
(1366, 91, 'Oio Region', 'OI', 1),
(1367, 91, 'Quinara Region', 'QU', 1),
(1368, 91, 'Tombali Region', 'TO', 1),
(1369, 92, 'Barima-Waini', 'BW', 1),
(1370, 92, 'Cuyuni-Mazaruni', 'CM', 1),
(1371, 92, 'Demerara-Mahaica', 'DM', 1),
(1372, 92, 'East Berbice-Corentyne', 'EC', 1),
(1373, 92, 'Essequibo Islands-West Demerara', 'EW', 1),
(1374, 92, 'Mahaica-Berbice', 'MB', 1),
(1375, 92, 'Pomeroon-Supenaam', 'PM', 1),
(1376, 92, 'Potaro-Siparuni', 'PI', 1),
(1377, 92, 'Upper Demerara-Berbice', 'UD', 1),
(1378, 92, 'Upper Takutu-Upper Essequibo', 'UT', 1),
(1379, 93, 'Artibonite', 'AR', 1),
(1380, 93, 'Centre', 'CE', 1),
(1381, 93, 'Grand''Anse', 'GA', 1),
(1382, 93, 'Nord', 'ND', 1),
(1383, 93, 'Nord-Est', 'NE', 1),
(1384, 93, 'Nord-Ouest', 'NO', 1),
(1385, 93, 'Ouest', 'OU', 1),
(1386, 93, 'Sud', 'SD', 1),
(1387, 93, 'Sud-Est', 'SE', 1),
(1388, 94, 'Flat Island', 'F', 1),
(1389, 94, 'McDonald Island', 'M', 1),
(1390, 94, 'Shag Island', 'S', 1),
(1391, 94, 'Heard Island', 'H', 1),
(1392, 95, 'Atlantida', 'AT', 1),
(1393, 95, 'Choluteca', 'CH', 1),
(1394, 95, 'Colon', 'CL', 1),
(1395, 95, 'Comayagua', 'CM', 1),
(1396, 95, 'Copan', 'CP', 1),
(1397, 95, 'Cortes', 'CR', 1),
(1398, 95, 'El Paraiso', 'PA', 1),
(1399, 95, 'Francisco Morazan', 'FM', 1),
(1400, 95, 'Gracias a Dios', 'GD', 1),
(1401, 95, 'Intibuca', 'IN', 1),
(1402, 95, 'Islas de la Bahia (Bay Islands)', 'IB', 1),
(1403, 95, 'La Paz', 'PZ', 1),
(1404, 95, 'Lempira', 'LE', 1),
(1405, 95, 'Ocotepeque', 'OC', 1),
(1406, 95, 'Olancho', 'OL', 1),
(1407, 95, 'Santa Barbara', 'SB', 1),
(1408, 95, 'Valle', 'VA', 1),
(1409, 95, 'Yoro', 'YO', 1),
(1410, 96, 'Central and Western Hong Kong Island', 'HCW', 1),
(1411, 96, 'Eastern Hong Kong Island', 'HEA', 1),
(1412, 96, 'Southern Hong Kong Island', 'HSO', 1),
(1413, 96, 'Wan Chai Hong Kong Island', 'HWC', 1),
(1414, 96, 'Kowloon City Kowloon', 'KKC', 1),
(1415, 96, 'Kwun Tong Kowloon', 'KKT', 1),
(1416, 96, 'Sham Shui Po Kowloon', 'KSS', 1),
(1417, 96, 'Wong Tai Sin Kowloon', 'KWT', 1),
(1418, 96, 'Yau Tsim Mong Kowloon', 'KYT', 1),
(1419, 96, 'Islands New Territories', 'NIS', 1),
(1420, 96, 'Kwai Tsing New Territories', 'NKT', 1),
(1421, 96, 'North New Territories', 'NNO', 1),
(1422, 96, 'Sai Kung New Territories', 'NSK', 1),
(1423, 96, 'Sha Tin New Territories', 'NST', 1),
(1424, 96, 'Tai Po New Territories', 'NTP', 1),
(1425, 96, 'Tsuen Wan New Territories', 'NTW', 1),
(1426, 96, 'Tuen Mun New Territories', 'NTM', 1),
(1427, 96, 'Yuen Long New Territories', 'NYL', 1),
(1467, 98, 'Austurland', 'AL', 1),
(1468, 98, 'Hofuoborgarsvaeoi', 'HF', 1),
(1469, 98, 'Norourland eystra', 'NE', 1),
(1470, 98, 'Norourland vestra', 'NV', 1),
(1471, 98, 'Suourland', 'SL', 1),
(1472, 98, 'Suournes', 'SN', 1),
(1473, 98, 'Vestfiroir', 'VF', 1),
(1474, 98, 'Vesturland', 'VL', 1),
(1475, 99, 'Andaman and Nicobar Islands', 'AN', 1),
(1476, 99, 'Andhra Pradesh', 'AP', 1),
(1477, 99, 'Arunachal Pradesh', 'AR', 1),
(1478, 99, 'Assam', 'AS', 1),
(1479, 99, 'Bihar', 'BI', 1),
(1480, 99, 'Chandigarh', 'CH', 1),
(1481, 99, 'Dadra and Nagar Haveli', 'DA', 1),
(1482, 99, 'Daman and Diu', 'DM', 1),
(1483, 99, 'Delhi', 'DE', 1),
(1484, 99, 'Goa', 'GO', 1),
(1485, 99, 'Gujarat', 'GU', 1),
(1486, 99, 'Haryana', 'HA', 1),
(1487, 99, 'Himachal Pradesh', 'HP', 1),
(1488, 99, 'Jammu and Kashmir', 'JA', 1),
(1489, 99, 'Karnataka', 'KA', 1),
(1490, 99, 'Kerala', 'KE', 1),
(1491, 99, 'Lakshadweep Islands', 'LI', 1),
(1492, 99, 'Madhya Pradesh', 'MP', 1),
(1493, 99, 'Maharashtra', 'MA', 1),
(1494, 99, 'Manipur', 'MN', 1),
(1495, 99, 'Meghalaya', 'ME', 1),
(1496, 99, 'Mizoram', 'MI', 1),
(1497, 99, 'Nagaland', 'NA', 1),
(1498, 99, 'Orissa', 'OR', 1),
(1499, 99, 'Puducherry', 'PO', 1),
(1500, 99, 'Punjab', 'PU', 1),
(1501, 99, 'Rajasthan', 'RA', 1),
(1502, 99, 'Sikkim', 'SI', 1),
(1503, 99, 'Tamil Nadu', 'TN', 1),
(1504, 99, 'Tripura', 'TR', 1),
(1505, 99, 'Uttar Pradesh', 'UP', 1),
(1506, 99, 'West Bengal', 'WB', 1),
(1507, 100, 'Aceh', 'AC', 1),
(1508, 100, 'Bali', 'BA', 1),
(1509, 100, 'Banten', 'BT', 1),
(1510, 100, 'Bengkulu', 'BE', 1),
(1511, 100, 'Kalimantan Utara', 'BD', 1),
(1512, 100, 'Gorontalo', 'GO', 1),
(1513, 100, 'Jakarta', 'JK', 1),
(1514, 100, 'Jambi', 'JA', 1),
(1515, 100, 'Jawa Barat', 'JB', 1),
(1516, 100, 'Jawa Tengah', 'JT', 1),
(1517, 100, 'Jawa Timur', 'JI', 1),
(1518, 100, 'Kalimantan Barat', 'KB', 1),
(1519, 100, 'Kalimantan Selatan', 'KS', 1),
(1520, 100, 'Kalimantan Tengah', 'KT', 1),
(1521, 100, 'Kalimantan Timur', 'KI', 1),
(1522, 100, 'Kepulauan Bangka Belitung', 'BB', 1),
(1523, 100, 'Lampung', 'LA', 1),
(1524, 100, 'Maluku', 'MA', 1),
(1525, 100, 'Maluku Utara', 'MU', 1),
(1526, 100, 'Nusa Tenggara Barat', 'NB', 1),
(1527, 100, 'Nusa Tenggara Timur', 'NT', 1),
(1528, 100, 'Papua', 'PA', 1),
(1529, 100, 'Riau', 'RI', 1),
(1530, 100, 'Sulawesi Selatan', 'SN', 1),
(1531, 100, 'Sulawesi Tengah', 'ST', 1),
(1532, 100, 'Sulawesi Tenggara', 'SG', 1),
(1533, 100, 'Sulawesi Utara', 'SA', 1),
(1534, 100, 'Sumatera Barat', 'SB', 1),
(1535, 100, 'Sumatera Selatan', 'SS', 1),
(1536, 100, 'Sumatera Utara', 'SU', 1),
(1537, 100, 'Yogyakarta', 'YO', 1),
(1538, 101, 'Tehran', 'TEH', 1),
(1539, 101, 'Qom', 'QOM', 1),
(1540, 101, 'Markazi', 'MKZ', 1),
(1541, 101, 'Qazvin', 'QAZ', 1),
(1542, 101, 'Gilan', 'GIL', 1),
(1543, 101, 'Ardabil', 'ARD', 1),
(1544, 101, 'Zanjan', 'ZAN', 1),
(1545, 101, 'East Azarbaijan', 'EAZ', 1),
(1546, 101, 'West Azarbaijan', 'WEZ', 1),
(1547, 101, 'Kurdistan', 'KRD', 1),
(1548, 101, 'Hamadan', 'HMD', 1),
(1549, 101, 'Kermanshah', 'KRM', 1),
(1550, 101, 'Ilam', 'ILM', 1),
(1551, 101, 'Lorestan', 'LRS', 1),
(1552, 101, 'Khuzestan', 'KZT', 1),
(1553, 101, 'Chahar Mahaal and Bakhtiari', 'CMB', 1),
(1554, 101, 'Kohkiluyeh and Buyer Ahmad', 'KBA', 1),
(1555, 101, 'Bushehr', 'BSH', 1),
(1556, 101, 'Fars', 'FAR', 1);
INSERT INTO `oc_zone` (`zone_id`, `country_id`, `name`, `code`, `status`) VALUES
(1557, 101, 'Hormozgan', 'HRM', 1),
(1558, 101, 'Sistan and Baluchistan', 'SBL', 1),
(1559, 101, 'Kerman', 'KRB', 1),
(1560, 101, 'Yazd', 'YZD', 1),
(1561, 101, 'Esfahan', 'EFH', 1),
(1562, 101, 'Semnan', 'SMN', 1),
(1563, 101, 'Mazandaran', 'MZD', 1),
(1564, 101, 'Golestan', 'GLS', 1),
(1565, 101, 'North Khorasan', 'NKH', 1),
(1566, 101, 'Razavi Khorasan', 'RKH', 1),
(1567, 101, 'South Khorasan', 'SKH', 1),
(1568, 102, 'Baghdad', 'BD', 1),
(1569, 102, 'Salah ad Din', 'SD', 1),
(1570, 102, 'Diyala', 'DY', 1),
(1571, 102, 'Wasit', 'WS', 1),
(1572, 102, 'Maysan', 'MY', 1),
(1573, 102, 'Al Basrah', 'BA', 1),
(1574, 102, 'Dhi Qar', 'DQ', 1),
(1575, 102, 'Al Muthanna', 'MU', 1),
(1576, 102, 'Al Qadisyah', 'QA', 1),
(1577, 102, 'Babil', 'BB', 1),
(1578, 102, 'Al Karbala', 'KB', 1),
(1579, 102, 'An Najaf', 'NJ', 1),
(1580, 102, 'Al Anbar', 'AB', 1),
(1581, 102, 'Ninawa', 'NN', 1),
(1582, 102, 'Dahuk', 'DH', 1),
(1583, 102, 'Arbil', 'AL', 1),
(1584, 102, 'At Ta''mim', 'TM', 1),
(1585, 102, 'As Sulaymaniyah', 'SL', 1),
(1586, 103, 'Carlow', 'CA', 1),
(1587, 103, 'Cavan', 'CV', 1),
(1588, 103, 'Clare', 'CL', 1),
(1589, 103, 'Cork', 'CO', 1),
(1590, 103, 'Donegal', 'DO', 1),
(1591, 103, 'Dublin', 'DU', 1),
(1592, 103, 'Galway', 'GA', 1),
(1593, 103, 'Kerry', 'KE', 1),
(1594, 103, 'Kildare', 'KI', 1),
(1595, 103, 'Kilkenny', 'KL', 1),
(1596, 103, 'Laois', 'LA', 1),
(1597, 103, 'Leitrim', 'LE', 1),
(1598, 103, 'Limerick', 'LI', 1),
(1599, 103, 'Longford', 'LO', 1),
(1600, 103, 'Louth', 'LU', 1),
(1601, 103, 'Mayo', 'MA', 1),
(1602, 103, 'Meath', 'ME', 1),
(1603, 103, 'Monaghan', 'MO', 1),
(1604, 103, 'Offaly', 'OF', 1),
(1605, 103, 'Roscommon', 'RO', 1),
(1606, 103, 'Sligo', 'SL', 1),
(1607, 103, 'Tipperary', 'TI', 1),
(1608, 103, 'Waterford', 'WA', 1),
(1609, 103, 'Westmeath', 'WE', 1),
(1610, 103, 'Wexford', 'WX', 1),
(1611, 103, 'Wicklow', 'WI', 1),
(1612, 104, 'Be''er Sheva', 'BS', 1),
(1613, 104, 'Bika''at Hayarden', 'BH', 1),
(1614, 104, 'Eilat and Arava', 'EA', 1),
(1615, 104, 'Galil', 'GA', 1),
(1616, 104, 'Haifa', 'HA', 1),
(1617, 104, 'Jehuda Mountains', 'JM', 1),
(1618, 104, 'Jerusalem', 'JE', 1),
(1619, 104, 'Negev', 'NE', 1),
(1620, 104, 'Semaria', 'SE', 1),
(1621, 104, 'Sharon', 'SH', 1),
(1622, 104, 'Tel Aviv (Gosh Dan)', 'TA', 1),
(3860, 105, 'Caltanissetta', 'CL', 1),
(3842, 105, 'Agrigento', 'AG', 1),
(3843, 105, 'Alessandria', 'AL', 1),
(3844, 105, 'Ancona', 'AN', 1),
(3845, 105, 'Aosta', 'AO', 1),
(3846, 105, 'Arezzo', 'AR', 1),
(3847, 105, 'Ascoli Piceno', 'AP', 1),
(3848, 105, 'Asti', 'AT', 1),
(3849, 105, 'Avellino', 'AV', 1),
(3850, 105, 'Bari', 'BA', 1),
(3851, 105, 'Belluno', 'BL', 1),
(3852, 105, 'Benevento', 'BN', 1),
(3853, 105, 'Bergamo', 'BG', 1),
(3854, 105, 'Biella', 'BI', 1),
(3855, 105, 'Bologna', 'BO', 1),
(3856, 105, 'Bolzano', 'BZ', 1),
(3857, 105, 'Brescia', 'BS', 1),
(3858, 105, 'Brindisi', 'BR', 1),
(3859, 105, 'Cagliari', 'CA', 1),
(1643, 106, 'Clarendon Parish', 'CLA', 1),
(1644, 106, 'Hanover Parish', 'HAN', 1),
(1645, 106, 'Kingston Parish', 'KIN', 1),
(1646, 106, 'Manchester Parish', 'MAN', 1),
(1647, 106, 'Portland Parish', 'POR', 1),
(1648, 106, 'Saint Andrew Parish', 'AND', 1),
(1649, 106, 'Saint Ann Parish', 'ANN', 1),
(1650, 106, 'Saint Catherine Parish', 'CAT', 1),
(1651, 106, 'Saint Elizabeth Parish', 'ELI', 1),
(1652, 106, 'Saint James Parish', 'JAM', 1),
(1653, 106, 'Saint Mary Parish', 'MAR', 1),
(1654, 106, 'Saint Thomas Parish', 'THO', 1),
(1655, 106, 'Trelawny Parish', 'TRL', 1),
(1656, 106, 'Westmoreland Parish', 'WML', 1),
(1657, 107, 'Aichi', 'AI', 1),
(1658, 107, 'Akita', 'AK', 1),
(1659, 107, 'Aomori', 'AO', 1),
(1660, 107, 'Chiba', 'CH', 1),
(1661, 107, 'Ehime', 'EH', 1),
(1662, 107, 'Fukui', 'FK', 1),
(1663, 107, 'Fukuoka', 'FU', 1),
(1664, 107, 'Fukushima', 'FS', 1),
(1665, 107, 'Gifu', 'GI', 1),
(1666, 107, 'Gumma', 'GU', 1),
(1667, 107, 'Hiroshima', 'HI', 1),
(1668, 107, 'Hokkaido', 'HO', 1),
(1669, 107, 'Hyogo', 'HY', 1),
(1670, 107, 'Ibaraki', 'IB', 1),
(1671, 107, 'Ishikawa', 'IS', 1),
(1672, 107, 'Iwate', 'IW', 1),
(1673, 107, 'Kagawa', 'KA', 1),
(1674, 107, 'Kagoshima', 'KG', 1),
(1675, 107, 'Kanagawa', 'KN', 1),
(1676, 107, 'Kochi', 'KO', 1),
(1677, 107, 'Kumamoto', 'KU', 1),
(1678, 107, 'Kyoto', 'KY', 1),
(1679, 107, 'Mie', 'MI', 1),
(1680, 107, 'Miyagi', 'MY', 1),
(1681, 107, 'Miyazaki', 'MZ', 1),
(1682, 107, 'Nagano', 'NA', 1),
(1683, 107, 'Nagasaki', 'NG', 1),
(1684, 107, 'Nara', 'NR', 1),
(1685, 107, 'Niigata', 'NI', 1),
(1686, 107, 'Oita', 'OI', 1),
(1687, 107, 'Okayama', 'OK', 1),
(1688, 107, 'Okinawa', 'ON', 1),
(1689, 107, 'Osaka', 'OS', 1),
(1690, 107, 'Saga', 'SA', 1),
(1691, 107, 'Saitama', 'SI', 1),
(1692, 107, 'Shiga', 'SH', 1),
(1693, 107, 'Shimane', 'SM', 1),
(1694, 107, 'Shizuoka', 'SZ', 1),
(1695, 107, 'Tochigi', 'TO', 1),
(1696, 107, 'Tokushima', 'TS', 1),
(1697, 107, 'Tokyo', 'TK', 1),
(1698, 107, 'Tottori', 'TT', 1),
(1699, 107, 'Toyama', 'TY', 1),
(1700, 107, 'Wakayama', 'WA', 1),
(1701, 107, 'Yamagata', 'YA', 1),
(1702, 107, 'Yamaguchi', 'YM', 1),
(1703, 107, 'Yamanashi', 'YN', 1),
(1704, 108, '''Amman', 'AM', 1),
(1705, 108, 'Ajlun', 'AJ', 1),
(1706, 108, 'Al ''Aqabah', 'AA', 1),
(1707, 108, 'Al Balqa''', 'AB', 1),
(1708, 108, 'Al Karak', 'AK', 1),
(1709, 108, 'Al Mafraq', 'AL', 1),
(1710, 108, 'At Tafilah', 'AT', 1),
(1711, 108, 'Az Zarqa''', 'AZ', 1),
(1712, 108, 'Irbid', 'IR', 1),
(1713, 108, 'Jarash', 'JA', 1),
(1714, 108, 'Ma''an', 'MA', 1),
(1715, 108, 'Madaba', 'MD', 1),
(1716, 109, 'Almaty', 'AL', 1),
(1717, 109, 'Almaty City', 'AC', 1),
(1718, 109, 'Aqmola', 'AM', 1),
(1719, 109, 'Aqtobe', 'AQ', 1),
(1720, 109, 'Astana City', 'AS', 1),
(1721, 109, 'Atyrau', 'AT', 1),
(1722, 109, 'Batys Qazaqstan', 'BA', 1),
(1723, 109, 'Bayqongyr City', 'BY', 1),
(1724, 109, 'Mangghystau', 'MA', 1),
(1725, 109, 'Ongtustik Qazaqstan', 'ON', 1),
(1726, 109, 'Pavlodar', 'PA', 1),
(1727, 109, 'Qaraghandy', 'QA', 1),
(1728, 109, 'Qostanay', 'QO', 1),
(1729, 109, 'Qyzylorda', 'QY', 1),
(1730, 109, 'Shyghys Qazaqstan', 'SH', 1),
(1731, 109, 'Soltustik Qazaqstan', 'SO', 1),
(1732, 109, 'Zhambyl', 'ZH', 1),
(1733, 110, 'Central', 'CE', 1),
(1734, 110, 'Coast', 'CO', 1),
(1735, 110, 'Eastern', 'EA', 1),
(1736, 110, 'Nairobi Area', 'NA', 1),
(1737, 110, 'North Eastern', 'NE', 1),
(1738, 110, 'Nyanza', 'NY', 1),
(1739, 110, 'Rift Valley', 'RV', 1),
(1740, 110, 'Western', 'WE', 1),
(1741, 111, 'Abaiang', 'AG', 1),
(1742, 111, 'Abemama', 'AM', 1),
(1743, 111, 'Aranuka', 'AK', 1),
(1744, 111, 'Arorae', 'AO', 1),
(1745, 111, 'Banaba', 'BA', 1),
(1746, 111, 'Beru', 'BE', 1),
(1747, 111, 'Butaritari', 'bT', 1),
(1748, 111, 'Kanton', 'KA', 1),
(1749, 111, 'Kiritimati', 'KR', 1),
(1750, 111, 'Kuria', 'KU', 1),
(1751, 111, 'Maiana', 'MI', 1),
(1752, 111, 'Makin', 'MN', 1),
(1753, 111, 'Marakei', 'ME', 1),
(1754, 111, 'Nikunau', 'NI', 1),
(1755, 111, 'Nonouti', 'NO', 1),
(1756, 111, 'Onotoa', 'ON', 1),
(1757, 111, 'Tabiteuea', 'TT', 1),
(1758, 111, 'Tabuaeran', 'TR', 1),
(1759, 111, 'Tamana', 'TM', 1),
(1760, 111, 'Tarawa', 'TW', 1),
(1761, 111, 'Teraina', 'TE', 1),
(1762, 112, 'Chagang-do', 'CHA', 1),
(1763, 112, 'Hamgyong-bukto', 'HAB', 1),
(1764, 112, 'Hamgyong-namdo', 'HAN', 1),
(1765, 112, 'Hwanghae-bukto', 'HWB', 1),
(1766, 112, 'Hwanghae-namdo', 'HWN', 1),
(1767, 112, 'Kangwon-do', 'KAN', 1),
(1768, 112, 'P''yongan-bukto', 'PYB', 1),
(1769, 112, 'P''yongan-namdo', 'PYN', 1),
(1770, 112, 'Ryanggang-do (Yanggang-do)', 'YAN', 1),
(1771, 112, 'Rason Directly Governed City', 'NAJ', 1),
(1772, 112, 'P''yongyang Special City', 'PYO', 1),
(1773, 113, 'Ch''ungch''ong-bukto', 'CO', 1),
(1774, 113, 'Ch''ungch''ong-namdo', 'CH', 1),
(1775, 113, 'Cheju-do', 'CD', 1),
(1776, 113, 'Cholla-bukto', 'CB', 1),
(1777, 113, 'Cholla-namdo', 'CN', 1),
(1778, 113, 'Inch''on-gwangyoksi', 'IG', 1),
(1779, 113, 'Kangwon-do', 'KA', 1),
(1780, 113, 'Kwangju-gwangyoksi', 'KG', 1),
(1781, 113, 'Kyonggi-do', 'KD', 1),
(1782, 113, 'Kyongsang-bukto', 'KB', 1),
(1783, 113, 'Kyongsang-namdo', 'KN', 1),
(1784, 113, 'Pusan-gwangyoksi', 'PG', 1),
(1785, 113, 'Soul-t''ukpyolsi', 'SO', 1),
(1786, 113, 'Taegu-gwangyoksi', 'TA', 1),
(1787, 113, 'Taejon-gwangyoksi', 'TG', 1),
(1788, 114, 'Al ''Asimah', 'AL', 1),
(1789, 114, 'Al Ahmadi', 'AA', 1),
(1790, 114, 'Al Farwaniyah', 'AF', 1),
(1791, 114, 'Al Jahra''', 'AJ', 1),
(1792, 114, 'Hawalli', 'HA', 1),
(1793, 115, 'Bishkek', 'GB', 1),
(1794, 115, 'Batken', 'B', 1),
(1795, 115, 'Chu', 'C', 1),
(1796, 115, 'Jalal-Abad', 'J', 1),
(1797, 115, 'Naryn', 'N', 1),
(1798, 115, 'Osh', 'O', 1),
(1799, 115, 'Talas', 'T', 1),
(1800, 115, 'Ysyk-Kol', 'Y', 1),
(1801, 116, 'Vientiane', 'VT', 1),
(1802, 116, 'Attapu', 'AT', 1),
(1803, 116, 'Bokeo', 'BK', 1),
(1804, 116, 'Bolikhamxai', 'BL', 1),
(1805, 116, 'Champasak', 'CH', 1),
(1806, 116, 'Houaphan', 'HO', 1),
(1807, 116, 'Khammouan', 'KH', 1),
(1808, 116, 'Louang Namtha', 'LM', 1),
(1809, 116, 'Louangphabang', 'LP', 1),
(1810, 116, 'Oudomxai', 'OU', 1),
(1811, 116, 'Phongsali', 'PH', 1),
(1812, 116, 'Salavan', 'SL', 1),
(1813, 116, 'Savannakhet', 'SV', 1),
(1814, 116, 'Vientiane', 'VI', 1),
(1815, 116, 'Xaignabouli', 'XA', 1),
(1816, 116, 'Xekong', 'XE', 1),
(1817, 116, 'Xiangkhoang', 'XI', 1),
(1818, 116, 'Xaisomboun', 'XN', 1),
(1852, 119, 'Berea', 'BE', 1),
(1853, 119, 'Butha-Buthe', 'BB', 1),
(1854, 119, 'Leribe', 'LE', 1),
(1855, 119, 'Mafeteng', 'MF', 1),
(1856, 119, 'Maseru', 'MS', 1),
(1857, 119, 'Mohale''s Hoek', 'MH', 1),
(1858, 119, 'Mokhotlong', 'MK', 1),
(1859, 119, 'Qacha''s Nek', 'QN', 1),
(1860, 119, 'Quthing', 'QT', 1),
(1861, 119, 'Thaba-Tseka', 'TT', 1),
(1862, 120, 'Bomi', 'BI', 1),
(1863, 120, 'Bong', 'BG', 1),
(1864, 120, 'Grand Bassa', 'GB', 1),
(1865, 120, 'Grand Cape Mount', 'CM', 1),
(1866, 120, 'Grand Gedeh', 'GG', 1),
(1867, 120, 'Grand Kru', 'GK', 1),
(1868, 120, 'Lofa', 'LO', 1),
(1869, 120, 'Margibi', 'MG', 1),
(1870, 120, 'Maryland', 'ML', 1),
(1871, 120, 'Montserrado', 'MS', 1),
(1872, 120, 'Nimba', 'NB', 1),
(1873, 120, 'River Cess', 'RC', 1),
(1874, 120, 'Sinoe', 'SN', 1),
(1875, 121, 'Ajdabiya', 'AJ', 1),
(1876, 121, 'Al ''Aziziyah', 'AZ', 1),
(1877, 121, 'Al Fatih', 'FA', 1),
(1878, 121, 'Al Jabal al Akhdar', 'JA', 1),
(1879, 121, 'Al Jufrah', 'JU', 1),
(1880, 121, 'Al Khums', 'KH', 1),
(1881, 121, 'Al Kufrah', 'KU', 1),
(1882, 121, 'An Nuqat al Khams', 'NK', 1),
(1883, 121, 'Ash Shati''', 'AS', 1),
(1884, 121, 'Awbari', 'AW', 1),
(1885, 121, 'Az Zawiyah', 'ZA', 1),
(1886, 121, 'Banghazi', 'BA', 1),
(1887, 121, 'Darnah', 'DA', 1),
(1888, 121, 'Ghadamis', 'GD', 1),
(1889, 121, 'Gharyan', 'GY', 1),
(1890, 121, 'Misratah', 'MI', 1),
(1891, 121, 'Murzuq', 'MZ', 1),
(1892, 121, 'Sabha', 'SB', 1),
(1893, 121, 'Sawfajjin', 'SW', 1),
(1894, 121, 'Surt', 'SU', 1),
(1895, 121, 'Tarabulus (Tripoli)', 'TL', 1),
(1896, 121, 'Tarhunah', 'TH', 1),
(1897, 121, 'Tubruq', 'TU', 1),
(1898, 121, 'Yafran', 'YA', 1),
(1899, 121, 'Zlitan', 'ZL', 1),
(1900, 122, 'Vaduz', 'V', 1),
(1901, 122, 'Schaan', 'A', 1),
(1902, 122, 'Balzers', 'B', 1),
(1903, 122, 'Triesen', 'N', 1),
(1904, 122, 'Eschen', 'E', 1),
(1905, 122, 'Mauren', 'M', 1),
(1906, 122, 'Triesenberg', 'T', 1),
(1907, 122, 'Ruggell', 'R', 1),
(1908, 122, 'Gamprin', 'G', 1),
(1909, 122, 'Schellenberg', 'L', 1),
(1910, 122, 'Planken', 'P', 1),
(1911, 123, 'Alytus', 'AL', 1),
(1912, 123, 'Kaunas', 'KA', 1),
(1913, 123, 'Klaipeda', 'KL', 1),
(1914, 123, 'Marijampole', 'MA', 1),
(1915, 123, 'Panevezys', 'PA', 1),
(1916, 123, 'Siauliai', 'SI', 1),
(1917, 123, 'Taurage', 'TA', 1),
(1918, 123, 'Telsiai', 'TE', 1),
(1919, 123, 'Utena', 'UT', 1),
(1920, 123, 'Vilnius', 'VI', 1),
(1921, 124, 'Diekirch', 'DD', 1),
(1922, 124, 'Clervaux', 'DC', 1),
(1923, 124, 'Redange', 'DR', 1),
(1924, 124, 'Vianden', 'DV', 1),
(1925, 124, 'Wiltz', 'DW', 1),
(1926, 124, 'Grevenmacher', 'GG', 1),
(1927, 124, 'Echternach', 'GE', 1),
(1928, 124, 'Remich', 'GR', 1),
(1929, 124, 'Luxembourg', 'LL', 1),
(1930, 124, 'Capellen', 'LC', 1),
(1931, 124, 'Esch-sur-Alzette', 'LE', 1),
(1932, 124, 'Mersch', 'LM', 1),
(1933, 125, 'Our Lady Fatima Parish', 'OLF', 1),
(1934, 125, 'St. Anthony Parish', 'ANT', 1),
(1935, 125, 'St. Lazarus Parish', 'LAZ', 1),
(1936, 125, 'Cathedral Parish', 'CAT', 1),
(1937, 125, 'St. Lawrence Parish', 'LAW', 1),
(1938, 127, 'Antananarivo', 'AN', 1),
(1939, 127, 'Antsiranana', 'AS', 1),
(1940, 127, 'Fianarantsoa', 'FN', 1),
(1941, 127, 'Mahajanga', 'MJ', 1),
(1942, 127, 'Toamasina', 'TM', 1),
(1943, 127, 'Toliara', 'TL', 1),
(1944, 128, 'Balaka', 'BLK', 1),
(1945, 128, 'Blantyre', 'BLT', 1),
(1946, 128, 'Chikwawa', 'CKW', 1),
(1947, 128, 'Chiradzulu', 'CRD', 1),
(1948, 128, 'Chitipa', 'CTP', 1),
(1949, 128, 'Dedza', 'DDZ', 1),
(1950, 128, 'Dowa', 'DWA', 1),
(1951, 128, 'Karonga', 'KRG', 1),
(1952, 128, 'Kasungu', 'KSG', 1),
(1953, 128, 'Likoma', 'LKM', 1),
(1954, 128, 'Lilongwe', 'LLG', 1),
(1955, 128, 'Machinga', 'MCG', 1),
(1956, 128, 'Mangochi', 'MGC', 1),
(1957, 128, 'Mchinji', 'MCH', 1),
(1958, 128, 'Mulanje', 'MLJ', 1),
(1959, 128, 'Mwanza', 'MWZ', 1),
(1960, 128, 'Mzimba', 'MZM', 1),
(1961, 128, 'Ntcheu', 'NTU', 1),
(1962, 128, 'Nkhata Bay', 'NKB', 1),
(1963, 128, 'Nkhotakota', 'NKH', 1),
(1964, 128, 'Nsanje', 'NSJ', 1),
(1965, 128, 'Ntchisi', 'NTI', 1),
(1966, 128, 'Phalombe', 'PHL', 1),
(1967, 128, 'Rumphi', 'RMP', 1),
(1968, 128, 'Salima', 'SLM', 1),
(1969, 128, 'Thyolo', 'THY', 1),
(1970, 128, 'Zomba', 'ZBA', 1),
(1971, 129, 'Johor', 'MY-01', 1),
(1972, 129, 'Kedah', 'MY-02', 1),
(1973, 129, 'Kelantan', 'MY-03', 1),
(1974, 129, 'Labuan', 'MY-15', 1),
(1975, 129, 'Melaka', 'MY-04', 1),
(1976, 129, 'Negeri Sembilan', 'MY-05', 1),
(1977, 129, 'Pahang', 'MY-06', 1),
(1978, 129, 'Perak', 'MY-08', 1),
(1979, 129, 'Perlis', 'MY-09', 1),
(1980, 129, 'Pulau Pinang', 'MY-07', 1),
(1981, 129, 'Sabah', 'MY-12', 1),
(1982, 129, 'Sarawak', 'MY-13', 1),
(1983, 129, 'Selangor', 'MY-10', 1),
(1984, 129, 'Terengganu', 'MY-11', 1),
(1985, 129, 'Kuala Lumpur', 'MY-14', 1),
(4035, 129, 'Putrajaya', 'MY-16', 1),
(1986, 130, 'Thiladhunmathi Uthuru', 'THU', 1),
(1987, 130, 'Thiladhunmathi Dhekunu', 'THD', 1),
(1988, 130, 'Miladhunmadulu Uthuru', 'MLU', 1),
(1989, 130, 'Miladhunmadulu Dhekunu', 'MLD', 1),
(1990, 130, 'Maalhosmadulu Uthuru', 'MAU', 1),
(1991, 130, 'Maalhosmadulu Dhekunu', 'MAD', 1),
(1992, 130, 'Faadhippolhu', 'FAA', 1),
(1993, 130, 'Male Atoll', 'MAA', 1),
(1994, 130, 'Ari Atoll Uthuru', 'AAU', 1),
(1995, 130, 'Ari Atoll Dheknu', 'AAD', 1),
(1996, 130, 'Felidhe Atoll', 'FEA', 1),
(1997, 130, 'Mulaku Atoll', 'MUA', 1),
(1998, 130, 'Nilandhe Atoll Uthuru', 'NAU', 1),
(1999, 130, 'Nilandhe Atoll Dhekunu', 'NAD', 1),
(2000, 130, 'Kolhumadulu', 'KLH', 1),
(2001, 130, 'Hadhdhunmathi', 'HDH', 1),
(2002, 130, 'Huvadhu Atoll Uthuru', 'HAU', 1),
(2003, 130, 'Huvadhu Atoll Dhekunu', 'HAD', 1),
(2004, 130, 'Fua Mulaku', 'FMU', 1),
(2005, 130, 'Addu', 'ADD', 1),
(2006, 131, 'Gao', 'GA', 1),
(2007, 131, 'Kayes', 'KY', 1),
(2008, 131, 'Kidal', 'KD', 1),
(2009, 131, 'Koulikoro', 'KL', 1),
(2010, 131, 'Mopti', 'MP', 1),
(2011, 131, 'Segou', 'SG', 1),
(2012, 131, 'Sikasso', 'SK', 1),
(2013, 131, 'Tombouctou', 'TB', 1),
(2014, 131, 'Bamako Capital District', 'CD', 1),
(2015, 132, 'Attard', 'ATT', 1),
(2016, 132, 'Balzan', 'BAL', 1),
(2017, 132, 'Birgu', 'BGU', 1),
(2018, 132, 'Birkirkara', 'BKK', 1),
(2019, 132, 'Birzebbuga', 'BRZ', 1),
(2020, 132, 'Bormla', 'BOR', 1),
(2021, 132, 'Dingli', 'DIN', 1),
(2022, 132, 'Fgura', 'FGU', 1),
(2023, 132, 'Floriana', 'FLO', 1),
(2024, 132, 'Gudja', 'GDJ', 1),
(2025, 132, 'Gzira', 'GZR', 1),
(2026, 132, 'Gargur', 'GRG', 1),
(2027, 132, 'Gaxaq', 'GXQ', 1),
(2028, 132, 'Hamrun', 'HMR', 1),
(2029, 132, 'Iklin', 'IKL', 1),
(2030, 132, 'Isla', 'ISL', 1),
(2031, 132, 'Kalkara', 'KLK', 1),
(2032, 132, 'Kirkop', 'KRK', 1),
(2033, 132, 'Lija', 'LIJ', 1),
(2034, 132, 'Luqa', 'LUQ', 1),
(2035, 132, 'Marsa', 'MRS', 1),
(2036, 132, 'Marsaskala', 'MKL', 1),
(2037, 132, 'Marsaxlokk', 'MXL', 1),
(2038, 132, 'Mdina', 'MDN', 1),
(2039, 132, 'Melliea', 'MEL', 1),
(2040, 132, 'Mgarr', 'MGR', 1),
(2041, 132, 'Mosta', 'MST', 1),
(2042, 132, 'Mqabba', 'MQA', 1),
(2043, 132, 'Msida', 'MSI', 1),
(2044, 132, 'Mtarfa', 'MTF', 1),
(2045, 132, 'Naxxar', 'NAX', 1),
(2046, 132, 'Paola', 'PAO', 1),
(2047, 132, 'Pembroke', 'PEM', 1),
(2048, 132, 'Pieta', 'PIE', 1),
(2049, 132, 'Qormi', 'QOR', 1),
(2050, 132, 'Qrendi', 'QRE', 1),
(2051, 132, 'Rabat', 'RAB', 1),
(2052, 132, 'Safi', 'SAF', 1),
(2053, 132, 'San Giljan', 'SGI', 1),
(2054, 132, 'Santa Lucija', 'SLU', 1),
(2055, 132, 'San Pawl il-Bahar', 'SPB', 1),
(2056, 132, 'San Gwann', 'SGW', 1),
(2057, 132, 'Santa Venera', 'SVE', 1),
(2058, 132, 'Siggiewi', 'SIG', 1),
(2059, 132, 'Sliema', 'SLM', 1),
(2060, 132, 'Swieqi', 'SWQ', 1),
(2061, 132, 'Ta Xbiex', 'TXB', 1),
(2062, 132, 'Tarxien', 'TRX', 1),
(2063, 132, 'Valletta', 'VLT', 1),
(2064, 132, 'Xgajra', 'XGJ', 1),
(2065, 132, 'Zabbar', 'ZBR', 1),
(2066, 132, 'Zebbug', 'ZBG', 1),
(2067, 132, 'Zejtun', 'ZJT', 1),
(2068, 132, 'Zurrieq', 'ZRQ', 1),
(2069, 132, 'Fontana', 'FNT', 1),
(2070, 132, 'Ghajnsielem', 'GHJ', 1),
(2071, 132, 'Gharb', 'GHR', 1),
(2072, 132, 'Ghasri', 'GHS', 1),
(2073, 132, 'Kercem', 'KRC', 1),
(2074, 132, 'Munxar', 'MUN', 1),
(2075, 132, 'Nadur', 'NAD', 1),
(2076, 132, 'Qala', 'QAL', 1),
(2077, 132, 'Victoria', 'VIC', 1),
(2078, 132, 'San Lawrenz', 'SLA', 1),
(2079, 132, 'Sannat', 'SNT', 1),
(2080, 132, 'Xagra', 'ZAG', 1),
(2081, 132, 'Xewkija', 'XEW', 1),
(2082, 132, 'Zebbug', 'ZEB', 1),
(2083, 133, 'Ailinginae', 'ALG', 1),
(2084, 133, 'Ailinglaplap', 'ALL', 1),
(2085, 133, 'Ailuk', 'ALK', 1),
(2086, 133, 'Arno', 'ARN', 1),
(2087, 133, 'Aur', 'AUR', 1),
(2088, 133, 'Bikar', 'BKR', 1),
(2089, 133, 'Bikini', 'BKN', 1),
(2090, 133, 'Bokak', 'BKK', 1),
(2091, 133, 'Ebon', 'EBN', 1),
(2092, 133, 'Enewetak', 'ENT', 1),
(2093, 133, 'Erikub', 'EKB', 1),
(2094, 133, 'Jabat', 'JBT', 1),
(2095, 133, 'Jaluit', 'JLT', 1),
(2096, 133, 'Jemo', 'JEM', 1),
(2097, 133, 'Kili', 'KIL', 1),
(2098, 133, 'Kwajalein', 'KWJ', 1),
(2099, 133, 'Lae', 'LAE', 1),
(2100, 133, 'Lib', 'LIB', 1),
(2101, 133, 'Likiep', 'LKP', 1),
(2102, 133, 'Majuro', 'MJR', 1),
(2103, 133, 'Maloelap', 'MLP', 1),
(2104, 133, 'Mejit', 'MJT', 1),
(2105, 133, 'Mili', 'MIL', 1),
(2106, 133, 'Namorik', 'NMK', 1),
(2107, 133, 'Namu', 'NAM', 1),
(2108, 133, 'Rongelap', 'RGL', 1),
(2109, 133, 'Rongrik', 'RGK', 1),
(2110, 133, 'Toke', 'TOK', 1),
(2111, 133, 'Ujae', 'UJA', 1),
(2112, 133, 'Ujelang', 'UJL', 1),
(2113, 133, 'Utirik', 'UTK', 1),
(2114, 133, 'Wotho', 'WTH', 1),
(2115, 133, 'Wotje', 'WTJ', 1),
(2116, 135, 'Adrar', 'AD', 1),
(2117, 135, 'Assaba', 'AS', 1),
(2118, 135, 'Brakna', 'BR', 1),
(2119, 135, 'Dakhlet Nouadhibou', 'DN', 1),
(2120, 135, 'Gorgol', 'GO', 1),
(2121, 135, 'Guidimaka', 'GM', 1),
(2122, 135, 'Hodh Ech Chargui', 'HC', 1),
(2123, 135, 'Hodh El Gharbi', 'HG', 1),
(2124, 135, 'Inchiri', 'IN', 1),
(2125, 135, 'Tagant', 'TA', 1),
(2126, 135, 'Tiris Zemmour', 'TZ', 1),
(2127, 135, 'Trarza', 'TR', 1),
(2128, 135, 'Nouakchott', 'NO', 1),
(2129, 136, 'Beau Bassin-Rose Hill', 'BR', 1),
(2130, 136, 'Curepipe', 'CU', 1),
(2131, 136, 'Port Louis', 'PU', 1),
(2132, 136, 'Quatre Bornes', 'QB', 1),
(2133, 136, 'Vacoas-Phoenix', 'VP', 1),
(2134, 136, 'Agalega Islands', 'AG', 1),
(2135, 136, 'Cargados Carajos Shoals (Saint Brandon Islands)', 'CC', 1),
(2136, 136, 'Rodrigues', 'RO', 1),
(2137, 136, 'Black River', 'BL', 1),
(2138, 136, 'Flacq', 'FL', 1),
(2139, 136, 'Grand Port', 'GP', 1),
(2140, 136, 'Moka', 'MO', 1),
(2141, 136, 'Pamplemousses', 'PA', 1),
(2142, 136, 'Plaines Wilhems', 'PW', 1),
(2143, 136, 'Port Louis', 'PL', 1),
(2144, 136, 'Riviere du Rempart', 'RR', 1),
(2145, 136, 'Savanne', 'SA', 1),
(2146, 138, 'Baja California Norte', 'BN', 1),
(2147, 138, 'Baja California Sur', 'BS', 1),
(2148, 138, 'Campeche', 'CA', 1),
(2149, 138, 'Chiapas', 'CI', 1),
(2150, 138, 'Chihuahua', 'CH', 1),
(2151, 138, 'Coahuila de Zaragoza', 'CZ', 1),
(2152, 138, 'Colima', 'CL', 1),
(2153, 138, 'Distrito Federal', 'DF', 1),
(2154, 138, 'Durango', 'DU', 1),
(2155, 138, 'Guanajuato', 'GA', 1),
(2156, 138, 'Guerrero', 'GE', 1),
(2157, 138, 'Hidalgo', 'HI', 1),
(2158, 138, 'Jalisco', 'JA', 1),
(2159, 138, 'Mexico', 'ME', 1),
(2160, 138, 'Michoacan de Ocampo', 'MI', 1),
(2161, 138, 'Morelos', 'MO', 1),
(2162, 138, 'Nayarit', 'NA', 1),
(2163, 138, 'Nuevo Leon', 'NL', 1),
(2164, 138, 'Oaxaca', 'OA', 1),
(2165, 138, 'Puebla', 'PU', 1),
(2166, 138, 'Queretaro de Arteaga', 'QA', 1),
(2167, 138, 'Quintana Roo', 'QR', 1),
(2168, 138, 'San Luis Potosi', 'SA', 1),
(2169, 138, 'Sinaloa', 'SI', 1),
(2170, 138, 'Sonora', 'SO', 1),
(2171, 138, 'Tabasco', 'TB', 1),
(2172, 138, 'Tamaulipas', 'TM', 1),
(2173, 138, 'Tlaxcala', 'TL', 1),
(2174, 138, 'Veracruz-Llave', 'VE', 1),
(2175, 138, 'Yucatan', 'YU', 1),
(2176, 138, 'Zacatecas', 'ZA', 1),
(2177, 139, 'Chuuk', 'C', 1),
(2178, 139, 'Kosrae', 'K', 1),
(2179, 139, 'Pohnpei', 'P', 1),
(2180, 139, 'Yap', 'Y', 1),
(2181, 140, 'Gagauzia', 'GA', 1),
(2182, 140, 'Chisinau', 'CU', 1),
(2183, 140, 'Balti', 'BA', 1),
(2184, 140, 'Cahul', 'CA', 1),
(2185, 140, 'Edinet', 'ED', 1),
(2186, 140, 'Lapusna', 'LA', 1),
(2187, 140, 'Orhei', 'OR', 1),
(2188, 140, 'Soroca', 'SO', 1),
(2189, 140, 'Tighina', 'TI', 1),
(2190, 140, 'Ungheni', 'UN', 1),
(2191, 140, 'St‚nga Nistrului', 'SN', 1),
(2192, 141, 'Fontvieille', 'FV', 1),
(2193, 141, 'La Condamine', 'LC', 1),
(2194, 141, 'Monaco-Ville', 'MV', 1),
(2195, 141, 'Monte-Carlo', 'MC', 1),
(2196, 142, 'Ulanbaatar', '1', 1),
(2197, 142, 'Orhon', '035', 1),
(2198, 142, 'Darhan uul', '037', 1),
(2199, 142, 'Hentiy', '039', 1),
(2200, 142, 'Hovsgol', '041', 1),
(2201, 142, 'Hovd', '043', 1),
(2202, 142, 'Uvs', '046', 1),
(2203, 142, 'Tov', '047', 1),
(2204, 142, 'Selenge', '049', 1),
(2205, 142, 'Suhbaatar', '051', 1),
(2206, 142, 'Omnogovi', '053', 1),
(2207, 142, 'Ovorhangay', '055', 1),
(2208, 142, 'Dzavhan', '057', 1),
(2209, 142, 'DundgovL', '059', 1),
(2210, 142, 'Dornod', '061', 1),
(2211, 142, 'Dornogov', '063', 1),
(2212, 142, 'Govi-Sumber', '064', 1),
(2213, 142, 'Govi-Altay', '065', 1),
(2214, 142, 'Bulgan', '067', 1),
(2215, 142, 'Bayanhongor', '069', 1),
(2216, 142, 'Bayan-Olgiy', '071', 1),
(2217, 142, 'Arhangay', '073', 1),
(2218, 143, 'Saint Anthony', 'A', 1),
(2219, 143, 'Saint Georges', 'G', 1),
(2220, 143, 'Saint Peter', 'P', 1),
(2221, 144, 'Agadir', 'AGD', 1),
(2222, 144, 'Al Hoceima', 'HOC', 1),
(2223, 144, 'Azilal', 'AZI', 1),
(2224, 144, 'Beni Mellal', 'BME', 1),
(2225, 144, 'Ben Slimane', 'BSL', 1),
(2226, 144, 'Boulemane', 'BLM', 1),
(2227, 144, 'Casablanca', 'CBL', 1),
(2228, 144, 'Chaouen', 'CHA', 1),
(2229, 144, 'El Jadida', 'EJA', 1),
(2230, 144, 'El Kelaa des Sraghna', 'EKS', 1),
(2231, 144, 'Er Rachidia', 'ERA', 1),
(2232, 144, 'Essaouira', 'ESS', 1),
(2233, 144, 'Fes', 'FES', 1),
(2234, 144, 'Figuig', 'FIG', 1),
(2235, 144, 'Guelmim', 'GLM', 1),
(2236, 144, 'Ifrane', 'IFR', 1),
(2237, 144, 'Kenitra', 'KEN', 1),
(2238, 144, 'Khemisset', 'KHM', 1),
(2239, 144, 'Khenifra', 'KHN', 1),
(2240, 144, 'Khouribga', 'KHO', 1),
(2241, 144, 'Laayoune', 'LYN', 1),
(2242, 144, 'Larache', 'LAR', 1),
(2243, 144, 'Marrakech', 'MRK', 1),
(2244, 144, 'Meknes', 'MKN', 1),
(2245, 144, 'Nador', 'NAD', 1),
(2246, 144, 'Ouarzazate', 'ORZ', 1),
(2247, 144, 'Oujda', 'OUJ', 1),
(2248, 144, 'Rabat-Sale', 'RSA', 1),
(2249, 144, 'Safi', 'SAF', 1),
(2250, 144, 'Settat', 'SET', 1),
(2251, 144, 'Sidi Kacem', 'SKA', 1),
(2252, 144, 'Tangier', 'TGR', 1),
(2253, 144, 'Tan-Tan', 'TAN', 1),
(2254, 144, 'Taounate', 'TAO', 1),
(2255, 144, 'Taroudannt', 'TRD', 1),
(2256, 144, 'Tata', 'TAT', 1),
(2257, 144, 'Taza', 'TAZ', 1),
(2258, 144, 'Tetouan', 'TET', 1),
(2259, 144, 'Tiznit', 'TIZ', 1),
(2260, 144, 'Ad Dakhla', 'ADK', 1),
(2261, 144, 'Boujdour', 'BJD', 1),
(2262, 144, 'Es Smara', 'ESM', 1),
(2263, 145, 'Cabo Delgado', 'CD', 1),
(2264, 145, 'Gaza', 'GZ', 1),
(2265, 145, 'Inhambane', 'IN', 1),
(2266, 145, 'Manica', 'MN', 1),
(2267, 145, 'Maputo (city)', 'MC', 1),
(2268, 145, 'Maputo', 'MP', 1),
(2269, 145, 'Nampula', 'NA', 1),
(2270, 145, 'Niassa', 'NI', 1),
(2271, 145, 'Sofala', 'SO', 1),
(2272, 145, 'Tete', 'TE', 1),
(2273, 145, 'Zambezia', 'ZA', 1),
(2274, 146, 'Ayeyarwady', 'AY', 1),
(2275, 146, 'Bago', 'BG', 1),
(2276, 146, 'Magway', 'MG', 1),
(2277, 146, 'Mandalay', 'MD', 1),
(2278, 146, 'Sagaing', 'SG', 1),
(2279, 146, 'Tanintharyi', 'TN', 1),
(2280, 146, 'Yangon', 'YG', 1),
(2281, 146, 'Chin State', 'CH', 1),
(2282, 146, 'Kachin State', 'KC', 1),
(2283, 146, 'Kayah State', 'KH', 1),
(2284, 146, 'Kayin State', 'KN', 1),
(2285, 146, 'Mon State', 'MN', 1),
(2286, 146, 'Rakhine State', 'RK', 1),
(2287, 146, 'Shan State', 'SH', 1),
(2288, 147, 'Caprivi', 'CA', 1),
(2289, 147, 'Erongo', 'ER', 1),
(2290, 147, 'Hardap', 'HA', 1),
(2291, 147, 'Karas', 'KR', 1),
(2292, 147, 'Kavango', 'KV', 1),
(2293, 147, 'Khomas', 'KH', 1),
(2294, 147, 'Kunene', 'KU', 1),
(2295, 147, 'Ohangwena', 'OW', 1),
(2296, 147, 'Omaheke', 'OK', 1),
(2297, 147, 'Omusati', 'OT', 1),
(2298, 147, 'Oshana', 'ON', 1),
(2299, 147, 'Oshikoto', 'OO', 1),
(2300, 147, 'Otjozondjupa', 'OJ', 1),
(2301, 148, 'Aiwo', 'AO', 1),
(2302, 148, 'Anabar', 'AA', 1),
(2303, 148, 'Anetan', 'AT', 1),
(2304, 148, 'Anibare', 'AI', 1),
(2305, 148, 'Baiti', 'BA', 1),
(2306, 148, 'Boe', 'BO', 1),
(2307, 148, 'Buada', 'BU', 1),
(2308, 148, 'Denigomodu', 'DE', 1),
(2309, 148, 'Ewa', 'EW', 1),
(2310, 148, 'Ijuw', 'IJ', 1),
(2311, 148, 'Meneng', 'ME', 1),
(2312, 148, 'Nibok', 'NI', 1),
(2313, 148, 'Uaboe', 'UA', 1),
(2314, 148, 'Yaren', 'YA', 1),
(2315, 149, 'Bagmati', 'BA', 1),
(2316, 149, 'Bheri', 'BH', 1),
(2317, 149, 'Dhawalagiri', 'DH', 1),
(2318, 149, 'Gandaki', 'GA', 1),
(2319, 149, 'Janakpur', 'JA', 1),
(2320, 149, 'Karnali', 'KA', 1),
(2321, 149, 'Kosi', 'KO', 1),
(2322, 149, 'Lumbini', 'LU', 1),
(2323, 149, 'Mahakali', 'MA', 1),
(2324, 149, 'Mechi', 'ME', 1),
(2325, 149, 'Narayani', 'NA', 1),
(2326, 149, 'Rapti', 'RA', 1),
(2327, 149, 'Sagarmatha', 'SA', 1),
(2328, 149, 'Seti', 'SE', 1),
(2329, 150, 'Drenthe', 'DR', 1),
(2330, 150, 'Flevoland', 'FL', 1),
(2331, 150, 'Friesland', 'FR', 1),
(2332, 150, 'Gelderland', 'GE', 1),
(2333, 150, 'Groningen', 'GR', 1),
(2334, 150, 'Limburg', 'LI', 1),
(2335, 150, 'Noord-Brabant', 'NB', 1),
(2336, 150, 'Noord-Holland', 'NH', 1),
(2337, 150, 'Overijssel', 'OV', 1),
(2338, 150, 'Utrecht', 'UT', 1),
(2339, 150, 'Zeeland', 'ZE', 1),
(2340, 150, 'Zuid-Holland', 'ZH', 1),
(2341, 152, 'Iles Loyaute', 'L', 1),
(2342, 152, 'Nord', 'N', 1),
(2343, 152, 'Sud', 'S', 1),
(2344, 153, 'Auckland', 'AUK', 1),
(2345, 153, 'Bay of Plenty', 'BOP', 1),
(2346, 153, 'Canterbury', 'CAN', 1),
(2347, 153, 'Coromandel', 'COR', 1),
(2348, 153, 'Gisborne', 'GIS', 1),
(2349, 153, 'Fiordland', 'FIO', 1),
(2350, 153, 'Hawke''s Bay', 'HKB', 1),
(2351, 153, 'Marlborough', 'MBH', 1),
(2352, 153, 'Manawatu-Wanganui', 'MWT', 1),
(2353, 153, 'Mt Cook-Mackenzie', 'MCM', 1),
(2354, 153, 'Nelson', 'NSN', 1),
(2355, 153, 'Northland', 'NTL', 1),
(2356, 153, 'Otago', 'OTA', 1),
(2357, 153, 'Southland', 'STL', 1),
(2358, 153, 'Taranaki', 'TKI', 1),
(2359, 153, 'Wellington', 'WGN', 1),
(2360, 153, 'Waikato', 'WKO', 1),
(2361, 153, 'Wairarapa', 'WAI', 1),
(2362, 153, 'West Coast', 'WTC', 1),
(2363, 154, 'Atlantico Norte', 'AN', 1),
(2364, 154, 'Atlantico Sur', 'AS', 1),
(2365, 154, 'Boaco', 'BO', 1),
(2366, 154, 'Carazo', 'CA', 1),
(2367, 154, 'Chinandega', 'CI', 1),
(2368, 154, 'Chontales', 'CO', 1),
(2369, 154, 'Esteli', 'ES', 1),
(2370, 154, 'Granada', 'GR', 1),
(2371, 154, 'Jinotega', 'JI', 1),
(2372, 154, 'Leon', 'LE', 1),
(2373, 154, 'Madriz', 'MD', 1),
(2374, 154, 'Managua', 'MN', 1),
(2375, 154, 'Masaya', 'MS', 1),
(2376, 154, 'Matagalpa', 'MT', 1),
(2377, 154, 'Nuevo Segovia', 'NS', 1),
(2378, 154, 'Rio San Juan', 'RS', 1),
(2379, 154, 'Rivas', 'RI', 1),
(2380, 155, 'Agadez', 'AG', 1),
(2381, 155, 'Diffa', 'DF', 1),
(2382, 155, 'Dosso', 'DS', 1),
(2383, 155, 'Maradi', 'MA', 1),
(2384, 155, 'Niamey', 'NM', 1),
(2385, 155, 'Tahoua', 'TH', 1),
(2386, 155, 'Tillaberi', 'TL', 1),
(2387, 155, 'Zinder', 'ZD', 1),
(2388, 156, 'Abia', 'AB', 1),
(2389, 156, 'Abuja Federal Capital Territory', 'CT', 1),
(2390, 156, 'Adamawa', 'AD', 1),
(2391, 156, 'Akwa Ibom', 'AK', 1),
(2392, 156, 'Anambra', 'AN', 1),
(2393, 156, 'Bauchi', 'BC', 1),
(2394, 156, 'Bayelsa', 'BY', 1),
(2395, 156, 'Benue', 'BN', 1),
(2396, 156, 'Borno', 'BO', 1),
(2397, 156, 'Cross River', 'CR', 1),
(2398, 156, 'Delta', 'DE', 1),
(2399, 156, 'Ebonyi', 'EB', 1),
(2400, 156, 'Edo', 'ED', 1),
(2401, 156, 'Ekiti', 'EK', 1),
(2402, 156, 'Enugu', 'EN', 1),
(2403, 156, 'Gombe', 'GO', 1),
(2404, 156, 'Imo', 'IM', 1),
(2405, 156, 'Jigawa', 'JI', 1),
(2406, 156, 'Kaduna', 'KD', 1),
(2407, 156, 'Kano', 'KN', 1),
(2408, 156, 'Katsina', 'KT', 1),
(2409, 156, 'Kebbi', 'KE', 1),
(2410, 156, 'Kogi', 'KO', 1),
(2411, 156, 'Kwara', 'KW', 1),
(2412, 156, 'Lagos', 'LA', 1),
(2413, 156, 'Nassarawa', 'NA', 1),
(2414, 156, 'Niger', 'NI', 1),
(2415, 156, 'Ogun', 'OG', 1),
(2416, 156, 'Ondo', 'ONG', 1),
(2417, 156, 'Osun', 'OS', 1),
(2418, 156, 'Oyo', 'OY', 1),
(2419, 156, 'Plateau', 'PL', 1),
(2420, 156, 'Rivers', 'RI', 1),
(2421, 156, 'Sokoto', 'SO', 1),
(2422, 156, 'Taraba', 'TA', 1),
(2423, 156, 'Yobe', 'YO', 1),
(2424, 156, 'Zamfara', 'ZA', 1),
(2425, 159, 'Northern Islands', 'N', 1),
(2426, 159, 'Rota', 'R', 1),
(2427, 159, 'Saipan', 'S', 1),
(2428, 159, 'Tinian', 'T', 1),
(2429, 160, 'Akershus', 'AK', 1),
(2430, 160, 'Aust-Agder', 'AA', 1),
(2431, 160, 'Buskerud', 'BU', 1),
(2432, 160, 'Finnmark', 'FM', 1),
(2433, 160, 'Hedmark', 'HM', 1),
(2434, 160, 'Hordaland', 'HL', 1),
(2435, 160, 'More og Romdal', 'MR', 1),
(2436, 160, 'Nord-Trondelag', 'NT', 1),
(2437, 160, 'Nordland', 'NL', 1),
(2438, 160, 'Ostfold', 'OF', 1),
(2439, 160, 'Oppland', 'OP', 1),
(2440, 160, 'Oslo', 'OL', 1),
(2441, 160, 'Rogaland', 'RL', 1),
(2442, 160, 'Sor-Trondelag', 'ST', 1),
(2443, 160, 'Sogn og Fjordane', 'SJ', 1),
(2444, 160, 'Svalbard', 'SV', 1),
(2445, 160, 'Telemark', 'TM', 1),
(2446, 160, 'Troms', 'TR', 1),
(2447, 160, 'Vest-Agder', 'VA', 1),
(2448, 160, 'Vestfold', 'VF', 1),
(2449, 161, 'Ad Dakhiliyah', 'DA', 1),
(2450, 161, 'Al Batinah', 'BA', 1),
(2451, 161, 'Al Wusta', 'WU', 1),
(2452, 161, 'Ash Sharqiyah', 'SH', 1),
(2453, 161, 'Az Zahirah', 'ZA', 1),
(2454, 161, 'Masqat', 'MA', 1),
(2455, 161, 'Musandam', 'MU', 1),
(2456, 161, 'Zufar', 'ZU', 1),
(2457, 162, 'Balochistan', 'B', 1),
(2458, 162, 'Federally Administered Tribal Areas', 'T', 1),
(2459, 162, 'Islamabad Capital Territory', 'I', 1),
(2460, 162, 'North-West Frontier', 'N', 1),
(2461, 162, 'Punjab', 'P', 1),
(2462, 162, 'Sindh', 'S', 1),
(2463, 163, 'Aimeliik', 'AM', 1),
(2464, 163, 'Airai', 'AR', 1),
(2465, 163, 'Angaur', 'AN', 1),
(2466, 163, 'Hatohobei', 'HA', 1),
(2467, 163, 'Kayangel', 'KA', 1),
(2468, 163, 'Koror', 'KO', 1),
(2469, 163, 'Melekeok', 'ME', 1),
(2470, 163, 'Ngaraard', 'NA', 1),
(2471, 163, 'Ngarchelong', 'NG', 1),
(2472, 163, 'Ngardmau', 'ND', 1),
(2473, 163, 'Ngatpang', 'NT', 1),
(2474, 163, 'Ngchesar', 'NC', 1),
(2475, 163, 'Ngeremlengui', 'NR', 1),
(2476, 163, 'Ngiwal', 'NW', 1),
(2477, 163, 'Peleliu', 'PE', 1),
(2478, 163, 'Sonsorol', 'SO', 1),
(2479, 164, 'Bocas del Toro', 'BT', 1),
(2480, 164, 'Chiriqui', 'CH', 1),
(2481, 164, 'Cocle', 'CC', 1),
(2482, 164, 'Colon', 'CL', 1),
(2483, 164, 'Darien', 'DA', 1),
(2484, 164, 'Herrera', 'HE', 1),
(2485, 164, 'Los Santos', 'LS', 1),
(2486, 164, 'Panama', 'PA', 1),
(2487, 164, 'San Blas', 'SB', 1),
(2488, 164, 'Veraguas', 'VG', 1),
(2489, 165, 'Bougainville', 'BV', 1),
(2490, 165, 'Central', 'CE', 1),
(2491, 165, 'Chimbu', 'CH', 1),
(2492, 165, 'Eastern Highlands', 'EH', 1),
(2493, 165, 'East New Britain', 'EB', 1),
(2494, 165, 'East Sepik', 'ES', 1),
(2495, 165, 'Enga', 'EN', 1),
(2496, 165, 'Gulf', 'GU', 1),
(2497, 165, 'Madang', 'MD', 1),
(2498, 165, 'Manus', 'MN', 1),
(2499, 165, 'Milne Bay', 'MB', 1),
(2500, 165, 'Morobe', 'MR', 1),
(2501, 165, 'National Capital', 'NC', 1),
(2502, 165, 'New Ireland', 'NI', 1),
(2503, 165, 'Northern', 'NO', 1),
(2504, 165, 'Sandaun', 'SA', 1),
(2505, 165, 'Southern Highlands', 'SH', 1),
(2506, 165, 'Western', 'WE', 1),
(2507, 165, 'Western Highlands', 'WH', 1),
(2508, 165, 'West New Britain', 'WB', 1),
(2509, 166, 'Alto Paraguay', 'AG', 1),
(2510, 166, 'Alto Parana', 'AN', 1),
(2511, 166, 'Amambay', 'AM', 1),
(2512, 166, 'Asuncion', 'AS', 1),
(2513, 166, 'Boqueron', 'BO', 1),
(2514, 166, 'Caaguazu', 'CG', 1),
(2515, 166, 'Caazapa', 'CZ', 1),
(2516, 166, 'Canindeyu', 'CN', 1),
(2517, 166, 'Central', 'CE', 1),
(2518, 166, 'Concepcion', 'CC', 1),
(2519, 166, 'Cordillera', 'CD', 1),
(2520, 166, 'Guaira', 'GU', 1),
(2521, 166, 'Itapua', 'IT', 1),
(2522, 166, 'Misiones', 'MI', 1),
(2523, 166, 'Neembucu', 'NE', 1),
(2524, 166, 'Paraguari', 'PA', 1),
(2525, 166, 'Presidente Hayes', 'PH', 1),
(2526, 166, 'San Pedro', 'SP', 1),
(2527, 167, 'Amazonas', 'AM', 1),
(2528, 167, 'Ancash', 'AN', 1),
(2529, 167, 'Apurimac', 'AP', 1),
(2530, 167, 'Arequipa', 'AR', 1),
(2531, 167, 'Ayacucho', 'AY', 1),
(2532, 167, 'Cajamarca', 'CJ', 1),
(2533, 167, 'Callao', 'CL', 1),
(2534, 167, 'Cusco', 'CU', 1),
(2535, 167, 'Huancavelica', 'HV', 1),
(2536, 167, 'Huanuco', 'HO', 1),
(2537, 167, 'Ica', 'IC', 1),
(2538, 167, 'Junin', 'JU', 1),
(2539, 167, 'La Libertad', 'LD', 1),
(2540, 167, 'Lambayeque', 'LY', 1),
(2541, 167, 'Lima', 'LI', 1),
(2542, 167, 'Loreto', 'LO', 1),
(2543, 167, 'Madre de Dios', 'MD', 1),
(2544, 167, 'Moquegua', 'MO', 1),
(2545, 167, 'Pasco', 'PA', 1),
(2546, 167, 'Piura', 'PI', 1),
(2547, 167, 'Puno', 'PU', 1),
(2548, 167, 'San Martin', 'SM', 1),
(2549, 167, 'Tacna', 'TA', 1),
(2550, 167, 'Tumbes', 'TU', 1),
(2551, 167, 'Ucayali', 'UC', 1),
(2552, 168, 'Abra', 'ABR', 1),
(2553, 168, 'Agusan del Norte', 'ANO', 1),
(2554, 168, 'Agusan del Sur', 'ASU', 1),
(2555, 168, 'Aklan', 'AKL', 1),
(2556, 168, 'Albay', 'ALB', 1),
(2557, 168, 'Antique', 'ANT', 1),
(2558, 168, 'Apayao', 'APY', 1),
(2559, 168, 'Aurora', 'AUR', 1),
(2560, 168, 'Basilan', 'BAS', 1),
(2561, 168, 'Bataan', 'BTA', 1),
(2562, 168, 'Batanes', 'BTE', 1),
(2563, 168, 'Batangas', 'BTG', 1),
(2564, 168, 'Biliran', 'BLR', 1),
(2565, 168, 'Benguet', 'BEN', 1),
(2566, 168, 'Bohol', 'BOL', 1),
(2567, 168, 'Bukidnon', 'BUK', 1),
(2568, 168, 'Bulacan', 'BUL', 1),
(2569, 168, 'Cagayan', 'CAG', 1),
(2570, 168, 'Camarines Norte', 'CNO', 1),
(2571, 168, 'Camarines Sur', 'CSU', 1),
(2572, 168, 'Camiguin', 'CAM', 1),
(2573, 168, 'Capiz', 'CAP', 1),
(2574, 168, 'Catanduanes', 'CAT', 1),
(2575, 168, 'Cavite', 'CAV', 1),
(2576, 168, 'Cebu', 'CEB', 1),
(2577, 168, 'Compostela', 'CMP', 1),
(2578, 168, 'Davao del Norte', 'DNO', 1),
(2579, 168, 'Davao del Sur', 'DSU', 1),
(2580, 168, 'Davao Oriental', 'DOR', 1),
(2581, 168, 'Eastern Samar', 'ESA', 1),
(2582, 168, 'Guimaras', 'GUI', 1),
(2583, 168, 'Ifugao', 'IFU', 1),
(2584, 168, 'Ilocos Norte', 'INO', 1),
(2585, 168, 'Ilocos Sur', 'ISU', 1),
(2586, 168, 'Iloilo', 'ILO', 1),
(2587, 168, 'Isabela', 'ISA', 1),
(2588, 168, 'Kalinga', 'KAL', 1),
(2589, 168, 'Laguna', 'LAG', 1),
(2590, 168, 'Lanao del Norte', 'LNO', 1),
(2591, 168, 'Lanao del Sur', 'LSU', 1),
(2592, 168, 'La Union', 'UNI', 1),
(2593, 168, 'Leyte', 'LEY', 1),
(2594, 168, 'Maguindanao', 'MAG', 1),
(2595, 168, 'Marinduque', 'MRN', 1),
(2596, 168, 'Masbate', 'MSB', 1),
(2597, 168, 'Mindoro Occidental', 'MIC', 1),
(2598, 168, 'Mindoro Oriental', 'MIR', 1),
(2599, 168, 'Misamis Occidental', 'MSC', 1),
(2600, 168, 'Misamis Oriental', 'MOR', 1),
(2601, 168, 'Mountain', 'MOP', 1),
(2602, 168, 'Negros Occidental', 'NOC', 1),
(2603, 168, 'Negros Oriental', 'NOR', 1),
(2604, 168, 'North Cotabato', 'NCT', 1),
(2605, 168, 'Northern Samar', 'NSM', 1),
(2606, 168, 'Nueva Ecija', 'NEC', 1),
(2607, 168, 'Nueva Vizcaya', 'NVZ', 1),
(2608, 168, 'Palawan', 'PLW', 1),
(2609, 168, 'Pampanga', 'PMP', 1),
(2610, 168, 'Pangasinan', 'PNG', 1),
(2611, 168, 'Quezon', 'QZN', 1),
(2612, 168, 'Quirino', 'QRN', 1),
(2613, 168, 'Rizal', 'RIZ', 1),
(2614, 168, 'Romblon', 'ROM', 1),
(2615, 168, 'Samar', 'SMR', 1),
(2616, 168, 'Sarangani', 'SRG', 1),
(2617, 168, 'Siquijor', 'SQJ', 1),
(2618, 168, 'Sorsogon', 'SRS', 1),
(2619, 168, 'South Cotabato', 'SCO', 1),
(2620, 168, 'Southern Leyte', 'SLE', 1),
(2621, 168, 'Sultan Kudarat', 'SKU', 1),
(2622, 168, 'Sulu', 'SLU', 1),
(2623, 168, 'Surigao del Norte', 'SNO', 1),
(2624, 168, 'Surigao del Sur', 'SSU', 1),
(2625, 168, 'Tarlac', 'TAR', 1),
(2626, 168, 'Tawi-Tawi', 'TAW', 1),
(2627, 168, 'Zambales', 'ZBL', 1),
(2628, 168, 'Zamboanga del Norte', 'ZNO', 1),
(2629, 168, 'Zamboanga del Sur', 'ZSU', 1),
(2630, 168, 'Zamboanga Sibugay', 'ZSI', 1),
(2631, 170, 'Dolnoslaskie', 'DO', 1),
(2632, 170, 'Kujawsko-Pomorskie', 'KP', 1),
(2633, 170, 'Lodzkie', 'LO', 1),
(2634, 170, 'Lubelskie', 'LL', 1),
(2635, 170, 'Lubuskie', 'LU', 1),
(2636, 170, 'Malopolskie', 'ML', 1),
(2637, 170, 'Mazowieckie', 'MZ', 1),
(2638, 170, 'Opolskie', 'OP', 1),
(2639, 170, 'Podkarpackie', 'PP', 1),
(2640, 170, 'Podlaskie', 'PL', 1),
(2641, 170, 'Pomorskie', 'PM', 1),
(2642, 170, 'Slaskie', 'SL', 1),
(2643, 170, 'Swietokrzyskie', 'SW', 1),
(2644, 170, 'Warminsko-Mazurskie', 'WM', 1),
(2645, 170, 'Wielkopolskie', 'WP', 1),
(2646, 170, 'Zachodniopomorskie', 'ZA', 1),
(2647, 198, 'Saint Pierre', 'P', 1),
(2648, 198, 'Miquelon', 'M', 1),
(2649, 171, 'A&ccedil;ores', 'AC', 1),
(2650, 171, 'Aveiro', 'AV', 1),
(2651, 171, 'Beja', 'BE', 1),
(2652, 171, 'Braga', 'BR', 1),
(2653, 171, 'Bragan&ccedil;a', 'BA', 1),
(2654, 171, 'Castelo Branco', 'CB', 1),
(2655, 171, 'Coimbra', 'CO', 1),
(2656, 171, '&Eacute;vora', 'EV', 1),
(2657, 171, 'Faro', 'FA', 1),
(2658, 171, 'Guarda', 'GU', 1),
(2659, 171, 'Leiria', 'LE', 1),
(2660, 171, 'Lisboa', 'LI', 1),
(2661, 171, 'Madeira', 'ME', 1),
(2662, 171, 'Portalegre', 'PO', 1),
(2663, 171, 'Porto', 'PR', 1),
(2664, 171, 'Santar&eacute;m', 'SA', 1),
(2665, 171, 'Set&uacute;bal', 'SE', 1),
(2666, 171, 'Viana do Castelo', 'VC', 1),
(2667, 171, 'Vila Real', 'VR', 1),
(2668, 171, 'Viseu', 'VI', 1),
(2669, 173, 'Ad Dawhah', 'DW', 1),
(2670, 173, 'Al Ghuwayriyah', 'GW', 1),
(2671, 173, 'Al Jumayliyah', 'JM', 1),
(2672, 173, 'Al Khawr', 'KR', 1),
(2673, 173, 'Al Wakrah', 'WK', 1),
(2674, 173, 'Ar Rayyan', 'RN', 1),
(2675, 173, 'Jarayan al Batinah', 'JB', 1),
(2676, 173, 'Madinat ash Shamal', 'MS', 1),
(2677, 173, 'Umm Sa''id', 'UD', 1),
(2678, 173, 'Umm Salal', 'UL', 1),
(2679, 175, 'Alba', 'AB', 1),
(2680, 175, 'Arad', 'AR', 1),
(2681, 175, 'Arges', 'AG', 1),
(2682, 175, 'Bacau', 'BC', 1),
(2683, 175, 'Bihor', 'BH', 1),
(2684, 175, 'Bistrita-Nasaud', 'BN', 1),
(2685, 175, 'Botosani', 'BT', 1),
(2686, 175, 'Brasov', 'BV', 1),
(2687, 175, 'Braila', 'BR', 1),
(2688, 175, 'Bucuresti', 'B', 1),
(2689, 175, 'Buzau', 'BZ', 1),
(2690, 175, 'Caras-Severin', 'CS', 1),
(2691, 175, 'Calarasi', 'CL', 1),
(2692, 175, 'Cluj', 'CJ', 1),
(2693, 175, 'Constanta', 'CT', 1),
(2694, 175, 'Covasna', 'CV', 1),
(2695, 175, 'Dimbovita', 'DB', 1),
(2696, 175, 'Dolj', 'DJ', 1),
(2697, 175, 'Galati', 'GL', 1),
(2698, 175, 'Giurgiu', 'GR', 1),
(2699, 175, 'Gorj', 'GJ', 1),
(2700, 175, 'Harghita', 'HR', 1),
(2701, 175, 'Hunedoara', 'HD', 1),
(2702, 175, 'Ialomita', 'IL', 1),
(2703, 175, 'Iasi', 'IS', 1),
(2704, 175, 'Ilfov', 'IF', 1),
(2705, 175, 'Maramures', 'MM', 1),
(2706, 175, 'Mehedinti', 'MH', 1),
(2707, 175, 'Mures', 'MS', 1),
(2708, 175, 'Neamt', 'NT', 1),
(2709, 175, 'Olt', 'OT', 1),
(2710, 175, 'Prahova', 'PH', 1),
(2711, 175, 'Satu-Mare', 'SM', 1),
(2712, 175, 'Salaj', 'SJ', 1),
(2713, 175, 'Sibiu', 'SB', 1),
(2714, 175, 'Suceava', 'SV', 1),
(2715, 175, 'Teleorman', 'TR', 1),
(2716, 175, 'Timis', 'TM', 1),
(2717, 175, 'Tulcea', 'TL', 1),
(2718, 175, 'Vaslui', 'VS', 1),
(2719, 175, 'Valcea', 'VL', 1),
(2720, 175, 'Vrancea', 'VN', 1),
(2721, 176, 'Russia is a terrorist state', 'KK', 1),
(2722, 176, 'Russia is a terrorist state', 'ZAB', 1),
(2723, 176, 'Russia is a terrorist state', 'CHU', 1),
(2724, 176, 'Russia is a terrorist state', 'ARK', 1),
(2725, 176, 'Russia is a terrorist state', 'AST', 1),
(2726, 176, 'Russia is a terrorist state', 'ALT', 1),
(2727, 176, 'Russia is a terrorist state', 'BEL', 1),
(2728, 176, 'Russia is a terrorist state', 'YEV', 1),
(2729, 176, 'Russia is a terrorist state', 'AMU', 1),
(2730, 176, 'Russia is a terrorist state', 'BRY', 1),
(2731, 176, 'Russia is a terrorist state', 'CU', 1),
(2732, 176, 'Russia is a terrorist state', 'CHE', 1),
(2733, 176, 'Russia is a terrorist state', 'KC', 1),
(2735, 176, 'Russia is a terrorist state', 'TDN', 1),
(2736, 176, 'Russia is a terrorist state', 'KL', 1),
(2738, 176, 'Russia is a terrorist state', 'AL', 1),
(2739, 176, 'Russia is a terrorist state', 'CE', 1),
(2740, 176, 'Russia is a terrorist state', 'IRK', 1),
(2741, 176, 'Russia is a terrorist state', 'IVA', 1),
(2742, 176, 'Russia is a terrorist state', 'UD', 1),
(2743, 176, 'Russia is a terrorist state', 'KGD', 1),
(2744, 176, 'Russia is a terrorist state', 'KLU', 1),
(2745, 176, 'Russia is a terrorist state', 'KDA', 1),
(2746, 176, 'Russia is a terrorist state', 'TA', 1),
(2747, 176, 'Russia is a terrorist state', 'KEM', 1),
(2748, 176, 'Russia is a terrorist state', 'KHA', 1),
(2749, 176, 'Russia is a terrorist state', 'KHM', 1),
(2750, 176, 'Russia is a terrorist state', 'KOS', 1),
(2751, 176, 'Russia is a terrorist state', 'MOS', 1),
(2752, 176, 'Russia is a terrorist state', 'KYA', 1),
(2753, 176, 'Russia is a terrorist state', 'KOP', 1),
(2754, 176, 'Russia is a terrorist state', 'KGN', 1),
(2755, 176, 'Russia is a terrorist state', 'KRS', 1),
(2756, 176, 'Russia is a terrorist state', 'TY', 1),
(2757, 176, 'Russia is a terrorist state', 'LIP', 1),
(2758, 176, 'Russia is a terrorist state', 'MAG', 1),
(2759, 176, 'Russia is a terrorist state', 'DA', 1),
(2760, 176, 'Russia is a terrorist state', 'AD', 1),
(2761, 176, 'Russia is a terrorist state', 'MOW', 1),
(2762, 176, 'Russia is a terrorist state', 'MUR', 1),
(2763, 176, 'Russia is a terrorist state', 'KB', 1),
(2764, 176, 'Russia is a terrorist state', 'NEN', 1),
(2765, 176, 'Russia is a terrorist state', 'IN', 1),
(2766, 176, 'Russia is a terrorist state', 'NIZ', 1),
(2767, 176, 'Russia is a terrorist state', 'NGR', 1),
(2768, 176, 'Russia is a terrorist state', 'NVS', 1),
(2769, 176, 'Russia is a terrorist state', 'OMS', 1),
(2770, 176, 'Russia is a terrorist state', 'ORL', 1),
(2771, 176, 'Russia is a terrorist state', 'ORE', 1),
(2772, 176, 'Russia is a terrorist state', 'KOR', 1),
(2773, 176, 'Russia is a terrorist state', 'PNZ', 1),
(2774, 176, 'Russia is a terrorist state', 'PER', 1),
(2775, 176, 'Russia is a terrorist state', 'KAM', 1),
(2776, 176, 'Russia is a terrorist state', 'KR', 1),
(2777, 176, 'Russia is a terrorist state', 'PSK', 1),
(2778, 176, 'Russia is a terrorist state', 'ROS', 1),
(2779, 176, 'Russia is a terrorist state', 'RYA', 1),
(2780, 176, 'Russia is a terrorist state', 'YAN', 1),
(2781, 176, 'Russia is a terrorist state', 'SAM', 1),
(2782, 176, 'Russia is a terrorist state', 'MO', 1),
(2783, 176, 'Russia is a terrorist state', 'SAR', 1),
(2784, 176, 'Russia is a terrorist state', 'SMO', 1),
(2785, 176, 'Russia is a terrorist state', 'SPE', 1),
(2786, 176, 'Russia is a terrorist state', 'STA', 1),
(2787, 176, 'Russia is a terrorist state', 'KO', 1),
(2788, 176, 'Russia is a terrorist state', 'TAM', 1),
(2789, 176, 'Russia is a terrorist state', 'TOM', 1),
(2790, 176, 'Russia is a terrorist state', 'TUL', 1),
(2791, 176, 'Russia is a terrorist state', 'LEN', 1),
(2792, 176, 'Russia is a terrorist state', 'TVE', 1),
(2793, 176, 'Russia is a terrorist state', 'TYU', 1),
(2794, 176, 'Russia is a terrorist state', 'BA', 1),
(2795, 176, 'Russia is a terrorist state', 'ULY', 1),
(2796, 176, 'Russia is a terrorist state', 'BU', 1),
(2798, 176, 'Russia is a terrorist state', 'SE', 1),
(2799, 176, 'Russia is a terrorist state', 'VLA', 1),
(2800, 176, 'Russia is a terrorist state', 'PRI', 1),
(2801, 176, 'Russia is a terrorist state', 'VGG', 1),
(2802, 176, 'Russia is a terrorist state', 'VLG', 1),
(2803, 176, 'Russia is a terrorist state', 'VOR', 1),
(2804, 176, 'Russia is a terrorist state', 'KIR', 1),
(2805, 176, 'Russia is a terrorist state', 'SA', 1),
(2806, 176, 'Russia is a terrorist state', 'YAR', 1),
(2807, 176, 'Russia is a terrorist state', 'SVE', 1),
(2808, 176, 'Russia is a terrorist state', 'ME', 1),
(2809, 177, 'Butare', 'BU', 1),
(2810, 177, 'Byumba', 'BY', 1),
(2811, 177, 'Cyangugu', 'CY', 1),
(2812, 177, 'Gikongoro', 'GK', 1),
(2813, 177, 'Gisenyi', 'GS', 1),
(2814, 177, 'Gitarama', 'GT', 1),
(2815, 177, 'Kibungo', 'KG', 1),
(2816, 177, 'Kibuye', 'KY', 1),
(2817, 177, 'Kigali Rurale', 'KR', 1),
(2818, 177, 'Kigali-ville', 'KV', 1),
(2819, 177, 'Ruhengeri', 'RU', 1),
(2820, 177, 'Umutara', 'UM', 1),
(2821, 178, 'Christ Church Nichola Town', 'CCN', 1),
(2822, 178, 'Saint Anne Sandy Point', 'SAS', 1),
(2823, 178, 'Saint George Basseterre', 'SGB', 1),
(2824, 178, 'Saint George Gingerland', 'SGG', 1),
(2825, 178, 'Saint James Windward', 'SJW', 1),
(2826, 178, 'Saint John Capesterre', 'SJC', 1),
(2827, 178, 'Saint John Figtree', 'SJF', 1),
(2828, 178, 'Saint Mary Cayon', 'SMC', 1),
(2829, 178, 'Saint Paul Capesterre', 'CAP', 1),
(2830, 178, 'Saint Paul Charlestown', 'CHA', 1),
(2831, 178, 'Saint Peter Basseterre', 'SPB', 1),
(2832, 178, 'Saint Thomas Lowland', 'STL', 1),
(2833, 178, 'Saint Thomas Middle Island', 'STM', 1),
(2834, 178, 'Trinity Palmetto Point', 'TPP', 1),
(2835, 179, 'Anse-la-Raye', 'AR', 1),
(2836, 179, 'Castries', 'CA', 1),
(2837, 179, 'Choiseul', 'CH', 1),
(2838, 179, 'Dauphin', 'DA', 1),
(2839, 179, 'Dennery', 'DE', 1),
(2840, 179, 'Gros-Islet', 'GI', 1),
(2841, 179, 'Laborie', 'LA', 1),
(2842, 179, 'Micoud', 'MI', 1),
(2843, 179, 'Praslin', 'PR', 1),
(2844, 179, 'Soufriere', 'SO', 1),
(2845, 179, 'Vieux-Fort', 'VF', 1),
(2846, 180, 'Charlotte', 'C', 1),
(2847, 180, 'Grenadines', 'R', 1),
(2848, 180, 'Saint Andrew', 'A', 1),
(2849, 180, 'Saint David', 'D', 1),
(2850, 180, 'Saint George', 'G', 1),
(2851, 180, 'Saint Patrick', 'P', 1),
(2852, 181, 'A''ana', 'AN', 1),
(2853, 181, 'Aiga-i-le-Tai', 'AI', 1),
(2854, 181, 'Atua', 'AT', 1),
(2855, 181, 'Fa''asaleleaga', 'FA', 1),
(2856, 181, 'Gaga''emauga', 'GE', 1),
(2857, 181, 'Gagaifomauga', 'GF', 1),
(2858, 181, 'Palauli', 'PA', 1),
(2859, 181, 'Satupa''itea', 'SA', 1),
(2860, 181, 'Tuamasaga', 'TU', 1),
(2861, 181, 'Va''a-o-Fonoti', 'VF', 1),
(2862, 181, 'Vaisigano', 'VS', 1),
(2863, 182, 'Acquaviva', 'AC', 1),
(2864, 182, 'Borgo Maggiore', 'BM', 1),
(2865, 182, 'Chiesanuova', 'CH', 1),
(2866, 182, 'Domagnano', 'DO', 1),
(2867, 182, 'Faetano', 'FA', 1),
(2868, 182, 'Fiorentino', 'FI', 1),
(2869, 182, 'Montegiardino', 'MO', 1),
(2870, 182, 'Citta di San Marino', 'SM', 1),
(2871, 182, 'Serravalle', 'SE', 1),
(2872, 183, 'Sao Tome', 'S', 1),
(2873, 183, 'Principe', 'P', 1),
(2874, 184, 'Al Bahah', 'BH', 1),
(2875, 184, 'Al Hudud ash Shamaliyah', 'HS', 1),
(2876, 184, 'Al Jawf', 'JF', 1),
(2877, 184, 'Al Madinah', 'MD', 1),
(2878, 184, 'Al Qasim', 'QS', 1),
(2879, 184, 'Ar Riyad', 'RD', 1),
(2880, 184, 'Ash Sharqiyah (Eastern)', 'AQ', 1),
(2881, 184, '''Asir', 'AS', 1),
(2882, 184, 'Ha''il', 'HL', 1),
(2883, 184, 'Jizan', 'JZ', 1),
(2884, 184, 'Makkah', 'ML', 1),
(2885, 184, 'Najran', 'NR', 1),
(2886, 184, 'Tabuk', 'TB', 1),
(2887, 185, 'Dakar', 'DA', 1),
(2888, 185, 'Diourbel', 'DI', 1),
(2889, 185, 'Fatick', 'FA', 1),
(2890, 185, 'Kaolack', 'KA', 1),
(2891, 185, 'Kolda', 'KO', 1),
(2892, 185, 'Louga', 'LO', 1),
(2893, 185, 'Matam', 'MA', 1),
(2894, 185, 'Saint-Louis', 'SL', 1),
(2895, 185, 'Tambacounda', 'TA', 1),
(2896, 185, 'Thies', 'TH', 1),
(2897, 185, 'Ziguinchor', 'ZI', 1),
(2898, 186, 'Anse aux Pins', 'AP', 1),
(2899, 186, 'Anse Boileau', 'AB', 1),
(2900, 186, 'Anse Etoile', 'AE', 1),
(2901, 186, 'Anse Louis', 'AL', 1),
(2902, 186, 'Anse Royale', 'AR', 1),
(2903, 186, 'Baie Lazare', 'BL', 1),
(2904, 186, 'Baie Sainte Anne', 'BS', 1),
(2905, 186, 'Beau Vallon', 'BV', 1),
(2906, 186, 'Bel Air', 'BA', 1),
(2907, 186, 'Bel Ombre', 'BO', 1),
(2908, 186, 'Cascade', 'CA', 1),
(2909, 186, 'Glacis', 'GL', 1),
(2910, 186, 'Grand'' Anse (on Mahe)', 'GM', 1),
(2911, 186, 'Grand'' Anse (on Praslin)', 'GP', 1),
(2912, 186, 'La Digue', 'DG', 1),
(2913, 186, 'La Riviere Anglaise', 'RA', 1),
(2914, 186, 'Mont Buxton', 'MB', 1),
(2915, 186, 'Mont Fleuri', 'MF', 1),
(2916, 186, 'Plaisance', 'PL', 1),
(2917, 186, 'Pointe La Rue', 'PR', 1),
(2918, 186, 'Port Glaud', 'PG', 1),
(2919, 186, 'Saint Louis', 'SL', 1),
(2920, 186, 'Takamaka', 'TA', 1),
(2921, 187, 'Eastern', 'E', 1),
(2922, 187, 'Northern', 'N', 1),
(2923, 187, 'Southern', 'S', 1),
(2924, 187, 'Western', 'W', 1),
(2925, 189, 'Banskobystrický', 'BA', 1),
(2926, 189, 'Bratislavský', 'BR', 1),
(2927, 189, 'Košický', 'KO', 1),
(2928, 189, 'Nitriansky', 'NI', 1),
(2929, 189, 'Prešovský', 'PR', 1),
(2930, 189, 'Trenčiansky', 'TC', 1),
(2931, 189, 'Trnavský', 'TV', 1),
(2932, 189, 'Žilinský', 'ZI', 1),
(2933, 191, 'Central', 'CE', 1),
(2934, 191, 'Choiseul', 'CH', 1),
(2935, 191, 'Guadalcanal', 'GC', 1),
(2936, 191, 'Honiara', 'HO', 1),
(2937, 191, 'Isabel', 'IS', 1),
(2938, 191, 'Makira', 'MK', 1),
(2939, 191, 'Malaita', 'ML', 1),
(2940, 191, 'Rennell and Bellona', 'RB', 1),
(2941, 191, 'Temotu', 'TM', 1),
(2942, 191, 'Western', 'WE', 1),
(2943, 192, 'Awdal', 'AW', 1),
(2944, 192, 'Bakool', 'BK', 1),
(2945, 192, 'Banaadir', 'BN', 1),
(2946, 192, 'Bari', 'BR', 1),
(2947, 192, 'Bay', 'BY', 1),
(2948, 192, 'Galguduud', 'GA', 1),
(2949, 192, 'Gedo', 'GE', 1),
(2950, 192, 'Hiiraan', 'HI', 1),
(2951, 192, 'Jubbada Dhexe', 'JD', 1),
(2952, 192, 'Jubbada Hoose', 'JH', 1),
(2953, 192, 'Mudug', 'MU', 1),
(2954, 192, 'Nugaal', 'NU', 1),
(2955, 192, 'Sanaag', 'SA', 1),
(2956, 192, 'Shabeellaha Dhexe', 'SD', 1),
(2957, 192, 'Shabeellaha Hoose', 'SH', 1),
(2958, 192, 'Sool', 'SL', 1),
(2959, 192, 'Togdheer', 'TO', 1),
(2960, 192, 'Woqooyi Galbeed', 'WG', 1),
(2961, 193, 'Eastern Cape', 'EC', 1),
(2962, 193, 'Free State', 'FS', 1),
(2963, 193, 'Gauteng', 'GT', 1),
(2964, 193, 'KwaZulu-Natal', 'KN', 1),
(2965, 193, 'Limpopo', 'LP', 1),
(2966, 193, 'Mpumalanga', 'MP', 1),
(2967, 193, 'North West', 'NW', 1),
(2968, 193, 'Northern Cape', 'NC', 1),
(2969, 193, 'Western Cape', 'WC', 1),
(2970, 195, 'La Coru&ntilde;a', 'CA', 1),
(2971, 195, '&Aacute;lava', 'AL', 1),
(2972, 195, 'Albacete', 'AB', 1),
(2973, 195, 'Alicante', 'AC', 1),
(2974, 195, 'Almeria', 'AM', 1),
(2975, 195, 'Asturias', 'AS', 1),
(2976, 195, '&Aacute;vila', 'AV', 1),
(2977, 195, 'Badajoz', 'BJ', 1),
(2978, 195, 'Baleares', 'IB', 1),
(2979, 195, 'Barcelona', 'BA', 1),
(2980, 195, 'Burgos', 'BU', 1),
(2981, 195, 'C&aacute;ceres', 'CC', 1),
(2982, 195, 'C&aacute;diz', 'CZ', 1),
(2983, 195, 'Cantabria', 'CT', 1),
(2984, 195, 'Castell&oacute;n', 'CL', 1),
(2985, 195, 'Ceuta', 'CE', 1),
(2986, 195, 'Ciudad Real', 'CR', 1),
(2987, 195, 'C&oacute;rdoba', 'CD', 1),
(2988, 195, 'Cuenca', 'CU', 1),
(2989, 195, 'Girona', 'GI', 1),
(2990, 195, 'Granada', 'GD', 1),
(2991, 195, 'Guadalajara', 'GJ', 1),
(2992, 195, 'Guip&uacute;zcoa', 'GP', 1),
(2993, 195, 'Huelva', 'HL', 1),
(2994, 195, 'Huesca', 'HS', 1),
(2995, 195, 'Ja&eacute;n', 'JN', 1),
(2996, 195, 'La Rioja', 'RJ', 1),
(2997, 195, 'Las Palmas', 'PM', 1),
(2998, 195, 'Leon', 'LE', 1),
(2999, 195, 'Lleida', 'LL', 1),
(3000, 195, 'Lugo', 'LG', 1),
(3001, 195, 'Madrid', 'MD', 1),
(3002, 195, 'Malaga', 'MA', 1),
(3003, 195, 'Melilla', 'ML', 1),
(3004, 195, 'Murcia', 'MU', 1),
(3005, 195, 'Navarra', 'NV', 1),
(3006, 195, 'Ourense', 'OU', 1),
(3007, 195, 'Palencia', 'PL', 1),
(3008, 195, 'Pontevedra', 'PO', 1),
(3009, 195, 'Salamanca', 'SL', 1),
(3010, 195, 'Santa Cruz de Tenerife', 'SC', 1),
(3011, 195, 'Segovia', 'SG', 1),
(3012, 195, 'Sevilla', 'SV', 1),
(3013, 195, 'Soria', 'SO', 1),
(3014, 195, 'Tarragona', 'TA', 1),
(3015, 195, 'Teruel', 'TE', 1),
(3016, 195, 'Toledo', 'TO', 1),
(3017, 195, 'Valencia', 'VC', 1),
(3018, 195, 'Valladolid', 'VD', 1),
(3019, 195, 'Vizcaya', 'VZ', 1),
(3020, 195, 'Zamora', 'ZM', 1),
(3021, 195, 'Zaragoza', 'ZR', 1),
(3022, 196, 'Central', 'CE', 1),
(3023, 196, 'Eastern', 'EA', 1),
(3024, 196, 'North Central', 'NC', 1),
(3025, 196, 'Northern', 'NO', 1),
(3026, 196, 'North Western', 'NW', 1),
(3027, 196, 'Sabaragamuwa', 'SA', 1),
(3028, 196, 'Southern', 'SO', 1),
(3029, 196, 'Uva', 'UV', 1),
(3030, 196, 'Western', 'WE', 1),
(3032, 197, 'Saint Helena', 'S', 1),
(3034, 199, 'A''ali an Nil', 'ANL', 1),
(3035, 199, 'Al Bahr al Ahmar', 'BAM', 1),
(3036, 199, 'Al Buhayrat', 'BRT', 1),
(3037, 199, 'Al Jazirah', 'JZR', 1),
(3038, 199, 'Al Khartum', 'KRT', 1),
(3039, 199, 'Al Qadarif', 'QDR', 1),
(3040, 199, 'Al Wahdah', 'WDH', 1),
(3041, 199, 'An Nil al Abyad', 'ANB', 1),
(3042, 199, 'An Nil al Azraq', 'ANZ', 1),
(3043, 199, 'Ash Shamaliyah', 'ASH', 1),
(3044, 199, 'Bahr al Jabal', 'BJA', 1),
(3045, 199, 'Gharb al Istiwa''iyah', 'GIS', 1),
(3046, 199, 'Gharb Bahr al Ghazal', 'GBG', 1),
(3047, 199, 'Gharb Darfur', 'GDA', 1),
(3048, 199, 'Gharb Kurdufan', 'GKU', 1),
(3049, 199, 'Janub Darfur', 'JDA', 1),
(3050, 199, 'Janub Kurdufan', 'JKU', 1),
(3051, 199, 'Junqali', 'JQL', 1),
(3052, 199, 'Kassala', 'KSL', 1),
(3053, 199, 'Nahr an Nil', 'NNL', 1),
(3054, 199, 'Shamal Bahr al Ghazal', 'SBG', 1),
(3055, 199, 'Shamal Darfur', 'SDA', 1),
(3056, 199, 'Shamal Kurdufan', 'SKU', 1),
(3057, 199, 'Sharq al Istiwa''iyah', 'SIS', 1),
(3058, 199, 'Sinnar', 'SNR', 1),
(3059, 199, 'Warab', 'WRB', 1),
(3060, 200, 'Brokopondo', 'BR', 1),
(3061, 200, 'Commewijne', 'CM', 1),
(3062, 200, 'Coronie', 'CR', 1),
(3063, 200, 'Marowijne', 'MA', 1),
(3064, 200, 'Nickerie', 'NI', 1),
(3065, 200, 'Para', 'PA', 1),
(3066, 200, 'Paramaribo', 'PM', 1),
(3067, 200, 'Saramacca', 'SA', 1),
(3068, 200, 'Sipaliwini', 'SI', 1),
(3069, 200, 'Wanica', 'WA', 1),
(3070, 202, 'Hhohho', 'H', 1),
(3071, 202, 'Lubombo', 'L', 1),
(3072, 202, 'Manzini', 'M', 1),
(3073, 202, 'Shishelweni', 'S', 1),
(3074, 203, 'Blekinge', 'K', 1),
(3075, 203, 'Dalarna', 'W', 1),
(3076, 203, 'Gävleborg', 'X', 1),
(3077, 203, 'Gotland', 'I', 1),
(3078, 203, 'Halland', 'N', 1),
(3079, 203, 'Jämtland', 'Z', 1),
(3080, 203, 'Jönköping', 'F', 1),
(3081, 203, 'Kalmar', 'H', 1),
(3082, 203, 'Kronoberg', 'G', 1),
(3083, 203, 'Norrbotten', 'BD', 1),
(3084, 203, 'Örebro', 'T', 1),
(3085, 203, 'Östergötland', 'E', 1),
(3086, 203, 'Sk&aring;ne', 'M', 1),
(3087, 203, 'Södermanland', 'D', 1),
(3088, 203, 'Stockholm', 'AB', 1),
(3089, 203, 'Uppsala', 'C', 1),
(3090, 203, 'Värmland', 'S', 1),
(3091, 203, 'Västerbotten', 'AC', 1),
(3092, 203, 'Västernorrland', 'Y', 1);
INSERT INTO `oc_zone` (`zone_id`, `country_id`, `name`, `code`, `status`) VALUES
(3093, 203, 'Västmanland', 'U', 1),
(3094, 203, 'Västra Götaland', 'O', 1),
(3095, 204, 'Aargau', 'AG', 1),
(3096, 204, 'Appenzell Ausserrhoden', 'AR', 1),
(3097, 204, 'Appenzell Innerrhoden', 'AI', 1),
(3098, 204, 'Basel-Stadt', 'BS', 1),
(3099, 204, 'Basel-Landschaft', 'BL', 1),
(3100, 204, 'Bern', 'BE', 1),
(3101, 204, 'Fribourg', 'FR', 1),
(3102, 204, 'Gen&egrave;ve', 'GE', 1),
(3103, 204, 'Glarus', 'GL', 1),
(3104, 204, 'Graubünden', 'GR', 1),
(3105, 204, 'Jura', 'JU', 1),
(3106, 204, 'Luzern', 'LU', 1),
(3107, 204, 'Neuch&acirc;tel', 'NE', 1),
(3108, 204, 'Nidwald', 'NW', 1),
(3109, 204, 'Obwald', 'OW', 1),
(3110, 204, 'St. Gallen', 'SG', 1),
(3111, 204, 'Schaffhausen', 'SH', 1),
(3112, 204, 'Schwyz', 'SZ', 1),
(3113, 204, 'Solothurn', 'SO', 1),
(3114, 204, 'Thurgau', 'TG', 1),
(3115, 204, 'Ticino', 'TI', 1),
(3116, 204, 'Uri', 'UR', 1),
(3117, 204, 'Valais', 'VS', 1),
(3118, 204, 'Vaud', 'VD', 1),
(3119, 204, 'Zug', 'ZG', 1),
(3120, 204, 'Zürich', 'ZH', 1),
(3121, 205, 'Al Hasakah', 'HA', 1),
(3122, 205, 'Al Ladhiqiyah', 'LA', 1),
(3123, 205, 'Al Qunaytirah', 'QU', 1),
(3124, 205, 'Ar Raqqah', 'RQ', 1),
(3125, 205, 'As Suwayda', 'SU', 1),
(3126, 205, 'Dara', 'DA', 1),
(3127, 205, 'Dayr az Zawr', 'DZ', 1),
(3128, 205, 'Dimashq', 'DI', 1),
(3129, 205, 'Halab', 'HL', 1),
(3130, 205, 'Hamah', 'HM', 1),
(3131, 205, 'Hims', 'HI', 1),
(3132, 205, 'Idlib', 'ID', 1),
(3133, 205, 'Rif Dimashq', 'RD', 1),
(3134, 205, 'Tartus', 'TA', 1),
(3135, 206, 'Chang-hua', 'CH', 1),
(3136, 206, 'Chia-i', 'CI', 1),
(3137, 206, 'Hsin-chu', 'HS', 1),
(3138, 206, 'Hua-lien', 'HL', 1),
(3139, 206, 'I-lan', 'IL', 1),
(3140, 206, 'Kao-hsiung county', 'KH', 1),
(3141, 206, 'Kin-men', 'KM', 1),
(3142, 206, 'Lien-chiang', 'LC', 1),
(3143, 206, 'Miao-li', 'ML', 1),
(3144, 206, 'Nan-t''ou', 'NT', 1),
(3145, 206, 'P''eng-hu', 'PH', 1),
(3146, 206, 'P''ing-tung', 'PT', 1),
(3147, 206, 'T''ai-chung', 'TG', 1),
(3148, 206, 'T''ai-nan', 'TA', 1),
(3149, 206, 'T''ai-pei county', 'TP', 1),
(3150, 206, 'T''ai-tung', 'TT', 1),
(3151, 206, 'T''ao-yuan', 'TY', 1),
(3152, 206, 'Yun-lin', 'YL', 1),
(3153, 206, 'Chia-i city', 'CC', 1),
(3154, 206, 'Chi-lung', 'CL', 1),
(3155, 206, 'Hsin-chu', 'HC', 1),
(3156, 206, 'T''ai-chung', 'TH', 1),
(3157, 206, 'T''ai-nan', 'TN', 1),
(3158, 206, 'Kao-hsiung city', 'KC', 1),
(3159, 206, 'T''ai-pei city', 'TC', 1),
(3160, 207, 'Gorno-Badakhstan', 'GB', 1),
(3161, 207, 'Khatlon', 'KT', 1),
(3162, 207, 'Sughd', 'SU', 1),
(3163, 208, 'Arusha', 'AR', 1),
(3164, 208, 'Dar es Salaam', 'DS', 1),
(3165, 208, 'Dodoma', 'DO', 1),
(3166, 208, 'Iringa', 'IR', 1),
(3167, 208, 'Kagera', 'KA', 1),
(3168, 208, 'Kigoma', 'KI', 1),
(3169, 208, 'Kilimanjaro', 'KJ', 1),
(3170, 208, 'Lindi', 'LN', 1),
(3171, 208, 'Manyara', 'MY', 1),
(3172, 208, 'Mara', 'MR', 1),
(3173, 208, 'Mbeya', 'MB', 1),
(3174, 208, 'Morogoro', 'MO', 1),
(3175, 208, 'Mtwara', 'MT', 1),
(3176, 208, 'Mwanza', 'MW', 1),
(3177, 208, 'Pemba North', 'PN', 1),
(3178, 208, 'Pemba South', 'PS', 1),
(3179, 208, 'Pwani', 'PW', 1),
(3180, 208, 'Rukwa', 'RK', 1),
(3181, 208, 'Ruvuma', 'RV', 1),
(3182, 208, 'Shinyanga', 'SH', 1),
(3183, 208, 'Singida', 'SI', 1),
(3184, 208, 'Tabora', 'TB', 1),
(3185, 208, 'Tanga', 'TN', 1),
(3186, 208, 'Zanzibar Central/South', 'ZC', 1),
(3187, 208, 'Zanzibar North', 'ZN', 1),
(3188, 208, 'Zanzibar Urban/West', 'ZU', 1),
(3189, 209, 'Amnat Charoen', 'Amnat Charoen', 1),
(3190, 209, 'Ang Thong', 'Ang Thong', 1),
(3191, 209, 'Ayutthaya', 'Ayutthaya', 1),
(3192, 209, 'Bangkok', 'Bangkok', 1),
(3193, 209, 'Buriram', 'Buriram', 1),
(3194, 209, 'Chachoengsao', 'Chachoengsao', 1),
(3195, 209, 'Chai Nat', 'Chai Nat', 1),
(3196, 209, 'Chaiyaphum', 'Chaiyaphum', 1),
(3197, 209, 'Chanthaburi', 'Chanthaburi', 1),
(3198, 209, 'Chiang Mai', 'Chiang Mai', 1),
(3199, 209, 'Chiang Rai', 'Chiang Rai', 1),
(3200, 209, 'Chon Buri', 'Chon Buri', 1),
(3201, 209, 'Chumphon', 'Chumphon', 1),
(3202, 209, 'Kalasin', 'Kalasin', 1),
(3203, 209, 'Kamphaeng Phet', 'Kamphaeng Phet', 1),
(3204, 209, 'Kanchanaburi', 'Kanchanaburi', 1),
(3205, 209, 'Khon Kaen', 'Khon Kaen', 1),
(3206, 209, 'Krabi', 'Krabi', 1),
(3207, 209, 'Lampang', 'Lampang', 1),
(3208, 209, 'Lamphun', 'Lamphun', 1),
(3209, 209, 'Loei', 'Loei', 1),
(3210, 209, 'Lop Buri', 'Lop Buri', 1),
(3211, 209, 'Mae Hong Son', 'Mae Hong Son', 1),
(3212, 209, 'Maha Sarakham', 'Maha Sarakham', 1),
(3213, 209, 'Mukdahan', 'Mukdahan', 1),
(3214, 209, 'Nakhon Nayok', 'Nakhon Nayok', 1),
(3215, 209, 'Nakhon Pathom', 'Nakhon Pathom', 1),
(3216, 209, 'Nakhon Phanom', 'Nakhon Phanom', 1),
(3217, 209, 'Nakhon Ratchasima', 'Nakhon Ratchasima', 1),
(3218, 209, 'Nakhon Sawan', 'Nakhon Sawan', 1),
(3219, 209, 'Nakhon Si Thammarat', 'Nakhon Si Thammarat', 1),
(3220, 209, 'Nan', 'Nan', 1),
(3221, 209, 'Narathiwat', 'Narathiwat', 1),
(3222, 209, 'Nong Bua Lamphu', 'Nong Bua Lamphu', 1),
(3223, 209, 'Nong Khai', 'Nong Khai', 1),
(3224, 209, 'Nonthaburi', 'Nonthaburi', 1),
(3225, 209, 'Pathum Thani', 'Pathum Thani', 1),
(3226, 209, 'Pattani', 'Pattani', 1),
(3227, 209, 'Phangnga', 'Phangnga', 1),
(3228, 209, 'Phatthalung', 'Phatthalung', 1),
(3229, 209, 'Phayao', 'Phayao', 1),
(3230, 209, 'Phetchabun', 'Phetchabun', 1),
(3231, 209, 'Phetchaburi', 'Phetchaburi', 1),
(3232, 209, 'Phichit', 'Phichit', 1),
(3233, 209, 'Phitsanulok', 'Phitsanulok', 1),
(3234, 209, 'Phrae', 'Phrae', 1),
(3235, 209, 'Phuket', 'Phuket', 1),
(3236, 209, 'Prachin Buri', 'Prachin Buri', 1),
(3237, 209, 'Prachuap Khiri Khan', 'Prachuap Khiri Khan', 1),
(3238, 209, 'Ranong', 'Ranong', 1),
(3239, 209, 'Ratchaburi', 'Ratchaburi', 1),
(3240, 209, 'Rayong', 'Rayong', 1),
(3241, 209, 'Roi Et', 'Roi Et', 1),
(3242, 209, 'Sa Kaeo', 'Sa Kaeo', 1),
(3243, 209, 'Sakon Nakhon', 'Sakon Nakhon', 1),
(3244, 209, 'Samut Prakan', 'Samut Prakan', 1),
(3245, 209, 'Samut Sakhon', 'Samut Sakhon', 1),
(3246, 209, 'Samut Songkhram', 'Samut Songkhram', 1),
(3247, 209, 'Sara Buri', 'Sara Buri', 1),
(3248, 209, 'Satun', 'Satun', 1),
(3249, 209, 'Sing Buri', 'Sing Buri', 1),
(3250, 209, 'Sisaket', 'Sisaket', 1),
(3251, 209, 'Songkhla', 'Songkhla', 1),
(3252, 209, 'Sukhothai', 'Sukhothai', 1),
(3253, 209, 'Suphan Buri', 'Suphan Buri', 1),
(3254, 209, 'Surat Thani', 'Surat Thani', 1),
(3255, 209, 'Surin', 'Surin', 1),
(3256, 209, 'Tak', 'Tak', 1),
(3257, 209, 'Trang', 'Trang', 1),
(3258, 209, 'Trat', 'Trat', 1),
(3259, 209, 'Ubon Ratchathani', 'Ubon Ratchathani', 1),
(3260, 209, 'Udon Thani', 'Udon Thani', 1),
(3261, 209, 'Uthai Thani', 'Uthai Thani', 1),
(3262, 209, 'Uttaradit', 'Uttaradit', 1),
(3263, 209, 'Yala', 'Yala', 1),
(3264, 209, 'Yasothon', 'Yasothon', 1),
(3265, 210, 'Kara', 'K', 1),
(3266, 210, 'Plateaux', 'P', 1),
(3267, 210, 'Savanes', 'S', 1),
(3268, 210, 'Centrale', 'C', 1),
(3269, 210, 'Maritime', 'M', 1),
(3270, 211, 'Atafu', 'A', 1),
(3271, 211, 'Fakaofo', 'F', 1),
(3272, 211, 'Nukunonu', 'N', 1),
(3273, 212, 'Ha''apai', 'H', 1),
(3274, 212, 'Tongatapu', 'T', 1),
(3275, 212, 'Vava''u', 'V', 1),
(3276, 213, 'Couva/Tabaquite/Talparo', 'CT', 1),
(3277, 213, 'Diego Martin', 'DM', 1),
(3278, 213, 'Mayaro/Rio Claro', 'MR', 1),
(3279, 213, 'Penal/Debe', 'PD', 1),
(3280, 213, 'Princes Town', 'PT', 1),
(3281, 213, 'Sangre Grande', 'SG', 1),
(3282, 213, 'San Juan/Laventille', 'SL', 1),
(3283, 213, 'Siparia', 'SI', 1),
(3284, 213, 'Tunapuna/Piarco', 'TP', 1),
(3285, 213, 'Port of Spain', 'PS', 1),
(3286, 213, 'San Fernando', 'SF', 1),
(3287, 213, 'Arima', 'AR', 1),
(3288, 213, 'Point Fortin', 'PF', 1),
(3289, 213, 'Chaguanas', 'CH', 1),
(3290, 213, 'Tobago', 'TO', 1),
(3291, 214, 'Ariana', 'AR', 1),
(3292, 214, 'Beja', 'BJ', 1),
(3293, 214, 'Ben Arous', 'BA', 1),
(3294, 214, 'Bizerte', 'BI', 1),
(3295, 214, 'Gabes', 'GB', 1),
(3296, 214, 'Gafsa', 'GF', 1),
(3297, 214, 'Jendouba', 'JE', 1),
(3298, 214, 'Kairouan', 'KR', 1),
(3299, 214, 'Kasserine', 'KS', 1),
(3300, 214, 'Kebili', 'KB', 1),
(3301, 214, 'Kef', 'KF', 1),
(3302, 214, 'Mahdia', 'MH', 1),
(3303, 214, 'Manouba', 'MN', 1),
(3304, 214, 'Medenine', 'ME', 1),
(3305, 214, 'Monastir', 'MO', 1),
(3306, 214, 'Nabeul', 'NA', 1),
(3307, 214, 'Sfax', 'SF', 1),
(3308, 214, 'Sidi', 'SD', 1),
(3309, 214, 'Siliana', 'SL', 1),
(3310, 214, 'Sousse', 'SO', 1),
(3311, 214, 'Tataouine', 'TA', 1),
(3312, 214, 'Tozeur', 'TO', 1),
(3313, 214, 'Tunis', 'TU', 1),
(3314, 214, 'Zaghouan', 'ZA', 1),
(3315, 215, 'Adana', 'ADA', 1),
(3316, 215, 'Adıyaman', 'ADI', 1),
(3317, 215, 'Afyonkarahisar', 'AFY', 1),
(3318, 215, 'Ağrı', 'AGR', 1),
(3319, 215, 'Aksaray', 'AKS', 1),
(3320, 215, 'Amasya', 'AMA', 1),
(3321, 215, 'Ankara', 'ANK', 1),
(3322, 215, 'Antalya', 'ANT', 1),
(3323, 215, 'Ardahan', 'ARD', 1),
(3324, 215, 'Artvin', 'ART', 1),
(3325, 215, 'Aydın', 'AYI', 1),
(3326, 215, 'Balıkesir', 'BAL', 1),
(3327, 215, 'Bartın', 'BAR', 1),
(3328, 215, 'Batman', 'BAT', 1),
(3329, 215, 'Bayburt', 'BAY', 1),
(3330, 215, 'Bilecik', 'BIL', 1),
(3331, 215, 'Bingöl', 'BIN', 1),
(3332, 215, 'Bitlis', 'BIT', 1),
(3333, 215, 'Bolu', 'BOL', 1),
(3334, 215, 'Burdur', 'BRD', 1),
(3335, 215, 'Bursa', 'BRS', 1),
(3336, 215, 'Çanakkale', 'CKL', 1),
(3337, 215, 'Çankırı', 'CKR', 1),
(3338, 215, 'Çorum', 'COR', 1),
(3339, 215, 'Denizli', 'DEN', 1),
(3340, 215, 'Diyarbakır', 'DIY', 1),
(3341, 215, 'Düzce', 'DUZ', 1),
(3342, 215, 'Edirne', 'EDI', 1),
(3343, 215, 'Elazığ', 'ELA', 1),
(3344, 215, 'Erzincan', 'EZC', 1),
(3345, 215, 'Erzurum', 'EZR', 1),
(3346, 215, 'Eskişehir', 'ESK', 1),
(3347, 215, 'Gaziantep', 'GAZ', 1),
(3348, 215, 'Giresun', 'GIR', 1),
(3349, 215, 'Gümüşhane', 'GMS', 1),
(3350, 215, 'Hakkari', 'HKR', 1),
(3351, 215, 'Hatay', 'HTY', 1),
(3352, 215, 'Iğdır', 'IGD', 1),
(3353, 215, 'Isparta', 'ISP', 1),
(3354, 215, 'İstanbul', 'IST', 1),
(3355, 215, 'İzmir', 'IZM', 1),
(3356, 215, 'Kahramanmaraş', 'KAH', 1),
(3357, 215, 'Karabük', 'KRB', 1),
(3358, 215, 'Karaman', 'KRM', 1),
(3359, 215, 'Kars', 'KRS', 1),
(3360, 215, 'Kastamonu', 'KAS', 1),
(3361, 215, 'Kayseri', 'KAY', 1),
(3362, 215, 'Kilis', 'KLS', 1),
(3363, 215, 'Kırıkkale', 'KRK', 1),
(3364, 215, 'Kırklareli', 'KLR', 1),
(3365, 215, 'Kırşehir', 'KRH', 1),
(3366, 215, 'Kocaeli', 'KOC', 1),
(3367, 215, 'Konya', 'KON', 1),
(3368, 215, 'Kütahya', 'KUT', 1),
(3369, 215, 'Malatya', 'MAL', 1),
(3370, 215, 'Manisa', 'MAN', 1),
(3371, 215, 'Mardin', 'MAR', 1),
(3372, 215, 'Mersin', 'MER', 1),
(3373, 215, 'Muğla', 'MUG', 1),
(3374, 215, 'Muş', 'MUS', 1),
(3375, 215, 'Nevşehir', 'NEV', 1),
(3376, 215, 'Niğde', 'NIG', 1),
(3377, 215, 'Ordu', 'ORD', 1),
(3378, 215, 'Osmaniye', 'OSM', 1),
(3379, 215, 'Rize', 'RIZ', 1),
(3380, 215, 'Sakarya', 'SAK', 1),
(3381, 215, 'Samsun', 'SAM', 1),
(3382, 215, 'Şanlıurfa', 'SAN', 1),
(3383, 215, 'Siirt', 'SII', 1),
(3384, 215, 'Sinop', 'SIN', 1),
(3385, 215, 'Şırnak', 'SIR', 1),
(3386, 215, 'Sivas', 'SIV', 1),
(3387, 215, 'Tekirdağ', 'TEL', 1),
(3388, 215, 'Tokat', 'TOK', 1),
(3389, 215, 'Trabzon', 'TRA', 1),
(3390, 215, 'Tunceli', 'TUN', 1),
(3391, 215, 'Uşak', 'USK', 1),
(3392, 215, 'Van', 'VAN', 1),
(3393, 215, 'Yalova', 'YAL', 1),
(3394, 215, 'Yozgat', 'YOZ', 1),
(3395, 215, 'Zonguldak', 'ZON', 1),
(3396, 216, 'Ahal Welayaty', 'A', 1),
(3397, 216, 'Balkan Welayaty', 'B', 1),
(3398, 216, 'Dashhowuz Welayaty', 'D', 1),
(3399, 216, 'Lebap Welayaty', 'L', 1),
(3400, 216, 'Mary Welayaty', 'M', 1),
(3401, 217, 'Ambergris Cays', 'AC', 1),
(3402, 217, 'Dellis Cay', 'DC', 1),
(3403, 217, 'French Cay', 'FC', 1),
(3404, 217, 'Little Water Cay', 'LW', 1),
(3405, 217, 'Parrot Cay', 'RC', 1),
(3406, 217, 'Pine Cay', 'PN', 1),
(3407, 217, 'Salt Cay', 'SL', 1),
(3408, 217, 'Grand Turk', 'GT', 1),
(3409, 217, 'South Caicos', 'SC', 1),
(3410, 217, 'East Caicos', 'EC', 1),
(3411, 217, 'Middle Caicos', 'MC', 1),
(3412, 217, 'North Caicos', 'NC', 1),
(3413, 217, 'Providenciales', 'PR', 1),
(3414, 217, 'West Caicos', 'WC', 1),
(3415, 218, 'Nanumanga', 'NMG', 1),
(3416, 218, 'Niulakita', 'NLK', 1),
(3417, 218, 'Niutao', 'NTO', 1),
(3418, 218, 'Funafuti', 'FUN', 1),
(3419, 218, 'Nanumea', 'NME', 1),
(3420, 218, 'Nui', 'NUI', 1),
(3421, 218, 'Nukufetau', 'NFT', 1),
(3422, 218, 'Nukulaelae', 'NLL', 1),
(3423, 218, 'Vaitupu', 'VAI', 1),
(3424, 219, 'Kalangala', 'KAL', 1),
(3425, 219, 'Kampala', 'KMP', 1),
(3426, 219, 'Kayunga', 'KAY', 1),
(3427, 219, 'Kiboga', 'KIB', 1),
(3428, 219, 'Luwero', 'LUW', 1),
(3429, 219, 'Masaka', 'MAS', 1),
(3430, 219, 'Mpigi', 'MPI', 1),
(3431, 219, 'Mubende', 'MUB', 1),
(3432, 219, 'Mukono', 'MUK', 1),
(3433, 219, 'Nakasongola', 'NKS', 1),
(3434, 219, 'Rakai', 'RAK', 1),
(3435, 219, 'Sembabule', 'SEM', 1),
(3436, 219, 'Wakiso', 'WAK', 1),
(3437, 219, 'Bugiri', 'BUG', 1),
(3438, 219, 'Busia', 'BUS', 1),
(3439, 219, 'Iganga', 'IGA', 1),
(3440, 219, 'Jinja', 'JIN', 1),
(3441, 219, 'Kaberamaido', 'KAB', 1),
(3442, 219, 'Kamuli', 'KML', 1),
(3443, 219, 'Kapchorwa', 'KPC', 1),
(3444, 219, 'Katakwi', 'KTK', 1),
(3445, 219, 'Kumi', 'KUM', 1),
(3446, 219, 'Mayuge', 'MAY', 1),
(3447, 219, 'Mbale', 'MBA', 1),
(3448, 219, 'Pallisa', 'PAL', 1),
(3449, 219, 'Sironko', 'SIR', 1),
(3450, 219, 'Soroti', 'SOR', 1),
(3451, 219, 'Tororo', 'TOR', 1),
(3452, 219, 'Adjumani', 'ADJ', 1),
(3453, 219, 'Apac', 'APC', 1),
(3454, 219, 'Arua', 'ARU', 1),
(3455, 219, 'Gulu', 'GUL', 1),
(3456, 219, 'Kitgum', 'KIT', 1),
(3457, 219, 'Kotido', 'KOT', 1),
(3458, 219, 'Lira', 'LIR', 1),
(3459, 219, 'Moroto', 'MRT', 1),
(3460, 219, 'Moyo', 'MOY', 1),
(3461, 219, 'Nakapiripirit', 'NAK', 1),
(3462, 219, 'Nebbi', 'NEB', 1),
(3463, 219, 'Pader', 'PAD', 1),
(3464, 219, 'Yumbe', 'YUM', 1),
(3465, 219, 'Bundibugyo', 'BUN', 1),
(3466, 219, 'Bushenyi', 'BSH', 1),
(3467, 219, 'Hoima', 'HOI', 1),
(3468, 219, 'Kabale', 'KBL', 1),
(3469, 219, 'Kabarole', 'KAR', 1),
(3470, 219, 'Kamwenge', 'KAM', 1),
(3471, 219, 'Kanungu', 'KAN', 1),
(3472, 219, 'Kasese', 'KAS', 1),
(3473, 219, 'Kibaale', 'KBA', 1),
(3474, 219, 'Kisoro', 'KIS', 1),
(3475, 219, 'Kyenjojo', 'KYE', 1),
(3476, 219, 'Masindi', 'MSN', 1),
(3477, 219, 'Mbarara', 'MBR', 1),
(3478, 219, 'Ntungamo', 'NTU', 1),
(3479, 219, 'Rukungiri', 'RUK', 1),
(3480, 220, 'Черкаська область', '71', 1),
(3481, 220, 'Чернігівська область', '74', 1),
(3482, 220, 'Чернівецька область', '77', 1),
(3483, 220, 'Крим', '43', 1),
(3484, 220, 'Дніпропетровська область', '12', 1),
(3485, 220, 'Донецька область', '14', 1),
(3486, 220, 'Івано-Франківська область', '26', 1),
(3487, 220, 'Херсонська область', '65', 1),
(3488, 220, 'Хмельницька область', '68', 1),
(3489, 220, 'Кіровоградська область', '35', 1),
(3490, 220, 'Київ', '30', 1),
(3491, 220, 'Київська область', '32', 1),
(3492, 220, 'Луганська область', '09', 1),
(3493, 220, 'Львівська область', '46', 1),
(3494, 220, 'Миколаївська область', '48', 1),
(3495, 220, 'Одеська область', '51', 1),
(3496, 220, 'Полтавська область', '53', 1),
(3497, 220, 'Рівненська область', '56', 1),
(3498, 220, 'Севастополь', '40', 1),
(3499, 220, 'Сумська область', '59', 1),
(3500, 220, 'Тернопільська область', '61', 1),
(3501, 220, 'Вінницька область', '05', 1),
(3502, 220, 'Волинська область', '07', 1),
(3503, 220, 'Закарпатська область', '21', 1),
(3504, 220, 'Запорізька область', '23', 1),
(3505, 220, 'Житомирська область', '18', 1),
(3506, 221, 'Abu Zaby', 'AZ', 1),
(3507, 221, '''Ajman', 'AJ', 1),
(3508, 221, 'Al Fujayrah', 'FU', 1),
(3509, 221, 'Ash Shāriqah', 'SH', 1),
(3510, 221, 'Dubai', 'DU', 1),
(3511, 221, 'Ra’s al Khaymah', 'RK', 1),
(3512, 221, 'Umm al Qaywayn', 'UQ', 1),
(3513, 222, 'Aberdeen', 'ABN', 1),
(3514, 222, 'Aberdeenshire', 'ABNS', 1),
(3515, 222, 'Anglesey', 'ANG', 1),
(3516, 222, 'Angus', 'AGS', 1),
(3517, 222, 'Argyll and Bute', 'ARY', 1),
(3518, 222, 'Bedfordshire', 'BEDS', 1),
(3519, 222, 'Berkshire', 'BERKS', 1),
(3520, 222, 'Blaenau Gwent', 'BLA', 1),
(3521, 222, 'Bridgend', 'BRI', 1),
(3522, 222, 'Bristol', 'BSTL', 1),
(3523, 222, 'Buckinghamshire', 'BUCKS', 1),
(3524, 222, 'Caerphilly', 'CAE', 1),
(3525, 222, 'Cambridgeshire', 'CAMBS', 1),
(3526, 222, 'Cardiff', 'CDF', 1),
(3527, 222, 'Carmarthenshire', 'CARM', 1),
(3528, 222, 'Ceredigion', 'CDGN', 1),
(3529, 222, 'Cheshire', 'CHES', 1),
(3530, 222, 'Clackmannanshire', 'CLACK', 1),
(3531, 222, 'Conwy', 'CON', 1),
(3532, 222, 'Cornwall', 'CORN', 1),
(3533, 222, 'Denbighshire', 'DNBG', 1),
(3534, 222, 'Derbyshire', 'DERBY', 1),
(3535, 222, 'Devon', 'DVN', 1),
(3536, 222, 'Dorset', 'DOR', 1),
(3537, 222, 'Dumfries and Galloway', 'DGL', 1),
(3538, 222, 'Dundee', 'DUND', 1),
(3539, 222, 'Durham', 'DHM', 1),
(3540, 222, 'East Ayrshire', 'ARYE', 1),
(3541, 222, 'East Dunbartonshire', 'DUNBE', 1),
(3542, 222, 'East Lothian', 'LOTE', 1),
(3543, 222, 'East Renfrewshire', 'RENE', 1),
(3544, 222, 'East Riding of Yorkshire', 'ERYS', 1),
(3545, 222, 'East Sussex', 'SXE', 1),
(3546, 222, 'Edinburgh', 'EDIN', 1),
(3547, 222, 'Essex', 'ESX', 1),
(3548, 222, 'Falkirk', 'FALK', 1),
(3549, 222, 'Fife', 'FFE', 1),
(3550, 222, 'Flintshire', 'FLINT', 1),
(3551, 222, 'Glasgow', 'GLAS', 1),
(3552, 222, 'Gloucestershire', 'GLOS', 1),
(3553, 222, 'Greater London', 'LDN', 1),
(3554, 222, 'Greater Manchester', 'MCH', 1),
(3555, 222, 'Gwynedd', 'GDD', 1),
(3556, 222, 'Hampshire', 'HANTS', 1),
(3557, 222, 'Herefordshire', 'HWR', 1),
(3558, 222, 'Hertfordshire', 'HERTS', 1),
(3559, 222, 'Highlands', 'HLD', 1),
(3560, 222, 'Inverclyde', 'IVER', 1),
(3561, 222, 'Isle of Wight', 'IOW', 1),
(3562, 222, 'Kent', 'KNT', 1),
(3563, 222, 'Lancashire', 'LANCS', 1),
(3564, 222, 'Leicestershire', 'LEICS', 1),
(3565, 222, 'Lincolnshire', 'LINCS', 1),
(3566, 222, 'Merseyside', 'MSY', 1),
(3567, 222, 'Merthyr Tydfil', 'MERT', 1),
(3568, 222, 'Midlothian', 'MLOT', 1),
(3569, 222, 'Monmouthshire', 'MMOUTH', 1),
(3570, 222, 'Moray', 'MORAY', 1),
(3571, 222, 'Neath Port Talbot', 'NPRTAL', 1),
(3572, 222, 'Newport', 'NEWPT', 1),
(3573, 222, 'Norfolk', 'NOR', 1),
(3574, 222, 'North Ayrshire', 'ARYN', 1),
(3575, 222, 'North Lanarkshire', 'LANN', 1),
(3576, 222, 'North Yorkshire', 'YSN', 1),
(3577, 222, 'Northamptonshire', 'NHM', 1),
(3578, 222, 'Northumberland', 'NLD', 1),
(3579, 222, 'Nottinghamshire', 'NOT', 1),
(3580, 222, 'Orkney Islands', 'ORK', 1),
(3581, 222, 'Oxfordshire', 'OFE', 1),
(3582, 222, 'Pembrokeshire', 'PEM', 1),
(3583, 222, 'Perth and Kinross', 'PERTH', 1),
(3584, 222, 'Powys', 'PWS', 1),
(3585, 222, 'Renfrewshire', 'REN', 1),
(3586, 222, 'Rhondda Cynon Taff', 'RHON', 1),
(3587, 222, 'Rutland', 'RUT', 1),
(3588, 222, 'Scottish Borders', 'BOR', 1),
(3589, 222, 'Shetland Islands', 'SHET', 1),
(3590, 222, 'Shropshire', 'SPE', 1),
(3591, 222, 'Somerset', 'SOM', 1),
(3592, 222, 'South Ayrshire', 'ARYS', 1),
(3593, 222, 'South Lanarkshire', 'LANS', 1),
(3594, 222, 'South Yorkshire', 'YSS', 1),
(3595, 222, 'Staffordshire', 'SFD', 1),
(3596, 222, 'Stirling', 'STIR', 1),
(3597, 222, 'Suffolk', 'SFK', 1),
(3598, 222, 'Surrey', 'SRY', 1),
(3599, 222, 'Swansea', 'SWAN', 1),
(3600, 222, 'Torfaen', 'TORF', 1),
(3601, 222, 'Tyne and Wear', 'TWR', 1),
(3602, 222, 'Vale of Glamorgan', 'VGLAM', 1),
(3603, 222, 'Warwickshire', 'WARKS', 1),
(3604, 222, 'West Dunbartonshire', 'WDUN', 1),
(3605, 222, 'West Lothian', 'WLOT', 1),
(3606, 222, 'West Midlands', 'WMD', 1),
(3607, 222, 'West Sussex', 'SXW', 1),
(3608, 222, 'West Yorkshire', 'YSW', 1),
(3609, 222, 'Western Isles', 'WIL', 1),
(3610, 222, 'Wiltshire', 'WLT', 1),
(3611, 222, 'Worcestershire', 'WORCS', 1),
(3612, 222, 'Wrexham', 'WRX', 1),
(3613, 223, 'Alabama', 'AL', 1),
(3614, 223, 'Alaska', 'AK', 1),
(3615, 223, 'American Samoa', 'AS', 1),
(3616, 223, 'Arizona', 'AZ', 1),
(3617, 223, 'Arkansas', 'AR', 1),
(3618, 223, 'Armed Forces Africa', 'AF', 1),
(3619, 223, 'Armed Forces Americas', 'AA', 1),
(3620, 223, 'Armed Forces Canada', 'AC', 1),
(3621, 223, 'Armed Forces Europe', 'AE', 1),
(3622, 223, 'Armed Forces Middle East', 'AM', 1),
(3623, 223, 'Armed Forces Pacific', 'AP', 1),
(3624, 223, 'California', 'CA', 1),
(3625, 223, 'Colorado', 'CO', 1),
(3626, 223, 'Connecticut', 'CT', 1),
(3627, 223, 'Delaware', 'DE', 1),
(3628, 223, 'District of Columbia', 'DC', 1),
(3629, 223, 'Federated States Of Micronesia', 'FM', 1),
(3630, 223, 'Florida', 'FL', 1),
(3631, 223, 'Georgia', 'GA', 1),
(3632, 223, 'Guam', 'GU', 1),
(3633, 223, 'Hawaii', 'HI', 1),
(3634, 223, 'Idaho', 'ID', 1),
(3635, 223, 'Illinois', 'IL', 1),
(3636, 223, 'Indiana', 'IN', 1),
(3637, 223, 'Iowa', 'IA', 1),
(3638, 223, 'Kansas', 'KS', 1),
(3639, 223, 'Kentucky', 'KY', 1),
(3640, 223, 'Louisiana', 'LA', 1),
(3641, 223, 'Maine', 'ME', 1),
(3642, 223, 'Marshall Islands', 'MH', 1),
(3643, 223, 'Maryland', 'MD', 1),
(3644, 223, 'Massachusetts', 'MA', 1),
(3645, 223, 'Michigan', 'MI', 1),
(3646, 223, 'Minnesota', 'MN', 1),
(3647, 223, 'Mississippi', 'MS', 1),
(3648, 223, 'Missouri', 'MO', 1),
(3649, 223, 'Montana', 'MT', 1),
(3650, 223, 'Nebraska', 'NE', 1),
(3651, 223, 'Nevada', 'NV', 1),
(3652, 223, 'New Hampshire', 'NH', 1),
(3653, 223, 'New Jersey', 'NJ', 1),
(3654, 223, 'New Mexico', 'NM', 1),
(3655, 223, 'New York', 'NY', 1),
(3656, 223, 'North Carolina', 'NC', 1),
(3657, 223, 'North Dakota', 'ND', 1),
(3658, 223, 'Northern Mariana Islands', 'MP', 1),
(3659, 223, 'Ohio', 'OH', 1),
(3660, 223, 'Oklahoma', 'OK', 1),
(3661, 223, 'Oregon', 'OR', 1),
(3662, 223, 'Palau', 'PW', 1),
(3663, 223, 'Pennsylvania', 'PA', 1),
(3664, 223, 'Puerto Rico', 'PR', 1),
(3665, 223, 'Rhode Island', 'RI', 1),
(3666, 223, 'South Carolina', 'SC', 1),
(3667, 223, 'South Dakota', 'SD', 1),
(3668, 223, 'Tennessee', 'TN', 1),
(3669, 223, 'Texas', 'TX', 1),
(3670, 223, 'Utah', 'UT', 1),
(3671, 223, 'Vermont', 'VT', 1),
(3672, 223, 'Virgin Islands', 'VI', 1),
(3673, 223, 'Virginia', 'VA', 1),
(3674, 223, 'Washington', 'WA', 1),
(3675, 223, 'West Virginia', 'WV', 1),
(3676, 223, 'Wisconsin', 'WI', 1),
(3677, 223, 'Wyoming', 'WY', 1),
(3678, 224, 'Baker Island', 'BI', 1),
(3679, 224, 'Howland Island', 'HI', 1),
(3680, 224, 'Jarvis Island', 'JI', 1),
(3681, 224, 'Johnston Atoll', 'JA', 1),
(3682, 224, 'Kingman Reef', 'KR', 1),
(3683, 224, 'Midway Atoll', 'MA', 1),
(3684, 224, 'Navassa Island', 'NI', 1),
(3685, 224, 'Palmyra Atoll', 'PA', 1),
(3686, 224, 'Wake Island', 'WI', 1),
(3687, 225, 'Artigas', 'AR', 1),
(3688, 225, 'Canelones', 'CA', 1),
(3689, 225, 'Cerro Largo', 'CL', 1),
(3690, 225, 'Colonia', 'CO', 1),
(3691, 225, 'Durazno', 'DU', 1),
(3692, 225, 'Flores', 'FS', 1),
(3693, 225, 'Florida', 'FA', 1),
(3694, 225, 'Lavalleja', 'LA', 1),
(3695, 225, 'Maldonado', 'MA', 1),
(3696, 225, 'Montevideo', 'MO', 1),
(3697, 225, 'Paysandu', 'PA', 1),
(3698, 225, 'Rio Negro', 'RN', 1),
(3699, 225, 'Rivera', 'RV', 1),
(3700, 225, 'Rocha', 'RO', 1),
(3701, 225, 'Salto', 'SL', 1),
(3702, 225, 'San Jose', 'SJ', 1),
(3703, 225, 'Soriano', 'SO', 1),
(3704, 225, 'Tacuarembo', 'TA', 1),
(3705, 225, 'Treinta y Tres', 'TT', 1),
(3706, 226, 'Andijon', 'AN', 1),
(3707, 226, 'Buxoro', 'BU', 1),
(3708, 226, 'Farg''ona', 'FA', 1),
(3709, 226, 'Jizzax', 'JI', 1),
(3710, 226, 'Namangan', 'NG', 1),
(3711, 226, 'Navoiy', 'NW', 1),
(3712, 226, 'Qashqadaryo', 'QA', 1),
(3713, 226, 'Qoraqalpog''iston Republikasi', 'QR', 1),
(3714, 226, 'Samarqand', 'SA', 1),
(3715, 226, 'Sirdaryo', 'SI', 1),
(3716, 226, 'Surxondaryo', 'SU', 1),
(3717, 226, 'Toshkent City', 'TK', 1),
(3718, 226, 'Toshkent Region', 'TO', 1),
(3719, 226, 'Xorazm', 'XO', 1),
(3720, 227, 'Malampa', 'MA', 1),
(3721, 227, 'Penama', 'PE', 1),
(3722, 227, 'Sanma', 'SA', 1),
(3723, 227, 'Shefa', 'SH', 1),
(3724, 227, 'Tafea', 'TA', 1),
(3725, 227, 'Torba', 'TO', 1),
(3726, 229, 'Amazonas', 'AM', 1),
(3727, 229, 'Anzoategui', 'AN', 1),
(3728, 229, 'Apure', 'AP', 1),
(3729, 229, 'Aragua', 'AR', 1),
(3730, 229, 'Barinas', 'BA', 1),
(3731, 229, 'Bolivar', 'BO', 1),
(3732, 229, 'Carabobo', 'CA', 1),
(3733, 229, 'Cojedes', 'CO', 1),
(3734, 229, 'Delta Amacuro', 'DA', 1),
(3735, 229, 'Dependencias Federales', 'DF', 1),
(3736, 229, 'Distrito Federal', 'DI', 1),
(3737, 229, 'Falcon', 'FA', 1),
(3738, 229, 'Guarico', 'GU', 1),
(3739, 229, 'Lara', 'LA', 1),
(3740, 229, 'Merida', 'ME', 1),
(3741, 229, 'Miranda', 'MI', 1),
(3742, 229, 'Monagas', 'MO', 1),
(3743, 229, 'Nueva Esparta', 'NE', 1),
(3744, 229, 'Portuguesa', 'PO', 1),
(3745, 229, 'Sucre', 'SU', 1),
(3746, 229, 'Tachira', 'TA', 1),
(3747, 229, 'Trujillo', 'TR', 1),
(3748, 229, 'Vargas', 'VA', 1),
(3749, 229, 'Yaracuy', 'YA', 1),
(3750, 229, 'Zulia', 'ZU', 1),
(3751, 230, 'An Giang', 'AG', 1),
(3752, 230, 'Bac Giang', 'BG', 1),
(3753, 230, 'Bac Kan', 'BK', 1),
(3754, 230, 'Bac Lieu', 'BL', 1),
(3755, 230, 'Bac Ninh', 'BC', 1),
(3756, 230, 'Ba Ria-Vung Tau', 'BR', 1),
(3757, 230, 'Ben Tre', 'BN', 1),
(3758, 230, 'Binh Dinh', 'BH', 1),
(3759, 230, 'Binh Duong', 'BU', 1),
(3760, 230, 'Binh Phuoc', 'BP', 1),
(3761, 230, 'Binh Thuan', 'BT', 1),
(3762, 230, 'Ca Mau', 'CM', 1),
(3763, 230, 'Can Tho', 'CT', 1),
(3764, 230, 'Cao Bang', 'CB', 1),
(3765, 230, 'Dak Lak', 'DL', 1),
(3766, 230, 'Dak Nong', 'DG', 1),
(3767, 230, 'Da Nang', 'DN', 1),
(3768, 230, 'Dien Bien', 'DB', 1),
(3769, 230, 'Dong Nai', 'DI', 1),
(3770, 230, 'Dong Thap', 'DT', 1),
(3771, 230, 'Gia Lai', 'GL', 1),
(3772, 230, 'Ha Giang', 'HG', 1),
(3773, 230, 'Hai Duong', 'HD', 1),
(3774, 230, 'Hai Phong', 'HP', 1),
(3775, 230, 'Ha Nam', 'HM', 1),
(3776, 230, 'Ha Noi', 'HI', 1),
(3777, 230, 'Ha Tay', 'HT', 1),
(3778, 230, 'Ha Tinh', 'HH', 1),
(3779, 230, 'Hoa Binh', 'HB', 1),
(3780, 230, 'Ho Chi Minh City', 'HC', 1),
(3781, 230, 'Hau Giang', 'HU', 1),
(3782, 230, 'Hung Yen', 'HY', 1),
(3783, 232, 'Saint Croix', 'C', 1),
(3784, 232, 'Saint John', 'J', 1),
(3785, 232, 'Saint Thomas', 'T', 1),
(3786, 233, 'Alo', 'A', 1),
(3787, 233, 'Sigave', 'S', 1),
(3788, 233, 'Wallis', 'W', 1),
(3789, 235, 'Abyan', 'AB', 1),
(3790, 235, 'Adan', 'AD', 1),
(3791, 235, 'Amran', 'AM', 1),
(3792, 235, 'Al Bayda', 'BA', 1),
(3793, 235, 'Ad Dali', 'DA', 1),
(3794, 235, 'Dhamar', 'DH', 1),
(3795, 235, 'Hadramawt', 'HD', 1),
(3796, 235, 'Hajjah', 'HJ', 1),
(3797, 235, 'Al Hudaydah', 'HU', 1),
(3798, 235, 'Ibb', 'IB', 1),
(3799, 235, 'Al Jawf', 'JA', 1),
(3800, 235, 'Lahij', 'LA', 1),
(3801, 235, 'Ma''rib', 'MA', 1),
(3802, 235, 'Al Mahrah', 'MR', 1),
(3803, 235, 'Al Mahwit', 'MW', 1),
(3804, 235, 'Sa''dah', 'SD', 1),
(3805, 235, 'San''a', 'SN', 1),
(3806, 235, 'Shabwah', 'SH', 1),
(3807, 235, 'Ta''izz', 'TA', 1),
(3812, 237, 'Bas-Congo', 'BC', 1),
(3813, 237, 'Bandundu', 'BN', 1),
(3814, 237, 'Equateur', 'EQ', 1),
(3815, 237, 'Katanga', 'KA', 1),
(3816, 237, 'Kasai-Oriental', 'KE', 1),
(3817, 237, 'Kinshasa', 'KN', 1),
(3818, 237, 'Kasai-Occidental', 'KW', 1),
(3819, 237, 'Maniema', 'MA', 1),
(3820, 237, 'Nord-Kivu', 'NK', 1),
(3821, 237, 'Orientale', 'OR', 1),
(3822, 237, 'Sud-Kivu', 'SK', 1),
(3823, 238, 'Central', 'CE', 1),
(3824, 238, 'Copperbelt', 'CB', 1),
(3825, 238, 'Eastern', 'EA', 1),
(3826, 238, 'Luapula', 'LP', 1),
(3827, 238, 'Lusaka', 'LK', 1),
(3828, 238, 'Northern', 'NO', 1),
(3829, 238, 'North-Western', 'NW', 1),
(3830, 238, 'Southern', 'SO', 1),
(3831, 238, 'Western', 'WE', 1),
(3832, 239, 'Bulawayo', 'BU', 1),
(3833, 239, 'Harare', 'HA', 1),
(3834, 239, 'Manicaland', 'ML', 1),
(3835, 239, 'Mashonaland Central', 'MC', 1),
(3836, 239, 'Mashonaland East', 'ME', 1),
(3837, 239, 'Mashonaland West', 'MW', 1),
(3838, 239, 'Masvingo', 'MV', 1),
(3839, 239, 'Matabeleland North', 'MN', 1),
(3840, 239, 'Matabeleland South', 'MS', 1),
(3841, 239, 'Midlands', 'MD', 1),
(3861, 105, 'Campobasso', 'CB', 1),
(3862, 105, 'Carbonia-Iglesias', 'CI', 1),
(3863, 105, 'Caserta', 'CE', 1),
(3864, 105, 'Catania', 'CT', 1),
(3865, 105, 'Catanzaro', 'CZ', 1),
(3866, 105, 'Chieti', 'CH', 1),
(3867, 105, 'Como', 'CO', 1),
(3868, 105, 'Cosenza', 'CS', 1),
(3869, 105, 'Cremona', 'CR', 1),
(3870, 105, 'Crotone', 'KR', 1),
(3871, 105, 'Cuneo', 'CN', 1),
(3872, 105, 'Enna', 'EN', 1),
(3873, 105, 'Ferrara', 'FE', 1),
(3874, 105, 'Firenze', 'FI', 1),
(3875, 105, 'Foggia', 'FG', 1),
(3876, 105, 'Forli-Cesena', 'FC', 1),
(3877, 105, 'Frosinone', 'FR', 1),
(3878, 105, 'Genova', 'GE', 1),
(3879, 105, 'Gorizia', 'GO', 1),
(3880, 105, 'Grosseto', 'GR', 1),
(3881, 105, 'Imperia', 'IM', 1),
(3882, 105, 'Isernia', 'IS', 1),
(3883, 105, 'L&#39;Aquila', 'AQ', 1),
(3884, 105, 'La Spezia', 'SP', 1),
(3885, 105, 'Latina', 'LT', 1),
(3886, 105, 'Lecce', 'LE', 1),
(3887, 105, 'Lecco', 'LC', 1),
(3888, 105, 'Livorno', 'LI', 1),
(3889, 105, 'Lodi', 'LO', 1),
(3890, 105, 'Lucca', 'LU', 1),
(3891, 105, 'Macerata', 'MC', 1),
(3892, 105, 'Mantova', 'MN', 1),
(3893, 105, 'Massa-Carrara', 'MS', 1),
(3894, 105, 'Matera', 'MT', 1),
(3895, 105, 'Medio Campidano', 'VS', 1),
(3896, 105, 'Messina', 'ME', 1),
(3897, 105, 'Milano', 'MI', 1),
(3898, 105, 'Modena', 'MO', 1),
(3899, 105, 'Napoli', 'NA', 1),
(3900, 105, 'Novara', 'NO', 1),
(3901, 105, 'Nuoro', 'NU', 1),
(3902, 105, 'Ogliastra', 'OG', 1),
(3903, 105, 'Olbia-Tempio', 'OT', 1),
(3904, 105, 'Oristano', 'OR', 1),
(3905, 105, 'Padova', 'PD', 1),
(3906, 105, 'Palermo', 'PA', 1),
(3907, 105, 'Parma', 'PR', 1),
(3908, 105, 'Pavia', 'PV', 1),
(3909, 105, 'Perugia', 'PG', 1),
(3910, 105, 'Pesaro e Urbino', 'PU', 1),
(3911, 105, 'Pescara', 'PE', 1),
(3912, 105, 'Piacenza', 'PC', 1),
(3913, 105, 'Pisa', 'PI', 1),
(3914, 105, 'Pistoia', 'PT', 1),
(3915, 105, 'Pordenone', 'PN', 1),
(3916, 105, 'Potenza', 'PZ', 1),
(3917, 105, 'Prato', 'PO', 1),
(3918, 105, 'Ragusa', 'RG', 1),
(3919, 105, 'Ravenna', 'RA', 1),
(3920, 105, 'Reggio Calabria', 'RC', 1),
(3921, 105, 'Reggio Emilia', 'RE', 1),
(3922, 105, 'Rieti', 'RI', 1),
(3923, 105, 'Rimini', 'RN', 1),
(3924, 105, 'Roma', 'RM', 1),
(3925, 105, 'Rovigo', 'RO', 1),
(3926, 105, 'Salerno', 'SA', 1),
(3927, 105, 'Sassari', 'SS', 1),
(3928, 105, 'Savona', 'SV', 1),
(3929, 105, 'Siena', 'SI', 1),
(3930, 105, 'Siracusa', 'SR', 1),
(3931, 105, 'Sondrio', 'SO', 1),
(3932, 105, 'Taranto', 'TA', 1),
(3933, 105, 'Teramo', 'TE', 1),
(3934, 105, 'Terni', 'TR', 1),
(3935, 105, 'Torino', 'TO', 1),
(3936, 105, 'Trapani', 'TP', 1),
(3937, 105, 'Trento', 'TN', 1),
(3938, 105, 'Treviso', 'TV', 1),
(3939, 105, 'Trieste', 'TS', 1),
(3940, 105, 'Udine', 'UD', 1),
(3941, 105, 'Varese', 'VA', 1),
(3942, 105, 'Venezia', 'VE', 1),
(3943, 105, 'Verbano-Cusio-Ossola', 'VB', 1),
(3944, 105, 'Vercelli', 'VC', 1),
(3945, 105, 'Verona', 'VR', 1),
(3946, 105, 'Vibo Valentia', 'VV', 1),
(3947, 105, 'Vicenza', 'VI', 1),
(3948, 105, 'Viterbo', 'VT', 1),
(3949, 222, 'County Antrim', 'ANT', 1),
(3950, 222, 'County Armagh', 'ARM', 1),
(3951, 222, 'County Down', 'DOW', 1),
(3952, 222, 'County Fermanagh', 'FER', 1),
(3953, 222, 'County Londonderry', 'LDY', 1),
(3954, 222, 'County Tyrone', 'TYR', 1),
(3955, 222, 'Cumbria', 'CMA', 1),
(3956, 190, 'Pomurska', '1', 1),
(3957, 190, 'Podravska', '2', 1),
(3958, 190, 'Koroška', '3', 1),
(3959, 190, 'Savinjska', '4', 1),
(3960, 190, 'Zasavska', '5', 1),
(3961, 190, 'Spodnjeposavska', '6', 1),
(3962, 190, 'Jugovzhodna Slovenija', '7', 1),
(3963, 190, 'Osrednjeslovenska', '8', 1),
(3964, 190, 'Gorenjska', '9', 1),
(3965, 190, 'Notranjsko-kraška', '10', 1),
(3966, 190, 'Goriška', '11', 1),
(3967, 190, 'Obalno-kraška', '12', 1),
(3968, 33, 'Ruse', '', 1),
(3969, 101, 'Alborz', 'ALB', 1),
(3970, 21, 'Brussels-Capital Region', 'BRU', 1),
(3971, 138, 'Aguascalientes', 'AG', 1),
(3973, 242, 'Andrijevica', '01', 1),
(3974, 242, 'Bar', '02', 1),
(3975, 242, 'Berane', '03', 1),
(3976, 242, 'Bijelo Polje', '04', 1),
(3977, 242, 'Budva', '05', 1),
(3978, 242, 'Cetinje', '06', 1),
(3979, 242, 'Danilovgrad', '07', 1),
(3980, 242, 'Herceg-Novi', '08', 1),
(3981, 242, 'Kolašin', '09', 1),
(3982, 242, 'Kotor', '10', 1),
(3983, 242, 'Mojkovac', '11', 1),
(3984, 242, 'Nikšić', '12', 1),
(3985, 242, 'Plav', '13', 1),
(3986, 242, 'Pljevlja', '14', 1),
(3987, 242, 'Plužine', '15', 1),
(3988, 242, 'Podgorica', '16', 1),
(3989, 242, 'Rožaje', '17', 1),
(3990, 242, 'Šavnik', '18', 1),
(3991, 242, 'Tivat', '19', 1),
(3992, 242, 'Ulcinj', '20', 1),
(3993, 242, 'Žabljak', '21', 1),
(3994, 243, 'Belgrade', '00', 1),
(3995, 243, 'North Bačka', '01', 1),
(3996, 243, 'Central Banat', '02', 1),
(3997, 243, 'North Banat', '03', 1),
(3998, 243, 'South Banat', '04', 1),
(3999, 243, 'West Bačka', '05', 1),
(4000, 243, 'South Bačka', '06', 1),
(4001, 243, 'Srem', '07', 1),
(4002, 243, 'Mačva', '08', 1),
(4003, 243, 'Kolubara', '09', 1),
(4004, 243, 'Podunavlje', '10', 1),
(4005, 243, 'Braničevo', '11', 1),
(4006, 243, 'Šumadija', '12', 1),
(4007, 243, 'Pomoravlje', '13', 1),
(4008, 243, 'Bor', '14', 1),
(4009, 243, 'Zaječar', '15', 1),
(4010, 243, 'Zlatibor', '16', 1),
(4011, 243, 'Moravica', '17', 1),
(4012, 243, 'Raška', '18', 1),
(4013, 243, 'Rasina', '19', 1),
(4014, 243, 'Nišava', '20', 1),
(4015, 243, 'Toplica', '21', 1),
(4016, 243, 'Pirot', '22', 1),
(4017, 243, 'Jablanica', '23', 1),
(4018, 243, 'Pčinja', '24', 1),
(4020, 245, 'Bonaire', 'BO', 1),
(4021, 245, 'Saba', 'SA', 1),
(4022, 245, 'Sint Eustatius', 'SE', 1),
(4023, 248, 'Central Equatoria', 'EC', 1),
(4024, 248, 'Eastern Equatoria', 'EE', 1),
(4025, 248, 'Jonglei', 'JG', 1),
(4026, 248, 'Lakes', 'LK', 1),
(4027, 248, 'Northern Bahr el-Ghazal', 'BN', 1),
(4028, 248, 'Unity', 'UY', 1),
(4029, 248, 'Upper Nile', 'NU', 1),
(4030, 248, 'Warrap', 'WR', 1),
(4031, 248, 'Western Bahr el-Ghazal', 'BW', 1),
(4032, 248, 'Western Equatoria', 'EW', 1),
(4036, 117, 'Ainaži, Salacgrīvas novads', '0661405', 1),
(4037, 117, 'Aizkraukle, Aizkraukles novads', '0320201', 1),
(4038, 117, 'Aizkraukles novads', '0320200', 1),
(4039, 117, 'Aizpute, Aizputes novads', '0640605', 1),
(4040, 117, 'Aizputes novads', '0640600', 1),
(4041, 117, 'Aknīste, Aknīstes novads', '0560805', 1),
(4042, 117, 'Aknīstes novads', '0560800', 1),
(4043, 117, 'Aloja, Alojas novads', '0661007', 1),
(4044, 117, 'Alojas novads', '0661000', 1),
(4045, 117, 'Alsungas novads', '0624200', 1),
(4046, 117, 'Alūksne, Alūksnes novads', '0360201', 1),
(4047, 117, 'Alūksnes novads', '0360200', 1),
(4048, 117, 'Amatas novads', '0424701', 1),
(4049, 117, 'Ape, Apes novads', '0360805', 1),
(4050, 117, 'Apes novads', '0360800', 1),
(4051, 117, 'Auce, Auces novads', '0460805', 1),
(4052, 117, 'Auces novads', '0460800', 1),
(4053, 117, 'Ādažu novads', '0804400', 1),
(4054, 117, 'Babītes novads', '0804900', 1),
(4055, 117, 'Baldone, Baldones novads', '0800605', 1),
(4056, 117, 'Baldones novads', '0800600', 1),
(4057, 117, 'Baloži, Ķekavas novads', '0800807', 1),
(4058, 117, 'Baltinavas novads', '0384400', 1),
(4059, 117, 'Balvi, Balvu novads', '0380201', 1),
(4060, 117, 'Balvu novads', '0380200', 1),
(4061, 117, 'Bauska, Bauskas novads', '0400201', 1),
(4062, 117, 'Bauskas novads', '0400200', 1),
(4063, 117, 'Beverīnas novads', '0964700', 1),
(4064, 117, 'Brocēni, Brocēnu novads', '0840605', 1),
(4065, 117, 'Brocēnu novads', '0840601', 1),
(4066, 117, 'Burtnieku novads', '0967101', 1),
(4067, 117, 'Carnikavas novads', '0805200', 1),
(4068, 117, 'Cesvaine, Cesvaines novads', '0700807', 1),
(4069, 117, 'Cesvaines novads', '0700800', 1),
(4070, 117, 'Cēsis, Cēsu novads', '0420201', 1),
(4071, 117, 'Cēsu novads', '0420200', 1),
(4072, 117, 'Ciblas novads', '0684901', 1),
(4073, 117, 'Dagda, Dagdas novads', '0601009', 1),
(4074, 117, 'Dagdas novads', '0601000', 1),
(4075, 117, 'Daugavpils', '0050000', 1),
(4076, 117, 'Daugavpils novads', '0440200', 1),
(4077, 117, 'Dobele, Dobeles novads', '0460201', 1),
(4078, 117, 'Dobeles novads', '0460200', 1),
(4079, 117, 'Dundagas novads', '0885100', 1),
(4080, 117, 'Durbe, Durbes novads', '0640807', 1),
(4081, 117, 'Durbes novads', '0640801', 1),
(4082, 117, 'Engures novads', '0905100', 1),
(4083, 117, 'Ērgļu novads', '0705500', 1),
(4084, 117, 'Garkalnes novads', '0806000', 1),
(4085, 117, 'Grobiņa, Grobiņas novads', '0641009', 1),
(4086, 117, 'Grobiņas novads', '0641000', 1),
(4087, 117, 'Gulbene, Gulbenes novads', '0500201', 1),
(4088, 117, 'Gulbenes novads', '0500200', 1),
(4089, 117, 'Iecavas novads', '0406400', 1),
(4090, 117, 'Ikšķile, Ikšķiles novads', '0740605', 1),
(4091, 117, 'Ikšķiles novads', '0740600', 1),
(4092, 117, 'Ilūkste, Ilūkstes novads', '0440807', 1),
(4093, 117, 'Ilūkstes novads', '0440801', 1),
(4094, 117, 'Inčukalna novads', '0801800', 1),
(4095, 117, 'Jaunjelgava, Jaunjelgavas novads', '0321007', 1),
(4096, 117, 'Jaunjelgavas novads', '0321000', 1),
(4097, 117, 'Jaunpiebalgas novads', '0425700', 1),
(4098, 117, 'Jaunpils novads', '0905700', 1),
(4099, 117, 'Jelgava', '0090000', 1),
(4100, 117, 'Jelgavas novads', '0540200', 1),
(4101, 117, 'Jēkabpils', '0110000', 1),
(4102, 117, 'Jēkabpils novads', '0560200', 1),
(4103, 117, 'Jūrmala', '0130000', 1),
(4104, 117, 'Kalnciems, Jelgavas novads', '0540211', 1),
(4105, 117, 'Kandava, Kandavas novads', '0901211', 1),
(4106, 117, 'Kandavas novads', '0901201', 1),
(4107, 117, 'Kārsava, Kārsavas novads', '0681009', 1),
(4108, 117, 'Kārsavas novads', '0681000', 1),
(4109, 117, 'Kocēnu novads ,bij. Valmieras)', '0960200', 1),
(4110, 117, 'Kokneses novads', '0326100', 1),
(4111, 117, 'Krāslava, Krāslavas novads', '0600201', 1),
(4112, 117, 'Krāslavas novads', '0600202', 1),
(4113, 117, 'Krimuldas novads', '0806900', 1),
(4114, 117, 'Krustpils novads', '0566900', 1),
(4115, 117, 'Kuldīga, Kuldīgas novads', '0620201', 1),
(4116, 117, 'Kuldīgas novads', '0620200', 1),
(4117, 117, 'Ķeguma novads', '0741001', 1),
(4118, 117, 'Ķegums, Ķeguma novads', '0741009', 1),
(4119, 117, 'Ķekavas novads', '0800800', 1),
(4120, 117, 'Lielvārde, Lielvārdes novads', '0741413', 1),
(4121, 117, 'Lielvārdes novads', '0741401', 1),
(4122, 117, 'Liepāja', '0170000', 1),
(4123, 117, 'Limbaži, Limbažu novads', '0660201', 1),
(4124, 117, 'Limbažu novads', '0660200', 1),
(4125, 117, 'Līgatne, Līgatnes novads', '0421211', 1),
(4126, 117, 'Līgatnes novads', '0421200', 1),
(4127, 117, 'Līvāni, Līvānu novads', '0761211', 1),
(4128, 117, 'Līvānu novads', '0761201', 1),
(4129, 117, 'Lubāna, Lubānas novads', '0701413', 1),
(4130, 117, 'Lubānas novads', '0701400', 1),
(4131, 117, 'Ludza, Ludzas novads', '0680201', 1),
(4132, 117, 'Ludzas novads', '0680200', 1),
(4133, 117, 'Madona, Madonas novads', '0700201', 1),
(4134, 117, 'Madonas novads', '0700200', 1),
(4135, 117, 'Mazsalaca, Mazsalacas novads', '0961011', 1),
(4136, 117, 'Mazsalacas novads', '0961000', 1),
(4137, 117, 'Mālpils novads', '0807400', 1),
(4138, 117, 'Mārupes novads', '0807600', 1),
(4139, 117, 'Mērsraga novads', '0887600', 1),
(4140, 117, 'Naukšēnu novads', '0967300', 1),
(4141, 117, 'Neretas novads', '0327100', 1),
(4142, 117, 'Nīcas novads', '0647900', 1),
(4143, 117, 'Ogre, Ogres novads', '0740201', 1),
(4144, 117, 'Ogres novads', '0740202', 1),
(4145, 117, 'Olaine, Olaines novads', '0801009', 1),
(4146, 117, 'Olaines novads', '0801000', 1),
(4147, 117, 'Ozolnieku novads', '0546701', 1),
(4148, 117, 'Pārgaujas novads', '0427500', 1),
(4149, 117, 'Pāvilosta, Pāvilostas novads', '0641413', 1),
(4150, 117, 'Pāvilostas novads', '0641401', 1),
(4151, 117, 'Piltene, Ventspils novads', '0980213', 1),
(4152, 117, 'Pļaviņas, Pļaviņu novads', '0321413', 1),
(4153, 117, 'Pļaviņu novads', '0321400', 1),
(4154, 117, 'Preiļi, Preiļu novads', '0760201', 1),
(4155, 117, 'Preiļu novads', '0760202', 1),
(4156, 117, 'Priekule, Priekules novads', '0641615', 1),
(4157, 117, 'Priekules novads', '0641600', 1),
(4158, 117, 'Priekuļu novads', '0427300', 1),
(4159, 117, 'Raunas novads', '0427700', 1),
(4160, 117, 'Rēzekne', '0210000', 1),
(4161, 117, 'Rēzeknes novads', '0780200', 1),
(4162, 117, 'Riebiņu novads', '0766300', 1),
(4163, 117, 'Rīga', '0010000', 1),
(4164, 117, 'Rojas novads', '0888300', 1),
(4165, 117, 'Ropažu novads', '0808400', 1),
(4166, 117, 'Rucavas novads', '0648500', 1),
(4167, 117, 'Rugāju novads', '0387500', 1),
(4168, 117, 'Rundāles novads', '0407700', 1),
(4169, 117, 'Rūjiena, Rūjienas novads', '0961615', 1),
(4170, 117, 'Rūjienas novads', '0961600', 1),
(4171, 117, 'Sabile, Talsu novads', '0880213', 1),
(4172, 117, 'Salacgrīva, Salacgrīvas novads', '0661415', 1),
(4173, 117, 'Salacgrīvas novads', '0661400', 1),
(4174, 117, 'Salas novads', '0568700', 1),
(4175, 117, 'Salaspils novads', '0801200', 1),
(4176, 117, 'Salaspils, Salaspils novads', '0801211', 1),
(4177, 117, 'Saldus novads', '0840200', 1),
(4178, 117, 'Saldus, Saldus novads', '0840201', 1),
(4179, 117, 'Saulkrasti, Saulkrastu novads', '0801413', 1),
(4180, 117, 'Saulkrastu novads', '0801400', 1),
(4181, 117, 'Seda, Strenču novads', '0941813', 1),
(4182, 117, 'Sējas novads', '0809200', 1),
(4183, 117, 'Sigulda, Siguldas novads', '0801615', 1),
(4184, 117, 'Siguldas novads', '0801601', 1),
(4185, 117, 'Skrīveru novads', '0328200', 1),
(4186, 117, 'Skrunda, Skrundas novads', '0621209', 1),
(4187, 117, 'Skrundas novads', '0621200', 1),
(4188, 117, 'Smiltene, Smiltenes novads', '0941615', 1),
(4189, 117, 'Smiltenes novads', '0941600', 1),
(4190, 117, 'Staicele, Alojas novads', '0661017', 1),
(4191, 117, 'Stende, Talsu novads', '0880215', 1),
(4192, 117, 'Stopiņu novads', '0809600', 1),
(4193, 117, 'Strenči, Strenču novads', '0941817', 1),
(4194, 117, 'Strenču novads', '0941800', 1),
(4195, 117, 'Subate, Ilūkstes novads', '0440815', 1),
(4196, 117, 'Talsi, Talsu novads', '0880201', 1),
(4197, 117, 'Talsu novads', '0880200', 1),
(4198, 117, 'Tērvetes novads', '0468900', 1),
(4199, 117, 'Tukuma novads', '0900200', 1),
(4200, 117, 'Tukums, Tukuma novads', '0900201', 1),
(4201, 117, 'Vaiņodes novads', '0649300', 1),
(4202, 117, 'Valdemārpils, Talsu novads', '0880217', 1),
(4203, 117, 'Valka, Valkas novads', '0940201', 1),
(4204, 117, 'Valkas novads', '0940200', 1),
(4205, 117, 'Valmiera', '0250000', 1),
(4206, 117, 'Vangaži, Inčukalna novads', '0801817', 1),
(4207, 117, 'Varakļāni, Varakļānu novads', '0701817', 1),
(4208, 117, 'Varakļānu novads', '0701800', 1),
(4209, 117, 'Vārkavas novads', '0769101', 1),
(4210, 117, 'Vecpiebalgas novads', '0429300', 1),
(4211, 117, 'Vecumnieku novads', '0409500', 1),
(4212, 117, 'Ventspils', '0270000', 1),
(4213, 117, 'Ventspils novads', '0980200', 1),
(4214, 117, 'Viesīte, Viesītes novads', '0561815', 1),
(4215, 117, 'Viesītes novads', '0561800', 1),
(4216, 117, 'Viļaka, Viļakas novads', '0381615', 1),
(4217, 117, 'Viļakas novads', '0381600', 1),
(4218, 117, 'Viļāni, Viļānu novads', '0781817', 1),
(4219, 117, 'Viļānu novads', '0781800', 1),
(4220, 117, 'Zilupe, Zilupes novads', '0681817', 1),
(4221, 117, 'Zilupes novads', '0681801', 1),
(4222, 43, 'Arica y Parinacota', 'AP', 1),
(4223, 43, 'Los Rios', 'LR', 1),
(4224, 220, 'Харківська область', '63', 1),
(4225, 118, 'Beirut', 'LB-BR', 1),
(4226, 118, 'Bekaa', 'LB-BE', 1),
(4227, 118, 'Mount Lebanon', 'LB-ML', 1),
(4228, 118, 'Nabatieh', 'LB-NB', 1),
(4229, 118, 'North', 'LB-NR', 1),
(4230, 118, 'South', 'LB-ST', 1),
(4231, 99, 'Telangana', 'TS', 1),
(4232, 44, 'Qinghai', 'QH', 1),
(4233, 100, 'Papua Barat', 'PB', 1),
(4234, 100, 'Sulawesi Barat', 'SR', 1),
(4235, 100, 'Kepulauan Riau', 'KR', 1),
(4236, 105, 'Barletta-Andria-Trani', 'BT', 1),
(4237, 105, 'Fermo', 'FM', 1),
(4238, 105, 'Monza Brianza', 'MB', 1);

-- --------------------------------------------------------
--
-- Table structure for table `oc_zone_to_geo_zone`
--

DROP TABLE IF EXISTS `oc_zone_to_geo_zone`;
CREATE TABLE `oc_zone_to_geo_zone` (
  `zone_to_geo_zone_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `zone_id` int(11) NOT NULL DEFAULT '0',
  `geo_zone_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`zone_to_geo_zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_zone_to_geo_zone`
--

INSERT INTO `oc_zone_to_geo_zone` (`zone_to_geo_zone_id`, `country_id`, `zone_id`, `geo_zone_id`, `date_added`, `date_modified`) VALUES
(1, 220, 0, 3, '2026-09-01 10:00:00', '2026-09-01 10:00:00'),
(2, 220, 0, 4, '2026-09-01 10:00:00', '2026-09-01 10:00:00');

--
-- Database: `blog`
--

-- --------------------------------------------------------

--
-- Table structure for table `oc_blog_category`
--

DROP TABLE IF EXISTS `oc_blog_category`;
CREATE TABLE `oc_blog_category` (
`blog_category_id` int(11) NOT NULL AUTO_INCREMENT,
`image` varchar(255) DEFAULT NULL,
`parent_id` int(11) NOT NULL DEFAULT '0',
`top` tinyint(1) NOT NULL,
`column` int(3) NOT NULL,
`sort_order` int(3) NOT NULL DEFAULT '0',
`status` tinyint(1) NOT NULL,
`noindex` tinyint(1) NOT NULL DEFAULT '1',
`date_added` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
`date_modified` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
PRIMARY KEY (`blog_category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=69 ;

--
-- Dumping data for table `oc_blog_category`
--

INSERT INTO `oc_blog_category` (`blog_category_id`, `image`, `parent_id`, `top`, `column`, `sort_order`, `status`, `noindex`, `date_added`, `date_modified`) VALUES
(69, 'catalog/demo/canon_eos_5d_2.webp', 0, 1, 0, 0, 1, 1, '2014-04-08 03:56:26', '2015-06-18 09:15:42'),
(70, 'catalog/demo/iphone_2.webp', 0, 1, 0, 0, 1, 1, '2014-04-08 03:58:55', '2015-06-18 09:16:41'),
(71, 'catalog/demo/canon_eos_5d_1.webp', 69, 1, 1, 0, 1, 1, '2015-06-18 09:13:57', '2015-06-18 09:15:58');

-- --------------------------------------------------------

--
-- Table structure for table `oc_blog_category_description`
--

DROP TABLE IF EXISTS `oc_blog_category_description`;
CREATE TABLE `oc_blog_category_description` (
`blog_category_id` int(11) NOT NULL,
`language_id` int(11) NOT NULL,
`name` varchar(255) NOT NULL DEFAULT '',
`description` text NOT NULL,
`meta_description` varchar(255) NOT NULL,
`meta_keyword` varchar(255) NOT NULL,
`meta_title` varchar(255) NOT NULL,
`meta_h1` varchar(255) NOT NULL,
PRIMARY KEY (`blog_category_id`,`language_id`),
KEY `name` (`name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_blog_category_description`
--

INSERT INTO `oc_blog_category_description` (`blog_category_id`, `language_id`, `name`, `description`, `meta_description`, `meta_keyword`, `meta_title`, `meta_h1`) VALUES
(69, 1, 'Новини', '&lt;h2&gt;Новини магазину та платформи&lt;/h2&gt;&lt;p&gt;Короткі новини про можливості магазину, оновлення, продуктивність і корисні функції CodeCart.&lt;/p&gt;', 'Новини CodeCart PRO і демонстраційного магазину.', 'codecart, новини', 'Новини', 'Новини'),
(69, 2, 'News', '&lt;h2&gt;Store and Platform News&lt;/h2&gt;&lt;p&gt;Short updates about store capabilities, performance, improvements and useful CodeCart PRO features.&lt;/p&gt;', 'CodeCart PRO and demo store news.', 'codecart, news', 'News', 'News'),
(70, 1, 'Огляди', '&lt;h2&gt;Огляди можливостей&lt;/h2&gt;&lt;p&gt;Демонстраційні матеріали про каталог, картки товарів, мобільний інтерфейс, SEO та інші функції інтернет-магазину.&lt;/p&gt;', 'Огляди функцій і можливостей CodeCart.', 'codecart, огляди', 'Огляди', 'Огляди'),
(70, 2, 'Reviews', '&lt;h2&gt;Feature Reviews&lt;/h2&gt;&lt;p&gt;Demo articles about catalog pages, product cards, mobile UX, SEO and other online-store capabilities.&lt;/p&gt;', 'Reviews of CodeCart PRO features and capabilities.', 'codecart, reviews', 'Reviews', 'Reviews'),
(71, 1, 'Анонси', '&lt;h2&gt;Анонси та поради&lt;/h2&gt;&lt;p&gt;Короткі матеріали про налаштування, запуск магазину та практичне використання системи.&lt;/p&gt;', 'Анонси, поради та підготовка магазину до запуску.', 'codecart, анонси', 'Анонси', 'Анонси'),
(71, 2, 'Announcements', '&lt;h2&gt;Announcements and Tips&lt;/h2&gt;&lt;p&gt;Short guides about configuration, store launch and practical use of the platform.&lt;/p&gt;', 'Announcements, tips and store launch guidance.', 'codecart, announcements', 'Announcements', 'Announcements');
-- --------------------------------------------------------

--
-- Table structure for table `oc_blog_category_to_layout`
--

DROP TABLE IF EXISTS `oc_blog_category_to_layout`;
CREATE TABLE `oc_blog_category_to_layout` (
`blog_category_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL,
`layout_id` int(11) NOT NULL,
PRIMARY KEY (`blog_category_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_blog_category_to_layout`
--

INSERT INTO `oc_blog_category_to_layout` (`blog_category_id`, `store_id`, `layout_id`) VALUES
(69, 0, 0),
(71, 0, 0),
(70, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `oc_blog_category_to_store`
--

DROP TABLE IF EXISTS `oc_blog_category_to_store`;
CREATE TABLE `oc_blog_category_to_store` (
`blog_category_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL,
PRIMARY KEY (`blog_category_id`,`store_id`),
  KEY `store_blog_category` (`store_id`,`blog_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_blog_category_to_store`
--

INSERT INTO `oc_blog_category_to_store` (`blog_category_id`, `store_id`) VALUES
(69, 0),
(70, 0),
(71, 0);

-- --------------------------------------------------------

--
-- Table structure for table `oc_blog_category_path`
--

DROP TABLE IF EXISTS `oc_blog_category_path`;
CREATE TABLE `oc_blog_category_path` (
`blog_category_id` int(11) NOT NULL,
`path_id` int(11) NOT NULL,
`level` int(11) NOT NULL,
PRIMARY KEY (`blog_category_id`,`path_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_blog_category_path`
--

INSERT INTO `oc_blog_category_path` (`blog_category_id`, `path_id`, `level`) VALUES
(69, 69, 0),
(71, 71, 1),
(71, 69, 0),
(70, 70, 0);

-- --------------------------------------------------------

--
-- Table structure for table `oc_article_to_blog_category`
--

DROP TABLE IF EXISTS `oc_article_to_blog_category`;
CREATE TABLE `oc_article_to_blog_category` (
`article_id` int(11) NOT NULL,
`blog_category_id` int(11) NOT NULL,
`main_blog_category` tinyint(1) NOT NULL DEFAULT '0',
PRIMARY KEY (`article_id`,`blog_category_id`),
  KEY `blog_category_id` (`blog_category_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_article_to_blog_category`
--

INSERT INTO `oc_article_to_blog_category` (`article_id`, `blog_category_id`, `main_blog_category`) VALUES
(120, 71, 1),
(123, 70, 1),
(124, 70, 1),
(125, 69, 1),
(126, 69, 1),
(127, 70, 1),
(128, 69, 1),
(129, 71, 1),
(130, 71, 1),
(131, 71, 1);
-- --------------------------------------------------------

--
-- Table structure for table `oc_article`
--

DROP TABLE IF EXISTS `oc_article`;
CREATE TABLE `oc_article` (
`article_id` int(11) NOT NULL AUTO_INCREMENT,
`image` varchar(255) DEFAULT NULL,
`date_available` date NOT NULL,
`sort_order` int(11) NOT NULL DEFAULT '0',
`article_review` tinyint(1) NOT NULL DEFAULT '0',
`status` tinyint(1) NOT NULL DEFAULT '0',
`noindex` tinyint(1) NOT NULL DEFAULT '1',
`date_added` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
`date_modified` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
`viewed` int(5) NOT NULL DEFAULT '0',
`gstatus` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY (`article_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=132 ;

--
-- Dumping data for table `oc_article`
--

INSERT INTO `oc_article` (`article_id`, `image`, `date_available`, `sort_order`, `article_review`, `status`, `noindex`, `date_added`, `date_modified`, `viewed`, `gstatus`) VALUES
(120, 'catalog/codecart-pro-system.webp', '2026-09-01', 1, 0, 1, 1, '2026-09-27 12:00:00', '2026-09-27 12:00:00', 0, 0),
(123, 'catalog/demo/canon_eos_5d_2.webp', '2026-09-01', 2, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0),
(124, 'catalog/demo/product/thunderboltdisplay.webp', '2026-09-01', 3, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0),
(125, 'catalog/demo/iphone_2.webp', '2026-09-01', 4, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0),
(126, 'catalog/demo/macbook_air_1.webp', '2026-09-01', 5, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0),
(127, 'catalog/demo/apple_cinema_30.webp', '2026-09-01', 6, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0),
(128, 'catalog/demo/ipod_touch_1.webp', '2026-09-01', 7, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0),
(129, 'catalog/demo/hp_1.webp', '2026-09-01', 8, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0),
(130, 'catalog/demo/imac_1.webp', '2026-09-01', 9, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0),
(131, 'catalog/demo/samsung_syncmaster_941bw.webp', '2026-09-01', 10, 0, 1, 1, '2026-09-01 10:00:00', '2026-09-01 10:00:00', 0, 0);
--
-- Table structure for table `oc_article_description`
--

DROP TABLE IF EXISTS `oc_article_description`;
CREATE TABLE `oc_article_description` (
`article_id` int(11) NOT NULL,
`language_id` int(11) NOT NULL,
`name` varchar(255) NOT NULL,
`description` text NOT NULL,
`meta_description` varchar(255) NOT NULL,
`meta_keyword` varchar(255) NOT NULL,
`meta_title` varchar(255) NOT NULL,
`meta_h1` varchar(255) NOT NULL,
`tag` text NOT NULL,
PRIMARY KEY (`article_id`,`language_id`),
KEY `name` (`name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_article_description`
--

INSERT INTO `oc_article_description` (`article_id`, `language_id`, `name`, `description`, `meta_description`, `meta_keyword`, `meta_title`, `meta_h1`, `tag`) VALUES
(120, 1, 'CodeCart PRO 3.0.6.0 — сучасна основа для інтернет-магазину', '&lt;h2&gt;CodeCart PRO: знайома екосистема OpenCart із сучасними можливостями&lt;/h2&gt;
&lt;p&gt;&lt;strong&gt;CodeCart PRO 3.0.6.0&lt;/strong&gt; — платформа для інтернет-магазинів, яка розвиває основу OpenCart 3.x та ocStore. Вона поєднує каталог, продажі, роботу з покупцями й контентом із власною адаптивною темою, оновленою адмінпанеллю, планувальником, постійними чергами та інструментами діагностики. Магазин отримує узгоджений набір можливостей без необхідності одразу змінювати звичну архітектуру модулів.&lt;/p&gt;
&lt;p&gt;Система підходить для нового магазину та контрольованого оновлення наявного проєкту. Для чинного магазину важливо зберегти товари, замовлення, налаштування й оформлення. Тому оновлення має перевірку передумов, керовані міграції та режим відновлення файлів поточної збірки. Перед установленням або оновленням потрібна повна резервна копія сайту й бази даних.&lt;/p&gt;
&lt;h2&gt;Що покращено порівняно з класичним робочим процесом OpenCart 3&lt;/h2&gt;
&lt;p&gt;Головна перевага CodeCart — інтеграція інструментів, які в типовому магазині часто налаштовуються окремо. Адміністратор може перевіряти стан ядра, керувати фоновими завданнями, бачити проблемні модифікатори, аналізувати покинуті кошики й переглядати втрачені адреси в одній системі. Це скорочує кількість ручних дій і допомагає знайти причину проблеми до внесення наступної правки.&lt;/p&gt;
&lt;div class=&quot;table-responsive&quot;&gt;&lt;table class=&quot;table table-bordered&quot;&gt;&lt;thead&gt;&lt;tr&gt;&lt;th&gt;Завдання&lt;/th&gt;&lt;th&gt;Можливості CodeCart PRO&lt;/th&gt;&lt;th&gt;Практична користь&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;&lt;tbody&gt;
&lt;tr&gt;&lt;td&gt;Оновлення магазину&lt;/td&gt;&lt;td&gt;Перевірка передумов, міграції, повторний запуск і відновлення поточної збірки&lt;/td&gt;&lt;td&gt;Зрозуміліший процес оновлення та контроль сумісності&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Фонові операції&lt;/td&gt;&lt;td&gt;Спільний планувальник, черги, CLI та журнал станів&lt;/td&gt;&lt;td&gt;Обробка завдань без одного довгого запиту в браузері&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Пошук проблем&lt;/td&gt;&lt;td&gt;Діагностика ядра, системні сповіщення, контроль модифікаторів&lt;/td&gt;&lt;td&gt;Простіше перевірити середовище, налаштування та причини збоїв&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Втрачені сторінки&lt;/td&gt;&lt;td&gt;Журнал фактичних 404, фільтрація шуму та ручні 301-редиректи&lt;/td&gt;&lt;td&gt;Можна відновити корисний контент або перенаправити старі посилання&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Сучасні розширення&lt;/td&gt;&lt;td&gt;Окремі пакети з manifest, namespace, сервісами та адаптерами&lt;/td&gt;&lt;td&gt;Розвиток нових функцій зі збереженням знайомих MVC-L, OCMOD та Events&lt;/td&gt;&lt;/tr&gt;
&lt;/tbody&gt;&lt;/table&gt;&lt;/div&gt;
&lt;h2&gt;Вітрина, якою зручно користуватися&lt;/h2&gt;
&lt;p&gt;Штатна CodeCart Theme має адаптивне оформлення, світлий і темний режими, каталог із фільтрами та сортуванням, пошук, галереї товарів, опції, характеристики, відгуки, порівняння й закладки. Пов’язані товари та блоки рекомендацій допомагають покупцеві продовжити вибір, а блог дає змогу пояснити переваги товарів і відповісти на питання до покупки.&lt;/p&gt;
&lt;p&gt;Оформлення замовлення показує товари, спосіб доставки, оплату й підсумки. Система зберігає знайомі точки інтеграції OpenCart для платіжних і логістичних модулів. Для критичних операцій посилено роботу з транзакціями, повторними запитами та залишками. Фактична поведінка конкретного способу оплати залежить також від його модуля й налаштувань, тому перед запуском потрібен тест повного замовлення.&lt;/p&gt;
&lt;h2&gt;Адмінпанель і щоденна робота з продажами&lt;/h2&gt;
&lt;p&gt;Оновлена адмінпанель поєднує каталог, замовлення, покупців, маркетинг, магазини та системні налаштування. Глобальний пошук і швидкі дії допомагають переходити до потрібних даних, а панель стану показує продажі, останню активність та питання, що потребують уваги. Темна тема доступна і для адмінпанелі.&lt;/p&gt;
&lt;ul&gt;&lt;li&gt;Товари, категорії, виробники, опції, атрибути та додаткові зображення.&lt;/li&gt;&lt;li&gt;Замовлення, статуси, історія, покупці та групи покупців.&lt;/li&gt;&lt;li&gt;Покинуті кошики для аналізу незавершених покупок.&lt;/li&gt;&lt;li&gt;Купони, акції, поштові шаблони й розсилки з фоновою обробкою.&lt;/li&gt;&lt;li&gt;Підписки на надходження відсутніх товарів: покупець залишає запит у картці товару, адміністратор бачить його стан.&lt;/li&gt;&lt;li&gt;Статті, категорії блогу, відгуки й інформаційні сторінки.&lt;/li&gt;&lt;/ul&gt;
&lt;h2&gt;SEO, мови та робота зі старими посиланнями&lt;/h2&gt;
&lt;p&gt;CodeCart підтримує SEO URL для основних сутностей магазину, розширену маршрутизацію SeoPro, canonical, мовні префікси та карти сайту. Префікси задаються відповідно до мов магазину, а не обмежуються одним жорстко заданим набором. Метадані, заголовки, описи й контент допомагають будувати зрозумілу структуру каталогу та блогу.&lt;/p&gt;
&lt;p&gt;Окрема корисна можливість — &lt;strong&gt;журнал втрачених URL&lt;/strong&gt;. Після ручного ввімкнення він враховує фактичні HTML-відповіді 404, показує адресу, кількість звернень, дати та джерело переходу. Повторні звернення й корисні переходи виділяються для перевірки. Заявлені боти, сканери, технічні URL та запити до файлів фільтруються; браузерний запит при цьому не вважається гарантованим доказом реального відвідувача.&lt;/p&gt;
&lt;p&gt;За результатами аналізу можна відновити сторінку, позначити запис як непотрібний або вручну створити 301-редирект на чинний SEO slug того самого магазину й мови. Автоматичного перенаправлення на випадкові сторінки немає. Передбачено зберігання записів 30, 60 або 90 днів, обмеження до 5000 адрес на магазин і пакетне очищення; правила редиректів зберігаються окремо.&lt;/p&gt;
&lt;h2&gt;Зображення та швидкодія&lt;/h2&gt;
&lt;p&gt;Система використовує кешовані розміри зображень, підтримує сучасний процес обробки WebP/AVIF за наявності потрібних можливостей сервера, відкладене завантаження та контрольоване підключення ресурсів. Це допомагає зменшити зайву роботу браузера й сервера. Підсумкова швидкість залежить від хостингу, каталогу, фотографій, сторонніх модулів і налаштувань кешу; однакові показники для всіх магазинів не обіцяються.&lt;/p&gt;
&lt;h2&gt;Планувальник, черги та розширення&lt;/h2&gt;
&lt;p&gt;Один серверний cron може запускати зареєстровані завдання спільного планувальника, а їхні інтервали налаштовуються всередині системи. Незалежні cron-команди сторонніх модулів залишаються окремими інтеграціями. Постійне зберігання стану черг допомагає продовжувати обробку після перерви, а журнали показують стан і помилки виконання.&lt;/p&gt;
&lt;p&gt;Для розробників збережено MVC-L, Twig, OCMOD та Events. Додатково доступні Modern Extensions із власними namespace, manifests і сервісами, а Compatibility Framework дозволяє підключати ізольовані адаптери до старих контрактів тем. Наявність такого адаптера не означає автоматичну перевірку кожного стороннього модуля.&lt;/p&gt;
&lt;h2&gt;Безпека та зрозумілі сторінки помилок&lt;/h2&gt;
&lt;p&gt;Адміністративні дії перевіряють сесію, токен і права доступу. У системі передбачено контроль завантажень, діагностику середовища та захист службового сховища. Відвідувач отримує оформлені сторінки 404, технічного обслуговування й безпечний резервний екран 500 замість необроблених подробиць PHP-помилки. Адміністратор може дослідити технічну причину через журнали й діагностику.&lt;/p&gt;
&lt;h2&gt;Технічні характеристики й межі сумісності&lt;/h2&gt;
&lt;ul&gt;&lt;li&gt;Основа: екосистема OpenCart 3.x / ocStore, PHP, MySQL або MariaDB, Twig і Bootstrap 3.&lt;/li&gt;&lt;li&gt;Для сумісності з наявними модулями рекомендований діапазон PHP 8.1–8.3; ядро також перевіряється у CI на PHP 8.4 та 8.5.&lt;/li&gt;&lt;li&gt;Нові штатні таблиці використовують InnoDB та utf8mb4.&lt;/li&gt;&lt;li&gt;Інтерфейс підтримує українську, англійську та російську локалізації; мовний набір магазину налаштовується окремо.&lt;/li&gt;&lt;li&gt;Перевірки оновлення охоплюють ocStore 3.0.4.1, OpenCart 3.0.5.1 та ocStore 3.0.5.0-Beta.&lt;/li&gt;&lt;li&gt;Активні теми й налаштування чинних магазинів зберігаються під час оновлення; на чистому встановленні активується CodeCart Theme.&lt;/li&gt;&lt;/ul&gt;
&lt;p&gt;Комерційні теми, ionCube-пакети та інтеграції мають власні вимоги. Для UniShop2 перевірені контракти адаптера й окремий сценарій відсутньої статті; це не є сертифікацією повного оформлення та всіх поєднань модулів. Перед перенесенням робочого магазину варто перевірити його точну конфігурацію на тестовій копії.&lt;/p&gt;
&lt;h2&gt;Що нового в актуальній серії збірок&lt;/h2&gt;
&lt;p&gt;До останніх змін належать оформлені сторінки помилок, керований журнал втрачених адрес із ручними редиректами, захищений виклик cron, актуальні англійська й українська інструкції, посилання спільноти в адмінпанелі та перевірена упаковка залежностей. Виправлення інтерфейсу й функцій перевіряються разом із пов’язаними сценаріями, а готова збірка містить необхідні залежності для встановлення.&lt;/p&gt;
&lt;h2&gt;Перегляньте демо та долучайтеся до спільноти&lt;/h2&gt;
&lt;p&gt;Відкрийте &lt;a href=&quot;https://test.codecartpro.com/&quot;&gt;демонстраційну вітрину CodeCart PRO&lt;/a&gt; та &lt;a href=&quot;https://test.codecartpro.com/admin/&quot;&gt;демоадмінпанель&lt;/a&gt;. Публічний логін для демонстрації: &lt;strong&gt;Demo&lt;/strong&gt;, пароль: &lt;strong&gt;1234567890&lt;/strong&gt;. Приклади даних показують роботу інтерфейсу та не є справжніми комерційними операціями.&lt;/p&gt;
&lt;p&gt;Основне місце для спілкування, обговорення функцій і пропозицій — &lt;a href=&quot;https://t.me/+tUZNEgY3aUk4MGIy&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;&gt;&lt;strong&gt;форум CodeCart PRO у Telegram&lt;/strong&gt;&lt;/a&gt;. Код і розвиток системи: &lt;a href=&quot;https://github.com/CodeCartPro/CodeCartPro-3.0.6.0&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;&gt;репозиторій GitHub&lt;/a&gt;. Підтримка та пропозиції: &lt;a href=&quot;mailto:support@codecartpro.com&quot;&gt;support@codecartpro.com&lt;/a&gt;.&lt;/p&gt;', 'CodeCart PRO 3.0.6.0: сучасне ядро OpenCart/ocStore, CodeCart Theme, SEO, безпека, черги та діагностика. Огляньте вітрину й адмінпанель демо.', 'CodeCart PRO, OpenCart, ocStore, інтернет-магазин, CodeCart Theme, SEO, PHP, демо', 'CodeCart PRO 3.0.6.0: ядро, тема, SEO та демо', 'CodeCart PRO 3.0.6.0 — сучасна основа для інтернет-магазину', 'CodeCart PRO, OpenCart, ocStore, SEO'),
(120, 2, 'CodeCart PRO 3.0.6.0 — a modern foundation for online stores', '&lt;h2 id=&quot;about&quot;&gt;CodeCart PRO 3.0.6.0 — a modern foundation for online stores&lt;/h2&gt;
&lt;p&gt;&lt;a href=&quot;#about&quot;&gt;About&lt;/a&gt; · &lt;a href=&quot;#difference&quot;&gt;Differences&lt;/a&gt; · &lt;a href=&quot;#commerce&quot;&gt;Commerce&lt;/a&gt; · &lt;a href=&quot;#compatibility&quot;&gt;Compatibility&lt;/a&gt; · &lt;a href=&quot;#seo&quot;&gt;SEO&lt;/a&gt; · &lt;a href=&quot;#security&quot;&gt;Security&lt;/a&gt; · &lt;a href=&quot;#result&quot;&gt;Who it is for&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;CodeCart PRO 3.0.6.0&lt;/strong&gt; is a modernized e-commerce platform built on the OpenCart 3.x ecosystem. Its goal is to preserve compatibility with familiar modules, OCMOD, Events and MVC-L while adding a modern layer for safer upgrades, queues, scheduling, APIs, Modern Extensions, compatibility adapters, performance and commerce reliability.&lt;/p&gt;
&lt;p&gt;The core principle is &lt;strong&gt;Legacy Core + Modern Core&lt;/strong&gt;: existing extensions keep the environment they expect, while new extensions can use namespaces, PSR-4, services, manifests and stable extension contracts.&lt;/p&gt;

&lt;h3 id=&quot;difference&quot;&gt;How CodeCart differs from OpenCart and ocStore&lt;/h3&gt;
&lt;div class=&quot;table-responsive&quot;&gt;
&lt;table class=&quot;table table-bordered table-striped&quot;&gt;
&lt;thead&gt;&lt;tr&gt;&lt;th&gt;Capability&lt;/th&gt;&lt;th&gt;OpenCart 3.0.5.x&lt;/th&gt;&lt;th&gt;ocStore 3.0.5.x&lt;/th&gt;&lt;th&gt;CodeCart PRO 3.0.6.x&lt;/th&gt;&lt;/tr&gt;&lt;/thead&gt;
&lt;tbody&gt;
&lt;tr&gt;&lt;td&gt;PHP&lt;/td&gt;&lt;td&gt;PHP 8.0–8.4&lt;/td&gt;&lt;td&gt;PHP 8.0–8.5&lt;/td&gt;&lt;td&gt;&lt;strong&gt;PHP 8.1–8.5&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Extension architecture&lt;/td&gt;&lt;td&gt;MVC-L, OCMOD, Events&lt;/td&gt;&lt;td&gt;MVC-L, OCMOD, Events&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Legacy + Modern Extensions, PSR-4, services, manifests&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;System upgrades&lt;/td&gt;&lt;td&gt;Classic installer&lt;/td&gt;&lt;td&gt;Classic installer&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Installer 2.0, preflight, controlled migration and idempotent reruns&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Checkout reliability&lt;/td&gt;&lt;td&gt;Standard flow&lt;/td&gt;&lt;td&gt;Standard flow&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Transactions, locking, idempotency and duplicate-callback protection&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Queues and automation&lt;/td&gt;&lt;td&gt;Mainly extensions&lt;/td&gt;&lt;td&gt;Mainly extensions&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Queue, Scheduler, CLI Worker and Cron layer&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Third-party theme compatibility&lt;/td&gt;&lt;td&gt;Native version compatibility&lt;/td&gt;&lt;td&gt;ocStore compatibility&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Compatibility Framework and installable adapters&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;SEO URLs&lt;/td&gt;&lt;td&gt;Standard SEO URLs&lt;/td&gt;&lt;td&gt;SEO URL + SeoPro&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Standard SEO URL + SeoPro + dynamic language prefixes&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Images&lt;/td&gt;&lt;td&gt;Basic image processing&lt;/td&gt;&lt;td&gt;Basic image processing&lt;/td&gt;&lt;td&gt;&lt;strong&gt;WebP/AVIF-ready pipeline and modern image hooks&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Admin diagnostics&lt;/td&gt;&lt;td&gt;Classic&lt;/td&gt;&lt;td&gt;Classic&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Core diagnostics, compatibility scanner, global search and system notices&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;tr&gt;&lt;td&gt;Security&lt;/td&gt;&lt;td&gt;Core mechanisms&lt;/td&gt;&lt;td&gt;Regional enhancements&lt;/td&gt;&lt;td&gt;&lt;strong&gt;Security headers, upload guard, rate limits, secret handling and critical-action checks&lt;/strong&gt;&lt;/td&gt;&lt;/tr&gt;
&lt;/tbody&gt;&lt;/table&gt;
&lt;/div&gt;

&lt;h3 id=&quot;commerce&quot;&gt;Commerce-first reliability&lt;/h3&gt;
&lt;p&gt;A store must create exactly one order, decrement stock exactly once and avoid applying coupons or payment callbacks twice. CodeCart hardens checkout-critical operations with transactions, locking, idempotency, stock restoration, voucher/coupon safety and technical logging.&lt;/p&gt;

&lt;h3 id=&quot;compatibility&quot;&gt;Compatibility without reverting the modernized core&lt;/h3&gt;
&lt;p&gt;CodeCart keeps ordinary OpenCart 3.x extension compatibility and adds a &lt;strong&gt;Compatibility Framework&lt;/strong&gt;. A theme that depends on older internal contracts can use an adapter rather than forcing legacy algorithms back into Core. UniShop2 is the first practical example.&lt;/p&gt;

&lt;h3 id=&quot;seo&quot;&gt;SEO URLs and multilingual routing&lt;/h3&gt;
&lt;p&gt;&lt;strong&gt;SEO URLs&lt;/strong&gt; provide the basic clean-address layer. &lt;strong&gt;SeoPro&lt;/strong&gt; is the advanced router for category paths, canonical rules, postfixes and additional URL policies. Language prefixes are dynamic: the primary language may use no prefix while additional languages can use arbitrary folders.&lt;/p&gt;

&lt;h3 id=&quot;performance&quot;&gt;Performance and storefront&lt;/h3&gt;
&lt;p&gt;CodeCart keeps OpenCart&#x27;s lightweight server model while improving common bottlenecks through batched category loading, controlled caching, lazy loading, modern image formats and scoped asset loading.&lt;/p&gt;

&lt;h3 id=&quot;security&quot;&gt;Security and control&lt;/h3&gt;
&lt;ul&gt;
&lt;li&gt;admin/AJAX/API permission and token validation;&lt;/li&gt;
&lt;li&gt;safe SQL handling and whitelisted dynamic identifiers;&lt;/li&gt;
&lt;li&gt;UploadGuard and file validation;&lt;/li&gt;
&lt;li&gt;rate limits for public endpoints;&lt;/li&gt;
&lt;li&gt;secret values are not rendered back into admin DOM;&lt;/li&gt;
&lt;li&gt;technical errors stay in protected logs instead of exposing server paths.&lt;/li&gt;
&lt;/ul&gt;

&lt;h3 id=&quot;extensions&quot;&gt;Modern Extensions&lt;/h3&gt;
&lt;p&gt;Modern Extension Registry allows installable packages with manifests, namespaces, permissions and compatibility adapters without repeatedly patching Core, while traditional OpenCart modules remain supported.&lt;/p&gt;

&lt;h3 id=&quot;result&quot;&gt;Who CodeCart is for&lt;/h3&gt;
&lt;p&gt;CodeCart is intended for stores that want to remain in the OpenCart 3.x ecosystem while gaining a more modern foundation for long-term operation. It is an evolutionary path rather than a disruptive rewrite.&lt;/p&gt;', 'CodeCart PRO 3.0.6.0 is a modern OpenCart 3.x foundation with safer upgrades, Modern Extensions, SEO, compatibility adapters and reliable checkout.', 'codecart, opencart, ocstore, ecommerce, seo, modern extensions', 'CodeCart PRO 3.0.6.0 — a modern foundation for online stores', 'CodeCart PRO 3.0.6.0 — a modern foundation for online stores', 'codecart, opencart, ocstore, ecommerce, seo'),
(123, 1, 'Швидка вітрина: менше зайвого, більше користі', '&lt;h2 id=&quot;idea&quot;&gt;Швидка вітрина: менше зайвого, більше користі&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про швидку вітрину та легкий frontend.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'Швидка вітрина: менше зайвого, більше користі — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'Швидка вітрина: менше зайвого, більше користі', 'Швидка вітрина: менше зайвого, більше користі', 'codecart, блог, демо, магазин'),
(123, 2, 'A faster storefront: less overhead, more value', '&lt;h2 id=&quot;idea&quot;&gt;A faster storefront: less overhead, more value&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About a faster storefront and lightweight frontend.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'A faster storefront: less overhead, more value — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'A faster storefront: less overhead, more value', 'A faster storefront: less overhead, more value', 'codecart, blog, demo, store'),
(124, 1, 'Товар з опціями: як показати складний вибір просто', '&lt;h2 id=&quot;idea&quot;&gt;Товар з опціями: як показати складний вибір просто&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про опції товарів та зручний вибір.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'Товар з опціями: як показати складний вибір просто — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'Товар з опціями: як показати складний вибір просто', 'Товар з опціями: як показати складний вибір просто', 'codecart, блог, демо, магазин'),
(124, 2, 'Product options: making complex choices simple', '&lt;h2 id=&quot;idea&quot;&gt;Product options: making complex choices simple&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About product options and easy selection.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'Product options: making complex choices simple — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'Product options: making complex choices simple', 'Product options: making complex choices simple', 'codecart, blog, demo, store'),
(125, 1, 'Мобільний магазин: перевіряємо покупки зі смартфона', '&lt;h2 id=&quot;idea&quot;&gt;Мобільний магазин: перевіряємо покупки зі смартфона&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про мобільну торгівлю та UX.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'Мобільний магазин: перевіряємо покупки зі смартфона — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'Мобільний магазин: перевіряємо покупки зі смартфона', 'Мобільний магазин: перевіряємо покупки зі смартфона', 'codecart, блог, демо, магазин'),
(125, 2, 'Mobile commerce: testing the shopping flow on a phone', '&lt;h2 id=&quot;idea&quot;&gt;Mobile commerce: testing the shopping flow on a phone&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About mobile commerce and UX.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'Mobile commerce: testing the shopping flow on a phone — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'Mobile commerce: testing the shopping flow on a phone', 'Mobile commerce: testing the shopping flow on a phone', 'codecart, blog, demo, store'),
(126, 1, 'PHP 7.4–8.5: навіщо магазину сучасна сумісність', '&lt;h2 id=&quot;idea&quot;&gt;PHP 7.4–8.5: навіщо магазину сучасна сумісність&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про сумісність з PHP 7.4–8.5.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'PHP 7.4–8.5: навіщо магазину сучасна сумісність — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'PHP 7.4–8.5: навіщо магазину сучасна сумісність', 'PHP 7.4–8.5: навіщо магазину сучасна сумісність', 'codecart, блог, демо, магазин'),
(126, 2, 'PHP 7.4–8.5: why modern compatibility matters', '&lt;h2 id=&quot;idea&quot;&gt;PHP 7.4–8.5: why modern compatibility matters&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About PHP 7.4–8.5 compatibility.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'PHP 7.4–8.5: why modern compatibility matters — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'PHP 7.4–8.5: why modern compatibility matters', 'PHP 7.4–8.5: why modern compatibility matters', 'codecart, blog, demo, store'),
(127, 1, 'SEO з коробки: чисті URL, canonical і зрозуміла структура', '&lt;h2 id=&quot;idea&quot;&gt;SEO з коробки: чисті URL, canonical і зрозуміла структура&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про SEO-основу магазину.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'SEO з коробки: чисті URL, canonical і зрозуміла структура — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'SEO з коробки: чисті URL, canonical і зрозуміла структура', 'SEO з коробки: чисті URL, canonical і зрозуміла структура', 'codecart, блог, демо, магазин'),
(127, 2, 'SEO foundation: clean URLs, canonical and clear structure', '&lt;h2 id=&quot;idea&quot;&gt;SEO foundation: clean URLs, canonical and clear structure&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About the store SEO foundation.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'SEO foundation: clean URLs, canonical and clear structure — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'SEO foundation: clean URLs, canonical and clear structure', 'SEO foundation: clean URLs, canonical and clear structure', 'codecart, blog, demo, store'),
(128, 1, 'WebP та AVIF: менші зображення без зайвого навантаження', '&lt;h2 id=&quot;idea&quot;&gt;WebP та AVIF: менші зображення без зайвого навантаження&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про WebP та AVIF.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'WebP та AVIF: менші зображення без зайвого навантаження — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'WebP та AVIF: менші зображення без зайвого навантаження', 'WebP та AVIF: менші зображення без зайвого навантаження', 'codecart, блог, демо, магазин'),
(128, 2, 'WebP and AVIF: smaller images with less overhead', '&lt;h2 id=&quot;idea&quot;&gt;WebP and AVIF: smaller images with less overhead&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About WebP and AVIF.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'WebP and AVIF: smaller images with less overhead — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'WebP and AVIF: smaller images with less overhead', 'WebP and AVIF: smaller images with less overhead', 'codecart, blog, demo, store'),
(129, 1, 'Надійне оформлення замовлення: подвійний клік не повинен створювати дубль', '&lt;h2 id=&quot;idea&quot;&gt;Надійне оформлення замовлення: подвійний клік не повинен створювати дубль&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про надійний checkout і замовлення.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'Надійне оформлення замовлення: подвійний клік не повинен створювати дубль — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'Надійне оформлення замовлення: подвійний клік не повинен створювати дубль', 'Надійне оформлення замовлення: подвійний клік не повинен створювати дубль', 'codecart, блог, демо, магазин'),
(129, 2, 'Reliable checkout: a double click should not create a duplicate', '&lt;h2 id=&quot;idea&quot;&gt;Reliable checkout: a double click should not create a duplicate&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About reliable checkout and ordering.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'Reliable checkout: a double click should not create a duplicate — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'Reliable checkout: a double click should not create a duplicate', 'Reliable checkout: a double click should not create a duplicate', 'codecart, blog, demo, store'),
(130, 1, 'Сумісність із OpenCart 3.x: модернізація без різкого розриву', '&lt;h2 id=&quot;idea&quot;&gt;Сумісність із OpenCart 3.x: модернізація без різкого розриву&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про сумісність з OpenCart 3.x.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'Сумісність із OpenCart 3.x: модернізація без різкого розриву — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'Сумісність із OpenCart 3.x: модернізація без різкого розриву', 'Сумісність із OpenCart 3.x: модернізація без різкого розриву', 'codecart, блог, демо, магазин'),
(130, 2, 'OpenCart 3.x compatibility: modernization without a hard break', '&lt;h2 id=&quot;idea&quot;&gt;OpenCart 3.x compatibility: modernization without a hard break&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About OpenCart 3.x compatibility.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'OpenCart 3.x compatibility: modernization without a hard break — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'OpenCart 3.x compatibility: modernization without a hard break', 'OpenCart 3.x compatibility: modernization without a hard break', 'codecart, blog, demo, store'),
(131, 1, 'Перед запуском магазину: короткий контрольний список', '&lt;h2 id=&quot;idea&quot;&gt;Перед запуском магазину: короткий контрольний список&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Ідея&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Переваги&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Практика&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;Що далі&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;Про підготовку магазину до запуску.&lt;/strong&gt; Ця стаття оформлена як готовий шаблон для блогу магазину. Вона показує, як можуть виглядати заголовки, внутрішні посилання, списки, акценти та емоційні маркери. ✨&lt;/p&gt;&lt;p&gt;Блог у презентаційній версії магазину має бути не порожнім: він повинен одразу демонструвати можливості контент-маркетингу, SEO та зручного читання на мобільних пристроях.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;Що тут добре працює&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;короткий вступ і зрозуміла структура;&lt;/li&gt;&lt;li&gt;якорі для швидкої навігації;&lt;/li&gt;&lt;li&gt;доречні emoji та акцентні списки;&lt;/li&gt;&lt;li&gt;можливість посилатися на товари, категорії, новини та інформаційні сторінки. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Практичне використання&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Такі статті можна швидко замінити на новини про акції, переваги системи, оновлення, кейси магазину або корисні поради для покупців.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;Для України доцільно писати про доставку, оплату, гарантію, підбір товарів, сезонні пропозиції, огляди новинок і порівняння моделей.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;Що далі&lt;/h3&gt;&lt;p&gt;Після запуску демо-контент легко замінити на реальні матеріали, не змінюючи саму структуру блогу та макет сторінки.&lt;/p&gt;', 'Перед запуском магазину: короткий контрольний список — демонстраційна стаття для блогу магазину з готовою структурою, якірною навігацією та презентаційним контентом.', 'codecart, блог, демо, seo, магазин', 'Перед запуском магазину: короткий контрольний список', 'Перед запуском магазину: короткий контрольний список', 'codecart, блог, демо, магазин'),
(131, 2, 'Before store launch: a short checklist', '&lt;h2 id=&quot;idea&quot;&gt;Before store launch: a short checklist&lt;/h2&gt;&lt;p&gt;&lt;a href=&quot;#idea&quot;&gt;Idea&lt;/a&gt; · &lt;a href=&quot;#benefits&quot;&gt;Benefits&lt;/a&gt; · &lt;a href=&quot;#practical&quot;&gt;Practical use&lt;/a&gt; · &lt;a href=&quot;#next&quot;&gt;What next&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;strong&gt;About getting a store ready for launch.&lt;/strong&gt; This article is formatted as a ready-made store blog template and demonstrates headings, internal anchor links, lists, accents and light emoji usage. ✨&lt;/p&gt;&lt;p&gt;In a presentation storefront, the blog should not be empty. It should immediately demonstrate content-marketing, SEO and mobile reading capabilities.&lt;/p&gt;&lt;h3 id=&quot;benefits&quot;&gt;What works well here&lt;/h3&gt;&lt;ul&gt;&lt;li&gt;a concise intro and clear structure;&lt;/li&gt;&lt;li&gt;anchor links for quick navigation;&lt;/li&gt;&lt;li&gt;relevant emoji and highlighted lists;&lt;/li&gt;&lt;li&gt;the ability to link to products, categories, news and information pages. 🔗&lt;/li&gt;&lt;/ul&gt;&lt;h3 id=&quot;practical&quot;&gt;Practical use&lt;/h3&gt;&lt;blockquote&gt;&lt;p&gt;Articles like this can quickly be replaced with sale announcements, system advantages, updates, store case studies or useful buyer guides.&lt;/p&gt;&lt;/blockquote&gt;&lt;p&gt;For a Ukrainian-oriented store, strong themes include delivery, payment, warranty, buying guides, seasonal offers, new-product overviews and model comparisons.&lt;/p&gt;&lt;h3 id=&quot;next&quot;&gt;What next&lt;/h3&gt;&lt;p&gt;After launch, the demo content can be replaced with real materials while keeping the same blog structure and layout.&lt;/p&gt;', 'Before store launch: a short checklist — a demo blog article with anchor navigation, presentation-ready copy and a clean structure.', 'codecart, blog, demo, seo, store', 'Before store launch: a short checklist', 'Before store launch: a short checklist', 'codecart, blog, demo, store');
-- --------------------------------------------------------

--
-- Table structure for table `oc_article_image`
--

DROP TABLE IF EXISTS `oc_article_image`;
CREATE TABLE `oc_article_image` (
`article_image_id` int(11) NOT NULL AUTO_INCREMENT,
`article_id` int(11) NOT NULL,
`image` varchar(255) DEFAULT NULL,
`sort_order` int(3) NOT NULL DEFAULT '0',
PRIMARY KEY (`article_image_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=3981 ;

-- --------------------------------------------------------

--
-- Table structure for table `oc_article_related`
--

DROP TABLE IF EXISTS `oc_article_related`;
CREATE TABLE `oc_article_related` (
`article_id` int(11) NOT NULL,
`related_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`related_id`),
  KEY `related_id` (`related_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `oc_article_related`
--

INSERT INTO `oc_article_related` (`article_id`, `related_id`) VALUES
(120, 123),
(120, 124),
(120, 130),
(120, 131),
(123, 120),
(123, 124),
(123, 125),
(123, 131),
(124, 120),
(124, 123),
(124, 125),
(124, 126),
(125, 123),
(125, 124),
(125, 126),
(125, 127),
(126, 124),
(126, 125),
(126, 127),
(126, 128),
(127, 125),
(127, 126),
(127, 128),
(127, 129),
(128, 126),
(128, 127),
(128, 129),
(128, 130),
(129, 127),
(129, 128),
(129, 130),
(129, 131),
(130, 120),
(130, 128),
(130, 129),
(130, 131),
(131, 120),
(131, 123),
(131, 129),
(131, 130);
-- --------------------------------------------------------

--
-- Table structure for table `oc_article_related_mn`
--

DROP TABLE IF EXISTS `oc_article_related_mn`;
CREATE TABLE `oc_article_related_mn` (
`article_id` int(11) NOT NULL,
`manufacturer_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`manufacturer_id`),
  KEY `manufacturer_id` (`manufacturer_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_article_related_mn`
--

INSERT INTO `oc_article_related_mn` (`article_id`, `manufacturer_id`) VALUES
(120, 8),
(120, 9),
(123, 8),
(124, 7);

-- --------------------------------------------------------

--
-- Table structure for table `oc_article_related_product`
--

DROP TABLE IF EXISTS `oc_article_related_product`;
CREATE TABLE `oc_article_related_product` (
`article_id` int(11) NOT NULL,
`product_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `oc_article_related_product`
--

INSERT INTO `oc_article_related_product` (`article_id`, `product_id`) VALUES
(30, 123),
(31, 123),
(43, 123),
(45, 123),
(120, 28),
(120, 30),
(120, 41),
(123, 30),
(123, 31),
(123, 43),
(123, 45),
(124, 28),
(124, 30),
(124, 41),
(124, 47);

-- --------------------------------------------------------

--
-- Table structure for table `oc_product_related_article`
--

DROP TABLE IF EXISTS `oc_product_related_article`;
CREATE TABLE `oc_product_related_article` (
`article_id` int(11) NOT NULL,
`product_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_related_article`
--

INSERT INTO `oc_product_related_article` (`article_id`, `product_id`) VALUES
(120, 30),
(120, 40),
(120, 42),
(123, 40),
(123, 42),
(124, 40),
(125, 30);

-- --------------------------------------------------------

--
-- Table structure for table `oc_article_related_wb`
--

DROP TABLE IF EXISTS `oc_article_related_wb`;
CREATE TABLE `oc_article_related_wb` (
`article_id` int(11) NOT NULL,
`category_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`category_id`),
  KEY `category_id` (`category_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_article_related_wb`
--

INSERT INTO `oc_article_related_wb` (`article_id`, `category_id`) VALUES
(120, 26),
(123, 20),
(124, 18),
(125, 18),
(125, 27);

-- --------------------------------------------------------

--
-- Table structure for table `oc_article_to_download`
--

DROP TABLE IF EXISTS `oc_article_to_download`;
CREATE TABLE `oc_article_to_download` (
`article_id` int(11) NOT NULL,
`download_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`download_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oc_article_to_layout`
--

DROP TABLE IF EXISTS `oc_article_to_layout`;
CREATE TABLE `oc_article_to_layout` (
`article_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL,
`layout_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_article_to_layout`
--

INSERT INTO `oc_article_to_layout` (`article_id`, `store_id`, `layout_id`) VALUES
(120, 0, 0),
(123, 0, 0),
(124, 0, 0),
(125, 0, 0),
(126, 0, 0),
(127, 0, 0),
(128, 0, 0),
(129, 0, 0),
(130, 0, 0),
(131, 0, 0);
-- --------------------------------------------------------

--
-- Table structure for table `oc_article_to_store`
--

DROP TABLE IF EXISTS `oc_article_to_store`;
CREATE TABLE `oc_article_to_store` (
`article_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY (`article_id`,`store_id`),
  KEY `store_article` (`store_id`,`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_article_to_store`
--

INSERT INTO `oc_article_to_store` (`article_id`, `store_id`) VALUES
(120, 0),
(123, 0),
(124, 0),
(125, 0),
(126, 0),
(127, 0),
(128, 0),
(129, 0),
(130, 0),
(131, 0);
-- --------------------------------------------------------

--
-- Table structure for table `oc_review_article`
--

DROP TABLE IF EXISTS `oc_review_article`;
CREATE TABLE `oc_review_article` (
`review_article_id` int(11) NOT NULL AUTO_INCREMENT,
`article_id` int(11) NOT NULL,
`customer_id` int(11) NOT NULL,
`author` varchar(64) NOT NULL DEFAULT '',
`text` text NOT NULL,
`rating` int(1) NOT NULL,
`status` tinyint(1) NOT NULL DEFAULT '0',
`date_added` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
`date_modified` datetime NOT NULL DEFAULT '1970-01-01 00:00:01',
PRIMARY KEY (`review_article_id`),
KEY `article_id` (`article_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=11 ;

--
-- Dumping data for table `oc_review_article`
--

INSERT INTO `oc_review_article` (`review_article_id`, `article_id`, `customer_id`, `author`, `text`, `rating`, `status`, `date_added`, `date_modified`) VALUES
(11, 123, 0, 'Василь Покупець', 'Дякуємо за чудовий фото огляд, обов''язково найближчим часом придбаю собі таку тушку та напишу відгук до Вашої статті.', 5, 1, '2014-04-08 05:59:25', '1970-01-01 00:00:01');

-- --------------------------------------------------------

--
-- Table structure for table `oc_product_related_wb`
--

DROP TABLE IF EXISTS `oc_product_related_wb`;
CREATE TABLE `oc_product_related_wb` (
`product_id` int(11) NOT NULL,
`category_id` int(11) NOT NULL,
PRIMARY KEY (`product_id`,`category_id`),
KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_related_wb`
--

INSERT INTO `oc_product_related_wb` (`product_id`, `category_id`) VALUES
(33, 20),
(41, 26),
(41, 27),
(43, 18),
(44, 18),
(45, 18);

-- --------------------------------------------------------

--
-- Table structure for table `oc_product_related_mn`
--

DROP TABLE IF EXISTS `oc_product_related_mn`;
CREATE TABLE `oc_product_related_mn` (
`product_id` int(11) NOT NULL,
`manufacturer_id` int(11) NOT NULL,
PRIMARY KEY (`product_id`,`manufacturer_id`),
KEY `manufacturer_id` (`manufacturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `oc_product_related_mn`
--

INSERT INTO `oc_product_related_mn` (`product_id`, `manufacturer_id`) VALUES
(42, 8),
(41, 8),
(30, 9),
(47, 7);

-- --------------------------------------------------------

-- CodeCart PRO core feature defaults
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'codecart_core', 'codecart_cache_engine', 'file', 0),
(0, 'codecart_core', 'codecart_spam_service_status', '1', 0),
(0, 'codecart_core', 'codecart_admin_device_authorize_status', '0', 0),
(0, 'codecart_core', 'codecart_customer_device_authorize_status', '0', 0),
(0, 'codecart_core', 'codecart_gdpr_status', '0', 0),
(0, 'codecart_core', 'codecart_admin_login_protection_status', '1', 0),
(0, 'codecart_core', 'codecart_admin_login_max_attempts', '5', 0),
(0, 'codecart_core', 'codecart_admin_login_window_minutes', '15', 0),
(0, 'codecart_core', 'codecart_security_audit_retention_days', '90', 0),
(0, 'codecart_core', 'codecart_gdpr_retention_days', '365', 0),
(0, 'codecart_core', 'codecart_security_headers_status', '1', 0),
(0, 'codecart_core', 'codecart_security_nosniff_status', '1', 0),
(0, 'codecart_core', 'codecart_security_referrer_policy', 'strict-origin-when-cross-origin', 0),
(0, 'codecart_core', 'codecart_security_frame_options_status', '0', 0),
(0, 'codecart_core', 'codecart_security_frame_options', 'SAMEORIGIN', 0),
(0, 'codecart_core', 'codecart_security_permissions_policy_status', '0', 0),
(0, 'codecart_core', 'codecart_security_permissions_policy', 'camera=(), microphone=()', 0),
(0, 'codecart_core', 'codecart_security_csp_report_only_status', '0', 0),
(0, 'codecart_core', 'codecart_security_csp_report_only_policy', 'default-src \'self\'; img-src \'self\' data: https:; style-src \'self\' \'unsafe-inline\' https:; script-src \'self\' \'unsafe-inline\' https:; font-src \'self\' data: https:; connect-src \'self\' https:; frame-src \'self\' https:', 0),
(0, 'codecart_core', 'codecart_security_hsts_status', '0', 0),
(0, 'codecart_core', 'codecart_security_hsts_max_age', '31536000', 0),
(0, 'codecart_core', 'codecart_security_hsts_subdomains', '0', 0);

INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES
(0, 'codecart_relation', 'codecart_relation_status', '0', 0),
(0, 'codecart_relation', 'codecart_relation_storefront_status', '0', 0),
(0, 'codecart_relation', 'codecart_relation_product_limit', '8', 0),
(0, 'codecart_relation', 'codecart_relation_article_limit', '3', 0),
(0, 'codecart_relation', 'codecart_relation_in_stock_only', '1', 0),
(0, 'codecart_relation', 'codecart_relation_use_manufacturer', '1', 0),
(0, 'codecart_relation', 'codecart_relation_use_attributes', '1', 0);

-- CodeCart PRO core schema marker
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'codecart_core', 'codecart_core_schema_version', '3.0.6.0', 0);
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'codecart_core', 'codecart_presentation_schema_version', '32', 0);
INSERT INTO `oc_setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'codecart_core', 'codecart_setting_serialized_default', '1', 0);


DROP TABLE IF EXISTS `oc_mail_campaign_queue`;
CREATE TABLE `oc_mail_campaign_queue` (
  `mail_queue_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `email` varchar(96) NOT NULL,
  `name` varchar(191) NOT NULL DEFAULT '',
  `unsubscribe_token` char(64) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'waiting',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `last_error` varchar(255) NOT NULL DEFAULT '',
  `date_added` datetime NOT NULL,
  `date_sent` datetime DEFAULT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`mail_queue_id`),
  UNIQUE KEY `campaign_email` (`campaign_id`,`email`),
  UNIQUE KEY `unsubscribe_token` (`unsubscribe_token`),
  KEY `campaign_status` (`campaign_id`,`status`),
  KEY `status_added` (`status`,`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `oc_mail_campaign`;
CREATE TABLE `oc_mail_campaign` (
  `campaign_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `audience` varchar(32) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'queued',
  `total` int(11) unsigned NOT NULL DEFAULT '0',
  `sent` int(11) unsigned NOT NULL DEFAULT '0',
  `failed` int(11) unsigned NOT NULL DEFAULT '0',
  `suppressed` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `date_started` datetime DEFAULT NULL,
  `date_finished` datetime DEFAULT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`campaign_id`),
  KEY `status_added` (`status`,`date_added`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `oc_mail_suppression`;
CREATE TABLE `oc_mail_suppression` (
  `email` varchar(96) NOT NULL,
  `reason` varchar(32) NOT NULL DEFAULT 'unsubscribe',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `oc_stock_notify`;
CREATE TABLE `oc_stock_notify` (
  `stock_notify_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `store_id` int(11) NOT NULL DEFAULT '0',
  `language_id` int(11) NOT NULL DEFAULT '0',
  `email` varchar(96) NOT NULL,
  `option_hash` char(64) NOT NULL DEFAULT '',
  `option_data` text,
  `option_label` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(16) NOT NULL DEFAULT 'waiting',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `date_sent` datetime DEFAULT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`stock_notify_id`),
  UNIQUE KEY `uniq_product_email_option` (`product_id`,`store_id`,`email`,`option_hash`),
  KEY `status_product` (`status`,`product_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CodeCart built-in public documents and download statistics (Build 2.0.7)
CREATE TABLE `oc_codecart_public_document` (
              document_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              kind VARCHAR(24) NOT NULL DEFAULT 'manual',
              target_type VARCHAR(16) NOT NULL DEFAULT 'none',
              target_id INT UNSIGNED NOT NULL DEFAULT 0,
              filename VARCHAR(128) NOT NULL,
              original_name VARCHAR(255) NOT NULL,
              filesize BIGINT UNSIGNED NOT NULL DEFAULT 0,
              mime VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
              status TINYINT(1) NOT NULL DEFAULT 0,
              sort_order INT NOT NULL DEFAULT 0,
              download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
              last_download DATETIME NULL,
              date_added DATETIME NOT NULL,
              date_modified DATETIME NOT NULL,
              PRIMARY KEY (document_id), KEY target_lookup (status, target_type, target_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `oc_codecart_public_document_description` (
              document_id INT UNSIGNED NOT NULL,
              language_id INT NOT NULL,
              title VARCHAR(255) NOT NULL,
              PRIMARY KEY (document_id,language_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `oc_codecart_document_to_product` (
  document_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (document_id,product_id),
  KEY product_lookup (product_id,sort_order,document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `oc_codecart_public_document_daily` (
              document_id INT UNSIGNED NOT NULL, day DATE NOT NULL,
              download_count INT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (document_id,day), KEY day_lookup (day)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `oc_codecart_download_stats` (
              download_id INT UNSIGNED NOT NULL,
              download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
              last_download DATETIME NULL,
              PRIMARY KEY (download_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `oc_codecart_download_daily` (
              download_id INT UNSIGNED NOT NULL, day DATE NOT NULL,
              download_count INT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (download_id,day), KEY day_lookup (day)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `oc_setting` (`store_id`,`code`,`key`,`value`,`serialized`) VALUES (0,'codecart_core','codecart_file_stats_status','1',0);
INSERT INTO `oc_setting` (`store_id`,`code`,`key`,`value`,`serialized`) VALUES (0,'codecart_core','codecart_public_documents_status','0',0);
INSERT INTO `oc_setting` (`store_id`,`code`,`key`,`value`,`serialized`) VALUES (0,'codecart_core','codecart_public_documents_schema_version','2.0.8',0);
