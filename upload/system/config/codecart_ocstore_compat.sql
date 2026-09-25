-- CodeCart PRO 3.0.6.0 stock OpenCart -> ocStore compatibility tables.
-- Trusted internal DDL; existing tables are never replaced.

-- article
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_description
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_description` (
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

-- article_image
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_image` (
`article_image_id` int(11) NOT NULL AUTO_INCREMENT,
`article_id` int(11) NOT NULL,
`image` varchar(255) DEFAULT NULL,
`sort_order` int(3) NOT NULL DEFAULT '0',
PRIMARY KEY (`article_image_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_related
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_related` (
`article_id` int(11) NOT NULL,
`related_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`related_id`),
  KEY `related_id` (`related_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_related_mn
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_related_mn` (
`article_id` int(11) NOT NULL,
`manufacturer_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`manufacturer_id`),
  KEY `manufacturer_id` (`manufacturer_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_related_product
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_related_product` (
`article_id` int(11) NOT NULL,
`product_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_related_wb
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_related_wb` (
`article_id` int(11) NOT NULL,
`category_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`category_id`),
  KEY `category_id` (`category_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_to_blog_category
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_to_blog_category` (
`article_id` int(11) NOT NULL,
`blog_category_id` int(11) NOT NULL,
`main_blog_category` tinyint(1) NOT NULL DEFAULT '0',
PRIMARY KEY (`article_id`,`blog_category_id`),
  KEY `blog_category_id` (`blog_category_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_to_download
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_to_download` (
`article_id` int(11) NOT NULL,
`download_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`download_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_to_layout
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_to_layout` (
`article_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL,
`layout_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- article_to_store
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}article_to_store` (
`article_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY (`article_id`,`store_id`),
  KEY `store_article` (`store_id`,`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- blog_category
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}blog_category` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- blog_category_description
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}blog_category_description` (
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

-- blog_category_path
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}blog_category_path` (
`blog_category_id` int(11) NOT NULL,
`path_id` int(11) NOT NULL,
`level` int(11) NOT NULL,
PRIMARY KEY (`blog_category_id`,`path_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- blog_category_to_layout
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}blog_category_to_layout` (
`blog_category_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL,
`layout_id` int(11) NOT NULL,
PRIMARY KEY (`blog_category_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- blog_category_to_store
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}blog_category_to_store` (
`blog_category_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL,
PRIMARY KEY (`blog_category_id`,`store_id`),
  KEY `store_blog_category` (`store_id`,`blog_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- manufacturer_description
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}manufacturer_description` (
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

-- manufacturer_to_layout
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}manufacturer_to_layout` (
`manufacturer_id` int(11) NOT NULL,
`store_id` int(11) NOT NULL,
`layout_id` int(11) NOT NULL,
PRIMARY KEY (`manufacturer_id`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- modification_backup
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}modification_backup` (
`backup_id` int(11) NOT NULL AUTO_INCREMENT,
`modification_id` int(11) NOT NULL,
`code` varchar(64) NOT NULL,
`xml` mediumtext NOT NULL,
`date_added` datetime NOT NULL,
PRIMARY KEY (`backup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- order_shipment
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}order_shipment` (
  `order_shipment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `shipping_courier_id` varchar(255) NOT NULL DEFAULT '',
  `tracking_number` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`order_shipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- product_related_article
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}product_related_article` (
`article_id` int(11) NOT NULL,
`product_id` int(11) NOT NULL,
PRIMARY KEY (`article_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- product_related_mn
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}product_related_mn` (
`product_id` int(11) NOT NULL,
`manufacturer_id` int(11) NOT NULL,
PRIMARY KEY (`product_id`,`manufacturer_id`),
KEY `manufacturer_id` (`manufacturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- product_related_wb
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}product_related_wb` (
`product_id` int(11) NOT NULL,
`category_id` int(11) NOT NULL,
PRIMARY KEY (`product_id`,`category_id`),
KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- review_article
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}review_article` (
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- shipping_courier
CREATE TABLE IF NOT EXISTS `{DB_PREFIX}shipping_courier` (
  `shipping_courier_id` int(11) NOT NULL,
  `shipping_courier_code` varchar(255) NOT NULL DEFAULT '',
  `shipping_courier_name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`shipping_courier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
