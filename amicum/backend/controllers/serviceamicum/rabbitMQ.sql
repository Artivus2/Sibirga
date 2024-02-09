/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

CREATE TABLE `amicum3`.`rabbit_mq` (
                                       `id` INT NOT NULL AUTO_INCREMENT COMMENT 'ключ журнала синхронизации',
                                       `message` LONGTEXT NOT NULL COMMENT 'сообщений синхронизации',
                                       `date_time_create` DATETIME NOT NULL COMMENT 'дата создания сообщения синхронизации',
                                       `status` TINYINT NULL COMMENT 'статус обработки синхронизации',
                                       `queue_name` VARCHAR(45) NOT NULL COMMENT 'Название очереди, она же таблица синхронизации',
                                       PRIMARY KEY (`id`),
                                       UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
                                       INDEX `rabbit_mq_queue_name_date_time_create` (`queue_name` ASC, `date_time_create` ASC) INVISIBLE,
                                       INDEX `rabbit_mq_date_time_create` (`date_time_create` DESC) VISIBLE,
                                       INDEX `rabbit_mq_queue_name` (`queue_name` ASC) VISIBLE)
    COMMENT = 'Журнал синхронизации 1с через rabbitMq';

ALTER TABLE `amicum3`.`rabbit_mq`
    CHANGE COLUMN `date_time_create` `date_time_create` DATETIME(6) NOT NULL COMMENT 'дата создания сообщения синхронизации с микросекундами' ;


ALTER TABLE `amicum3`.`company`
    ADD COLUMN `link_1c` VARCHAR(100) NULL COMMENT 'сслыка синхронизации из 1с' AFTER `upper_company_id`,
    ADD INDEX `link_1c_idx` (`link_1c` ASC) INVISIBLE,
    ADD INDEX `upper_company_id_idx` (`upper_company_id` ASC, `id` ASC) INVISIBLE;
;

ALTER TABLE `amicum3`.`company`
    ADD COLUMN `date_time_sync` DATETIME NULL COMMENT 'Дата и время последней синхронизации с 1с' AFTER `link_1c`,
    ADD INDEX `date_time_sync_idx` (`date_time_sync` DESC) VISIBLE;
;

ALTER TABLE `amicum3`.`employee`
    ADD COLUMN `link_1c` VARCHAR(100) NULL DEFAULT NULL COMMENT 'сслыка синхронизации из 1с' AFTER `birthdate`,
    ADD COLUMN `date_time_sync` DATETIME NULL DEFAULT NULL COMMENT 'Дата и время последней синхронизации с 1с' AFTER `link_1c`,
    ADD INDEX `link_1c_idx` (`link_1c` ASC) INVISIBLE,
    ADD INDEX `date_time_sync_idx` (`date_time_sync` ASC) VISIBLE;
;

ALTER TABLE `amicum3`.`worker`
    ADD COLUMN `link_1c` VARCHAR(100) NULL DEFAULT NULL COMMENT 'сслыка синхронизации из 1с' AFTER `vgk`,
    ADD COLUMN `date_time_sync` DATETIME NULL DEFAULT NULL COMMENT 'Дата и время последней синхронизации с 1с' AFTER `link_1c`,
    ADD INDEX `link_1c_idx` (`link_1c` ASC) INVISIBLE,
    ADD INDEX `date_time_sync_idx` (`date_time_sync` ASC) VISIBLE;
;

ALTER TABLE `amicum3`.`position`
    ADD COLUMN `link_1c` VARCHAR(100) NULL DEFAULT NULL COMMENT 'сслыка синхронизации из 1с' AFTER `short_title`,
    ADD COLUMN `date_time_sync` DATETIME NULL DEFAULT NULL COMMENT 'Дата и время последней синхронизации с 1с' AFTER `link_1c`,
    ADD INDEX `link_1c_idx` (`link_1c` ASC) INVISIBLE,
    ADD INDEX `date_time_sync_idx` (`date_time_sync` ASC) VISIBLE;
;

ALTER TABLE `amicum3`.`worker`
    CHANGE COLUMN `date_time_sync` `date_time_sync` DATETIME(6) NULL DEFAULT NULL COMMENT 'Дата и время последней синхронизации с 1с 6' ;

ALTER TABLE `amicum3`.`company`
    CHANGE COLUMN `date_time_sync` `date_time_sync` DATETIME(6) NULL DEFAULT NULL COMMENT 'Дата и время последней синхронизации с 1с 6' ;

ALTER TABLE `amicum3`.`employee`
    CHANGE COLUMN `date_time_sync` `date_time_sync` DATETIME(6) NULL DEFAULT NULL COMMENT 'Дата и время последней синхронизации с 1с 6' ;

ALTER TABLE `amicum3`.`position`
    CHANGE COLUMN `date_time_sync` `date_time_sync` DATETIME(6) NULL DEFAULT NULL COMMENT 'Дата и время последней синхронизации с 1с 6' ;

#25.08.2023