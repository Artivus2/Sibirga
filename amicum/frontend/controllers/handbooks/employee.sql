/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

USE `amicum3`;
CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `view_company_department` AS
SELECT `company_department`.`id` AS `id`,
       `company`.`id`            AS `company_id`,
       `company`.`title`         AS `title_company`,
       `department`.`id`         AS `department_id`,
       `department`.`title`      AS `title_department`,
       `department_type`.`id`    AS `department_type_id`,
       `department_type`.`title` AS `department_type_title`,
       company_upper.id          as company_upper_id,
       company_upper.title       as company_upper_title
FROM (((`company_department`
    LEFT JOIN `department` ON ((`company_department`.`department_id` = `department`.`id`)))
    LEFT JOIN `department_type` `department_type` ON ((`department_type`.`id` = `company_department`.`department_type_id`)))
         LEFT JOIN `company` ON ((`company_department`.`company_id` = `company`.`id`))
         LEFT JOIN `company` `company_upper` ON ((`company`.`upper_company_id` = `company_upper`.`id`)));

USE `amicum3`;
CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `view_handbook_employee` AS
SELECT `worker`.`id`                                   AS `worker_id`,
       `worker_object`.`id`                            AS `worker_object_id`,
       CONCAT(`employee`.`last_name`,
              ' ',
              `employee`.`first_name`,
              ' ',
              COALESCE(`employee`.`patronymic`, ' '))  AS `FIO`,
       `employee`.`birthdate`                          AS `birthdate`,
       `position`.`title`                              AS `position_title`,
       `view_company_department`.`company_id`          AS `company_id`,
       `view_company_department`.`title_company`       AS `company_title`,
       `view_company_department`.`id`                  AS `company_department_id`,
       `view_company_department`.`company_upper_title` AS `department_title`,
       `worker`.`tabel_number`                         AS `tabel_number`,
       `worker`.`date_start`                           AS `date_start`,
       `worker`.`vgk`                                  AS `vgk_status`,
       IF((`worker`.`date_end` IS NULL),
          '-',
          `worker`.`date_end`)                         AS `date_end`
FROM ((((`employee`
    JOIN `worker` ON ((`worker`.`employee_id` = `employee`.`id`)))
    JOIN `worker_object` ON ((`worker`.`id` = `worker_object`.`worker_id`)))
    LEFT JOIN `position` ON ((`position`.`id` = `worker`.`position_id`)))
         LEFT JOIN `view_company_department` ON ((`view_company_department`.`id` = `worker`.`company_department_id`)));

#25.08.2023