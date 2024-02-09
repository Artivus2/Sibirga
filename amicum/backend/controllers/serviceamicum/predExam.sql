/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/**
 * Скрипт для создания интеграционного слоя Предсменного экзаменатора
 */
CREATE TABLE IF NOT EXISTS `amicum3`.`sap_kind_exam_mv`
(
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ключ справочника видов экзаменов',
    `name`          VARCHAR(255) NULL COMMENT 'название вида экзамена',
    `quantity`      INT          NULL COMMENT 'количество правильных ответов как проходной бал',
    `date_created`  DATETIME(6)  NULL COMMENT 'дата создания записи',
    `date_modified` DATETIME(6)  NULL COMMENT 'дата изменения записи',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    INDEX `sap_kind_exam_mv_date_time_created` (`date_created` ASC) VISIBLE,
    INDEX `sap_kind_exam_mv_date_time_modified` (`date_modified` ASC) VISIBLE,
    INDEX `sap_kind_exam_mv_name` (`name` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'справочник синхронизации  видов экзаменов';

CREATE TABLE IF NOT EXISTS `amicum3`.`sap_pred_exam_history_full_mv`
(
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ключ теста',
    `personal_number`  VARCHAR(255) NULL COMMENT 'ключ работника',
    `start_test_time`  INT          NULL COMMENT 'дата и время старта экзамена',
    `count_right`      VARCHAR(45)  NULL COMMENT 'количество правильных ответов',
    `count_false`      VARCHAR(45)  NULL COMMENT 'количество не правильных ответов',
    `points`           VARCHAR(45)  NULL COMMENT 'количество баллов',
    `sap_kind_exam_id` VARCHAR(45)  NULL COMMENT 'ключ справочника вида экзамена',
    `date_created`     DATETIME(6)  NULL COMMENT 'дата создания записи',
    `date_modified`    DATETIME(6)  NULL COMMENT 'дата изменения записи',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    INDEX `sap_pred_exam_history_full_mv_date_time_created` (`date_created` ASC) VISIBLE,
    INDEX `sap_pred_exam_history_full_mv_date_time_modified` (`date_modified` ASC) VISIBLE,
    INDEX `sap_pred_exam_history_full_mv_personal_number` (`personal_number` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'синхронизации истории тестирования (экзаменов)';

CREATE TABLE IF NOT EXISTS `amicum3`.`kind_exam`
(
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ключ справочника видов экзаменов',
    `name`          VARCHAR(255) NOT NULL COMMENT 'название вида экзамена',
    `quantity`      INT          NULL COMMENT 'количество правильных ответов как проходной бал',
    `date_created`  DATETIME(6)  NULL COMMENT 'дата создания записи',
    `date_modified` DATETIME(6)  NULL COMMENT 'дата изменения записи',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    INDEX `kind_exam_date_time_created` (`date_created` ASC) VISIBLE,
    INDEX `kind_exam_date_time_modified` (`date_modified` ASC) VISIBLE,
    INDEX `kind_exam_name` (`name` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'справочник видов экзаменов';

CREATE TABLE IF NOT EXISTS `amicum3`.`pred_exam_history`
(
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ключ теста',
    `worker_id`        VARCHAR(255) NOT NULL COMMENT 'ключ работника',
    `start_test_time`  INT          NOT NULL COMMENT 'дата и время старта экзамена',
    `count_right`      VARCHAR(45)  NULL COMMENT 'количество правильных ответов',
    `count_false`      VARCHAR(45)  NULL COMMENT 'количество не правильных ответов',
    `points`           VARCHAR(45)  NULL COMMENT 'количество баллов',
    `sap_kind_exam_id` VARCHAR(45)  NOT NULL COMMENT 'ключ справочника вида экзамена',
    `date_created`     DATETIME(6)  NULL COMMENT 'дата создания записи',
    `date_modified`    DATETIME(6)  NULL COMMENT 'дата изменения записи',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    INDEX `pred_exam_history_date_time_created` (`date_created` ASC) VISIBLE,
    INDEX `pred_exam_history_date_time_modified` (`date_modified` ASC) VISIBLE,
    INDEX `pred_exam_history_worker_id` (`worker_id` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'история тестирования (экзаменов)';

ALTER TABLE `amicum3`.`kind_exam`
    ADD COLUMN `sap_id` INT NULL COMMENT 'ключ из сап' AFTER `date_modified`;

ALTER TABLE `amicum3`.`pred_exam_history`
    ADD COLUMN `sap_id` INT NULL AFTER `date_modified`;

ALTER TABLE `amicum3`.`pred_exam_history`
    CHANGE COLUMN `start_test_time` `start_test_time` DATETIME NOT NULL COMMENT 'дата и время старта экзамена';



ALTER TABLE `amicum3`.`pred_exam_history`
    CHANGE COLUMN `start_test_time` `start_test_time` DATETIME NOT NULL COMMENT 'дата и время старта экзамена1',
    CHANGE COLUMN `date_created` `date_created`       DATETIME NULL DEFAULT NULL COMMENT 'дата создания записи1',
    CHANGE COLUMN `date_modified` `date_modified`     DATETIME NULL DEFAULT NULL COMMENT 'дата изменения записи1';

ALTER TABLE `amicum3`.`sap_kind_exam_mv`
    CHANGE COLUMN `date_created` `date_created`   DATETIME NULL DEFAULT NULL COMMENT 'дата создания записи1',
    CHANGE COLUMN `date_modified` `date_modified` DATETIME NULL DEFAULT NULL COMMENT 'дата изменения записи1';

ALTER TABLE `amicum3`.`kind_exam`
    CHANGE COLUMN `date_created` `date_created`   DATETIME NULL DEFAULT NULL COMMENT 'дата создания записи1',
    CHANGE COLUMN `date_modified` `date_modified` DATETIME NULL DEFAULT NULL COMMENT 'дата изменения записи1';

ALTER TABLE `amicum3`.`sap_pred_exam_history_full_mv`
    CHANGE COLUMN `start_test_time` `start_test_time` DATETIME NULL DEFAULT NULL COMMENT 'дата и время старта экзамена',
    CHANGE COLUMN `date_created` `date_created`       DATETIME NULL DEFAULT NULL COMMENT 'дата создания записи1',
    CHANGE COLUMN `date_modified` `date_modified`     DATETIME NULL DEFAULT NULL COMMENT 'дата изменения записи1';

ALTER TABLE `amicum3`.`pred_exam_history`
    CHANGE COLUMN `count_right` `count_right` INT UNSIGNED NULL DEFAULT NULL COMMENT 'количество правильных ответов',
    CHANGE COLUMN `count_false` `count_false` INT UNSIGNED NULL DEFAULT NULL COMMENT 'количество не правильных ответов',
    CHANGE COLUMN `points` `points`           FLOAT        NULL DEFAULT NULL COMMENT 'количество баллов';

ALTER TABLE `amicum3`.`sap_pred_exam_history_full_mv`
    CHANGE COLUMN `count_right` `count_right` INT UNSIGNED NULL DEFAULT NULL COMMENT 'количество правильных ответов',
    CHANGE COLUMN `count_false` `count_false` INT UNSIGNED NULL DEFAULT NULL COMMENT 'количество не правильных ответов',
    CHANGE COLUMN `points` `points` FLOAT NULL DEFAULT NULL COMMENT 'количество баллов';

ALTER TABLE `amicum3`.`sap_kind_exam_mv`
    ADD INDEX `id_idx` (`id` ASC) VISIBLE,
    DROP INDEX `id_UNIQUE`,
    DROP PRIMARY KEY;
;

ALTER TABLE `amicum3`.`pred_exam_history`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`mine_id`, `worker_id`, `start_test_time`, `sap_id`);
;
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

# применено 22.08.2023
ALTER TABLE `amicum3`.`pred_exam_history`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`sap_id`);
;

ALTER TABLE `amicum3`.`pred_exam_history`
    ADD UNIQUE INDEX `sap_id_UNIQUE` (`sap_id` ASC) VISIBLE;
;
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

# применено 05.09.2023


ALTER TABLE `amicum3`.`pred_exam_history`
    CHANGE COLUMN `worker_id` `employee_id` VARCHAR(255) NOT NULL COMMENT 'ключ человека';

# применено 12.09.2023