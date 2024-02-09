ALTER TABLE `amicum3`.`AMICUM_INSTRUCTION_MV`
    CHANGE COLUMN `DATE_CREATED_IG` `DATE_CREATED_IG`     DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_CREATED_IRES` `DATE_CREATED_IRES` DATETIME NULL DEFAULT NULL;

ALTER TABLE `amicum3`.`AMICUM_ROSTEX_MV`
    CHANGE COLUMN `ROSTEX_DATE` `ROSTEX_DATE`       DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_PLAN` `DATE_PLAN`           DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_FACT` `DATE_FACT`           DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_TRANSFER` `DATE_TRANSFER`   DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_RECIPIENT` `DATE_RECIPIENT` DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_STOP_WORK` `DATE_STOP_WORK` DATETIME NULL DEFAULT NULL;

ALTER TABLE `amicum3`.`AMICUM_PAB_N_N_MV`
    CHANGE COLUMN `DT_BEG_AUDIT` `DT_BEG_AUDIT` DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DT_END_AUDIT` `DT_END_AUDIT` DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `ACTION_DATE` `ACTION_DATE`   DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_STOP` `DATE_STOP`       DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_CHECK` `DATE_CHECK`     DATETIME NULL DEFAULT NULL,
    CHANGE COLUMN `DATE_TALK` `DATE_TALK`       DATETIME NULL DEFAULT NULL;

ALTER TABLE `amicum3`.`injunction`
    ADD INDEX `fk_injunction_sync_key` (`instruct_id_ip` ASC) VISIBLE,
    ADD INDEX `fk_injunction_date_time_sync` (`date_time_sync` ASC) VISIBLE;
ALTER TABLE `amicum3`.`injunction` ALTER INDEX `fk_injunction_sync_pab_key` INVISIBLE;
ALTER TABLE `amicum3`.`injunction` ALTER INDEX `fk_injunction_date_time_sync_pab` INVISIBLE;

ALTER TABLE `amicum3`.`violation`
    ADD INDEX `idx_violation_title` (`title`(50) ASC) VISIBLE;;

ALTER TABLE `amicum3`.`paragraph_pb`
    ADD INDEX `idx_paragraph_pb_title` (`text`(20) ASC) VISIBLE;
ALTER TABLE `amicum3`.`paragraph_pb` ALTER INDEX `fk_paragraph_pb_document1_idx` INVISIBLE;

ALTER TABLE `amicum3`.`checking_place`
    ADD INDEX `idx_checking_place_id` (`place_id` ASC) VISIBLE;
ALTER TABLE `amicum3`.`checking_place` ALTER INDEX `fk_checking_place_checking_idx` INVISIBLE;

ALTER TABLE `amicum3`.`REF_PLACE_AUDIT_MV`
    ADD INDEX `REF_PLACE_AUDIT_ID` (`REF_PLACE_AUDIT_ID` ASC) VISIBLE,
    DROP PRIMARY KEY;
ALTER TABLE `amicum3`.`REF_PLACE_AUDIT_MV` ALTER INDEX `ref_place_audit_mv_date_modified` INVISIBLE;

ALTER TABLE `amicum3`.`REF_PLACE_AUDIT_MV`
    ADD INDEX `ref_place_audit_mv_date_modified` (`DATE_MODIFIED` ASC) VISIBLE;
ALTER TABLE `amicum3`.`REF_PLACE_AUDIT_MV` ALTER INDEX `REF_PLACE_AUDIT_ID` INVISIBLE;


ALTER TABLE `amicum3`.`injunction`
    ADD INDEX `date_time_sync_nn_idx` (`date_time_sync_nn` ASC) VISIBLE;
;

ALTER TABLE `amicum3`.`injunction_violation`
    ADD INDEX `date_time_sync_pab_idx` (`date_time_sync_pab` ASC) INVISIBLE,
    ADD INDEX `date_time_sync_nn_idx` (`date_time_sync_nn` ASC) INVISIBLE,
    ADD INDEX `date_time_sync_rostex_idx` (`date_time_sync_rostex` ASC) INVISIBLE;
ALTER TABLE `amicum3`.`injunction_violation` ALTER INDEX `fk_injunction_violation_violation1_idx` INVISIBLE;

ALTER TABLE `amicum3`.`violator`
    ADD INDEX `injunction_violation_id_worker_id_idx` (`worker_id` ASC, `injunction_violation_id` ASC) VISIBLE;
;

#25.08.2023