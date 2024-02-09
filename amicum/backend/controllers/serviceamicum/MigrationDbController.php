<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\serviceamicum;

use backend\controllers\Assistant;
use frontend\models\Edge;
use frontend\models\EdgeParameterHandbookValue;
use frontend\models\EquipmentParameterHandbookValue;
use frontend\models\EquipmentParameterValue;
use frontend\models\EventJournal;
use frontend\models\Main;
use frontend\models\MainSync;
use frontend\models\ParameterSunc;
use frontend\models\Sensor;
use frontend\models\SensorParameter;
use frontend\models\SensorParameterHandbookValue;
use frontend\models\SensorParameterValue;
use frontend\models\SensorParameterValueHistory;
use frontend\models\SituationJournal;
use frontend\models\WorkerParameterValue;
use frontend\models\WorkerParameterValueHistory;
use frontend\models\WorkerParameterValueTemp;
use Throwable;
use WebSocket\Exception;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class MigrationDbController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    //Список методов:
    // actionMoveCacheToDb                       - метод миграции справочных значений сеноров из кеша в базу данных - применяется в тех случаях когда есть кеш, но похерена база.
    // actionSyncDb                              - центральный метод по синхронизации двух баз данных
    // CopyTableDate                             - копирование таблиц с одинаковой структурой из таблицы источника в таблицу назначение
    // TransferSensors()                         - метод переноса данные по сенсорам с одной БД в другой с новыми айдишниками (id)
    // TransferSensorConnectString               - Метод по переносу и переидентификации данных с таблицы sensor_connect_string
    // TransferConnectString                     - метод по замени айдишников таблицы connect_string
    // TransferWorkerParameters()                - метод переноса данные по работникам с одной БД в другой с новыми айдишниками (id)
    // TransferEquipments()                      - метод по переносу оборудование с учетом новых айдишников

    /**  Схема шахты   */
    // actionMigrationScheme                     - метод переноса схемы в новую БД (conjunction, place, edge их параметры и значения)
    // TransferConjunctions()                    - метод по переносу дынных conjunction
    // TransferPlaces                            - метод по переносу данных place
    // TransferEdges                             - метод по переносу данных edge

    // TransOrderPlace                           - метод по переносу данные с табицы order_place c новых place_id
    // TransOrderWorkerCoordinatee               - метод по пеносу данных с order_worker_coordinate c новых place_id
    // TransOrderOperation                       - метод по пеносу данных с order_operation c новых equipment_id
    // TransOrderTemplatePlace                   - метод по переносу данные с табицы order_template_place c новых place_id
    // TransOrderPlaceVtbAb                      - метод по переносу данные с табицы order_place_vtb_ab c новых place_id
    // TransSensorEdgeHandbook                   - метод изминения старых эджей на новый в таблице sensor_parameter_handbook_value
    // TransSensorPlaceHandbook                  - метод по изменения старого ключа места на новое для сенсоров в таблице sensor_parameter_handbook_value
    // TransEquipmentPlaceHandbook               - метод по изменения старого ключа места на новое для воркеров в таблице equipment_parameter_handbook_value
    // TransEquipmentPlaceHandbook               - метод по изменения старого ключа места на новое для воркеров в таблице equipment_parameter_handbook_value
    // TransEquipmentEdgeHandbook()              - метод изминения старых эджей на новый в таблице equipment_parameter_handbook_value
    // TransSensorPlaceValue                     - метод по изменения старого ключа места на новое для сенсоров в таблице sensor_parameter_value
    // actionCompareTableId()                    - метод сравнения ключей таблиц
    // JoinTableParameters()                     - метод по объединению таблицы parameter по OPC тэгам
    // TransSituationJournal()                   - метод по переносу данных с таблиц situation_journal
    // TransEvents()                             - метод по переносу всех событий
    // actionToComplementTablesOld               - метод для дополнения таблиц нехватающими данными при репликации
    // actionComplementTables                    - метод для дополнения таблиц нехватающими данными
    // TransferRoutes                            - метод для переноса данных по маршрутам
    // actionMigrationDbSensor                   - метод переноса сенсоров из одной БД в другую
    // actionSynchroSensorParameterTableId       - метод синхронизации ключей записи sensor_parameter
    // actionSynchroWorkerParameterTableId       - метод синхронизации ключей записи worker_parameter
    // actionSynchrodepartmentParameterTableId   - метод синхронизации ключей записи department_parameter
    // actionTransSpv                            - метод по переносу spw минуя родительскую таблицу sensor_parameter
    // actonTransWpv                             - Метод по переносу данных с таблицы worker_parameter_value на новую БД с учетом изминения записи в таблице worker_parameter
    // actionMoveSensorParameterTableHistory     - метод архивирования таблицы sensor_parameter_value в sensor_parameter_value_history
    // actionMoveWorkerParameterTableHistory     - метод архивирования таблицы worker_parameter_value в worker_parameter_value_history

    //базовый метод синхронизации баз данных между собой
    // type_migration   -  тип переноса:
    //                              full                - полный перенос данных
    //                              big_table           - перенос данных c большими таблицами
    //                              param               - перенос данных параметров сенсоров/воркеров и т.д.
    //                              order               - ЭКНиП
    //                              handbook            - перенос справочников
    //                              synhro_table        - перенос таблиц синхронизации

    // 127.0.0.1/admin/serviceamicum/migration-db/sync-db?type_migration={'handbook':1,'order':1,'param':1,'synhro_table':1,'big_table':1}
    public function actionSyncDb()
    {
//        ini_set('max_execution_time', 6000000);
//        ini_set('memory_limit', "30500M");

        $method_name = "actionSyncDb";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            $post = Assistant::GetServerMethod();
            if (isset($post['type_migration']) and $post['type_migration'] != "") {
                $type_migration = json_decode($post['type_migration']);
            } else {
                $type_migration['handbook'] = 0;
                $type_migration['order'] = 0;
                $type_migration['param'] = 0;
                $type_migration['synhro_table'] = 0;
                $type_migration['big_table'] = 0;
            }

            /** Правильные методы */

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            if (isset($type_migration['handbook']) and
                $type_migration['handbook']
            ) {
                $warnings[] = $this->CopyTableDate('employee', -1, 3);
                $warnings[] = $this->CopyTableDate('department', -1, 3);
                $warnings[] = $this->CopyTableDate('company', -1, 3);
                $warnings[] = $this->CopyTableDate('department_type', -1, 3);
//        $warnings[] = $this->CopyTableDate('department_role');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('company_department', -1, 3);
                $warnings[] = $this->CopyTableDate('place_type', -1, 3);
                $warnings[] = $this->CopyTableDate('position', -1, 3);
                $warnings[] = $this->CopyTableDate('worker', -1, 3);
                $warnings[] = $this->CopyTableDate('kind_accident', -1, 3);
                $warnings[] = $this->CopyTableDate('kind_crash', -1, 3);
                $warnings[] = $this->CopyTableDate('kind_direction_store', -1, 3);
                $warnings[] = $this->CopyTableDate('kind_document');
                $warnings[] = $this->CopyTableDate('kind_duration');
                $warnings[] = $this->CopyTableDate('handbook_list');
                $warnings[] = $this->CopyTableDate('kind_fire_prevention_instruction');
                $warnings[] = $this->CopyTableDate('kind_group_situation');
                $warnings[] = $this->CopyTableDate('kind_incident');
                $warnings[] = $this->CopyTableDate('kind_mishap');
                $warnings[] = $this->CopyTableDate('kind_object');
                $warnings[] = $this->CopyTableDate('kind_parameter');
                $warnings[] = $this->CopyTableDate('kind_reason');
                $warnings[] = $this->CopyTableDate('kind_repair');
                $warnings[] = $this->CopyTableDate('kind_stop_pb');
                $warnings[] = $this->CopyTableDate('kind_violation');
                $warnings[] = $this->CopyTableDate('kind_working_time');
                $warnings[] = $this->CopyTableDate('role');
                $warnings[] = $this->CopyTableDate('object_type');
                $warnings[] = $this->CopyTableDate('object');
                $warnings[] = $this->CopyTableDate('group_med_report_result');
                $warnings[] = $this->CopyTableDate('worker_object');
                $warnings[] = $this->CopyTableDate('worker_object_role');
                $warnings[] = $this->CopyTableDate('parameter_type');
                $warnings[] = $this->CopyTableDate('unit');
                $warnings[] = $this->CopyTableDate('parameter');
//        $warnings[] = $this->CopyTableDate('worker_parameter_calc_value');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('function_type');
                $warnings[] = $this->CopyTableDate('func');
                $warnings[] = $this->CopyTableDate('function_parameter');
//            $warnings[] = $this->CopyTableDate('worker_extra');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('status_type');
                $warnings[] = $this->CopyTableDate('status');
                $warnings[] = $this->CopyTableDate('vid_document');
                $warnings[] = $this->CopyTableDate('document');
                $warnings[] = $this->CopyTableDate('siz_group');
                $warnings[] = $this->CopyTableDate('siz_kind');
                $warnings[] = $this->CopyTableDate('siz_subgroup');
                $warnings[] = $this->CopyTableDate('season');
                $warnings[] = $this->CopyTableDate('page');
                $warnings[] = $this->CopyTableDate('access');
                $warnings[] = $this->CopyTableDate('workstation');
                $warnings[] = $this->CopyTableDate('menu');
                $warnings[] = $this->CopyTableDate('workstation_menu');
                $warnings[] = $this->CopyTableDate('user');
                $warnings[] = $this->CopyTableDate('user_access');
                $warnings[] = $this->CopyTableDate('user_password');
                $warnings[] = $this->CopyTableDate('Settings_DCS');
                $warnings[] = $this->CopyTableDate('asmtp');
                $warnings[] = $this->CopyTableDate('sensor_type');
                $warnings[] = $this->CopyTableDate('xml_model');
                $warnings[] = $this->CopyTableDate('xml_send_type');
                $warnings[] = $this->CopyTableDate('xml_time_unit');
                $warnings[] = $this->CopyTableDate('group_alarm');
                $warnings[] = $this->CopyTableDate('event');
                $warnings[] = $this->CopyTableDate('xml_config');
                $warnings[] = $this->CopyTableDate('mine');
                $warnings[] = $this->CopyTableDate('mine_camera_rotation');
                $warnings[] = $this->CopyTableDate('plast');
                $warnings[] = $this->CopyTableDate('place');
//        $warnings[] = $this->CopyTableDate('object_place');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('operation_kind');
                $warnings[] = $this->CopyTableDate('operation_type');
                $warnings[] = $this->CopyTableDate('operation');
                $warnings[] = $this->CopyTableDate('operation_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('operation_parameters');
                $warnings[] = $this->CopyTableDate('shift');
//        $warnings[] = $this->CopyTableDate('brigade_parameter_calc_value');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('place_operation');
                $warnings[] = $this->CopyTableDate('place_operation_value');
                $warnings[] = $this->CopyTableDate('place_parameter_sensor');
                $warnings[] = $this->CopyTableDate('place_company_department');
                $warnings[] = $this->CopyTableDate('group_operation');
                $warnings[] = $this->CopyTableDate('route_type');
                $warnings[] = $this->CopyTableDate('conjunction');
                $warnings[] = $this->CopyTableDate('edge_type');
                $warnings[] = $this->CopyTableDate('edge');
                $warnings[] = $this->CopyTableDate('edge_status');
                $warnings[] = $this->CopyTableDate('attachment');
                $warnings[] = $this->CopyTableDate('research_type');
                $warnings[] = $this->CopyTableDate('research_index');
                $warnings[] = $this->CopyTableDate('plan_shift');
                $warnings[] = $this->CopyTableDate('type_operation');
                $warnings[] = $this->CopyTableDate('equipment');
                $warnings[] = $this->CopyTableDate('operation_equipment');
                $warnings[] = $this->CopyTableDate('operation_function');
                $warnings[] = $this->CopyTableDate('operation_group');
                $warnings[] = $this->CopyTableDate('type_object_function');
                $warnings[] = $this->CopyTableDate('type_object_parameter');
                $warnings[] = $this->CopyTableDate('type_object_parameter_function');
                $warnings[] = $this->CopyTableDate('type_object_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('type_object_parameter_sensor');
                $warnings[] = $this->CopyTableDate('union_equipment');
                $warnings[] = $this->CopyTableDate('unit_sap');
                $warnings[] = $this->CopyTableDate('unity_texture');
                $warnings[] = $this->CopyTableDate('stop_type');
                $warnings[] = $this->CopyTableDate('reason_check_knowledge');
                $warnings[] = $this->CopyTableDate('reason_danger_motion');
                $warnings[] = $this->CopyTableDate('reason_occupational_illness');
                $warnings[] = $this->CopyTableDate('activity');
                $warnings[] = $this->CopyTableDate('danger_level');
                $warnings[] = $this->CopyTableDate('group_situation');
                $warnings[] = $this->CopyTableDate('situation');
//        $warnings[] = $this->CopyTableDate('trigger');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('operation_regulation');
                $warnings[] = $this->CopyTableDate('shift_department');
                $warnings[] = $this->CopyTableDate('shift_mine');
                $warnings[] = $this->CopyTableDate('shift_schedule');
                $warnings[] = $this->CopyTableDate('shift_type');
                $warnings[] = $this->CopyTableDate('shift_worker');
                $warnings[] = $this->CopyTableDate('nomenclature');
                $warnings[] = $this->CopyTableDate('material');
//        $warnings[] = $this->CopyTableDate('strata_device_type');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('strata_gateway');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('strata_graph');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('strata_ip');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('strata_main');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('strata_mobile_device');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('strata_node');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('strata_nodes_position');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('strata_package_type');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('subscription');
//        $warnings[] = $this->CopyTableDate('tabel');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('tabel_worker');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('temp_comdep_del');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('temp_company_del');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('temp_dublicate_for_delete');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('temp_id_for_remove');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('temp_sphv');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('temp_table_worker_checkIn_mine');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('test_ora_sensors_type');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('test_planner');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('testreplication');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('text_message');
                $warnings[] = $this->CopyTableDate('type_accident');
                $warnings[] = $this->CopyTableDate('type_briefing');
                $warnings[] = $this->CopyTableDate('type_check_knowledge');
//        $warnings[] = $this->CopyTableDate('timetable');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('timetable_instruction_pb');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('timetable_pb');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('timetable_status');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('timetable_tabel');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('group_dep');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('group_dep_config');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('group_dep_parameter');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('group_dep_parameter_calc_value');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('group_dep_parameter_handbook_value');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('group_dep_worker');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('harmful_factors');
                $warnings[] = $this->CopyTableDate('case_pb');
//        $warnings[] = $this->CopyTableDate('chane_fact');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('classifier_diseases_kind');
                $warnings[] = $this->CopyTableDate('classifier_diseases_type');
                $warnings[] = $this->CopyTableDate('classifier_diseases');
                $warnings[] = $this->CopyTableDate('briefing_reason');
                $warnings[] = $this->CopyTableDate('internship_reason');
                $warnings[] = $this->CopyTableDate('activity_regulation');
//        $warnings[] = $this->CopyTableDate('area');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('passport');
                $warnings[] = $this->CopyTableDate('passport_parameter');
                $warnings[] = $this->CopyTableDate('passport_section');
                $warnings[] = $this->CopyTableDate('passport_sketch');
                $warnings[] = $this->CopyTableDate('passport_attachment');
                $warnings[] = $this->CopyTableDate('passport_cyclegramm_lava');
                $warnings[] = $this->CopyTableDate('passport_group_operation');
                $warnings[] = $this->CopyTableDate('passport_object');
                $warnings[] = $this->CopyTableDate('passport_operation');
                $warnings[] = $this->CopyTableDate('passport_operation_material');
                $warnings[] = $this->CopyTableDate('outcome');
                $warnings[] = $this->CopyTableDate('event_situation');
//        $warnings[] = $this->CopyTableDate('face');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('face_function');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('face_parameter');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('face_parameter_handbook_value');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('fact_tabel_worker');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('contingent');
                $warnings[] = $this->CopyTableDate('factors_of_contingent');

                $warnings[] = $this->CopyTableDate('working_time');
//        $warnings[] = $this->CopyTableDate('configuration_face');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('configuration_face_equipment');  //- удалена таблица


                $warnings[] = $this->CopyTableDate('department_parameter');
                $warnings[] = $this->CopyTableDate('department_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('department_parameter_summary');
                $warnings[] = $this->CopyTableDate('department_parameter_summary_worker_settings');
//        $warnings[] = $this->CopyTableDate('device');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('diseases');
//        $warnings[] = $this->CopyTableDate('downtime');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('downtime_dependency_face');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('employee_extra');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('employee_location');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('energy_mine');
                $warnings[] = $this->CopyTableDate('energy_mine_function');
                $warnings[] = $this->CopyTableDate('energy_mine_parameter');
                $warnings[] = $this->CopyTableDate('energy_mine_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('energy_mine_parameter_sensor');
//        $warnings[] = $this->CopyTableDate('instructor');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('memory_worker_checkin');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('message_sensor');
                $warnings[] = $this->CopyTableDate('message_specific_object');
//        $warnings[] = $this->CopyTableDate('min_date_checking_rostex');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('mine_situation');
                $warnings[] = $this->CopyTableDate('mine_situation_event');
                $warnings[] = $this->CopyTableDate('mine_situation_event_fact');
                $warnings[] = $this->CopyTableDate('mine_situation_fact');
                $warnings[] = $this->CopyTableDate('mine_situation_fact_parameter');
                $warnings[] = $this->CopyTableDate('mo_dopusk');
                $warnings[] = $this->CopyTableDate('mo_result');
//        $warnings[] = $this->CopyTableDate('modbus_tags');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('operation_reguation_fact_parameter');
                $warnings[] = $this->CopyTableDate('operation_regulation_fact');
                $warnings[] = $this->CopyTableDate('operation_regulation_fact_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('operation_regulation_parameter');
                $warnings[] = $this->CopyTableDate('operation_regulation_parameter_handbook_value');
//        $warnings[] = $this->CopyTableDate('path');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('path_edge');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('persn');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('phone_number');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('pla');
                $warnings[] = $this->CopyTableDate('pla_activity');
                $warnings[] = $this->CopyTableDate('pla_activity_fact');
                $warnings[] = $this->CopyTableDate('pla_fact');
//        $warnings[] = $this->CopyTableDate('podgroup_dep');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('podgroup_dep_worker');  //- удалена таблица
//            $warnings[] = $this->CopyTableDate('queue');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('reason');
//        $warnings[] = $this->CopyTableDate('recieved_strata_package');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('regulation');
                $warnings[] = $this->CopyTableDate('regulation_fact');
//        $warnings[] = $this->CopyTableDate('snmp_tags');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('workstation_page');
//        $warnings[] = $this->CopyTableDate('opc_tags');  //- удалена таблица
            }

            if (
                isset($type_migration['param']) and
                $type_migration['param']
            ) {
                $warnings[] = $this->CopyTableDate('worker_parameter');
                $warnings[] = $this->CopyTableDate('worker_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('worker_motion_less');
                $warnings[] = $this->CopyTableDate('worker_function');
                $warnings[] = $this->CopyTableDate('worker_type');
                $warnings[] = $this->CopyTableDate('sensor');
                $warnings[] = $this->CopyTableDate('connect_string');
                $warnings[] = $this->CopyTableDate('sensor_connect_string');
                $warnings[] = $this->CopyTableDate('sensor_function');
                $warnings[] = $this->CopyTableDate('sensor_parameter');
                $warnings[] = $this->CopyTableDate('sensor_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('sensor_parameter_sensor');
                $warnings[] = $this->CopyTableDate('worker_parameter_sensor', -1, 4);
                $warnings[] = $this->CopyTableDate('mine_function');
                $warnings[] = $this->CopyTableDate('mine_parameter');
                $warnings[] = $this->CopyTableDate('mine_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('mine_parameter_sensor');
                $warnings[] = $this->CopyTableDate('place_function');
                $warnings[] = $this->CopyTableDate('place_parameter');
                $warnings[] = $this->CopyTableDate('place_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('edge_changes');
                $warnings[] = $this->CopyTableDate('edge_changes_history');
                $warnings[] = $this->CopyTableDate('edge_function');
                $warnings[] = $this->CopyTableDate('edge_parameter');
                $warnings[] = $this->CopyTableDate('edge_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('equipment_function');
                $warnings[] = $this->CopyTableDate('equipment_parameter');
                $warnings[] = $this->CopyTableDate('equipment_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('equipment_parameter_sensor');
                $warnings[] = $this->CopyTableDate('plast_function');
                $warnings[] = $this->CopyTableDate('plast_parameter');
                $warnings[] = $this->CopyTableDate('plast_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('plast_parameter_sensor');
                $warnings[] = $this->CopyTableDate('event_journal');
                $warnings[] = $this->CopyTableDate('event_journal_correct_measure');
                $warnings[] = $this->CopyTableDate('event_journal_gilty');
                $warnings[] = $this->CopyTableDate('event_journal_status');
                $warnings[] = $this->CopyTableDate('event_status', -1, 1);
                $warnings[] = $this->CopyTableDate('main');
                $warnings[] = $this->CopyTableDate('pps_mine');
                $warnings[] = $this->CopyTableDate('pps_mine_function');
                $warnings[] = $this->CopyTableDate('pps_mine_parameter');
                $warnings[] = $this->CopyTableDate('pps_mine_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('pps_mine_parameter_sensor');
                $warnings[] = $this->CopyTableDate('worker_registered_without_checkin');
                $warnings[] = $this->CopyTableDate('nominal_conjunction_parameter');
                $warnings[] = $this->CopyTableDate('nominal_energy_mine_parameter');
                $warnings[] = $this->CopyTableDate('nominal_equipment_parameter');
////                $warnings[] = $this->CopyTableDate('nominal_face_parameter');  //- удалена таблица
                $warnings[] = $this->CopyTableDate('nominal_operation_parameter');
                $warnings[] = $this->CopyTableDate('nominal_operation_regulation_fact_parameter');
                $warnings[] = $this->CopyTableDate('nominal_operation_regulation_parameter');
                $warnings[] = $this->CopyTableDate('nominal_place_parameter');
                $warnings[] = $this->CopyTableDate('nominal_plast_parameter');
                $warnings[] = $this->CopyTableDate('nominal_pps_mine_parameter');
                $warnings[] = $this->CopyTableDate('nominal_sensor_parameter');
                $warnings[] = $this->CopyTableDate('nominal_type_object_parameter');
                $warnings[] = $this->CopyTableDate('nominal_worker_parameter');

                $warnings[] = $this->CopyTableDate('situation_fact');
                $warnings[] = $this->CopyTableDate('situation_journal');
                $warnings[] = $this->CopyTableDate('event_journal_situation_journal');
                $warnings[] = $this->CopyTableDate('event_situation_fact');
                $warnings[] = $this->CopyTableDate('situation_fact_parameter');
                $warnings[] = $this->CopyTableDate('situation_journal_correct_measure');
                $warnings[] = $this->CopyTableDate('situation_journal_gilty');
                $warnings[] = $this->CopyTableDate('situation_journal_send_status');
                $warnings[] = $this->CopyTableDate('situation_journal_status');
                $warnings[] = $this->CopyTableDate('situation_journal_zone');
                $warnings[] = $this->CopyTableDate('situation_status');
                $warnings[] = $this->CopyTableDate('event_compare_gas');
                $warnings[] = $this->CopyTableDate('conjunction_function');
                $warnings[] = $this->CopyTableDate('conjunction_parameter');
                $warnings[] = $this->CopyTableDate('conjunction_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('conjunction_parameter_sensor');
            }

            if (isset($type_migration['order']) and
                $type_migration['order']
            ) {
                $warnings[] = $this->CopyTableDate('route');
                $warnings[] = $this->CopyTableDate('siz');
                $warnings[] = $this->CopyTableDate('siz_store');
                $warnings[] = $this->CopyTableDate('worker_siz');
                $warnings[] = $this->CopyTableDate('worker_siz_status');
                $warnings[] = $this->CopyTableDate('brigade');
                $warnings[] = $this->CopyTableDate('brigade_parameter');
                $warnings[] = $this->CopyTableDate('brigade_parameter_handbook_value');
                $warnings[] = $this->CopyTableDate('brigade_worker');
                $warnings[] = $this->CopyTableDate('place_route');
                $warnings[] = $this->CopyTableDate('route_template');
                $warnings[] = $this->CopyTableDate('route_edge');
                $warnings[] = $this->CopyTableDate('route_template_edge');
                $warnings[] = $this->CopyTableDate('inquiry_attachment');
                $warnings[] = $this->CopyTableDate('document_attachment');
                $warnings[] = $this->CopyTableDate('company_department_attachment_type');
                $warnings[] = $this->CopyTableDate('company_department_attachment');
                $warnings[] = $this->CopyTableDate('company_department_info');
                $warnings[] = $this->CopyTableDate('company_department_route');
                $warnings[] = $this->CopyTableDate('company_department_worker_vgk');
                $warnings[] = $this->CopyTableDate('company_expert');
                $warnings[] = $this->CopyTableDate('checking_sout_type');
                $warnings[] = $this->CopyTableDate('sout');
                $warnings[] = $this->CopyTableDate('sout_attachment');
                $warnings[] = $this->CopyTableDate('sout_research');
                $warnings[] = $this->CopyTableDate('contingent_from_sout');
                $warnings[] = $this->CopyTableDate('contingent_harmful_factor_sout');
                $warnings[] = $this->CopyTableDate('planned_sout_kind');
                $warnings[] = $this->CopyTableDate('planned_sout');
                $warnings[] = $this->CopyTableDate('planned_sout_company_expert');
                $warnings[] = $this->CopyTableDate('working_place');
                $warnings[] = $this->CopyTableDate('planned_sout_working_place');
                $warnings[] = $this->CopyTableDate('amicum3.order');
                $warnings[] = $this->CopyTableDate('type_instruction_pb');
                $warnings[] = $this->CopyTableDate('instruction_pb');
                $warnings[] = $this->CopyTableDate('order_instruction_pb');
                $warnings[] = $this->CopyTableDate('order_itr_department');
                $warnings[] = $this->CopyTableDate('order_place');
                $warnings[] = $this->CopyTableDate('order_operation');
                $warnings[] = $this->CopyTableDate('order_operation_attachment');
                $warnings[] = $this->CopyTableDate('violation_type');
                $warnings[] = $this->CopyTableDate('violation');
                $warnings[] = $this->CopyTableDate('order_operation_img');
                $warnings[] = $this->CopyTableDate('order_vtb_ab');
                $warnings[] = $this->CopyTableDate('order_place_vtb_ab');
                $warnings[] = $this->CopyTableDate('order_place_vtb_ab_reason');
                $warnings[] = $this->CopyTableDate('order_operation_place_vtb_ab');
                $warnings[] = $this->CopyTableDate('order_operation_place_status_vtb_ab');
                $warnings[] = $this->CopyTableDate('chane_type');
                $warnings[] = $this->CopyTableDate('chane');
                $warnings[] = $this->CopyTableDate('chane_worker');
                $warnings[] = $this->CopyTableDate('operation_worker');
                $warnings[] = $this->CopyTableDate('order_operation_worker_status');
                $warnings[] = $this->CopyTableDate('order_status');
                $warnings[] = $this->CopyTableDate('order_status_attachment');
                $warnings[] = $this->CopyTableDate('order_template');
                $warnings[] = $this->CopyTableDate('stop');
                $warnings[] = $this->CopyTableDate('stop_face');
                $warnings[] = $this->CopyTableDate('checking_type');
                $warnings[] = $this->CopyTableDate('checking');
                $warnings[] = $this->CopyTableDate('checking_place');
                $warnings[] = $this->CopyTableDate('checking_plan');
                $warnings[] = $this->CopyTableDate('checking_worker_type');
                $warnings[] = $this->CopyTableDate('injunction');
                $warnings[] = $this->CopyTableDate('injunction_attachment');
                $warnings[] = $this->CopyTableDate('injunction_status');
                $warnings[] = $this->CopyTableDate('paragraph_pb');
                $warnings[] = $this->CopyTableDate('injunction_violation');
                $warnings[] = $this->CopyTableDate('injunction_violation_status');
                $warnings[] = $this->CopyTableDate('injunction_img');
                $warnings[] = $this->CopyTableDate('stop_pb');
                $warnings[] = $this->CopyTableDate('section');
                $warnings[] = $this->CopyTableDate('equipment_section');
                $warnings[] = $this->CopyTableDate('equipment_union');
                $warnings[] = $this->CopyTableDate('storage');
                $warnings[] = $this->CopyTableDate('template_order_vtb_ab');
                $warnings[] = $this->CopyTableDate('repair_map_typical');
                $warnings[] = $this->CopyTableDate('repair_map_specific');
                $warnings[] = $this->CopyTableDate('repair_map_specific_equipment_section');
                $warnings[] = $this->CopyTableDate('repair_map_typical_device');
                $warnings[] = $this->CopyTableDate('repair_map_typical_equipment_section');
                $warnings[] = $this->CopyTableDate('repair_map_typical_instrument');
                $warnings[] = $this->CopyTableDate('repair_map_typical_material');
                $warnings[] = $this->CopyTableDate('repair_map_typical_operation');
                $warnings[] = $this->CopyTableDate('repair_map_typical_role');
                $warnings[] = $this->CopyTableDate('chat_attachment_type');
                $warnings[] = $this->CopyTableDate('chat_type');
                $warnings[] = $this->CopyTableDate('chat_role');
                $warnings[] = $this->CopyTableDate('chat_room');
                $warnings[] = $this->CopyTableDate('chat_member');
                $warnings[] = $this->CopyTableDate('chat_member_config');
                $warnings[] = $this->CopyTableDate('chat_message');
                $warnings[] = $this->CopyTableDate('chat_message_favorites');
                $warnings[] = $this->CopyTableDate('chat_message_pinned');
                $warnings[] = $this->CopyTableDate('chat_message_reciever');
                $warnings[] = $this->CopyTableDate('chat_reciever_history');
                $warnings[] = $this->CopyTableDate('check_knowledge');
                $warnings[] = $this->CopyTableDate('check_knowledge_worker');
                $warnings[] = $this->CopyTableDate('check_knowledge_worker_status');
                $warnings[] = $this->CopyTableDate('check_protocol');
                $warnings[] = $this->CopyTableDate('audit');
                $warnings[] = $this->CopyTableDate('audit_place');
                $warnings[] = $this->CopyTableDate('audit_worker');
                $warnings[] = $this->CopyTableDate('briefing');
                $warnings[] = $this->CopyTableDate('briefer');
                $warnings[] = $this->CopyTableDate('inquiry_pb');
                $warnings[] = $this->CopyTableDate('event_pb');
                $warnings[] = $this->CopyTableDate('event_pb_worker');
                $warnings[] = $this->CopyTableDate('industrial_safety_object_type');
                $warnings[] = $this->CopyTableDate('industrial_safety_object');
                $warnings[] = $this->CopyTableDate('expertise');
                $warnings[] = $this->CopyTableDate('expertise_attachment');
                $warnings[] = $this->CopyTableDate('expertise_company_expert');
                $warnings[] = $this->CopyTableDate('expertise_equipment');
                $warnings[] = $this->CopyTableDate('expertise_history');
                $warnings[] = $this->CopyTableDate('expertise_status');
                $warnings[] = $this->CopyTableDate('fire_fighting_equipment');
                $warnings[] = $this->CopyTableDate('fire_fighting_equipment_specific');
                $warnings[] = $this->CopyTableDate('fire_fighting_equipment_documents');
                $warnings[] = $this->CopyTableDate('fire_fighting_equipment_specific_status');
                $warnings[] = $this->CopyTableDate('fire_fighting_equipment_type');
                $warnings[] = $this->CopyTableDate('fire_fighting_object');
                $warnings[] = $this->CopyTableDate('forbidden_zone');
                $warnings[] = $this->CopyTableDate('forbidden_edge');
                $warnings[] = $this->CopyTableDate('forbidden_type');
                $warnings[] = $this->CopyTableDate('forbidden_zapret');
                $warnings[] = $this->CopyTableDate('forbidden_time');
                $warnings[] = $this->CopyTableDate('forbidden_zapret_status');
                $warnings[] = $this->CopyTableDate('grafic_tabel_main');
                $warnings[] = $this->CopyTableDate('grafic_chane_table');
                $warnings[] = $this->CopyTableDate('grafic_tabel_date_fact');
                $warnings[] = $this->CopyTableDate('grafic_tabel_date_plan');
                $warnings[] = $this->CopyTableDate('grafic_tabel_status');
                $warnings[] = $this->CopyTableDate('graphic_list');
                $warnings[] = $this->CopyTableDate('graphic_repair');
                $warnings[] = $this->CopyTableDate('graphic_status');
                $warnings[] = $this->CopyTableDate('contracting_organization');
                $warnings[] = $this->CopyTableDate('contractor_company');
                $warnings[] = $this->CopyTableDate('correct_measures');
                $warnings[] = $this->CopyTableDate('correct_measures_attachment');
                $warnings[] = $this->CopyTableDate('cyclegramm_type');
                $warnings[] = $this->CopyTableDate('cyclegramm');
                $warnings[] = $this->CopyTableDate('cyclegramm_operation');
                $warnings[] = $this->CopyTableDate('document_event_pb');
                $warnings[] = $this->CopyTableDate('document_event_pb_attachment');
                $warnings[] = $this->CopyTableDate('document_event_pb_status');
                $warnings[] = $this->CopyTableDate('document_physical');
                $warnings[] = $this->CopyTableDate('document_physical_attachment');
                $warnings[] = $this->CopyTableDate('document_physical_status');
                $warnings[] = $this->CopyTableDate('document_status');
                $warnings[] = $this->CopyTableDate('inquiry_document');
                $warnings[] = $this->CopyTableDate('instrument');
                $warnings[] = $this->CopyTableDate('med_report_result');
                $warnings[] = $this->CopyTableDate('med_report');
                $warnings[] = $this->CopyTableDate('med_report_disease');
                $warnings[] = $this->CopyTableDate('order_permit');
                $warnings[] = $this->CopyTableDate('order_permit_attachment');
                $warnings[] = $this->CopyTableDate('order_permit_operation');
                $warnings[] = $this->CopyTableDate('order_permit_status');
                $warnings[] = $this->CopyTableDate('order_permit_worker');
                $warnings[] = $this->CopyTableDate('physical');
                $warnings[] = $this->CopyTableDate('physical_attachment');
                $warnings[] = $this->CopyTableDate('physical_esmo');
                $warnings[] = $this->CopyTableDate('physical_history');
                $warnings[] = $this->CopyTableDate('physical_kind');
                $warnings[] = $this->CopyTableDate('physical_schedule');
                $warnings[] = $this->CopyTableDate('physical_schedule_attachment');
                $warnings[] = $this->CopyTableDate('physical_worker');
                $warnings[] = $this->CopyTableDate('physical_worker_date');
                $warnings[] = $this->CopyTableDate('planogramma');
                $warnings[] = $this->CopyTableDate('planogramm_operation');
                $warnings[] = $this->CopyTableDate('repair_map_specific_device');
                $warnings[] = $this->CopyTableDate('repair_map_specific_instrument');
                $warnings[] = $this->CopyTableDate('repair_map_specific_material');
                $warnings[] = $this->CopyTableDate('repair_map_specific_operation');
                $warnings[] = $this->CopyTableDate('repair_map_specific_role');
                $warnings[] = $this->CopyTableDate('restriction_order');
                $warnings[] = $this->CopyTableDate('stop_pb_equipment');
                $warnings[] = $this->CopyTableDate('stop_pb_event');
                $warnings[] = $this->CopyTableDate('stop_pb_status');
                $warnings[] = $this->CopyTableDate('violator');
                $warnings[] = $this->CopyTableDate('worker_card');
                $warnings[] = $this->CopyTableDate('zipper_journal');
                $warnings[] = $this->CopyTableDate('zipper_journal_send_status');
                $warnings[] = $this->CopyTableDate('occupational_illness');
                $warnings[] = $this->CopyTableDate('occupational_illness_attachment');
                $warnings[] = $this->CopyTableDate('order_place_path');
                $warnings[] = $this->CopyTableDate('order_place_reason');
                $warnings[] = $this->CopyTableDate('order_relation');
                $warnings[] = $this->CopyTableDate('order_relation_status');
                $warnings[] = $this->CopyTableDate('order_route_worker');
                $warnings[] = $this->CopyTableDate('order_shift_fact');
                $warnings[] = $this->CopyTableDate('order_template_instruction_pb');
                $warnings[] = $this->CopyTableDate('order_template_place');
                $warnings[] = $this->CopyTableDate('order_template_operation');
                $warnings[] = $this->CopyTableDate('order_worker_coordinate');
                $warnings[] = $this->CopyTableDate('order_worker_vgk');
            }

            if (
                isset($type_migration['big_table']) and
                $type_migration['big_table']
            ) {
                $warnings[] = $this->CopyTableDate('bpd_package_info');
                $warnings[] = $this->CopyTableDate('strata_package_info');
                $warnings[] = $this->CopyTableDate('strata_package_source');
                $warnings[] = $this->CopyTableDate('snmp_package_info');
                $warnings[] = $this->CopyTableDate('summary_report_end_of_shift');
                $warnings[] = $this->CopyTableDate('summary_report_forbidden_zones');
                $warnings[] = $this->CopyTableDate('summary_report_forbidden_zones_result');
                $warnings[] = $this->CopyTableDate('summary_report_gaz_concentration');
                $warnings[] = $this->CopyTableDate('summary_report_sensor_gas_concentration');
                $warnings[] = $this->CopyTableDate('summary_report_time_spent');
                $warnings[] = $this->CopyTableDate('summary_report_time_table_report');
                $warnings[] = $this->CopyTableDate('summary_report_transport_history');
                $warnings[] = $this->CopyTableDate('type_object_parameter_value');
                $warnings[] = $this->CopyTableDate('mine_parameter_value');
                $warnings[] = $this->CopyTableDate('place_parameter_value');
                $warnings[] = $this->CopyTableDate('edge_parameter_value');
                $warnings[] = $this->CopyTableDate('plast_parameter_value');
                $warnings[] = $this->CopyTableDate('pps_mine_parameter_value');
                $warnings[] = $this->CopyTableDate('situation_fact_parameter_value');
                $warnings[] = $this->CopyTableDate('operation_parameter_value');
                $warnings[] = $this->CopyTableDate('equipment_parameter_value');
                $warnings[] = $this->CopyTableDate('sensor_parameter_value');
                $warnings[] = $this->CopyTableDate('worker_parameter_value');
                $warnings[] = $this->CopyTableDate('conjunction_parameter_value');
                $warnings[] = $this->CopyTableDate('department_parameter_value');
                $warnings[] = $this->CopyTableDate('energy_mine_parameter_value');
                $warnings[] = $this->CopyTableDate('mine_situation_fact_parameter_value');
                $warnings[] = $this->CopyTableDate('operation_regulation_fact_parameter_value');
                $warnings[] = $this->CopyTableDate('operation_regulation_parameter_value');
                $warnings[] = $this->CopyTableDate('worker_collection');
//        $warnings[] = $this->CopyTableDate('sensor_parameter_value_errors');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('worker_parameter_value_temp');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('sensor_parameter_value_temp');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('worker_parameter_value_temp_snapshot');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('sensor_parameter_value_temp_snapshot');  //- удалена таблица
//        $warnings[] = $this->CopyTableDate('face_parameter_value');  //- удалена таблица
            }

            if (
                isset($type_migration['synhro_table']) and
                $type_migration['synhro_table']
            ) {
                $warnings[] = $this->CopyTableDate('spr_business_unit');
                $warnings[] = $this->CopyTableDate('spr_hrm_department_update');
//                $warnings[] = $this->CopyTableDate('AMICUM_INSTRUCTION_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('AMICUM_PAB_N_N_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('AMICUM_ROSTEX_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_CHECK_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_DEC_MANAGER_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_ERROR_DIRECTION_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_FAILURE_EFFECT_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_INSTRUCTION_OPO_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_NORM_DOC_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_PLACE_AUDIT_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_REPRES_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('REF_SITUATION_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('SO_AMICUM_NOMENCL_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('SO_AMICUM_SIGN_WRITTEN_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('SO_AMICUM_WORK_WEAR_MV');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('HCM_HRSROOT_PERNR_VIEW');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('HCM_STRUCT_OBJID_VIEW');    // отключены т.к. в них нет поля id
                $warnings[] = $this->CopyTableDate('sap_role_full');
                $warnings[] = $this->CopyTableDate('sap_role_update');
                $warnings[] = $this->CopyTableDate('sap_siz_update');
                $warnings[] = $this->CopyTableDate('sap_skud_update');
                $warnings[] = $this->CopyTableDate('sap_skud_update_ZAP');
                $warnings[] = $this->CopyTableDate('sap_user_copy');
                $warnings[] = $this->CopyTableDate('sap_user_update');
//                $warnings[] = $this->CopyTableDate('sap_worker_card');    // отключены т.к. в них нет поля id
                $warnings[] = $this->CopyTableDate('sap_worker_siz_update');
//                $warnings[] = $this->CopyTableDate('sap_asu_siz_full');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('sap_asu_worker_siz_full');    // отключены т.к. в них нет поля id
                $warnings[] = $this->CopyTableDate('sap_asu_worker_siz_update');
                $warnings[] = $this->CopyTableDate('sap_company_full');
                $warnings[] = $this->CopyTableDate('sap_company_update');
                $warnings[] = $this->CopyTableDate('sap_department_full');
                $warnings[] = $this->CopyTableDate('sap_department_update');
                $warnings[] = $this->CopyTableDate('sap_employee_full');
                $warnings[] = $this->CopyTableDate('sap_employee_update');
                $warnings[] = $this->CopyTableDate('sap_positioin_yagok');
                $warnings[] = $this->CopyTableDate('sap_position_full');
                $warnings[] = $this->CopyTableDate('sap_position_group_from_contact_view');
                $warnings[] = $this->CopyTableDate('sap_position_update');
//                $warnings[] = $this->CopyTableDate('sap_ref_error_direction_mv');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('sap_ref_norm_doc_mv');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('sap_amicum_lookuot_action_mv');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('sap_amicum_rostext_mv');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('sap_amicum_stop_lookout_act_mv');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('sap_hcm_hrsroot_pernr_view');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('sap_hcm_struct_objid_view');    // отключены т.к. в них нет поля id
//                $warnings[] = $this->CopyTableDate('sap_instruction_givers_mv');    // отключены т.к. в них нет поля id
                //        $warnings[] = $this->CopyTableDate('USER_BLOB_MV');
            }

            /** конец */

            /** Отладка */
            $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (\Throwable $ex) {
            $errors[] = $method_name . '. Исключение:';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
//            LogCacheController::setGasLogValue('createEventForWorkerGas', array('Items' => $result,
//                'errors' => $errors,
//                'status' => $status,
//                'warnings' => $warnings), 2);
        }

        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    // CopyTableDate - копирование таблиц с одинаковой структурой из таблицы источника в таблицу назначение
    // $sync_table_source       -   откуда берем данные
    // $sync_table_target       -   куда кладем данные
    // $type_copy_table         -   тип переноса

    // Вариант №1. Вставка по одной записи, но не нужно определять название столбцов
    // Вариант №2. Полная выборка и массовая вставка частями
    // Вариант №3. Массовая вставка за один заход, но нужно определять название столбцов
    // Вариант №4. Выборка и вставка частями
    public function CopyTableDate($sync_table_source, $sync_table_target = -1, $type_copy_table = 4)
    {
        $method_name = "CopyTableDate";
        $warnings[] = $sync_table_source . " Начало переноса";
        if ($sync_table_target == -1) {
            $sync_table_target = $sync_table_source;
        }
        $duration_method = 0;
        $microtime_start = microtime(true);
//        ini_set('max_execution_time', 600000);
//        ini_set('memory_limit', "20500M");
        //Assistant::PrintR("Начинаю подключаться к базе данных назначения для очистки таблицы записи " . $sync_table_target);
        $target_table = Yii::$app->db_target->createCommand()->delete($sync_table_target)->execute(); //очистка таблицы назначения для устранения проблем с дубликатами
        //Assistant::PrintR("---Очистил таблицу назначения " . $sync_table_target);
        //Assistant::PrintR("---Начинаю выборку данных таблицы источника " . $sync_table_source);

        //Assistant::PrintR("---Закончил выборку данных таблицы источника " . $sync_table_source);
        //Assistant::PrintR("---Начинаю запись данных в таблицы назначение " . $sync_table_target);
        $sql_query_source_column = "SHOW COLUMNS FROM $sync_table_source"; //определяем столбцы исходной таблицы
        $source_table_columns = Yii::$app->db_source->createCommand($sql_query_source_column)->queryAll();
        $source_column = array();
        foreach ($source_table_columns as $source_table_column) {
            $source_column[] = $source_table_column['Field'];
        }

        if ($type_copy_table != 4) {
// Выборка для вариантов 1-3.
            $sql_query_source = "SELECT * FROM $sync_table_source LIMIT 15000"; //запрос исходных данных
            $source_table = Yii::$app->db_source->createCommand($sql_query_source)->queryAll();

// Вариант №1. Вставка по одной записи, но не нужно определять название столбцов
            if ($type_copy_table == 1) {
                foreach ($source_table as $source_row) $target_table = Yii::$app->db_target->createCommand()->insert($sync_table_target, $source_row)->execute();
            }
// Вариант №2. Полная выборка и массовая вставка частями
            if ($type_copy_table == 2) {
                $count = 0;
                foreach ($source_table as $source_row) {
                    $source_row_array[] = $source_row;
                    $count++;
                    if ($count == 2000) {
                        $target_table_count = Yii::$app->db_target->createCommand()->batchInsert($sync_table_target, $source_column, $source_row_array)->execute();
                        $warnings[] = $sync_table_source . " Количество вставленных записей: " . $target_table_count;
                        $count = 0;
                        unset($source_row_array);
                    }
                }
                if (isset($source_row_array)) {
                    $target_table_count = Yii::$app->db_target->createCommand()->batchInsert($sync_table_target, $source_column, $source_row_array)->execute();
                    $warnings[] = $sync_table_source . " Количество вставленных записей: " . $target_table_count;
                }
            }

// Вариант №3. Массовая вставка за один заход, но нужно определять название столбцов
            if ($type_copy_table == 3) {
                $sql_query_source_column = "SHOW COLUMNS FROM $sync_table_source"; //определяем столбцы исходной таблицы
                $source_table_columns = Yii::$app->db_source->createCommand($sql_query_source_column)->queryAll();
                $source_column = array();
                foreach ($source_table_columns as $source_table_column) {
                    $source_column[] = $source_table_column['Field'];
                }
                $target_table_count = Yii::$app->db_target->createCommand()->batchInsert($sync_table_target, $source_column, $source_table)->execute();//запрос исходных данных
                $warnings[] = $sync_table_source . " Количество вставленных записей: " . $target_table_count;
            }
        }
// Вариант №4. Выборка и вставка частями
        if ($type_copy_table == 4) {
            $counter_iteration = true;
            $last_id = 0;
            $count_all = 0;
            while ($counter_iteration) {

                $source_table = Yii::$app->db_source->createCommand("SELECT * FROM $sync_table_source where id > " . $last_id . ' limit 15000')->queryAll();
                if (count($source_table) < 15000) {
                    $counter_iteration = false;
                }
                $source_row_array = null;                                                         //в данной перемены будет размешена последння итерация foreach так как если данные меньше 2500 по внутренной условии данные не попадут в БД
                foreach ($source_table as $source_row) {
                    $source_row_array[] = $source_row;
                    $count_all++;
                    $last_id = $source_row['id'];
                }
                unset($source_table);
                //вставка остаток записей в source_row
                if (!empty($source_row_array)) {
                    $target_table_count = Yii::$app->db_target->createCommand()->batchInsert($sync_table_target, $source_column, $source_row_array)->execute();
                    $warnings[] = $sync_table_source . " Количество вставленных записей: " . $target_table_count;
                    if (!$target_table_count) {
                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу ' . $sync_table_target . " " . $target_table_count);
                    }
                }
                unset($source_row_array);
            }
            //Assistant::PrintR("Последний id: $last_id. Закончил копирование из $sync_table_source в " . $sync_table_target);
        }
        //$sql_query_target="SELECT * FROM $sync_table_target"; //проверка вставки данных
        //$target_table=Yii::$app->db_target->createCommand($sql_query_target)->queryAll();
        $duration_method = microtime(true) - $microtime_start;
        //Assistant::PrintR("Продолжительность $duration_method. Закончил копирование из $sync_table_source в " . $sync_table_target);
        $warnings[] = $sync_table_source . " Продолжительность: " . $duration_method;
        $warnings[] = $sync_table_source . " Окончание переноса";
        return $warnings;
    }

    //actionMoveCacheToDb - метод миграции справочных значений сеноров из кеша в базу данных - применяется в тех случаях когда есть кеш, но похерена база.
    public function actionMoveCacheToDb()
    {
        $cache = Yii::$app->cache; //todo метод не корректен по причине не верного использования кеша сейчас такого нет
        $sensors = $cache->Get('SensorMine_270');
        foreach ($sensors as $sensor) {

            $sensor_parameter_value = $cache->Get('SensorParameter_' . $sensor['sensor_id'] . '_1-122');
            if ($sensor_parameter_value) {
                try {
                    Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_handbook_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], [[$sensor_parameter_value['sensor_parameter_id'], $sensor_parameter_value['handbook_date_time_work'], $sensor_parameter_value['handbook_value'], '1']])->execute();
                } catch (\Exception $e) {
                    echo 'dublicate';
                }
            } else {
                echo $sensor['sensor_id'];
            }
            $sensor_parameter_value = $cache->Get('SensorParameter_' . $sensor['sensor_id'] . '_1-83');
            if ($sensor_parameter_value) {
                try {
                    Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_handbook_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], [[$sensor_parameter_value['sensor_parameter_id'], $sensor_parameter_value['handbook_date_time_work'], $sensor_parameter_value['handbook_value'], '1']])->execute();
                } catch (\Exception $e) {
                    echo 'dublicate';
                }
            } else {
                echo $sensor['sensor_id'];
            }
            $sensor_parameter_value = $cache->Get('SensorParameter_' . $sensor['sensor_id'] . '_1-269');
            if ($sensor_parameter_value) {
                try {
                    Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_handbook_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], [[$sensor_parameter_value['sensor_parameter_id'], $sensor_parameter_value['handbook_date_time_work'], $sensor_parameter_value['handbook_value'], '1']])->execute();
                } catch (\Exception $e) {
                    echo 'dublicate';
                }
            } else {
                echo $sensor['sensor_id'];
            }
        }

    }

    /**
     * TransferSensors() - метод переноса данные по сенсорам с одной БД в другой с новыми айдишниками (id)
     * Аогаритм:
     * 1. Загружаем данные с родителькой таблицы (например sensors)
     * 2. По одному вставляем в новый БД и оотуда получаем новый айдишники объекта(например sensor'a)
     * 3. Полученного айди вставлем в новый объект (например last_sensor_id[new_sensor_id])
     * 4. Получем следующую дочерную таблицу (например sensor_parameter)
     * 5. По одному вставляем в новый БД с новыми айдишниками родительской таблицы и оттуда получаем новый айдишники объекта
     * 6. Получаем слдующую доченную таблицу и по аналогии 5-го пункта
     *
     * Список задействованых таблиц:
     * sensor
     *   sensor_parameter
     *       sensor_parameter_value
     *       sensor_parameter_handbook_value
     *       sensor_parameter_value_errors
     *       sensor_parameter_sensor
     *
     * Пример вызова: 127.0.0.1:98/admin/serviceamicum/migration-db/transfer-sensors
     */
    public static function TransferSensors()
    {
        // Стартовая отладочная информация
        $method_name = 'actionTransferSensors';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


        $sensor_new_ids = array();//новые айдишники сенсоров
        $sensor_new_and_last_ids = array();//новые айдишники сенсоров
        $sensor_parameter_new_ids = array();//новый айдишники параметров сенсоров
        $sensor_parameter_new_and_last_ids = array();//новый айдишники параметров сенсоров


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        /**
         * список задействованых таблиц
         * main
         * sensor
         *   sensor_parameter
         *       sensor_parameter_value
         *       sensor_parameter_handbook_value
         *       sensor_parameter_sensor
         */

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // запись в БД начала выполнения скрипта
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new \Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }


            /**
             * 1. Загружаем данные с родителькой таблицы (sensors)
             */
            $sensors = Yii::$app->db_source->createCommand('SELECT * FROM sensor')->queryAll();

            /** Отладка */
            $description = 'Получил данные по sensor';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            /**
             * 2. По одному вставляем в новый БД и оотуда получаем новый айдишники объекта(sensor'a)
             */

            foreach ($sensors as $sensor) {
                $new_main = new MainSync();
                $new_main->table_address = 'sensor';
                $new_main->db_address = 'amicum3';

                if (!$new_main->save()) {
                    $errors[] = $new_main->errors;
                    throw new Exception($method_name . '. Не удалось сохранить новый id в таблице main');
                }

                //$new_main->refresh();


                Yii::$app->db_target->createCommand()->insert('sensor', [
                    'id' => $new_main->id,
                    'title' => $sensor['title'],
                    'sensor_type_id' => $sensor['sensor_type_id'],
                    'asmtp_id' => $sensor['asmtp_id'],
                    'object_id' => $sensor['object_id'],
                ])->execute();


                /**
                 * 3. Полученного айди вставлем в новый объект (например last_sensor_id[new_sensor_id])
                 */

                $sensor_new_and_last_ids[$sensor['id']] = $new_main->id;

                $sensor_new_ids[] = array(
                    'sensor_last_id' => $sensor['id'],
                    'sensor_new_id' => $new_main->id,
                );
                $count_all++;
            }
            unset($sensors);


            /** Отладка */
            $description = 'Заполнили новую таблицу Sensor';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;

            //вставка новые и старые айдишники во временную талицу sensor_new_ids
            $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('sensor_new_ids', ['sensor_last_id', 'sensor_new_id'], $sensor_new_ids)->execute();
            $count_all = $insert_result_to_MySQL;
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу sensor_new_ids' . $insert_result_to_MySQL);
            }
            unset($insert_result_to_MySQL);
            unset($sensor_new_ids);

            /** Отладка */
            $description = 'Создал трансферную таблицу айдишников sensor';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**
             * 4. Получем следующую дочерную таблицу (sensor_parameter)
             */

            /** Отладка */
            $description = 'Получаю sensor_parameter';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $sensor_parameters = Yii::$app->db_source->createCommand('SELECT * FROM sensor_parameter')->queryAll();

            $count_all = 0;
            $warnings[] = $method_name . '. Начинаю вставку  в sensor_parameter';
            foreach ($sensor_parameters as $each) {

                /**
                 * 5. По одному вставляем в новый БД с новыми айдишниками родительской таблицы и оттуда получаем новый айдишники объекта
                 */
                Yii::$app->db_target->createCommand()->insert('sensor_parameter',
                    [

                        'sensor_id' => $sensor_new_and_last_ids[$each['sensor_id']],
                        'parameter_id' => $each['parameter_id'],
                        'parameter_type_id' => $each['parameter_type_id']
                    ]
                )->execute();
                $new_sp_id = Yii::$app->db_target->getLastInsertID();
                $sensor_parameter_new_and_last_ids[$each['id']] = $new_sp_id;

                $sensor_parameter_new_ids[] = array(
                    'sensor_parameter_last_id' => $each['id'],
                    'sensor_parameter_new_id' => $new_sp_id
                );
                $count_all++;

            }
            unset($sensor_new_and_last_ids);
            unset($sensor_parameters);
            /** Отладка */
            $description = 'Закончил вставку в sensor_parameter';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;

            $warnings[] = $method_name . '. Начинаю вставку новые и старые айдишники во временную талицу sensor_parameter_new_ids';
            //вставка новые и старые айдишники во временную талицу sensor_parameter_new_ids

            $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_new_ids', ['sensor_parameter_last_id', 'sensor_parameter_new_id'], $sensor_parameter_new_ids)->execute();
            $count_all = $insert_result_to_MySQL;
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу sensor_parameter_new_ids' . $insert_result_to_MySQL);
            }
            unset($insert_result_to_MySQL);
            unset($sensor_parameter_new_ids);
            /** Отладка */
            $description = 'Закончил вставку в sensor_parameter_new_ids';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . '. Закончил вставку новые и старые айдишники во временную талицу sensor_parameter_new_ids';

            /**
             * 6. Получаем слдующую доченную таблицу и по аналогии 5-го пункта
             */
            $warnings[] = $method_name . '. Приступаю к sensor_parameter_value';


            $counter_iteration = true;
            $last_id = 0;
            while ($counter_iteration) {

                $spv = Yii::$app->db_source->createCommand('SELECT * FROM sensor_parameter_value where id > ' . $last_id . ' limit 15000')->queryAll();
                if (count($spv) < 15000) {
                    $counter_iteration = false;
                }
                $data_sensor_parameter_value_to_batchInsert = null;//в данной перемены будет размешена последння итерация foreach так как если данные меньше 2500 по внутренной условии данные не попадут в БД
                foreach ($spv as $sensor_parameter_value) {
                    $data_sensor_parameter_value_to_batchInsert[] = array(
                        'sensor_parameter_id' => $sensor_parameter_new_and_last_ids[$sensor_parameter_value['sensor_parameter_id']],
                        'date_time' => $sensor_parameter_value['date_time'],
                        'value' => $sensor_parameter_value['value'],
                        'status_id' => $sensor_parameter_value['status_id']
                    );
                    $count_all++;
                    $last_id = $sensor_parameter_value['id'];
                }
                unset($spv);
                //вставка остаток записей в sensor_parameter_value
                if (!empty($data_sensor_parameter_value_to_batchInsert)) {
                    $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $data_sensor_parameter_value_to_batchInsert)->execute();

                    if (!$insert_result_to_MySQL) {
                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу sensor_parameter_value' . $insert_result_to_MySQL);
                    }
                }
                unset($data_sensor_parameter_value_to_batchInsert);


            }


            /** Отладка */
            $description = 'Закончил вставку в sensor_parameter_value';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . '. Закончил вставку остаток записей в sensor_parameter_value';

            /**
             * Перенос и переиндентификация sensor_parameter_handbook_value
             */
            $warnings[] = $method_name . '. Приступаю к sensor_parameter_handbook_value';


            $counter_iteration = true;

            $last_id = 0;
            while ($counter_iteration) {

                $sphv = Yii::$app->db_source->createCommand('SELECT * FROM sensor_parameter_handbook_value where id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($sphv) < 25000) {
                    $counter_iteration = false;
                }
                $data_sensor_parameter_handbook_valuee_to_batchInsert = null;//в данной перемены будет размешена последння итерация foreach так как если данные меньше 2500 по внутренной условии данные не попадут в БД
                foreach ($sphv as $sensor_parameter_value) {
                    $last_id = $sensor_parameter_value['id'];

                    $data_sensor_parameter_handbook_valuee_to_batchInsert[] = array(
                        'sensor_parameter_id' => $sensor_parameter_new_and_last_ids[$sensor_parameter_value['sensor_parameter_id']],
                        'date_time' => $sensor_parameter_value['date_time'],
                        'value' => $sensor_parameter_value['value'],
                        'status_id' => $sensor_parameter_value['status_id']
                    );

                    $count_all++;
                }
                unset($sphv);


                if (!empty($data_sensor_parameter_handbook_valuee_to_batchInsert)) {
                    $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_handbook_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $data_sensor_parameter_handbook_valuee_to_batchInsert)->execute();

                    if (!$insert_result_to_MySQL) {
                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу sensor_parameter_value' . $insert_result_to_MySQL);
                    }
                }
                unset($data_sensor_parameter_handbook_valuee_to_batchInsert);

            }


            /** Отладка */
            $description = 'Закончил вставку в sensor_parameter_handbook_value';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            /**
             * Перенос и переиндентификация sensor_parameter_sensor
             */
            $warnings[] = $method_name . '. Приступаю к sensor_parameter_sensor';
            $count_all = 0;
            $sensor_parameter_sensors = Yii::$app->db_source->createCommand('SELECT * FROM sensor_parameter_sensor')->queryAll();
            foreach ($sensor_parameter_sensors as $sensor_parameter_sensor) {

                // $sensor_parameter_sensor_temp[$sensor_parameter_sensor['sensor_parameter_id']] = $sensor_parameter_new_ids[$sensor_parameter_sensor['id']];
                if ($sensor_parameter_sensor['sensor_parameter_id_source'] == '-1') {
                    $data_to_batchInsert[] = array(
                        'sensor_parameter_id' => $sensor_parameter_new_and_last_ids[$sensor_parameter_sensor['sensor_parameter_id']],
                        'sensor_parameter_id_source' => $sensor_parameter_sensor['sensor_parameter_id_source'],
                        'date_time' => $sensor_parameter_sensor['date_time']
                    );
                } else {
                    if (isset($sensor_parameter_new_and_last_ids[$sensor_parameter_sensor['sensor_parameter_id_source']])) {
                        $data_to_batchInsert[] = array(

                            'sensor_parameter_id' => $sensor_parameter_new_and_last_ids[$sensor_parameter_sensor['sensor_parameter_id']],
                            'sensor_parameter_id_source' => $sensor_parameter_new_and_last_ids[$sensor_parameter_sensor['sensor_parameter_id_source']],
                            'date_time' => $sensor_parameter_sensor['date_time']
                        );
                    } else {
                        $errors[] = $method_name . ' не смог найти sensor_parameter_id_source =' . $sensor_parameter_sensor['sensor_parameter_id_source'] . ' это означает что такой sensor_parameter_id нет';
                    }
                }

            }
            unset($sensor_parameter_sensors);

            //массовая вставка значение в БД в sensor_parameter_handbook_value
            $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_sensor', ['sensor_parameter_id', 'sensor_parameter_id_source', 'date_time'], $data_to_batchInsert)->execute();
            $count_all = $insert_result_to_MySQL;
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу sensor_parameter_sensor' . $insert_result_to_MySQL);
            }

            /** Отладка */
            $description = 'Закончил вставку в sensor_parameter_handbook_value';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            unset($data_to_batchInsert);
            unset($insert_result_to_MySQL);
        } catch (\Throwable $ex) {
            $errors[] = $method_name . '. Исключение:';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
//            LogCacheController::setGasLogValue('createEventForWorkerGas', array('Items' => $result,
//                'errors' => $errors,
//                'status' => $status,
//                'warnings' => $warnings), 2);
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /*  Yii::$app->response->format = Response::FORMAT_JSON;
          Yii::$app->response->data = $result_main;*/
    }


    /**
     * TransferSensorConnectString - Метод по переносу и переидентификации данных с таблицы sensor_connect_string
     * Метод получает значение из веменной таблицы где хранены стары значение и новые айдишкники сенсоров,
     * далее получет значение с таблицы sensor_connect_string и меняя старые айдишники на новые вставляет значение в новую БД (db_target)
     * Пример вызова: 127.0.0.1:98/admin/serviceamicum/migration-db/transfer-sensor-connect-string
     */
    public static function TransferSensorConnectString()
    {
        // Стартовая отладочная информация
        $method_name = 'TransferSensorConnectString';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        $status = 1;
        $warnings = array();
        $errors = array();
        $sensors_new_and_last_ids = array();                                                                            // объект с новыми и счтарими айдишникам сенсоров
        $connect_string_new_and_last_ids = array();                                                                     // объект с новыми и счтарими айдишникам строки подключения
        $data_to_batchIsert = array();
        /** Отладка */
        $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */


        try {
            $warnings[] = __FUNCTION__ . '';
            $warnings[] = __FUNCTION__ . ' Получаю значение с таблицы sensor_new_ids';
            $sensor_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM sensor_new_ids')->queryAll();
            foreach ($sensor_new_ids as $sensors_new_id) {
                $count_all++;
                $sensors_new_and_last_ids[$sensors_new_id['sensor_last_id']] = $sensors_new_id['sensor_new_id'];
            }
            unset($sensor_new_ids);
            /** Отладка */
            $description = 'Получил значение с таблицы sensor_new_ids';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $count_all = 0;
            $warnings[] = __FUNCTION__ . ' Получаю значение с таблицы connect_string_new_ids';
            $connect_string_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM connect_string_new_ids')->queryAll();
            foreach ($connect_string_new_ids as $connect_string_new_id) {
                $count_all++;
                $connect_string_new_and_last_ids[$connect_string_new_id['connect_string_last_id']] = $connect_string_new_id['connect_string_new_id'];
            }
            unset($connect_string_new_ids);

            /** Отладка */
            $description = 'Получил значение с таблицы connect_string_new_ids';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            $warnings[] = __FUNCTION__ . ' Получаю значение с таблицы SensorConnectString';
            $sensor_connect_strings = Yii::$app->db_source->createCommand('SELECT * FROM sensor_connect_string')->queryAll();
            $count_all = 0;
            foreach ($sensor_connect_strings as $sensor_connect_string) {
                $data_to_batchIsert[] = array(
                    'sensor_id' => $sensors_new_and_last_ids[$sensor_connect_string['sensor_id']],
                    'connect_string_id' => $connect_string_new_and_last_ids[$sensor_connect_string['connect_string_id']],
                    'date_time' => $sensor_connect_string['date_time'],
                    'subscription_id' => $sensor_connect_string['subscription_id']
                );
                $count_all++;
            }

            $resultInsert = Yii::$app->db_target->createCommand()->batchInsert('sensor_connect_string',
                [
                    'sensor_id',
                    'connect_string_id',
                    'date_time',
                    'subscription_id',
                ], $data_to_batchIsert
            )->execute();
            if (!$resultInsert) {
                throw new \yii\db\Exception(' Возникла ошибка при сохранение данных в sensor_connect_string');
            }

            /** Отладка */
            $description = 'Перекинул новые айдишники дынные в SensorConnectString';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {
            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
    }

    /**
     * TransferConnectString - метод по замени айдишников таблицы connect_string
     * ВАЖНО: Метод надо запустит после переноса данной таблицы методом actionSyncDb и до начала переноса данные по сенсорам
     *
     */
    public static function TransferConnectString()
    {

        // Стартовая отладочная информация
        $method_name = 'TransferConnectString';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта
        $status = 1;
        $warnings = array();
        $errors = array();
        $ids_to_connect_string_new_ids = array();
        $data_to_batchIsert = array();
        /** Отладка */
        $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        try {

            $data_from_table_conn_string = Yii::$app->db_source->createCommand('SELECT * FROM connect_string')->queryAll();
            foreach ($data_from_table_conn_string as $connect_string) {


                $data_from_old_db = Yii::$app->db_target->createCommand()->insert('connect_string',
                    [
                        'title' => $connect_string['title'],
                        'ip' => $connect_string['ip'],
                        'connect_string' => $connect_string['connect_string'],
                        'Settings_DCS_id' => $connect_string['Settings_DCS_id'],
                        'source_type' => $connect_string['source_type'],
                    ]
                )->execute();
                if (!$data_from_old_db) {
                    throw new Exception($method_name . '. Не удалось добавить запись в таблице connect_string');
                }
                $new_connect_string_id = Yii::$app->db_target->getLastInsertID();
                $ids_to_connect_string_new_ids[] = array(
                    'connect_string_last_id' => $connect_string['id'],
                    'connect_string_new_id' => $new_connect_string_id
                );

                $count_all++;
            }
            /** Отладка */
            $description = 'Закончил запись в connect_string';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $count_all = 0;
            $warnings[] = $method_name . 'Начинаю укладовать новый айдишники во временную таблицу connect_string_new_ids';
            $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('connect_string_new_ids', ['connect_string_last_id', 'connect_string_new_id'], $ids_to_connect_string_new_ids)->execute();
            $count_all = $insert_result_to_MySQL;
            if (!$insert_result_to_MySQL) {
                throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу connect_string_last_id' . $insert_result_to_MySQL);
            }

            /** Отладка */
            $description = 'Закончил запись в connect_string';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }


        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);


//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;


    }


    /**
     * TransferWorkerParameters() - метод переноса данные по работникам с одной БД в другой с новыми айдишниками (id)
     * Пример вызова: 127.0.0.1:98/admin/serviceamicum/migration-db/transfer-worker-parameters
     * Спосок задействоаных таблиц:
     * worker_parameter
     *      worker_parameter_value
     *      worker_parameter_handbook_value
     *      worker_parameter_sensor
     */
    public static function TransferWorkerParameters()
    {
        // Стартовая отладочная информация
        $method_name = 'TransferWorkerParameters ';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


        $worker_parameter_sensor = array();
        $sensors_new_and_last_ids = array();
        $worker_parameter_handbook_value = array();
        $worker_parameter_value = array();
        $worker_parameter = array();
        $worker_parameter_new_ids = array();//новый айдишники параметров сенсоров
        $worker_parameter_new_and_last_ids = array();//новый айдишники параметров сенсоров
        $data_to_db = null;


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            $warnings[] = $method_name . 'Получею данные с worker_parameters';
            $worker_parameters = Yii::$app->db_source->createCommand('SELECT * FROM worker_parameter')->queryAll();
            $worker_objects = Yii::$app->db_target->createCommand('SELECT id FROM worker_object')->queryAll();
            foreach ($worker_objects as $worker_object) {
                $worker_object_obj[$worker_object['id']] = $worker_object['id'];
            }
            unset($worker_objects);


            foreach ($worker_parameters as $each) {

                $worker_parameter_check = Yii::$app->db_target->createCommand('SELECT id FROM worker_parameter where worker_object_id = ' . $each['worker_object_id'] . ' and  parameter_id = ' . $each['parameter_id'] . ' and parameter_type_id = ' . $each['parameter_type_id'])->queryAll();
                if (!$worker_parameter_check) {
                    if (isset($worker_object_obj[$each['worker_object_id']])) {
                        $data_to_db[] = array();
                        Yii::$app->db_target->createCommand()->insert('worker_parameter',
                            [
                                'worker_object_id' => $each['worker_object_id'],
                                'parameter_id' => $each['parameter_id'],
                                'parameter_type_id' => $each['parameter_type_id'],
                            ]
                        )->execute();
                        $worker_parameter_id = Yii::$app->db_target->getLastInsertID();

                        $worker_parameter_new_and_last_ids[$each['id']] = $worker_parameter_id;

                        $worker_parameter_new_ids[] = array(
                            'worker_parameter_new_id' => $worker_parameter_id,
                            'worker_parameter_last_id' => $each['id']
                        );

                    }


                    $count_all++;
                }

            }
            unset($worker_parameters);
            unset($worker_object_obj);


            /** Отладка */
            $description = 'Зккончил вставку в worker_parameter';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;
            $warnings[] = $method_name . 'Начинаю запись во временную таблицу worker_parameter_new_ids';
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('worker_parameter_new_ids', ['worker_parameter_new_id', 'worker_parameter_last_id'], $worker_parameter_new_ids)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице worker_parameter_new_ids');
            }
            unset($worker_parameter_new_ids);

            /** Отладка */
            $description = 'Зккончил вставку в worker_parameter_new_ids';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;


            $warnings[] = $method_name . 'Получаю данные с worker_parameter_handbook_value';
//            //
//            $worker_parameter_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM worker_parameter_new_ids')->queryAll();
//            foreach ($worker_parameter_new_ids as $worker_parameter_new_id) {
//                $worker_parameter_new_and_last_ids[$worker_parameter_new_id['worker_parameter_last_id']] = $worker_parameter_new_id['worker_parameter_new_id'];
//            }
//            unset($worker_parameter_new_ids);
            //
            $counter_iteration = true;
            $last_id = -1;
            while ($counter_iteration) {


                $wpvs = Yii::$app->db_source->createCommand('SELECT * FROM worker_parameter_handbook_value where id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($wpvs) < 25000) {
                    $counter_iteration = false;
                }
                $data_worker_parameter_handbook_valuee_to_batchInsert = null;//в данной перемены будет размешена последння итерация foreach так как если данные меньше 2500 по внутренной условии данные не попадут в БД
                foreach ($wpvs as $worker_parameter_handbook_value) {

                    if (isset($worker_parameter_new_and_last_ids[$worker_parameter_handbook_value['worker_parameter_id']])) {
                        //$sensor_parameter_value_temp[$sensor_parameter_value['sensor_parameter_id']] = $sensor_parameter_new_ids[$sensor_parameter_value['id']];
                        $data_worker_parameter_handbook_valuee_to_batchInsert[] = array(
                            'worker_parameter_id' => $worker_parameter_new_and_last_ids[$worker_parameter_handbook_value['worker_parameter_id']],
                            'date_time' => $worker_parameter_handbook_value['date_time'],
                            'value' => $worker_parameter_handbook_value['value'],
                            'status_id' => $worker_parameter_handbook_value['status_id'],
                        );
                    }


                    $count_all++;
                }
                $last_id = $worker_parameter_handbook_value['id'];
                unset($wpvs);

                //вставка остаток записей в sensor_parameter_value
                if (!empty($data_worker_parameter_handbook_valuee_to_batchInsert)) {
                    $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('worker_parameter_handbook_value', [
                        'worker_parameter_id',
                        'date_time',
                        'value',
                        'status_id',
                    ], $data_worker_parameter_handbook_valuee_to_batchInsert)->execute();

                    if (!$insert_result_to_MySQL) {
                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу worker_parameter_handbook_value' . $insert_result_to_MySQL);
                    }
                }
                unset($data_worker_parameter_handbook_valuee_to_batchInsert);
            }


            /** Отладка */
            $description = 'Зккончил вставку в worker_parameter_handbook_value';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;
            unset($worker_parameter_handbook_value);

            //</editor-fold>

            //<editor-fold desc="worker_parameter_sensor">
            $warnings[] = __FUNCTION__ . ' Получаю значение с таблицы sensor_new_ids';
            $sensor_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM sensor_new_ids')->queryAll();
            foreach ($sensor_new_ids as $sensors_new_id) {
                $count_all++;
                $sensors_new_and_last_ids[$sensors_new_id['sensor_last_id']] = $sensors_new_id['sensor_new_id'];
            }

            /** Отладка */
            $description = 'Получил значение с таблицы sensor_new_ids';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            //</editor-fold>

            $warnings[] = $method_name . 'Получаю данные с worker_parameter_sensor';

            $wps = Yii::$app->db_source->createCommand('SELECT * FROM worker_parameter_sensor')->queryAll();
            $warnings[] = $wps;
            $count_all = 0;
            $data_to_db = null;
            foreach ($wps as $each) {


                if (isset($sensors_new_and_last_ids[$each['sensor_id']])) {
                    $sensor_id_value = $sensors_new_and_last_ids[$each['sensor_id']];
                } else {
                    $sensor_id_value = -1;
                }
                if (isset($worker_parameter_new_and_last_ids[$each['worker_parameter_id']])) {
                    $data_to_db[] = array(
                        'worker_parameter_id' => $worker_parameter_new_and_last_ids[$each['worker_parameter_id']],
                        'sensor_id' => $sensor_id_value,
                        'date_time' => $each['date_time'],
                        'type_relation_sensor' => $each['type_relation_sensor'],
                    );
                }


                $count_all++;
            }
            $warnings[] = "Вышел из форича";

            unset($wps);
            // unset($worker_parameter_new_and_last_ids);
//            unset($sensors_new_and_last_ids);
//            if (!empty($data_to_db)) {
//                $warnings[] = $data_to_db;
//                $builder_data_to_db = Yii::$app->db_target->createCommand()->batchInsert('worker_parameter_sensor', [
//                    'worker_parameter_id',
//                    'sensor_id',
//                    'date_time',
//                    'type_relation_sensor',
//
//                ], $data_to_db)->execute();
//
//                //$insert_result_to_MySQL = Yii::$app->db_target->createCommand($builder_data_to_db, " ON DUPLICATE KEY UPDATE `type_relation_sensor` = values (`type_relation_sensor`)")->execute();
//
//                if (!$insert_result_to_MySQL) {
//                    throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу worker_parameter_sensor' . $insert_result_to_MySQL);
//                }
//            }
//            unset($data_to_db);

            unset($sensors_new_and_last_ids);
            if (!empty($data_to_db)) {
                $warnings[] = $data_to_db;
                $builder_data_to_db = Yii::$app->db_target->queryBuilder->batchInsert('worker_parameter_sensor', ['worker_parameter_id', 'sensor_id', 'date_time', 'type_relation_sensor'], $data_to_db);
                $insert_result_to_MySQL = Yii::$app->db_target->createCommand($builder_data_to_db . " ON DUPLICATE KEY UPDATE `type_relation_sensor` = VALUES (`type_relation_sensor`)")->execute();

                if (!$insert_result_to_MySQL) {
                    throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу worker_parameter_sensor' . $insert_result_to_MySQL);
                }
            }
            unset($data_to_db);

            /** Отладка */
            $description = 'Зккончил вставку в worker_parameter_sensor';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;
            unset($worker_parameter_sensor);


            $warnings[] = $method_name . 'Получаю данные с worker_parameter_value';

            $counter_iteration = true;
            $last_id = -1;
            while ($counter_iteration) {


                $wpvs = Yii::$app->db_source->createCommand('SELECT * FROM worker_parameter_value where id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($wpvs) < 25000) {
                    $counter_iteration = false;
                }
                $data_worker_parameter_value_to_batchInsert = null;//в данной перемены будет размешена последння итерация foreach так как если данные меньше 2500 по внутренной условии данные не попадут в БД
                foreach ($wpvs as $worker_parameter_value) {

                    //$sensor_parameter_value_temp[$sensor_parameter_value['sensor_parameter_id']] = $sensor_parameter_new_ids[$sensor_parameter_value['id']];
                    if (isset($worker_parameter_new_and_last_ids[$worker_parameter_value['worker_parameter_id']])) {
                        $data_worker_parameter_value_to_batchInsert[] = array(
                            'worker_parameter_id' => $worker_parameter_new_and_last_ids[$worker_parameter_value['worker_parameter_id']],
                            'date_time' => $worker_parameter_value['date_time'],
                            'value' => $worker_parameter_value['value'],
                            'status_id' => $worker_parameter_value['status_id'],
                            'shift' => $worker_parameter_value['shift'],
                            'date_work' => $worker_parameter_value['date_work']
                        );
                    }

                    $count_all++;
                    $last_id = (int)$worker_parameter_value['id'];
                }

                unset($wpvs);
                $insert_param_val = Yii::$app->db_target->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $data_worker_parameter_value_to_batchInsert);
                Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `shift` = VALUES (`shift`), `date_work` = VALUES (`date_work`)")->execute();


//                //вставка остаток записей в sensor_parameter_value
//                if (!empty($data_worker_parameter_value_to_batchInsert)) {
//                    $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('worker_parameter_value', [
//                        'worker_parameter_id',
//                        'date_time',
//                        'value',
//                        'status_id',
//                        'shift',
//                        'date_work'
//                    ], $data_worker_parameter_value_to_batchInsert)->execute();
//
//                    if (!$insert_result_to_MySQL) {
//                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу sensor_parameter_value' . $insert_result_to_MySQL);
//                    }
//                }
                unset($data_worker_parameter_value_to_batchInsert);

            }


            /** Отладка */
            $description = 'Зккончил вставку в worker_parameter_value';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }


        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);


    }

    /**
     * TransferEquipments() - метод по переносу оборудование с учетом новых айдишникуов
     * список зайдействованых таблиц:
     *  equipment
     *      equipment_parameter
     *          equipment_parameter_value
     *          equipment_parameter_handbook_value
     *          equipment_parameter_sensor
     */
    public static function TransferEquipments()
    {
        // Стартовая отладочная информация
        $method_name = 'actoinTransferEquipments';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                   // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $data_to_db = null;                                                                                                 //массив для массовой вставки
        $equipment_new_ids = array();                                                                                      //новые айдишники equipment для сохранения в БД
        $equipment_new_and_last_ids = array();                                                                             //объект новых айдишников для equipment_parameter
        $equipment_parameter_new_ids = array();                                                                           //новые айдишники для сохранения в БД
        $equipment_parameter_last_and_new_ids = array();                                                                 //объект новых айдишников для equipment_parameter_value И везде где equipment_parameter_id есть
        $equipment_parameter_value = array();                                                                             //массив для сохранения в БД в equipment_parameter_value
        $equipment_parameter_handbook_value = array();                                                                    //массив для сохранения в БД в equipment_parameter_handbook_value
        $equipment_parameter_sensor = array();                                                                            //массив для сохранения в БД в equipment_parameter_sensor


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            $warnings[] = $method_name . ' Получаю eqipment';
            $equipments = Yii::$app->db_source->createCommand('SELECT * FROM equipment')->queryAll();
            foreach ($equipments as $each) {
                $count_all++;


                $new_main = new MainSync();
                $new_main->table_address = 'equipment';
                $new_main->db_address = 'amicum3';


                if (!$new_main->save()) {
                    $errors[] = $new_main->errors;
                    throw new Exception($method_name . '. Не удалось сохранить новый id в таблице main');
                }
                $equipment_id = $new_main->id;

                $data_to_db[] = array(
                    'id' => $equipment_id,
                    'title' => $each['title'],
                    'inventory_number' => $each['inventory_number'],
                    'object_id' => $each['object_id'],

                );


                $equipment_new_ids[] = array(
                    'equipment_last_id' => $each['id'],
                    'equipment_new_id' => $equipment_id
                );
                $equipment_new_and_last_ids[$each['id']] = $equipment_id;

            }

            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('equipment', ['id', 'title', 'inventory_number', 'object_id'], $data_to_db)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый equipment');
            }
            $description = ' Закончил обновления таблицы eqipment с учетом новых айдишников из main';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Начинаю запись во временную таблицу equipment_new_ids';

            $count_all = 0;
            $warnings[] = $method_name . 'Начинаю запись во временную таблицу equipment_new_ids';
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('equipment_new_ids', ['equipment_last_id', 'equipment_new_id'], $equipment_new_ids)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице equipment_new_ids');
            }

            //для отладки
