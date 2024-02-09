/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

ALTER TABLE `amicum3`.`unity_texture`
    ADD COLUMN `color_hex` VARCHAR(7) NULL COMMENT 'hex цвет выработки' AFTER `description`,
    CHANGE COLUMN `id` `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ключ тектуры',
    CHANGE COLUMN `texture` `texture` VARCHAR(120) NOT NULL COMMENT 'название текстуры для unity',
    CHANGE COLUMN `title` `title` VARCHAR(120) NOT NULL COMMENT 'название текстуры для фронта',
    CHANGE COLUMN `description` `description` MEDIUMTEXT NULL DEFAULT NULL COMMENT 'описание текстуры',
    COMMENT = 'справочник цветов/текстур схемы шахты' ;

INSERT INTO `amicum3`.`parameter` (`id`, `title`, `unit_id`, `kind_parameter_id`) VALUES ('150', 'Форма выработки', '79', '1');

USE `amicum3`;
CREATE
    OR REPLACE ALGORITHM = UNDEFINED
    DEFINER = `amicum_system`@`%`
    SQL SECURITY DEFINER
    VIEW `view_edge` AS
SELECT
    `edge`.`id` AS `edge_id`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 151),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `lenght`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 128),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `height`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 129),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `width`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 130),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `section`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 150),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `shape_edge`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 131),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `danger_zona`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 132),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `color_texture`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 442),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `conveyor`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 389),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `conveyor_tag`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 263),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `value_ch`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 264),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `value_co`
FROM
    (`edge`
        LEFT JOIN `view_edge_parameter_handbook_value_maxDate_main` ON ((`view_edge_parameter_handbook_value_maxDate_main`.`edge_id` = `edge`.`id`)))
WHERE
    (((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 151)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 130)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 128)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 129)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 131)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 132)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 442)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 389)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 263)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 264))
        AND (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_type_id` = 1)
        AND (`view_edge_parameter_handbook_value_maxDate_main`.`status_id` = 1))
GROUP BY `edge`.`id`;

USE `amicum3`;
CREATE
    OR REPLACE ALGORITHM = UNDEFINED
    DEFINER = `amicum_system`@`%`
    SQL SECURITY DEFINER
    VIEW `view_initEdgeScheme` AS
SELECT
    `view_edge_conjunction_place`.`edge_id` AS `edge_id`,
    `view_edge_conjunction_place`.`place_id` AS `place_id`,
    `view_edge_conjunction_place`.`place_title` AS `place_title`,
    `view_edge_conjunction_place`.`conjunction_start_id` AS `conjunction_start_id`,
    `view_edge_conjunction_place`.`conjunction_end_id` AS `conjunction_end_id`,
    `view_edge_conjunction_place`.`xStart` AS `xStart`,
    `view_edge_conjunction_place`.`yStart` AS `yStart`,
    `view_edge_conjunction_place`.`zStart` AS `zStart`,
    `view_edge_conjunction_place`.`xEnd` AS `xEnd`,
    `view_edge_conjunction_place`.`yEnd` AS `yEnd`,
    `view_edge_conjunction_place`.`zEnd` AS `zEnd`,
    `view_edge_conjunction_place`.`place_object_id` AS `place_object_id`,
    `view_edge_conjunction_place`.`plast_title` AS `plast_title`,
    `view_edge_conjunction_place`.`type_place_title` AS `type_place_title`,
    `view_edge_conjunction_place`.`edge_type_title` AS `edge_type_title`,
    `view_edge_conjunction_place`.`edge_type_id` AS `edge_type_id`,
    IF((`view_edge`.`lenght` IS NULL),
       '-1',
       `view_edge`.`lenght`) AS `lenght`,
    IF((`view_edge`.`height` IS NULL),
       '-1',
       `view_edge`.`height`) AS `height`,
    IF((`view_edge`.`width` IS NULL),
       '-1',
       `view_edge`.`width`) AS `width`,
    IF((`view_edge`.`section` IS NULL),
       '-1',
       `view_edge`.`section`) AS `section`,
    IF((`view_edge`.`danger_zona` IS NULL),
       '-1',
       `view_edge`.`danger_zona`) AS `danger_zona`,
    IF((`unity_texture`.`title` IS NULL),
       '-1',
       `unity_texture`.`title`) AS `color_edge_rus`,
    IF((`unity_texture`.`texture` IS NULL),
       '-1',
       `unity_texture`.`id`) AS `color_edge`,
    IF((`unity_texture`.`color_hex` IS NULL),
       '-1',
       `unity_texture`.`color_hex`) AS `color_hex`,
    IF((`view_edge`.`conveyor` IS NULL),
       '-1',
       `view_edge`.`conveyor`) AS `conveyor`,
    IF((`view_edge`.`conveyor_tag` IS NULL),
       '-1',
       `view_edge`.`conveyor_tag`) AS `conveyor_tag`,
    IF((`view_edge`.`value_ch` IS NULL),
       '-1',
       `view_edge`.`value_ch`) AS `value_ch`,
    IF((`view_edge`.`value_co` IS NULL),
       '-1',
       `view_edge`.`value_co`) AS `value_co`,
    IF((`view_edge`.`shape_edge` IS NULL),
       '-1',
       `view_edge`.`shape_edge`) AS `shape_edge`,
    `mine`.`id` AS `mine_id`,
    `mine`.`title` AS `mine_title`,
    `view_edge_conjunction_place`.`date_time` AS `date_time`
