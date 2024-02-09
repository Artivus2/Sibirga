/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

#  Скрипт по созданию схемы режимов работы

CREATE TABLE IF NOT EXISTS `amicum3`.`type_work_mode`
(
    `id`    INT          NOT NULL AUTO_INCREMENT COMMENT 'ключ справочника типа режима работы',
    `title` VARCHAR(255) NOT NULL COMMENT 'Название типа режима работы',
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    COMMENT = 'Справочник режимов работы';

CREATE TABLE IF NOT EXISTS `amicum3`.`work_mode`
(
    `id`                INT          NOT NULL AUTO_INCREMENT COMMENT 'ключ справочника режимов работы',
    `title`             VARCHAR(255) NOT NULL COMMENT 'Название режима работы',
    `type_work_mode_id` INT          NOT NULL COMMENT 'ключ типа режима работы (праздничный/предпраздничный/рабочий)',
    `count_hours`       FLOAT        NOT NULL COMMENT 'Количество часов в режиме работы',
    PRIMARY KEY (`id`),
    INDEX `ал_work_mode_type_work_mode_id_idx` (`type_work_mode_id` ASC) VISIBLE,
    CONSTRAINT `ал_work_mode_type_work_mode_id`
        FOREIGN KEY (`type_work_mode_id`)
            REFERENCES `amicum3`.`type_work_mode` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    ENGINE = InnoDB
    COMMENT = 'Справочник типов режимов работы (праздничный/предпраздничный/рабочий)';

CREATE TABLE IF NOT EXISTS `amicum3`.`work_mode_shift`
(
    `id`            INT(11)    NOT NULL AUTO_INCREMENT,
    `work_mode_id`  INT(11)    NOT NULL,
    `time_start`    TIME       NOT NULL,
    `time_end`      TIME       NOT NULL,
    `shift_type_id` TINYINT(4) NOT NULL,
    `shift_id`      INT        NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_work_mode_shift_type_id_idx` (`shift_type_id` ASC) VISIBLE,
    INDEX `fk_work_mode_shift_id_idx` (`shift_id` ASC) VISIBLE,
    INDEX `fk_work_mode_work_mode_id_idx` (`work_mode_id` ASC) VISIBLE,
    CONSTRAINT `fk_work_mode_shift_type_id`
        FOREIGN KEY (`shift_type_id`)
            REFERENCES `amicum3`.`shift_type` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_work_mode_shift_id`
        FOREIGN KEY (`shift_id`)
            REFERENCES `amicum3`.`shift` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_work_mode_work_mode_id`
        FOREIGN KEY (`work_mode_id`)
            REFERENCES `amicum3`.`work_mode` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 85
    DEFAULT CHARACTER SET = utf8mb3
    COMMENT = 'Графики смен режимов работы';

CREATE TABLE IF NOT EXISTS `amicum3`.`work_mode_worker`
(
    `id`                INT(11)  NOT NULL AUTO_INCREMENT COMMENT 'ключ связки режима работы и компании',
    `status_id`         INT      NOT NULL DEFAULT 1 COMMENT 'Статус режима работы (действует или нет1/19)',
    `date_time_start`   DATETIME NOT NULL COMMENT 'дата, с которой действует режим работы',
    `date_time_end`     DATETIME NULL COMMENT 'дата по который действует режим работы',
    `worker_id`         INT(11)  NOT NULL COMMENT 'ключ компании, на который распространяется режим работы',
    `work_mode_id`      INT      NOT NULL COMMENT 'ключ режима работы',
    `creater_worker_id` INT      NOT NULL,
    PRIMARY KEY (`date_time_start`, `worker_id`, `work_mode_id`),
    INDEX `fk_work_mode_worker_idx` (`worker_id` ASC) VISIBLE,
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    INDEX `fk_work_mode_creater_worker_id_idx` (`creater_worker_id` ASC) VISIBLE,
    CONSTRAINT `fk_work_mode_worker`
        FOREIGN KEY (`worker_id`)
            REFERENCES `amicum3`.`worker` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_work_mode_worker_id`
        FOREIGN KEY (`work_mode_id`)
            REFERENCES `amicum3`.`work_mode` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_work_mode_creater_worker_id`
        FOREIGN KEY (`creater_worker_id`)
            REFERENCES `amicum3`.`worker` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 956
    DEFAULT CHARACTER SET = utf8mb3
    COMMENT = 'Привязка режима работы к подразделению';

CREATE TABLE IF NOT EXISTS `amicum3`.`work_mode_company`
(
    `id`                INT(11)  NOT NULL AUTO_INCREMENT COMMENT 'ключ связки режима работы и компании',
    `status_id`         INT      NOT NULL DEFAULT 1 COMMENT 'Статус режима работы (действует или нет1/19)',
    `date_time_start`   DATETIME NOT NULL COMMENT 'дата, с которой действует режим работы',
    `date_time_end`     DATETIME NULL COMMENT 'дата по который действует режим работы',
    `company_id`        INT(11)  NOT NULL COMMENT 'ключ компании, на который распространяется режим работы',
    `work_mode_id`      INT      NOT NULL COMMENT 'ключ режима работы',
    `creater_worker_id` INT      NOT NULL,
    PRIMARY KEY (`date_time_start`, `company_id`, `work_mode_id`),
    INDEX `fk_work_mode_company_idx` (`company_id` ASC) VISIBLE,
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    INDEX `fk_work_mode_creater_worker_id_idx` (`creater_worker_id` ASC) VISIBLE,
    CONSTRAINT `fk_work_mode_company`
        FOREIGN KEY (`company_id`)
            REFERENCES `amicum3`.`company` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_work_mode_company_id`
        FOREIGN KEY (`work_mode_id`)
            REFERENCES `amicum3`.`work_mode` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_work_mode_company_creater_worker_id`
        FOREIGN KEY (`creater_worker_id`)
            REFERENCES `amicum3`.`worker` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    ENGINE = InnoDB
    AUTO_INCREMENT = 956
    DEFAULT CHARACTER SET = utf8mb3
    COMMENT = 'Привязка режима работы к подразделению';


INSERT INTO `amicum3`.`type_work_mode` (`id`, `title`)
VALUES ('1', 'Рабочий');
INSERT INTO `amicum3`.`type_work_mode` (`id`, `title`)
VALUES ('2', 'Предпраздничный');
INSERT INTO `amicum3`.`type_work_mode` (`id`, `title`)
VALUES ('3', 'Праздничный');

INSERT INTO `amicum3`.`work_mode` (`id`, `title`, `type_work_mode_id`, `count_hours`)
VALUES ('1', 'Четырехсменка', '1', '6');
INSERT INTO `amicum3`.`work_mode` (`id`, `title`, `type_work_mode_id`, `count_hours`)
VALUES ('2', 'Односменка', '1', '12');
INSERT INTO `amicum3`.`work_mode` (`id`, `title`, `type_work_mode_id`, `count_hours`)
VALUES ('3', 'Трехсменка', '1', '8');
INSERT INTO `amicum3`.`work_mode` (`id`, `title`, `type_work_mode_id`, `count_hours`)
VALUES ('4', 'ТрехсменкаПредпраз', '2', '7');

INSERT INTO `amicum3`.`work_mode_shift` (`id`, `work_mode_id`, `time_start`, `time_end`, `shift_type_id`, `shift_id`)
VALUES ('1', '1', '08:00:00', '14:00:00', '1', '1');
INSERT INTO `amicum3`.`work_mode_shift` (`id`, `work_mode_id`, `time_start`, `time_end`, `shift_type_id`, `shift_id`)
VALUES ('2', '1', '14:00:00', '20:00:00', '2', '2');
INSERT INTO `amicum3`.`work_mode_shift` (`id`, `work_mode_id`, `time_start`, `time_end`, `shift_type_id`, `shift_id`)
VALUES ('3', '1', '20:00:00', '02:00:00', '2', '3');
INSERT INTO `amicum3`.`work_mode_shift` (`id`, `work_mode_id`, `time_start`, `time_end`, `shift_type_id`, `shift_id`)
VALUES ('4', '1', '02:00:00', '08:00:00', '2', '4');

INSERT INTO `amicum3`.`work_mode_worker` (`status_id`, `date_time_start`, `date_time_end`, `worker_id`, `work_mode_id`,
                                          `creater_worker_id`)
VALUES ('1', '2021-07-21', '2021-07-31', '1', '1', '1');

INSERT INTO `amicum3`.`work_mode_company` (`status_id`, `date_time_start`, `date_time_end`, `company_id`,
                                           `work_mode_id`, `creater_worker_id`)
VALUES ('1', '2021-07-01', '2021-07-31', '501', '1', '1');

ALTER TABLE `amicum3`.`work_mode_shift`
    ADD UNIQUE INDEX `shift_id_UNIQUE` (`shift_id` ASC, `work_mode_id` ASC, `time_start` ASC) VISIBLE;
;

CREATE TABLE `amicum3`.`prod_graphic_work`
(
    `id`                  INT         NOT NULL,
    `year`                YEAR        NULL COMMENT 'год',
    `1`                   VARCHAR(45) NULL COMMENT 'январь',
    `2`                   VARCHAR(45) NULL COMMENT 'февраль',
    `3`                   VARCHAR(45) NULL COMMENT 'март',
    `4`                   VARCHAR(45) NULL COMMENT 'апрель',
    `5`                   VARCHAR(45) NULL COMMENT 'май',
    `6`                   VARCHAR(45) NULL COMMENT 'июнь',
    `7`                   VARCHAR(45) NULL COMMENT 'июль',
    `8`                   VARCHAR(45) NULL COMMENT 'август',
    `9`                   VARCHAR(45) NULL COMMENT 'сентябрь',
    `10`                  VARCHAR(45) NULL COMMENT 'октябрь',
    `11`                  VARCHAR(45) NULL COMMENT 'ноябрь',
    `12`                  VARCHAR(45) NULL COMMENT 'декабрь',
    `all_work_day`        INT         NULL COMMENT 'Всего рабочих дней',
    `all_week_end`        INT         NULL COMMENT 'Всего праздничных и выходных дней',
    `count_work_hours_40` INT         NULL COMMENT 'Количество рабочих часов при 40-часовой рабочей неделе',
    `count_work_hours_36` INT         NULL COMMENT 'Количество рабочих часов при 36-часовой рабочей неделе',
    `count_work_hours_24` INT         NULL COMMENT 'Количество рабочих часов при 24-часовой рабочей неделе',
    PRIMARY KEY (`id`)
)
    COMMENT = 'произовдственный график';

INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('1', 1999, '1,2,3,4,6*,7,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28', '6,7,8,13,14,20,21,27,28',
        '3,4,10,11,17,18,24,25,30*', '1,2,3,4,8,9,10,15,16,22,23,29,30', '5,6,11*,12,13,14,19,20,26,27',
        '3,4,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,25,26', '2,3,9,10,16,17,23,24,30,31',
        '6,7,8,13,14,20,21,27,28', '4,5,11,12,13,18,19,25,26,31*', '251', '114', '2004', '1807.2', '1204.8');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('2', 2000, '1,2,3,4,6*,7,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,26,27', '4,5,7*,8,11,12,18,19,25,26',
        '1,2,8,9,15,16,22,23,29,30', '1,2,6,7,8*,9,13,14,20,21,27,28', '3,4,10,11,12,17,18,24,25',
        '1,2,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30', '1,7,8,14,15,21,22,28,29',
        '4,5,7,11,12,18,19,25,26', '2,3,9,10,11*,12,16,17,23,24,30,31', '250', '116', '1995', '1800', '1200');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('3', 2001, '1,2,6,7,8,13,14,20,21,27,28', '3,4,10,11,17,18,24,25', '3,4,7*,8,10,11,17,18,24,25,31',
        '1,7,8,14,15,21,22,28,29,30*', '1,2,5,6,8*,9,12,13,19,20,26,27', '2,3,9,10,11*,12,16,17,23,24,30',
        '1,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,29,30', '6,7,13,14,20,21,27,28',
        '3,4,6*,7,10,11,17,18,24,25', '1,2,8,9,12,15,16,22,23,29,30', '251', '114', '2001', '1807.2', '1204.8');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('4', 2002, '1,2,5,6,7,12,13,19,20,26,27', '2,3,9,10,16,17,22*,23,24,25', '2,3,7*,8,9,10,16,17,23,24,30,31',
        '6,7,13,14,20,21,28,30*', '1,2,3,4,5,8*,9,10,11,12,19,25,26', '1,2,8,9,11*,12,15,16,22,23,29,30',
        '6,7,13,14,20,21,27,28', '3,4,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,28,29', '5,6,12,13,19,20,26,27',
        '2,3,6*,7,8,9,16,17,23,24,30', '1,7,8,11*,12,13,14,21,22,28,29,31*', '250', '115', '1992', '1792', '1192');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('5', 2003, '1,2,3,5*,6,7,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,24', '1,2,7*,8,9,10,15,16,22,23,29,30',
        '5,6,12,13,19,20,26,27,30*', '1,2,3,4,8*,9,10,11,17,18,24,25,31', '1,7,8,11*,12,13,14,15,22,28,29',
        '5,6,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28', '4,5,11,12,18,19,25,26',
        '1,2,6*,7,8,9,15,16,22,23,29,30', '6,7,11*,12,13,14,20,21,27,28,31*', '250', '115', '1992', '1792', '1192');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('6', 2004, '1,2,3,4,6*,7,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,23,28,29', '6,7,8,13,14,20,21,27,28',
        '3,4,10,11,17,18,24,25,30*', '1,2,3,4,8,9,10,15,16,22,23,29,30', '5,6,11*,12,13,14,19,20,26,27',
        '3,4,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,25,26', '2,3,9,10,16,17,23,24,30,31',
        '6,7,8,13,14,20,21,27,28', '4,5,11,12,13,18,19,25,26,31*', '251', '115', '2004', '1803.2', '1200.8');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('7', 2005, '1,2,3,4,5,6,7,8,9,10,15,16,22,23,29,30', '5,6,12,13,19,20,22*,23,26,27',
        '5*,6,7,8,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30', '1,2,7,8,9,14,15,21,22,28,29',
        '4,5,11,12,13,18,19,25,26', '2,3,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28', '3,4,10,11,17,18,24,25',
        '1,2,8,9,15,16,22,23,29,30', '3*,4,5,6,12,13,19,20,26,27', '3,4,10,11,17,18,24,25,31', '248', '117', '1981',
        '1782.6', '1187.4');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('8', 2006, '1,2,3,4,5,6,7,8,9,14,15,21,22,28,29', '4,5,11,12,18,19,22*,23,24,25', '4,5,7*,8,11,12,18,19,25,26',
        '1,2,8,9,15,16,22,23,29,30', '1,6*,7,8,9,13,14,20,21,27,28', '3,4,10,11,12,17,18,24,25',
        '1,2,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30', '1,7,8,14,15,21,22,28,29',
        '3*,4,5,6,11,12,18,19,25,26', '2,3,9,10,16,17,23,24,30,31', '248', '117', '1981', '1782.6', '1187.4');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('9', 2007, '1,2,3,4,5,6,7,8,13,14,20,21,27,28', '3,4,10,11,17,18,22*,23,24,25', '3,4,7*,8,10,11,17,18,24,25,31',
        '1,7,8,14,15,21,22,28*,29,30', '1,5,6,8*,9,12,13,19,20,26,27', '2,3,9*,10,11,12,16,17,23,24,30',
        '1,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,29,30', '6,7,13,14,20,21,27,28',
        '3,4,5,10,11,17,18,24,25', '1,2,8,9,15,16,22,23,29*,30,31', '249', '116', '1986', '1786.8', '1189.2');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('10', 2008, '1,2,3,4,5,6,7,8,12,13,19,20,26,27', '2,3,9,10,16,17,22*,23,24,25',
        '1,2,7*,8,9,10,15,16,22,23,29,30', '5,6,12,13,19,20,26,27,30*', '1,2,3,8*,9,10,11,17,18,24,25,31',
        '1,8,11*,12,13,14,15,21,22,28,29', '5,6,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30,31',
        '6,7,13,14,20,21,27,28', '4,5,11,12,18,19,25,26', '1*,2,3,4,8,9,15,16,22,23,29,30', '6,7,13,14,20,21,27,28,31*',
        '250', '116', '1993', '1793', '1193');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('11', 2009, '1,2,3,4,5,6,7,8,9,10,17,18,24,25,31', '1,7,8,14,15,21,22,23,28', '1,7,8,9,14,15,21,22,28,29',
        '4,5,11,12,18,19,25,26', '1,2,3,8*,9,10,11,16,17,23,24,30,31', '6,7,11*,12,13,14,20,21,27,28',
        '4,5,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,26,27', '3,4,10,11,17,18,24,25,31',
        '1,3*,4,7,8,14,15,21,22,28,29', '5,6,12,13,19,20,26,27,31*', '249', '116', '1987', '1787.8', '1190.2');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('12', 2010, '1,2,3,4,5,6,7,8,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,22,23,27*,28', '6,7,8,13,14,20,21,27,28',
        '3,4,10,11,17,18,24,25,30*', '1,2,3,8,9,10,15,16,22,23,29,30', '5,6,11*,12,13,14,19,20,26,27',
        '3,4,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,25,26', '2,3,9,10,16,17,23,24,30,31',
        '3*,4,5,6,7,14,20,21,27,28', '4,5,11,12,18,19,25,26,31*', '249', '116', '1987', '1787.8', '1190.2');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('13', 2011, '1,2,3,4,5,6,7,8,9,10,15,16,22,23,29,30', '5,6,12,13,19,20,22*,23,26,27',
        '5*,6,7,8,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30', '1,2,7,8,9,14,15,21,22,28,29',
        '4,5,11,12,13,18,19,25,26', '2,3,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28', '3,4,10,11,17,18,24,25',
        '1,2,8,9,15,16,22,23,29,30', '3*,4,5,6,12,13,19,20,26,27', '3,4,10,11,17,18,24,25,31', '248', '117', '1981',
        '1782.6', '1187.4');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('14', 2012, '1,2,3,4,5,6,7,8,9,14,15,21,22,28,29', '4,5,11,12,18,19,22*,23,25,26',
        '3,4,7*,8,9,10,17,18,24,25,31', '1,7,8,14,15,21,22,28*,29,30', '1,6,7,8,9,12*,13,19,20,26,27',
        '2,3,9*,10,11,12,16,17,23,24,30', '1,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,25,26',
        '1,2,8,9,15,16,22,23,29,30', '6,7,13,14,20,21,27,28', '3,4,5,10,11,17,18,24,25',
        '1,2,8,9,15,16,22,23,29*,30,31', '249', '117', '1986', '1786.8', '1189.2');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('15', 2013, '1,2,3,4,5,6,7,8,12,13,19,20,26,27', '2,3,9,10,16,17,22*,23,24', '2,3,7*,8,9,10,16,17,23,24,30,31',
        '6,7,13,14,20,21,27,28', '1,2,3,4,5,8*,9,10,11,12,18,19,25,26', '1,2,8,9,11*,12,15,16,22,23,29,30',
        '6,7,13,14,20,21,27,28', '3,4,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,28,29', '5,6,12,13,19,20,26,27',
        '2,3,4,9,10,16,17,23,24,30', '1,7,8,14,15,21,22,28,29,31*', '247', '118', '1970', '1772.4', '1179.6');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('16', 2014, '1,2,3,4,5,6,7,8,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,24*', '1,2,7*,8,9,10,15,16,22,23,29,30',
        '5,6,12,13,19,20,26,27,30*', '1,2,3,4,8*,9,10,11,17,18,24,25,31', '1,7,8,11*,12,13,14,15,21,22,28,29',
        '5,6,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28', '4,5,11,12,18,19,25,26',
        '1,2,3,4,8,9,15,16,22,23,29,30', '6,7,13,14,20,21,27,28', '247', '118', '1970', '1772.4', '1179.6');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('17', 2015, '1,2,3,4,5,6,7,8,9,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,23,28', '1,7,8,9,14,15,21,22,28,29',
        '4,5,11,12,18,19,25,26,30*', '1,2,3,4,8*,9,10,11,16,17,23,24,30,31', '6,7,11*,12,13,14,20,21,27,28',
        '4,5,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,26,27', '3,4,10,11,17,18,24,25,31',
        '1,3*,4,7,8,14,15,21,22,28,29', '5,6,12,13,19,20,26,27,31*', '247', '118', '1971', '1773.4', '1180.6');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('18', 2016, '1,2,3,4,5,6,7,8,9,10,16,17,23,24,30,31', '6,7,13,14,20*,21,22,23,27,28',
        '5,6,7,8,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30', '1,2,3,7,8,9,14,15,21,22,28,29',
        '4,5,11,12,13,18,19,25,26', '2,3,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28', '3,4,10,11,17,18,24,25',
        '1,2,8,9,15,16,22,23,29,30', '3*,4,5,6,12,13,19,20,26,27', '3,4,10,11,17,18,24,25,31', '247', '119', '1974',
        '1776.4', '1183.6');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('19', 2017, '1,2,3,4,5,6,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,22*,23,24,25,26',
        '4,5,7*,8,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,29,30', '1,6,7,8,9,13,14,20,21,27,28',
        '3,4,10,11,12,17,18,24,25', '1,2,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30',
        '1,7,8,14,15,21,22,28,29', '3*,4,5,6,11,12,18,19,25,26', '2,3,9,10,16,17,23,24,30,31', '247', '118', '1973',
        '1775.4', '1182.6');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('20', 2018, '1,2,3,4,5,6,7,8,13,14,20,21,27,28', '3,4,10,11,17,18,22*,23,24,25',
        '3,4,7*,8,9,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,28*,29,30', '1,2,5,6,8*,9,12,13,19,20,26,27',
        '2,3,9*,10,11,12,16,17,23,24,30', '1,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,25,26',
        '1,2,8,9,15,16,22,23,29,30', '6,7,13,14,20,21,27,28', '3,4,5,10,11,17,18,24,25',
        '1,2,8,9,15,16,22,23,29*,30,31', '247', '118', '1970', '1772.4', '1179.6');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('21', 2019, '1,2,3,4,5,6,7,8,12,13,19,20,26,27', '2,3,9,10,16,17,22*,23,24', '2,3,7*,8,9,10,16,17,23,24,30,31',
        '6,7,13,14,20,21,27,28,30*', '1,2,3,4,5,8*,9,10,11,12,18,19,25,26', '1,2,8,9,11*,12,15,16,22,23,29,30',
        '6,7,13,14,20,21,27,28', '3,4,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,28,29', '5,6,12,13,19,20,26,27',
        '2,3,4,9,10,16,17,23,24,30', '1,7,8,14,15,21,22,28,29,31*', '247', '118', '1970', '1772.4', '1179.6');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('22', 2020, '1,2,3,4,5,6,7,8,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,24+,29', '1,7,8,9+,14,15,21,22,28,29',
        '4,5,11,12,18,19,25,26,30*', '1,2,3,4+,5+,8*,9,10,11+,16,17,23,24,30,31', '6,7,11*,12,13,14,20,21,27,28',
        '4,5,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,26,27', '3,4,10,11,17,18,24,25,31',
        '1,3*,4,7,8,14,15,21,22,28,29', '5,6,12,13,19,20,26,27,31*', '248', '118', '1979', '1780.6', '1185.4');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('23', 2021, '1,2,3,4,5,6,7,8,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,22*,23,27,28',
        '6,7,8*,13,14,20,21,27,28', '3,4,10,11,17,18,24,25,30*', '1,2,3+,8,9,10+,15,16,22,23,29,30',
        '5,6,11*,12,13,14+,19,20,26,27', '3,4,10,11,17,18,24,25,31', '1,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,25,26',
        '2,3,9,10,16,17,23,24,30,31', '3*,4,6,7,13,14,20,21,27,28', '4,5,11,12,18,19,25,26,31*', '249', '116', '1987',
        '1787.8', '1190.2');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('24', 2022, '1,2,3,4,5,6,7,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,22*,23,26,27', '5,6,7*,8,12,13,19,20,26,27',
        '2,3,9,10,16,17,23,24,30', '1,2+,7,8,9,14,15,21,22,28,29', '4,5,11,12,13+,18,19,25,26',
        '2,3,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28', '3,4,10,11,17,18,24,25', '1,2,8,9,15,16,22,23,29,30',
        '3*,4,5,6,12,13,19,20,26,27', '3,4,10,11,17,18,24,25,31', '249', '116', '1989', '1789.8', '1192.2');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('25', 2023, '1,2,3,4,5,6,7,8,14,15,21,22,28,29', '4,5,11,12,18,19,22*,23,25,26', '4,5,7*,8,11,12,18,19,25,26',
        '1,2,8,9,15,16,22,23,29,30', '1,6,7,8*,9,13,14,20,21,27,28', '3,4,10,11,12,17,18,24,25',
        '1,2,8,9,15,16,22,23,29,30', '5,6,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30', '1,7,8,14,15,21,22,28,29',
        '3*,4,5,6+,11,12,18,19,25,26', '2,3,9,10,16,17,23,24,30,31', '249', '116', '1988', '1788.8', '1191.6');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('26', 2024, '1,2,3,4,5,6,7,8,13,14,20,21,27,28', '3,4,10,11,17,18,22*,23,24,25',
        '2,3,7*,8,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28,30*', '1,4,5,8*,9,11,12,18,19,25,26',
        '1,2,8,9,11*,12,15,16,22,23,29,30', '6,7,13,14,20,21,27,28', '3,4,10,11,17,18,24,25,31',
        '1,7,8,14,15,21,22,28,29', '5,6,12,13,19,20,26,27', '2,3,4,9,10,16,17,23,24,30', '1,7,8,14,15,21,22,28,29,31*',
        '250', '116', '1994', '1794', '1194');
INSERT INTO `amicum3`.`prod_graphic_work` (`id`, `year`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`,
                                           `all_work_day`, `all_week_end`, `count_work_hours_40`, `count_work_hours_36`,
                                           `count_work_hours_24`)
VALUES ('27', 2025, '1,2,3,4,5,6,7,8,11,12,18,19,25,26', '1,2,8,9,15,16,22,23,24+', '1,2,7*,8,9,10+,15,16,22,23,29,30',
        '5,6,12,13,19,20,26,27,30*', '1,3,4,8*,9,10,11,17,18,24,25,31', '1,7,8,11*,12,14,15,21,22,28,29',
        '5,6,12,13,19,20,26,27', '2,3,9,10,16,17,23,24,30,31', '6,7,13,14,20,21,27,28', '4,5,11,12,18,19,25,26',
        '1,2,3*,4,8,9,15,16,22,23,29,30', '6,7,13,14,20,21,27,28,31*', '249', '116', '1986', '1786.8', '1189.2');

ALTER TABLE `amicum3`.`work_mode_worker`
    CHANGE COLUMN `date_time_start` `date_time_start` DATE NOT NULL COMMENT 'дата, с которой действует режим работы' ,
    CHANGE COLUMN `date_time_end` `date_time_end` DATE NULL DEFAULT NULL COMMENT 'дата по который действует режим работы' ;

ALTER TABLE `amicum3`.`work_mode_worker`
    ADD COLUMN `date_time_create` DATETIME NULL COMMENT 'Дата и время установки режима работы' AFTER `creater_worker_id`;
ALTER TABLE `amicum3`.`work_mode_worker`
    CHANGE COLUMN `date_time_create` `date_time_create` DATETIME NOT NULL COMMENT 'Дата и время установки режима работы';

ALTER TABLE `amicum3`.`work_mode_company`
    ADD COLUMN `date_time_create` DATETIME NULL COMMENT 'Дата и время установки режима работы' AFTER `creater_worker_id`;

ALTER TABLE `amicum3`.`work_mode_company`
    CHANGE COLUMN `date_time_start` `date_time_start` DATE NOT NULL COMMENT 'дата, с которой действует режим работы',
    CHANGE COLUMN `date_time_end` `date_time_end` DATE NULL DEFAULT NULL COMMENT 'дата по который действует режим работы',
    CHANGE COLUMN `date_time_create` `date_time_create` DATETIME NOT NULL COMMENT 'Дата и время установки режима работы';

ALTER TABLE `amicum3`.`work_mode`
    ADD COLUMN `count_norm_hours` FLOAT NULL COMMENT 'количество нормированных часов' AFTER `count_hours`;

#25.08.2023