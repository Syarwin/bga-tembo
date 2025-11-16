-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Tembo implementation : © Timothée (Tisaac) Pecatte <tim.pecatte@gmail.com>, Pavel Kulagin (KuWizard) <kuzwiz@mail.ru>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

ALTER TABLE `player`
    ADD `player_rotation` TINYINT DEFAULT -1;

CREATE TABLE IF NOT EXISTS `meeples`
(
    `meeple_id`       smallint unsigned NOT NULL AUTO_INCREMENT,
    `meeple_location` varchar(16)       NOT NULL,
    `meeple_state`    tinyint           NOT NULL,
    `type`            varchar(32) DEFAULT NULL,
    `player_id`       int(10)           NULL,
    `x`               tinyint           NOT NULL,
    `y`               tinyint           NOT NULL,
    PRIMARY KEY (`meeple_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;


CREATE TABLE IF NOT EXISTS `cards`
(
    `card_id`       smallint unsigned NOT NULL,
    `card_location` varchar(15)       NOT NULL,
    `card_state`    tinyint           NOT NULL,
    `x`             tinyint,
    `y`             tinyint,
    `rotation`      tinyint DEFAULT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `event_tiles`
(
    `card_id`       smallint unsigned NOT NULL AUTO_INCREMENT,
    `card_location` varchar(7)        NOT NULL,
    `card_state`    tinyint           NOT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;


CREATE TABLE IF NOT EXISTS `global_variables`
(
    `name`  varchar(255) NOT NULL,
    `value` JSON,
    PRIMARY KEY (`name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;


CREATE TABLE IF NOT EXISTS `log`
(
    `id`       int(10) unsigned NOT NULL AUTO_INCREMENT,
    `move_id`  int(10)          NOT NULL,
    `table`    varchar(32)      NOT NULL,
    `primary`  varchar(32)      NOT NULL,
    `type`     varchar(32)      NOT NULL,
    `affected` JSON,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