//            $equipment_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM equipment_new_ids')->queryAll();
//
//            foreach ($equipment_new_ids as $equipment_new_id) {
//                $equipment_new_and_last_ids[$equipment_new_id['equipment_last_id']] = $equipment_new_id['equipment_new_id'];
//            }

            unset($equipment);
            unset($equipment_new_ids);
            unset($equipments);
            /** Отладка */
            $description = 'Закончил запись во временную таблицу equipment_new_ids и подготовил объект новый и старых айдишников';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . ' Получаю данные из equipment_parameter';
            $equipment_parameters = Yii::$app->db_source->createCommand('SELECT * FROM equipment_parameter')->queryAll();
            $count_all = 0;
            foreach ($equipment_parameters as $each) {
                $count_all++;

                Yii::$app->db_target->createCommand()->insert('equipment_parameter',
                    [
                        'equipment_id' => $equipment_new_and_last_ids[$each['equipment_id']],
                        'parameter_id' => $each['parameter_id'],
                        'parameter_type_id' => $each['parameter_type_id'],
                    ]
                )->execute();
                $equipment_parameter_id = Yii::$app->db_target->getLastInsertID();


                $equipment_parameter_new_ids[] = array(
                    'equipment_parameter_last_id' => $each['id'],
                    'equipment_parameter_new_id' => $equipment_parameter_id
                );
                $equipment_parameter_last_and_new_ids[$each['id']] = $equipment_parameter_id;

            }
            unset($equipment_new_and_last_ids);
            /** Отладка */
            $description = 'Закончил запись в equipment_parameter';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей

            $microtime_current = microtime(true);
            /** Окончание отладки */


            $warnings[] = $method_name . ' Начинаю  вставку в equipment_parameter_new_ids';
            $count_all = 0;
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('equipment_parameter_new_ids', ['equipment_parameter_last_id', 'equipment_parameter_new_id'], $equipment_parameter_new_ids)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице equipment_parameter_new_ids');
            }


            /** Отладка */
            $description = 'Закончил запись во временную таблицу equipment_parameter_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Начинаю перенос дынных equipment_parameter_value по 25000 шт.';
            $count_all = 0;
            $counter_iteration = true;
            $last_id = -1;
            while ($counter_iteration) {

                $epvs = Yii::$app->db_source->createCommand('SELECT * FROM equipment_parameter_value where id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($epvs) < 25000) {
                    $counter_iteration = false;
                }

                foreach ($epvs as $each) {

                    $equipment_parameter_value[] = array(
                        'equipment_parameter_id' => $equipment_parameter_last_and_new_ids[$each['equipment_parameter_id']],
                        'date_time' => $each['date_time'],
                        'value' => $each['value'],
                        'status_id' => $each['status_id']
                    );
                    $last_id = $each['id'];
                }
                unset($epvs);
                $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('equipment_parameter_value',
                    [
                        'equipment_parameter_id',
                        'date_time',
                        'value',
                        'status_id'
                    ], $equipment_parameter_value)->execute();
                if (!$data_to_db) {
                    throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице equipment_parameter_value');
                }
                unset($equipment_parameter_value);


            }


            /** Отладка */
            $description = ' Закончил с equipment_parameter_value';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $count_all = 0;

            $warnings[] = $method_name . 'Начинаю перенос дынных equipment_parameter_handbook_value';

            $warnings[] = $method_name . 'Начинаю перенос дынных equipment_parameter_value по 25000 шт.';
            $count_all = 0;
            $counter_iteration = true;
            $last_id = -1;
            while ($counter_iteration) {

                $ephvs = Yii::$app->db_source->createCommand('SELECT * FROM equipment_parameter_value where id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($ephvs) < 25000) {
                    $counter_iteration = false;
                }

                foreach ($ephvs as $each) {

                    $equipment_parameter_handbook_value[] = array(
                        'equipment_parameter_id' => $equipment_parameter_last_and_new_ids[$each['equipment_parameter_id']],
                        'date_time' => $each['date_time'],
                        'value' => $each['value'],
                        'status_id' => $each['status_id']
                    );
                    $count_all++;
                    $last_id = $each['id'];
                }
                unset($ephvs);
                $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('equipment_parameter_handbook_value',
                    [
                        'equipment_parameter_id',
                        'date_time',
                        'value',
                        'status_id'
                    ], $equipment_parameter_handbook_value)->execute();
                unset($equipment_parameter_handbook_value);


            }

            /** Отладка */
            $description = ' Закончил с equipment_parameter_handbook_value';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

