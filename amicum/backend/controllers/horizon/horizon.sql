/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

# скрипты службы горизонт

# Скрипт получения списка сенсоров - их последней шахты и их последнего сетевого адреса
# используется для построения списка сенсоров для работы очереди сервиса горизонт
USE `amicum3`;
CREATE OR REPLACE ALGORITHM = UNDEFINED DEFINER = `amicum_system`@`%` SQL SECURITY DEFINER VIEW `vw_sensor_mine_by_sensor_type` AS
SELECT `sensor`.`id`             AS `sensor_id`,
       `sensor`.`title`          AS `sensor_title`,
       `s_p_h_v_346`.`value`     AS `mine_id`,
       `s_p_h_v_88`.`value`      AS `net_id`,
       `sensor`.`sensor_type_id` AS `sensor_type_id`
FROM `sensor`
         LEFT JOIN `sensor_parameter` s_p_88 ON `sensor`.`id` = `s_p_88`.`sensor_id`
         JOIN `sensor_parameter_handbook_value` s_p_h_v_88 ON `s_p_88`.`id` = `s_p_h_v_88`.`sensor_parameter_id`
         JOIN `view_sensor_parameter_handbook_value_maxDate` s_p_h_v_max_88
              ON `s_p_h_v_max_88`.`sensor_parameter_id` = `s_p_h_v_88`.`sensor_parameter_id` AND
                 `s_p_h_v_max_88`.`date_time_last` = `s_p_h_v_88`.`date_time`

         LEFT JOIN `sensor_parameter` s_p_346 ON `sensor`.`id` = `s_p_346`.`sensor_id`
         JOIN `sensor_parameter_handbook_value` s_p_h_v_346 ON `s_p_346`.`id` = `s_p_h_v_346`.`sensor_parameter_id`
         JOIN `view_sensor_parameter_handbook_value_maxDate` s_p_h_v_max_346
              ON `s_p_h_v_max_346`.`sensor_parameter_id` = `s_p_h_v_346`.`sensor_parameter_id` AND
                 `s_p_h_v_max_346`.`date_time_last` = `s_p_h_v_346`.`date_time`

WHERE `s_p_346`.`parameter_id` = 346
  AND `s_p_346`.`parameter_type_id` = 1
  AND `s_p_88`.`parameter_id` = 88
  AND `s_p_88`.`parameter_type_id` = 1;

#25.08.2023

