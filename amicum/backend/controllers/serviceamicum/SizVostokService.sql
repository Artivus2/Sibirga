CREATE TABLE IF NOT EXISTS `amicum3`.`size`
(
    `id`      INT          NOT NULL AUTO_INCREMENT COMMENT 'Ключ справочника размеров',
    `title`   VARCHAR(100) NULL,
    `link_1c` VARCHAR(100) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `link_1c_UNIQUE` (`link_1c` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'Справочник размеров';

CREATE TABLE IF NOT EXISTS `amicum3`.`siz_hand`
(
    `id`      INT          NOT NULL AUTO_INCREMENT COMMENT 'ключ сиз',
    `title`   VARCHAR(255) NULL COMMENT 'Название СИЗ',
    `link_1c` VARCHAR(100) NULL COMMENT 'ключ СИЗ из внешней системы 1С',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `link_1c_UNIQUE` (`link_1c` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'Справочник СИЗ';

CREATE TABLE IF NOT EXISTS `amicum3`.`norm_hand`
(
    `id`               INT          NOT NULL AUTO_INCREMENT COMMENT 'ключ нормы',
    `title`            VARCHAR(255) NULL COMMENT 'Название нормы выдачи СИЗ',
    `link_1c`          VARCHAR(100) NULL COMMENT 'ключ нормы выдачи СИЗ из внешней системы 1С',
    `issue_type`       VARCHAR(150) NULL COMMENT 'тип нормы выдачи (персональная, групповая)',
    `calculation_type` VARCHAR(150) NULL COMMENT 'тип расчета нормы выдачи (период, до даты)',
    `period_type`      VARCHAR(150) NULL COMMENT 'тип периода нормы выдачи - месяц, год, день и т.д.',
    `period_count`     INT          NULL COMMENT 'длительность периода',
    `period_quantity`  INT          NULL COMMENT 'количество раз выдачи в период',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `link_1c_UNIQUE` (`link_1c` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'Справочник норм выдачи';

CREATE TABLE IF NOT EXISTS `amicum3`.`siz_hand_norm`
(
    `id`      INT          NOT NULL AUTO_INCREMENT COMMENT 'ключ сиз',
    `title`   VARCHAR(255) NULL COMMENT 'Название СИЗ',
    `link_1c` VARCHAR(100) NULL COMMENT 'ключ СИЗ из внешней системы 1С',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `link_1c_UNIQUE` (`link_1c` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'Справочник СИЗ для норм выдачи';

CREATE TABLE IF NOT EXISTS `amicum3`.`norm_siz`
(
    `id`                       INT          NOT NULL AUTO_INCREMENT COMMENT 'ключ нормы',
    `norm_hand_id_link_1c`     VARCHAR(100) NOT NULL COMMENT 'ключ нормы выдачи СИЗ из внешней системы 1С',
    `siz_hand_norm_id_link_1c` VARCHAR(100) NOT NULL COMMENT 'Название нормы выдачи СИЗ',
    PRIMARY KEY (`norm_hand_id_link_1c`, `siz_hand_norm_id_link_1c`),
    INDEX `siz_hand_norm_id_link_1c_idx` (`siz_hand_norm_id_link_1c` ASC) VISIBLE,
    INDEX `norm_hand_id_link_1c` (`norm_hand_id_link_1c` ASC) VISIBLE,
    UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    CONSTRAINT `siz_hand_norm_id_link_1c`
        FOREIGN KEY (`siz_hand_norm_id_link_1c`)
            REFERENCES `amicum3`.`siz_hand_norm` (`link_1c`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `norm_hand_id_link_1c`
        FOREIGN KEY (`norm_hand_id_link_1c`)
            REFERENCES `amicum3`.`norm_hand` (`link_1c`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    ENGINE = InnoDB
    COMMENT = 'Связка норм выдачи и справочника сиз для норм выдачи';

ALTER TABLE `amicum3`.`siz`
    ADD COLUMN `link_1c` VARCHAR(100) NULL COMMENT 'ключ СИЗ из внешней системы 1С' AFTER `siz_subgroup_id`;

INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('124', 'Возврат', '-', '13');

CREATE TABLE IF NOT EXISTS `amicum3`.`norm_siz_need`
(
    `id`             INT  NOT NULL AUTO_INCREMENT COMMENT 'ключ потребности в СИЗ работника',
    `worker_id`      INT  NULL COMMENT 'ключа работника',
    `date_time_need` DATE NULL COMMENT 'Дата и время назначения нормы',
    `count_siz`      INT  NULL COMMENT 'количество сиз установленных по норме\n',
    `norm_siz_id`    INT  NULL COMMENT 'ключ связки нормы СИЗ и СИЗ по норме',
    PRIMARY KEY (`id`),
    INDEX `siz_worker_idx` (`worker_id` ASC, `date_time_need` ASC) VISIBLE
)
    ENGINE = InnoDB
    COMMENT = 'Список потребностей СИЗ у работника';

ALTER TABLE `amicum3`.`norm_siz_need`
    CHANGE COLUMN `worker_id` `worker_id` INT NOT NULL COMMENT 'ключа работника',
    CHANGE COLUMN `date_time_need` `date_time_need` DATE NOT NULL COMMENT 'Дата и время назначения нормы',
    CHANGE COLUMN `norm_siz_id` `norm_siz_id` INT NOT NULL COMMENT 'ключ связки нормы СИЗ и СИЗ по норме',
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`worker_id`, `norm_siz_id`, `date_time_need`),
    ADD UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
    DROP INDEX `siz_worker_idx`;
;

INSERT INTO `amicum3`.`status` (`id`, `title`, `trigger`, `status_type_id`)
VALUES ('125', 'Списано, но не выдано', '-', '13');


ALTER TABLE `amicum3`.`norm_siz_need`
    CHANGE COLUMN `count_siz` `count_siz` NVARCHAR(10) NULL DEFAULT NULL COMMENT 'количество сиз установленных по норме\\\\n';

#25.08.2023