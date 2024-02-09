/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/**
 * Скрипт для создания интеграционного слоя ТОРО
 */
INSERT INTO `amicum3`.`object_type` (`id`, `title`, `kind_object_id`) VALUES ('50', 'SAP', '1');
INSERT INTO `amicum3`.`object` (`id`, `title`, `object_type_id`, `object_table`) VALUES ('119', 'Прочее SAP', '50', 'equipment');
CREATE TABLE `amicum3`.`sap_equi_out` (
  `equipment_id` INT UNSIGNED NOT NULL COMMENT 'номер оборудования',
  `parent_equipment_id` INT NOT NULL COMMENT 'номер оборудования родителя',
  `equipment_title` VARCHAR(40) NOT NULL COMMENT 'название оборудования',
  `EQTYP` TINYINT NOT NULL COMMENT 'тип единицы оборудования',
  `inventory_number` VARCHAR(25) NULL COMMENT 'инвентарный номер',
  `ANLNR` VARCHAR(12) NULL COMMENT 'основной номер оборудования',
  `ANLUN` VARCHAR(4) NULL COMMENT 'субномер номер оборудования',
  `TPLNR` VARCHAR(40) NULL COMMENT 'код технического места',
  `BUKRS` VARCHAR(4) NULL COMMENT 'балансовая единица',
  `DATAB` DATETIME NULL COMMENT 'дата начал действия оборудования',
  `DATBI` DATETIME NULL COMMENT 'дата окончания действия оборудования',
  `INBDT` DATETIME NULL COMMENT 'дата ввода в эксплуатацию',
  `DATE_MODIFIED` DATETIME NOT NULL COMMENT 'дата изменения записи',
  PRIMARY KEY (`equipment_id`))
COMMENT = 'справочник оборудования из САП';
ALTER TABLE `amicum3`.`sap_equi_out`
ADD INDEX `sap_equi_out_date_modify` (`DATE_MODIFIED` DESC) VISIBLE;
;
ALTER TABLE `amicum3`.`sap_equi_out`
CHANGE COLUMN `parent_equipment_id` `parent_equipment_id` INT NULL COMMENT 'номер оборудования родителя' ;
ALTER TABLE `amicum3`.`sap_equi_out`
CHANGE COLUMN `EQTYP` `EQTYP` VARCHAR(1) NOT NULL COMMENT 'тип единицы оборудования' ;

ALTER TABLE `amicum3`.`equipment`
DROP FOREIGN KEY `fk_equipment_object1`;
ALTER TABLE `amicum3`.`equipment`
ADD COLUMN `date_time_sync` DATETIME NULL COMMENT 'Дата и время синхронизации позиции оборудования' AFTER `parent_equipment_id`,
CHANGE COLUMN `id` `id` INT NOT NULL COMMENT 'ключ оборудования' ,
CHANGE COLUMN `title` `title` VARCHAR(255) NOT NULL COMMENT 'название оборудования' ,
CHANGE COLUMN `inventory_number` `inventory_number` VARCHAR(20) NULL DEFAULT NULL COMMENT 'инвентарный номер' ,
CHANGE COLUMN `object_id` `object_id` INT NOT NULL COMMENT 'ключ типового объекта' , COMMENT = 'Справочник оборудования' ;
ALTER TABLE `amicum3`.`equipment`
ADD CONSTRAINT `fk_equipment_object1`
  FOREIGN KEY (`object_id`)
  REFERENCES `amicum3`.`object` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
ALTER TABLE `amicum3`.`equipment`
ADD COLUMN `sap_id` INT NULL COMMENT 'ключ оборудования sap' AFTER `date_time_sync`;

USE `amicum3`;
CREATE
     OR REPLACE ALGORITHM = UNDEFINED
    DEFINER = `amicum_system`@`%`
    SQL SECURITY DEFINER
VIEW `view_all_equipments_sensors_metka` AS
    SELECT
        `equipment`.`id` AS `equipment_id`,
        `equipment`.`object_id` AS `object_id`,
        `equipment`.`title` AS `equipment_title`,
        `equipment`.`inventory_number` AS `inventory_number`,
        `view_equipment_last_sensor`.`sensor_id` AS `sensor_id`,
        `sensor`.`title` AS `sensor_title`,
        `place`.`title` AS `place_title`,
        `view_equipment_parameters`.`factory_number` AS `factory_number`,
        '-' AS `department_title`
    FROM
        ((((`equipment`
        LEFT JOIN `view_equipment_last_sensor` ON ((`view_equipment_last_sensor`.`equipment_id` = `equipment`.`id`)))
        LEFT JOIN `sensor` ON ((`sensor`.`id` = `view_equipment_last_sensor`.`sensor_id`)))
        LEFT JOIN `view_equipment_parameters` ON ((`view_equipment_parameters`.`equipment_id` = `equipment`.`id`)))
        LEFT JOIN `place` ON ((`place`.`id` = `view_equipment_parameters`.`place_id`)));

#25.08.2023