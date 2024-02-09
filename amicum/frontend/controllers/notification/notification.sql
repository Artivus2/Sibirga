# таблица статуса прочтения
CREATE TABLE `amicum3`.`notification_status` (
                                                 `id` INT NOT NULL AUTO_INCREMENT,
                                                 `worker_id` INT NOT NULL,
                                                 `date_time` DATETIME NOT NULL,
                                                 `restriction_id` INT NOT NULL,
                                                 `type_restriction` VARCHAR(45) NOT NULL,
                                                 `status_id` INT NOT NULL,
                                                 PRIMARY KEY (`id`),
                                                 UNIQUE INDEX `id_UNIQUE` (`id` ASC) INVISIBLE,
                                                 INDEX `fk_notification_status_status_id_idx` (`status_id` ASC) INVISIBLE,
                                                 INDEX `fk_notification_status_worker_id_idx` (`worker_id` ASC) INVISIBLE,
                                                 CONSTRAINT `fk_notification_status_status_id`
                                                     FOREIGN KEY (`status_id`)
                                                         REFERENCES `amicum3`.`status` (`id`)
                                                         ON DELETE NO ACTION
                                                         ON UPDATE NO ACTION,
                                                 CONSTRAINT `fk_notification_status_worker_id`
                                                     FOREIGN KEY (`worker_id`)
                                                         REFERENCES `amicum3`.`worker` (`id`)
                                                         ON DELETE NO ACTION
                                                         ON UPDATE NO ACTION)
    ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_bin;
# применено 11.10.2023