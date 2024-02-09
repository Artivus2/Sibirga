/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

truncate table amicum2_log.amicum_statistic;
truncate table amicum2_log.amicum_synchronization;
truncate table amicum2_log.bpd_package_info;
truncate table amicum2_log.ErrorMethods;
truncate table amicum2_log.last_date_replicate;
truncate table amicum2_log.queue_log;
truncate table amicum2_log.snmp_package_info;
truncate table amicum2_log.strata_action_log;
truncate table amicum2_log.strata_device_type;
truncate table amicum2_log.strata_package_info;
truncate table amicum2_log.strata_package_source;
truncate table amicum2_log.user_access_log;
truncate table amicum2_log.user_action_log;
truncate table amicum2_log.ws_log;

OPTIMIZE TABLE `amicum3`.`AMICUM_INSTRUCTION_MV`;
OPTIMIZE TABLE `amicum3`.`sensor_parameter_value`;
OPTIMIZE TABLE `amicum3`.`worker_parameter_value`;
OPTIMIZE TABLE `amicum3`.`AMICUM_PAB_N_N_MV`;

OPTIMIZE table amicum2_log.amicum_statistic;
OPTIMIZE table amicum2_log.amicum_synchronization;
OPTIMIZE table amicum2_log.bpd_package_info;
OPTIMIZE table amicum2_log.ErrorMethods;
OPTIMIZE table amicum2_log.last_date_replicate;
OPTIMIZE table amicum2_log.queue_log;
OPTIMIZE table amicum2_log.snmp_package_info;
OPTIMIZE table amicum2_log.strata_action_log;
OPTIMIZE table amicum2_log.strata_device_type;
OPTIMIZE table amicum2_log.strata_package_info;
OPTIMIZE table amicum2_log.strata_package_source;
OPTIMIZE table amicum2_log.user_access_log;
OPTIMIZE table amicum2_log.user_action_log;
OPTIMIZE table amicum2_log.ws_log;

delete
FROM amicum2_log.amicum_synchronization
where date_time_start < "2021-02-20 04:48:06";
delete
FROM amicum2_log.amicum_synchronization
where method_name = "newSynchronizationSKUD";


mysqldump -u root -p --protocol=socket -S /var/lib/mysql/mysql.sock --opt amicum3 > /home/ingener401/db/backup_06112020.sql
tar -czf - /home/ingener401/db/backup_06112020.sql | split -b 12048m - "mybackup_06112020.tar.gz-part-"

sh /var/www/html/amicum/script_amicum/pull_1_5.sh -full

    #25.08.2023