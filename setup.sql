CREATE TABLE IF NOT EXISTS `im_category` (
  `id` int(11) NOT NULL auto_increment,
  `home_id` int(11) NOT NULL,
  `name` varchar(512) collate utf8_czech_ci NOT NULL,
  `description` varchar(512) collate utf8_czech_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `home_id` (`home_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `im_home` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(64) collate utf8_czech_ci NOT NULL,
  `description` varchar(512) collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `im_home_user` (
  `home_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_home_id` int(11) default NULL,
  KEY `home_id` (`home_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `im_item` (
  `id` int(11) NOT NULL auto_increment,
  `home_id` int(11) NOT NULL,
  `name` varchar(512) character set utf8 collate utf8_czech_ci NOT NULL,
  `description` varchar(1024) character set utf8 collate utf8_czech_ci default NULL,
  `author` varchar(128) character set utf8 collate utf8_czech_ci default NULL,
  `image` longblob,
  `thumbnail` longblob,
  `location_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `deleted` bool default false,
  PRIMARY KEY  (`id`),
  KEY `location_id` (`location_id`,`category_id`),
  KEY `im_item_im_category_FK` (`category_id`),
  KEY `home_id` (`home_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `im_location` (
  `id` int(11) NOT NULL auto_increment,
  `home_id` int(11) NOT NULL,
  `parent_location_id` int(11) default NULL,
  `name` varchar(512) collate utf8_czech_ci NOT NULL,
  `description` varchar(512) collate utf8_czech_ci default NULL,
  `image` longblob,
  `thumbnail` longblob,
  PRIMARY KEY  (`id`),
  KEY `parent_location_id` (`parent_location_id`),
  KEY `home_id` (`home_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;


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
  `action_id ` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `item_id` (`item_id`,`from_location_id`,`to_location_id`),
  KEY `im_transcation_im_location_from_FK` (`from_location_id`),
  KEY `im_transcation_im_location_to_FK` (`to_location_id`),
  KEY `user_id` (`user_id`),
  KEY `parent_location_id` (`parent_location_id`,`parent_from_location_id`,`parent_to_location_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `im_user` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(64) collate utf8_czech_ci NOT NULL,
  `password` varchar(512) character set utf8 collate utf8_bin NOT NULL,
  `email` varchar(256) collate utf8_czech_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `im_action` (
  `id` int NOT NULL,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO im_action(id, name) values(1, "Item deleted");
INSERT INTO im_action(id, name) values(2, "Item restored");

ALTER TABLE im_item ADD FOREIGN KEY FK_item_location(location_id) REFERENCES im_location(id);
ALTER TABLE im_item ADD FOREIGN KEY FK_item_home(home_id) REFERENCES im_home(id);
ALTER TABLE im_item ADD FOREIGN KEY FK_item_category(category_id) REFERENCES im_category(id);
ALTER TABLE im_location ADD FOREIGN KEY FK_location_location(parent_location_id) REFERENCES im_location(id);
ALTER TABLE im_home_user ADD FOREIGN KEY FK_home_user_home(home_id) REFERENCES im_home(id);
ALTER TABLE im_home_user ADD FOREIGN KEY FK_home_user_user(user_id) REFERENCES im_user(id);
ALTER TABLE im_transaction ADD FOREIGN KEY FK_transaction_item(item_id) REFERENCES im_item(id) ON DELETE CASCADE;
ALTER TABLE im_transaction ADD FOREIGN KEY FK_transaction_action(action_id) REFERENCES im_action(id);
