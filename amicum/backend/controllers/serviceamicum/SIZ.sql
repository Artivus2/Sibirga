# Сервисные скрипты при обслуживании АСУ СИЗ интеграции

# очистка таблиц СИЗ
delete
from amicum3.sap_asu_siz_full;
delete
from amicum3.sap_asu_worker_siz_full;
delete
from amicum3.sap_asu_worker_siz_update;
delete
FROM amicum3.sap_asu_worker_siz_update;
delete
from amicum3.sap_siz_update;
delete
from amicum3.sap_worker_siz_update;
delete
from amicum3.worker_siz_status;
delete
from amicum3.siz_store;
delete
from amicum3.worker_siz;
delete
from amicum3.siz;

# сброс СИЗ полный
truncate table amicum3.sap_asu_siz_full;
truncate table amicum3.sap_asu_worker_siz_full;
truncate table amicum3.sap_asu_worker_siz_update;
truncate table amicum3.sap_siz_update;
truncate table amicum3.sap_worker_siz_update;
truncate table amicum3.worker_siz_status;
truncate table amicum3.siz_store;
ALTER TABLE `amicum3`.`worker_siz_status`
    DROP FOREIGN KEY `fk_worker_siz_status_worker_siz1`;
truncate table amicum3.worker_siz;
ALTER TABLE `amicum3`.`worker_siz`
    DROP FOREIGN KEY `fk_worker_siz_siz1`;
ALTER TABLE `amicum3`.`worker_siz`
    DROP INDEX `fk_worker_siz_siz1_idx`;
ALTER TABLE `amicum3`.`siz_store`
    DROP FOREIGN KEY `fk_siz_store_siz1`;
truncate table amicum3.siz;
ALTER TABLE `amicum3`.`siz_store`
    ADD CONSTRAINT `fk_siz_store_siz1`
        FOREIGN KEY (`siz_id`)
            REFERENCES `amicum3`.`siz` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;
ALTER TABLE `amicum3`.`worker_siz_status`
    ADD CONSTRAINT `fk_worker_siz_status_worker_siz1`
        FOREIGN KEY (`worker_siz_id`)
            REFERENCES `amicum3`.`worker_siz` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;
ALTER TABLE `amicum3`.`worker_siz`
    ADD INDEX `fk_worker_siz_siz1_idx` (`siz_id` ASC) VISIBLE;
;
ALTER TABLE `amicum3`.`worker_siz`
    ADD CONSTRAINT `fk_worker_siz_siz1`
        FOREIGN KEY (`siz_id`)
            REFERENCES `amicum3`.`siz` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE;


# блокировка таблиц сиз
LOCK TABLES amicum3.sap_asu_worker_siz_update read;
LOCK TABLES amicum3.sap_asu_worker_siz_update write;
UNLOCK TABLES;

# контроль процесса копирования данных
SELECT *
FROM amicum3.sap_asu_worker_siz_update;
SELECT count(*)
FROM amicum3.sap_asu_worker_siz_update;

# индекс для ускорения поиска
ALTER TABLE `amicum3`.`sap_asu_worker_siz_update`
    ADD INDEX `tab_num_siz_id` (`tabn` ASC, `n_nomencl` ASC) VISIBLE;
;

#25.08.2023