//            $warnings[] = $method_name . 'Начинаю перенос дынных equipment_parameter_sensor';
//            $parameter_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM equipment_parameter_new_ids')->queryAll();
//            foreach ($parameter_new_ids as $parameter_new_id) {
//                $equipment_parameter_last_and_new_ids[$parameter_new_id['equipment_parameter_last_id']] = $parameter_new_id['equipment_parameter_new_id'];
//            }
//            unset($parameter_new_ids);

            $equipment_parameter_sensors = Yii::$app->db_source->createCommand('SELECT * FROM equipment_parameter_sensor')->queryAll();


            $warnings[] = __FUNCTION__ . ' Получаю значение с таблицы sensor_new_ids';
            $sensor_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM sensor_new_ids')->queryAll();
            $count_all = 0;
            $sensors_new_and_last_ids = null;
            foreach ($sensor_new_ids as $sensors_new_id) {

                $sensors_new_and_last_ids[$sensors_new_id['sensor_last_id']] = $sensors_new_id['sensor_new_id'];
            }

            foreach ($equipment_parameter_sensors as $each_item) {


                if ($each_item['sensor_id'] != '-1' and isset($sensors_new_and_last_ids[$each_item['sensor_id']])) {
                    $sensor_id_value = $sensors_new_and_last_ids[$each_item['sensor_id']];
                } else {
                    $sensor_id_value = -1;
                }
                $equipment_parameter_sensor[] = array(
                    'equipment_parameter_id' => $equipment_parameter_last_and_new_ids[$each_item['equipment_parameter_id']],
                    'sensor_id' => $sensor_id_value,
                    'date_time' => $each_item['date_time']
                );

            }
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('equipment_parameter_sensor',
                [
                    'equipment_parameter_id',
                    'sensor_id',
                    'date_time'
                ], $equipment_parameter_sensor)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице equipment_parameter_sensor');
            }
            unset($equipment_parameter_sensors);
            unset($sensor_new_ids);
            unset($equipment_parameter_values);

            /** Отладка */
            $description = ' Закончил с equipment_parameter_sensor';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /*Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;*/

    }


    /**
     * TransferPlaces - метод по переносу данных place
     * список задействованых таблиц:
     * main
     * place
     *  place_parameter
     *     place_parameter_handbook_valu
     * Пример вызова: MigrationDbController::TransferPlaces();
     */
    public static function TransferPlaces()
    {
        // Стартовая отладочная информация
        $method_name = 'TransferPlaces';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $place_to_db = array();                                                                                         // массив для массовой вставки в place
        $place_new_ids = array();                                                                                      //новые айдишники place для сохранения в БД
        $place_new_and_last_ids = array();                                                                             //объект новых айдишников для place_parameter
        $place_parameter_new_ids = array();                                                                           //новые айдишники для сохранения в БД
        $place_parameter_last_and_new_ids = array();                                                                 //объект новых айдишников place_parameter
        $place_parameter_handbook_value = array();                                                                    //массив для сохранения в БД в place_parameter_handbook_value


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . ' Получаю place';
            $places = Yii::$app->db_source->createCommand('SELECT * FROM place')->queryAll();

            $places_target = Yii::$app->db_target->createCommand('SELECT * FROM place')->queryAll();
            foreach ($places_target as $place_target) {
                $places_target_hand[$place_target['title']][$place_target['mine_id']][$place_target['object_id']][$place_target['plast_id']] = $place_target['id'];
            }

            $count_all = 0;
            $count = 0;
            $place_to_db = array();
            foreach ($places as $place) {

                if (!isset($places_target_hand[$place['title']][$place['mine_id']][$place['object_id']][$place['plast_id']])) {
                    $new_main = new MainSync();
                    $new_main->table_address = 'place';
                    $new_main->db_address = 'amicum3';

                    if (!$new_main->save()) {
                        $errors[] = $new_main->errors;
                        throw new Exception($method_name . '. Не удалось сохранить новый id в таблице main');
                    }
                    $place_id = $new_main->id;

                    $place_to_db[] = array(
                        'id' => $place_id,
                        'title' => $place['title'],
                        'mine_id' => $place['mine_id'],
                        'object_id' => $place['object_id'],
                        'plast_id' => $place['plast_id']
                    );

                } else {
                    $place_id = $places_target_hand[$place['title']][$place['mine_id']][$place['object_id']][$place['plast_id']];
                }

                $place_new_and_last_ids[$place['id']] = $place_id;

                $place_new_ids[] = array(
                    'place_new_id' => $place_id,
                    'place_last_id' => $place['id']

                );

                $count_all++;
            }

            if (!empty($place_to_db)) {
                $warnings[] = $method_name . " Начинаю записю в conjaction массво";
                $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('place',
                    [
                        'id',
                        'title',
                        'mine_id',
                        'object_id',
                        'plast_id'
                    ], $place_to_db)->execute();

                if (!$data_to_db) {
                    throw new Exception($method_name . '. Не удалось массово сохранить в таблице place');
                }
            }

            /** Отладка */
            $description = ' Закончил с place';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;
            unset($place_to_db);
            unset($data_to_db);

            $count_all = 0;
            $data_to_db = Yii::$app->db_target->queryBuilder->batchInsert('place_new_ids',
                [
                    'place_new_id',
                    'place_last_id'
                ], $place_new_ids);

            Yii::$app->db_amicum2->createCommand($data_to_db . " ON DUPLICATE KEY UPDATE `place_new_id` = VALUES (`place_new_id`), `place_last_id` = VALUES (`place_last_id`)")->execute();

            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить массово связку в таблице place_new_ids');
            }


            unset($places);
            unset($new_main);
            unset($place_new_ids);
            /** Отладка */
            $description = ' Закончил с place_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;

            $warnings[] = $method_name . ' Начинаю получать данные с place_parameter';

            $place_parameters = Yii::$app->db_source->createCommand('SELECT * FROM place_parameter')->queryAll();
            $place_parameters_target = Yii::$app->db_target->createCommand('SELECT * FROM place_parameter')->queryAll();
            foreach ($place_parameters_target as $place_parameter_target) {
                $place_parameters_target_hand[$place_parameter_target['place_id']][$place_parameter_target['parameter_id']][$place_parameter_target['parameter_type_id']] = $place_parameter_target['id'];
            }

            $count = 0;
            foreach ($place_parameters as $each) {
                $count_all++;

                if ($place_new_and_last_ids[$each['place_id']]) {

                    if (!isset($place_parameters_target_hand[$place_new_and_last_ids[$each['place_id']]][$each['parameter_id']][$each['parameter_type_id']])) {
                        $data_from_old_db = Yii::$app->db_target->createCommand()->insert('place_parameter',
                            [
                                'place_id' => $place_new_and_last_ids[$each['place_id']],
                                'parameter_id' => $each['parameter_id'],
                                'parameter_type_id' => $each['parameter_type_id'],

                            ]
                        )->execute();
                        $place_parameter_id = Yii::$app->db_target->getLastInsertID();
                    } else {
                        $place_parameter_id = $place_parameters_target_hand[$place_new_and_last_ids[$each['place_id']]][$each['parameter_id']][$each['parameter_type_id']];
                    }

                    $place_parameter_new_ids[] = array(
                        'place_parameter_new_id' => $place_parameter_id,
                        'place_parameter_last_id' => $each['id']

                    );
                    $place_parameter_last_and_new_ids[$each['id']] = $place_parameter_id;
                }
            }
            unset($place_new_and_last_ids);


            /** Отладка */
            $description = ' Закончил с place_parameter';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $count_all = 0;
            if (!empty($place_parameter_new_ids)) {
                $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('place_parameter_new_ids',
                    [
                        'place_parameter_new_id',
                        'place_parameter_last_id'
                    ], $place_parameter_new_ids)->execute();
                $count_all = $data_to_db;
                if (!$data_to_db) {
                    throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице place_parameter_new_ids');
                }
            }
            unset($place_parameter_new_ids);
            unset($place_parameters);

            /** Отладка */
            $description = ' Закончил с place_parameter_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;


            $warnings[] = $method_name . ' Получаю данные place_parameter_handbook_value';


            $place_parameter_handbook_values = Yii::$app->db_source->createCommand('SELECT * FROM place_parameter_handbook_value')->queryAll();
            $place_parameter_handbook_values_target = Yii::$app->db_target->createCommand('SELECT * FROM place_parameter_handbook_value')->queryAll();
            foreach ($place_parameter_handbook_values_target as $place_parameter_handbook_value_target) {
                $date_time_format = date("Y-m-d H:i:s", strtotime($place_parameter_handbook_value_target['date_time']));
                $place_parameter_handbook_values_target_hand[$place_parameter_handbook_value_target['place_parameter_id']][$date_time_format] = $place_parameter_handbook_value_target;
            }

            foreach ($place_parameter_handbook_values as $each) {
                if ($place_parameter_last_and_new_ids[$each['place_parameter_id']]) {

                    $date_time_format = date("Y-m-d H:i:s", strtotime($each['date_time']));
                    if (!isset($place_parameter_handbook_values_target_hand[$place_parameter_last_and_new_ids[$each['place_parameter_id']]][$date_time_format])) {
                        $place_parameter_handbook_value[] = array(
                            'place_parameter_id' => $place_parameter_last_and_new_ids[$each['place_parameter_id']],
                            'date_time' => $each['date_time'],
                            'value' => $each['value'],
                            'status_id' => $each['status_id']

                        );
                    }
                } else {
                    $errors[] = $method_name . ' Не найден place_parameter_id =' . $each['place_parameter_id'] . ' в объекте из таблицы place_parameter_new_ids';
                }

            }


            $count_all = 0;
            if (!empty($place_parameter_handbook_value)) {
                $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('place_parameter_handbook_value',
                    [
                        'place_parameter_id',
                        'date_time',
                        'value',
                        'status_id'

                    ], $place_parameter_handbook_value)->execute();
                $count_all = $data_to_db;
                if (!$data_to_db) {
                    throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице place_parameter_handbook_value');
                }
            }

            /** Отладка */
            $description = ' Закончил с place_parameter_handbook_value';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/
    }


    /**
     * TransferEdges - метод по переносу данных edge
     * список задействованых таблиц:
     * main
     * edge
     *  edge_changes
     *   edge_changes_history
     *   edge_parameter
     *    edge_status
     *    edge_parameter_handbook_value
     *
     */
    public static function TransferEdges()
    {

        // Стартовая отладочная информация
        $method_name = 'TransferEdges ';                                                                                // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                             // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                           // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                               // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $edge_new_ids = array();                                                                                        //новые айдишники edge для сохранения в БД
        $edge_to_db = array();                                                                                          //edge на массовую вставку в БД
        $edge_new_and_last_ids = array();                                                                               //объект новых айдишников для edge_parameter и дргуие
        $edge_changes_new_ids = array();                                                                                //новые айдишники edge_changes для edge_changes_history
        $edge_changes_history_new_ids = array();                                                                        //новые айдишники edge_changes_history  для сохранения в БД
        $edge_changes_last_new_ids = array();                                                                           //новые айдишники edge_changes для сохранения в БД
        $edge_parameter_new_ids = array();                                                                              //новые айдишники edge_parameter для сохранения в БД
        $edge_parameter_last_and_new_ids = array();                                                                     //объект новых айдишников для edge_parameter_handbook_value
        $edge_status_new_ids = array();                                                                                 //новые айдишники для сохранения в БД
        $edge_status_last_new_ids = array();                                                                            //объект новых айдишников для edge_parameter_handbook_value
        $edge_parameter_handbook_value = array();                                                                       //массив для сохранения в БД в edge_parameter_handbook_value
        $place_new_ids = array();                                                                                       //новые и старые айдишнокв place для таблицы edge
        $conjunction_last_and_new_ids = array();                                                                        //новые и старые айдишников conjunction


        //edge
        //edge_changes
        //edge_changes_history
        //edge_parameter
        //edge_status
        //edge_parameter_handbook_value

//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта

            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            //edge
            $warnings[] = $method_name . ' Получаю conjunction_new_ids';

            $conjunction_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM conjunction_new_ids')->queryAll();
            $count_all = 0;
            foreach ($conjunction_new_ids as $conjunction_new_id) {
                $count_all++;
                $conjunction_last_and_new_ids[$conjunction_new_id['conjunction_last_id']] = $conjunction_new_id['conjunction_new_id'];
            }
            /** Отладка */
            $description = ' Получил новые айдшники conjunction';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            unset($conjunction_new_ids);

            $warnings[] = $method_name . ' Получаю $place_new_ids';
            $place_new_ids_from_db = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();
            $count_all = 0;
            foreach ($place_new_ids_from_db as $place_new_id_from_db) {
                $count_all++;
                $place_new_ids[$place_new_id_from_db['place_last_id']] = $place_new_id_from_db['place_new_id'];
            }
            /** Отладка */
            $description = ' Получил новые айдшники place_new_ids';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            unset($place_new_ids_from_db);

            $warnings[] = $method_name . ' Получаю edge';
            $edges = Yii::$app->db_source->createCommand('SELECT * FROM edge')->queryAll();
            $count_all = 0;
            foreach ($edges as $each) {
                $count_all++;
                $new_main = new MainSync();
                $new_main->table_address = 'edge';
                $new_main->db_address = 'amicum3';

                if (!$new_main->save()) {
                    $errors[] = $new_main->errors;
                    throw new Exception($method_name . '. Не удалось сохранить новый id в таблице main');
                }

                if (isset($conjunction_last_and_new_ids[$each['conjunction_start_id']]) and isset($conjunction_last_and_new_ids[$each['conjunction_end_id']])) {
                    $edge_to_db[] = array(
                        'id' => $new_main->id,
                        'conjunction_start_id' => $conjunction_last_and_new_ids[$each['conjunction_start_id']],
                        'conjunction_end_id' => $conjunction_last_and_new_ids[$each['conjunction_end_id']],
                        'place_id' => $place_new_ids[$each['place_id']],
                        'edge_type_id' => $each['edge_type_id'],
                        'ventilation_id' => $each['ventilation_id'],
                        'ventilation_current_id' => $each['ventilation_current_id']
                    );
                } else {
                    $errors[] = $method_name . ' Не найден conjunction_start_id или conjunction_end_id. Искаемый id' . $each['conjunction_end_id'] . $each['conjunction_start_id'];
                }

                $edge_new_ids[] = array(
                    'edge_new_id' => $new_main->id,
                    'edge_last_id' => $each['id'],

                );
                $edge_new_and_last_ids[$each['id']] = $new_main->id;

            }

            unset($place_new_ids);
            unset($conjunction_last_and_new_ids);
            unset($edges);

            /** Отладка */
            $description = ' подготовил массив для масовой вставке в edge';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;
            $warnings[] = $method_name . ' начинаю массовую вставку в edge_new_ids';
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('edge_new_ids',
                [
                    'edge_new_id',
                    'edge_last_id',
                ], $edge_new_ids)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице edge_new_ids');
            }
            unset($edge_new_ids);

            /** Отладка */
            $description = ' Закончил с edge_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;

            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('edge',
                [
                    'id',
                    'conjunction_start_id',
                    'conjunction_end_id',
                    'place_id',
                    'edge_type_id',
                    'ventilation_id',
                    'ventilation_current_id'
                ], $edge_to_db)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице edge');
            }

            unset($edge_to_db);
            unset($new_main);
            /** Отладка */
            $description = ' Закончил edge';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            //edge_changes

            $warnings[] = $method_name . ' Начинаю получать данные с edge_changes';
            $edge_changes_from_db = Yii::$app->db_source->createCommand('SELECT * FROM edge_changes')->queryAll();
            foreach ($edge_changes_from_db as $edge_change_from_db) {

                Yii::$app->db_target->createCommand()->insert('edge_changes',
                    [
                        'date_time' => $edge_change_from_db['date_time'],
                        'status_id' => $edge_change_from_db['status_id']
                    ])->execute();
                $edge_changes_new_id = Yii::$app->db_target->getLastInsertID();
                $edge_changes_last_new_ids[$edge_change_from_db['id']] = $edge_changes_new_id;
                $edge_changes_new_ids[] = array(
                    'edge_changes_new_id' => $edge_changes_new_id,
                    'edge_changes_last_id' => $edge_change_from_db['id'],
                );
                $count_all++;
            }

            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('edge_changes_new_ids',
                [
                    'edge_changes_new_id',
                    'edge_changes_last_id',
                ], $edge_changes_new_ids)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице edge_changes_new_ids');
            }
            unset($edge_changes_new_ids);


            unset($edge_changes_from_db);
            /** Отладка */
            $description = ' Закончил с edge_changes';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;

            //edge_changes_history

            $warnings[] = $method_name . ' Начинаю получать данные с edge_changes_history';
            $edge_changes_historys = Yii::$app->db_source->createCommand('SELECT * FROM edge_changes_history')->queryAll();
            foreach ($edge_changes_historys as $edge_changes_history) {
                $count_all++;
                $edge_changes_history_new_ids[] = array(
                    'id_edge_changes' => $edge_changes_last_new_ids[$edge_changes_history['id_edge_changes']],
                    'edge_id' => $edge_new_and_last_ids[$edge_changes_history['edge_id']],
                );

            }
            unset($edge_changes_last_new_ids);
            unset($edge_changes_historys);

            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('edge_changes_history',
                [
                    'id_edge_changes',
                    'edge_id',

                ], $edge_changes_history_new_ids)->execute();

            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице edge_changes_history');
            }

            unset($edge_changes_history_new_ids);
            /** Отладка */
            $description = ' Закончил с edge_changes_history';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            //edge_parameter
            $warnings[] = $method_name . ' Начинаю получать данные с edge_parameter';
            $edge_parameters = Yii::$app->db_source->createCommand('SELECT * FROM edge_parameter')->queryAll();
            $count_all = 0;
            foreach ($edge_parameters as $edge_parameter) {

                Yii::$app->db_target->createCommand()->insert('edge_parameter',
                    [
                        'edge_id' => $edge_new_and_last_ids[$edge_parameter['edge_id']],
                        'parameter_id' => $edge_parameter['parameter_id'],
                        'parameter_type_id' => $edge_parameter['parameter_type_id']
                    ]
                )->execute();
                $edge_parameter_new_id = Yii::$app->db_target->getLastInsertID();
                $edge_parameter_last_and_new_ids[$edge_parameter['id']] = $edge_parameter_new_id;

                $edge_parameter_new_ids[] = array(
                    'edge_parameter_new_id' => $edge_parameter_new_id,
                    'edge_parameter_last_id' => $edge_parameter['id'],
                );
                $count_all++;
            }
            unset($edge_parameters);

            /** Отладка */
            $description = ' Закончил запись в edge_parameter';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;


            $warnings[] = $method_name . 'Начинаю массовую вставку в edge_parameter_new_ids';
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('edge_parameter_new_ids',
                [
                    'edge_parameter_new_id',
                    'edge_parameter_last_id',

                ], $edge_parameter_new_ids)->execute();

            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице edge_parameter_new_ids');
            }
            /** Отладка */
            $description = ' Закончил запись в edge_parameter_new_ids';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;

            //edge_status
            $warnings[] = $method_name . ' Начинаю получать данные с edge_status';
            $edge_statuses = Yii::$app->db_source->createCommand('SELECT * FROM edge_status')->queryAll();
            $count_all = 0;
            foreach ($edge_statuses as $edge_status) {
                Yii::$app->db_target->createCommand()->insert('edge_status', ['edge_id' => $edge_new_and_last_ids[$edge_status['edge_id']], 'status_id' => $edge_status['status_id'], 'date_time' => $edge_status['date_time']])->execute();

                $edge_status_new_ids[] = array(
                    'edge_status_new_id' => Yii::$app->db_target->getLastInsertID(),
                    'edge_status_last_id' => $edge_status['id']
                );
                $count_all++;
            }
            unset($edge_statuses);
            unset($edge_new_and_last_ids);
            /** Отладка */
            $description = ' Закончил запись в edge_status';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;
            $warnings[] = $method_name . 'Начинаю массовую вставку в edge_parameter_new_ids';
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('edge_status_new_ids',
                [
                    'edge_status_new_id',
                    'edge_status_last_id',

                ], $edge_status_new_ids)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице edge_parameter_new_ids');
            }
            unset($edge_status_new_ids);

            /** Отладка */
            $description = ' Закончил запись в edge_status_new_ids';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;


            //edge_parameter_handbook_value
            $warnings[] = $method_name . ' Начинаю получать данные с edge_parameter_handbook_value';
            $edge_parameter_handbook_values = Yii::$app->db_source->createCommand('SELECT * FROM edge_parameter_handbook_value')->queryAll();
            $count_all = 0;
            $count = 0;
            foreach ($edge_parameter_handbook_values as $edge_parameter_handbook_value) {

                $count_all++;
                $count++;

                $edge_parameter_handbook_value_do_db[] = array(
                    'edge_parameter_id' => $edge_parameter_last_and_new_ids[$edge_parameter_handbook_value['edge_parameter_id']],
                    'date_time' => $edge_parameter_handbook_value['date_time'],
                    'value' => $edge_parameter_handbook_value['value'],
                    'status_id' => $edge_parameter_handbook_value['status_id']
                );
                if ($count >= 2500) {
                    $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('edge_parameter_handbook_value',
                        [
                            'edge_parameter_id',
                            'date_time',
                            'value',
                            'status_id'

                        ], $edge_parameter_handbook_value_do_db)->execute();
                    if (!$data_to_db) {
                        throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице edge_parameter_handbook_value');
                    }
                    unset($edge_parameter_handbook_value_do_db);
                    $count = 0;
                }

            }
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('edge_parameter_handbook_value',
                [
                    'edge_parameter_id',
                    'date_time',
                    'value',
                    'status_id'

                ], $edge_parameter_handbook_value_do_db)->execute();
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице edge_parameter_handbook_value');
            }
            unset($edge_parameter_handbook_value_do_db);
            unset($edge_parameter_handbook_values);
            /** Отладка */
            $description = ' Закончил запись в edge_parameter_handbook_value';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        /*
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->data = $result_main;
        */
    }

    /**
     * TransferConjunctions() - метод по переносу дынных conjunction
     * список задействованых таблиц:
     * сonjunction
     *  сonjunction_parameter
     *  сonjunction_parameter_handbook_value
     */
    public static function TransferConjunctions()
    {

        // Стартовая отладочная информация
        $method_name = 'TransferConjunctions';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $conjunction_to_db = array();                                                                                         // массив для массовой вставки в conjunction
        $conjunction_new_ids = array();                                                                                      //новые айдишники conjunction для сохранения в БД
        $conjunction_new_and_last_ids = array();                                                                             //объект новых айдишников для conjunction_parameter
        $conjunction_parameter_new_ids = array();                                                                           //новые айдишники для сохранения в БД
        $conjunction_parameter_last_and_new_ids = array();                                                                 //объект новых айдишников conjunction_parameter
        $conjunction_parameter_handbook_value = array();                                                                    //массив для сохранения в БД в conjunction_parameter_handbook_value


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . ' Получаю conjunction';
            $conjunctions = Yii::$app->db_source->createCommand('SELECT * FROM conjunction')->queryAll();

            $conjunctions_target = Yii::$app->db_target->createCommand('SELECT * FROM conjunction')->queryAll();
            foreach ($conjunctions_target as $conjunction_target) {
                $conjunctions_target_hand[$conjunction_target['title']][$conjunction_target['x']][$conjunction_target['y']][$conjunction_target['z']] = $conjunction_target['id'];
            }

            $count_all = 0;
            $count = 0;
            $conjunction_to_db = array();
            foreach ($conjunctions as $conjunction) {

                if (!isset($conjunctions_target_hand[$conjunction['title']][$conjunction['x']][$conjunction['y']][$conjunction['z']])) {
                    $new_main = new MainSync();
                    $new_main->table_address = 'conjunction';
                    $new_main->db_address = 'amicum3';

                    if (!$new_main->save()) {
                        $errors[] = $new_main->errors;
                        throw new Exception($method_name . '. Не удалось сохранить новый id в таблице main');
                    }
                    $conjunction_id = $new_main->id;

                    $conjunction_to_db[] = array(
                        'id' => $conjunction_id,
                        'title' => "Поворот " . $conjunction_id,
                        'object_id' => $conjunction['object_id'],
                        'x' => $conjunction['x'],
                        'z' => $conjunction['z'],
                        'y' => $conjunction['y'],
                        'mine_id' => $conjunction['mine_id'],
                        'ventilation_id' => $conjunction['ventilation_id'],
                    );

                } else {
                    $conjunction_id = $conjunctions_target_hand[$conjunction['title']][$conjunction['x']][$conjunction['y']][$conjunction['z']];
                }

                $conjunction_new_and_last_ids[$conjunction['id']] = $conjunction_id;
                $conjunction_new_ids[] = array(
                    'conjunction_new_id' => $conjunction_id,
                    'conjunction_last_id' => $conjunction['id']

                );

                $count_all++;
            }

            if (!empty($conjunction_to_db)) {
                $warnings[] = $method_name . " Начинаю записю в conjunction массво";
                $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('conjunction',
                    [
                        'id',
                        'title',
                        'object_id',
                        'x',
                        'z',
                        'y',
                        'mine_id',
                        'ventilation_id',
                    ], $conjunction_to_db)->execute();

                if (!$data_to_db) {
                    throw new Exception($method_name . '. Не удалось массово сохранить conjunction в таблице conjunction');
                }
            }

            /** Отладка */
            $description = ' Закончил с conjunction';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;
            unset($conjunction_to_db);
            unset($data_to_db);

            $count_all = 0;
            $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('conjunction_new_ids',
                [
                    'conjunction_new_id',
                    'conjunction_last_id'
                ], $conjunction_new_ids)->execute();
            $count_all = $data_to_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новeю связку в таблице conjunction_new_ids');
            }


            unset($conjunctions);
            unset($new_main);
            unset($conjunction_new_ids);
            /** Отладка */
            $description = ' Закончил с conjunction_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;

            $warnings[] = $method_name . ' Начинаю получать данные с conjunction_parameter';

            $conjunction_parameters = Yii::$app->db_source->createCommand('SELECT * FROM conjunction_parameter')->queryAll();
            $conjunction_parameters_target = Yii::$app->db_target->createCommand('SELECT * FROM conjunction_parameter')->queryAll();
            foreach ($conjunction_parameters_target as $conjunction_parameter_target) {
                $conjunction_parameters_target_hand[$conjunction_parameter_target['conjunction_id']][$conjunction_parameter_target['parameter_id']][$conjunction_parameter_target['parameter_type_id']] = $conjunction_parameter_target['id'];
            }

            $count = 0;
            foreach ($conjunction_parameters as $each) {
                $count_all++;

                if ($conjunction_new_and_last_ids[$each['conjunction_id']]) {

                    if (!isset($conjunction_parameters_target_hand[$conjunction_new_and_last_ids[$each['conjunction_id']]][$each['parameter_id']][$each['parameter_type_id']])) {
                        $data_from_old_db = Yii::$app->db_target->createCommand()->insert('conjunction_parameter',
                            [
                                'conjunction_id' => $conjunction_new_and_last_ids[$each['conjunction_id']],
                                'parameter_id' => $each['parameter_id'],
                                'parameter_type_id' => $each['parameter_type_id'],

                            ]
                        )->execute();
                        $conjunction_parameter_id = Yii::$app->db_target->getLastInsertID();
                    } else {
                        $conjunction_parameter_id = $conjunction_parameters_target_hand[$conjunction_new_and_last_ids[$each['conjunction_id']]][$each['parameter_id']][$each['parameter_type_id']];
                    }

                    $conjunction_parameter_new_ids[] = array(
                        'conjunction_parameter_new_id' => $conjunction_parameter_id,
                        'conjunction_parameter_last_id' => $each['id']

                    );
                    $conjunction_parameter_last_and_new_ids[$each['id']] = $conjunction_parameter_id;
                }
            }
            unset($conjunction_new_and_last_ids);


            /** Отладка */
            $description = ' Закончил с conjunction_parameter';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $count_all = 0;
            if (!empty($conjunction_parameter_new_ids)) {
                $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('conjunction_parameter_new_ids',
                    [
                        'conjunction_parameter_new_id',
                        'conjunction_parameter_last_id'
                    ], $conjunction_parameter_new_ids)->execute();
                $count_all = $data_to_db;
                if (!$data_to_db) {
                    throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице conjunction_parameter_new_ids');
                }
            }
            unset($conjunction_parameter_new_ids);
            unset($conjunction_parameters);

            /** Отладка */
            $description = ' Закончил с conjunction_parameter_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;


            $warnings[] = $method_name . ' Получаю данные conjunction_parameter_handbook_value';


            $conjunction_parameter_handbook_values = Yii::$app->db_source->createCommand('SELECT * FROM conjunction_parameter_handbook_value')->queryAll();
            $conjunction_parameter_handbook_values_target = Yii::$app->db_target->createCommand('SELECT * FROM conjunction_parameter_handbook_value')->queryAll();
            foreach ($conjunction_parameter_handbook_values_target as $conjunction_parameter_handbook_value_target) {
                $date_time_format = date("Y-m-d H:i:s", strtotime($conjunction_parameter_handbook_value_target['date_time']));
                $conjunction_parameter_handbook_values_target_hand[$conjunction_parameter_handbook_value_target['conjunction_parameter_id']][$date_time_format] = $conjunction_parameter_handbook_value_target;
            }

            foreach ($conjunction_parameter_handbook_values as $each) {
                if ($conjunction_parameter_last_and_new_ids[$each['conjunction_parameter_id']]) {

                    $date_time_format = date("Y-m-d H:i:s", strtotime($each['date_time']));
                    if (!isset($conjunction_parameter_handbook_values_target_hand[$conjunction_parameter_last_and_new_ids[$each['conjunction_parameter_id']]][$date_time_format])) {
                        $conjunction_parameter_handbook_value[] = array(
                            'conjunction_parameter_id' => $conjunction_parameter_last_and_new_ids[$each['conjunction_parameter_id']],
                            'date_time' => $each['date_time'],
                            'value' => $each['value'],
                            'status_id' => $each['status_id']

                        );
                    }
                } else {
                    $errors[] = $method_name . ' Не найден conjunction_parameter_id =' . $each['conjunction_parameter_id'] . ' в объекте из таблицы conjunction_parameter_new_ids';
                }

            }


            $count_all = 0;
            if (!empty($conjunction_parameter_handbook_value)) {
                $data_to_db = Yii::$app->db_target->createCommand()->batchInsert('conjunction_parameter_handbook_value',
                    [
                        'conjunction_parameter_id',
                        'date_time',
                        'value',
                        'status_id'

                    ], $conjunction_parameter_handbook_value)->execute();
                $count_all = $data_to_db;
                if (!$data_to_db) {
                    throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице conjunction_parameter_handbook_value');
                }
            }

            /** Отладка */
            $description = ' Закончил с conjunction_parameter_handbook_value';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     * TransOrderPlace - метод по переносу данные с табицы order_place c новых place_id
     */
    public static function TransOrderPlace()
    {

        // Стартовая отладочная информация
        $method_name = 'OrderPlace';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю place_new_ids';
            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();
            $place_new_and_last_ids = null;
            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Получил place_new_ids';

            $warnings[] = $method_name . 'Получаю order_place';

            $order_places = Yii::$app->db_source->createCommand('SELECT * FROM order_place')->queryAll();
            $data_to_db = null;
            foreach ($order_places as $each) {

                $data_to_db[] = array(

                    'id' => $each['id'],
                    'order_id' => $each['order_id'],
                    'place_id' => $place_new_and_last_ids[$each['place_id']],
                    'passport_id' => $each['passport_id'],
                    'coordinate' => $each['coordinate'],
                    'edge_id' => $each['edge_id'],
                    'route_template_id' => $each['route_template_id'],
                );


            }
            $count_all = 0;
            $result_insert_from_db = Yii::$app->db_target->createCommand()->batchInsert('order_place',
                [
                    'id',
                    'order_id',
                    'place_id',
                    'passport_id',
                    'coordinate',
                    'edge_id',
                    'route_template_id',
                ], $data_to_db)->execute();
            $count_all = $result_insert_from_db;
            if (!$result_insert_from_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице order_place');
            }
            unset($data_to_db);
            unset($order_worker_coordinates);


            /** Отладка */
            $description = ' Закончил с order_place';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     * TransOrderWorkerCoordinatee - метод по пеносу данных с order_worker_coordinate c новых place_id
     */
    public static function TransOrderWorkerCoordinatee()
    {

        // Стартовая отладочная информация
        $method_name = 'OrderPlace';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю place_new_ids';
            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();
            $place_new_and_last_ids = null;
            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Получил place_new_ids';

            $warnings[] = $method_name . 'Получаю order_worker_coordinate';
            $order_worker_coordinates = Yii::$app->db_source->createCommand('SELECT * FROM order_worker_coordinate')->queryAll();
            foreach ($order_worker_coordinates as $each) {

                if (isset($place_new_and_last_ids[$each['place_id']])) {
                    $data_to_db[] = array(
                        'id' => $each['id'],
                        'edge_id' => $each['edge_id'],
                        'place_id' => $place_new_and_last_ids[$each['place_id']],
                        'order_id' => $each['order_id'],
                        'worker_id' => $each['worker_id'],
                        'brigade_id' => $each['brigade_id'],
                        'chane_id' => $each['chane_id'],
                        'coordinate_chane' => $each['coordinate_chane'],
                        'coordinate_worker' => $each['coordinate_worker'],

                    );
                } else {
                    $errors[] = $method_name . ' не найден place_id ' . $each['place_id'];
                }

            }
            $count_all = 0;
            $result_insert_from_db = Yii::$app->db_target->createCommand()->batchInsert('order_worker_coordinate',
                [
                    'id',
                    'edge_id',
                    'place_id',
                    'order_id',
                    'worker_id',
                    'brigade_id',
                    'chane_id',
                    'coordinate_chane',
                    'coordinate_worker',
                ], $data_to_db)->execute();
            $count_all = $result_insert_from_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице order_worker_coordinate');
            }
            unset($data_to_db);
            unset($order_worker_coordinates);
            /** Отладка */
            $description = ' Закончил order_worker_coordinate';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     * TransOrderOperation - метод по пеносу данных с order_operation c новых equipment_id
     */
    public static function TransOrderOperation()
    {

        // Стартовая отладочная информация
        $method_name = 'TransOrderOperation';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю place_new_ids';
            $equipment_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM equipment_new_ids')->queryAll();
            $equipment_new_and_last_ids = null;
            foreach ($equipment_new_ids as $equipment_new_id) {
                $equipment_new_and_last_ids[$equipment_new_id['equipment_last_id']] = $equipment_new_id['equipment_new_id'];
            }
            $warnings[] = $method_name . ' Получил place_new_ids';

            $warnings[] = $method_name . 'Получаю order_operation';
            $order_operations = Yii::$app->db_source->createCommand('SELECT * FROM order_operation')->queryAll();
            $order_operation_last_and_new_ids = null;
            $count_all = 0;
            $data_to_db = null;
            foreach ($order_operations as $each) {
                $count_all++;
                $data_to_db[] = array(
                    'id' => $each['id'],
                    'order_place_id' => $each['order_place_id'],
                    'operation_id' => $each['operation_id'],
                    'operation_value_plan' => $each['operation_value_plan'],
                    'operation_value_fact' => $each['operation_value_fact'],
                    'status_id' => $each['status_id'],
                    'description' => $each['description'],
                    'equipment_id' => $equipment_new_and_last_ids[$each['equipment_id']],
                    'order_operation_id_vtb' => $each['order_operation_id_vtb'],
                    'correct_measures_id' => $each['correct_measures_id'],
                    'order_place_id_vtb' => $each['order_operation_id_vtb'],
                    'coordinate' => $each['coordinate'],
                    'edge_id' => $each['edge_id'],
                    'injunction_violation_id' => $each['injunction_violation_id'],
                    'injunction_id' => $each['injunction_id'],
                );


            }
            unset($order_operations);
            /** Отладка */
            $description = ' Закончил order_operation';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            $result_insert_from_db = Yii::$app->db_target->createCommand()->batchInsert('order_operation',
                [
                    'id',
                    'order_place_id',
                    'operation_id',
                    'operation_value_plan',
                    'operation_value_fact',
                    'status_id',
                    'description',
                    'equipment_id',
                    'order_operation_id_vtb',
                    'correct_measures_id',
                    'order_place_id_vtb',
                    'coordinate',
                    'edge_id',
                    'injunction_violation_id',
                    'injunction_id',
                ], $data_to_db)->execute();
            $count_all = $result_insert_from_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице order_operation');
            }
            unset($data_to_db);
            unset($order_worker_coordinates);
            /** Отладка */
            $description = ' Закончил вставку order_operation';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     * TransOrderTemplatePlace - метод по переносу данные с табицы order_template_place c новых place_id
     */
    public static function TransOrderTemplatePlace()
    {

        // Стартовая отладочная информация
        $method_name = 'TransOrderTemplatePlace';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю place_new_ids';
            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();
            $place_new_and_last_ids = null;
            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Получил place_new_ids';

            $warnings[] = $method_name . 'Получаю order_template_place';

            $order_places = Yii::$app->db_source->createCommand('SELECT * FROM order_template_place')->queryAll();
            $data_to_db = null;
            foreach ($order_places as $each) {

                $data_to_db[] = array(

                    'id' => $each['id'],
                    'place_id' => $place_new_and_last_ids[$each['place_id']],
                    'passport_id' => $each['passport_id'],
                    'order_template_id' => $each['order_template_id'],
                    'coordinate' => $each['coordinate'],
                    'edge_id' => $each['edge_id'],
                    'route_template_id' => $each['route_template_id'],
                );


            }
            $count_all = 0;
            $result_insert_from_db = Yii::$app->db_target->createCommand()->batchInsert('order_template_place',
                [
                    'id',
                    'place_id',
                    'passport_id',
                    'order_template_id',
                    'coordinate',
                    'edge_id',
                    'route_template_id',

                ], $data_to_db)->execute();
            $count_all = $result_insert_from_db;
            if (!$data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице order_template_place');
            }
            unset($data_to_db);
            unset($order_worker_coordinates);


            /** Отладка */
            $description = ' Закончил с order_template_place';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     *
     * TransOrderPlaceVtbAb - метод по переносу данные с табицы order_place_vtb_ab c новых place_id
     */
    public static function TransOrderPlaceVtbAb()
    {

        // Стартовая отладочная информация
        $method_name = 'TransOrderPlaceVtbAb';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {
            $e = Edge::find();

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю place_new_ids';
            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();
            $order_place_vtb_ab = null;
            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Получил place_new_ids';

            $warnings[] = $method_name . 'Получаю order_place_vtb_ab';

            $order_places = Yii::$app->db_source->createCommand('SELECT * FROM order_place_vtb_ab')->queryAll();
            $data_to_db = null;
            foreach ($order_places as $each) {

                $data_to_db[] = array(

                    'id' => $each['id'],
                    'order_vtb_ab_id' => $each['order_vtb_ab_id'],
                    'place_id' => $place_new_and_last_ids[$each['place_id']],

                );


            }
            $count_all = 0;
            $result_insert_from_db = Yii::$app->db_target->createCommand()->batchInsert('order_place_vtb_ab',
                [
                    'id',
                    'order_vtb_ab_id',
                    'place_id',


                ], $data_to_db)->execute();
            $count_all = $result_insert_from_db;
            if (!$result_insert_from_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый запись в таблице order_place_vtb_ab');
            }
            unset($data_to_db);
            unset($order_worker_coordinates);


            /** Отладка */
            $description = ' Закончил с order_place_vtb_ab';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     *
     * TransSensorPlaceHandbook - метод по изменения старого ключа места на новое для сенсоров в таблице sensor_parameter_handbook_value
     * алгоритм:
     * 1. получаем трансферную таблицу мест
     * 2. получаем справочные параметры сенсора в части мест параметр 122
     * 3. обновляем параметр на новый и так по по кругу
     */
    public static function TransSensorPlaceHandbook()
    {

        // Стартовая отладочная информация
        $method_name = 'TransSensorPlaceHandbook';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю place_new_ids';

            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();

            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Закончил получать place_new_ids и формировать справочник ключей для замены';

            $sphvs_place = SensorParameterHandbookValue::find()
                ->innerJoin('sensor_parameter', 'sensor_parameter.id=sensor_parameter_handbook_value.sensor_parameter_id and sensor_parameter.parameter_id=122 and sensor_parameter.parameter_type_id=1');

            foreach ($sphvs_place->each(2000) as $sphv_place) {
                $last_edge = $sphv_place->value;
                if (isset($place_new_and_last_ids[$last_edge])) {
                    $sphv_place->value = $place_new_and_last_ids[$last_edge];
                    if (!$sphv_place->save()) {
                        throw new Exception($method_name . '. Не удалось сохранить SensorParameterHandbookValue для старого edge_id=' . $last_edge . ' на новый edge_id=' . $place_new_and_last_ids[$last_edge]);
                    }
                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;

            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     * TransSensorEdgeHandbook - метод изминения старых эджей на новый в таблице sensor_parameter_handbook_value
     * 1. Получаем данные из трансферной таблице  edge_new_ids
     * 2. Получем все 269 из таблицы sensor_parameter_handbook_value
     * 3. Пробегая по полученный массив из sensor_parameter_handbook_value меняем все 269 параметры в соответвии с теми что есть в edge_new_ids и обратно вставляем в БД
     *
     */
    public static function TransSensorEdgeHandbook()
    {

        // Стартовая отладочная информация
        $method_name = 'TransSensorEdgeHandbook';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю edge_new_ids';

            $edge_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM edge_new_ids')->queryAll();
            foreach ($edge_new_ids as $edge_new_id) {
                $edge_last_new_ids[$edge_new_id['edge_last_id']] = $edge_new_id['edge_new_id'];
            }
            $sphvs_edges = SensorParameterHandbookValue::find()
                ->innerJoin('sensor_parameter', 'sensor_parameter.id=sensor_parameter_handbook_value.sensor_parameter_id and sensor_parameter.parameter_id=269 and sensor_parameter.parameter_type_id=1');
            $count_save = 0;
            foreach ($sphvs_edges->each(2500) as $sphv_edge) {
                $last_edge = $sphv_edge->value;
                if (isset($edge_last_new_ids[$last_edge])) {
                    $sphv_edge->value = $edge_last_new_ids[$last_edge];
                    if (!$sphv_edge->save()) {
                        throw new \yii\db\Exception('Не удалось обновить старый edge на новый в sensor_parameter_handbook_value');
                    }

                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;


            /** Отладка */
            $description = ' Закончил с edge_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     * TransEquipmentPlaceHandbook - метод по изменения старого ключа места на новое для воркеров в таблице equipment_parameter_handbook_value
     * алгоритм:
     * 1. получаем трансферную таблицу мест
     * 2. получаем измеряемые параметры сенсора в части мест параметр 122
     * 3. обновляем параметр на новый и так по по кругу
     */
    public static function TransEquipmentPlaceHandbook()
    {

        // Стартовая отладочная информация
        $method_name = 'TransEquipmentPlaceHandbook';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю place_new_ids';

            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();

            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Закончил получать place_new_ids и формировать справочник ключей для замены';

            $sphvs_place = EquipmentParameterHandbookValue::find()
                ->innerJoin('equipment_parameter', 'equipment_parameter.id=equipment_parameter_handbook_value.equipment_parameter_id and equipment_parameter.parameter_id=122 and equipment_parameter.parameter_type_id=2');

            $count_save = 0;
            foreach ($sphvs_place->each(2000) as $sphv_place) {
                $last_place = $sphv_place->value;
                if (isset($place_new_and_last_ids[$last_place])) {
                    $sphv_place->value = $place_new_and_last_ids[$last_place];
                    if (!$sphv_place->save()) {
                        throw new Exception($method_name . '. Не удалось обновить equipment_parameter_handbook_value для старого plcae_id=' . $last_edge . ' на новый plcae_id=' . $place_new_and_last_ids[$last_edge]);
                    }
                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;

            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     * TransEquipmentEdgeHandbook() - метод изминения старых эджей на новый в таблице equipment_parameter_handbook_value
     * 1. Получаем данные из трансферной таблице  edge_new_ids
     * 2. Получем все 269 из таблицы sensor_parameter_handbook_value
     * 3. Пробегая по полученный массив из sensor_parameter_handbook_value меняем все 269 параметры в соответвии с теми что есть в edge_new_ids и обратно вставляем в БД
     *
     */
    public static function TransEquipmentEdgeHandbook()
    {

        // Стартовая отладочная информация
        $method_name = 'TransEquipmentEdgeHandbook';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю edge_new_ids';

            $edge_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM edge_new_ids')->queryAll();
            foreach ($edge_new_ids as $edge_new_id) {
                $edge_last_new_ids[$edge_new_id['edge_last_id']] = $edge_new_id['edge_new_id'];
            }
            $sphvs_edges = EquipmentParameterHandbookValue::find()
                ->innerJoin('equipment_parameter', 'equipment_parameter.id=equipment_parameter_handbook_value.equipment_parameter_id and equipment_parameter.parameter_id=269 and equipment_parameter.parameter_type_id=1');
            $count_save = 0;
            foreach ($sphvs_edges->each(2500) as $sphv_edge) {
                $last_edge = $sphv_edge->value;
                if (isset($edge_last_new_ids[$last_edge])) {
                    $sphv_edge->value = $edge_last_new_ids[$last_edge];
                    if (!$sphv_edge->save()) {
                        throw new \yii\db\Exception('Не удалось обновить старый edge на новый в equipment_parameter_handbook_value');
                    }

                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;


            /** Отладка */
            $description = ' Закончил с edge_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }
    }

    /**
     *
     * TransWorkerPlaceValue - метод по изменения старого ключа места на новое для воркеров в таблице worker_parameter_value
     * алгоритм:
     * 1. получаем трансферную таблицу мест
     * 2. получаем измеряемые параметры сенсора в части мест параметр 122
     * 3. обновляем параметр на новый и так по по кругу
     */
    public static function TransWorkerPlaceValue()
    {

        // Стартовая отладочная информация
        $method_name = 'TransWorkerPlaceHandbook';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю place_new_ids';

            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();

            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Закончил получать place_new_ids и формировать справочник ключей для замены';

            $sphvs_place = WorkerParameterValueTemp::find()
                ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value.worker_parameter_id and worker_parameter.parameter_id=122 and worker_parameter.parameter_type_id=2');

            foreach ($sphvs_place->each(2000) as $sphv_place) {
                $last_place = $sphv_place->value;
                if (isset($place_new_and_last_ids[$last_place])) {
                    $sphv_place->value = $place_new_and_last_ids[$last_place];
                    if (!$sphv_place->save()) {
                        throw new Exception($method_name . '. Не удалось обновить worker_parameter_value для старого plcae_id=' . $last_edge . ' на новый plcae_id=' . $place_new_and_last_ids[$last_edge]);
                    }
                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;

            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     *
     * TransWorkerEdgeValue - метод по изменения старого ключа эджа на новое для воркеров в таблице worker_parameter_value
     * алгоритм:
     * 1. получаем трансферную таблицу мест
     * 2. получаем измеряемые параметры сенсора в части мест параметр 269
     * 3. обновляем параметр на новый и так по по кругу
     */
    public static function TransWorkerEdgeValue()
    {

        // Стартовая отладочная информация
        $method_name = 'TransWorkerEdgeValue';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю place_new_ids';

            $new_ids = Yii::$app->db_target->createCommand('SELECT * FROM edge_new_ids')->queryAll();

            foreach ($new_ids as $new_id) {
                $new_and_last_ids[$new_id['edge_last_id']] = $new_id['edge_new_id'];
            }
            $warnings[] = $method_name . ' Закончил получать edge_new_ids и формировать справочник ключей для замены';
            $data_from_db = WorkerParameterValueTemp::find()
                ->innerJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value.worker_parameter_id and worker_parameter.parameter_id=269 and worker_parameter.parameter_type_id=2');
            foreach ($data_from_db->each(2000) as $eache_items) {
                $last_value = $eache_items->value;
                if (isset($new_and_last_ids[$last_value])) {
                    $eache_items->value = $new_and_last_ids[$last_value];
                    if (!$eache_items->save()) {
                        throw new Exception($method_name . '. Не удалось обновить worker_parameter_value для старого plcae_id=' . $last_edge . ' на новый plcae_id=' . $place_new_and_last_ids[$last_edge]);
                    }
                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;

            /** Отладка */
            $description = ' Закончил обновление worker_parameter_value';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     *
     * TransEquipmentPlaceValue - метод по изменения старого ключа места на новое для воркеров в таблице worker_parameter_value
     * алгоритм:
     * 1. получаем трансферную таблицу мест
     * 2. получаем измеряемые параметры сенсора в части мест параметр 122
     * 3. обновляем параметр на новый и так по по кругу
     */
    public static function TransEquipmentPlaceValue()
    {

        // Стартовая отладочная информация
        $method_name = 'TransEquipmentPlaceValue';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю place_new_ids';

            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();

            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Закончил получать place_new_ids и формировать справочник ключей для замены';

            $spvs_place = EquipmentParameterValue::find()
                ->innerJoin('equipment_parameter', 'equipment_parameter.id=equipment_parameter_value.equipment_parameter_id and equipment_parameter.parameter_id=122 and equipment_parameter.parameter_type_id=2');

            foreach ($spvs_place->each(2000) as $spv_place) {

                $last_place = $spv_place->value;
                if (isset($place_new_and_last_ids[$last_place])) {
                    $spv_place->value = $place_new_and_last_ids[$last_place];
                    if (!$spv_place->save()) {
                        throw new Exception($method_name . '. Не удалось обновить equipment_parameter_value для старого plcae_id=' . $last_edge . ' на новый plcae_id=' . $place_new_and_last_ids[$last_edge]);
                    }
                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;

            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     *
     * TransEquipmentEdgeValue - метод по изменения старого ключа места на новое для оборудовании в таблице equipment_parameter_value
     * алгоритм:
     * 1. получаем трансферную таблицу мест
     * 2. получаем измеряемые параметры сенсора в части мест параметр 269
     * 3. обновляем параметр на новый и так по по кругу
     */
    public static function TransEquipmentEdgeValue()
    {

        // Стартовая отладочная информация
        $method_name = 'TransEquipmentEdgeValue';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю place_new_ids';

            $new_ids = Yii::$app->db_target->createCommand('SELECT * FROM edge_new_ids')->queryAll();

            foreach ($new_ids as $new_id) {
                $new_and_last_ids[$new_id['edge_last_id']] = $new_id['edge_new_id'];
            }
            $warnings[] = $method_name . ' Закончил получать edge_new_ids и формировать справочник ключей для замены';
            $data_from_db = EquipmentParameterValue::find()
                ->innerJoin('equipment_parameter', 'equipment_parameter.id=equipment_parameter_value.equipment_parameter_id and equipment_parameter.parameter_id=269 and equipment_parameter.parameter_type_id=2');
            foreach ($data_from_db->each(2000) as $eache_items) {
                $last_value = $eache_items->value;
                if (isset($new_and_last_ids[$last_value])) {
                    $eache_items->value = $new_and_last_ids[$last_value];
                    if (!$eache_items->save()) {
                        throw new Exception($method_name . '. Не удалось обновить equipment_parameter_value для старого plcae_id=' . $last_edge . ' на новый plcae_id=' . $place_new_and_last_ids[$last_edge]);
                    }
                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;

            /** Отладка */
            $description = ' Закончил обновление equipment_parameter_value';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     *
     * TransSensorPlaceValue - метод по изменения старого ключа места на новое для сенсоров в таблице sensor_parameter_value
     * алгоритм:
     * 1. получаем трансферную таблицу мест
     * 2. получаем измеряемые параметры сенсора в части мест параметр 122
     * 3. обновляем параметр на новый и так по по кругу
     */
    public static function TransSensorPlaceValue()
    {

        // Стартовая отладочная информация
        $method_name = 'TransSensorPlaceValue';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю place_new_ids';

            $place_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM place_new_ids')->queryAll();

            foreach ($place_new_ids as $place_new_id) {
                $place_new_and_last_ids[$place_new_id['place_last_id']] = $place_new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Закончил получать place_new_ids и формировать справочник ключей для замены';

            $spvs_place = SensorParameterValue::find()
                ->innerJoin('sensor_parameter', 'sensor_parameter.id=sensor_parameter_value.sensor_parameter_id and sensor_parameter.parameter_id=122 and sensor_parameter.parameter_type_id=2');
            foreach ($spvs_place->each(2000) as $spv_place) {

                $last_place = $spv_place->value;
                if (isset($place_new_and_last_ids[$last_place])) {
                    $spv_place->value = $place_new_and_last_ids[$last_place];
                    if (!$spv_place->save()) {
                        throw new Exception($method_name . '. Не удалось обновить sensor_parameter_value для старого plcae_id=' . $last_edge . ' на новый plcae_id=' . $place_new_and_last_ids[$last_edge]);
                    }
                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;

            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }

    /**
     *
     * TransEdgePlaceHandbook - метод по изменения старого ключа места на новое для выработок в таблице edge_parameter_handbook_value
     * алгоритм:
     * 1. получаем трансферную таблицу мест
     * 2. получаем измеряемые параметры сенсора в части мест параметр 269
     * 3. обновляем параметр на новый и так по по кругу
     */
    public static function TransEdgePlaceHandbook()
    {

        // Стартовая отладочная информация
        $method_name = 'TransEdgePlaceHandbook';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю place_new_ids';

            $new_ids = Yii::$app->db_target->createCommand('SELECT * FROM plcae_new_ids')->queryAll();

            foreach ($new_ids as $new_id) {
                $new_and_last_ids[$new_id['place_last_id']] = $new_id['place_new_id'];
            }
            $warnings[] = $method_name . ' Закончил получать edge_new_ids и формировать справочник ключей для замены';
            $data_from_db = EdgeParameterHandbookValue::find()
                ->innerJoin('edge_parameter', 'edge_parameter.id=edge_parameter_handbook_value.edge_parameter_id and edge_parameter.parameter_id=269 and edge_parameter.parameter_type_id=1');
            foreach ($data_from_db->each(2000) as $eache_items) {
                $last_value = $eache_items->value;
                if (isset($new_and_last_ids[$last_value])) {
                    $eache_items->value = $new_and_last_ids[$last_value];
                    if (!$eache_items->save()) {
                        throw new Exception($method_name . '. Не удалось обновить edge_parameter_handbook_value для старого plcae_id=' . $last_edge . ' на новый plcae_id=' . $place_new_and_last_ids[$last_edge]);
                    }
                    $count_save++;
                }
                $count_all++;
            }

            $warnings[] = $method_name . '. Количество сохраненных записей ' . $count_save;
            $warnings[] = $method_name . '. Количество всех записей ' . $count_all;

            /** Отладка */
            $description = ' Закончил обновление equipment_parameter_value';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        /* Yii::$app->response->format = Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/

    }


    /**
     * JoinTableParameters() - метод по объединению таблицы parameter по OPC тэгам
     * алгоритм:
     * 1. Поулчить все OPC сервера имеющейся в данной предприятии (SELECT * FROM sensor where object_id = 155)
     * 2. Получить все тэги по конкретному OPC серверу
     * 3. По одной вставит в новый БД в таблицы parameter и обновит этот стрый parameter_id в строй БД на новый полученноц из новой БД
     * Важно! Перед запуском обязательно самостоятельно запустить метод по получении тэгов и убедится в том, что не возвращает нечего кроме тэгов
     * Метод для получнии тэгов по конкретному OPC серверу - http://127.0.0.1/admin/opc/get-list-tags?sensor_id=301601&mine_id=290
     *  где sensor_id - можно получить по http://127.0.0.1/admin/opc/get-config?opc_title=ССД OPC Комсомольская
     *  где opc_title - это названии OPC сервера который прписан в конфиге ССД
     */
    public static function JoinTableParameters()
    {
        // Стартовая отладочная информация
        $method_name = 'JoinTableParameters';                                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                             // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                           // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                               // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю OPC сервера';
            $opc_servers = Yii::$app->db_source->createCommand('SELECT * FROM sensor where object_id = 155')->queryAll();
            $opc_server_sensor_id = null;
            foreach ($opc_servers as $opc_server) {
                if ($opc_server['title'] != 'Заглушка') {
                    if (isset($opc_server['id']) and $opc_server['id'] != '') {
                        $opc_server_sensor_id = $opc_server['id'];
                        $warnings[] = $method_name . ' Есть OPC = ' . $opc_server_sensor_id . ' title = ' . $opc_server['title'];

                        $opc_list_tags = Yii::$app->db_source->createCommand
                        (
                            'SELECT 
                        sensor_id, 
                        parameter_id
                            FROM sensor_parameter 
                            join parameter on  sensor_parameter.parameter_id=parameter.id 
                            where sensor_id = ' . $opc_server_sensor_id . ' and parameter_type_id = 2 and parameter_id != 346'
                        )->queryAll();


                        foreach ($opc_list_tags as $tag) {
                            $count_all++;

                            $last_parameter_id = $tag['parameter_id'];

                            $parameter = Yii::$app->db_source->createCommand('SELECT * FROM parameter where (id = ' . $last_parameter_id . ')')->queryAll();
                            Yii::$app->db_target->createCommand()->insert('parameter', ['title' => $parameter[0]['title'], 'unit_id' => $parameter[0]['unit_id'], 'kind_parameter_id' => $parameter[0]['kind_parameter_id']])->execute();
                            $parameter_new_id = Yii::$app->db_target->getLastInsertID();
                            $parameter_to_db = ParameterSunc::find()
                                ->where(['id' => $last_parameter_id])
                                ->one();

                            $parameter_to_db->id = $parameter_new_id;
                            if (!$parameter_to_db->save()) {
                                throw new Exception(' Не удалось обновить parameter ' . $last_parameter_id);
                            }

                            //Yii::$app->db_source->createCommand('UPDATE parameter SET id = ' . $parameter_new_id . ' WHERE (id = ' . $last_parameter_id . ')')->queryAll();

                        }
                    } else {
                        throw new \yii\db\Exception(' Не находил ни один OPC сервер');
                    }

                }

            }


            /** Отладка */
            $description = ' Закончил выполенеие метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        return array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

    }


    /**
     * TransSituationJournal() - метод по переносу данных с таблиц situation_journal
     * спикок задейсвованых таблиц:
     * situation_journal
     *  situation_journal_correct_measure
     *  situation_journal_gilty
     *  situation_journal_send_status
     *  situation_journal_zone
     *  situation_status
     *  Важно! Метод запустит после переключения БД новый как основной
     */
    public static function TransSituationJournal()
    {

        // Стартовая отладочная информация
        $method_name = 'TransSituationJournal ';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");
        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю данные из таблицы sensor_new_ids';
            $sensor_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM sensor_new_ids')->queryAll();
            $count_all = 0;
            $sensors_new_and_last_ids = null;
            foreach ($sensor_new_ids as $sensors_new_id) {

                $sensors_new_and_last_ids[$sensors_new_id['sensor_last_id']] = $sensors_new_id['sensor_new_id'];
            }
            unset($sensor_new_ids);
            $warnings[] = $method_name . 'Получаю данные из таблицы situation_journal';
            $situation_journals = Yii::$app->db_source->createCommand('SELECT * FROM situation_journal')->queryAll();
            foreach ($situation_journals as $situation_journal) {
                $count_all++;
                if (isset($sensors_new_and_last_ids[$situation_journal['main_id']])) {
                    $main_id = $sensors_new_and_last_ids[$situation_journal['main_id']];
                } else {
                    $main_id = $situation_journal['main_id'];
                }
                $SituationJournal = new SituationJournal();
                $SituationJournal->id = $situation_journal['id'];
                $SituationJournal->situation_id = $situation_journal['situation_id'];
                $SituationJournal->date_time = $situation_journal['date_time'];
                $SituationJournal->main_id = $main_id;
                $SituationJournal->status_id = $situation_journal['status_id'];
                $SituationJournal->danger_level_id = $situation_journal['danger_level_id'];
                $SituationJournal->company_department_id = $situation_journal['company_department_id'];
                $SituationJournal->mine_id = $situation_journal['mine_id'];
                $SituationJournal->date_time_start = $situation_journal['date_time_start'];
                $SituationJournal->date_time_end = $situation_journal['date_time_end'];
                if (!$SituationJournal->save()) {
                    throw new \yii\db\Exception($method_name . ' Не удалось сохранить запись в таблицу situation_journal');
                }
                $situation_journal_new_ids[] = array(
                    'situation_journal_new_id' => $SituationJournal->id,
                    'situation_journal_last_id' => $situation_journal['id']
                );
                $situation_journal_last_and_new_ids[$situation_journal['id']] = $SituationJournal->id;
            }
            unset($situation_journals);
            /** Отладка */
            $description = ' Закончил с situation_journal';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Начинаю массовую вставку в таблицы situation_journal_new_ids';
            $count_all = 0;
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('situation_journal_new_ids', ['situation_journal_new_id', 'situation_journal_last_id'], $situation_journal_new_ids)->execute();
            unset($situation_journal_new_ids);
            /** Отладка */
            $description = ' Закончил с situation_journal_new_ids';                                                         // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                           // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Начинаю работат с situation_journal_correct_measure';
            $situation_journal_correct_measures = Yii::$app->db_source->createCommand('SELECT * FROM situation_journal_correct_measure')->queryAll();
            foreach ($situation_journal_correct_measures as $situation_journal_correct_measure) {
                $data_to_db[] = array(
                    'situation_journal_id' => $situation_journal_last_and_new_ids[$situation_journal_correct_measure['situation_journal_id']],
                    'operation_id' => $situation_journal_correct_measure['operation_id']
                );
            }
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('situation_journal_correct_measure', ['situation_journal_id', 'operation_id'], $data_to_db)->execute();
            unset($data_to_db);
            /** Отладка */
            $description = ' Закончил с situation_journal_correct_measure';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Начинаю работат с situation_journal_gilty';
            $situation_journal_giltys = Yii::$app->db_source->createCommand('SELECT * FROM situation_journal_gilty')->queryAll();
            foreach ($situation_journal_giltys as $situation_journal_gilty) {
                $data_to_db[] = array(
                    'situation_journal_id' => $situation_journal_last_and_new_ids[$situation_journal_gilty['situation_journal_id']],
                    'worker_id' => $situation_journal_gilty['worker_id']
                );
            }
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('situation_journal_gilty', ['situation_journal_id', 'worker_id'], $data_to_db)->execute();
            unset($data_to_db);
            /** Отладка */
            $description = ' Закончил с situation_journal_gilty';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            $warnings[] = $method_name . 'Начинаю работат с situation_journal_send_status';
            $situation_journal_send_statuses = Yii::$app->db_source->createCommand('SELECT * FROM situation_journal_send_status')->queryAll();
            foreach ($situation_journal_send_statuses as $situation_journal_send_status) {
                $data_to_db[] = array(
                    'situation_journal_id' => $situation_journal_last_and_new_ids[$situation_journal_send_status['situation_journal_id']],
                    'status_id' => $situation_journal_send_status['status_id'],
                    'date_time' => $situation_journal_send_status['date_time'],
                    'xml_send_type_id' => $situation_journal_send_status['xml_send_type_id']
                );
            }
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('situation_journal_send_status', ['situation_journal_id', 'status_id', 'date_time', 'xml_send_type_id'], $data_to_db)->execute();
            unset($data_to_db);
            unset($situation_journal_send_statuses);
            /** Отладка */
            $description = ' Закончил с situation_journal_send_status';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            //
//            $new_ids = Yii::$app->db_target->createCommand('SELECT * FROM situation_journal_new_ids')->queryAll();
//
//            foreach ($new_ids as $new_id) {
//                $situation_journal_last_and_new_ids[$new_id['situation_journal_last_id']] = $new_id['situation_journal_new_id'];
//            }
//            unset($new_ids);
            //

            $new_ids = Yii::$app->db_target->createCommand('SELECT * FROM edge_new_ids')->queryAll();

            foreach ($new_ids as $new_id) {
                $new_and_last_ids[$new_id['edge_last_id']] = $new_id['edge_new_id'];
            }
            unset($new_ids);
            $warnings[] = $method_name . 'Начинаю работат с situation_journal_zone';
            $situation_journal_zones = Yii::$app->db_source->createCommand('SELECT * FROM situation_journal_zone')->queryAll();
            foreach ($situation_journal_zones as $situation_journal_zone) {
                $data_to_db[] = array(
                    'situation_journal_id' => $situation_journal_last_and_new_ids[$situation_journal_zone['situation_journal_id']],
                    'edge_id' => $new_and_last_ids[$situation_journal_zone['edge_id']]
                );
            }
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('situation_journal_zone', ['situation_journal_id', 'edge_id'], $data_to_db)->execute();
            unset($data_to_db);
            unset($situation_journal_zones);
            /** Отладка */
            $description = ' Закончил с situation_journal_zone';                                                     // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            $warnings[] = $method_name . 'Начинаю работат с situation_status';
            $situation_statuses = Yii::$app->db_source->createCommand('SELECT * FROM situation_status')->queryAll();
            foreach ($situation_statuses as $situation_status) {
                $data_to_db[] = array(
                    'situation_journal_id' => $situation_journal_last_and_new_ids[$situation_status['situation_journal_id']],
                    'status_id' => $situation_status['status_id'],
                    'date_time' => $situation_status['date_time'],
                    'kind_reason_id' => $situation_status['kind_reason_id'],
                    'description' => $situation_status['description'],

                );
            }
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('situation_status', ['situation_journal_id', 'status_id', 'date_time', 'kind_reason_id', 'description'], $data_to_db)->execute();
            unset($situation_statuses);
            unset($data_to_db);
            /** Отладка */
            $description = ' Закончил с situation_status';                                                     // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
        }
        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
    }


    /**
     * TransEvents() - метод по переносу всех событий
     * спикок задейсвованых таблиц:
     * event_journal
     *  event_status
     *    event_journal_status
     *    event_journal_situation_journal
     *    event_compare_gas
     * Важно! Метод запустит после переключения БД новый как основной. Обратите внимание, данные берутся за 2020-03-11
     */
    public static function TransEvents()
    {

        // Стартовая отладочная информация
        $method_name = 'TransEvents ';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");
        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $warnings[] = $method_name . 'Получаю данные из таблицы sensor_new_ids';
            $sensor_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM sensor_new_ids')->queryAll();
            $sensors_new_and_last_ids = null;
            foreach ($sensor_new_ids as $sensors_new_id) {

                $sensors_new_and_last_ids[$sensors_new_id['sensor_last_id']] = $sensors_new_id['sensor_new_id'];
            }
            unset($sensor_new_ids);

            $warnings[] = $method_name . 'Получаю данные из таблицы edge_new_ids';
            $edge_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM edge_new_ids')->queryAll();
            foreach ($edge_new_ids as $new_id) {
                $edge_new_and_last_ids[$new_id['edge_last_id']] = $new_id['edge_new_id'];
            }
            unset($edge_new_ids);
            $warnings[] = $method_name . 'Получаю данные из таблицы event_situation';
            $counter_iteration = true;
            $last_id = -1;
            while ($counter_iteration) {

                $data_from_portable_table = Yii::$app->db_source->createCommand('SELECT * FROM event_journal where date_time > \'2020-03-11\' and id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($data_from_portable_table) < 25000) {
                    $counter_iteration = false;
                }

                foreach ($data_from_portable_table as $item) {
                    $last_id = $item['id'];

                    if ($item['object_table'] == 'sensor' and isset($sensors_new_and_last_ids[$item['main_id']])) {
                        $main_id = $sensors_new_and_last_ids[$item['main_id']];
                    } else {
                        $main_id = $item['main_id'];
                    }
                    if (isset($edge_new_and_last_ids[$item['edge_id']])) {
                        $edge_id = $edge_new_and_last_ids[$item['edge_id']];
                    } else {
                        $edge_id = $item['edge_id'];
                    }

                    $event_joural = new EventJournal();
                    $event_joural->event_id = $item['event_id'];
                    $event_joural->main_id = $main_id;
                    $event_joural->edge_id = $edge_id;
                    $event_joural->value = $item['value'];
                    $event_joural->date_time = $item['date_time'];
                    $event_joural->xyz = $item['xyz'];
                    $event_joural->status_id = $item['status_id'];
                    $event_joural->parameter_id = $item['parameter_id'];
                    $event_joural->object_id = $item['object_id'];
                    $event_joural->mine_id = $item['mine_id'];
                    $event_joural->object_title = $item['object_title'];
                    $event_joural->object_table = $item['object_table'];
                    $event_joural->event_status_id = $item['event_status_id'];
                    $event_joural->group_alarm_id = $item['group_alarm_id'];
                    if (!$event_joural->save()) {
                        throw new \yii\db\Exception(' Не удалось сохранить новый запись в таблицу event_journal');
                    }
                    $event_joural_new_id = $event_joural->id;
                    $event_joural_new_and_last_ids[$item['id']] = $event_joural_new_id;
                    $data_to_db[] = array(
                        'event_joural_new_id' => $event_joural_new_id,
                        'event_joural_last_id' => $item['id']
                    );
                }
            }


            $warnings[] = ' Закончил event_journal';
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            unset($data_from_portable_table);
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('event_joural_new_ids', ['event_joural_new_id', 'event_joural_last_id'], $data_to_db)->execute();
            if (!$count_all) {
                throw new \yii\db\Exception(' Массовая вставка выполнилась с ошибкой');
            }
            $warnings[] = $method_name . ' Закончил массовую вставку в event_joural_new_ids';
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
//            $event_joural_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM event_joural_new_ids')->queryAll();
//            foreach ($event_joural_new_ids as $new_id) {
//                $event_joural_new_and_last_ids[$new_id['event_joural_last_id']] = $new_id['event_joural_new_id'];
//            }
//            unset($event_joural_new_ids);

            $warnings[] = $method_name . 'Получаю данные из таблицы event_compare_gas';
            $counter_iteration = true;
            $last_id = -1;

            $count_all = 0;
            while ($counter_iteration) {

                $data_from_portable_table = Yii::$app->db_source->createCommand('SELECT * FROM event_compare_gas where date_time > \'2020-03-11\' and id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($data_from_portable_table) < 25000) {
                    $counter_iteration = false;
                }
                foreach ($data_from_portable_table as $item) {
                    $last_id = $item['id'];
                    if (
                        isset($event_joural_new_and_last_ids[$item['lamp_event_journal_id']])
                        and isset($edge_new_and_last_ids[$item['static_edge_id']])
                        and isset($sensors_new_and_last_ids[$item['static_sensor_id']])
                        and isset($sensors_new_and_last_ids[$item['lamp_sensor_id']])
                        and isset($edge_new_and_last_ids[$item['lamp_edge_id']])
                        and isset($event_joural_new_and_last_ids[$item['static_event_journal_id']])
                    ) {

                        //TODO: в зависимоти от таго чего лежит в lamp_object_table меняются поля. На данный момент (2020-05-05) все sensor поэтому не добавлен проверка
                        $data_to_db[] = array(
                            'event_id' => $item['event_id'],
                            'date_time' => $item['date_time'],
                            'static_edge_id' => $edge_new_and_last_ids[$item['static_edge_id']],
                            'static_value' => $item['static_value'],
                            'static_sensor_id' => $sensors_new_and_last_ids[$item['static_sensor_id']],
                            'static_xyz' => $item['static_xyz'],
                            'static_status_id' => $item['static_status_id'],
                            'static_parameter_id' => $item['static_parameter_id'],
                            'static_object_id' => $item['static_object_id'],
                            'static_mine_id' => $item['static_mine_id'],
                            'static_object_title' => $item['static_object_title'],
                            'static_object_table' => $item['static_object_table'],
                            'lamp_sensor_id' => $sensors_new_and_last_ids[$item['lamp_sensor_id']],
                            'lamp_edge_id' => $edge_new_and_last_ids[$item['lamp_edge_id']],
                            'lamp_value' => $item['lamp_value'],
                            'lamp_xyz' => $item['lamp_xyz'],
                            'lamp_status_id' => $item['lamp_status_id'],
                            'lamp_parameter_id' => $item['lamp_parameter_id'],
                            'lamp_object_id' => $item['lamp_object_id'],
                            'lamp_mine_id' => $item['lamp_mine_id'],
                            'lamp_object_title' => $item['lamp_object_title'],
                            'lamp_object_table' => $item['lamp_object_table'],
                            'static_event_journal_id' => $event_joural_new_and_last_ids[$item['static_event_journal_id']],
                            'lamp_event_journal_id' => $event_joural_new_and_last_ids[$item['lamp_event_journal_id']]
                        );

                        $count_all++;
                    }

                }
                unset($data_from_portable_table);
                $insert_result = Yii::$app->db_target->createCommand()->batchInsert('event_compare_gas',
                    [
                        'event_id',
                        'date_time',
                        'static_edge_id',
                        'static_value',
                        'static_sensor_id',
                        'static_xyz',
                        'static_status_id',
                        'static_parameter_id',
                        'static_object_id',
                        'static_mine_id',
                        'static_object_title',
                        'static_object_table',
                        'lamp_sensor_id',
                        'lamp_edge_id',
                        'lamp_value',
                        'lamp_xyz',
                        'lamp_status_id',
                        'lamp_parameter_id',
                        'lamp_object_id',
                        'lamp_mine_id',
                        'lamp_object_title',
                        'lamp_object_table',
                        'static_event_journal_id',
                        'lamp_event_journal_id'
                    ], $data_to_db)->execute();

                if (!$insert_result) {
                    throw new \yii\db\Exception('Ошибка в результате массовой вставки в event_compare_gas');
                }
            }
            $warnings[] = ' Закончил event_compare_gas';
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            unset($edge_new_and_last_ids);
            unset($sensors_new_and_last_ids);
            $warnings[] = $method_name . ' Начинаю работать с event_status';
            $counter_iteration = true;
            $last_id = -1;

            $count_all = 0;
            while ($counter_iteration) {

                $data_from_portable_table = Yii::$app->db_source->createCommand('SELECT * FROM event_status where datetime > \'2020-03-11\' and id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($data_from_portable_table) < 25000) {
                    $counter_iteration = false;
                }
                foreach ($data_from_portable_table as $item) {
                    $last_id = $item['id'];
                    if (isset($event_joural_new_and_last_ids[$item['event_journal_id']])) {
                        $data_to_db[] = array(
                            'event_journal_id' => $event_joural_new_and_last_ids[$item['event_journal_id']],
                            'status_id' => $item['status_id'],
                            'datetime' => $item['datetime'],
                            'kind_reason_id' => $item['kind_reason_id']
                        );

                        $count_all++;
                    }
                }
                unset($data_from_portable_table);
                $insert_result = Yii::$app->db_target->createCommand()->batchInsert('event_status', ['event_journal_id', 'status_id', 'datetime', 'kind_reason_id'], $data_to_db)->execute();
                if (!$insert_result) {
                    throw new \yii\db\Exception(' Массовая вставка выполнилась с ошибкой');
                }
                unset($data_to_db);
            }
            $warnings[] = ' Закончил event_status';
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . 'Получаю данные из таблицы event_journal_status';
            $data_from_portable_table = Yii::$app->db_source->createCommand('SELECT * FROM event_journal_status where date_time > \'2020-03-11\'')->queryAll();
            foreach ($data_from_portable_table as $item) {
                if (isset($event_joural_new_and_last_ids[$item['event_journal_id']])) {

                    $data_to_db[] = array(
                        'event_journal_id' => $event_joural_new_and_last_ids[$item['event_journal_id']],
                        'date_time' => $item['date_time'],
                        'worker_id' => $item['worker_id'],
                        'kind_reason_id' => $item['kind_reason_id'],
                        'description' => $item['description'],
                        'check_done_date_time' => $item['check_done_date_time'],
                        'check_ignore_date_time' => $item['check_ignore_date_time'],
                        'check_done_status' => $item['check_done_status'],
                        'check_ignore_status' => $item['check_ignore_status'],
                    );
                }
            }
            unset($data_from_portable_table);
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('event_journal_status', ['event_journal_id', 'date_time', 'worker_id', 'kind_reason_id', 'description', 'check_done_date_time', 'check_ignore_date_time', 'check_done_status', 'check_ignore_status'], $data_to_db)->execute();
            if (!$count_all) {
                throw new \yii\db\Exception(' Массовая вставка выполнилась с ошибкой');
            }
            unset($data_to_db);
            $warnings[] = ' Закончил event_journal_status';
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $situation_joural_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM situation_journal_new_ids')->queryAll();
            foreach ($situation_joural_new_ids as $new_id) {
                $situation_joural_new_and_last_ids[$new_id['situation_journal_last_id']] = $new_id['situation_journal_new_id'];
            }
            unset($situation_joural_new_ids);
            $warnings[] = $method_name . 'Получаю данные из таблицы event_journal_status';
            $data_from_portable_table = Yii::$app->db_source->createCommand('SELECT * FROM event_journal_situation_journal')->queryAll();
            foreach ($data_from_portable_table as $item) {
                if (isset($event_joural_new_and_last_ids[$item['event_journal_id']]) and isset($situation_joural_new_and_last_ids[$item['situation_journal_id']])) {
                    $data_to_db[] = array(
                        'situation_journal_id' => $situation_joural_new_and_last_ids[$item['situation_journal_id']],
                        'event_journal_id' => $event_joural_new_and_last_ids[$item['event_journal_id']],
                    );
                }
            }
            unset($data_from_portable_table);
            $count_all = Yii::$app->db_target->createCommand()->batchInsert('event_journal_situation_journal', ['situation_journal_id', 'event_journal_id'], $data_to_db)->execute();
            if (!$count_all) {
                throw new \yii\db\Exception(' Массовая вставка выполнилась с ошибкой');
            }
            unset($data_to_db);
            $warnings[] = ' Закончил event_journal_situation_journal';
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
        }

        $description = 'Конец выполнение метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */
        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;


    }
    //TransferRoutes - метод для переноса данных по маршрутам
    //важно! route_template переносить в ручную до запуска метода
    public static function TransferRoutes()
    {

        // Стартовая отладочная информация
        $method_name = 'TransferRoutes';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                             // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                           // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                               // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $edge_new_ids = array();                                                                                        //новые айдишники edge для сохранения в БД
        $edge_to_db = array();                                                                                          //edge на массовую вставку в БД
        $edge_new_and_last_ids = array();                                                                               //объект новых айдишников для edge_parameter и дргуие
        $edge_changes_new_ids = array();                                                                                //новые айдишники edge_changes для edge_changes_history
        $edge_changes_history_new_ids = array();                                                                        //новые айдишники edge_changes_history  для сохранения в БД
        $edge_changes_last_new_ids = array();                                                                           //новые айдишники edge_changes для сохранения в БД
        $edge_parameter_new_ids = array();                                                                              //новые айдишники edge_parameter для сохранения в БД
        $edge_parameter_last_and_new_ids = array();                                                                     //объект новых айдишников для edge_parameter_handbook_value
        $edge_status_new_ids = array();                                                                                 //новые айдишники для сохранения в БД
        $edge_status_last_new_ids = array();                                                                            //объект новых айдишников для edge_parameter_handbook_value
        $edge_parameter_handbook_value = array();                                                                      //массив для сохранения в БД в edge_parameter_handbook_value
        $place_new_ids = array();                                                                                         //новые и старые айдишнокв place для таблицы edge
        $conjunction_last_and_new_ids = array();                                                                        //новые и старые айдишников conjunction


        //route_template
        //route_template_edge

//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта

            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description; // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            //edge
            $warnings[] = $method_name . ' Получаю edge_new_ids';

            $edge_new_ids = Yii::$app->db_target->createCommand('SELECT * FROM edge_new_ids')->queryAll();
            $count_all = 0;
            foreach ($edge_new_ids as $edge_new_id) {
                $edge_last_and_new_ids[$edge_new_id['edge_last_id']] = $edge_new_id['edge_new_id'];
            }

            unset($edge_new_ids);

            $warnings[] = $method_name . ' Получаю route_template_edge';
            $route_template_edges = Yii::$app->db_source->createCommand('SELECT * FROM route_template_edge')->queryAll();

            $count_all = 0;
            $data_to_db = null;
            foreach ($route_template_edges as $each) {

                if ($edge_last_and_new_ids[$each['edge_id']]) {
                    $count_all++;
                    $data_to_db[] = array('route_template_id' => $each['route_template_id'], 'edge_id' => $edge_last_and_new_ids[$each['edge_id']]);
                }

            }

            /** Отладка */
            $description = ' Подготовил массив для масовой вставке в route_template_edge';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            $count_all = 0;
            $warnings[] = $method_name . ' начинаю массовую вставку в route_template_edge';
            $result_insert_data_to_db = Yii::$app->db_target->createCommand()->batchInsert('route_template_edge',
                [
                    'route_template_id',
                    'edge_id',
                ], $data_to_db)->execute();
            $count_all = $result_insert_data_to_db;
            if (!$result_insert_data_to_db) {
                throw new Exception($method_name . '. Не удалось сохранить новый sensor в таблице route_template_edge');
            }
            unset($edge_new_ids);

            /** Отладка */
            $description = ' Закончил с route_template_edge';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        return $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        /*
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->data = $result_main;
        */
    }

    /**
     * actionCompareTableId() - метод сравнения ключей таблиц
     * dif_array            - содержимое записей не совпадает
     *     []
     *      source              - запись в источнике
     *          {}
     *      target              - запись в назначении
     *          {}
     * target_errors        - массив нет ключей в источнике
     * source_errors        - массив нет ключей в назначении
     * пример: 127.0.0.1/admin/serviceamicum/migration-db/compare-table-id?table_name=checking
     *
     */
    public function actionCompareTableId()
    {

        // Стартовая отладочная информация
        $method_name = 'actionCompareTableId';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $count_break_all = 0;                                                                                           // количество не совпадающих записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = array();                                                                                                 // результирующий массив (если требуется)
        $source_errors = array();                                                                                       // нет айди в назначении
        $target_errors = array();                                                                                       // нет айди в источнике
        $dif_array = array();                                                                                           // различные записи
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $post = Assistant::GetServerMethod();
            $table_name = $post['table_name'];

            $warnings[] = $method_name . '. Получаю данные из таблицы ' . $table_name;
            $query = '`' . $table_name . '`';

            $target_ids = Yii::$app->db_target->createCommand('SELECT * FROM ' . $query)->queryAll();
            foreach ($target_ids as $target_id) {
                $targets_obj[$target_id['id']] = $target_id;
            }

            $source_ids = Yii::$app->db_source->createCommand('SELECT * FROM ' . $query)->queryAll();
            foreach ($source_ids as $source_id) {
                $source_obj[$source_id['id']] = $source_id;
            }

            $sql_query_source_column = "SHOW COLUMNS FROM $query"; //определяем столбцы исходной таблицы
            $source_table_columns = Yii::$app->db_source->createCommand($sql_query_source_column)->queryAll();
            $source_column = array();
            foreach ($source_table_columns as $source_table_column) {
                $source_column[] = $source_table_column['Field'];
            }

            foreach ($source_ids as $source_id) {
                if (!isset($targets_obj[$source_id['id']])) {
//                    $errors[] = $method_name . '.  нет такого ключа в назначении ' . $source_id['id'];
                    $target_errors[] = $source_id['id'];
                } else {
                    $flag = 0;
                    foreach ($source_column as $column) {
                        if ($targets_obj[$source_id['id']][$column] != $source_id[$column]) {
                            $flag = 1;
                        }
                    }
                    if ($flag) {
                        $count_break_all++;
//                        $warnings[] = $method_name . '. Записи за одним айди не одинаковые';
//                        $warnings[] = $method_name . ". Источник: ";
//                        $warnings[] = $source_id;

//                        $warnings[] = $method_name . ". Назначение";
//                        $warnings[] = $targets_obj[$source_id['id']];

                        $dif_array[] = array("source" => $source_id, "target" => $targets_obj[$source_id['id']]);
                    }
                }
                $count_all++;
            }

            foreach ($target_ids as $target_id) {
                if (!isset($source_obj[$target_id['id']])) {
//                    $errors[] = $method_name . '.  нет такого ключа в источнике ' . $target_id['id'];
                    $source_errors[] = $target_id['id'];
                }
            }

            $result['source_errors'] = $source_errors;
            $result['target_errors'] = $target_errors;
            $result['dif_array'] = $dif_array;

            $warnings[] = $method_name . '. Количество всех записей в источнике ' . $count_all;
            $warnings[] = $method_name . '. Количество Разных записей в источнике ' . $count_break_all;

            /** Отладка */
            $description = ' Закончил с edge_new_ids';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /**МЕТОД НЕ ИСПОЛЬЗУЕТСЯ!!!!
     * actionToComplementTablesOld - метод для дополнения таблиц нехватающими данными
     * Описание:
     * Метод может дополнить конесный БД (target) недостающими данными из исходный БД (source).
     * Можно укказать массив таблиц в самом методе чтобы синхронизировать несколько таблиц сразу или же указать сразу при вызове одну таблицу.
     * Предназначалось для репликации, но можно использовать чтобы синхронизивать таблицы из двух разных БД
     * Примеры вызова:
     * пример вызова для того чтобы запустить метод с массив таблиц: /admin/serviceamicum/migration-db/to-complement-tables-old?wholly&table_name
     * пример вызова для того чтобы начать синхронизировать с последного айдишника из конечный БД с массив таблиц: /admin/serviceamicum/migration-db/to-complement-tables-old?wholly=0table_name
     * пример вызова для того чтобы начать синхронизировать с последного айдишника из конечный БД по одному таблицу: /admin/serviceamicum/migration-db/to-complement-tables-old?wholly=1&table_name=name_table
     * пример вызова для того чтобы начать синхронизировались все таблицы из массива: /admin/serviceamicum/migration-db/to-complement-tables-old?wholly&table_name
     * пример вызова для того чтобы начать синхронизировать всю таблицу: /admin/serviceamicum/migration-db/to-complement-tables-old?wholly&table_name=name_table
     *
     */
    public function actionToComplementTablesOld($wholly = '', $table_name = '')
    {
        $name_method = 'actionToComplementTablesOld ';
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

//        ini_set('max_execution_time', 6000);

        try {
            $warnings[] = $name_method . 'Начало';

            if ($table_name == '') {

                $list_tables = array(
                    'order',
                    'order_status',
                    'order_instruction_pb',
                    'order_itr_department',
                    'order_place',
                    'order_operation',
                    'operation_worker',
                    'order_operation_worker_status',
                    'order_operation_img',
                    'order_operation_attachment',
                    'order_relation',
                    'order_relation_status',
                    'order_route_worker',
                    'order_shift_fact',
                    'order_template_instruction_pb',
                    'order_template_operation',
                    'order_template_place',
                    'order_worker_coordinate',
                    'order_worker_vgk',
                    'order_vtb_ab',
                    'order_place_vtb_ab',
                    'order_operation_place_vtb_ab',
                    'order_operation_place_status_vtb_ab',
                    'order_place_vtb_ab_reason',
                    'place_route',);
                //$list_tables = array('sensor','parameter','sensor_parameter');


            } else {
                $list_tables = array($table_name);
            }

            $difference_count_all = 0;

            foreach ($list_tables as $table) {
                $query = '`' . $table . '`';

                $iteration = true;
                $flag_check_last_id = true;
                $new_iteration = true;
                $id_last_iteration = 0;
                $difference_count = 0;
                //воизбежании больших количество запросов в БД берем последные айди с кончный бд и с этого начнем перенос с исходной бд
                //получaем последний id из target
                $get_target_last_id = Yii::$app->db_target->createCommand("select id from $query order by id desc limit 1")->queryAll();
                if (isset($get_target_last_id[0]['id'])) {
                    $target_last_id = $get_target_last_id[0]['id'];
                    $warnings[] = "Брал последный $target_last_id c $table";
                    //проверяем есть ли данные данные больше последного id из target
                    $last_id = Yii::$app->db_source->createCommand("select id from $query where id > $target_last_id order by id asc limit 1")->queryAll();
                }
                if (isset($last_id[0]['id'])) {
                    $id = $target_last_id;
                    $warnings[] = "Нашел данные больше полученнго айди ";
                } else {
                    $warnings[] = "В $table не найден id больше $target_last_id, буду снхронизировать id > -1";
                    $new_iteration = false;
                    if ($wholly != '') {
                        throw new \Exception("В $table не найден id больше $target_last_id. Так как wholly = $wholly не смысла идти дальше");
                    }
                    $id = -1;

                }
                $warnings[] = "Найденные расхождение в таблице $table:";

                while ($iteration) {
                    $source_data = Yii::$app->db_source->createCommand("select * from $query where id > $id order by id asc limit 1")->queryAll();
                    if ($source_data and $id_last_iteration != $source_data[0]['id']) {
                        $source_record_id = $source_data[0]['id'];
                        $id = $source_data[0]['id'];
                        $check_exitis = Yii::$app->db_target->createCommand("select id from $query where id = $source_record_id")->queryAll();
                        if (!isset($check_exitis[0]['id'])) {
                            $warnings[] = $source_record_id;

                            $difference_count++;
                            $target_data = Yii::$app->db_target->createCommand()->insert($query, $source_data[0])->execute();
                            $difference_count_all++;
                        }
                        $warnings['$wholly = '] = $wholly;
                    } else {
                        if ($new_iteration and $wholly == '') {

                            $id = -1;
                            $id_last_iteration = $source_record_id;
                            $new_iteration = false;
                        } else {
                            $iteration = false;
                        }
                    }
                }
                unset($id);
                $warnings[] = "Косличесво расхождение в таблице $table = $difference_count";
            }

            $warnings[] = 'Общее количество найденных расхождений = ' . $difference_count_all;
            $warnings[] = $name_method . 'Конец';
        } catch (\Throwable $throwable) {
            $errors[] = $name_method . ". Исключение: ";
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
            $status = 0;
        }
        $result = array('items' => '', 'status' => $status, 'warnings' => $warnings, 'debug' => '', 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * actionComplementTables - метод для дополнения таблиц нехватающими данными
     * Описание:
     * Метод может дополнить конечный БД (target) недостающими данными из исходный БД (source).
     * в source указывается внешнее соединение, в target указывается текущая БД
     * При сломанной репликации, у нас не хватает данных на slave, но есть на master. ПОтому master - это source, а slave - это target
     * Мы берем не достающие данные с master и заталкиваем их в master, т.к. мастеров может быть несколько,
     * то надо указывать с какого мастера брать данные - потому и передаем db - в котором хранится подключение к нужной нам базе данных
     * Можно укказать массив таблиц в самом методе чтобы синхронизировать несколько таблиц сразу или же указать сразу при вызове одну таблицу.
     * Предназначалось для репликации, но можно использовать чтобы синхронизивать таблицы из двух разных БД
     * Примеры вызова:
     * пример вызова для того чтобы начать синхронизировались все таблицы из массива: http://10.36.59.8/admin/serviceamicum/migration-db/complement-tables
     * пример вызова для того чтобы начать синхронизировать таблицы указанные в самом методе: /admin/serviceamicum/migration-db/complement-tables?tables=[]&db=db_amicum_log
     */
    public function actionComplementTables()
    {
        $name_method = 'actionComplementTables ';
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

//        ini_set('max_execution_time', 6000);

        try {
            $warnings[] = $name_method . 'Начало';
            $post = Assistant::GetServerMethod();
            if (!isset($post['db'])) {
                $errors[] = $post;
                throw new Exception('Ошибка получения входных данных ');
            }

            if (!isset($post['tables'])) {
                $tables = array('sensor', 'sensor_parameter');
                //amicum3.sensor_parameter_handbook_value,amicum3.sensor_parameter_value,amicum3.sensor_parameter,amicum3.sensor_parameter_sensor
                //  $tables = array('event_journal', 'event_status');
                //  $tables = array('event_journal', 'event_status');
                /*$tables = array(
                     'conjunction', 'conjunction_function', 'conjunction_parameter', 'conjunction_parameter_handbook_value', 'conjunction_parameter_sensor', 'conjunction_parameter_value',
                     'place_type', 'place', 'place_company_department', 'place_function', 'place_operation', 'place_operation_value', 'place_parameter', 'place_parameter_handbook_value', 'place_parameter_sensor', 'place_parameter_value',
                     'edge_type', 'edge', 'edge_status', 'edge_changes', 'edge_changes_history', 'edge_function', 'edge_parameter', 'edge_parameter_handbook_value', 'edge_parameter_value'
                 );*/
            }

            $tables = json_decode($post['tables']);
            $db = $post['db'];

            $difference_count_all = 0;
            foreach ($tables as $table) {

                if ($table == 'sensor_parameter_value' or $table == 'worker_parameter_value' or $table == 'equipment_parameter_value') {
                    $response = $this->MergerBigTables($table, $db);
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $status[] = $response['status'];
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка при переноса данных в таблице ' . $table);
                    }

                }

                $count_final_lacks_ids = 0;
                $count_inserted_datas = 0;
                $exit = true;
                $source_start_id = -1;
                $count = 0;
                $table = '`' . $table . '`';

                $warnings[] = "Айдишники расхожденных данных таблицы $table:";

                $sources = Yii::$app->$db->createCommand("SELECT * FROM $table")->queryAll();
                $target_ids = Yii::$app->db_target->createCommand("SELECT id FROM $table")->queryAll();
                foreach ($target_ids as $target_id) {
                    $targets_obj[$target_id['id']] = $target_id['id'];
                }
                unset($target_ids);

                $sql_query_source_column = "SHOW COLUMNS FROM $table"; //определяем столбцы исходной таблицы
                $source_table_columns = Yii::$app->db_source->createCommand($sql_query_source_column)->queryAll();
                $source_column = array();
                foreach ($source_table_columns as $source_table_column) {
                    $source_column[] = $source_table_column['Field'];
                }

                $count_to_insert = 0;
                $target_table_count = 0;
                foreach ($sources as $source) {
                    if (!isset($targets_obj[$source['id']])) {
                        $difference_count_all++;
                        $count_final_lacks_ids++;
                        $source_row_array[] = $source;
                        $count_to_insert++;
                    }

                    if ($count_to_insert == 5000) {
                        $target_table_count = Yii::$app->db_target->createCommand()->batchInsert($table, $source_column, $source_row_array)->execute();
                        $warnings[] = $name_method . " Количество вставленных записей: " . $target_table_count;
                        $count_to_insert = 0;
                        unset($source_row_array);
                    }
                }

                if (isset($source_row_array)) {
                    $target_table_count = Yii::$app->db_target->createCommand()->batchInsert($table, $source_column, $source_row_array)->execute();
                    $warnings[] = $name_method . " Количество вставленных записей: " . $target_table_count;
                    unset($source_row_array);
                }
                unset($targets_obj);

            }
            $warnings[] = 'Общее количество найденных расхождений = ' . $difference_count_all;


            $warnings[] = $name_method . 'Конец';
        } catch (\Throwable $throwable) {
            $errors[] = $name_method . ". Исключение: ";
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
            $status = 0;
        }
        $result = array('items' => '', 'status' => $status, 'warnings' => $warnings, 'debug' => '', 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //метод получения список таблиц по конкретной БД
    //пример вызова: /admin/serviceamicum/migration-db/get-list-tables-amicum
    public function actionGetListTablesAmicum()
    {
        $name_method = 'actionGetListTablesAmicum ';
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $tables = array();


        try {
            $warnings[] = $name_method . 'Начало';
            $lists = Yii::$app->db->createCommand('show tables')->queryAll();
            /**
             * перебираем список, чтобы составить одноуровневый массив
             * добавляем в массив тольок таблицы без представлений
             */
            $db_name = Assistant::getDsnAttribute('dbname', Yii::$app->getDb()->dsn);
            foreach ($lists as $table) {
                if (strpos($table['Tables_in_' . $db_name], "view") === false) {
                    $tables[] = $table['Tables_in_' . $db_name];
                }

            }
        } catch (\Throwable $throwable) {
            $errors[] = $name_method . ". Исключение: ";
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
            $status = 0;
        }
        // return json_encode(array('items' => $list, 'status' => $status, 'warnings' => $warnings, 'debug' => '', 'errors' => $errors));
        $result = array('Items' => $tables, 'status' => $status, 'warnings' => $warnings, 'debug' => '', 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //пример вызова: /admin/serviceamicum/migration-db/upsert-tables?tables=['sensor']
    public function actionUpsertTables()
    {
        $name_method = 'actionUpsertTables ';
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

//        ini_set('max_execution_time', 6000);

        try {

            $warnings[] = $name_method . 'Начало';

            $post = Assistant::GetServerMethod();
            if (isset($post['tables'])) {
                $tables = json_decode($post['tables']);

            } else {
                $warnings[] = "Входные параметры не пререданы, беру таблицы по умолчанию";
                $tables = array('sensor_parameter_handbook_value', 'sensor_parameter_sensor');
                //amicum3.sensor_parameter_handbook_value,amicum3.sensor_parameter_value,amicum3.sensor_parameter,amicum3.sensor_parameter_sensor
                //  $tables = array('event_journal', 'event_status');
                //  $tables = array('event_journal', 'event_status');
                /*$tables = array(
                     'conjunction', 'conjunction_function', 'conjunction_parameter', 'conjunction_parameter_handbook_value', 'conjunction_parameter_sensor', 'conjunction_parameter_value',
                     'place_type', 'place', 'place_company_department', 'place_function', 'place_operation', 'place_operation_value', 'place_parameter', 'place_parameter_handbook_value', 'place_parameter_sensor', 'place_parameter_value',
                     'edge_type', 'edge', 'edge_status', 'edge_changes', 'edge_changes_history', 'edge_function', 'edge_parameter', 'edge_parameter_handbook_value', 'edge_parameter_value'
                 );*/
            }
            $difference_count_all = 0;
            foreach ($tables as $table) {
                $count_final_lacks_ids = 0;
                $count_inserted_datas = 0;
                $exit = true;
                $source_strat_id = -1;
                $target_strat_id = -1;
                $count = 0;
                $table = '`' . $table . '`';
                $warnings[] = "Айдишники расхожденных данных таблицы $table:";

                while ($exit) {

                    $array_lacks_ids = array();
                    $source_ids = Yii::$app->db_source->createCommand("SELECT id FROM $table where id > $source_strat_id order by id asc limit 25000")->queryAll();

                    $target_ids = Yii::$app->db_target->createCommand("SELECT id FROM $table where id > $source_strat_id order by id asc limit 25000")->queryAll();

                    foreach ($source_ids as $source_id) {
                        $source_strat_id = $source_id['id'];
                        $source_obj[$source_id['id']] = $source_id['id'];
                    }
                    if (count($source_ids) < 25000) {
                        $exit = false;
                    }
                    foreach ($target_ids as $target_id) {
                        $target_strat_id = $target_id['id'];
                        $targets_obj[$target_id['id']] = $target_id['id'];
                    }
                    foreach ($source_ids as $source_id) {
                        if (!isset($targets_obj[$source_id['id']])) {
                            $difference_count_all++;
                            $count_final_lacks_ids++;
                            $warnings[] = $source_id['id'];
                            $array_lacks_ids[] = $source_id['id'];

                        }
                    }
                    unset($targets_obj);
                    unset($source_obj);
//                    $warnings[] = $array_lacks_ids;
//                    throw new \Exception("debug stop ");
                    if (count($array_lacks_ids) != 0) {
                        $data_from_db_source_to_db_target = Yii::$app->db_source->createCommand("select * from $table where id in (" . implode(',', $array_lacks_ids) . ")")->queryAll();
                        $table_columns = Yii::$app->db->createCommand('show columns from ' . $table)->queryAll();
                        $columns = null;
                        foreach ($table_columns as $coumn) {
                            $columns[] = $coumn['Field'];
                        }
                        $columns = implode(', ', $columns);
                        $result_insert_to_db = Yii::$app->db_target->createCommand()->upsert($table, $columns, true, $data_from_db_source_to_db_target)->execute();

                        unset($array_lacks_ids);

                    }
                }

                $warnings[] = "Количество нехватающих id в конечном БД в таблице $table = $count_final_lacks_ids";
                $warnings[] = "Количество вставленных записей в таблице $table = $count_inserted_datas";

                unset($count_inserted_datas);
                unset($count_final_lacks_ids);


            }
            $warnings[] = 'Общее количество найденных расхождений = ' . $difference_count_all;
            $warnings[] = $name_method . 'Конец';
        } catch (\Throwable $throwable) {
            $errors[] = $name_method . ". Исключение: ";
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
            $status = 0;
        }
        $result = array('items' => '', 'status' => $status, 'warnings' => $warnings, 'debug' => '', 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;

    }


    //MergerBigTables - метод для репликации больших таблиц
    // $table   - таблица для объединения
    // $db      - база данных для получения исходных данных - source
    private function MergerBigTables($table, $db)
    {
        $name_method = 'actionMergerBigTables ';
        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $difference_count_all = 0;                                                                                      // количество различных записей в БД

//        ini_set('max_execution_time', 100000);

        try {
            $warnings[] = $name_method . 'Начало';

            if ($table != '') {
                $table_address = "'" . $table . "'";
            } else {
                $warnings[] = "Входные параметры не пререданы, беру таблицы по умолчанию";
                $table_address = 'sensor_parameter_value';
            }
            $insert_difference_count_all = 0;
            $count_final_lacks_ids = 0;
            $count_inserted_datas = 0;
            $exit = true;
            $count = 0;
            $table = '`' . $table . '`';
            $last_id = Yii::$app->db_amicum_log->createCommand("SELECT * FROM last_date_replicate where table_address like $table_address order by id desc limit 1")->queryAll();
            $source_strat_id = $last_id[0]['id'];

            $warnings[] = "Айдишники расхожденных данных таблицы $table:";

            while ($exit) {

                $array_lacks_ids = array();
                $source_ids = Yii::$app->$db->createCommand("SELECT id FROM $table where id > $source_strat_id order by id asc limit 100000")->queryAll();

                $target_ids = Yii::$app->db_target->createCommand("SELECT id FROM $table where id > $source_strat_id order by id asc limit 100000")->queryAll();

                foreach ($target_ids as $target_id) {

                    $targets_obj[$target_id['id']] = $target_id['id'];
                }
                foreach ($source_ids as $source_id) {
                    $source_strat_id = $source_id['id'];
                    $source_obj[$source_id['id']] = $source_id['id'];
                    if (!isset($targets_obj[$source_id['id']])) {
                        $difference_count_all++;
                        $count_final_lacks_ids++;
                        $warnings[] = $source_id['id'];
                        $array_lacks_ids[] = $source_id['id'];
                    }
                }
                if (count($source_ids) < 100000) {
                    $exit = false;
                }

                unset($source_obj);
                unset($target_ids);
                unset($source_ids);
//                    $warnings[] = $array_lacks_ids;
//                    throw new \Exception("debug stop ");
                if (count($array_lacks_ids) != 0) {
                    $data_from_db_source_to_db_target = Yii::$app->$db->createCommand("select * from $table where id in (" . implode(',', $array_lacks_ids) . ")")->queryAll();
                    $table_columns = Yii::$app->db->createCommand('show columns from ' . $table)->queryAll();
                    $columns = null;
                    foreach ($table_columns as $coumn) {
                        $columns[] = $coumn['Field'];
                    }
                    $columns = implode(', ', $columns);
                    $result_insert = Yii::$app->db_target->createCommand()->batchInsert($table, [$columns], $data_from_db_source_to_db_target)->execute();

                    if (!$result_insert) {
                        throw new Exception('Не удалось вставить данные в таблицу ' . $table);
                    }
                    $insert_difference_count_all += $result_insert;
                    unset($array_lacks_ids);
                    unset($data_from_db_source_to_db_target);
                    unset($result_insert);

                }
            }

            $warnings[] = "Количество нехватающих id в конечном БД в таблице $table = $count_final_lacks_ids";
            $warnings[] = "Количество вставленных записей в таблице $table = $count_inserted_datas";

            unset($count_inserted_datas);
            unset($count_final_lacks_ids);


            $warnings[] = 'Общее количество найденных расхождений = ' . $difference_count_all;
            $warnings[] = "Общее количество вставленных данных в  $table = " . $insert_difference_count_all;
            $warnings[] = $name_method . 'Конец';
        } catch (\Throwable $throwable) {
            $errors[] = $name_method . ". Исключение: ";
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();
            $status = 0;
        }
        return array('Items' => '', 'status' => $status, 'warnings' => $warnings, 'debug' => '', 'errors' => $errors);

    }

    /**
     *
     * actionMigrationDbSensor - метод переноса сенсоров из одной БД в другую
     * пример запуска: 127.0.0.1/admin/serviceamicum/migration-db/migration-db-sensor
     */
    public static function actionMigrationDbSensor()
    {

        // Стартовая отладочная информация
        $method_name = 'actionMigrationDbSensor';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_sensor = 0;                                                                                      // количество созданных сенсоров
        $count_all_sensor = 0;                                                                                      // количество сенсоров обработанных
        $count_new_sensor_parameter = 0;                                                                            // количество созданных параметров сенсоров
        $count_all_sensor_parameter = 0;                                                                            // количество параметров сенсоров обработанных
        $count_new_sensor_parameter_value = 0;                                                                      // количество созданных значений параметров сенсоров
        $count_all_sensor_parameter_value = 0;                                                                      // количество значений параметров сенсоров обработанных
        $count_insert_sensor_parameter_value = 0;                                                                   // количество вставленных значений параметров сенсоров обработанных

//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $mine_id = 250;

            // 1 получить список сенсоров из источника

            $sensors_source = Yii::$app->db_source->createCommand("select * from sensor")->queryAll();

            if (!$sensors_source) {
                throw new Exception($method_name . ' Не удалось получить данные с источника Тбалица SENSOR ');
            }

            $sensors_target = Yii::$app->db_target->createCommand("select * from sensor")->queryAll();

            if (!$sensors_target) {
                throw new Exception($method_name . ' Не удалось получить данные с источника Тбалица SENSOR ');
            }

            if ($sensors_target) {
                foreach ($sensors_target as $sensor_target) {
                    $sensor_target_hand[trim($sensor_target['title'])] = $sensor_target;
                }
            }


            // создаем справочник параметров сенсора источника
            $sensor_parameters_source = Yii::$app->db_source->createCommand("select * from sensor_parameter")->queryAll();
            if ($sensor_parameters_source) {
                foreach ($sensor_parameters_source as $sensor_parameter_source) {
                    $sensor_parameters_source_hand[$sensor_parameter_source['sensor_id']][$sensor_parameter_source['parameter_id']][$sensor_parameter_source['parameter_type_id']] = $sensor_parameter_source;
                }
            }

            // создаем справочник параметров сенсора назначения
            $sensor_parameters_target = Yii::$app->db_target->createCommand("select * from sensor_parameter")->queryAll();
            if ($sensor_parameters_target) {
                foreach ($sensor_parameters_target as $sensor_parameter_target) {
                    $sensor_parameter_target_hand[$sensor_parameter_target['sensor_id']][$sensor_parameter_target['parameter_id']][$sensor_parameter_target['parameter_type_id']] = $sensor_parameter_target;
                }
            }

            // создаем справочник значений параметров сенсора из источника
            $sensor_parameter_handbook_values_source = Yii::$app->db_source->createCommand("select * from sensor_parameter_handbook_value")->queryAll();
            if ($sensor_parameter_handbook_values_source) {
                foreach ($sensor_parameter_handbook_values_source as $sensor_parameter_handbook_value_source) {
                    $sensor_parameter_handbook_values_source_hand[$sensor_parameter_handbook_value_source['sensor_parameter_id']][$sensor_parameter_handbook_value_source['date_time']] = $sensor_parameter_handbook_value_source;
                }
            }

            // создаем справочник значений параметров сенсора из назначения
            $sensor_parameter_handbook_values_target = Yii::$app->db_target->createCommand("select * from sensor_parameter_handbook_value")->queryAll();
            if ($sensor_parameter_handbook_values_target) {
                foreach ($sensor_parameter_handbook_values_target as $sensor_parameter_handbook_value_target) {
                    $sensor_parameter_handbook_values_target_hand[$sensor_parameter_handbook_value_target['sensor_parameter_id']][$sensor_parameter_handbook_value_target['date_time']] = $sensor_parameter_handbook_value_target;
                }
            }

            // создаем справочник мест из источника
            $places_source = Yii::$app->db_source->createCommand("select * from place")->queryAll();
            if ($places_source) {
                foreach ($places_source as $place_source) {
                    $places_source_hand[$place_source['id']] = $place_source;
                }
            }

            // создаем справочник мест из назначения
            $places_target = Yii::$app->db_target->createCommand("select * from place WHERE mine_id=" . $mine_id)->queryAll();
            if ($places_target) {
                foreach ($places_target as $place_target) {
                    $places_target_hand[trim($place_target['title'])] = $place_target;
                }
            }

            // сделать справочник старыйх эджей с конженкшенами
            $edges_source = Yii::$app->db_source->createCommand("select edge_id, xStart, yStart, zStart, xEnd, yEnd, zEnd from view_initEdgeScheme")->queryAll();
            if ($edges_source) {
                foreach ($edges_source as $edge_source) {
                    $edges_source_hand[$edge_source['edge_id']] = $edge_source;
                }
            }

            // получить справочник конжанкшенов с координатами
            $conjunctions_target = Yii::$app->db_target->createCommand("select * from conjunction")->queryAll();
            if ($conjunctions_target) {
                foreach ($conjunctions_target as $conjunction_target) {
                    $conjunctions_target_hand[trim($conjunction_target['x'])][trim($conjunction_target['y'])][trim($conjunction_target['z'])] = $conjunction_target['id'];
                }
            }

            // получить справочник эджей с конжанкшенами в назначении
            $edges_target = Yii::$app->db_target->createCommand("select * from edge")->queryAll();
            if ($edges_target) {
                foreach ($edges_target as $edge_target) {
                    $edges_target_hand[$edge_target['conjunction_start_id']][$edge_target['conjunction_end_id']] = $edge_target['id'];
                }
            }


            // проверить каждый сенсор на существование в назначении по title, если его нет, то создать
            foreach ($sensors_source as $sensor_source) {
                $sensor_id_source = $sensor_source['id'];

//                $sensor_target = Sensor::findOne(['title' => $sensor_source['title']]);
                if (!isset($sensor_target_hand[trim($sensor_source['title'])])) {
                    $main = new Main();
                    $main->table_address = "sensor";
                    $main->db_address = "amicum3";
                    if ($main->save()) {
                        $main_id = $main->id;
//                        $warnings[] = "addMain. Главный ключ сохранен и равен $main_id";
                    } else {
                        $errors[] = 'addMain. Ошибка сохранения модели Main';
                        $errors[] = $main->errors;
                        throw new \Exception('addMain. Ошибка создания главного ключа');
                    }
                    $sensor_target = new Sensor();
                    $sensor_target->id = $main_id;
                    $sensor_target->title = $sensor_source['title'];
                    $sensor_target->asmtp_id = $sensor_source['asmtp_id'];
                    $sensor_target->object_id = $sensor_source['object_id'];
                    $sensor_target->sensor_type_id = $sensor_source['sensor_type_id'];
                    if (!$sensor_target->save()) {
                        $errors[] = $sensor_source;
                        $errors[] = $sensor_target->errors;
                        throw new Exception($method_name . ' Не удалось сохранить модель SENSOR');
                    }
                    $count_new_sensor++;
                    $sensor_id_target = $sensor_target->id;
                } else {
                    $sensor_id_target = $sensor_target_hand[trim($sensor_source['title'])]['id'];
                }
                $count_all_sensor++;


                // проверить наличие параметров в назначении. Получаем параметры сенсора в назначении
                if (isset($sensor_parameters_source_hand[$sensor_id_source])) {
                    foreach ($sensor_parameters_source_hand[$sensor_id_source] as $parameter_ids) {
                        foreach ($parameter_ids as $parameter_source) {
                            $parameter_id_source = $parameter_source['parameter_id'];
                            $parameter_type_id_source = $parameter_source['parameter_type_id'];
                            $sensor_parameter_id_source = $parameter_source['id'];
//                            $sensor_parameters_target = SensorParameter::findOne(['sensor_id' => $sensor_id_target, 'parameter_id' => $parameter_id_source, 'parameter_type_id' => $parameter_type_id_source]);
                            if (!isset($sensor_parameter_target_hand[$sensor_id_target][$parameter_id_source][$parameter_type_id_source])) {
                                $sensor_parameters_target = new SensorParameter();
                                $sensor_parameters_target->parameter_id = $parameter_id_source;
                                $sensor_parameters_target->parameter_id = $parameter_id_source;
                                $sensor_parameters_target->parameter_type_id = $parameter_type_id_source;
                                $sensor_parameters_target->sensor_id = $sensor_id_target;

                                if (!$sensor_parameters_target->save()) {
                                    $errors[] = $parameter_source;
                                    $errors[] = $sensor_id_target;
                                    $errors[] = $sensor_parameters_target->errors;
                                    throw new Exception($method_name . ' Не удалось сохранить модель SensorParameter');
                                }
                                $count_new_sensor_parameter++;
                                $sensor_parameters_target->refresh();
                                $sensor_parameter_id_target = $sensor_parameters_target->id;
                                $sensor_parameter_target_hand[$sensor_id_target][$parameter_id_source][$parameter_type_id_source] = array(
                                    'id' => $sensor_parameter_id_target,
                                    'parameter_id' => $parameter_id_source,
                                    'parameter_type_id' => $parameter_type_id_source,
                                    'sensor_id' => $sensor_id_target
                                );
                            } else {
                                $sensor_parameter_id_target = $sensor_parameter_target_hand[$sensor_id_target][$parameter_id_source][$parameter_type_id_source]['id'];
                            }
                            $count_all_sensor_parameter++;


                            // переносим значение параметра сенсора справочного
                            if (isset($sensor_parameter_handbook_values_source_hand[$sensor_parameter_id_source])) {
                                foreach ($sensor_parameter_handbook_values_source_hand[$sensor_parameter_id_source] as $value_source) {
                                    $date_time_source = $value_source['date_time'];
                                    $status_id = $value_source['status_id'];
                                    $value_result = $value_source['value'];
                                    $flag_save = 1;

                                    if (!isset($sensor_parameter_handbook_values_target_hand[$sensor_parameter_id_target][$date_time_source])) {

                                        // перенести параметры места и выработки навые айдишники
                                        // если параметр 122 то найти его в старой базе и сопоставить с новой базой
                                        if ($parameter_id_source == 122) {
                                            $place_id_source = $value_source['value'];
                                            $value_result = -1;
                                            if (isset($places_source_hand[$place_id_source])) {
                                                $place_title_source = trim($places_source_hand[$place_id_source]['title']);
                                                if (isset($places_target_hand[$place_title_source])) {
                                                    $place_id_target = $places_target_hand[$place_title_source]['id'];
                                                } else {
                                                    $place_id_target = -1;
                                                }
                                                $value_result = $place_id_target;
                                            }
                                        }

                                        // перенос edge_id сенсора
                                        if ($parameter_id_source == 269) {
                                            $edge_id_last = $value_source['value'];
                                            // получить старый эдж вместе с координатами
                                            if (!isset($edges_source_hand[$edge_id_last])) {
                                                $value_result = -1;
                                            } else {
                                                // найти у старого эджа координаты для первого конжанкшена
                                                $xStart = trim($edges_source_hand[$edge_id_last]['xStart']);
                                                $yStart = trim($edges_source_hand[$edge_id_last]['yStart']);
                                                $zStart = trim($edges_source_hand[$edge_id_last]['zStart']);

                                                // найти у старого эджа координаты для второго конжанкшена
                                                $xEnd = trim($edges_source_hand[$edge_id_last]['xEnd']);
                                                $yEnd = trim($edges_source_hand[$edge_id_last]['yEnd']);
                                                $zEnd = trim($edges_source_hand[$edge_id_last]['zEnd']);


                                                if (isset($conjunctions_target_hand[$xStart][$yStart][$zStart]) and isset($conjunctions_target_hand[$xEnd][$yEnd][$zEnd])) {
                                                    // найти по конжанкшенами эдж вариант 1/2 2/1
                                                    $conj_start_target = $conjunctions_target_hand[$xStart][$yStart][$zStart];
                                                    $conj_end_target = $conjunctions_target_hand[$xEnd][$yEnd][$zEnd];

                                                    if (isset($edges_target_hand[$conj_start_target][$conj_end_target]) or isset($edges_target_hand[$conj_end_target][$conj_start_target])) {
                                                        // если нашел, то брать едж одного из них
                                                        if (isset($edges_target_hand[$conj_start_target][$conj_end_target])) {
                                                            $value_result = $edges_target_hand[$conj_start_target][$conj_end_target];
                                                        }
                                                        if (isset($edges_target_hand[$conj_end_target][$conj_start_target])) {
                                                            $value_result = $edges_target_hand[$conj_end_target][$conj_start_target];
                                                        }
                                                    } else {
                                                        // если такого нет, то -1
                                                        $value_result = -1;
                                                    }
                                                } else {
                                                    // если такого нет, то -1
                                                    $value_result = -1;
                                                }
                                            }
                                        }

                                        if ($date_time_source) {
                                            $data_sensor_parameter_values[] = array(
                                                'sensor_parameter_id' => $sensor_parameter_id_target,
                                                'date_time' => $date_time_source,
                                                'value' => $value_result,
                                                'status_id' => $status_id
                                            );

                                            $count_new_sensor_parameter_value++;
                                        }

                                        $count_all_sensor_parameter_value++;
                                    }
                                }
                            }
                            unset($parameter_source);
                        }
                    }
                }
            }

            if (isset($data_sensor_parameter_values)) {
                $count_insert_sensor_parameter_value = Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_handbook_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $data_sensor_parameter_values)->execute();
                if (!$count_insert_sensor_parameter_value) {
                    throw new Exception($method_name . ' Ошибка массовой вставки параметров в таблицу sensor_parameter_handbook_value');
                }
            }


            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,
        $warnings['количество созданных сенсоров'] = $count_new_sensor;                                                                                      // количество созданных сенсоров
        $warnings['количество сенсоров обработанных'] = $count_all_sensor;                                                                                      // количество сенсоров обработанных
        $warnings['количество созданных параметров сенсоров'] = $count_new_sensor_parameter;                                                                            // количество созданных параметров сенсоров
        $warnings['количество параметров сенсоров обработанных'] = $count_all_sensor_parameter;                                                                            // количество параметров сенсоров обработанных
        $warnings['количество созданных значений параметров сенсоров'] = $count_new_sensor_parameter_value;                                                                      // количество созданных значений параметров сенсоров
        $warnings['количество значений параметров сенсоров обработанных'] = $count_all_sensor_parameter_value;                                                                      // количество значений параметров сенсоров обработанных
        $warnings['количество вставленных значений параметров сенсоров обработанных'] = $count_insert_sensor_parameter_value;                                                                   // количество вставленных значений параметров сенсоров обработанных


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    /**
     * actionTransSpv - метод по переносу spw минуя родительскую таблицу sensor_parameter
     * Алгоритм:
     * . Получаем sensor из исходной БД и по title находм новый sensor_id из источника c trim
     * . Получаем sensor_parameter из исходной БД и сразу подготавлываем справочник сравнения
     * . Подготавлываем  справочник где под старой sensor_id лежит новый sensor_id
     * . Получаем sensor_parameter из источник и начинаем сравнивать
     * . Начинаем вставку в spv
     * ПРИМЕР ВЫЗОВА МЕТОДА - http://127.0.0.1/admin/serviceamicum/migration-db/trans-spv
     */
    public function actionTransSpv()
    {
        // Стартовая отладочная информация
        $method_name = 'actionTransSpv ';                                                                               // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                             // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                           // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                               // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_worker = 0;                                                                                          // количество созданных сенсоров


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $warnings[] = $method_name . '. Приступаю к sensor';

            // Получаем sensor из источник
            $sensors = Yii::$app->db_source->createCommand('SELECT * FROM sensor')->queryAll();
            foreach ($sensors as $sensor) {
                $count_all++;
                $obj_source_sensors[trim($sensor['title'])] = $sensor;
            }
            unset($sensors);
            /** Отладка */
            $description = ' Закончил перебор sensor из исходной БД';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            //получем все сенсоры из исходник и по title находм новый sensor_id из источника
            $sensors = Yii::$app->db_target->createCommand('SELECT * FROM sensor')->queryAll();
            $count_all = 0;
            foreach ($sensors as $sensor) {

                if (isset($obj_source_sensors[$sensor['title']])) {
                    $sensor_new_and_last_ids[$obj_source_sensors[trim($sensor['title'])]['id']] = $sensor['id'];
                    $count_all++;
                }
            }
            $warnings['количество найденных сенсоров на обе БД'] = count($sensor_new_and_last_ids);
            unset($sensors);

            /** Отладка */
            $description = ' Закончил перебор sensor из источника';                                                         // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // получаем sensor_parameter из источник и подготовим массив для сраванения заменяя старые sensor_id новый
            $source_sensor_parameters = Yii::$app->db_source->createCommand('SELECT * FROM sensor_parameter')->queryAll();

            $count_all = 0;
            $count = 0;
            foreach ($source_sensor_parameters as $source_sensor_parameter) {
                $count++;
                // проверяем если есть такой sensor то готовим справочник
                if (isset($sensor_new_and_last_ids[$source_sensor_parameter['sensor_id']])) {
                    $count_all++;
                    //готовим справочник из страых айдишников sensor_parameter
                    $obj_source_sensor_parameters[$sensor_new_and_last_ids[$source_sensor_parameter['sensor_id']]][$source_sensor_parameter['parameter_id']][$source_sensor_parameter['parameter_type_id']] = $source_sensor_parameter['id'];

                }
            }
            $warnings['количество записей sensor_parameter на источнике источнике'] = $count;
            $count = 0;


            unset($source_sensor_parameters);
            /** Отладка */
            $description = ' Закончил перебор sensor_parameter из источника';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            //Получаем sensor_parameter из исходной БД и сразу подготавлываем справочник сравнения
            $target_sensor_parameters = Yii::$app->db_target->createCommand('SELECT * FROM sensor_parameter')->queryAll();
            $count_all = 0;
            $count = 0;
            $count_fined_sp = 0; //количество записей которые есть на том и ином сервере
            foreach ($target_sensor_parameters as $target_sensor_parameter) {
                $count++;
                if (isset($obj_source_sensor_parameters[$target_sensor_parameter['sensor_id']][$target_sensor_parameter['parameter_id']][$target_sensor_parameter['parameter_type_id']])) {

                    $count_fined_sp++;
                    $count_all++;

                    //массив новых и старых айдишников параметров сенсоров
                    $sp_new_and_last_ids[$obj_source_sensor_parameters[$target_sensor_parameter['sensor_id']][$target_sensor_parameter['parameter_id']][$target_sensor_parameter['parameter_type_id']]] = $target_sensor_parameter['id'];
                }
            }
            $warnings['количество записей sensor_parameter в исходном сервере'] = $count;
            $warnings['количество записей которые есть на том и ином сервере'] = $count_fined_sp;
            $count = 0;

            unset($target_sensor_parameters);
            /** Отладка */
            $description = ' Закончил перебор sensor_parameter из БД назначения';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $counter_iteration = true;
            $last_id = 0;
            //throw new \Exception('ОТЛАДКА');
            while ($counter_iteration) {

                $spvs = Yii::$app->db_source->createCommand('SELECT * FROM sensor_parameter_value where id > ' . $last_id . ' limit 100000')->queryAll();
                if (count($spvs) < 100000) {
                    $counter_iteration = false;
                }
                $data_sensor_parameter_value_to_batchInsert = null;
                foreach ($spvs as $sensor_parameter_value) {

                    //проверяем если есть такой sensor_parameter_id в справочнике то добовлем его в массив для вставки в БД, иначе пропускаем этот запись
                    if (isset($sp_new_and_last_ids[$sensor_parameter_value['sensor_parameter_id']])) {
                        $data_sensor_parameter_value_to_batchInsert[] = array(
                            'sensor_parameter_id' => $sp_new_and_last_ids[$sensor_parameter_value['sensor_parameter_id']],
                            'date_time' => $sensor_parameter_value['date_time'],
                            'value' => $sensor_parameter_value['value'],
                            'status_id' => $sensor_parameter_value['status_id']
                        );
                        $count_all++;
                        $last_id = $sensor_parameter_value['id'];
                    }
                }
                unset($spvs);
                //вставка остаток записей в sensor_parameter_value
                if (!empty($data_sensor_parameter_value_to_batchInsert)) {
                    $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $data_sensor_parameter_value_to_batchInsert)->execute();

                    if (!$insert_result_to_MySQL) {
                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу sensor_parameter_value' . $insert_result_to_MySQL);
                    }
                }
                unset($data_sensor_parameter_value_to_batchInsert);
            }
            /** Отладка */
            $description = ' Закончил вставку в sensor_parameter_value';                                                         // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;              // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actonTransWpv - Метод по переносу данных с таблицы worker_parameter_value на новую БД с учетом изминения записи в таблице worker_parameter
     * Алгоритм::
     * Получаем worker_parameter из старой БД и готовим справочник
     * Получаем worker_parameter из новой БД и готовим справочник где под старой worker_parameter_id будет лежать новый worker_parameter_id
     * Получаем данные из старой БД по worker_parameter_value
     * По циклу меняем старый worker_parameter_id на новый
     * Массово вставляем данные в новой БД
     * Пример вызова: http://127.0.0.1/admin/serviceamicum/migration-db/trans-wpv
     */
    public function actionTransWpv()
    {
        // Стартовая отладочная информация
        $method_name = 'actionTransWpv ';                                                                               // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                             // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                           // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                               // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_worker = 0;                                                                                          // количество созданных сенсоров


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // Получаем worker_parameter из старой БД и готовим справочник
            $source_worker_parameters = Yii::$app->db_source->createCommand('SELECT * FROM worker_parameter')->queryAll();

            $count_all = 0;

            foreach ($source_worker_parameters as $source_worker_parameter) {
                $count_all++;
                //готовим справочник из страых айдишников worker_parameter
                $obj_source_worker_parameters[$source_worker_parameter['worker_object_id']][$source_worker_parameter['parameter_id']][$source_worker_parameter['parameter_type_id']] = $source_worker_parameter['id'];
            }
            $warnings['количество записей worker_parameter на источнике '] = $count_all;
            unset($source_worker_parameters);

            // Получаем worker_parameter из новой БД и готовим справочник где под старой worker_parameter_id будет лежать новый worker_parameter_id
            $target_worker_parameters = Yii::$app->db_target->createCommand('SELECT * FROM worker_parameter')->queryAll();

            $count_all = 0;

            $count = 0;
            foreach ($target_worker_parameters as $target_worker_parameter) {
                $count_all++;
                //если есть такие записи в новой БД
                if (isset($obj_source_worker_parameters[$target_worker_parameter['worker_object_id']][$target_worker_parameter['parameter_id']][$target_worker_parameter['parameter_type_id']])) {
                    //готовим справочник из страых айдишников worker_parameter
                    $wp_new_and_last_ids[$obj_source_worker_parameters[$target_worker_parameter['worker_object_id']][$target_worker_parameter['parameter_id']][$target_worker_parameter['parameter_type_id']]] = $target_worker_parameter['id'];
                    $count++;
                }

            }
            $warnings['общее количество записей worker_parameter на исходнике'] = $count_all;
            $warnings['общее количество обработаных записей worker_parameter на исходнике'] = $count;
            unset($target_worker_parameters);

            $warnings[] = $method_name . 'Получаю данные с worker_parameter_value';

            $counter_iteration = true;
            $last_id = -1;
            $count_isert = 0;
            while ($counter_iteration) {

                $wpvs = Yii::$app->db_source->createCommand('SELECT * FROM worker_parameter_value where id > ' . $last_id . ' limit 25000')->queryAll();
                if (count($wpvs) < 25000) {
                    $counter_iteration = false;
                }
                $data_worker_parameter_value_to_batchInsert = null;//в данной перемены будет размешена последння итерация foreach так как если данные меньше 2500 по внутренной условии данные не попадут в БД
                foreach ($wpvs as $worker_parameter_value) {
                    if (isset($wp_new_and_last_ids[$worker_parameter_value['worker_parameter_id']])) {
                        $data_worker_parameter_value_to_batchInsert[] = array(
                            'worker_parameter_id' => $wp_new_and_last_ids[$worker_parameter_value['worker_parameter_id']],
                            'date_time' => $worker_parameter_value['date_time'],
                            'value' => $worker_parameter_value['value'],
                            'status_id' => $worker_parameter_value['status_id'],
                            'shift' => $worker_parameter_value['shift'],
                            'date_work' => $worker_parameter_value['date_work']
                        );
                        $count++;
                    }

                    $count_all++;
                    $last_id = (int)$worker_parameter_value['id'];
                    $warnings['последний айдишкник с которого начинается выборка при следующем итерации'] = $last_id;
                }

                unset($wpvs);
                $insert_param_val = Yii::$app->db_target->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $data_worker_parameter_value_to_batchInsert);
                $insert_result_to_MySQL = Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `shift` = VALUES (`shift`), `date_work` = VALUES (`date_work`)")->execute();
                $count_isert += $insert_result_to_MySQL;

                //вставка остаток записей в worker_parameter_value
//                if (!empty($data_worker_parameter_value_to_batchInsert)) {
//                    $insert_result_to_MySQL = Yii::$app->db_target->createCommand()->batchInsert('worker_parameter_value', [
//                        'worker_parameter_id',
//                        'date_time',
//                        'value',
//                        'status_id',
//                        'shift',
//                        'date_work'
//                    ], $data_worker_parameter_value_to_batchInsert)->execute();
//
//                    if (!$insert_result_to_MySQL) {
//                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу worker_parameter_value' . $insert_result_to_MySQL);
//                    }
//                    $count_isert+=$insert_result_to_MySQL;
//                }
                unset($data_worker_parameter_value_to_batchInsert);

            }
            $warnings['общее количество записей worker_parameter_value на источнике'] = $count_all;
            $warnings['общее количество обработаных записей worker_parameter_value на вставку в новую БД'] = $count;
            $warnings['общее количество вставленых записей в worker_parameter_value '] = $count_isert;


            /** Отладка */
            $description = 'Зккончил вставку в worker_parameter_value';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_isert . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /**
     *
     * actionMigrationScheme - метод переноса схемы в новую БД (conjunction, place, edge их параметры и значения)
     * пример запуска: 127.0.0.1/admin/serviceamicum/migration-db/migration-scheme
     */
    public static function actionMigrationScheme()
    {

        // Стартовая отладочная информация
        $method_name = 'actionMigrationScheme';                                                                      // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_sensor = 0;                                                                                      // количество созданных сенсоров
        $count_all_sensor = 0;                                                                                      // количество сенсоров обработанных
        $count_new_sensor_parameter = 0;                                                                            // количество созданных параметров сенсоров
        $count_all_sensor_parameter = 0;                                                                            // количество параметров сенсоров обработанных
        $count_new_sensor_parameter_value = 0;                                                                      // количество созданных значений параметров сенсоров
        $count_all_sensor_parameter_value = 0;                                                                      // количество значений параметров сенсоров обработанных
        $count_insert_sensor_parameter_value = 0;                                                                   // количество вставленных значений параметров сенсоров обработанных

//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $response = self::TransferConjunctions();
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $debug[] = $response['debug'];
                $errors[] = $response['errors'];
            } else {
                $debug[] = $response['debug'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . ' TransferConjunctions. Ошибка при переносе conjynction');
            }

            $response = self::TransferPlaces();
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $debug[] = $response['debug'];
                $errors[] = $response['errors'];
            } else {
                $debug[] = $response['debug'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . ' TransferPlaces. Ошибка при переносе places');
            }
            $response = self::TransferEdges();
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $debug[] = $response['debug'];
                $errors[] = $response['errors'];
            } else {
                $debug[] = $response['debug'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . ' TransferEdges. Ошибка при переносе edges');
            }

            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     *
     * // actionSynchroSensorParameterTableId       - метод синхронизации ключей записи sensor_parameter
     * пример запуска: 127.0.0.1/admin/serviceamicum/migration-db/synchro-sensor-parameter-table-id
     */
    public static function actionSynchroSensorParameterTableId()
    {

        // Стартовая отладочная информация
        $method_name = 'actionSynchroSensorParameterTableId';                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_sensor = 0;                                                                                      // количество созданных сенсоров
        $count_all_sensor = 0;                                                                                      // количество сенсоров обработанных
        $count_new_sensor_parameter = 0;                                                                            // количество созданных параметров сенсоров
        $count_all_sensor_parameter = 0;                                                                            // количество параметров сенсоров обработанных
        $count_new_sensor_parameter_value = 0;                                                                      // количество созданных значений параметров сенсоров
        $count_all_sensor_parameter_value = 0;                                                                      // количество значений параметров сенсоров обработанных
        $count_insert_sensor_parameter_value = 0;                                                                   // количество вставленных значений параметров сенсоров обработанных

//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // получить данные с цод (источник)
            // получить данные с Комсомольской (назначение)

            // перебираем параметры с источника и меняем их id (не ключи) на назначении update если не совпадают

            $sensor_parameters_source = Yii::$app->db_source->createCommand('SELECT * FROM sensor_parameter')->queryAll();
            $sensors_target = Yii::$app->db_target->createCommand('SELECT id FROM sensor')->queryAll();
            foreach ($sensors_target as $sensor_target) {
                $sensors_target_hand[$sensor_target['id']] = $sensor_target['id'];
            }
            foreach ($sensor_parameters_source as $sensor_parameter_source) {
                if (isset($sensors_target_hand[$sensor_parameter_source['sensor_id']])) {
                    $sensor_parameters_array[] = array(
                        'id' => $sensor_parameter_source['id'],
                        'sensor_id' => $sensor_parameter_source['sensor_id'],
                        'parameter_id' => $sensor_parameter_source['parameter_id'],
                        'parameter_type_id' => $sensor_parameter_source['parameter_type_id'],
                    );
                }
            }

            if ($sensor_parameters_array) {
                $builder_data_to_db = Yii::$app->db_target->queryBuilder->batchInsert('sensor_parameter', ['id', 'sensor_id', 'parameter_id', 'parameter_type_id'], $sensor_parameters_array);
                $insert_result_to_MySQL = Yii::$app->db_target->createCommand($builder_data_to_db . " ON DUPLICATE KEY UPDATE `id` = VALUES (`id`)")->execute();
                $warnings[] = "actionSynchroSensorParameterTableId. закончил вставку данных в sensor_parameter: " . $insert_result_to_MySQL;
                if (!$insert_result_to_MySQL) {
                    throw new \Exception('actionSynchroSensorParameterTableId. Ошибка массовой вставки событий ситуации в БД ' . $insert_result_to_MySQL);
                }
            }


            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     *
     * // actionSynchroWorkerParameterTableId       - метод синхронизации ключей записи worker_parameter
     * пример запуска: 127.0.0.1/admin/serviceamicum/migration-db/synchro-worker-parameter-table-id
     */
    public static function actionSynchroWorkerParameterTableId()
    {

        // Стартовая отладочная информация
        $method_name = 'actionSynchroWorkerParameterTableId';                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_worker = 0;                                                                                      // количество созданных сенсоров
        $count_all_worker = 0;                                                                                      // количество сенсоров обработанных
        $count_new_worker_parameter = 0;                                                                            // количество созданных параметров сенсоров
        $count_all_worker_parameter = 0;                                                                            // количество параметров сенсоров обработанных
        $count_new_worker_parameter_value = 0;                                                                      // количество созданных значений параметров сенсоров
        $count_all_worker_parameter_value = 0;                                                                      // количество значений параметров сенсоров обработанных
        $count_insert_worker_parameter_value = 0;                                                                   // количество вставленных значений параметров сенсоров обработанных

//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // получить данные с цод (источник)
            // получить данные с Комсомольской (назначение)

            // перебираем параметры с источника и меняем их id (не ключи) на назначении update если не совпадают

            $worker_parameters_source = Yii::$app->db_source->createCommand('SELECT * FROM worker_parameter')->queryAll();
            $workers_target = Yii::$app->db_target->createCommand('SELECT id FROM worker')->queryAll();
            foreach ($workers_target as $worker_target) {
                $workers_target_hand[$worker_target['id']] = $worker_target['id'];
            }
            foreach ($worker_parameters_source as $worker_parameter_source) {
                if (isset($workers_target_hand[$worker_parameter_source['worker_object_id']])) {
                    $worker_parameters_array[] = array(
                        'id' => $worker_parameter_source['id'],
                        'worker_object_id' => $worker_parameter_source['worker_object_id'],
                        'parameter_id' => $worker_parameter_source['parameter_id'],
                        'parameter_type_id' => $worker_parameter_source['parameter_type_id'],
                    );
                }
            }

            if ($worker_parameters_array) {
                $builder_data_to_db = Yii::$app->db_target->queryBuilder->batchInsert('worker_parameter', ['id', 'worker_object_id', 'parameter_id', 'parameter_type_id'], $worker_parameters_array);
                $insert_result_to_MySQL = Yii::$app->db_target->createCommand($builder_data_to_db . " ON DUPLICATE KEY UPDATE `id` = VALUES (`id`)")->execute();
                $warnings[] = "actionSynchroSensorParameterTableId. закончил вставку данных в worker_parameter: " . $insert_result_to_MySQL;
                if (!$insert_result_to_MySQL) {
                    throw new \Exception('actionSynchroSensorParameterTableId. Ошибка массовой вставки событий ситуации в БД ' . $insert_result_to_MySQL);
                }
            }


            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /**
     *
     * // actionSynchroDepartmentParameterTableId       - метод синхронизации ключей записи department_parameter
     * пример запуска: 127.0.0.1/admin/serviceamicum/migration-db/synchro-department-parameter-table-id
     */
    public static function actionSynchroDepartmentParameterTableId()
    {

        // Стартовая отладочная информация
        $method_name = 'actionSynchroDepartmentParameterTableId';                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_department = 0;                                                                                      // количество созданных сенсоров
        $count_all_department = 0;                                                                                      // количество сенсоров обработанных
        $count_new_department_parameter = 0;                                                                            // количество созданных параметров сенсоров
        $count_all_department_parameter = 0;                                                                            // количество параметров сенсоров обработанных
        $count_new_department_parameter_value = 0;                                                                      // количество созданных значений параметров сенсоров
        $count_all_department_parameter_value = 0;                                                                      // количество значений параметров сенсоров обработанных
        $count_insert_department_parameter_value = 0;                                                                   // количество вставленных значений параметров сенсоров обработанных

//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // получить данные с цод (источник)
            // получить данные с Комсомольской (назначение)

            // перебираем параметры с источника и меняем их id (не ключи) на назначении update если не совпадают

            $department_parameters_source = Yii::$app->db_source->createCommand('SELECT * FROM department_parameter')->queryAll();
            $departments_target = Yii::$app->db_target->createCommand('SELECT id FROM company_department')->queryAll();
            foreach ($departments_target as $department_target) {
                $departments_target_hand[$department_target['id']] = $department_target['id'];
            }
            foreach ($department_parameters_source as $department_parameter_source) {
                if (isset($departments_target_hand[$department_parameter_source['company_department_id']])) {
                    $department_parameters_array[] = array(
                        'id' => $department_parameter_source['id'],
                        'company_department_id' => $department_parameter_source['company_department_id'],
                        'parameter_id' => $department_parameter_source['parameter_id'],
                        'parameter_type_id' => $department_parameter_source['parameter_type_id'],
                    );
                }
            }

            if ($department_parameters_array) {
                $builder_data_to_db = Yii::$app->db_target->queryBuilder->batchInsert('department_parameter', ['id', 'company_department_id', 'parameter_id', 'parameter_type_id'], $department_parameters_array);
                $insert_result_to_MySQL = Yii::$app->db_target->createCommand($builder_data_to_db . " ON DUPLICATE KEY UPDATE `id` = VALUES (`id`)")->execute();
                $warnings[] = "actionSynchroSensorParameterTableId. закончил вставку данных в department_parameter: " . $insert_result_to_MySQL;
                if (!$insert_result_to_MySQL) {
                    throw new \Exception('actionSynchroSensorParameterTableId. Ошибка массовой вставки событий ситуации в БД ' . $insert_result_to_MySQL);
                }
            }


            /** Отладка */
            $description = ' Закончил обновление department_parameter';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     *
     * // actionMoveSensorParameterTableHistory       - метод архивирования таблицы sensor_parameter_value в sensor_parameter_value_history
     * пример запуска: 127.0.0.1/admin/serviceamicum/migration-db/move-sensor-parameter-table-history
     */
    public static function actionMoveSensorParameterTableHistory()
    {

        // Стартовая отладочная информация
        $method_name = 'actionMoveSensorParameterTableHistory';                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_sensor = 0;                                                                                      // количество созданных сенсоров
        $count_all_sensor = 0;                                                                                      // количество сенсоров обработанных
        $count_new_sensor_parameter = 0;                                                                            // количество созданных параметров сенсоров
        $count_all_sensor_parameter = 0;                                                                            // количество параметров сенсоров обработанных
        $count_new_sensor_parameter_value = 0;                                                                      // количество созданных значений параметров сенсоров
        $count_all_sensor_parameter_value = 0;                                                                      // количество значений параметров сенсоров обработанных
        $count_insert_sensor_parameter_value = 0;                                                                   // количество вставленных значений параметров сенсоров обработанных

//        ini_set('max_execution_time', 60000);
//        ini_set('memory_limit', "20500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $counter_iteration = true;
            $last_id = 0;

            $min_date_time = SensorParameterValueHistory::find()
                ->max('date_time');
            $min_date_time_source = SensorParameterValue::find()
                ->min('date_time');

            $warnings[] = $method_name . ". Минимальная дата для переноса в истории:" . $min_date_time;
            $warnings[] = $method_name . ". Минимальная дата для переноса в оперативной:" . $min_date_time_source;

            if (strtotime($min_date_time_source) > strtotime($min_date_time)) {
                $min_date_time = $min_date_time_source;
            }

            $now_date_time = date("Y-m-d H:i:s", strtotime(Assistant::GetDateTimeNow() . "-5days"));
            $max_date_time = date("Y-m-d H:i:s", strtotime($min_date_time . "+2days"));
            $last_date_time = $max_date_time;
            $last_id = 0;
            $warnings[] = $method_name . ". Ограничение по текущей дате:" . $now_date_time;
            $warnings[] = $method_name . ". Минимальная дата для переноса:" . $min_date_time;
            $warnings[] = $method_name . ". Максимальная дата для переноса:" . $max_date_time;

            $sql_query_source_column = "SHOW COLUMNS FROM sensor_parameter_value"; //определяем столбцы исходной таблицы
            $source_table_columns = Yii::$app->db_amicum2->createCommand($sql_query_source_column)->queryAll();
            $source_column = array();
            foreach ($source_table_columns as $source_table_column) {
                $source_column[] = $source_table_column['Field'];
            }
            $warnings[] = $method_name . ". Столбцы исходной таблицы: ";
            $warnings[] = $source_column;

            $iteration_count = 0;
            while ($counter_iteration) {

                $warnings[] = $method_name . $iteration_count . ". Старт запроса.";
                $spvs = Yii::$app->db_amicum2->createCommand("SELECT * FROM sensor_parameter_value where date_time<'" . $now_date_time . "' and date_time between '" . $min_date_time . "' and '" . $max_date_time . "' order by date_time asc limit 100000")->queryAll();
                $count_spv = count($spvs);
                if ($count_spv < 100000) {
                    $counter_iteration = false;
                }
                if (!$spvs) {
                    $errors[] = $spvs;
                    throw new \Exception($method_name . '. Нет данных для вставки в БД: ' . $count_spv);
                }
                $last_date_time = $spvs[$count_spv - 1]['date_time'];
                $last_id = $spvs[$count_spv - 1]['id'];
                $warnings[] = $method_name . ". Количество записей для обработки: " . count($spvs);
                $warnings[] = $method_name . ". Последний id: " . $last_id;
                $warnings[] = $method_name . ". Последняя date_time: " . $last_date_time;

                $data_sensor_parameter_value_to_batchInsert = null;

                $warnings[] = $method_name . $iteration_count . ". Получил данные." . $count_spv;

                //вставка остаток записей в sensor_parameter_value
                if (!empty($spvs)) {
                    $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('sensor_parameter_value_history', $source_column, $spvs);
                    $insert_result_to_MySQL = Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`)")->execute();

                    if (!$insert_result_to_MySQL) {
                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу sensor_parameter_value' . $insert_result_to_MySQL);
                    }
                }
                $warnings[] = $method_name . $iteration_count . ". Вставил данные. " . $insert_result_to_MySQL;

                $delete_result = Yii::$app->db_amicum2->createCommand("DELETE FROM sensor_parameter_value where date_time<'" . $last_date_time . "' ")->execute();

                $warnings[] = $method_name . ". Количество удаленных записей: " . $delete_result;
                if (!$delete_result) {
                    throw new \Exception($method_name . ". Ошибка массового удаления истории из БД sensor_parameter_value" . $delete_result);
                }

                $warnings[] = $method_name . $iteration_count . " . Удалил данные ";

                $iteration_count++;
                $warnings[] = $method_name . " . Итератор: " . $iteration_count;
                if ($iteration_count == 10000) {
                    throw new \Exception($method_name . '. Превышено количество итераций');
                }

                unset($spvs);
            }


            /** Отладка */
            $description = ' Закончил обновление SensorParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     *
     * // actionMoveWorkerParameterTableHistory       - метод архивирования таблицы worker_parameter_value в worker_parameter_value_history
     * пример запуска: 127.0.0.1/admin/serviceamicum/migration-db/move-worker-parameter-table-history
     */
    public static function actionMoveWorkerParameterTableHistory()
    {

        // Стартовая отладочная информация
        $method_name = 'actionMoveWorkerParameterTableHistory';                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $count_new_worker = 0;                                                                                      // количество созданных сенсоров
        $count_all_worker = 0;                                                                                      // количество сенсоров обработанных
        $count_new_worker_parameter = 0;                                                                            // количество созданных параметров сенсоров
        $count_all_worker_parameter = 0;                                                                            // количество параметров сенсоров обработанных
        $count_new_worker_parameter_value = 0;                                                                      // количество созданных значений параметров сенсоров
        $count_all_worker_parameter_value = 0;                                                                      // количество значений параметров сенсоров обработанных
        $count_insert_worker_parameter_value = 0;                                                                   // количество вставленных значений параметров сенсоров обработанных

//        ini_set('max_execution_time', 60000);
//        ini_set('memory_limit', "20500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                  // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                 // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                     // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                          // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                            // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                        // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                     // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                       // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $counter_iteration = true;
            $last_id = 0;
            $now_date_time = date("Y-m-d H:i:s", strtotime(Assistant::GetDateTimeNow() . "-6days"));
            $min_date_time = WorkerParameterValueHistory::find()
                ->max('date_time');
            $min_date_time_source = WorkerParameterValue::find()
                ->min('date_time');

            $warnings[] = $method_name . ". Ограничение по текущей дате: " . $now_date_time;
            $warnings[] = $method_name . ". Минимальная дата для переноса в истории: " . $min_date_time;
            $warnings[] = $method_name . ". Минимальная дата для переноса в оперативной: " . $min_date_time_source;

            if (strtotime($min_date_time_source) > strtotime($min_date_time)) {
                $min_date_time = $min_date_time_source;
            }

            $max_date_time = date("Y-m-d H:i:s", strtotime($min_date_time . "+2days"));
            $last_date_time = $max_date_time;
            $last_id = 0;
            $warnings[] = $method_name . ". Минимальная дата для переноса: " . $min_date_time;
            $warnings[] = $method_name . ". Максимальная дата для переноса: " . $max_date_time;

            $sql_query_source_column = "SHOW COLUMNS FROM worker_parameter_value"; //определяем столбцы исходной таблицы
            $source_table_columns = Yii::$app->db_amicum2->createCommand($sql_query_source_column)->queryAll();
            $source_column = array();
            foreach ($source_table_columns as $source_table_column) {
                $source_column[] = $source_table_column['Field'];
            }
            $warnings[] = $method_name . ". Столбцы исходной таблицы: ";
            $warnings[] = $source_column;

            $iteration_count = 0;
            while ($counter_iteration) {

                $warnings[] = $method_name . $iteration_count . ". Старт запроса.";
                $wpvs = Yii::$app->db_amicum2->createCommand("SELECT * FROM worker_parameter_value where date_time<'" . $now_date_time . "' and date_time between '" . $min_date_time . "' and '" . $max_date_time . "' order by date_time asc limit 100000")->queryAll();
                $count_wpv = count($wpvs);
                $warnings[] = "Количество записей получил для вставки: $count_wpv";
                if ($count_wpv < 100000) {
                    $counter_iteration = false;
                }

                if (!$wpvs) {
                    $errors[] = $wpvs;
                    throw new \Exception($method_name . '. Нет данных для вставки в БД: ' . $count_wpv);
                }

                $last_date_time = $wpvs[$count_wpv - 1]['date_time'];
                $last_id = $wpvs[$count_wpv - 1]['id'];
                $warnings[] = $method_name . ". Количество записей для обработки: " . count($wpvs);
                $warnings[] = $method_name . ". Последний id: " . $last_id;
                $warnings[] = $method_name . ". Последняя date_time: " . $last_date_time;

                $data_worker_parameter_value_to_batchInsert = null;

                $warnings[] = $method_name . $iteration_count . ". Получил данные." . $count_wpv;

                //вставка остаток записей в worker_parameter_value
                if (!empty($wpvs)) {
                    $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('worker_parameter_value_history', $source_column, $wpvs);
                    $insert_result_to_MySQL = Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`)")->execute();

                    if (!$insert_result_to_MySQL) {
                        $warnings[] = $method_name . 'Ошибка массовой вставки в БД в таблицу worker_parameter_value: ' . $insert_result_to_MySQL;
//                        throw new \Exception($method_name . '. Ошибка массовой вставки в БД в таблицу worker_parameter_value: ' . $insert_result_to_MySQL);
                    }
                }
                $warnings[] = $method_name . $iteration_count . ". Вставил данные. " . $insert_result_to_MySQL;

                $delete_result = Yii::$app->db_amicum2->createCommand("DELETE FROM worker_parameter_value where date_time<'" . $last_date_time . "' ")->execute();

                $warnings[] = $method_name . ". Количество удаленных записей: " . $delete_result;
                if (!$delete_result) {
                    $warnings[] = $method_name . " Ошибка массового удаления истории из БД worker_parameter_value" . $delete_result;
//                    throw new \Exception($method_name . ". Ошибка массового удаления истории из БД worker_parameter_value" . $delete_result);
                }

                $warnings[] = $method_name . $iteration_count . " . Удалил данные ";

                $iteration_count++;
                $warnings[] = $method_name . " . Итератор: " . $iteration_count;
                if ($iteration_count == 10000) {
                    throw new \Exception($method_name . '. Превышено количество итераций');
                }

                unset($wpvs);
            }


            /** Отладка */
            $description = ' Закончил обновление WorkerParameterHandbookValue';                                                                 // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_main = array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

}