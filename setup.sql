CREATE TABLE IF NOT EXISTS `im_category` (
  `id` int(11) NOT NULL auto_increment,
  `home_id` int(11) NOT NULL,
  `name` varchar(512) collate utf8_czech_ci NOT NULL,
  `description` varchar(512) collate utf8_czech_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `home_id` (`home_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `im_home` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(64) collate utf8_czech_ci NOT NULL,
  `description` varchar(512) collate utf8_czech_ci NOT NULL,
  `image` longblob,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1


CREATE TABLE IF NOT EXISTS `im_home_user` (
  `home_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  KEY `home_id` (`home_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `im_item` (
  `id` int(11) NOT NULL auto_increment,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `home_id` int(11) NOT NULL,
  `name` varchar(512) character set utf8 collate utf8_czech_ci NOT NULL,
  `description` varchar(1024) character set utf8 collate utf8_czech_ci default NULL,
  `image` longblob,
  `thumbnail` longblob,
  `location_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `location_id` (`location_id`,`category_id`),
  KEY `im_item_im_category_FK` (`category_id`),
  KEY `home_id` (`home_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `im_location` (
  `id` int(11) NOT NULL auto_increment,
  `home_id` int(11) NOT NULL,
  `parent_location_id` int(11) default NULL,
  `name` varchar(512) collate utf8_czech_ci NOT NULL,
  `description` varchar(512) collate utf8_czech_ci default NULL,
  `image` longblob,
  `thumbnail` longblob
  PRIMARY KEY  (`id`),
  KEY `parent_location_id` (`parent_location_id`),
  KEY `home_id` (`home_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `im_transaction` (
  `id` int(11) NOT NULL auto_increment,
  `item_id` int(11) NOT NULL,
  `from_location_id` int(11) default NULL,
  `to_location_id` int(11) default NULL,
  `parent_location_id` int(11) default NULL,
  `parent_from_location_id` int(11) default NULL,
  `parent_to_location_id` int(11) default NULL,
  `user_id` int(11) default NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `comment` varchar(1024) collate utf8_czech_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `item_id` (`item_id`,`from_location_id`,`to_location_id`),
  KEY `im_transcation_im_location_from_FK` (`from_location_id`),
  KEY `im_transcation_im_location_to_FK` (`to_location_id`),
  KEY `user_id` (`user_id`),
  KEY `parent_location_id` (`parent_location_id`,`parent_from_location_id`,`parent_to_location_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `im_user` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(64) collate utf8_czech_ci NOT NULL,
  `password` varchar(512) character set utf8 collate utf8_bin NOT NULL,
  `email` varchar(256) collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;

ALTER TABLE im_item ADD FOREIGN KEY (location_id) REFERENCES im_location(id);