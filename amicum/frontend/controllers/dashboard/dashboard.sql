# Скрипты DashBoard

CREATE TABLE `amicum3`.`dashboard_config`
(
    `id`          INT      NOT NULL AUTO_INCREMENT,
    `user_id`     INT      NOT NULL COMMENT 'ключ пользователя системы Амикум',
    `date_time`   DATETIME NOT NULL COMMENT 'Дата и время сохранения конфигурации',
    `config_json` LONGTEXT NULL COMMENT 'конфиг интерактивного рабочего стола',
    PRIMARY KEY (`id`),
    INDEX `dashboard_user` (`user_id` ASC) VISIBLE
)
    COMMENT = 'Список конфигураций пользователя';

INSERT INTO `amicum3`.`modul_amicum` (`id`, `title`)
VALUES ('21', 'Интерактивное рабочее место');
INSERT INTO `amicum3`.`page` (`id`, `title`, `url`)
VALUES ('196', 'Даш Боард', '/dash-board');

ALTER TABLE `amicum3`.`checking`
    ADD COLUMN `date_time_create` DATETIME NULL DEFAULT now() COMMENT 'дата и время создания' AFTER `date_time_sync_nn`;

ALTER TABLE `amicum3`.`checking`
    ADD COLUMN `kind_document_id` INT NULL COMMENT 'Вид документа ПК (ПАБ, н/н, Предписание, предписание РТН)' AFTER `date_time_create`,
    ADD INDEX `fk_checking_kind_document_id_idx` (`kind_document_id` ASC) VISIBLE;
;
ALTER TABLE `amicum3`.`checking`
    ADD CONSTRAINT `fk_checking_kind_document_id`
        FOREIGN KEY (`kind_document_id`)
            REFERENCES `amicum3`.`kind_document` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;