FROM
    (((`view_edge_conjunction_place`
        JOIN `mine` ON ((`mine`.`id` = `view_edge_conjunction_place`.`mine_id`)))
        LEFT JOIN `view_edge` ON ((`view_edge`.`edge_id` = `view_edge_conjunction_place`.`edge_id`)))
        LEFT JOIN `unity_texture` ON ((`unity_texture`.`texture` = `view_edge`.`color_texture`)));

UPDATE `amicum3`.`unity_texture` SET `color_hex` = '0700FF' WHERE (`id` = '391');
UPDATE `amicum3`.`unity_texture` SET `color_hex` = '000000' WHERE (`id` = '392');
UPDATE `amicum3`.`unity_texture` SET `color_hex` = '646464' WHERE (`id` = '393');
UPDATE `amicum3`.`unity_texture` SET `color_hex` = '01F929' WHERE (`id` = '394');
UPDATE `amicum3`.`unity_texture` SET `color_hex` = 'E52DFF' WHERE (`id` = '395');
UPDATE `amicum3`.`unity_texture` SET `color_hex` = 'FF0500' WHERE (`id` = '396');

USE `amicum3`;
CREATE
    OR REPLACE ALGORITHM = UNDEFINED
    DEFINER = `amicum_system`@`%`
    SQL SECURITY DEFINER
    VIEW `view_edge` AS
SELECT
    `edge`.`id` AS `edge_id`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 151),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `lenght`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 128),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `height`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 129),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `width`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 130),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `section`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 150),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `shape_edge`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 131),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `danger_zona`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 132),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `color_texture`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 442),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `conveyor`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 389),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `conveyor_tag`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 263),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `value_ch`,
    MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 264),
           `view_edge_parameter_handbook_value_maxDate_main`.`value`,
           NULL)) AS `value_co`
FROM
    (`edge`
        LEFT JOIN `view_edge_parameter_handbook_value_maxDate_main` ON ((`view_edge_parameter_handbook_value_maxDate_main`.`edge_id` = `edge`.`id`)))
