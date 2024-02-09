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

/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

ALTER TABLE `amicum3`.`order_place`
    ADD COLUMN `description` VARCHAR(955) NULL COMMENT 'описание наряда' AFTER `route_template_id`;


CREATE TABLE `order_item_group`
(
    `id`    int          NOT NULL AUTO_INCREMENT COMMENT 'ключ группы',
    `title` varchar(255) NOT NULL COMMENT 'название группы',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8mb3 COMMENT ='группы наряда';


CREATE TABLE `order_history`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ключ истории',
    `order_id`         int          NOT NULL COMMENT 'ключ главного наряда',
    `date_time_create` datetime     NOT NULL COMMENT 'дата сохранения наряда',
    `worker_id`        int          NOT NULL COMMENT 'ключ сохранившего наряд',
    `status_id`        int          NOT NULL COMMENT 'ключ статуса наряда',
    PRIMARY KEY (`order_id`, `date_time_create`, `worker_id`, `status_id`),
    UNIQUE KEY `id_UNIQUE` (`id`),
    KEY `fkey_order_history_status_idx` (`status_id`),
    KEY `fkey_order_history_order_worker` (`worker_id`),
    CONSTRAINT `fkey_order_history_order` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `fkey_order_history_order_worker` FOREIGN KEY (`worker_id`) REFERENCES `worker` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_history_status` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb3 COMMENT ='История сохранения наряда';



CREATE TABLE `order_item`
(
    `id`                      int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ключ атомарного наряда',
    `order_history_id`        int unsigned NOT NULL COMMENT 'ключ главного наряда',
    `worker_id`               int   DEFAULT NULL COMMENT 'ключ работника',
    `equipment_id`            int   DEFAULT NULL COMMENT 'ключ оборудования',
    `operation_id`            int   DEFAULT NULL COMMENT 'ключ операции',
    `place_from_id`           int   DEFAULT NULL COMMENT 'ключ места откуда',
    `place_to_id`             int   DEFAULT NULL COMMENT 'ключ места куда',
    `group_order_id`          int   DEFAULT NULL COMMENT 'ключ группы наряда',
    `plan`                    float DEFAULT NULL COMMENT 'план',
    `fact`                    float DEFAULT NULL COMMENT 'факт',
    `description`             text COMMENT 'комментарий',
    `group_id`                int   DEFAULT NULL COMMENT 'ключ группы',
    `chane_id`                int   DEFAULT NULL COMMENT 'Звено в котором числится человек',
    `brigade_id`              int   DEFAULT NULL COMMENT 'бригада в которой числится человек',
    `status_id`               int   DEFAULT NULL COMMENT 'Внешний ключ справочника статусов',
    `order_operation_id_vtb`  int   DEFAULT NULL COMMENT 'ключ конкретной операции из наряда ВТБ',
    `correct_measures_id`     int   DEFAULT NULL COMMENT 'ключ конкретной операции из предписания',
    `order_place_id_vtb`      int   DEFAULT NULL COMMENT 'место в котором было выдан наряд ВТБ',
    `injunction_violation_id` int   DEFAULT NULL COMMENT 'ключ привязки нарушения к месту в наряде',
    `injunction_id`           int   DEFAULT NULL COMMENT 'ключ привязки предписания к месту в наряде',
    `equipment_status_id`     int   DEFAULT NULL,
    `role_id`                 int   DEFAULT NULL COMMENT 'ключ роли пользователя',
    `date_time_create`        datetime     NOT NULL COMMENT 'дата и время создания',
    PRIMARY KEY (`id`),
    KEY `order_item_worker_idx` (`worker_id`),
    KEY `fkey_order_item_equipment_idx` (`equipment_id`),
    KEY `fkey_order_item_operation_idx` (`operation_id`),
    KEY `fkey_order_item_place_idx` (`place_from_id`),
    KEY `fkey_order_item_place_to_idx` (`place_to_id`),
    KEY `fkey_order_item_group_item` (`group_id`),
    KEY `fkey_order_item_brigade_idx` (`brigade_id`),
    KEY `fkey_order_item_chane_idx` (`chane_id`),
    KEY `fkey_order_item_order_place_vtb_idx` (`order_place_id_vtb`),
    KEY `fkey_order_item_correct_measure_idx` (`correct_measures_id`),
    KEY `fkey_order_item_order_operation_vtb_idx` (`order_operation_id_vtb`),
    KEY `fkey_order_item_injunction_violation_idx` (`injunction_violation_id`),
    KEY `fkey_order_item_status_idx` (`status_id`),
    KEY `fkey_order_item_injunction` (`injunction_id`),
    KEY `fkey_order_item_equipment_status_idx` (`equipment_status_id`),
    KEY `fkey_order_item_role_idx` (`role_id`),
    KEY `fkey_order_item_order_history_idx` (`order_history_id`),
    CONSTRAINT `fkey_order_item_brigade` FOREIGN KEY (`brigade_id`) REFERENCES `brigade` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_chane` FOREIGN KEY (`chane_id`) REFERENCES `chane` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_correct_measure` FOREIGN KEY (`correct_measures_id`) REFERENCES `correct_measures` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_equipment_status` FOREIGN KEY (`equipment_status_id`) REFERENCES `status` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_group_item` FOREIGN KEY (`group_id`) REFERENCES `order_item_group` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_injunction` FOREIGN KEY (`injunction_id`) REFERENCES `injunction` (`id`),
    CONSTRAINT `fkey_order_item_injunction_violation` FOREIGN KEY (`injunction_violation_id`) REFERENCES `injunction_violation` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_operation` FOREIGN KEY (`operation_id`) REFERENCES `operation` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_order_history` FOREIGN KEY (`order_history_id`) REFERENCES `order_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_order_operation_vtb` FOREIGN KEY (`order_operation_id_vtb`) REFERENCES `order_operation_place_vtb_ab` (`id`),
    CONSTRAINT `fkey_order_item_order_place_vtb` FOREIGN KEY (`order_place_id_vtb`) REFERENCES `order_place_vtb_ab` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_place_from` FOREIGN KEY (`place_from_id`) REFERENCES `place` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_place_to` FOREIGN KEY (`place_to_id`) REFERENCES `place` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_status` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_worker` FOREIGN KEY (`worker_id`) REFERENCES `worker` (`id`) ON UPDATE CASCADE
) ENGINE = InnoDB
  AUTO_INCREMENT = 3
  DEFAULT CHARSET = utf8mb3 COMMENT ='Таблица нарядов для разрезов и мобильной версии';


CREATE TABLE `order_item_status`
(
    `id`               int          NOT NULL AUTO_INCREMENT COMMENT 'Ключ таблицы статуса наряда',
    `order_item_id`    int unsigned NOT NULL COMMENT 'внешний ключ атомарного наряда',
    `status_id`        int          NOT NULL COMMENT 'внешний ключ справочника статусов',
    `worker_id`        int          NOT NULL,
    `date_time_create` datetime     NOT NULL,
    `description`      varchar(255) DEFAULT NULL COMMENT 'Причина смены статуса',
    UNIQUE KEY `id_UNIQUE` (`id`),
    KEY `fkey_order_item_status_status_idx` (`status_id`),
    KEY `fkey_order_item_status_worker` (`worker_id`),
    KEY `fkey_order_item_status_order_item` (`order_item_id`),
    CONSTRAINT `fkey_order_item_status_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_item` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_status_status` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_status_worker` FOREIGN KEY (`worker_id`) REFERENCES `worker` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb3 COMMENT ='таблица статусов подписания наряда';


CREATE TABLE `order_item_instruction_pb`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ключ инструктажа работника',
    `instruction_id`   int          NOT NULL COMMENT 'ключ инструктажа',
    `order_history_id` int unsigned NOT NULL COMMENT 'ключ истории сохранения наряда',
    PRIMARY KEY (`order_history_id`, `instruction_id`),
    UNIQUE KEY `id_UNIQUE` (`id`),
    KEY `fkey_order_item_instruction_pb_instruction_pb_idx` (`instruction_id`),
    KEY `fkey_order_item_instruction_pb_order_history_idx` (`order_history_id`),
    CONSTRAINT `fkey_order_item_instruction_pb_instruction_pb` FOREIGN KEY (`instruction_id`) REFERENCES `instruction_pb` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_instruction_pb_order_history` FOREIGN KEY (`order_history_id`) REFERENCES `order_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb3 COMMENT ='Таблица инструктажей атомарного наряда';


CREATE TABLE `order_item_worker`
(
    `id`                      int unsigned NOT NULL AUTO_INCREMENT,
    `worker_id`               int          NOT NULL COMMENT 'ключ работника',
    `role_id`                 int          NOT NULL COMMENT 'ключ роли',
    `order_history_id`        int unsigned NOT NULL COMMENT 'ключ истории сохранения наряда',
    `worker_restriction_json` json DEFAULT NULL COMMENT 'ограничения по наряду работника',
    `workers_json`            json DEFAULT NULL,
    PRIMARY KEY (`order_history_id`, `worker_id`, `role_id`),
    UNIQUE KEY `id_UNIQUE` (`id`),
    KEY `fkey_order_item_worker_worker_idx` (`worker_id`),
    KEY `fkey_order_item_worker_role_idx` (`role_id`),
    CONSTRAINT `fkey_order_item_worker_order_history` FOREIGN KEY (`order_history_id`) REFERENCES `order_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_worker_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_worker_worker` FOREIGN KEY (`worker_id`) REFERENCES `worker` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb3 COMMENT ='Таблица работников атомарного наряда';


CREATE TABLE `order_item_equipment`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT,
    `equipment_id`     int          NOT NULL COMMENT 'ключ оборудования',
    `status_id`        int          NOT NULL COMMENT 'ключ статуса',
    `order_history_id` int unsigned NOT NULL COMMENT 'ключ истории сохранения наряда',
    `equipments_json`  json DEFAULT NULL COMMENT 'ограничения по наряду оборудования',
    PRIMARY KEY (`order_history_id`, `equipment_id`, `status_id`),
    UNIQUE KEY `id_UNIQUE` (`id`),
    KEY `fkey_order_item_equipment_equipment_idx` (`equipment_id`),
    KEY `fkey_order_item_equipment_status_idx` (`status_id`),
    CONSTRAINT `fkey_order_item_equipment_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_equipment_order_history` FOREIGN KEY (`order_history_id`) REFERENCES `order_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_equipment_status1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb3 COMMENT ='Таблица оборудования атомарного наряда';



CREATE TABLE `order_item_worker_instruction_pb`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ключ инструктажа работника',
    `instruction_id`   int          NOT NULL COMMENT 'ключ инструктажа',
    `order_history_id` int unsigned NOT NULL COMMENT 'ключ истории сохранения наряда',
    `worker_id`        int          NOT NULL COMMENT 'ключ работника',
    PRIMARY KEY (`worker_id`, `order_history_id`, `instruction_id`),
    UNIQUE KEY `id_UNIQUE` (`id`),
    KEY `fkey_order_item_worker_instruction_pb_instruction_pb_idx` (`instruction_id`),
    KEY `fkey_order_item_worker_instruction_pb_order_history_idx` (`order_history_id`),
    CONSTRAINT `fkey_order_item_worker_instruction_pb_instruction_pb` FOREIGN KEY (`instruction_id`) REFERENCES `instruction_pb` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_worker_instruction_pb_order_history` FOREIGN KEY (`order_history_id`) REFERENCES `order_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_worker_instruction_pb_worker` FOREIGN KEY (`worker_id`) REFERENCES `worker` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb3 COMMENT ='Таблица инструктажей работников';

CREATE TABLE `order_item_injunction`
(
    `id`               int unsigned NOT NULL AUTO_INCREMENT,
    `injunction_id`    int          NOT NULL COMMENT 'ключ предписания',
    `status_id`        int          NOT NULL COMMENT 'ключ статуса предписания',
    `order_history_id` int unsigned NOT NULL COMMENT 'ключ истории сохранения наряда',
    `injunctions_json` json DEFAULT NULL COMMENT 'объект предписания при сохранении',
    PRIMARY KEY (`order_history_id`, `injunction_id`, `status_id`),
    UNIQUE KEY `id_UNIQUE` (`id`),
    KEY `fkey_order_item_injunction_injunction_idx` (`injunction_id`),
    KEY `fkey_order_item_injunction_status_idx` (`status_id`),
    CONSTRAINT `fkey_order_item_injunction_injunction` FOREIGN KEY (`injunction_id`) REFERENCES `injunction` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_injunction_order_history` FOREIGN KEY (`order_history_id`) REFERENCES `order_history` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fkey_order_item_injunction_status1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb3 COMMENT ='Таблица предписаний атомарного наряда';

ALTER TABLE `amicum3`.`order_item`
    ADD COLUMN `order_type_id` INT NULL COMMENT 'тип наряда по месту ' AFTER `date_time_create`;


ALTER TABLE `amicum3`.`order_item_worker_instruction_pb`
    DROP FOREIGN KEY `fkey_order_item_worker_instruction_pb_instruction_pb`;
ALTER TABLE `amicum3`.`order_item_worker_instruction_pb`
    CHANGE COLUMN `instruction_id` `instruction_pb_id` INT NOT NULL COMMENT 'ключ инструктажа' ;
ALTER TABLE `amicum3`.`order_item_worker_instruction_pb`
    ADD CONSTRAINT `fkey_order_item_worker_instruction_pb_instruction_pb`
        FOREIGN KEY (`instruction_pb_id`)
            REFERENCES `amicum3`.`instruction_pb` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE;

ALTER TABLE `amicum3`.`order_item_instruction_pb`
    DROP FOREIGN KEY `fkey_order_item_instruction_pb_instruction_pb`;
ALTER TABLE `amicum3`.`order_item_instruction_pb`
    CHANGE COLUMN `instruction_id` `instruction_pb_id` INT NOT NULL COMMENT 'ключ инструктажа' ;
ALTER TABLE `amicum3`.`order_item_instruction_pb`
    ADD CONSTRAINT `fkey_order_item_instruction_pb_instruction_pb`
        FOREIGN KEY (`instruction_pb_id`)
            REFERENCES `amicum3`.`instruction_pb` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE;

ALTER TABLE `amicum3`.`order_history`
    ADD INDEX `order_hystory_idx` (`order_id` ASC, `date_time_create` DESC) VISIBLE;
ALTER TABLE `amicum3`.`order_history` ALTER INDEX `fkey_order_history_order_worker` INVISIBLE;

ALTER TABLE `amicum3`.`order_item`
    ADD COLUMN `chat_room_id` INT NULL COMMENT 'ключ чата, для работы с отчетом, вложениями, видео, аудио' AFTER `order_type_id`;

ALTER TABLE `amicum3`.`order_item`
    ADD COLUMN `passport_id` INT(11) NULL DEFAULT NULL COMMENT 'паспорт ведения работ' AFTER `chat_room_id`,
    ADD COLUMN `route_template_id` INT(11) NULL DEFAULT NULL COMMENT 'ключ шаблона маршрута' AFTER `passport_id`,
    ADD COLUMN `order_route_json` JSON NULL COMMENT 'наряд путевка горного мастера АБ/ВТБ' AFTER `route_template_id`,
    ADD COLUMN `order_route_esp_json` JSON NULL DEFAULT NULL COMMENT 'наряд путевка электрослесарей АБ' AFTER `order_route_json`,
    ADD INDEX `fkey_order_item_chat_room_idx` (`chat_room_id` ASC) VISIBLE,
    ADD INDEX `fkey_order_item_passport_idx` (`passport_id` ASC) VISIBLE,
    ADD INDEX `fkey_order_item_route_template_idx` (`route_template_id` ASC) VISIBLE;
;
ALTER TABLE `amicum3`.`order_item`
    ADD CONSTRAINT `fkey_order_item_chat_room`
        FOREIGN KEY (`chat_room_id`)
            REFERENCES `amicum3`.`chat_room` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    ADD CONSTRAINT `fkey_order_item_passport`
        FOREIGN KEY (`passport_id`)
            REFERENCES `amicum3`.`passport` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE,
    ADD CONSTRAINT `fkey_order_item_route_template`
        FOREIGN KEY (`route_template_id`)
            REFERENCES `amicum3`.`route_template` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE;

ALTER TABLE `amicum3`.`order_item_worker`
    ADD COLUMN `reason_status_id` INT NULL COMMENT 'ключ статуса работника по выходу из шахты ' AFTER `workers_json`,
    ADD COLUMN `reason_descriprion` VARCHAR(255) NULL COMMENT 'Описание причины того, что работник остался на вторую смену' AFTER `reason_status_id`,
    ADD INDEX `fkey_order_item_worker_status_idx` (`reason_status_id` ASC) VISIBLE;
;
ALTER TABLE `amicum3`.`order_item_worker`
    ADD CONSTRAINT `fkey_order_item_worker_status`
        FOREIGN KEY (`reason_status_id`)
            REFERENCES `amicum3`.`status` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE;

ALTER TABLE `amicum3`.`order_item_worker`
    CHANGE COLUMN `reason_descriprion` `reason_description` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Описание причины того, что работник остался на вторую смену' ;

CREATE TABLE IF NOT EXISTS `amicum3`.`order_item_worker_vgk` (
                                                                 `id` INT(11) NOT NULL AUTO_INCREMENT,
                                                                 `order_history_id` INT(11) UNSIGNED NOT NULL COMMENT 'ключ истории наряда',
                                                                 `worker_id` INT(11) NOT NULL COMMENT 'внешний идентификатор работника ВГК',
                                                                 `role_id` INT(11) NOT NULL COMMENT 'ключ роли',
                                                                 `vgk` TINYINT(1) NOT NULL COMMENT 'Принадлежность к ВГК',
                                                                 PRIMARY KEY (`id`),
                                                                 INDEX `fk_order_item_worker_vgk_worker_idx` (`worker_id` ASC) VISIBLE,
                                                                 INDEX `fk_order_item_worker_vgk_order0_idx` (`order_history_id` ASC) VISIBLE,
                                                                 INDEX `fk_order_item_worker_vgk_role_idx` (`role_id` ASC) VISIBLE,
                                                                 CONSTRAINT `fk_order_item_worker_vgk_order_history`
                                                                     FOREIGN KEY (`order_history_id`)
                                                                         REFERENCES `amicum3`.`order_history` (`id`)
                                                                         ON DELETE CASCADE
                                                                         ON UPDATE CASCADE,
                                                                 CONSTRAINT `fk_order_item_worker_vgk_worker`
                                                                     FOREIGN KEY (`worker_id`)
                                                                         REFERENCES `amicum3`.`worker` (`id`)
                                                                         ON DELETE CASCADE
                                                                         ON UPDATE CASCADE,
                                                                 CONSTRAINT `fk_order_item_worker_vgk_role`
                                                                     FOREIGN KEY (`role_id`)
                                                                         REFERENCES `amicum3`.`role` (`id`)
                                                                         ON DELETE RESTRICT
                                                                         ON UPDATE CASCADE
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 2
    DEFAULT CHARACTER SET = utf8mb3
    COMMENT = 'ВГК в наряде атомарном';

INSERT INTO `amicum3`.`status_type` (`id`, `title`)
VALUES ('20', 'Статусы оборудования в наряде');

INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('140', 'Исправно', '-', '20');
INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('141', 'В резерве', '-', '20');
INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('142', 'В ремонте', '-', '20');


ALTER TABLE `amicum3`.`storage`
    ADD COLUMN `date_work` DATE NULL COMMENT 'производственная дата' AFTER `description`;

INSERT INTO `amicum3`.`status_type` (`id`, `title`)
VALUES ('21', 'Статусы отчет горного мастера');
INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('143', 'Оставлен на вторую смену', '-', '21');
INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('144', 'Вышел на поверхность', '-', '21');
INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('145', 'Другое', '-', '21');
INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('146', 'В шахте/на смене', '-', '21');

UPDATE `amicum3`.`chat_attachment_type`
SET `title` = 'Цитата'
WHERE (`id` = '5');
UPDATE `amicum3`.`chat_attachment_type`
SET `title` = 'Аудио'
WHERE (`id` = '3');
UPDATE `amicum3`.`chat_attachment_type`
SET `title` = 'Видео'
WHERE (`id` = '2');
UPDATE `amicum3`.`chat_attachment_type`
SET `title` = 'Файл'
WHERE (`id` = '4');

/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

# применено 09.08.2023

CREATE TABLE `amicum3`.`document_with_ecp` (
                                               `id` INT NOT NULL AUTO_INCREMENT,
                                               `document_id` INT NOT NULL,
                                               `signed_data` BLOB NOT NULL,
                                               `signature` BLOB NOT NULL,
                                               PRIMARY KEY (`id`),
                                               INDEX `fk_document_id_idx` (`document_id` ASC) VISIBLE,
                                               CONSTRAINT `fk_document_id`
                                                   FOREIGN KEY (`document_id`)
                                                       REFERENCES `amicum3`.`document` (`id`)
                                                       ON DELETE CASCADE
                                                       ON UPDATE CASCADE
);

INSERT INTO `amicum3`.`vid_document` (`id`, `title`)
VALUES ('22', 'ЭЦП');

#25.08.2023
ALTER TABLE `amicum3`.`document_with_ecp`
    CHANGE COLUMN `signed_data` `signed_data` LONGBLOB NOT NULL;
# применено 05.09.2023

ALTER TABLE `amicum3`.`document`
    CHANGE COLUMN `jsondoc` `jsondoc` LONGTEXT NULL DEFAULT NULL COMMENT 'сериализованная строка - хранит наполнение документа';

# применено 06.09.2023
ALTER TABLE `amicum3`.`injunction_violation_status`
    ADD COLUMN `worker_id` INT NOT NULL COMMENT 'Внешний ключ работника' AFTER `date_time`,
    ADD INDEX `fk_injunction_violation_status_worker_idx` (`worker_id` ASC) VISIBLE;
ALTER TABLE `amicum3`.`injunction_violation_status`
    ALTER INDEX `fk_injunction_violation_status_stauts_idx` INVISIBLE;
ALTER TABLE `amicum3`.`injunction_violation_status`
    CHANGE COLUMN `worker_id` `worker_id` INT NULL COMMENT 'Внешний ключ работника';

# применено 19.09.2023
ALTER TABLE `amicum3`.`injunction_status`
    DROP INDEX `index_date_time`,
    ADD INDEX `index_date_time` (`injunction_id` ASC, `date_time` ASC) VISIBLE;
;

ALTER TABLE `amicum3`.`order_template_operation`
    DROP FOREIGN KEY `fkey_order_template_place`;

ALTER TABLE `amicum3`.`order_template_place`
    DROP FOREIGN KEY `fkey_order_template`;
ALTER TABLE `amicum3`.`order_template_place`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`id`);
;

ALTER TABLE `amicum3`.`order_template_place`
    ADD CONSTRAINT `fkey_order_template0`
        FOREIGN KEY (`order_template_id`)
            REFERENCES `amicum3`.`order_template` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;

ALTER TABLE `amicum3`.`order_template_place`
    DROP FOREIGN KEY `fkey_place0`;

ALTER TABLE `amicum3`.`order_template_place`
    ADD COLUMN `place_from_id` INT NULL COMMENT 'внешний ключ справочника мест из которого едут' AFTER `route_template_id`,
    ADD COLUMN `place_to_id`   INT NULL COMMENT 'внешний ключ справочника мест в которое едут' AFTER `place_from_id`,
    CHANGE COLUMN `place_id` `place_id` INT NULL COMMENT 'внешний ключ справочника мест',
    ADD INDEX `fkey_template_place_to_idx` (`place_to_id` ASC) VISIBLE,
    ADD INDEX `fkey_template_place_from_idx` (`place_from_id` ASC) VISIBLE;
;
ALTER TABLE `amicum3`.`order_template_place`
    ADD CONSTRAINT `fkey_place0`
        FOREIGN KEY (`place_id`)
            REFERENCES `amicum3`.`place` (`id`),
    ADD CONSTRAINT `fkey_template_place_from`
        FOREIGN KEY (`place_from_id`)
            REFERENCES `amicum3`.`place` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE,
    ADD CONSTRAINT `fkey_template_place_to`
        FOREIGN KEY (`place_to_id`)
            REFERENCES `amicum3`.`place` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE;

ALTER TABLE `amicum3`.`order_template_operation`
    ADD CONSTRAINT `fkey_order_template_place`
        FOREIGN KEY (`order_template_place_id`)
            REFERENCES `amicum3`.`order_template_place` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;

/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

# применено ЮК 11.10.2023


ALTER TABLE `amicum3`.`order_place`
    ADD COLUMN `place_from_id` INT NULL DEFAULT NULL COMMENT 'внешний ключ справочника мест из которого едут' AFTER `description`,
    ADD COLUMN `place_to_id` INT NULL DEFAULT NULL COMMENT 'внешний ключ справочника мест в которое едут' AFTER `place_from_id`,
    ADD INDEX `fkey_order_place_to_idx` (`place_to_id` ASC) INVISIBLE,
    ADD INDEX `fkey_order_place_from_idx` (`place_from_id` ASC) VISIBLE;
;
ALTER TABLE `amicum3`.`order_place`
    ADD CONSTRAINT `fkey_order_place_to_idx`
        FOREIGN KEY (`place_to_id`)
            REFERENCES `amicum3`.`place` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    ADD CONSTRAINT `fkey_order_place_from_idx`
        FOREIGN KEY (`place_from_id`)
            REFERENCES `amicum3`.`place` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;


CREATE TABLE `amicum3`.`order_json` (
                                        `id` INT NOT NULL AUTO_INCREMENT,
                                        `order_id` INT NOT NULL,
                                        `brigadeChaneWorker` MEDIUMTEXT NULL DEFAULT NULL,
                                        PRIMARY KEY (`id`),
                                        CONSTRAINT `fkey_order_json_order`
                                            FOREIGN KEY (`id`)
                                                REFERENCES `amicum3`.`order` (`id`)
                                                ON DELETE CASCADE
                                                ON UPDATE CASCADE
)
    COMMENT = 'таблица хранения json по нарядам';

insert into order_json (SELECT id, id as order_id, brigadeChaneWorker FROM amicum3.order);

update `order`
set brigadeChaneWorker=null;

# применено ЮК 16.11.2023