WHERE
    (((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 151)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 130)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 128)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 129)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 150)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 131)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 132)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 442)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 389)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 263)
        OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 264))
        AND (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_type_id` = 1)
        AND (`view_edge_parameter_handbook_value_maxDate_main`.`status_id` = 1))
GROUP BY `edge`.`id`;

USE `amicum3`;
CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `view_edge_main` AS
SELECT `view_edge_conjunction_place`.`edge_id`              AS `edge_id`,
       `view_edge_conjunction_place`.`place_id`             AS `place_id`,
       `view_edge_conjunction_place`.`place_title`          AS `place_title`,
       `view_edge_conjunction_place`.`conjunction_start_id` AS `conjunction_start_id`,
       `view_edge_conjunction_place`.`conjunction_end_id`   AS `conjunction_end_id`,
       `view_edge_conjunction_place`.`xStart`               AS `xStart`,
       `view_edge_conjunction_place`.`yStart`               AS `yStart`,
       `view_edge_conjunction_place`.`zStart`               AS `zStart`,
       `view_edge_conjunction_place`.`xEnd`                 AS `xEnd`,
       `view_edge_conjunction_place`.`yEnd`                 AS `yEnd`,
       `view_edge_conjunction_place`.`zEnd`                 AS `zEnd`,
       `view_edge_conjunction_place`.`place_object_id`      AS `place_object_id`,
       `view_edge_conjunction_place`.`plast_title`          AS `plast_title`,
       `view_edge_conjunction_place`.`type_place_title`     AS `type_place_title`,
       `view_edge_conjunction_place`.`edge_type_title`      AS `edge_type_title`,
       `view_edge_conjunction_place`.`edge_type_id`         AS `edge_type_id`,
       IF((`view_edge`.`lenght` IS NULL),
          '-1',
          `view_edge`.`lenght`)                             AS `lenght`,
       IF((`view_edge`.`height` IS NULL),
          '-1',
          `view_edge`.`height`)                             AS `height`,
       IF((`view_edge`.`width` IS NULL),
          '-1',
          `view_edge`.`width`)                              AS `width`,
       IF((`view_edge`.`section` IS NULL),
          '-1',
          `view_edge`.`section`)                            AS `section`,
       IF((`view_edge`.`danger_zona` IS NULL),
          '-1',
          `view_edge`.`danger_zona`)                        AS `danger_zona`,
       IF((`unity_texture`.`title` IS NULL),
          '-1',
          `unity_texture`.`title`)                          AS `color_edge_rus`,
       IF((`unity_texture`.`texture` IS NULL),
          '-1',
          `unity_texture`.`id`)                             AS `color_edge`,
       IF((`view_edge`.`conveyor` IS NULL),
          '-1',
          `view_edge`.`conveyor`)                           AS `conveyor`,
       IF((`view_edge`.`conveyor_tag` IS NULL),
          '-1',
          `view_edge`.`conveyor_tag`)                       AS `conveyor_tag`,
       IF((`view_edge`.`value_ch` IS NULL),
          '-1',
          `view_edge`.`value_ch`)                           AS `value_ch`,
       IF((`view_edge`.`value_co` IS NULL),
          '-1',
          `view_edge`.`value_co`)                           AS `value_co`,
       IF((`unity_texture`.`color_hex` IS NULL),
          '-1',
          `unity_texture`.`color_hex`)                      AS `color_hex`,
       IF((`view_edge`.`shape_edge` IS NULL),
          '-1',
          `view_edge`.`shape_edge`)                         AS `shape_edge`,
       `mine`.`id`                                          AS `mine_id`,
       `mine`.`title`                                       AS `mine_title`,
       `view_edge_conjunction_place`.`date_time`            AS `date_time`
FROM (((`view_edge_conjunction_place`
    JOIN `mine` ON ((`mine`.`id` = `view_edge_conjunction_place`.`mine_id`)))
    LEFT JOIN `view_edge` ON ((`view_edge`.`edge_id` = `view_edge_conjunction_place`.`edge_id`)))
    LEFT JOIN `unity_texture` ON ((`unity_texture`.`texture` = `view_edge`.`color_texture`)));

CREATE TABLE `amicum3`.`handbook_shape_edge`
(
    `id`    INT          NOT NULL AUTO_INCREMENT COMMENT 'ключ формы выработки',
    `title` VARCHAR(255) NULL COMMENT 'Наименование формы выработки',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE
)
    COMMENT = 'Справочник форм выработок';

INSERT INTO `amicum3`.`handbook_shape_edge` (`id`, `title`)
VALUES ('1', 'Прямоугольная');
INSERT INTO `amicum3`.`handbook_shape_edge` (`id`, `title`)
VALUES ('2', 'Трапециевидная');
INSERT INTO `amicum3`.`handbook_shape_edge` (`id`, `title`)
VALUES ('3', 'Полигональная');
INSERT INTO `amicum3`.`handbook_shape_edge` (`id`, `title`)
VALUES ('4', 'Сводчатая');
INSERT INTO `amicum3`.`handbook_shape_edge` (`id`, `title`)
VALUES ('5', 'Арочная');
INSERT INTO `amicum3`.`handbook_shape_edge` (`id`, `title`)
VALUES ('6', 'Круглая');


CREATE TABLE `amicum3`.`type_shield`
(
    `id`    INT          NOT NULL AUTO_INCREMENT COMMENT 'ключ типа крепи выработки',
    `title` VARCHAR(255) NOT NULL COMMENT 'наименование типа крепи выработки',
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    PRIMARY KEY (`id`)
)
    COMMENT = 'Тип крепи выработки';

INSERT INTO `amicum3`.`type_shield` (`id`, `title`)
VALUES ('1', 'Деревянная');
INSERT INTO `amicum3`.`type_shield` (`id`, `title`)
VALUES ('2', 'Металлическая');
INSERT INTO `amicum3`.`type_shield` (`id`, `title`)
VALUES ('3', 'Анкерная');
INSERT INTO `amicum3`.`type_shield` (`id`, `title`)
VALUES ('4', 'Каменная');


INSERT INTO `amicum3`.`unit` (`id`, `title`, `short`)
VALUES ('10', 'Градус', '°');
INSERT INTO `amicum3`.`parameter` (`id`, `title`, `unit_id`, `kind_parameter_id`)
VALUES ('123', 'Угол', '10', '1');
INSERT INTO `amicum3`.`parameter` (`id`, `title`, `unit_id`, `kind_parameter_id`)
VALUES ('125', 'Крепь выработки', '79', '1');
INSERT INTO `amicum3`.`parameter` (`id`, `title`, `unit_id`, `kind_parameter_id`)
VALUES ('124', 'Цвет', '79', '1');
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

# применил до сюда 09.08.2023

ALTER TABLE `amicum3`.`mine`
    DROP FOREIGN KEY `fk_mine_company`,
    DROP FOREIGN KEY `fk_mine_object1`;
ALTER TABLE `amicum3`.`mine`
    ADD COLUMN `version_scheme` VARCHAR(45) NULL COMMENT 'Версия схемы шахты' AFTER `company_id`,
    CHANGE COLUMN `id` `id` INT NOT NULL COMMENT 'ключ шахты',
    CHANGE COLUMN `title` `title` VARCHAR(255) NOT NULL COMMENT 'название шахты',
    CHANGE COLUMN `object_id` `object_id` INT NOT NULL COMMENT 'ключ типового объекта',
    CHANGE COLUMN `company_id` `company_id` INT NOT NULL DEFAULT '151' COMMENT 'ключ связанного подразделения';
ALTER TABLE `amicum3`.`mine`
    ADD CONSTRAINT `fk_mine_company`
        FOREIGN KEY (`company_id`)
            REFERENCES `amicum3`.`company` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_mine_object1`
        FOREIGN KEY (`object_id`)
            REFERENCES `amicum3`.`object` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;

ALTER TABLE `amicum3`.`mine`
    CHANGE COLUMN `version_scheme` `version_scheme` INT NULL DEFAULT NULL COMMENT 'Версия схемы шахты';

CREATE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `view_edge` AS
SELECT `edge`.`id`   AS `edge_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 151),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `lenght`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 128),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `height`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 129),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `width`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 130),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `section`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 125),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `type_shield_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 150),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `shape_edge_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 131),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `danger_zona`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 132),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `color_texture`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 442),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `conveyor`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 123),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `angle`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 389),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `conveyor_tag`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 263),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `value_ch`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 264),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `value_co`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 186),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `company_department_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 186),
              `view_edge_parameter_handbook_value_maxDate_main`.`date_time`,
              NULL)) AS `company_department_date`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 186),
              `view_edge_parameter_handbook_value_maxDate_main`.`status_id`,
              NULL)) AS `company_department_state`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 347),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `plast_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 124),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `color_hex`
FROM (`edge`
    LEFT JOIN `view_edge_parameter_handbook_value_maxDate_main`
      ON ((`view_edge_parameter_handbook_value_maxDate_main`.`edge_id` = `edge`.`id`)))
WHERE ((((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 151)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 130)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 125)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 128)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 129)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 150)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 131)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 132)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 442)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 347)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 389)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 123)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 124)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 263)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 264))
    AND (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_type_id` = 1)
    AND (`view_edge_parameter_handbook_value_maxDate_main`.`status_id` = 1))
    OR ((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 186)
        AND (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_type_id` = 1)))
GROUP BY `edge`.`id`;


CREATE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `view_initEdgeScheme` AS
SELECT `view_edge_conjunction_place`.`edge_id`              AS `edge_id`,
       `view_edge_conjunction_place`.`place_id`             AS `place_id`,
       `view_edge_conjunction_place`.`place_title`          AS `place_title`,
       `view_edge_conjunction_place`.`conjunction_start_id` AS `conjunction_start_id`,
       `view_edge_conjunction_place`.`conjunction_end_id`   AS `conjunction_end_id`,
       `view_edge_conjunction_place`.`xStart`               AS `xStart`,
       `view_edge_conjunction_place`.`yStart`               AS `yStart`,
       `view_edge_conjunction_place`.`zStart`               AS `zStart`,
       `view_edge_conjunction_place`.`xEnd`                 AS `xEnd`,
       `view_edge_conjunction_place`.`yEnd`                 AS `yEnd`,
       `view_edge_conjunction_place`.`zEnd`                 AS `zEnd`,
       `view_edge_conjunction_place`.`place_object_id`      AS `place_object_id`,
       `view_edge_conjunction_place`.`plast_title`          AS `plast_title`,
       `view_edge_conjunction_place`.`type_place_title`     AS `type_place_title`,
       `view_edge_conjunction_place`.`edge_type_title`      AS `edge_type_title`,
       `view_edge_conjunction_place`.`edge_type_id`         AS `edge_type_id`,
       IF((`view_edge`.`lenght` IS NULL),
          '-1',
          `view_edge`.`lenght`)                             AS `lenght`,
       IF((`view_edge`.`height` IS NULL),
          '-1',
          `view_edge`.`height`)                             AS `height`,
       IF((`view_edge`.`width` IS NULL),
          '-1',
          `view_edge`.`width`)                              AS `width`,
       IF((`view_edge`.`section` IS NULL),
          '-1',
          `view_edge`.`section`)                            AS `section`,
       IF((`view_edge`.`danger_zona` IS NULL),
          '-1',
          `view_edge`.`danger_zona`)                        AS `danger_zona`,
       IF((`unity_texture`.`title` IS NULL),
          '-1',
          `unity_texture`.`title`)                          AS `color_edge_rus`,
       IF((`unity_texture`.`texture` IS NULL),
          '-1',
          `unity_texture`.`id`)                             AS `color_edge`,
       IF((`view_edge`.`angle` IS NULL),
          '-1',
          `view_edge`.`angle`)                              AS `angle`,
       IF((`view_edge`.`color_hex` IS NULL),
          '-1',
          `view_edge`.`color_hex`)                          AS `color_hex`,
       IF((`view_edge`.`plast_id` IS NULL),
          '-1',
          `view_edge`.`plast_id`)                           AS `plast_id`,
       IF((`view_edge`.`company_department_id` IS NULL),
          '-1',
          `view_edge`.`company_department_id`)              AS `company_department_id`,
       IF((`view_edge`.`company_department_id` IS NULL),
          '-1',
          `view_edge`.`company_department_date`)            AS `company_department_date`,
       IF((`view_edge`.`company_department_state` IS NULL),
          '-1',
          `view_edge`.`company_department_state`)           AS `company_department_state`,
       `company`.`title`                                    AS `company_department_title`,
       IF((`view_edge`.`conveyor` IS NULL),
          '-1',
          `view_edge`.`conveyor`)                           AS `conveyor`,
       IF((`view_edge`.`conveyor_tag` IS NULL),
          '-1',
          `view_edge`.`conveyor_tag`)                       AS `conveyor_tag`,
       IF((`view_edge`.`value_ch` IS NULL),
          '-1',
          `view_edge`.`value_ch`)                           AS `value_ch`,
       IF((`view_edge`.`value_co` IS NULL),
          '-1',
          `view_edge`.`value_co`)                AS `value_co`,
       IF((`view_edge`.`shape_edge_id` IS NULL),
          '-1',
          `view_edge`.`shape_edge_id`)           AS `shape_edge_id`,
       `handbook_shape_edge`.`title`             AS `shape_edge_title`,
       IF((`view_edge`.`type_shield_id` IS NULL),
          '-1',
          `view_edge`.`type_shield_id`)          AS `type_shield_id`,
       `type_shield`.`title`                     AS `type_shield_title`,
       `mine`.`id`                               AS `mine_id`,
       `mine`.`title`                            AS `mine_title`,
       `view_edge_conjunction_place`.`date_time` AS `date_time`
FROM ((((((`view_edge_conjunction_place`
    JOIN `mine` ON ((`mine`.`id` = `view_edge_conjunction_place`.`mine_id`)))
    LEFT JOIN `view_edge` ON ((`view_edge`.`edge_id` = `view_edge_conjunction_place`.`edge_id`)))
    LEFT JOIN `unity_texture` ON ((`unity_texture`.`texture` = `view_edge`.`color_texture`)))
    LEFT JOIN `handbook_shape_edge` ON ((`handbook_shape_edge`.`id` = `view_edge`.`shape_edge_id`)))
    LEFT JOIN `type_shield` ON ((`type_shield`.`id` = `view_edge`.`type_shield_id`)))
    LEFT JOIN `company` ON ((`company`.`id` = `view_edge`.`company_department_id`)));

CREATE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `view_edge_main` AS
SELECT `view_edge_conjunction_place`.`edge_id`              AS `edge_id`,
       `view_edge_conjunction_place`.`place_id`             AS `place_id`,
       `view_edge_conjunction_place`.`place_title`          AS `place_title`,
       `view_edge_conjunction_place`.`conjunction_start_id` AS `conjunction_start_id`,
       `view_edge_conjunction_place`.`conjunction_end_id`   AS `conjunction_end_id`,
       `view_edge_conjunction_place`.`xStart`               AS `xStart`,
       `view_edge_conjunction_place`.`yStart`               AS `yStart`,
       `view_edge_conjunction_place`.`zStart`               AS `zStart`,
       `view_edge_conjunction_place`.`xEnd`                 AS `xEnd`,
       `view_edge_conjunction_place`.`yEnd`                 AS `yEnd`,
       `view_edge_conjunction_place`.`zEnd`                 AS `zEnd`,
       `view_edge_conjunction_place`.`place_object_id`      AS `place_object_id`,
       `view_edge_conjunction_place`.`plast_title`          AS `plast_title`,
       `view_edge_conjunction_place`.`type_place_title`     AS `type_place_title`,
       `view_edge_conjunction_place`.`edge_type_title`      AS `edge_type_title`,
       `view_edge_conjunction_place`.`edge_type_id`         AS `edge_type_id`,
       IF((`view_edge`.`lenght` IS NULL),
          '-1',
          `view_edge`.`lenght`)                             AS `lenght`,
       IF((`view_edge`.`height` IS NULL),
          '-1',
          `view_edge`.`height`)                             AS `height`,
       IF((`view_edge`.`width` IS NULL),
          '-1',
          `view_edge`.`width`)                              AS `width`,
       IF((`view_edge`.`section` IS NULL),
          '-1',
          `view_edge`.`section`)                            AS `section`,
       IF((`view_edge`.`danger_zona` IS NULL),
          '-1',
          `view_edge`.`danger_zona`)                        AS `danger_zona`,
       IF((`unity_texture`.`title` IS NULL),
          '-1',
          `unity_texture`.`title`)                          AS `color_edge_rus`,
       IF((`unity_texture`.`texture` IS NULL),
          '-1',
          `unity_texture`.`id`)                             AS `color_edge`,
       IF((`view_edge`.`conveyor` IS NULL),
          '-1',
          `view_edge`.`conveyor`)                           AS `conveyor`,
       IF((`view_edge`.`conveyor_tag` IS NULL),
          '-1',
          `view_edge`.`conveyor_tag`)                       AS `conveyor_tag`,
       IF((`view_edge`.`value_ch` IS NULL),
          '-1',
          `view_edge`.`value_ch`)                           AS `value_ch`,
       IF((`view_edge`.`value_co` IS NULL),
          '-1',
          `view_edge`.`value_co`)                           AS `value_co`,
       IF((`unity_texture`.`color_hex` IS NULL),
          '-1',
          `unity_texture`.`color_hex`)                      AS `color_hex`,
       IF((`view_edge`.`shape_edge_id` IS NULL),
          '-1',
          `view_edge`.`shape_edge_id`)                      AS `shape_edge_id`,
       `mine`.`id`                                          AS `mine_id`,
       `mine`.`title`                                       AS `mine_title`,
       `view_edge_conjunction_place`.`date_time`            AS `date_time`
FROM (((`view_edge_conjunction_place`
    JOIN `mine` ON ((`mine`.`id` = `view_edge_conjunction_place`.`mine_id`)))
    LEFT JOIN `view_edge` ON ((`view_edge`.`edge_id` = `view_edge_conjunction_place`.`edge_id`)))
    LEFT JOIN `unity_texture` ON ((`unity_texture`.`texture` = `view_edge`.`color_texture`)));

CREATE TABLE `amicum3`.`unity_config`
(
    `id`                INT  NOT NULL AUTO_INCREMENT,
    `mine_id`           INT  NOT NULL,
    `json_unity_config` JSON NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `mine_id_UNIQUE` (`mine_id` ASC) INVISIBLE
)
    COMMENT = 'Таблица для хранения конфигурации Unity';

/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

#25.08.2023

UPDATE `amicum3`.`status`
SET `title` = 'Послал SOS/Alarm'
WHERE (`id` = '41');
UPDATE `amicum3`.`status`
SET `title` = 'Подтвердил получение SOS/Alarm'
WHERE (`id` = '42');
UPDATE `amicum3`.`status`
SET `title` = 'Доставлен сигнал SOS/Alarm'
WHERE (`id` = '43');
UPDATE `amicum3`.`status`
SET `title` = 'Сигнал SOS/Alarm отменен'
WHERE (`id` = '48');


USE `amicum3`;
CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `view_edge` AS
SELECT `edge`.`id`   AS `edge_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 151),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `lenght`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 128),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `height`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 3),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `weight`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 129),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `width`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 130),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `section`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 125),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `type_shield_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 150),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `shape_edge_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 131),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `danger_zona`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 132),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `color_texture`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 442),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `conveyor`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 123),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `angle`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 389),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `conveyor_tag`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 263),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `value_ch`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 264),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `value_co`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 186),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `company_department_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 186),
              `view_edge_parameter_handbook_value_maxDate_main`.`date_time`,
              NULL)) AS `company_department_date`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 186),
              `view_edge_parameter_handbook_value_maxDate_main`.`status_id`,
              NULL)) AS `company_department_state`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 347),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `plast_id`,
       MAX(IF((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 124),
              `view_edge_parameter_handbook_value_maxDate_main`.`value`,
              NULL)) AS `color_hex`
FROM (`edge`
    LEFT JOIN `view_edge_parameter_handbook_value_maxDate_main`
      ON ((`view_edge_parameter_handbook_value_maxDate_main`.`edge_id` = `edge`.`id`)))
WHERE ((((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 151)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 130)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 125)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 128)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 129)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 150)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 131)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 132)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 442)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 347)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 389)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 123)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 124)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 263)
    OR (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 264))
    AND (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_type_id` = 1)
    AND (`view_edge_parameter_handbook_value_maxDate_main`.`status_id` = 1))
    OR ((`view_edge_parameter_handbook_value_maxDate_main`.`parameter_id` = 186)
        AND (`view_edge_parameter_handbook_value_maxDate_main`.`parameter_type_id` = 1)))
GROUP BY `edge`.`id`;

USE `amicum3`;
CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `view_initEdgeScheme` AS
SELECT `view_edge_conjunction_place`.`edge_id`              AS `edge_id`,
       `view_edge_conjunction_place`.`place_id`             AS `place_id`,
       `view_edge_conjunction_place`.`place_title`          AS `place_title`,
       `view_edge_conjunction_place`.`conjunction_start_id` AS `conjunction_start_id`,
       `view_edge_conjunction_place`.`conjunction_end_id`   AS `conjunction_end_id`,
       `view_edge_conjunction_place`.`xStart`               AS `xStart`,
       `view_edge_conjunction_place`.`yStart`               AS `yStart`,
       `view_edge_conjunction_place`.`zStart`               AS `zStart`,
       `view_edge_conjunction_place`.`xEnd`                 AS `xEnd`,
       `view_edge_conjunction_place`.`yEnd`                 AS `yEnd`,
       `view_edge_conjunction_place`.`zEnd`                 AS `zEnd`,
       `view_edge_conjunction_place`.`place_object_id`      AS `place_object_id`,
       `view_edge_conjunction_place`.`plast_title`          AS `plast_title`,
       `view_edge_conjunction_place`.`type_place_title`     AS `type_place_title`,
       `view_edge_conjunction_place`.`edge_type_title`      AS `edge_type_title`,
       `view_edge_conjunction_place`.`edge_type_id`         AS `edge_type_id`,
       IF((`view_edge`.`lenght` IS NULL),
          '-1',
          `view_edge`.`lenght`)                             AS `lenght`,
       IF((`view_edge`.`height` IS NULL),
          '-1',
          `view_edge`.`height`)                             AS `height`,
       IF((`view_edge`.`weight` IS NULL),
          '-1',
          `view_edge`.`weight`)                             AS `weight`,
       IF((`view_edge`.`width` IS NULL),
          '-1',
          `view_edge`.`width`)                              AS `width`,
       IF((`view_edge`.`section` IS NULL),
          '-1',
          `view_edge`.`section`)                            AS `section`,
       IF((`view_edge`.`danger_zona` IS NULL),
          '-1',
          `view_edge`.`danger_zona`)                        AS `danger_zona`,
       IF((`unity_texture`.`title` IS NULL),
          '-1',
          `unity_texture`.`title`)                          AS `color_edge_rus`,
       IF((`unity_texture`.`texture` IS NULL),
          '-1',
          `unity_texture`.`id`)                             AS `color_edge`,
       IF((`view_edge`.`angle` IS NULL),
          '-1',
          `view_edge`.`angle`)                              AS `angle`,
       IF((`view_edge`.`color_hex` IS NULL),
          '-1',
          `view_edge`.`color_hex`)                          AS `color_hex`,
       IF((`view_edge`.`plast_id` IS NULL),
          '-1',
          `view_edge`.`plast_id`)                           AS `plast_id`,
       IF((`view_edge`.`company_department_id` IS NULL),
          '-1',
          `view_edge`.`company_department_id`)              AS `company_department_id`,
       IF((`view_edge`.`company_department_id` IS NULL),
          '-1',
          `view_edge`.`company_department_date`)            AS `company_department_date`,
       IF((`view_edge`.`company_department_state` IS NULL),
          '-1',
          `view_edge`.`company_department_state`)           AS `company_department_state`,
       `company`.`title`                                    AS `company_department_title`,
       IF((`view_edge`.`conveyor` IS NULL),
          '-1',
          `view_edge`.`conveyor`)                           AS `conveyor`,
       IF((`view_edge`.`conveyor_tag` IS NULL),
          '-1',
          `view_edge`.`conveyor_tag`)                       AS `conveyor_tag`,
       IF((`view_edge`.`value_ch` IS NULL),
          '-1',
          `view_edge`.`value_ch`)                           AS `value_ch`,
       IF((`view_edge`.`value_co` IS NULL),
          '-1',
          `view_edge`.`value_co`)                           AS `value_co`,
       IF((`view_edge`.`shape_edge_id` IS NULL),
          '-1',
          `view_edge`.`shape_edge_id`)                      AS `shape_edge_id`,
       `handbook_shape_edge`.`title`                        AS `shape_edge_title`,
       IF((`view_edge`.`type_shield_id` IS NULL),
          '-1',
          `view_edge`.`type_shield_id`)                     AS `type_shield_id`,
       `type_shield`.`title`                                AS `type_shield_title`,
       `mine`.`id`                                          AS `mine_id`,
       `mine`.`title`                                       AS `mine_title`,
       `view_edge_conjunction_place`.`date_time`            AS `date_time`
FROM ((((((`view_edge_conjunction_place`
    JOIN `mine` ON ((`mine`.`id` = `view_edge_conjunction_place`.`mine_id`)))
    LEFT JOIN `view_edge` ON ((`view_edge`.`edge_id` = `view_edge_conjunction_place`.`edge_id`)))
    LEFT JOIN `unity_texture` ON ((`unity_texture`.`texture` = `view_edge`.`color_texture`)))
    LEFT JOIN `handbook_shape_edge` ON ((`handbook_shape_edge`.`id` = `view_edge`.`shape_edge_id`)))
    LEFT JOIN `type_shield` ON ((`type_shield`.`id` = `view_edge`.`type_shield_id`)))
    LEFT JOIN `company` ON ((`company`.`id` = `view_edge`.`company_department_id`)));


# применено ЮК 16.11.2023