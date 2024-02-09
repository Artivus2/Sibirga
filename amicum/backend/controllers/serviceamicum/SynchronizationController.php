<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\serviceamicum;

use backend\controllers\Assistant;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\const_amicum\DepartmentTypeEnum;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\const_amicum\VidDocumentEnumController;
use backend\controllers\LogAmicum;
use backend\controllers\StrataJobController;
use DateTime;
use Exception;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\handbooks\InjunctionController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Attachment;
use frontend\models\Checking;
use frontend\models\CheckingPlace;
use frontend\models\CheckingWorkerType;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\CompanyDepartment1;
use frontend\models\CorrectMeasures;
use frontend\models\Department1;
use frontend\models\Document;
use frontend\models\DocumentAttachment;
use frontend\models\Employee;
use frontend\models\Employee1;
use frontend\models\Injunction;
use frontend\models\InjunctionViolation;
use frontend\models\KindViolation;
use frontend\models\Mine;
use frontend\models\Place;
use frontend\models\PlaceCompanyDepartment;
use frontend\models\Plast;
use frontend\models\Position;
use frontend\models\Position1;
use frontend\models\SapAmicumLookuotActionMv;
use frontend\models\SapAmicumRostextMv;
use frontend\models\SapAmicumStopLookoutActMv;
use frontend\models\SapAsuSizFull;
use frontend\models\SapAsuWorkerSizFull;
use frontend\models\SapAsuWorkerSizUpdate;
use frontend\models\SapCompanyUpdate;
use frontend\models\SapDepartmentFull;
use frontend\models\SapEmployeeFull;
use frontend\models\SapEmployeeUpdate;
use frontend\models\SapHcmHrsrootPernrView;
use frontend\models\SapHcmStructObjidView;
use frontend\models\SapInstructionGiversMv;
use frontend\models\SapPositionFull;
use frontend\models\SapPositionUpdate;
use frontend\models\SapPositionYagok;
use frontend\models\SapRefErrorDirectionMv;
use frontend\models\SapRefNormDocMv;
use frontend\models\SapRoleUpdate;
use frontend\models\SapSizUpdate;
use frontend\models\SapSkudUpdate;
use frontend\models\SapWorkerCard;
use frontend\models\SapWorkerSizUpdate;
use frontend\models\Siz;
use frontend\models\SizKind;
use frontend\models\StopPb;
use frontend\models\User;
use frontend\models\USERBLOBMV;
use frontend\models\Violation;
use frontend\models\ViolationType;
use frontend\models\Violator;
use frontend\models\Worker;
use frontend\models\Worker1;
use frontend\models\WorkerCard;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameter;
use frontend\models\WorkerParameterValue;
use frontend\models\WorkerSiz;
use frontend\models\WorkerSizStatus;
use Throwable;
use WebSocket\Client;
use Yii;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class SynchronizationController
{
    // Р - рефакторинг проведен (можно пользоваться)
    /**  СИНХРОНИЗАЦИЯ ДОЛЖНОСТЕЙ !! ПОСЛЕ РЕФАКТОРИНГА !! */
    // ---- ГОСУДАРСТВЕННЫЕ ДОЛЖНОСТИ
    // Р partUpdateDataOraclePositionSecondInsert                            -   копирование таблицы position_update в sap_position_update (0 этап - нужен для сокращенных должностей (может не использоваться))

    // вариант А применяется на Воркуте и берет данные с CONTACT_VIEW (справочник персонала)
    // вариант Б берет данные из гос справочника должностей spr_prof_view САПА

    // ---- действующие применяемые методы  (ВАРИАНТ А)
    // Р sapCopyPositionFromContactView                                      -   копирование сгруппированных должностей из представления CONTACT_VIEW - ORACLE (1 этап)
    // Р sapUpdatePosition                                                   -   метод единоразового обновления должностей из сапа на основе сгруппированных должностей работников (2 этап)

    // ---- ГОСУДАРСТВЕННЫЕ ДОЛЖНОСТИ       (ВАРИАНТ Б)
    // partUpdateDataOraclePositionSecondUpdate                            -   обновление подразделений

    // ---- хз зачем они нужны
    // copyDataOraclePosition                                              -   копирование таблицы position_full в sap_position_full
    // insertDataOracleToMySQLPosition                                     -   копирование таблицы sap_position_full в position1
    // initializationTablesOracle                                          -   очищает таблицы position_full и position_update в Oracle и заполняет их несколькими строками тестовых данных
    // addNewDataToOracle                                                  -   добавляет строки в таблицу position_update в Oracle
    // RemoveDoubleEmployee                                                -   метод по удалению дубликатов после синхронизации сап и АМИКУМ ( у кого табельный номер не равен worker_id)

    /**  СИНХРОНИЗАЦИЯ ПОДРАЗДЕЛЕНИЙ - КОМПАНИЙ */
    // copyDataOracleDepartment                                            -   копирование данных о подразделениях из таблицы Oracle в таблицу MySQL
    // partLoadingDataOracleDepartment                                     -   частичная загрузка данных о подразделениях из таблицы Oracle в таблицу MySQL
    // getDataFromOracleForCompany                                         -   вставка данных в таблицы sap_company, sap_department, company_department_1
    // partCompanyDepartmentInsert                                         -   копирование таблицы обновлений company_department_update из Oracle в sap_company_update MySQL
    // partCompanyDepartmentUpdate                                         -   синхронизция данных между таблицами sap_company_update и sap_company в MySQL

    /**  СИНХРОНИЗАЦИЯ ЛЮДЕЙ */
    // ---- действующие применяемые методы
    // sapCopyEmployeeContactView                                          -   ЧАСТИЧНОЕ копирование данных об изменениях в справочнике сотрудников из Oracle  в MySQL
    // sapUpdateEmployee                                                   -   частичная загрузка данных из таблицы обновлений в спровочнике MySQL

    // ---- старые методы хз зачем они нужны
    // copyDataOracleEmployee                                              -   ПОЛНЫЙ перенос информации о сотрудниках из таблицы Oracle в таблицу MySQL
    // fullFromSapEmployeeToEmployee1                                      -   перенос данных  в справчник сотрудников
    // partUpdateEmployeeChangeId                                          -   частичная загрузка сотрудников (ПЕРЕЗАПИСЫВАЕТ id РАБОТНИКОВ в worker и удаляет лишних)
    // partUpdateEmployeeDel                                               -   удаляет дубли из таблицы Employee. Оставляет тех, кто соответствует своим табельным номерам

    /**  СИНХРОНИЗАЦИЯ СИЗ */
    // ---- действующие применяемые методы
    // Р - copyFullDataWorkWear                                                -   копирование таблицы выданных СИЗов из Oracle в MySQL (история носки)
    // Р - copyFullDataSIZ                                                     -   копирование таблицы СИЗов из Oracle в MySQL (справочник СИЗ)
    // Р - partUpdateSiz                                                       -   обновление справочника СИЗов
    // Р - partUpdateWorkerSiz                                                 -   обновление истории выдачи/списания СИЗ работника

    // ---- старые методы хз зачем они нужны
    // --------- полное копирование
    // copyFullDataWorkWear                                                -   копирование таблицы выданных СИЗов из Oracle в MySQL
    // sizTables                                                           -   Добавление данных по СИЗам из промежуточных таблиц в основные

    // --------- обновление таблиц в амикуме
    // partWorkerSizUpdateTables                                           -   копирование данных в промежуточные таблицы для обновления
    // partWorkerSizUpdateTableAdd                                         -   копирование данных в промежуточные таблицы для обновления

    /**  СИНХРОНИЗАЦИЯ СКУД */
    // ---- действующие применяемые методы
    // Р - newSynchronizationSKUD                                          - переписанный метод по синхронизации СКУд + создает параметр для алкотеста
    // Р - synchSKUD()                                                     - метод синхронизации СКУД на прямую из Firebird

    /**  СИНХРОНИЗАЦИЯ РОЛЕй */
    // copyDataOracleRole                                                  -   копирование данных из таблицы профессий Oracle в MySQL
    // copySapRoleToMySQLRole1                                             -   копирование справочника профессий из интеграциогнной таблицы в рабочую базу

    /**  ПОМОЙКА */
    // copyUpdatesFromOracleToMySQL                                        -   Копирование обновлений в справочнике профессий из Oracle в интеграционную таблицу в MySQL
    // copyFromOracleToMySQLYagok                                          -   перенос данных из Oracle в MySQL таблицы sap_position_yagok, position1
    // getDataFromOracleForCompanyYagok                                    -   вставка данных из таблицы sap_department_full в company_department_1, company1, department1
    // copyDataOracleEmployeeYagok                                         -   Полный перенос информации о сотрудниках из таблицы Oracle в таблицу MySQL
    // copyDataEmployeeYagok                                               -   перенос информации в employee1 и worker1
    // copyDataWorkerObjectYagok                                           -   перенос информации в worker_object1
    // copyDataUpdatesYagok                                                -   Копирование таблицы обновлений из Oracle  в MySQL
    // synchronizationEmloyeeUpdatesYagok                                  -   обновление работников
    // pabCopyAmicumStopLookoutActMV                                       -   Копирование данных по ПАБам из Oracle в промежуточные таблицы MySQL
    // pabCopyAmicumLookoutActMV                                           -   копирование данных по нарушениям и предписаниям из Oracle в промежуточные таблицы MySQL
    // selectDataSKUD                                                      -   синхронизация скуд. используются методы, не совсем подходящие к задаче
    // insertInjuction                                                     -   метод, синхоронизирующий данные по ПАБ и предписаниям.(вставка)

    /**  СИНХРОНИЗАЦИЯ СПРАВОЧНИКОВ ППК ПАБ */
    // ppkCopyPabmv()               - копирование данных ПАБ из Oracle в промежуточные таблицы MySQL
    // ppkCopyRefInstructionmv()    - копирование данных внутренних предписаний из Oracle в промежуточные таблицы MySQL
    // ppkCopyRtnmv()               - копирование данных ПРЕДПИСАНИЙ РТН из Oracle в промежуточные таблицы MySQL
    // ppkCopyRefCheckmv()          - копирование данных СПРАВОЧНИКА ВИДОВ ПРОВЕРОК из Oracle в промежуточные таблицы MySQL
    // ppkCopyGiltyManager()        - копирование данных СПРАВОЧНИК РЕШЕНИЙ РУКОВОДИТЕЛЯ из Oracle в промежуточные таблицы MySQL
    // ppkCopyErrorDirection()      - копирование данных СПРАВОЧНИК НАПРАВЛЕНИЙ НАРУШЕНИЙ из Oracle в промежуточные таблицы MySQL
    // ppkCopyRefFailureEffectmv()  - копирование данных СПРАВОЧНИК ПОСЛЕДСТВИЙ из Oracle в промежуточные таблицы MySQL
    // ppkCopyUserBlob()            - копирование данных СПРАВОЧНИК ДОКУМЕНТОВ из Oracle в промежуточные таблицы MySQL
    // ppkCopyRefOPONumber()        - копирование данных СПРАВОЧНИК РЕГИСТРАЦИОННЫХ НОМЕРОВ ОПО из Oracle в промежуточные таблицы MySQL REF_INSTRUCTION_OPO_MV
    // ppkCopyRefNormDocmv()        - копирование данных СПРАВОЧНИК Нормативных документов  из Oracle в промежуточные таблицы MySQL REF_NORM_DOC_MV
    // ppkCopyRefCheckmv()          - копирование данных СПРАВОЧНИКА ВИДОВ ПРОВЕРОК из Oracle в промежуточные таблицы MySQL
    // ppkCopyRefSituationmv()      - копирование данных СПРАВОЧНИК ВЗЫСКАНИЙ  из Oracle в промежуточные таблицы MySQL REF_SITUATION_MV

    /**  СИНХРОНИЗАЦИЯ ПАБ */
    // ppkSynhPABNNMain             - главный метод по синхронизации ПАБ (н/н - не делаются)
    // ppkSynhCheckingPab           - Метод синхронизации проверок ПАБ
    // ppkSynhCkeckingWorkerPab()   - Синхронизация проверяющих ПАБ
    // ppkSynhInjunctionPab()       - Синхронизция нарушений ПАБ

    /**  СИНХРОНИЗАЦИЯ н/н */
    // ppkSyncNNMain                - Главный метод синхронизации Нарушений несоответствий
    // ppkSyncCheckingNN()          - Синхронизация проверок нарушений несоответствий
    // ppkSyncCheckingWorkerNN()    - Синхронизация типов работников нарушения несоответствия

    /**  СИНХРОНИЗАЦИЯ ВНУТРЕННИХ ПРЕДПИСАНИЙ */
    // ppkSyncInjuctionMain         - ГЛАВНЫЙ МЕТОД СИНХРОНИЗАЦИИ ПРЕДПИСАНИЙ ВНУТРЕННИХ
    // ppkSynhChecking              - метод синхронизации главной таблицы проверок
    // ppkSynhCheckingWorker        - метод синхронизации проверяющих
    // ppkSynhInjunction            - метод синхронизации предписаний /нарушений

    /**  СИНХРОНИЗАЦИЯ Файлов */
    // SyncBlobMain()               - Синхронизация Файлов главный метод
    // ppkCopyUserBlob()            - копирование данных СПРАВОЧНИК ДОКУМЕНТОВ из Oracle в промежуточные таблицы MySQL
    // ppkSynhBlobDoc()             - метод синхронизации  вложений и связки вложений и документов по ref_norm_doc_id из таблицы USER_BLOB_MV

    /**  СИНХРОНИЗАЦИЯ ПРЕДПИСАНИЙ РТН (ростехнадзора) */
    // ppkSynhInjunctionRTNMain             - ГЛАВНЫЙ МЕТОД СИНХРОНИЗАЦИИ ПРЕДПИСАНИЙ РТН
    // ppkSynhCheckingRostex                - синхронизация проверки РТН
    // ppkSynchCheckingWorkerRostex         - Синхронизация связки проверок и работников участвующих в проверке
    // ppkSynhInjunctionRTN                 - Синхроиназция предписаний РТН
    // FullWorkerCard                       - Метод полной загрузки пропусков сотрудников
    // PartUpdateWorkerCard                 - Метод частичной загрузки пропусков сотрудников
    // CopyWorkerPass                       - Копирование из oracle  в нашу таблицу (sap_worker_card)

    /** СИНХРОНИЗАЦИЯ ДАННЫХ ПОЛЬЗОВАТЕЛЕЙ (user)*/
    // CopyUserData                         - Копирование данных пользователей из oracle в нашу таблицу (sap_user_copy)
    // UpdateUserData                       - Обновление данных пользователей

    /** СЕРВИСНЫЕ */
    // ppkCopyTableOnDuplicate              - универсальный метод копирования данных таблиц из Oracle в промежуточные таблицы MySQL с проверкой на дубликаты
    // ppkCopyTable                         - универсальный метод копирования данных из Oracle в промежуточные таблицы MySQL


    const STATUS_ISSUED = 'Выдан';
    const STATUS_EXTENDED = 'Продлен';
    const STATUS_DECOMMISSIONED = 'Списан';
    const COMPANY_DEPARTMENT_ID_RTN = 1;
    /**@var int Позиция по умолчанию: Инспектор */
    const DEFAULT_POSITION = 1001572;
    public $oracle_db;

    public function __construct()
    {
//          $this->oracle_db = oci_connect('SYSTEM', 'Administrator5', 'XE');
//        $this->oracle_db = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(BATCHPROD = (DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=batchprod.severstal.severstalgroup.com)(PORT=1521))(CONNECT_DATA=(SERVER=default)(SERVICE_NAME=BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
        $this->oracle_db = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHPROD . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
    }


    /**
     * Метод copyDataOracleDepartment() - тестовый метод полного переноса значений информации о подразделениях из таблицы Oracle dep_worker в таблицу MySQL sap_departments
     * @array  - стандартный массив выходных данных
     * @package backend\controllers
     * @example http://localhost/admin/serviceamicum/synchronization/copy-data-oracle-department
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 19.07.2019 8:19
     */
    public function copyDataOracleDepartment()
    {
        $errors = array();
        $query_result = array();
        $result = 0;
        $warnings = array();
        $status = 1;
        $num_sync = 0;
        $query = array();
        $result_dep = array();
        $microtime_start = microtime(true);
        try {
            /******************* Подключение к базе Oracle *******************/
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $query = oci_parse($conn_oracle, 'SELECT org_edinc, name_dep, status FROM dep_worker');                     //создание строки запроса по номеру подразделения, наименованию и статусу
            oci_execute($query);
            $warnings[] = 'copyDataOracleDepartment. Выполнил запрос ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //пробегаемся по массиву строк выполненного запроса
            {
                $rows = mb_convert_encoding($row, 'UTF-8', 'windows-1251');                                             //меняем кодировку на UTF-8
                /******************* Проверка статуса "null" в таблице Oracle и замена на "0" *******************/
                if ($rows['STATUS'] === null) {
                    $rows['STATUS'] = 0;
                } else {
                    throw new Exception('Ошибка синхронизации. Статус должен быть NULL');
                }
                $query_result[] = $rows;
            }
            $result_dep = ArrayHelper::index($query_result, 'ORG_EDINC');                                               //индексация массива по номеру организации, выборка уникальных записей
            $warnings[] = 'copyDataOracleDepartment. Начал добавлять данные в MySQL';
            $count_rows = Yii::$app->db->createCommand()->batchInsert('sap_department_full',                            //множественная вставка данных
                ['OBJID', 'STEXT', 'status'],
                $result_dep)->execute();
            $warnings[] = 'copyDataOracleDepartment добавил данные ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($count_rows !== 0) {
                $status = 1;
            } else {
                throw new Exception('copyDataOracleDepartment. Ошибка добавления записи');
            }
            $warnings[] = 'copyDataOracleDepartment закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);

        } catch (Throwable $ex) {
            $errors[] = 'copyDataOracleDepartment. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => $result_dep, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
//        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
//        Yii::$app->response->data = $result;
        return $result;
    }


    /**
     * Метод partLoadingDataOracleDepartment() - Частичная выгрузка таблицы department_update (измененные и новые записи)
     * @array  - стандартный массив выходных данных
     * @package backend\controllers\sinhronization
     * @example http://localhost/admin/serviceamicum/synchronization/part-loading-data-oracle-department
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 19.07.2019 9:19
     */
    public function partLoadingDataOracleDepartment()
    {
        $errors = array();
        $query_result = array();
        $result = 0;
        $warnings = array();
        $status = 1;
        $num_sync = 0;
        $add_rows = array();
        $microtime_start = microtime(true);
        try {
            /******************* Получение номера синхронизации из таблицы в MySQL *******************/
            if (($get_num_sync = Yii::$app->db->createCommand('SELECT max(num_sync) FROM sap_department_update')->queryScalar()) === NULL) {
                $num_sync = 1;
            } else {
                $num_sync = $get_num_sync + 1;
            }
            /******************* Подключение  к базе Oracle *******************/
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings [] = 'Соединение с Oracle не выполнено';                                                      //параметр соединения к базе данных если коннекшена
            } else {
                $warnings [] = 'Соединение с Oracle установлено';                                                       //параметр успешного соединения
            }
            /******************* Создание и выполнение запроса *******************/
            $query = oci_parse($conn_oracle, 'SELECT OBJID, STEXT FROM department_update');                             //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
            oci_execute($query);                                                                                        //выполнение запроса
            $warnings[] = 'partLoadingDataOracleDepartment. Выполнил запрос ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            /******************* Цикл по строкам запроса. Смена кодировки и индексация массива *******************/
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $rows = mb_convert_encoding($row, 'UTF-8', 'windows-1251');                                             //смена кодировки на UTF-8
                $rows['num_sync'] = $num_sync;
                $rows['status'] = StatusEnumController::NOT_DONE;
                $query_result[] = $rows;
            }
            foreach ($query_result as $item) {
                $query_result[] = mb_convert_encoding($item, 'utf-8', mb_detect_encoding($item));
            }

            $warnings[] = 'partLoadingDataOracleDepartment. Проиндексировал выполненный запрос';
            $warnings[] = 'partLoadingDataOracleDepartment. Начал добавлять данные в MySQL ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            /******************* Добавление строк в базу MySQL *******************/
            $add_rows = Yii::$app->db->createCommand()->batchInsert('sap_department_update',
                ['OBJID', 'STEXT', 'num_sync', 'status'],
                $query_result)->execute();
            if ($add_rows !== 0) {
                $warnings[] = 'partLoadingDataOracleDepartment добавил запись в таблицу со списком изменений';
            } else {
                throw new Exception('partLoadingDataOracleDepartment. Ошибка добавления записи');
            }
            /******************* Обновление строк в таблице sap_department и удаление выполненных из таблицы *******************/
            foreach ($query_result as $item) {
                $element = Yii::$app->db->createCommand("SELECT OBJID FROM sap_department_full WHERE OBJID={$item['OBJID']}")->execute();  //если элемента с данным id нет в базе, то добавляем поле
                if (!$element) {
                    $updated = Yii::$app->db->createCommand()->insert('sap_department_full', ['OBJID' => $item['OBJID'], 'STEXT' => $item['STEXT']])->execute();
                    $warnings[] = 'partLoadingDataOracleDepartment. Добавлена запись и обновлен статус' . $duration_method = round(microtime(true) - $microtime_start, 6);
                } else {
                    $updated = Yii::$app->db->createCommand()->update('sap_department_full', ['STEXT' => $item['STEXT']], ['OBJID' => $item['OBJID']])->execute();  //иначе обновляем существующее
                    $warnings[] = 'partLoadingDataOracleDepartment. Обновлена запись ' . $duration_method = round(microtime(true) - $microtime_start, 6);
                }
                if ($updated !== 0) {
                    $update_status = Yii::$app->db->createCommand()->update('sap_department_update', ['status' => StatusEnumController::DONE], ['STEXT' => $item['STEXT']])->execute();
                    $update_status_f = Yii::$app->db->createCommand()->update('sap_department_full', ['status' => StatusEnumController::DONE], ['OBJID' => $item['OBJID']])->execute();
//                    $query_del = oci_parse($conn_oracle, "delete from department_update where OBJID={$item['OBJID']}");   //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
//                    oci_execute($query_del);
                    //$delete_element = Yii::$app->db->createCommand()->delete('sap_department_update', ['id' => $item['OBJID'], 'name' => $item['STEXT']])->execute(); //если элемент успешно обработан, то удаляем его из промежуточной таблицы
                    $warnings[] = 'partLoadingDataOracleDepartment. Обновил запись в таблице обновлений ' . $duration_method = round(microtime(true) - $microtime_start, 6);
                } else {
                    $update_status = Yii::$app->db->createCommand()->update('sap_department_update', ['status' => StatusEnumController::NOT_DONE], ['num_sync' => $num_sync])->execute();
                    $update_status_f = Yii::$app->db->createCommand()->update('sap_department_full', ['status' => StatusEnumController::NOT_DONE], ['OBJID' => $item['OBJID']])->execute();
                }
            }
            $warnings[] = 'partLoadingDataOracleDepartment закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $status = 1;
            $num_sync++;
        } catch (Throwable $ex) {
            $errors[] = 'partLoadingDataOracleDepartment. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = ['Из Oracle в MySQL: ' => $query_result];
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
//        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
        return $result_main;
    }

    /******************* РАБОТА СО СПРАВОЧНИКОМ POSITION *******************/

    /**
     * Метод copyDataOraclePosition() - Копирование справочника должностей из Oracle в интеграционную таблицу в MySQL
     * @return array - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 16:18
     */
    public function copyDataOraclePosition()
    {
        $errors = array();
        $query_result = array();
        $result = array();
        $warnings = array();
        $status = 1;
        $query = array();
        $null_value = array();
        $result_dep = array();
        $microtime_start = microtime(true);
        try {
            SapPositionFull::deleteAll();
            /**
             * Подключение  к базе Oracle
             */
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            /******************* Запрос на получение данных о id и наименовании должностей *******************/
            $query = oci_parse($conn_oracle, 'SELECT SHORT, STEXT FROM SPR_PROF_VIEW where STEXT is not null');
            oci_execute($query);
            $warnings[] = 'copyDataOraclePosition. Выполнил запрос ' . $duration_method = round(microtime(true) - $microtime_start, 6);

            /******************* Построчный перебор данных из Oracle *******************/
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //oci_fetch_array разделяет строки запроса, преобразуя их в ассоциативный массив
            {
                /******************* Смена кодировки - в зависимости от кодировки данных в Oracle *******************/
                //  $rows = mb_convert_encoding($row, 'UTF-8', 'windows-1251');
                //             $rows['status']=$num_sync;

                if (!empty($row['SHORT']) || $row['STEXT'] === ' ') {
                    $query_result['SHORT'] = (int)$row['SHORT'];
                    $query_result['STEXT'] = $row['STEXT'];
                    if (strlen($query_result['SHORT']) > 6) {
                        $query_result['qualification'] = (int)substr($row['SHORT'], -2);
                    } else {
                        $code = $query_result['SHORT'];
                        $warnings[] = "Код должности $code < 6 символов";
                    }
                    $array_query[] = $query_result;
                } else {
                    $null_value[] = $row;
                }

            }
            $result_dep = $array_query;
            $result_dep = ArrayHelper::index($result_dep, 'SHORT');
            $warnings[] = 'copyDataOraclePosition. Начал добавлять данные в MySQL';
            $insert_rows = Yii::$app->db->createCommand()->batchInsert('sap_position_full',
                ['STELL', 'STEXT', 'qualification'],
                $result_dep)->execute();
            $warnings[] = 'copyDataOraclePosition добавил данные ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            if ($insert_rows !== 0) {
                $status = 1;
            } else {

                throw new Exception('copyDataOraclePosition. Ошибка добавления записи');
            }
            $nul = ' ';
            $status = 1;
            $warnings[] = 'copyDataOraclePosition закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $nul_val = Yii::$app->db->createCommand()->delete('sap_position_full', ['STELL' => $nul])->execute();
            $warnings[] = $nul_val;

        } catch (Throwable $ex) {
            $errors[] = 'copyDataOraclePosition. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => $result_dep, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Метод insertDataOracleToMySQLPosition() - копирование справочника должностей из интеграциогнной таблицы в рабочую базу
     * @return array - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 16:21
     */
    public function insertDataOracleToMySQLPosition()
    {
        $errors = array();
        $query_result = array();
        $result = array();
        $warnings = array();
        $insert = array();
        $status = 1;
        $query = array();
        $result_dep = array();
        $microtime_start = microtime(true);
        try {
            Position1::deleteAll();
            /******************* Получение данных из таблицы sap_position_full *******************/
            $query_result = SapPositionFull::find()
                ->select(['STELL', 'STEXT', 'qualification'])
                ->distinct()
                ->asArray()
                ->all();
            $duration_method = round(microtime(true) - $microtime_start, 6);

            /******************* Помещение данных в таблицу position1 *******************/
            $insert = Yii::$app->db->createCommand()->batchInsert('position1', ['id', 'title', 'qualification'], $query_result)->execute();
        } catch (Throwable $ex) {
            $errors[] = 'insertDataOracleToMySQLPosition. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => $query_result, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Метод partUpdateDataOraclePositionSecondInsert() - Копирование обновлений в справочнике должностей из Oracle в интеграционную таблицу в MySQL
     * @return array - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 24.07.2019 15:46ad
     */
    public static function partUpdateDataOraclePositionSecondInsert()
    {
        $errors = array();
        $query_result = array();
        $result = array();
        $warnings = array();
        $status = 1;
        $num_sync = 0;
        $add_rows = array();
        $microtime_start = microtime(true);
        try {
            /****************** Получение номера синхронизации из таблицы в MySQL ******************/
            $max_value = SapPositionUpdate::find()
                ->max('num_sync');
            if ($max_value === NULL) {
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }
            /****************** Вычисление даты последнего обновления ******************/
            $new_data_to_update = SapPositionUpdate::find()
                ->select('sap_position_update.date_modified')
                ->groupby('sap_position_update.date_modified')
                ->max('date_modified');
            if ($new_data_to_update != null) {
                $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));
            } else {
                $new_data_to_update = '2019-08-31';
                $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));
            }
            $check = SapPositionUpdate::find()
                ->select(['status'])
                ->where(['status' => 0])
                ->all();
            if ($check == null) {
                SapPositionUpdate::deleteAll();
            } else {
                throw new Exception('partUpdateDataOraclePositionSecondInsert. Есть не завершенные обновления по должностям. СИнхронизация остановлена');
            }
            /****************** Подключение  к базе Oracle ******************/
            $conn_oracle = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHPROD . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings [] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            /****************** Создание и выполнение запроса ******************/
            $query = oci_parse($conn_oracle, "SELECT SHORT, STEXT, 
                                TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED  FROM AMICUM.spr_prof_view where SHORT is not null and STEXT is not null and date_modified >TO_DATE('$new_data_to_update','YYYY-MM-DD HH24:MI:SS')");   //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
//            $query = oci_parse($conn_oracle, "SELECT SHORT, STEXT, DATE_MODIFIED  FROM AMICUM.spr_prof_view where SHORT is not null and STEXT is not null ");   //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
            oci_execute($query);                                                                                         //выполнение запроса
            $warnings[] = 'partUpdateDataOraclePositionSecondInsert. Выполнил запрос ' . $duration_method = round(microtime(true) - $microtime_start, 6);

            /****************** Цикл по строкам запроса. Смена кодировки и индексация массива ******************/
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                         //цикл по массиву строк запроса
            {
                if (!empty($row['SHORT']) || $row['STEXT'] === ' ') {
                    $row['SHORT'] = (int)$row['SHORT'];
                    if (strlen($row['SHORT']) > 6) {
                        $row['qualification'] = (int)substr($row['SHORT'], -2);
                        $row['num_sync'] = $num_sync;
                        $query_result[] = $row;
                    } else {
                        $code = $row['SHORT'];
                        $warnings[] = "Код должности $code < 6 символов";
                    }
                }

//                $query_result[] = $row;
            }
            $warnings[] = 'partUpdateDataOraclePositionSecondInsert. Проиндексировал выполненный запрос';
            $insert_to_update = Yii::$app->db->createCommand()->batchInsert('sap_position_update', ['STELL', 'STEXT', 'date_modified', 'qualification', 'num_sync'], $query_result)->execute();
            if ($insert_to_update != null) {
                $warnings[] = "partUpdateDataOraclePositionSecondInsert добавил $insert_to_update записей в sap_position_update " . $duration_method = round(microtime(true) - $microtime_start, 6);
            }
        } catch (Throwable $ex) {
            $errors[] = 'partUpdateDataOraclePositionSecondInsert. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = ['Из Oracle в MySQL: ' => $query_result];
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод partUpdateDataOraclePositionSecondUpdate() - обновление справочника должностей в рабочей базе
     * @return array  - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 25.07.2019 8:03
     */
    public static function partUpdateDataOraclePositionSecondUpdate()
    {
        $errors = array();
        $query_result = array();
        $result = array();
        $warnings = array();
        $status = 1;
        $num_sync = 0;
        $min_num_sync = 0;
        $add_rows = array();
        $element_id = null;
        $microtime_start = microtime(true);
        try {
            $num_sync = SapPositionUpdate::find()
                ->max('num_sync');
            $get_min_num_sync = Yii::$app->db->createCommand('SELECT min(num_sync) FROM sap_position_update where status is NULL')->queryScalar();
            // поиск ошибочных синхронизаций

//            $warnings[] = "partUpdateDataOraclePositionSecondUpdate. Прошел проверку на ошибочные записи";
            // поиск минимального номера синхронизации
            $now_sync = SapPositionUpdate::find()
                ->select(['min(num_sync)'])
                ->where(['is', 'status', new Expression('NULL')])
                ->limit(1)
                ->asArray()
                ->scalar();
            $duration[] = 'partUpdateDataOraclePositionSecondUpdate подготовил синхронизацию - ее настройки ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "partUpdateDataOraclePositionSecondUpdate. Нашли минимальный номер синхронизации" . $now_sync;
            // получаем все записи по одной транзакции на синхронизацию
            $data_for_insert = SapPositionUpdate::find()
                ->where(['status' => StatusEnumController::SET_NULL])
                ->andWhere(['num_sync' => $now_sync])
                ->asArray()
                ->all();
            $warnings[] = "partUpdateDataOraclePositionSecondUpdate. Получили данные по конкретной транзакции на синхронизацию: " . count($data_for_insert);
            $iterator_count = 1;
            foreach ($data_for_insert as $item) {
                $update_status = SapPositionUpdate::findOne(['id' => $item['id']]);
                $update = StatusEnumController::NOT_DONE;
                if ($update_status) {
                    $update_status->status = $update;
                    if ($update_status->save()) {
                        $warnings[] = "partUpdateDataOraclePositionSecondUpdate. Обновил статус в sap_position_update на '0' $num_sync  " . $duration_method = round(microtime(true) - $microtime_start, 6);
                    } else {
                        throw new Exception('partUpdateDataOraclePositionSecondUpdate. Ошибка сохранения модели SapPositionUpdate');
                    }
                }
                /******************* Поиск записи для обновления  *******************/
                $element = Position::findOne(['id' => $item['STELL']]);
                if (!$element) {
                    $element = new Position();
                }
                $element->id = $item['STELL'];
                $element->title = $item['STEXT'];
                $element->qualification = $item['qualification'];
                if ($element->save()) {
                    $element_id = $element->id;
                    $warnings[] = "partUpdateDataOraclePositionSecondUpdate. Добавлена запись $num_sync " . $duration_method = round(microtime(true) - $microtime_start, 6);
                } else {
                    $errors[] = $item['STELL'];
                    throw new Exception('partUpdateDataOraclePositionSecondUpdate. Запись с таким номером не добавлена');

                }
                /******************* Обновление статуса после успешного изменения записи в справочнике должностей *******************/
                $update_status = SapPositionUpdate::findOne(['id' => $item['id']]);
                $update = StatusEnumController::DONE;
                if ($update_status) {
                    $update_status->status = $update;
                    if ($update_status->save()) {
                        $warnings[] = "partUpdateDataOraclePositionSecondUpdate. Обновил статус в sap_position_update на '1' $num_sync  " . $duration_method = round(microtime(true) - $microtime_start, 6);
                    }
                } else {
                    throw new Exception('partUpdateDataOraclePositionSecondUpdate. Запись с таким номером синхрониации отсутствует');
                }
            }
            $warnings[] = 'partUpdateDataOraclePositionSecondUpdate закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $ex) {
            $errors[] = 'partUpdateDataOraclePositionSecondUpdate. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод initializationTablesOracle() - очищает таблицы position_full и position_update в Oracle и заполняет их несколькими строками тестовых данных
     * @return array
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 25.07.2019 10:07
     */
    public function initializationTablesOracle()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $status = 1;
        try {

            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings [] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            /******************* Очищение и заполнение таблиц в Oracle *******************/
            $query_del = oci_parse($conn_oracle, 'TRUNCATE TABLE position_full');   //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
            oci_execute($query_del);
            $query_del = oci_parse($conn_oracle, 'TRUNCATE TABLE position_update');   //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
            oci_execute($query_del);
            $warnings[] = 'initializationTablesOracle. Таблицы position_full и position_update в Oracle очищены';
            $query_insert = oci_parse($conn_oracle, "INSERT ALL INTO position_full (STELL, STEXT) VALUES (4012548, 'Агломератчик ') INTO position_full (STELL, STEXT) VALUES (5012548, 'Вальцовщик') INTO position_full (STELL, STEXT) VALUES (6012548, 'Концентраторщик') INTO position_full (STELL, STEXT) VALUES (7012548, 'Монтировщик шин') SELECT 1 FROM DUAL");
            oci_execute($query_insert);
            $query_param_values [] = "4012548, 'Испытатель-взрывник'";
            foreach ($query_param_values as $query_param_value) {
                $query_param_value = mb_convert_encoding($query_param_value, 'utf-8', mb_detect_encoding($query_param_value));
                $query_insert = oci_parse($conn_oracle, "INSERT ALL INTO position_update (STELL, STEXT) VALUES ($query_param_value) SELECT 1 FROM DUAL");
                oci_execute($query_insert);
            }
            $warnings[] = 'initializationTablesOracle. Таблицы position_full и position_update в Oracle наполнены тестовыми данными';
            $query_del = oci_parse($conn_oracle, 'select created_by from amicum.HCM_STRUCT_OBJID_VIEW');   //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
            oci_execute($query_del);
            while ($row = oci_fetch_array($query_del, OCI_ASSOC + OCI_RETURN_NULLS))                                //пробегаемся по массиву строк
            {
                $query_result[] = $row;
            }
            $result_dep = $query_result;
            /******************* Блок зачистки таблиц в MySQL *******************/
            SapPositionFull::deleteAll();
            SapPositionUpdate::deleteAll();
            Position1::deleteAll();
        } catch (Throwable $ex) {
            $errors[] = 'initializationTablesOracle. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод addNewDataToOracle() - Добавляет новые строки в таблицу position_update в Oracle
     * @return array
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 25.07.2019 13:50
     */
    public function addNewDataToOracle()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $status = 1;
        $num_sync = 0;
        try {
            $max_value = SapPositionUpdate::find()->max('num_sync');
            $num_sync = $max_value + 1;

            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings [] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $query_del = oci_parse($conn_oracle, 'TRUNCATE TABLE position_update');                                     //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
            oci_execute($query_del);
            $query_param_values [] = "5012548, 'Дозиметрист $num_sync '";
            $query_param_values [] = "5012549, 'Дозировщик $num_sync '";
            $query_param_values [] = "6012549, 'Зуборезчик $num_sync '";
            foreach ($query_param_values as $query_param_value) {
                $query_param_value = mb_convert_encoding($query_param_value, 'utf-8', mb_detect_encoding($query_param_value));
                $query_insert = oci_parse($conn_oracle, "INSERT ALL INTO position_update (STELL, STEXT) VALUES ($query_param_value) SELECT 1 FROM DUAL");
                oci_execute($query_insert);
            }
        } catch (Throwable $ex) {
            $errors[] = 'addNewDataToOracle. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }

    /******************* РАБОТА С КОМПАНИЯМИ И ПОДРАЗДЕЛЕНИЯМИ (company & department) *******************/

    /**
     * Метод partCompanyDepartmentInsert() - копирование таблицы обновлений company_department_update из Oracle в sap_company_update MySQL
     * @array  - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 8:24
     */
    public static function partCompanyDepartmentInsert()
    {
        $errors = array();
        $debug = array();
        $result = array();
        $warnings = array();
        $status = 1;
        $microtime_start = microtime(true);
        try {
//            SapCompanyUpdate::deleteAll();
            //           $conn_oracle = oci_connect('SYSTEM', 'Administrator5', 'XE', 'AL32UTF8');
            $conn_oracle = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHPROD . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings [] = 'Соединение с Oracle не выполнено';                                                      //параметр соединения к базе данных если коннекшена
            } else {
                $warnings [] = 'Соединение с Oracle установлено';                                                       //параметр успешного соединения
            }
            /******************* получение номера синхронизации *******************/
            $max_value = SapCompanyUpdate::find()
                ->max('num_sync');
            if ($max_value === NULL) {
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }
            $min_num_sync = SapCompanyUpdate::find()//поиск минимального номера синхронизации
            ->min('num_sync');
            $check = SapCompanyUpdate::find()
                ->select(['status'])
                ->where(['status' => 0])
                ->all();
            if ($check == null) {
//                SapCompanyUpdate::deleteAll();
            } else {
                throw new Exception('partCompanyDepartmentInsert. Есть не завершенные обновления по должностям. СИнхронизация остановлена');
            }
            /******************* Вычисление даты последнего обновления *******************/
            $new_data_to_update = SapCompanyUpdate::find()
                ->select('date_modified')
                ->groupby('date_modified')
                ->max('date_modified');
            $new_data_to_update_filter = "";
            if ($new_data_to_update != null) {
                $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));
            } else {
                $new_data_to_update = '2014-05-12';
                $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));
                $new_data_to_update_filter = " where DATE_CREATED> TO_DATE('$new_data_to_update','YYYY-MM-DD HH24:MI:SS')";
            }

//            $get_full_data = oci_parse($conn_oracle, "select ORGANIZATION_ID, DESCRIPTION, PARENT_ORGANIZATION_ID, DATE_CREATED from ORGANIZATION_VIEW");
            $get_full_data = oci_parse($conn_oracle, "select ORGANIZATION_ID, DESCRIPTION, PARENT_ORGANIZATION_ID, DATE_CREATED from AMICUM.ORGANIZATION_VIEW " . $new_data_to_update_filter);
            oci_execute($get_full_data);
            while ($row = oci_fetch_array($get_full_data, OCI_ASSOC + OCI_RETURN_NULLS))                                //пробегаемся по массиву строк
            {
                $search = '.';
                $replace = '-';
                $subrow_start = substr($row['DATE_CREATED'], 0, 7);
                $subrow_end = substr($row['DATE_CREATED'], 7);
                $row['DATE_CREATED'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['DATE_CREATED']);
                $row['DATE_CREATED'] = date("Y-m-d", strtotime($test_row));
                $row['num_sync'] = $num_sync;
                $query_result[] = $row;
            }
            $result_dep = $query_result;

            /******************* Множественная вставка строк обновлений в таблицу sap_company_update в MySQL *******************/
            $my_sql_insert = Yii::$app->db->createCommand()->batchInsert('sap_company_update', ['id_comp', 'title', 'upper_company_id', 'date_modified', 'num_sync'], $result_dep)->execute();
            if ($my_sql_insert !== 0) {
                $warnings[] = 'partCompanyDepartmentInsert Добавил данные в таблицу sap_company_update  ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            } else {
                throw new Exception('partCompanyDepartmentInsert Не добавил данные в таблицу sap_company_update');
            }
        } catch (Throwable $ex) {
            $errors[] = 'partCompanyDepartmentInsert. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'debug' => $debug,
            'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод partCompanyDepartmentUpdate() - синхронизция данных между таблицами sap_company_update и sap_company, sap_department, company_department_1 в MySQL
     * @return array  - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 15:09
     */
    public static function partCompanyDepartmentUpdate()
    {
        $errors = array();
        $debug = array();
        $result = array();
        $warnings = array();
        $update_table = array();
        $update_element = array();
        $status = 1;
        $microtime_start = microtime(true);
        try {
            $max_value = SapCompanyUpdate::find()//получение максимального номера синхронизации
            ->max('num_sync');
            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }
            $min_num_sync = SapCompanyUpdate::find()//получение минимального номера синхронизации
            ->min('num_sync');
            $warnings[] = "partCompanyDepartmentUpdate. Прошел проверку на ошибочные записи";
            $now_sync = SapCompanyUpdate::find()
                ->select(['num_sync'])
                ->where(['is', 'status', new Expression('NULL')])
                ->limit(1)
                ->asArray()
                ->scalar();
            $data_for_insert = SapCompanyUpdate::find()
                ->where(['status' => StatusEnumController::SET_NULL])
                ->andWhere(['num_sync' => $now_sync])
                ->all();

            foreach ($data_for_insert as $item) {

                /******************* Отмечаем строку, как взятую в обработку  *******************/
                $update = SapCompanyUpdate::findOne(['id' => $item['id']]);
                $update_status = StatusEnumController::NOT_DONE;
                if ($update) {
                    $update->status = $update_status;
                    if ($update->save()) {
//                        $warnings[] = "partCompanyDepartmentUpdate. Обновил статус в sap_company_update на '0' $now_sync  " . $duration_method = round(microtime(true) - $microtime_start, 6);
                    } else {
                        throw new Exception('partCompanyDepartmentUpdate. Ошибка сохранения модели SapPositionUpdate');
                    }
                }
                /******************* Поиск схожего id в таблице company и при наличии обновление записи, а при отсутствии - добавление новой  *******************/
                $element = Company::findOne(['id' => $item['id_comp']]);
                if (!$element) {
                    $element = new Company();
                    $warnings[] = "Добавил запись {$item['id_comp']} в company";
                }
                $element->id = $item['id_comp'];
                $element->title = $item['title'];
                $element->upper_company_id = $item['upper_company_id'];
                if ($element->save()) {
                    $updated_id = $element->id;
//                    $warnings[] = "partCompanyDepartmentUpdate добавил запись в company1 на $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                } else {
                    $errors[] = $element->errors;
                    throw new Exception("partCompanyDepartmentUpdate. Запись с {$item['id_comp']}  номером не добавлена");
                }

                /******************* Поиск схожего id в таблице company_department и при наличии обновление записи, а при отсутствии - добавление новой  *******************/
                $com_dep = CompanyDepartment::findOne(['id' => $item['id_comp']]);
                if (!$com_dep) {
                    $com_dep = new CompanyDepartment();
                    $com_dep->department_type_id = DepartmentTypeEnum::OTHER;
//                    $warnings[] = "Добавил запись {$item['id_comp']} в company_department";
                }
                $com_dep->id = $item['id_comp'];
                $com_dep->department_id = 1;
                $com_dep->company_id = $item['id_comp'];

                if ($com_dep->save()) {
//                    $warnings[] = "partCompanyDepartmentUpdate добавил запись в company_department на $now_sync синхронизации ";
                } else {
                    $errors[] = $com_dep->errors;
                    throw new Exception("partCompanyDepartmentUpdate. Запись в company_department с {$item['id_comp']} номером не добавлена");
                }
                /******************* Обновление статуса записи в таблице sap_company_update *******************/
                $update->status = StatusEnumController::DONE;
                if ($update->save()) {
//                    $warnings[] = "partCompanyDepartmentUpdate обновил статус на '1' в $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                } else {
                    $errors[] = $update->errors;
                    throw new Exception('Статус записи не обновлён');
                }
            }
            $warnings[] = 'partCompanyDepartmentUpdate завершил работу ' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $ex) {
            $errors[] = 'partCompanyDepartmentUpdate. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'debug' => $debug,
            'warnings' => $warnings);
        return $result_main;
    }



    /******************* СИНХРОНИЗАЦИЯ СПРАВОЧНИКА СОТРУДНИКОВ *******************/

    /**
     * Метод copyDataOracleEmployee() - Полный перенос информации о сотрудниках из таблицы Oracle в таблицу MySQL
     * @array  - стандартный массив выходных данных
     * @package backend\controllers
     * @example http://localhost/admin/serviceamicum/synchronization/copy-data-oracle-employee
     *
     * @author Якимов М.Н.
     * Created date: on 15.01.2020 17:29
     */
    public function copyDataOracleEmployee()
    {
        // отладочная информация
        $microtime_start = microtime(true);                                                                  // начало выполнения скрипта

        // выходные массивы
        $errors = array();                                                                                              // блок ошибок
        $debug = array();                                                                                               // блок отладочной информации
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $query_result = array();
        $workers = array();
        $rows = array();
        $i = 0;
        $worker_array_id = array();

        try {

            // подготовка таблиц назначения к синхронизации
            SapEmployeeFull::deleteAll();
//            SapEmployeeUpdate::deleteAll();
            Employee1::deleteAll();
            Worker1::deleteAll();
            /**
             * Подключение  к базе Oracle
             */
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $query = oci_parse($conn_oracle, 'SELECT plans_stext, VORNA, MIDNM, NACHN, GESCH, GBDAT, PERNR, HIRE_DATE, FIRE_DATE, OSTEXT02, STELL, OOBJID02  FROM EMPLOYEE_FULL1 where GESCH is not NULL');   //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
            oci_execute($query);                                                                                         //выполнение запроса
            $warnings[] = 'copyDataOracleEmployee. Выполнил запрос ' . $duration_method = round(microtime(true) - $microtime_start, 6);

            /******************* Цикл по строкам запроса *******************/
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {

                $search = '.';
                $replace = '-';
                $subrow_start = substr($row['GBDAT'], 0, 6);
                $subrow_end = substr($row['GBDAT'], 6);
                if (substr($row['GBDAT'], 6) < 05) {
                    $row['GBDAT'] = $subrow_start . '20' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['GBDAT']);
                    $row['GBDAT'] = date("Y-m-d", strtotime($test_row));
                } else {
                    $row['GBDAT'] = $subrow_start . '19' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['GBDAT']);
                    $row['GBDAT'] = date("Y-m-d", strtotime($test_row));
                }
//                $row['GBDAT']="$dd".date("y-m-d", strtotime($row['GBDAT']));                                            //преобразование даты
//                $row['HIRE_DATE']=date("y-m-d", strtotime($row['HIRE_DATE']));


                $subrow_start = substr($row['HIRE_DATE'], 0, 6);
                $subrow_end = substr($row['HIRE_DATE'], 6);
                if (substr($row['HIRE_DATE'], 6) < 50) {
                    $row['HIRE_DATE'] = $subrow_start . '20' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['HIRE_DATE']);
                    $row['HIRE_DATE'] = date("Y-m-d", strtotime($test_row));
                } else {
                    $row['HIRE_DATE'] = $subrow_start . '19' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['HIRE_DATE']);
                    $row['HIRE_DATE'] = date("Y-m-d", strtotime($test_row));
                }

                $subrow_start = substr($row['FIRE_DATE'], 0, 6);
                $subrow_end = substr($row['FIRE_DATE'], 6);
                if ($subrow_end > 30 || $subrow_end !== 99) {
                    $row['FIRE_DATE'] = $subrow_start . '20' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['FIRE_DATE']);
                    $row['FIRE_DATE'] = date("Y-m-d", strtotime($test_row));
                } else {
                    $row['FIRE_DATE'] = $subrow_start . '19' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['FIRE_DATE']);
                    $row['FIRE_DATE'] = date("Y-m-d", strtotime($test_row));
                }
//                $row['FIRE_DATE']="$d".date("y-m-d", strtotime($row['FIRE_DATE']));
                $today = date("Y-m-d");
                if ($row['FIRE_DATE'] > $today) {
                    $query_result[] = $row;
                }
            }
//            $update_table = Yii::$app->db->createCommand("ALTER TABLE sap_employee_full AUTO_INCREMENT = 1")->execute();
            $workers = ArrayHelper::index($query_result, 'PERNR');                                                    //индексация массива по табельному номеру сотрудника
            $warnings[] = 'copyDataOracleEmployee подготовил массив для вставки в таблицу ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            /**
             * Вставка массива работников в таблицу
             */
            $add_rows = Yii::$app->db->createCommand()->batchInsert('sap_employee_full',                                    //добавление в базу по 150 строк
                ['plans_text', 'VORNA', 'MIDNM', 'NACHN', 'GESCH', 'GBDAT', 'PERNR', 'HIRE_DATE', 'FIRE_DATE', 'OSTEXT02', 'STELL', 'OBJID02'],
                $workers)->execute();
            if ($add_rows !== 0) {
                $warnings[] = 'copyDataOracleEmployee добавил запись';
                $status = 1;
            } else {
                throw new Exception('copyDataOracleEmployee. Ошибка добавления записи');
            }

            $warnings[] = 'copyDataOracleEmployee закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $ex) {
            $errors[] = 'copyDataOracleEmployee. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
//        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
//        Yii::$app->response->data = $result;
        return $result;
    }

    /**
     * Метод copyDataEmployeeToWorkerMySQL() - копирование данных из таблицы employee в worker1 и в worker_object1
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 06.08.2019 14:06
     */
    public function copyDataEmployeeToWorkerMySQL()
    {
        $errors = array();
        $query_result = array();
        $result = 0;
        $workers = array();
        $rows = array();
        $i = 0;
        $status = 1;
        $warnings = array();
        $workers_array = array();
        $microtime_start = microtime(true);
        try {
            /******************* Очистка таблиц *******************/
//            Worker1::deleteAll();
//            WorkerObject1::deleteAll();
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
//            $query = oci_parse($conn_oracle, 'SELECT VORNA, MIDNM, NACHN, GESCH, GBDAT, PERNR, HIRE_DATE, FIRE_DATE, OSTEXT02, PLANS_STEXT, STELL, OOBJID02  FROM EMPLOYEE_FULL1 where GESCH is not NULL');   //создание строки запроса табельного номера, даты начала и окончания работы, фио сотрудника, внешнего ключа должности и подразделения
//            oci_execute($query);                                                                                         //выполнение запроса
            $query = SapEmployeeFull::find()
                ->select(['VORNA', 'MIDNM', 'NACHN', 'GESCH', 'GBDAT', 'PERNR', 'HIRE_DATE', 'FIRE_DATE', 'OSTEXT02', 'PLANS_TEXT', 'OBJID02', 'STELL'])
                ->asArray()
                ->all();
            $warnings[] = 'copyDataEmployeeToWorkerMySQL. Выполнил запрос ' . $duration_method = round(microtime(true) - $microtime_start, 6);
//            Assistant::PrintR($query);die;

            /******************* Цикл по строкам запроса. Преобразование полей с датами, разделение поля STELL на номер должности и квалификаци *******************/
            foreach ($query as $row)                                       //цикл по массиву строк запроса
            {
////                Assistant::PrintR($row);die;
////                $d = '20';
////                $dd = '19';
////                $row['GBDAT']="$dd".date("y-m-d", strtotime($row['GBDAT']));
//////                $row['HIRE_DATE']=date("y-m-d", strtotime($row['HIRE_DATE']));
//                $search = '.';
//                $replace = '-';
//                $subrow_start = substr($row['HIRE_DATE'],0,6);
//                $subrow_end = substr($row['HIRE_DATE'],7);
//                $row['HIRE_DATE'] ='20'.$subrow_start.'-'.$subrow_end;
//                $test_row = str_replace($search, $replace, $row['HIRE_DATE'] );
//                $row['HIRE_DATE'] = date("Y-m-d", strtotime($test_row));
//                $row['FIRE_DATE']="$d".date("y-m-d", strtotime($row['FIRE_DATE']));
////
                $row['qualification'] = substr($row['STELL'], -2);
                $query_result[] = $row;
            }
            $workerss = ArrayHelper::index($query_result, 'PERNR');
//            Assistant::PrintR($workerss);die;// убирает дубли

            /******************* Формирование массива данных для вставки в таблицу Worker1 *******************/
            foreach ($workerss as $worker) {
                $emp_id = Employee1::findOne(['last_name' => $worker['NACHN'], 'first_name' => $worker['VORNA'], 'patronymic' => $worker['MIDNM']]);
                $worker_array_id['employee_id'] = $emp_id['id'];
                $pos_id = Position1::findOne(['id' => $worker['STELL']]);
//                Assistant::PrintR(gettype($pos_id));die;
                $worker_array_id['position_id'] = $pos_id['id'];
                $comp_dep_id = CompanyDepartment1::findOne(['company_id' => $worker['OBJID02']]);
                $worker_array_id['company_department_id'] = $comp_dep_id['id'];
                $worker_array_id['tabel_number'] = $worker['PERNR'];
                $worker_array_id['HIRE_DATE'] = $worker['HIRE_DATE'];
                $worker_array_id['FIRE_DATE'] = $worker['FIRE_DATE'];
                $workers_array[] = $worker_array_id;
            }
//            foreach ($workers_array as $item)
//            {
//                $insert = new Worker1();
//                $insert->employee_id = $item['employee_id'];
//                $insert->position_id = $item['position_id'];
//                $insert->company_department_id = $item['company_department_id'];
//                $insert->tabel_number = $item['tabel_number'];
//                $insert->date_start = $item['HIRE_DATE'];
//                $insert->date_end = $item['FIRE_DATE'];
//                $insert->mine_id = 1;
//                $insert->save();
//            }
//            Assistant::PrintR($workers_array);die;

            /******************* Вставка массива данных в таблицу Worker1 *******************/
            $insert = Yii::$app->db->createCommand()->batchInsert('worker1', ['employee_id', 'position_id', 'company_department_id', 'tabel_number', 'date_start', 'date_end'], $workers_array)->execute();

            /******************* Формирование массива данных для вставки в таблицу Worker_object1 *******************/
            foreach ($workerss as $worker_obj) {
                $worker_id = Worker1::findOne(['tabel_number' => $worker_obj['PERNR']]);
                $array_worker_obj['worker_id'] = $worker_id['id'];
                $array_worker_obj['object_id'] = 25;
                $array_obj[] = $array_worker_obj;
            }
//            foreach ($array_obj as $item)
//            {
//                $insert_obj = new WorkerObject1();
//                $insert_obj->worker_id = $item['worker_id'];
//                $insert_obj->object_id = $item['object_id'];
//                $insert_obj->save();
//            }
            /******************* Вставка массива данных в таблицу Worker_object1 *******************/

            $insert = Yii::$app->db->createCommand()->batchInsert('worker_object1', ['worker_id', 'object_id'], $array_obj)->execute();
            $warnings[] = 'copyDataEmployeeToWorkerMySQL закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);

        } catch (Throwable $ex) {
            $errors[] = 'copyDataEmployeeToWorkerMySQL. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Метод fullFromSapEmployeeToEmployee1() - перенос данных  в справчник сотрудников
     * @return array - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 02.08.2019 16:03
     */
    public function fullFromSapEmployeeToEmployee1()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $update_table = array();
        $update_element = array();
        $employees = array();
        $qualifications = array();
        $status = 1;
        $microtime_start = microtime(true);
        try {

            /******************* Получение данных для таблицы employee_1 *******************/
            $data_from_mysql = SapEmployeeFull::find()
                ->select(['VORNA', 'MIDNM', 'NACHN', 'GESCH', 'GBDAT'])
                ->asArray()
                ->all();

            /******************* Вставка массива данных в таблицу employee_1 *******************/
            $insert_data = Yii::$app->db->createCommand()->batchInsert('employee_1',                            //множественная вставка данных
                ['first_name', 'patronymic', 'last_name', 'gender', 'birthdate'],
                $data_from_mysql)->execute();
            $warnings[] = 'fullFromSapEmployeeToEmployee1 закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);

            $employee = SapEmployeeFull::find()
                ->select(['STELL', 'OSTEXT02', 'PLANS_TEXT'])
                ->asArray()
                ->all();
            foreach ($employee as $item) {
                $employees[] = $item['STELL'];
                if (isset($item['STELL'])) {
                    $qualifications[] = $item['STELL'];
                }
            }
        } catch (Throwable $ex) {
            $errors[] = 'fullFromSapEmployeeToEmployee1. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод copyPartUpdateEmployeeTable() - копирование данных об изменениях в справочнике сотрудников из Oracle  в MySQL
     *  0. Проверить наличие не завершенных синхронизаций, если такие есть, то окончание метода
     *  1. Найти последний номер завершенной синхронизации
     *  2. Найти последнюю дату модификации данных в САП, которая есть в амикумме, что бы с нее начать получать новый список для вставки
     *  3. получить список людей из представления Оракл Contact_view ,
     *  4. Очистить промежуточную таблицу sap_employee_update системы амикум
     *  5. Обработать пол работника
     *  6. положить обработанные данные в промежуточную таблицу sap_employee_update системы амикум
     * @return array  - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Якимов М.Н.
     * Created date: on 15.01.2020 17:29
     */
    public function sapCopyEmployeeContactView()
    {

        // Стартовая отладочная информация
        $method_name = 'sapCopyEmployeeContactView';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // делаем проверку на наличие не завершенных синхронизаций
            $check = SapEmployeeUpdate::find()
                ->select(['status'])
                ->where(['status' => 0])
                ->all();
            if ($check == null) {
                //SapEmployeeUpdate::deleteAll();
            } else {
                throw new Exception($method_name . '. Есть не завершенные обновления по работникам. Синхронизация остановлена');
            }

            /** Отладка */
            $description = 'Сделал проверку на наличе незавершенных синхронизаций';                                                                      // описание текущей отладочной точки
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

            // ищем последний номер синхронизации
            $max_value = SapEmployeeUpdate::find()
                ->max('num_sync');
            if ($max_value === NULL) {
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }

            /** Отладка */
            $description = 'Нашел последний номер синхронизации';                                                                      // описание текущей отладочной точки
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

            // ищем последнюю дату модификации, что бы с нее начать загрузку данных
            $new_data_to_update = SapEmployeeUpdate::find()
                ->select('date_modified')
                ->orderBy('date_modified DESC')
                ->scalar();
            if (empty($new_data_to_update)) {
                $new_data_to_update = '2018-09-01';
            }
            $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));

            /** Отладка */
            $description = 'Нашел последнюю дату модификации записей работников';                                                                      // описание текущей отладочной точки
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

            // устанавливаем соединение с оракл
            $conn_oracle = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHPROD . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors[] = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                throw new Exception($method_name . '. Соединение с Oracle не выполнено');
            } else {
                $warnings [] = $method_name . 'Соединение с Oracle установлено';
            }

            // получаем данные о работниках
            $query = oci_parse($conn_oracle, "SELECT VORNA, MIDNM, NACHN, GESCH, 
                TO_CHAR(GBDAT, 'YYYY-MM-DD HH24:MI:SS') AS GBDAT, PERNR,
                TO_CHAR(HIRE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS HIRE_DATE,
                TO_CHAR(FIRE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS FIRE_DATE,
                OSTEXT02, PLANS_STEXT, STELL,OOBJID02, OOBJID, 
                TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED FROM amicum.CONTACT_VIEW where DATE_MODIFIED > TO_DATE('$new_data_to_update','YYYY-MM-DD HH24:MI:SS') ");
            oci_execute($query);                                                                                         //выполнение запроса

            /** Отладка */
            $description = 'Получил записи из ОРАКЛ для добавления';                                                                      // описание текущей отладочной точки
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

            // обрабатываем данные о работниках (делаем определение их пола)
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                if ($row['VORNA'] && $row['NACHN']) {
                    if ($row['GESCH'] == 1) {
                        $row['GESCH'] = 'М';
                    } else {
                        $row['GESCH'] = 'Ж';
                    }
                    $row['num_sync'] = $num_sync;
                    if ($row['STELL'] === ' ') {
                        $row['STELL'] = StatusEnumController::SET_NULL;
                    }
                    if ($row['HIRE_DATE'] == null) {
                        $row['HIRE_DATE'] = Assistant::GetDateNow();
                    }
                    $workers[] = $row;
                    $count_all++;
                } else {
                    $errors[] = $method_name . '. У работника нет Фамилии или Имени. Запись пропущена';
                }
            }

            /** Отладка */
            $description = 'Обработал пол работников';                                                                      // описание текущей отладочной точки
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

            /******************* Вставка массива с обновлениями в таблицу  sap_employee_update*******************/
            $add_rows = 0;
            if (isset($workers)) {
                $add_rows = Yii::$app->db->createCommand()->batchInsert('sap_employee_update',
                    ['VORNA', 'MIDNM', 'NACHN', 'GESCH', 'GBDAT', 'PERNR', 'HIRE_DATE', 'FIRE_DATE', 'OSTEXT02', 'PLANS_TEXT', 'STELL', 'OBJID02', 'OBJID', 'date_modified', 'num_sync'],
                    $workers)->execute();
                if ($add_rows !== 0) {
                    $warnings[] = $method_name . ' добавил запись';
                    $status = 1;
                } else {
                    throw new Exception($method_name . '. Ошибка добавления записи');
                }
            }
            /** Отладка */
            $description = 'Массово вставил данные В БД';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $add_rows . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод sapUpdateEmployee() - синхронизация сведений о работниках
     * алгоритм работы:
     * 1. Ищем последнюю синхронизацию
     * 2. Проверяем статус последней синхронизации если есть не законченные синхронизации то, останавливаемся, иначе выполняем скрипт (должно быть status = null)
     * 3. Получаем справочник должностей индекс по id- для того что бы получать сокращенное название должности
     * 4. Получаем справочник должностей индекс по названию - для того что бы получать айдишник должности работника
     * 5. Получаем справочник подразделений - для того что бы проверять наличие данного подразделение перед сохранением работника
     * 6. Помечаем обрабатываемую запись как взятую в работу - нужно для того, что бы остановить последующие синхронизации в случае ошибок при выполнении скрипта (status = 0)
     * 7. Сохраняем запись сотрудника (employee)
     * 8. ПРоверяем наличие должности в справочнике должностей, если нет то создаем и пополняем справочник должностей индексированных по названию, иначе просто берем айдишник должности
     * 9. Сохраняем работника (worker)
     * 10. Сохраняем конкретного работника запись worker_object
     * 11. Помечаем запись как сохраненную в случае успеха (status = 1)
     *
     * @return array - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     *
     * @author Якимов М.Н.
     * Created date: on 15.01.2020 17:29
     */
    public static function sapUpdateEmployee()
    {

        // Стартовая отладочная информация
        $method_name = 'sapUpdateEmployee';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */

            // поиск ошибочных синхронизаций
            $failed_sync = SapEmployeeUpdate::find()
                ->select(['num_sync'])
                ->where(['status' => 0])
                ->all();
            if ($failed_sync) {
                throw new Exception($method_name . '. Есть не завершенные обновления по персоналу. Синхронизация остановлена');
            }

            /** Отладка */
            $description = 'Выполнил проверку на незаконченные синхронизации';                                                                      // описание текущей отладочной точки
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

            // поиск минимального номера синхронизации
            $now_sync = SapEmployeeUpdate::find()
                ->select(['min(num_sync)'])
                ->where(['is', 'status', new Expression('NULL')])
                ->limit(1)
                ->asArray()
                ->scalar();

            /** Отладка */
            $description = 'Нашел последюю синхронизацию';                                                                      // описание текущей отладочной точки
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

            // готовим справончник должностей
            $position_spr = Position::find()
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$position_spr) {
                throw new Exception($method_name . '. Справочник должностей пуст');
            }

            /** Отладка */
            $description = 'Получил справочник должностей';                                                                      // описание текущей отладочной точки
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

            // готовим справочник должностей
            $position_spr_title = Position::find()
                ->indexBy('title')
                ->asArray()
                ->all();

            if (!$position_spr_title) {
                throw new Exception($method_name . '. Справочник должностей пуст');
            }

            /** Отладка */
            $description = 'Получил справчник должностей индексированный по названию';                                                                      // описание текущей отладочной точки
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

            // готовим справочник компаний
            $comp_dep_spr = CompanyDepartment::find()
                ->indexBy('id')
                ->asArray()
                ->all();
            if (!$comp_dep_spr) {
                throw new Exception($method_name . '. Справочник компаний CompanyDepartment пуст');
            }

            /** Отладка */
            $description = 'Получил список департаментов';                                                                      // описание текущей отладочной точки
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

            // получаем список сведений о рабониках для синхронизации
            $data_for_insert = SapEmployeeUpdate::find()
                ->where(['status' => StatusEnumController::SET_NULL])
                ->andWhere(['num_sync' => $now_sync])
                ->orderBy(['date_modified' => SORT_ASC])
                ->asArray()
                ->all();

            /** Отладка */
            $description = 'Получил список сведений о рабониках для синхронизации';                                                                      // описание текущей отладочной точки
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

            // начинаем синхронизацию Баз Данных
            foreach ($data_for_insert as $item) {
                // помечаем текущую обрабатываемую запись как взятую в обработку
                $update_status = SapEmployeeUpdate::findOne(['id' => $item['id']]);
                if (!$update_status) {
                    throw new Exception($method_name . '. Текущая синхронизируемая запись не найдена');
                }

                $update_status->status = StatusEnumController::NOT_DONE;
                if (!$update_status->save()) {
                    $errors[] = $update_status->errors;
                    throw new Exception($method_name . '. Ошибка сохранения модели SapEmployeeUpdate');
                }

                $tabel_number = (int)$item['PERNR'];
                if ($tabel_number == 0) {
                    throw new Exception($method_name . '. При синхронизации табельный номер оказался текстом');
                }
                /******************* Обновление строк в таблице Employee1 *******************/
                //ищем человека по табельному номеру
                $employee = Employee::findOne(['id' => $tabel_number]);
                if (!$employee) {
                    $employee = new Employee();
//                    $warnings[] = "новый сотрудник" . $tabel_number;
                }
                $employee->id = $tabel_number;
                $employee->last_name = $item['NACHN'];
                $employee->first_name = $item['VORNA'];
                $employee->patronymic = $item['MIDNM'];
                $employee->birthdate = $item['GBDAT'];
                if ($item['GESCH'] != NULL) {
                    $employee->gender = $item['GESCH'];
                } else {
                    $employee->gender = 'М';
                }
                if ($employee->save()) {
//                    $warnings[] = "partUpdateEmployee обновил запись в Employee $tabel_number  синхронизации $now_sync";
                } else {
                    $errors[] = $employee->errors;
                    throw new Exception($method_name . ". Запись с номером работника $tabel_number не обновлена");
                }

                /******************* Обновление/проверка строк в таблице должностей Position *******************/
                // проверка наличия должностей в справочнике
                if (isset($position_spr_title[$item['PLANS_TEXT']])) {
                    $position_id = $position_spr_title[$item['PLANS_TEXT']]['id'];
                } else if ($item['STELL'] and $item['PLANS_TEXT']) {
                    $position_new = new Position();
                    $position_new->title = $item['PLANS_TEXT'];
                    if ($item['STELL'] != null and isset($position_spr[$item['STELL']])) {
                        $position_new->short_title = $position_spr[$item['STELL']]['title'];
                    }
                    if ($item['STELL'] != null and strlen($item['STELL']) > 6) {
                        $position_new->qualification = (string)substr($item['STELL'], -2);                     // квалификация должности
                    }
                    if ($position_new->save()) {
                        $position_new->refresh();
//                    $warnings[] = "partUpdateEmployee обновил запись в Worker $tabel_number  синхронизация $now_sync";
                    } else {
                        $errors[] = $position_new->errors;
                        throw new Exception($method_name . '. Запись с таким номером не обновлена ' . $item['PERNR']);
                    }
                    $position_spr_title[$item['PLANS_TEXT']]['id'] = $position_new->id;
                    $position_id = $position_new->id;
                } else {
                    $position_id = 1;
                }

                /******************* Обновление строк в таблице Worker1 *******************/
                // ищем работника синхронизации
                $updated_worker = Worker::findOne(['id' => $tabel_number]);
                if (!$updated_worker) {
                    $updated_worker = new Worker();
//                    $warnings[] = "новый работник" . $tabel_number;
                }
                $updated_worker->id = $tabel_number;
                $updated_worker->employee_id = $tabel_number;
                $updated_worker->tabel_number = (string)$tabel_number;
                $updated_worker->position_id = $position_id;

                if (!isset($comp_dep_spr[$item['OBJID02']])) {
                    throw new Exception($method_name . ". Не существует такой компании " . $item['OBJID02']);
                }

                if (!isset($comp_dep_spr[$item['OBJID']])) {
                    $comp_dep_id = $item['OBJID02'];
                } else {
                    $comp_dep_id = $item['OBJID'];
                }
                $updated_worker->company_department_id = $comp_dep_id;
                $updated_worker->date_start = $item['HIRE_DATE'];
                $updated_worker->date_end = $item['FIRE_DATE'];
                if ($updated_worker->save()) {
//                    $warnings[] = "partUpdateEmployee обновил запись в Worker $tabel_number  синхронизация $now_sync";
                } else {
                    $errors[] = $updated_worker->errors;
                    throw new Exception($method_name . '. Запись с таким номером не обновлена ' . $item['PERNR']);
                }


                /******************* Обновление строк в таблице WorkerObject1 *******************/
                $updated_obj = WorkerObject::findOne(['worker_id' => $tabel_number]);
                if (!isset($updated_obj)) {
                    $updated_obj = new WorkerObject();
                }
                $updated_obj->id = $tabel_number;
                $updated_obj->worker_id = $tabel_number;
                $updated_obj->object_id = 25;
                if ($updated_obj->save()) {
//                    $warnings[] = "partUpdateEmployee добавил запись в WorkerObject $tabel_number  синхронизации  $now_sync";
                } else {
                    $errors[] = $updated_obj->errors;
                    throw new Exception($method_name . ". Запись с таким номером не добавлена" . $item['PERNR']);
                }

                /******************* Обновление статуса синхронизации для каждой строки в таблице sap_emoloyee_update *******************/

                // пометили запись как обновленную
                $update_status->status = StatusEnumController::DONE;
                if ($update_status->save()) {
//                    $warnings[] = "partUpdateEmployee. Итератор $iterator_count обновил статус на '1' в  синхронизации $now_sync номер записи " . $update_status->id;
                } else {
                    $errors[] = $employee->errors;
                    $errors[] = $item['PERNR'];
                    throw new Exception($method_name . "Статус записи $update_status->id не обновлён");
                }
                $number_row_affected++;
            }

            /** Отладка */
            $description = 'Закончил синхронизацию';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $number_row_affected . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */
            /** Метод окончание */

            HandbookCachedController::clearWorkerCache();
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }


    /**
     * Метод partUpdateEmployeeChangeId() - частичная загрузка данных из таблицы обновлений в спровочнике MySQL
     * @return array - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 02.08.2019 16:07
     */
    public static function partUpdateEmployeeChangeId()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $update_table = array();
        $update_element = array();
        $now_sync = 0;
        $status = 1;
        $microtime_start = microtime(true);
        $session = Yii::$app->session;
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $max_value = SapEmployeeUpdate::find()//получение максимального номера синхронизации
            ->max('num_sync');
            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }
            $min_num_sync = SapEmployeeUpdate::find()//получение минимального номера синхронизации
            ->min('num_sync');
            $get_min_num_sync = Yii::$app->db->createCommand('SELECT min(num_sync) FROM sap_position_update where status is NULL')->queryScalar();
            $now_sync = SapEmployeeUpdate::find()
                ->select(['num_sync'])
                ->where(['is', 'status', new Expression('NULL')])
                ->asArray()
                ->scalar();
            $data_for_insert = SapEmployeeUpdate::find()
                ->where(['status' => 2])
                ->asArray()
                ->all();

            $null_stell = SapEmployeeUpdate::find()
                ->where(['is', 'STELL', new Expression('NULL')])
                ->asArray()
                ->all();
            foreach ($data_for_insert as $item) {
                $update_status = SapEmployeeUpdate::findOne(['PERNR' => $item['PERNR'], 'num_sync' => $now_sync]);
                $update = StatusEnumController::NOT_DONE;
                if ($update_status) {
                    $update_status->status = $update;
                    if ($update_status->save()) {
                        $warnings[] = "partUpdateEmployeeChangeId обновил статус в sap_employee_update на 0 на $now_sync синхронизаци  " . $duration_method = round(microtime(true) - $microtime_start, 6);
                    } else {
                        $errors[] = $update_status->errors;
                        throw new Exception('partUpdateEmployeeChangeId. Ошибка сохранения модели SapEmployeeUpdate');
                    }
                }
                $employee = Worker::findOne(['tabel_number' => $item['PERNR']]);
                $extra = array();
                $extra_id = Worker::find()
                    ->where(['tabel_number' => $item['PERNR']])
                    ->andWhere(['!=', 'id', $item['PERNR']])
                    ->all();
                $warnings[] = $extra_id;
//                throw new \Exception("partUpdateEmployeeChangeId. стоп");
                if ($extra_id != null) {
                    $extra[] = $extra_id;
                    $insert_extra = Yii::$app->db->createCommand()->batchInsert('worker_extra', ['id', 'employee_id', 'position_id', 'company_department_id', 'tabel_number', 'date_start', 'date_end', 'mine_id', 'vgk'], $extra)->execute();
                    if ($insert_extra !== null) {
                        $warnings[] = 'Добавил в базу лишнего';
                    }
                    if ($extra_id->delete()) {
                        $warnings[] = "Удалил {$insert_extra['id']} из таблицы worker";
                    } else {
                        $errors[] = $extra_id->errors;
                    }
                }
                if (!$employee) {
                    /******************* Добавление в таблицу Employee1 *******************/
                    $extra_employee = array();
                    $extra_emp = Employee::findOne(['id' => $item['PERNR'], 'last_name' !== $item['NACHN']]);
                    if (isset($extra_emp)) {
                        $extra_employee[] = $extra_emp;
                        $insert_extra_emp = Yii::$app->db->createCommand()->batchInsert('employee_extra', ['id', 'last_name', 'first_name', 'patronymic', 'gender', 'birthdate'], $extra_employee)->execute();
                        if ($insert_extra_emp !== null) {
                            $warnings[] = 'Добавил в базу лишнего';
                        }
                        if ($extra_emp->delete()) {
                            $warnings[] = "Удалил {$insert_extra_emp['id']}";
                        } else {
                            $errors[] = $extra_emp->errors;
                        }
                    }
//                    Assistant::PrintR($warnings);die;
                    $check = Employee::findOne(['id' => $item['PERNR'], 'last_name' => $item['NACHN']]);

                    if (!isset($check)) {
                        $updated = new Employee();
//                    $updated->PERNR = $item['PERNR'];
                        $updated->id = $item['PERNR'];
                        $updated->last_name = $item['NACHN'];
                        $updated->first_name = $item['VORNA'];
                        $updated->patronymic = $item['MIDNM'];
                        if ($item['GESCH'] != NULL) {
                            $updated->gender = $item['GESCH'];
                        } else {
                            $num = $item['PERNR'];
                            $warnings[] = "ДЛЯ $num УСТАНОВЛЕН  ПОЛ ПО УМОЛЧАНИЮ (2) ";
                            $updated->gender = '2';
                        }
                        $updated->birthdate = $item['GBDAT'];
                        if ($updated->save()) {
                            $updated_id = $updated->id;
                            $updated->refresh();
//                            $warnings[] = "partUpdateEmployeeChangeId добавил запись в Employee_1 на $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                        } else {
                            $errors[] = $updated->errors;
                            $errors[] = $item['PERNR'];
                            throw new Exception("partUpdateEmployeeChangeId. Запись с таким номером не добавлена");
                        }
                    }
                    /******************* Добавление в таблицу Worker *******************/
                    $updated_worker = new Worker();
                    $updated_worker->id = $item['PERNR'];
                    $employee_id = Employee::findOne(['id' => $item['PERNR']]);
                    $updated_worker->employee_id = $employee_id['id'];
                    if ($item['STELL'] !== NULL) {
                        $position_id = Position::findOne(['id' => $item['STELL']]);
                        $updated_worker->position_id = $position_id['id'];
                    } else {
                        $position_id = 1;
                        $updated_worker->position_id = $position_id;
                    }
                    $comp_dep_id = CompanyDepartment::findOne(['id' => $item['OBJID']]);
                    if (!isset($comp_dep_id)) {
                        $comp_dep_id = CompanyDepartment::findOne(['id' => $item['OBJID02']]);
                    } else {
                        $comp_dep_id = CompanyDepartment::findOne(['id' => $item['OBJID']]);
                    }
                    $updated_worker->company_department_id = $comp_dep_id['id'];
                    $updated_worker->tabel_number = $item['PERNR'];
                    $updated_worker->date_start = $item['HIRE_DATE'];
                    $updated_worker->date_end = $item['FIRE_DATE'];
//                        Assistant::PrintR($updated_worker);die;
                    if ($updated_worker->save()) {
                        $updated_id = $updated_worker->id;
                        //                       $warnings[] = "partUpdateEmployeeChangeId добавил запись в Worker1 на $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                    } else {
                        $errors[] = $updated_worker->errors;
                        $errors[] = $item['PERNR'];
                        throw new Exception("partUpdateEmployeeChangeId. Запись с таким номером не добавлена");
                    }

                    /******************* Добавление в таблицу Worker_object1 *******************/
                    $worker_id = Worker::findOne(['tabel_number' => $item['PERNR']]);
                    $worker_obj_id = WorkerObject::findOne(['worker_id' => $worker_id['id']]);
                    if (!isset($worker_obj_id)) {
                        $updated_obj = new WorkerObject();
                        $updated_obj->id = $worker_id['id'];
                        $updated_obj->worker_id = $worker_id['id'];
                        $updated_obj->object_id = 25;
                        if ($updated_obj->save()) {
                            $updated_id = $updated_obj->id;
                            //                           $warnings[] = "partUpdateEmployeeChangeId добавил запись в WorkerObject1 на $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                        } else {
                            $errors[] = $updated_obj->errors;
                            $errors[] = $item['PERNR'];

                            throw new Exception("partUpdateEmployeeChangeId. Запись с таким номером не добавлена");
                        }
                    }

                    if ($updated_obj !== null) {
                        $update_status = SapEmployeeUpdate::findOne(['PERNR' => $item['PERNR'], 'num_sync' => $now_sync]);
//                    Assistant::PrintR($update_status);die;
                        if ($update_status) {
                            $update_status->status = StatusEnumController::DONE;
                            if ($update_status->save()) {
//                                $warnings[] = "partUpdateEmployeeChangeId обновил статус на '1' в $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                            } else {
                                throw new Exception('Статус записи не обновлён');
                            }
                        } else {
                            $update_status->status = StatusEnumController::NOT_DONE;
                            if ($update_status->save()) {
                                $warnings[] = "partUpdateEmployeeChangeId обновил статус на '0' в $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                            } else {
                                $errors[] = $updated->errors;
                                $errors[] = $item['PERNR'];
                                throw new Exception('Статус записи не обновлён');
                            }
                        }
                    }
                } else {
                    /******************* Обновление строк в таблице Employee1 *******************/
                    $person = Worker::findOne(['tabel_number' => $item['PERNR']]);
                    $updated = Employee::findOne(['id' => $person['tabel_number']]);
                    if (isset($updated)) {
                        $update_l_name = $item['NACHN'];
                        $update_f_name = $item['VORNA'];
                        $update_patr = $item['MIDNM'];
                        $update_gen = $item['GESCH'];
                        $update_birth = $item['GBDAT'];
                        $update_id = $item['PERNR'];
                        $updated->id = $update_id;
                        $updated->last_name = $update_l_name;
                        $updated->first_name = $update_f_name;
                        $updated->patronymic = $update_patr;
                        $updated->birthdate = $update_birth;
                        if ($item['GESCH'] != NULL) {
                            $updated->gender = $item['GESCH'];
                        } else {
                            $num = $item['PERNR'];
                            //                           $warnings[] = "ДЛЯ $num УСТАНОВЛЕН  ПОЛ ПО УМОЛЧАНИЮ (2) ";
                            $updated->gender = '2';
                        }
                        if ($updated->save()) {
                            $warnings[] = "partUpdateEmployeeChangeId обновил запись в Employee_1 на $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                        } else {
                            $errors[] = $updated->errors;
                            $errors[] = $item['PERNR'];
                            throw new Exception('partUpdateEmployeeChangeId. Запись с таким номером не обновлена');
                        }
                    } else {
                        $new_empl = new Employee();
                        $update_l_name = $item['NACHN'];
                        $update_f_name = $item['VORNA'];
                        $update_patr = $item['MIDNM'];
                        $update_gen = $item['GESCH'];
                        $update_birth = $item['GBDAT'];
                        $update_id = $item['PERNR'];
                        $new_empl->id = $update_id;
                        $new_empl->last_name = $update_l_name;
                        $new_empl->first_name = $update_f_name;
                        $new_empl->patronymic = $update_patr;
                        $new_empl->birthdate = $update_birth;
//                    Assistant::PrintR($updated);die;

                        if ($item['GESCH'] != NULL) {
                            $new_empl->gender = $item['GESCH'];
                        } else {
                            $num = $item['PERNR'];
                            $warnings[] = "ДЛЯ $num УСТАНОВЛЕН  ПОЛ ПО УМОЛЧАНИЮ (2) ";
                            $new_empl->gender = '2';
                        }
                        if ($new_empl->save()) {
                            $warnings[] = "partUpdateEmployeeChangeId обновил запись в Employee_1 на $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                        } else {
                            $errors[] = $new_empl->errors;
                            $errors[] = $item['PERNR'];
                            throw new Exception('partUpdateEmployeeChangeId. Запись с таким номером не обновлена');
                        }
                    }
                    /******************* Обновление строк в таблице Worker1 *******************/
                    $updated_worker = Worker::findOne(['tabel_number' => $item['PERNR']]);
                    $find_obj = WorkerObject::findOne(['worker_id' => $updated_worker['tabel_number']]);
                    if (isset($find_obj)) {
                        $delete_tabel = Yii::$app->db->createCommand()->delete('worker_object', ['worker_id' => $updated_worker['tabel_number']])->execute();
                    }
                    $updated_worker->id = $item['PERNR'];
                    $employee_id = Employee::findOne(['id' => $item['PERNR']]);

                    $updated_worker->employee_id = $employee_id['id'];
                    if ($item['STELL'] !== NULL) {
                        $position_id = Position::findOne(['id' => $item['STELL']]);
                        $updated_worker->position_id = $position_id['id'];
                    } else {
//                    Assistant::PrintR(gettype($position_id['id']));die;
                        $updated_worker->position_id = 1;
                    }

                    $comp_dep_id = CompanyDepartment::findOne(['id' => $item['OBJID']]);
                    if (!isset($comp_dep_id)) {
                        $comp_dep_id = CompanyDepartment::findOne(['id' => $item['OBJID02']]);
                    } else {
                        $comp_dep_id = CompanyDepartment::findOne(['id' => $item['OBJID']]);
                    }
                    $updated_worker->company_department_id = $comp_dep_id['id'];
                    $updated_worker->date_end = $item['FIRE_DATE'];
                    $updated_worker->date_start = $item['HIRE_DATE'];
                    if ($updated_worker->save()) {
                    } else {
                        $errors[] = $updated_worker->errors;
                        $errors[] = $item['PERNR'];
                        throw new Exception('partUpdateEmployeeChangeId. Запись с таким номером не обновлена');
                    }

                    /******************* Обновление строк в таблице WorkerObject1 *******************/
                    $worker_obj_id = 0;
                    $worker_id = Worker::findOne(['tabel_number' => $item['PERNR']]);
                    $worker_obj_id = WorkerObject::findOne(['worker_id' => $worker_id['id']]);
                    if (!isset($worker_obj_id)) {
                        $updated_obj = new WorkerObject();
                        $updated_obj->id = $worker_id['id'];
                        $updated_obj->worker_id = $worker_id['id'];
                        $updated_obj->object_id = 25;
//                        Assistant::PrintR($updated_obj);die;
                        if ($updated_obj->save()) {
                            $updated_id = $updated_obj->id;
//                            $warnings[] = "partUpdateEmployeeChangeId добавил запись в WorkerObject1 на $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                        } else {
                            $errors[] = $updated_obj->errors;
                            $errors[] = $item['PERNR'];

                            throw new Exception("partUpdateEmployeeChangeId. Запись с таким номером не добавлена");
                        }
                        if ($updated_obj !== null) {
                            $update_status = SapEmployeeUpdate::findOne(['PERNR' => $item['PERNR'], 'num_sync' => $now_sync]);
                            if ($update_status) {
                                $update_status->status = StatusEnumController::DONE;
                                if ($update_status->save()) {
                                    //                                   $warnings[] = "partUpdateEmployeeChangeId обновил статус на '1' в $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                                } else {
                                    throw new Exception('Статус записи не обновлён');
                                }
                            } else {
                                $update_status->status = StatusEnumController::NOT_DONE;
                                if ($update_status->save()) {
                                    //                                  $warnings[] = "partUpdateEmployeeChangeId обновил статус на '0' в $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                                } else {
                                    $errors[] = $updated->errors;
                                    $errors[] = $item['PERNR'];
                                    throw new Exception('Статус записи не обновлён');
                                }
                            }
                        }
                    } else {
                        $worker_obj_id->id = $worker_id['id'];
                        $worker_obj_id->object_id = 25;
                        if ($worker_obj_id->save()) {
                            $updated_id = $worker_obj_id->id;
                            //                         $warnings[] = "partUpdateEmployeeChangeId добавил запись в WorkerObject1 на $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                        } else {
                            $errors[] = $worker_obj_id->errors;
                            $errors[] = $item['PERNR'];

                            throw new Exception("partUpdateEmployeeChangeId. Запись с таким номером не добавлена");
                        }
                        if ($worker_obj_id !== null) {
                            $update_status = SapEmployeeUpdate::findOne(['PERNR' => $item['PERNR'], 'num_sync' => $now_sync]);
                            if ($update_status) {
                                $update_status->status = StatusEnumController::DONE;
                                if ($update_status->save()) {
                                } else {
                                    throw new Exception('Статус записи не обновлён');
                                }
                            } else {
                                $update_status->status = StatusEnumController::NOT_DONE;
                                if ($update_status->save()) {
                                    $warnings[] = "partUpdateEmployeeChangeId обновил статус на '0' в $now_sync синхронизации " . $duration_method = round(microtime(true) - $microtime_start, 6);
                                } else {
                                    $errors[] = $updated->errors;
                                    $errors[] = $item['PERNR'];
                                    throw new Exception('Статус записи не обновлён');
                                }
                            }
                        }
                    }

                    $warnings[] = 'Прошелся по ' . $item['PERNR'] . ' табельному';

                }
                $warnings[] = 'partUpdateEmployeeChangeId закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);
                /***** Логирование в БД *****/
                $tabel_number = $session['userStaffNumber'];
//            $post = json_encode($post);
                $errors_insert = json_encode($errors);
                $result[] = $warnings;
                $duration_method = round(microtime(true) - $microtime_start, 6);                                              //расчет времени выполнения метода
                /*  LogAmicum::LogEventAmicum(                                                                                      //записываем в журнал сведения о выполнении метода
                      'WebsocketServerController/index',
                      date("y.m.d H:i:s"), $duration_method, json_encode($result), $errors_insert, $tabel_number);      */
            }

            HandbookCachedController::clearWorkerCache();
        } catch (Throwable $ex) {
            $errors[] = 'partUpdateEmployeeChangeId. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $errors[] = $ex->getFile();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }

    /******************* Справочник профессий *******************/


    /**
     * Метод copyUpdatesFromOracleToMySQL() - Копирование обновлений в справочнике профессий из Oracle в интеграционную таблицу в MySQL
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 05.08.2019 9:35
     */
    public function copyUpdatesFromOracleToMySQL()
    {
        $errors = array();
        $warnings = array();
        $status = array();
        $result = array();
        $query_result = array();
        $microtime_start = microtime(true);
        try {
            /******************* Получение номера синхронизации из таблицы в MySQL *******************/
            $max_value = SapRoleUpdate::find()
                ->max('num_sync');
            if ($max_value === NULL) {
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }
            $warnings['max_value'] = $max_value;
            $warnings['num_sync'] = $num_sync;
            /******************* Подключение к базе Oracle  *******************/
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings [] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            /******************* Создание и выполнение запроса *******************/
            $query = oci_parse($conn_oracle, 'SELECT id, title FROM role_update');
            oci_execute($query);
            $warnings[] = 'copyUpdatesFromOracleToMySQL. Выполнил запрос ' . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $ex) {
            $errors[] = 'copySapRoleToMySQLRole1. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;

    }

    /******************* Справочники в ЯГОК *******************/

    /******************* Должности  *******************/

    /**
     * Метод copyFromOracleToMySQL() - перенос данных из Oracle в MySQL таблицы sap_position_yagok, position1
     * @return array
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 08.08.2019 12:54
     */
    public function copyFromOracleToMySQLYagok()
    {
        $errors = array();
        $warnings = array();
        $status = array();
        $result = array();
        $query_result = array();
        $microtime_start = microtime(true);
        try {
//            Position1::deleteAll();
            SapPositionYagok::deleteAll();
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings [] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }

            $query = oci_parse($conn_oracle, 'SELECT POSITION FROM POSITION_YAGOK');
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $query_result[] = $row;
            }
            $query_result = ArrayHelper::index($query_result, 'POSITION');
            $insert = Yii::$app->db->createCommand()->batchInsert('sap_position_yagok', ['title'], $query_result)->execute();
            $warnings [] = 'copyFromOracleToMySQL поместил данные в sap_position_yagok';
            $from_sap_yagok = SapPositionYagok::find()->select(['title'])->asArray()->all();
            $insert_position = Yii::$app->db->createCommand()->batchInsert('position', ['title'], $from_sap_yagok)->execute();
            $warnings [] = 'copyFromOracleToMySQL поместил данные в position';
        } catch (Throwable $ex) {
            $errors[] = 'copySapRoleToMySQLRole1. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;

    }

    /******************* Подразделения *******************/

    /**
     * Метод getDataFromOracleForCompany() - вставка данных из таблицы sap_department_full в company_department_1, company1, department1
     * @return array - стандартный массив выходных данных
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 15:23
     */
    public function getDataFromOracleForCompanyYagok()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $status = 1;
        $result_dep = array();
        $updated = array();
        $get_full_data = array();
        $microtime_start = microtime(true);
        try {
            SapDepartmentFull::deleteAll();
//            Department1::deleteAll();
//            Company1::deleteAll();
//            CompanyDepartment1Y::deleteAll();
            /******************* Поключение к базе Oracle *******************/
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings [] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $get_full_data = oci_parse($conn_oracle, 'select ORGEDINC, ORGANIZAC from POSITION_YAGOK');
            oci_execute($get_full_data);
            while ($row = oci_fetch_array($get_full_data, OCI_ASSOC + OCI_RETURN_NULLS))                                //пробегаемся по массиву строк
            {
                $query_result[] = $row;
            }
            $result_dep = ArrayHelper::index($query_result, 'ORGEDINC');
            $insert = Yii::$app->db->createCommand()->batchInsert('amicum_yagok.sap_department_full', ['OBJID', 'STEXT'], $result_dep)->execute();
            if ($insert) {
                $warnings[] = 'getDataFromOracleForCompanyYagok добавил данные в таблицу sap_department_full ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            } else {
                $errors[] = $updated->errors;
                throw new Exception('getDataFromOracleForCompanyYagok не добавил данные в таблицу sap_department_full');
            }
            $comp_depart_data = SapDepartmentFull::find()
                ->select(['OBJID', 'STEXT'])
                ->where(['OBJID' => 20039527])
                ->asArray()
                ->all();
            $comp = Yii::$app->db->createCommand()->batchInsert('amicum_yagok.company', ['id', 'title'], $comp_depart_data)->execute();
            if ($comp) {
                $warnings[] = 'getDataFromOracleForCompanyYagok добавил данные в таблицу company1 ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            } else {
                $errors[] = $updated->errors;
                throw new Exception('getDataFromOracleForCompanyYagok не добавил данные в таблицу company1');
            }
            $depart = SapDepartmentFull::find()
                ->select(['OBJID', 'STEXT'])
                ->asArray()
                ->all();
            $insert_dep = Yii::$app->db->createCommand()->batchInsert('amicum_yagok.department', ['id', 'title'], $depart)->execute();
            if ($insert_dep) {
                $warnings[] = 'getDataFromOracleForCompanyYagok добавил данные в таблицу department1 ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            } else {
                $errors[] = $updated->errors;
                throw new Exception('getDataFromOracleForCompanyYagok не добавил данные в таблицу department1');
            }
            $com_dep = Department1::find()
                ->select(['id'])
                ->asArray()
                ->all();
            foreach ($com_dep as $item) {
                $item['company_id'] = 20039527;
                $com_dep_array[] = $item;
            }
            $comp_dep_data = Yii::$app->db->createCommand()->batchInsert('amicum_yagok.company_department', ['department_id', 'company_id'], $com_dep_array)->execute();
            if ($comp_dep_data) {
                $warnings[] = 'getDataFromOracleForCompanyYagok добавил данные в таблицу company_department1 ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            } else {
                $errors[] = $updated->errors;
                throw new Exception('getDataFromOracleForCompanyYagok не добавил данные в таблицу company_department1');
            }

            $warnings[] = 'getDataFromOracleForCompanyYagok закончил выполнять метод ' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $ex) {
            $errors[] = 'getDataFromOracleForCompany. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);

//        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
        return $result_main;
    }

    /******************* СИЗы *******************/
    /**
     * Метод copyFullDataSIZ() - копирование таблицы СИЗов из Oracle в MySQL
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 10:51
     * рефакторинг
     * описание структуры полей синхронизации IDB01.SO_AMICUM_NOMENCL_MV:
     * N_NOMENCL        идентификатор СИЗ            siz_id
     * NAME_NOMENCL    название номенклатуры СИЗ    siz_title
     * UNIT_ID            единицы измерения СИЗ        unit_id
     * TYPE_COST        тип СИЗ                        siz_kind_id
     * NAME_COST        название типа СИЗ            siz_kind_title
     * SIGN_WINTER        сезон носки сиз                season_id
     * история выдачи сиз IDB01.SO_AMICUM_WORK_WEAR_MV:
     * WORK_WEAR_ID    идентификатор СИЗ            siz_id
     * N_NOMENCL        название номенклатуры СИЗ    siz_title
     * TABN            табельный номер PERNR        worker_siz.worker_id
     * OBJID            ключ подразделения            company_department_id
     * DATE_GIVE        дата выдачи                    worker_siz.date_issue
     * NAME_SIZE        название размера СИЗ        worker_siz.size
     * DATE_RETURN        дата возврата сиз
     * DATE_WRITTEN    дата списания                worker_siz.date_write_off
     * SIGN_WRITTEN    признак списания            worker_siz.status_id
     * WORKING_LIFE    время/период носки сиз    Siz.wear_period
     * алгоритм работы:
     * 1. проверяем наличие соединения с ОРАКАЛ
     * 2. ПОлучаем последний номер синхровнизации
     * 3. получаем последнюю дату синхронизации
     *  очищаем справочник номенлатур, т.к. САП выгружает последний снимок полного справочника
     * 4. получаем из IDB01.SO_AMICUM_NOMENCL_MV справочника номенклатур СИЗ ОРАКАЛ данные
     * 5. Проверяем заполненность единиц измерения - если единицы измерения СИЗ в САП пустые, то устанавливаем ед.изм как прочее (79)
     * 6. адаптируем справочники сезонов САП и АМИКУМ если в сапе нет сезона или он равен 0 или 2, то мы ставим наш ключ  равный 5 (все сезоны)
     * 7. проверяем наличие заполненного вида СИЗ, если он пуст, то пишем айди 30100092 - СИЗ
     * 8. т.к. необходимые поля для справочника единиц измерения находтся в таблице носимых СИЗ Sap_Asu_Worker_Siz_Full, то получаем из нее данные (working_life) и делаем из этого справочник (индекс по полю N_NOMENCL)
     * 9. делаем массовую вставку в таблицу sap_asu_siz_full
     * Разработал Якимов М.Н.
     * Дата 25.01.2020
     */
    public static function copyFullDataSIZ()
    {
        // Стартовая отладочная информация
        $method_name = 'copyFullDataSIZ';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $query_result = array();

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $conn_oracle = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHPROD . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                throw new Exception($method_name . '. Соединение с Oracle не выполнено');
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            /** Отладка */
            $description = 'Соединение с оракал установлено';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            // поиск ошибочных синхронизаций
            $failed_sync = SapSizUpdate::find()
                ->select(['num_sync'])
                ->where(['status' => 0])
                ->all();
            if ($failed_sync) {
                throw new Exception($method_name . '. Есть не завершенные обновления по СИЗ(SapSizUpdate). Синхронизация остановлена');
            }

            $max_value = SapSizUpdate::find()                                                                           //получение максимального номера синхронизации
            ->max('num_sync');
            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }

            $new_data_to_update = SapSizUpdate::find()
                ->max('date_modified');
            $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));
            $filter = "";                                                                                               // фильтр по дате начала синхронизации, если это первая синхронизация, то фильтр пуст
            if ($new_data_to_update != null) {
                $filter = "where DATE_CREATED > TO_DATE('$new_data_to_update','YYYY-MM-DD HH24:MI:SS')";
            }
            /** Отладка */
            $description = 'получена отметка о последней синзронизации СИЗ';                                                                      // описание текущей отладочной точки
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

            // очищаем справочник номенлатур, т.к. САП выгружает последний снимок полного справочника
//            SapSizUpdate::deleteAll();

            // получаем  период носки индексированный по номенклатурному номеру СИЗ (id)
            $siz_period = (new Query)
                ->select('n_nomencl, working_life')
                ->from('sap_asu_worker_siz_update')
                ->groupBy('n_nomencl, working_life')
                ->indexBy('n_nomencl')
                ->all();

            // получаем справочник СИЗ
            $query = oci_parse($conn_oracle, "SELECT 
                N_NOMENCL, 
                NAME_NOMENCL, 
                UNIT_ID, 
                TYPE_COST, 
                NAME_COST, 
                SIGN_WINTER, 
                TO_CHAR(DATE_CREATED,'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED FROM IDB01.SO_AMICUM_NOMENCL_MV " . $filter);
            oci_execute($query);

            /** Отладка */
            $description = 'Получил данные с оракал IDB01.SO_AMICUM_NOMENCL_MV СИЗ';                                                                      // описание текущей отладочной точки
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
            $count = 0;                                                                                                 // счетчик массовой записи строк за раз
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                // единицы измерения
                if ($row['UNIT_ID'] === null) {
                    $row['UNIT_ID'] = 79;
                }

                // сезон
                if ($row['SIGN_WINTER'] === null) {
                    $row['SIGN_WINTER'] = 5;
                } elseif ($row['SIGN_WINTER'] === '0') {
                    $row['SIGN_WINTER'] = 5;
                } elseif ($row['SIGN_WINTER'] === '2') {
                    $row['SIGN_WINTER'] = 5;
                }
                // вид сиз
                if ($row['TYPE_COST'] === null) {
                    $row['TYPE_COST'] = '30100092';
                }

                // продолжительность носки СИЗ
                if (isset($siz_period[$row['N_NOMENCL']])) {
                    $row['WORKING_LIFE'] = $siz_period[$row['N_NOMENCL']]['working_life'];
                } else {
                    $row['WORKING_LIFE'] = null;
                }

                $row['NUM_SYNC'] = $num_sync;
                $query_result[] = $row;
                $count++;
                $count_all++;

                if ($count == 2000) {
                    $insert_updates = Yii::$app->db->createCommand()->batchInsert('sap_siz_update', ['n_nomencl', 'name_nomencl', 'unit_id', 'type_cost', 'name_cost', 'sign_winter', 'date_modified', 'working_life', 'num_sync'], $query_result)->execute();
                    if ($insert_updates != null) {
                        $warnings[] = "Добавлено {$insert_updates} записей в таблицу sap_siz_update";
                    } else {
                        $warnings[] = 'Записи в таблицу sap_asu_worker_siz_update не добавлены';
                    }
                    $count = 0;
                    $query_result = array();
                }
            }

            /** Отладка */
            $description = 'Подготовили данные для массовой вставки данных в таблицу sap_asu_worker_siz_update';                                                                      // описание текущей отладочной точки
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
            if ($count) {
                $insert_updates = Yii::$app->db->createCommand()->batchInsert('sap_siz_update', ['n_nomencl', 'name_nomencl', 'unit_id', 'type_cost', 'name_cost', 'sign_winter', 'date_modified', 'working_life', 'num_sync'], $query_result)->execute();
                if ($insert_updates != null) {
                    $warnings[] = "Добавлено {$insert_updates} записей в таблицу sap_siz_update";
                } else {
                    $warnings[] = 'Записи в таблицу sap_asu_worker_siz_update не добавлены';
                }
            }

        } catch (Throwable $ex) {
            $errors[] = 'copyFullDataSIZ. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод copyFullDataWorkWear() - копирование таблицы выданных СИЗов из Oracle в MySQL
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * @retrun array -  стандартный набор
     * Created date: on 03.10.2019 10:52
     * рефакторинг
     * история выдачи сиз NHRS.SAP_ASU_WORKER_SIZ_UPDATE:
     * WORK_WEAR_ID    идентификатор СИЗ            siz_id
     * N_NOMENCL        название номенклатуры СИЗ    siz_title
     * TABN            табельный номер PERNR        worker_siz.worker_id
     * OBJID            ключ подразделения            company_department_id
     * DATE_GIVE        дата выдачи                    worker_siz.date_issue
     * NAME_SIZE        название размера СИЗ        worker_siz.size
     * DATE_RETURN        дата возврата сиз
     * DATE_WRITTEN    дата списания                worker_siz.date_write_off
     * SIGN_WRITTEN    признак списания            worker_siz.status_id
     * WORKING_LIFE    время/период носки сиз    Siz.wear_period
     * алгоритм работы:
     * 1. устанавливаем соединение с Оракал
     * 2. Получаем номер последней синхронизации
     * 2. получаем дату и время последней синхронизации
     * 3. получаем данные с таблицы NHRS.SAP_ASU_WORKER_SIZ_UPDATE - история изменения сведений о СИЗ (дата списаний и т.д.)
     * 4. готовим данные к записи. если SIGN_WRITTEN = null или 0, то status_id = 64 (выдан). В остальных случаях status_id = 66 (Списан)
     * если дата списания SIGN_WRITTEN, дата выдачи DATE_GIVE, дата возврата DATE_RETURN равны null то пишем 2999 год - как не наступивший
     * 5. записываем полученную информацию кусками в таблицу sap_asu_worker_siz_update промежуточную системы АМИКУМ
     * Разработал Якимов М.Н.
     * Дата 25.01.2020
     * периодичность выполнения раз в 15минут
     * метод подходит как для начальной загрузки данных , так и для частичной
     */
    public static function copyFullDataWorkWear(): array
    {
        // Стартовая отладочная информация
        $method_name = 'copyFullDataWorkWear';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $rows = array();                                                                                                // обрабатываемые строки с оракла
        $add_workers = array();
        $i = 0;
        $query_result = array();

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $conn_oracle = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHPROD . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                throw new Exception($method_name . '. Соединение с Oracle не выполнено');
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            /** Отладка */
            $description = 'Соединение с оракал установлено';                                                           // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            $max_value = SapAsuWorkerSizUpdate::find()->max('num_sync');//получение максимального номера синхронизации
            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }

            $new_data_to_update = SapAsuWorkerSizUpdate::find()->max('date_modified');// получаем последнюю дату синхронизации
            $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));
            $filter = "";                                                                                               // фильтр по дате начала синхронизации, если это первая синхронизация, то фильтр пуст
            if ($new_data_to_update != null) {
                $filter = "where DATE_MODIFIED > TO_DATE('$new_data_to_update','YYYY-MM-DD HH24:MI:SS')";
            }
            /** Отладка */
            $description = 'получена отметка о последней синзронизации СИЗ';                                                                      // описание текущей отладочной точки
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

            $query = oci_parse($conn_oracle, "SELECT 
                    WORK_WEAR_ID, N_NOMENCL, TABN, OBJID, 
                    TO_CHAR(DATE_GIVE,'YYYY-MM-DD HH24:MI:SS') AS DATE_GIVE, NAME_SIZE, 
                    TO_CHAR(DATE_RETURN,'YYYY-MM-DD HH24:MI:SS') AS DATE_RETURN, 
                    TO_CHAR(DATE_WRITTEN,'YYYY-MM-DD HH24:MI:SS') AS DATE_WRITTEN, SIGN_WRITTEN, WORKING_LIFE, 
                    TO_CHAR(DATE_MODIFIED,'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED FROM AMICUM.SAP_ASU_WORKER_SIZ_UPDATE_VIEW " . $filter . " ORDER BY DATE_CREATED ASC, DATE_MODIFIED ASC");
            oci_execute($query);

            /** Отладка */
//            $description = 'Получил данные с оракал NHRS.SAP_ASU_WORKER_SIZ_UPDATE СИЗ';                                                                      // описание текущей отладочной точки
            $description = 'Получил данные с оракал AMICUM.SAP_ASU_WORKER_SIZ_UPDATE_VIEW СИЗ';                                                                      // описание текущей отладочной точки
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
            $count = 0;                                                                                                 // счетчик массовой записи строк за раз
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                if ($row['SIGN_WRITTEN'] == null) {
                    $row['SIGN_WRITTEN'] = 64;
                } elseif ($row['SIGN_WRITTEN'] == 11) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 10) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 5) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 3) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 1) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 0) {
                    $row['SIGN_WRITTEN'] = 64;
                }
                if ($row['DATE_WRITTEN'] == null) {
                    if ($row['DATE_RETURN'] == null) {
                        $row['DATE_WRITTEN'] = '2099-12-31';
                    } else {
                        $row['DATE_WRITTEN'] = $row['DATE_RETURN'];
                    }
                }
                if ($row['DATE_GIVE'] == null) {
                    $row['DATE_GIVE'] = '2099-12-31';
                }
                if ($row['DATE_RETURN'] == null) {
                    $row['DATE_RETURN'] = '2099-12-31';
                }
                $row['NUM_SYNC'] = $num_sync;
                $query_result[] = $row;
                $count++;
                $count_all++;

                if ($count == 2000) {
                    $insert_updates = Yii::$app->db->createCommand()->batchInsert('sap_asu_worker_siz_update', ['work_wear_id', 'n_nomencl', 'tabn', 'objid', 'date_give', 'name_size', 'date_return', 'date_written', 'sign_written', 'working_life', 'date_modified', 'num_sync'], $query_result)->execute();
                    if ($insert_updates != null) {
                        $warnings[] = "Добавлено {$insert_updates} записей в таблицу sap_asu_worker_siz_update";
                    } else {
                        $warnings[] = 'Записи в таблицу sap_asu_worker_siz_update не добавлены';
                    }
                    $count = 0;
                    $query_result = array();
                }
            }

            /** Отладка */
            $description = 'Подготовили данные для массовой вставки данных в таблицу sap_asu_worker_siz_update';                                                                      // описание текущей отладочной точки
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
            if ($count) {
                $insert_updates = Yii::$app->db->createCommand()->batchInsert('sap_asu_worker_siz_update', ['work_wear_id', 'n_nomencl', 'tabn', 'objid', 'date_give', 'name_size', 'date_return', 'date_written', 'sign_written', 'working_life', 'date_modified', 'num_sync'], $query_result)->execute();
                if ($insert_updates != null) {
                    $warnings[] = "Добавлено {$insert_updates} записей в таблицу sap_asu_worker_siz_update";
                } else {
                    $warnings[] = 'Записи в таблицу sap_asu_worker_siz_update не добавлены';
                }
            }

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    /**
     * Метод sizTables() - Добавление данных по СИЗам из промежуточных таблиц в основные
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 10:58
     */
    public static function sizTables()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $add_workers = array();
        $i = 0;
        $status_id = 64;
        $microtime_start = microtime(true);
        try {

            WorkerSiz::deleteAll();
            WorkerSizStatus::deleteAll();
            SizKind::deleteAll();
            $query_type_siz = SapAsuSizFull::find()
                ->select(['type_cost', 'name_cost'])
                ->where(['is not', 'name_cost', null])
                ->distinct()
                ->asArray()
                ->all();
            $insert_type_siz = Yii::$app->db->createCommand()->batchInsert('siz_kind', ['id', 'title'], $query_type_siz)->execute();
            if ($insert_type_siz === 0) {
                throw new Exception('Записи в таблицу siz_kind не добавлены');
            } else {
                $warnings[] = "sizTables добавил $insert_type_siz записей в таблицу siz_kind";
            }

            $siz_select = SapAsuSizFull::find()
                ->select(['sap_asu_siz_full.n_nomencl', 'sap_asu_siz_full.name_nomencl', 'unit.id', 'sap_asu_siz_full.working_life', 'sap_asu_siz_full.sign_winter', 'sap_asu_siz_full.type_cost'])
                ->innerJoin('unit', 'unit.sap_id=sap_asu_siz_full.unit_id')
                ->asArray()
                ->all();
            $insert_siz_full = Yii::$app->db->createCommand()->batchInsert('siz', ['id', 'title', 'unit_id', 'wear_period', 'season_id', 'siz_kind_id'], $siz_select)->execute();
//            $query_type_siz = SapAsuSizFull::find()
//                ->select(['type_cost', 'name_cost'])
//                ->where(['is not', 'name_cost', null])
//                ->distinct()
//                ->asArray()
//                ->all();
//            $insert_type_siz = Yii::$app->db->createCommand()->batchInsert('siz_kind', ['id', 'title'], $query_type_siz)->execute();
//            if ($insert_type_siz === 0) {
//                throw new \Exception('Записи в таблицу siz_kind не добавлены');
//            } else {
//                $warnings[] = "sizTables добавил $insert_type_siz записей в таблицу siz_kind";
//            }
            $result = (new Query())
                ->select([
                    'count(work_wear_id)',
                    'n_nomencl',
                    'worker.tabel_number',
                    'date_give',
                ])
                ->from('sap_asu_worker_siz_full')
                ->innerJoin('worker', 'worker.tabel_number=sap_asu_worker_siz_full.tabn and worker.id=sap_asu_worker_siz_full.tabn')
                //    ->limit(100)
                ->groupBy([
                    'n_nomencl',
                    'tabn',
                    'date_give'
                ]);
            $worker_siz_indexing = SapAsuWorkerSizFull::find()
                ->select(['date_return', 'name_size', 'n_nomencl', 'tabn', 'date_give', 'date_written'])
//                ->limit(1000)
                ->indexBy(function ($index) {
                    return $index['n_nomencl'] . '_' . $index['tabn'] . '_' . $index['date_give'];
                });
            $arr_worker_siz = array();
            foreach ($worker_siz_indexing->batch(1000) as $indexing_data) {
                foreach ($result->each() as $item) {
                    if (isset($indexing_data[$item['n_nomencl'] . '_' . $item['tabel_number'] . '_' . $item['date_give']])) {
                        $worker_siz_date_return = $indexing_data[$item['n_nomencl'] . '_' . $item['tabel_number'] . '_' . $item['date_give']]['date_return'];
                        $worker_date_written = $indexing_data[$item['n_nomencl'] . '_' . $item['tabel_number'] . '_' . $item['date_give']]['date_written'];
                        $worker_siz_name_size = $indexing_data[$item['n_nomencl'] . '_' . $item['tabel_number'] . '_' . $item['date_give']]['name_size'];
                        $item['date_return'] = $worker_siz_date_return;
                        $item['name_size'] = $worker_siz_name_size;
                        if ($worker_date_written != null) {
                            $status_id = 66;
                        } else {
                            $status_id = 64;
                        }
                        $item['status_id'] = $status_id;
                        $arr_worker_siz[] = $item;
                    }
                }
                $duration_method = round(microtime(true) - $microtime_start, 6);
            }
            $extra_worker_siz = array();
            foreach ($arr_worker_siz as $set)                                                                             //цикл по каждому работнику в индексированном массиве
            {
                if ($i === 99999) {
                    $count_rows = Yii::$app->db->createCommand()->batchInsert('worker_siz', ['count_issued_siz', 'siz_id', 'worker_id', 'date_issue', 'date_write_off', 'size', 'status_id'], $add_workers)->execute();
                    $i = 0;
                    if ($count_rows !== 0) {
                        $warnings[] = "sizTables добавил $count_rows записей в worker_siz" . $duration_method = round(microtime(true) - $microtime_start, 6);
                        $status = 1;
                    } else {
                        throw new Exception('sizTables. Ошибка добавления записи в worker_siz');
                    }
                    $add_workers = array();                                                                           //зануление массива после добавления в базу
                }
                $add_workers[] = $set;
                $i++;
            }
//            unset($arr_worker_siz);
            $warnings[] = 'sizTables. Добавляются данные в MySQL';                                       //добавление оставшихся строк
            /**
             * Добавление оставшихся строк
             */
            if ($i !== 1) {
                $count_rows = Yii::$app->db->createCommand()->batchInsert('worker_siz', ['count_issued_siz', 'siz_id', 'worker_id', 'date_issue', 'date_write_off', 'size', 'status_id'], $add_workers)->execute();
                if ($count_rows !== 0) {
                    $warnings[] = "sizTables добавил еще $count_rows записей  в worker_siz " . $duration_method = round(microtime(true) - $microtime_start, 6);
                } else {
                    throw new Exception('sizTables. Ошибка добавления записи в worker_siz  ');
                }
            }
            $siz_status_insert = array();
            $worker_siz_status = WorkerSiz::find()
                ->select(['id', 'date_issue', 'status_id', 'siz_id', 'worker_id'])
                ->asArray()
                ->all();
            $siz_status_insert = array();
            foreach ($worker_siz_status as $row_worker_siz) {
                $siz = SapAsuWorkerSizFull::find()
                    ->select(['so_amicum_sign_written_mv.name_written'])
                    ->where(['n_nomencl' => $row_worker_siz['siz_id'], 'tabn' => $row_worker_siz['worker_id'], 'date_give' => $row_worker_siz['date_issue']])
                    ->innerJoin('so_amicum_sign_written_mv', 'so_amicum_sign_written_mv.sign_written=sap_asu_worker_siz_full.sign_written')
                    ->asArray()
                    ->one();
                unset($row_worker_siz['siz_id']);
                unset($row_worker_siz['worker_id']);
                $row_worker_siz['name_written'] = (string)$siz['name_written'];
                $siz_status_insert[] = $row_worker_siz;
            }
            $warnings[] = 'Собрал массив для worker_siz_status ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            $siz_status_insert = Yii::$app->db->createCommand()->batchInsert('worker_siz_status', ['worker_siz_id', 'date', 'status_id', 'comment'], $siz_status_insert)->execute();
            if ($siz_status_insert !== null) {
                $warnings[] = 'Добавлены строки в worker_siz_status ';
            } else {
                $warnings[] = 'Данные в worker_siz_status не добавлены';
            }
        } catch (Throwable $ex) {
            $errors[] = 'sizTables. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Метод partWorkerSizUpdateTables() - обновление назначенных СИЗов
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 11:02
     */
    public static function partWorkerSizUpdateTables()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $memory_size = array();
        $microtime_start = microtime(true);
        try {
            SapAsuWorkerSizUpdate::deleteAll();
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;
            $conn_oracle = oci_connect('AMICUM_CON', 'vRqrWIOlStg7p24IAaR8', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . HOST_BATCHPROD . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =BATCHPROD.severstal.severstalgroup.com)))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $new_data_to_update = SapAsuWorkerSizUpdate::find()
                ->select('date_modified')
                ->orderBy('date_modified DESC')
                ->scalar();
            $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));
            if ($new_data_to_update != null) {
                $query = oci_parse($conn_oracle, "SELECT 
WORK_WEAR_ID, 
N_NOMENCL, 
TABN, 
OBJID, 
TO_CHAR(DATE_GIVE,'YYYY-MM-DD HH24:MI:SS') AS DATE_GIVE, 
NAME_SIZE, 
TO_CHAR(DATE_RETURN,'YYYY-MM-DD HH24:MI:SS') AS DATE_RETURN, 
TO_CHAR(DATE_WRITTEN,'YYYY-MM-DD HH24:MI:SS') AS DATE_WRITTEN,
SIGN_WRITTEN, 
WORKING_LIFE, 
TO_CHAR(DATE_MODIFIED,'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED FROM NHRS.sap_asu_worker_siz_update where DATE_MODIFIED > TO_DATE('$new_data_to_update','YYYY-MM-DD HH24:MI:SS')");
            } else {
                $query = oci_parse($conn_oracle, "SELECT 
WORK_WEAR_ID, 
N_NOMENCL, 
TABN, 
OBJID, 
TO_CHAR(DATE_GIVE,'YYYY-MM-DD HH24:MI:SS') AS DATE_GIVE, 
NAME_SIZE, 
TO_CHAR(DATE_RETURN,'YYYY-MM-DD HH24:MI:SS') AS DATE_RETURN, 
TO_CHAR(DATE_WRITTEN,'YYYY-MM-DD HH24:MI:SS') AS DATE_WRITTEN,
SIGN_WRITTEN, 
WORKING_LIFE, 
TO_CHAR(DATE_MODIFIED,'YYYY-MM-DD HH24:MI:SS') FROM NHRS.sap_asu_worker_siz_update");
            }
            oci_execute($query);
            $memory_size[] = 'Выгрзили данные по СИЗам - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрзили данные по СИЗам PEAK - ' . (memory_get_peak_usage()) / 1024;
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $search = '.';
                $replace = '-';
                if ($row['SIGN_WRITTEN'] == null) {
                    $row['SIGN_WRITTEN'] = 64;
                } elseif ($row['SIGN_WRITTEN'] == 11) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 10) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 5) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 3) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 1) {
                    $row['SIGN_WRITTEN'] = 66;
                } elseif ($row['SIGN_WRITTEN'] == 0) {
                    $row['SIGN_WRITTEN'] = 64;
                }
                if ($row['DATE_WRITTEN'] == null) {
                    $row['DATE_WRITTEN'] = '2099-12-31';
                }
                if ($row['DATE_GIVE'] == null) {
                    $row['DATE_GIVE'] = '2099-12-31';
                }
                if ($row['DATE_RETURN'] == null) {
                    $row['DATE_RETURN'] = '2099-12-31';
                }
//                if ($row['DATE_WRITTEN'] == null) {
//                    $row['DATE_WRITTEN'] = '31.12.99';
//                    $subrow_start = substr($row['DATE_WRITTEN'], 0, 7);
//                    $subrow_end = substr($row['DATE_WRITTEN'], 7);
//                    $row['DATE_WRITTEN'] = $subrow_start . '20' . $subrow_end;
//                    $test_row = str_replace($search, $replace, $row['DATE_WRITTEN']);
//                    $row['DATE_WRITTEN'] = date("Y-m-d", strtotime($test_row));
//                }
//                if ($row['DATE_GIVE'] == null) {
//                    $row['DATE_GIVE'] = '31.12.99';
//                    $subrow_start = substr($row['DATE_GIVE'], 0, 7);
//                    $subrow_end = substr($row['DATE_GIVE'], 7);
//                    $row['DATE_GIVE'] = $subrow_start . '20' . $subrow_end;
//                    $test_row = str_replace($search, $replace, $row['DATE_GIVE']);
//                    $row['DATE_GIVE'] = date("Y-m-d", strtotime($test_row));
//                }
//                if ($row['DATE_RETURN'] == null) {
//                    $row['DATE_RETURN'] = '31.12.99';
//                    $subrow_start = substr($row['DATE_RETURN'], 0, 7);
//                    $subrow_end = substr($row['DATE_RETURN'], 7);
//                    $row['DATE_RETURN'] = $subrow_start . '20' . $subrow_end;
//                    $test_row = str_replace($search, $replace, $row['DATE_RETURN']);
//                    $row['DATE_RETURN'] = date("Y-m-d", strtotime($test_row));
//                }

                $query_result[] = $row;
            }
            $memory_size[] = 'Массив для вствки создан - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Массив для вствки создан PEAK - ' . (memory_get_peak_usage()) / 1024;
            $warnings[] = "Обработал запрос из Oracle :  " . $duration_method = round(microtime(true) - $microtime_start, 6);
            $insert_updates = Yii::$app->db->createCommand()->batchInsert('sap_asu_worker_siz_update', ['work_wear_id', 'n_nomencl', 'tabn', 'objid', 'date_give', 'name_size', 'date_return', 'date_written', 'sign_written', 'working_life', 'date_modified'], $query_result)->execute();
            if ($insert_updates != null) {
                $warnings[] = "Добавлено {$insert_updates} записей в таблицу sap_asu_worker_siz_update";
            } else {
                $warnings[] = 'Записи в таблицу sap_asu_worker_siz_update не добавлены';
            }
            $memory_size[] = 'Данные вставлены в таблицу sap__asu_worker_siz_update - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Данные вставлены в таблицу sap__asu_worker_siz_update PEAK - ' . (memory_get_peak_usage()) / 1024;
            $warnings[] = $memory_size;
        } catch (Throwable $ex) {
            $errors[] = 'partWorkerSizUpdateTables. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Метод partWorkerSizUpdateTableAdd() - копирование данных в промежуточные таблицы для обновления
     * @return array|Query
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 11:05
     */
    public
    static function partWorkerSizUpdateTableAdd()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $num_sync = 0;
        $add_workers = array();
        $memory_size = array();
        $microtime_start = microtime(true);
        try {
            SapWorkerSizUpdate::deleteAll();
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $start_mem = memory_get_usage();
            $memory_size[] = 'start mem - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'start mem PEAK - ' . (memory_get_peak_usage()) / 1024;
            if (($get_num_sync = Yii::$app->db->createCommand('SELECT max(num_sync) FROM sap_worker_siz_update')->queryScalar()) === NULL) {
                $num_sync = 1;
            } else {
                $num_sync = $get_num_sync + 1;
            }
            $result = (new Query())
                ->select([
                    'count(work_wear_id)',
                    'n_nomencl',
                    'worker.tabel_number',
                    'date_give',
                ])
                ->from('sap_asu_worker_siz_update')
                ->innerJoin('worker', 'worker.tabel_number=sap_asu_worker_siz_update.tabn and worker.id=sap_asu_worker_siz_update.tabn')
//                ->limit(1000)
                ->groupBy([
                    'n_nomencl',
                    'tabn',
                    'date_give'
                ]);
            $memory_size[] = 'Выгрзили данные для вставки в промежуточные таблицы - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрзили данные для вставки в промежуточные таблицы PEAK - ' . (memory_get_peak_usage()) / 1024;
            $worker_siz_indexing = SapAsuWorkerSizUpdate::find()
                ->select(['name_size', 'n_nomencl', 'tabn', 'date_give', 'date_written', 'sign_written', 'date_return'])
                ->indexBy(function ($index) {
                    return $index['n_nomencl'] . '_' . $index['tabn'] . '_' . $index['date_give'];
                });
            $memory_size[] = 'Выгрузили данные синдексированные по n_nomencl, табельному номеру и дате - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выгрузили данные синдексированные по n_nomencl, табельному номеру и дате PEAK - ' . (memory_get_peak_usage()) / 1024;
            $arr_worker_siz = array();
            foreach ($worker_siz_indexing->batch(1000) as $indexing_data) {
                foreach ($result->each() as $item) {
                    if (isset($indexing_data[$item['n_nomencl'] . '_' . $item['tabel_number'] . '_' . $item['date_give']])) {
                        $worker_siz_date_return = $indexing_data[$item['n_nomencl'] . '_' . $item['tabel_number'] . '_' . $item['date_give']]['date_written'];
                        $worker_siz_sign_written = $indexing_data[$item['n_nomencl'] . '_' . $item['tabel_number'] . '_' . $item['date_give']]['sign_written'];
                        $worker_siz_name_size = $indexing_data[$item['n_nomencl'] . '_' . $item['tabel_number'] . '_' . $item['date_give']]['name_size'];
                        $item['date_written'] = $worker_siz_date_return;
                        $item['name_size'] = $worker_siz_name_size;
                        $item['status_id'] = $worker_siz_sign_written;
                        $item['num_sync'] = $num_sync;
                        $arr_worker_siz[] = $item;
                    }
                }
                $duration_method = round(microtime(true) - $microtime_start, 6);
            }
            $memory_size[] = 'Выполнили перебор полученных данных - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Выполнили перебор полученных данных PEAK - ' . (memory_get_peak_usage()) / 1024;
            $i = 0;
            foreach ($arr_worker_siz as $set)                                                                             //цикл по каждому работнику в индексированном массиве
            {
                if ($i === 99999) {
                    $count_rows = Yii::$app->db->createCommand()
                        ->batchInsert(
                            'sap_worker_siz_update',
                            ['count_issued_siz', 'siz_id', 'worker_id', 'date_issue', 'date_write_off', 'size', 'status_id', 'num_sync'],
                            $add_workers)
                        ->execute();
                    $i = 0;
                    if ($count_rows !== 0) {
                        $warnings[] = "partWorkerSizUpdateTableAdd добавил $count_rows записей в sap_worker_siz_update" . $duration_method = round(microtime(true) - $microtime_start, 6);
                        $status = 1;
                    } else {
                        throw new Exception('partWorkerSizUpdateTableAdd. Ошибка добавления записи в sap_worker_siz_update');
                    }
                    $add_workers = array();                                                                           //зануление массива после добавления в базу
                }
                $add_workers[] = $set;
                $i++;
            }
            $memory_size[] = 'Добавили остновную часть данных - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Добавили остновную часть данных PEAK - ' . (memory_get_peak_usage()) / 1024;
            $warnings[] = 'partWorkerSizUpdateTableAdd. Добавляются данные в MySQL';                                       //добавление оставшихся строк
            /**
             * Добавление оставшихся строк
             */
            if ($i !== 1) {
                $count_rows = Yii::$app->db->createCommand()
                    ->batchInsert(
                        'sap_worker_siz_update',
                        ['count_issued_siz', 'siz_id', 'worker_id', 'date_issue', 'date_write_off', 'size', 'status_id', 'num_sync'],
                        $add_workers)
                    ->execute();
                if ($count_rows !== 0) {
                    $warnings[] = "partWorkerSizUpdateTableAdd добавил еще $count_rows записей  в sap_worker_siz_update " . $duration_method = round(microtime(true) - $microtime_start, 6);
                } else {
                    throw new Exception('partWorkerSizUpdateTableAdd. Ошибка добавления записи в sap_worker_siz_update  ');
                }
            }
            $memory_size[] = 'Докинули остальные данные - ' . (memory_get_usage() - $start_mem) / 1024;
            $memory_size[] = 'Докинули остальные данные PEAK - ' . (memory_get_peak_usage()) / 1024;
            $warnings[] = $memory_size;
        } catch (Throwable $ex) {
            $errors[] = 'partWorkerSizUpdateTableAdd. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Метод partUpdateWorkerSiz() - обновление назначенных СИЗов
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 11:08
     * Рефакторинг:
     * алгоритм работы метода:
     * 1. проверяем на возможность синхронизации (последний номер синхронизации, последняя дата синхронизации, статус status != 0)
     * 2. получаем историю СИЗ из таблицы SapWorkerSizUpdate
     * 3. Обрабатываем по одной записи каждый сиз
     */
    public static function partUpdateWorkerSiz()
    {
//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");
        // Стартовая отладочная информация
        $method_name = 'partUpdateWorkerSiz';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $rows = array();                                                                                                // обрабатываемые строки с оракла
        $add_workers = array();
        $i = 0;
        $query_result = array();

        $inserted_worker_siz_status = array();
        $memory_size = array();
        $now_sync = 0;

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                           // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            $status_sync = SapAsuWorkerSizUpdate::find()
                ->select(['num_sync'])
                ->where(['status' => 0])
                ->scalar();
            if ($status_sync) {
                throw new Exception($method_name . '. Есть не завершенные синхронизации SapAsuWorkerSizUpdate');
            }

            /** Отладка */
            $description = 'Проверил статус синхронизации';                                                           // описание текущей отладочной точки
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


            $data_for_update = SapAsuWorkerSizUpdate::find()
                ->innerJoin('worker', 'worker.id=sap_asu_worker_siz_update.tabn')
                ->where(['is', 'status', null])
                ->asArray();


            /** Отладка */
            $description = 'Получил данные из таблицы SapAsuWorkerSizUpdate для синхронизации';                                                           // описание текущей отладочной точки
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
            foreach ($data_for_update->each(2000) as $item) {
                $added_worker_siz_id = null;
                $updated_worker_siz_id = null;
                $update_status = SapAsuWorkerSizUpdate::findOne(['id' => $item['id']]);

                $update_status->status = 0;
                if (!$update_status->save()) {
                    $errors[] = $update_status->errors;
                    throw new Exception($method_name . '. Ошибка сохранения модели SapWorkerSizUpdate');
                }

                if ($item['sign_written'] == 64) {
                    $status_title = self::STATUS_ISSUED;
                } elseif ($item['sign_written'] == 65) {
                    $status_title = self::STATUS_EXTENDED;
                } elseif ($item['sign_written'] == 66) {
                    $status_title = self::STATUS_DECOMMISSIONED;
                }
                $worker_siz = WorkerSiz::findOne(['siz_id' => $item['n_nomencl'], 'worker_id' => $item['tabn'], 'date_issue' => $item['date_give']]);
                if (!$worker_siz) {
                    /******************* Добавление записи в таблицу worker_siz *******************/
                    $worker_siz = new WorkerSiz();
                    $worker_siz->siz_id = $item['n_nomencl'];
                    $worker_siz->worker_id = $item['tabn'];
                    $worker_siz->date_issue = $item['date_give'];
                }
                $worker_siz->count_issued_siz = 1;
                $worker_siz->size = $item['name_size'];
                $worker_siz->date_write_off = $item['date_written'];
                $worker_siz->date_return = $item['date_return'];
                $worker_siz->status_id = $item['sign_written'];
                $worker_siz->company_department_id = $item['objid'];

                if ($worker_siz->save()) {
                    $worker_siz->refresh();
                    $worker_siz_id = $worker_siz->id;
                } else {
                    $errors[] = $worker_siz->errors;
                    $errors[] = $item['id'];
                    throw new Exception($method_name . '. Запись с таким номером не обновлена');
                }
                /******************* Добавление записи в таблицу worker_siz_status *******************/
                $inserted_worker_siz_status[] = [
                    $worker_siz_id,
                    $item['date_modified'],
                    $item['sign_written'],
                    $status_title
                ];
                unset($worker_siz);


                $update_status->status = 1;
                if (!$update_status->save()) {
                    throw new Exception($method_name . 'Статус записи не обновлён');
                }

                unset($update_status);
                $count_all++;
            }

            /** Отладка */
            $description = 'Закончил основной по штучный блок синхронизации';                                                           // описание текущей отладочной точки
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

            if (!empty($inserted_worker_siz_status)) {
                $count_to_insert = 0;
                foreach ($inserted_worker_siz_status as $worker_status) {
                    $count_to_insert++;
                    $inserted_worker_siz_status_part[] = $worker_status;
                    if ($count_to_insert == 2000) {
                        $sql_to_insert_worker_siz_status = Yii::$app->db->queryBuilder->batchInsert('worker_siz_status', [
                            'worker_siz_id',
                            'date',
                            'status_id',
                            'comment'
                        ], $inserted_worker_siz_status_part);
                        $result_inserted_worker_siz_status = Yii::$app->db
                            ->createCommand($sql_to_insert_worker_siz_status . ' ON DUPLICATE KEY UPDATE `date` = VALUES(`date`), `status_id`= VALUES(`status_id`)')
                            ->execute();
                        if ($result_inserted_worker_siz_status == 0) {
                            throw new Exception($method_name . '. Ошибка при добавлении статусов СИЗов');
                        }
                        $count_to_insert = 0;
                        unset($sql_to_insert_worker_siz_status);
                        unset($result_inserted_worker_siz_status);
                        unset($inserted_worker_siz_status_part);
//                        $inserted_worker_siz_status_part = [];
                    }
                }
                if (!empty($inserted_worker_siz_status_part)) {
                    $sql_to_insert_worker_siz_status = Yii::$app->db->queryBuilder->batchInsert('worker_siz_status', [
                        'worker_siz_id',
                        'date',
                        'status_id',
                        'comment'
                    ], $inserted_worker_siz_status_part);
                    $result_inserted_worker_siz_status = Yii::$app->db
                        ->createCommand($sql_to_insert_worker_siz_status . ' ON DUPLICATE KEY UPDATE `date` = VALUES(`date`), `status_id`= VALUES(`status_id`)')
                        ->execute();
                    if ($result_inserted_worker_siz_status == 0) {
                        $errors[] = $method_name . '. Ошибка при добавлении статусов СИЗов: ' . $inserted_worker_siz_status_part;
                    }
                    unset($sql_to_insert_worker_siz_status);
                    unset($result_inserted_worker_siz_status);
                    unset($inserted_worker_siz_status_part);
                }
            }
            unset($inserted_worker_siz_status);
            /** Отладка */
            $description = 'Закончил массовую вставку статусов сиз работника';                                                           // описание текущей отладочной точки
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

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод partUpdateSiz() - обновление списка СИЗов
     * @return array
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 11:12
     * рефакторинг
     * справочник СИЗ САП сиз sap_siz_update:
     * N_NOMENCL        идентификатор СИЗ            siz_id
     * NAME_NOMENCL     название номенклатуры СИЗ    siz_title
     * UNIT_ID          единицы измерения СИЗ        unit_id
     * TYPE_COST        тип СИЗ                      siz_kind_id
     * NAME_COST        название типа СИЗ            siz_kind_title
     * SIGN_WINTER      сезон носки сиз              season_id
     * алгоритм работы:
     * 0. Проверяем на наличие статуса 0 в таблице sap_siz_update, если такие есть, то выход с метода
     * 1. Плучаем данные из таблицы sap_siz_update, с учетом соединения с таблицей единиц измерения по номеру sap_id
     * 2. В таблице sap_siz_update делаем массовое обновление статуса на 0 (справочник маленький выгодней работать массово и сразу)
     * 3. Получаем справочник СИЗ системы Амикум siz
     * 4. Сравниваем между собой два справочника, и в случае выявления различий делаем обновление данных в справочнике СИЗ
     * Если метод завершился без ошибок, то Статусы обновлений ставим равным 1
     * Разработал Якимов М.Н.
     * Дата 25.01.2020
     * периодичность выполнения раз в 15минут
     * метод подходит как для начальной загрузки данных , так и для частичной
     */
    public static function partUpdateSiz()
    {
        // Стартовая отладочная информация
        $method_name = 'partUpdateSiz';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        $rows = array();                                                                                                // обрабатываемые строки с оракла
        $add_workers = array();
        $i = 0;
        $query_result = array();

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                           // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }


            // поиск ошибочных синхронизаций
            $failed_sync = SapSizUpdate::find()
                ->select(['num_sync'])
                ->where(['status' => 0])
                ->all();
            if ($failed_sync) {
                throw new Exception($method_name . '. Есть не завершенные обновления по СИЗ(SapSizUpdate). Синхронизация остановлена');
            }

            SapSizUpdate::updateAll(['status' => 0]);

            /** Отладка */
            $description = 'прошел проверку SapSizUpdate на не завершенные обновления СИЗ и установил у всех записей статус 0';                                                                      // описание текущей отладочной точки
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

            // получаем список сизов на обновление
            $data_for_update = SapSizUpdate::find()
                ->select('n_nomencl, name_nomencl, unit.id as unit_id1, type_cost, name_cost, sign_winter, working_life')
                ->leftJoin('unit', 'unit.sap_id=sap_siz_update.unit_id')
                ->where('name_nomencl is not null')
                ->asArray()
                ->all();
            if (!$data_for_update) {
                throw new Exception($method_name . '. Нет данных для обновления таблица синхронизации пуста');
            }

            // получаем спиок сизов из АМИКУМ для проверки необходимости обновления
            $spr_siz_amicum = Siz::find()
                ->asArray()
                ->indexBy('id')
                ->all();
//            $warnings[]=$spr_siz_amicum;
            $flag_save = 0;
            $count_debug['Всего обработанно записей'] = 0;
            $count_debug['Добавлено новых записей'] = 0;
            $count_debug['Обновлено всего записей'] = 0;
            $count_debug['Не требует обновления'] = 0;
            foreach ($data_for_update as $item) {
                $count_all++;
                $count_debug['Всего обработанно записей']++;
                if (isset($spr_siz_amicum[$item['n_nomencl']])) {
                    if (!$item['working_life']) {
                        $item_working_life = 0;
                    } else {
                        $item_working_life = $item['working_life'];
                    }
                    if (
                        $spr_siz_amicum[$item['n_nomencl']]['title'] != $item['name_nomencl'] or
                        $spr_siz_amicum[$item['n_nomencl']]['unit_id'] != $item['unit_id1'] or
                        $spr_siz_amicum[$item['n_nomencl']]['wear_period'] != $item_working_life or
                        $spr_siz_amicum[$item['n_nomencl']]['season_id'] != $item['sign_winter'] or
                        $spr_siz_amicum[$item['n_nomencl']]['siz_kind_id'] != $item['type_cost']
                    ) {
                        $siz_item = Siz::findOne(['id' => $item['n_nomencl']]);
                        $count_debug['Обновлено всего записей']++;
                        $flag_save = 1;
                    } else {
                        $count_debug['Не требует обновления']++;
                    }
                } else {
                    $item_working_life = 0;
                    $siz_item = new Siz();
                    $item_working_life = 0;
                    $flag_save = 1;
                    $count_debug['Добавлено новых записей']++;
                }

                if ($flag_save) {
                    $siz_item->id = $item['n_nomencl'];
                    $siz_item->title = $item['name_nomencl'];
                    $siz_item->unit_id = $item['unit_id1'];
                    $siz_item->wear_period = $item_working_life;
                    $siz_item->season_id = $item['sign_winter'];
                    $siz_item->siz_kind_id = $item['type_cost'];
                    if (!$siz_item->save()) {
                        $errors[] = $siz_item->errors;
                        throw new Exception($method_name . "Запись c n_nomencl = {$item['n_nomencl']} не добавлена");                         //очистили пременную $update_status
                    }
                    $flag_save = 0;
                }
            }

            $debug['number_row_affected'][] = $count_debug;                                                                          // количество обработанных записей

            /** Отладка */
            $description = 'Закончил выполнять основной метод';                                                                      // описание текущей отладочной точки
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

            SapSizUpdate::updateAll(['status' => 1]);

            /** Отладка */
            $description = 'установил у всех записей статус 1';                                                                      // описание текущей отладочной точки
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

        } catch (Throwable $ex) {
            $errors[] = 'partUpdateSiz. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;

    }


    /******************* ПАБы *******************/
    /**
     * Метод pabCopyAmicumStopLookoutActMV() - Копирование данных по ПАБам из Oracle в промежуточные таблицы MySQL
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 11:15
     */
    public function pabCopyAmicumStopLookoutActMV()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $microtime_start = microtime(true);
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            SapAmicumStopLookoutActMv::deleteAll();
            $query = oci_parse($conn_oracle, "SELECT LOOKOUT_ACTION_ID, INSTRUCTION_ID, PAB_ID, ACTION_NAME, ACTION_DATE, DATE_FAKT, COLOR FROM AMICUM_STOP_LOOKOUT_ACT_MV");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $search = '.';
                $replace = '-';
                $subrow_start = substr($row['ACTION_DATE'], 0, 2);
                $subrow_end = substr($row['ACTION_DATE'], 3);
                $row['ACTION_DATE'] = '20' . $subrow_start . '-' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['ACTION_DATE']);
                $row['ACTION_DATE'] = date("Y-m-d", strtotime($test_row));

                $subrow_start = substr($row['DATE_FAKT'], 0, 6);
                $subrow_end = substr($row['DATE_FAKT'], 6);
                $row['DATE_FAKT'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['DATE_FAKT']);
                $row['DATE_FAKT'] = date("Y-m-d", strtotime($test_row));
                $query_result[] = $row;
            }
            $warnings[] = $query_result;
//            Assistant::PrintR($query_result);die;
            $insert = Yii::$app->db->createCommand()->batchInsert('sap_amicum_stop_lookout_act_mv', ['LOOKOUT_ACTION_ID', 'INSTRUCTION_ID', 'PAB_ID', 'ACTION_NAME', 'ACTION_DATE', 'DATE_FACT', 'COLOR'], $query_result)->execute();
            $query_result = array();
            SapHcmStructObjidView::deleteAll();
            $query = oci_parse($conn_oracle, "SELECT HCM_STRUCT_OBJID_ID, ZZORG, STRUCT_ID, OBJID, IND_TOP, FN,  CREATED_BY, DATE_CREATED, MODIFIED_BY, DATE_MODIFIED FROM HCM_STRUCT_OBJID_VIEW");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $search = '.';
                $replace = '-';
                $subrow_start = substr($row['DATE_CREATED'], 0, 6);
                $subrow_end = substr($row['DATE_CREATED'], 6);
                $row['DATE_CREATED'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['DATE_CREATED']);
                $row['DATE_CREATED'] = date("Y-m-d", strtotime($test_row));

                $subrow_start = substr($row['DATE_MODIFIED'], 0, 6);
                $subrow_end = substr($row['DATE_MODIFIED'], 6);
                $row['DATE_MODIFIED'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['DATE_MODIFIED']);
                $row['DATE_MODIFIED'] = date("Y-m-d", strtotime($test_row));
                $query_result[] = $row;
            }

            $insert = Yii::$app->db->createCommand()->batchInsert('sap_hcm_struct_objid_view', ['hcm_struct_objid_id', 'zzorg', 'struct_id', 'objid', 'ind_top', 'fn', 'created_by', 'date_created', 'modified_by', 'date_modified'], $query_result)->execute();
            $query_result = array();
            SapHcmHrsrootPernrView::deleteAll();
            $query = oci_parse($conn_oracle, "SELECT HCM_HRSROOT_PERNR_ID, ZZORG, HRSROOT_ID, PERNR,  CREATED_BY, DATE_CREATED, MODIFIED_BY, DATE_MODIFIED, IND_CANDIDAT, IND_A6 FROM HCM_HRSROOT_PERNR_VIEW");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $search = '.';
                $replace = '-';
                $subrow_start = substr($row['DATE_CREATED'], 0, 6);
                $subrow_end = substr($row['DATE_CREATED'], 6);
                $row['DATE_CREATED'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['DATE_CREATED']);
                $row['DATE_CREATED'] = date("Y-m-d", strtotime($test_row));

                $subrow_start = substr($row['DATE_MODIFIED'], 0, 6);
                $subrow_end = substr($row['DATE_MODIFIED'], 6);
                $row['DATE_MODIFIED'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['DATE_MODIFIED']);
                $row['DATE_MODIFIED'] = date("Y-m-d", strtotime($test_row));
                $query_result[] = $row;
//                Assistant::PrintR($row);die;
            }
            $insert = Yii::$app->db->createCommand()->batchInsert('sap_hcm_hrsroot_pernr_view', ['hcm_hrsroot_pernr_id', 'zzorg', 'hrsroot_id', 'pernr', 'created_by', 'date_created', 'modified_by', 'date_modified', 'ind_candidat', 'ind_a6'], $query_result)->execute();

        } catch (Throwable $ex) {
            $errors[] = 'sizTables. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Метод pabCopyAmicumLookoutActMV() - копирование данных по нарушениям и предписаниям из Oracle в промежуточные таблицы MySQL
     * @package backend\controllers\serviceamicum
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 11:17
     */
    public
    function pabCopyAmicumLookoutActMV()
    {
        $errors = array();
        $status = 1;
        $warnings = array();

        $microtime_start = microtime(true);
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $query = oci_parse($conn_oracle, "SELECT REG_DAN_EFFECT_ID, DATE_CHECK, HRSROOT_ID_A, DATA_CREATED_REG_DAN_EFFECT, PRIS_PR, INSTRUCTION_ID, DATA_INSTRUCTION, LOOKOUT_ACTION_ID, LOOKOUT_ID, DAN_EFFECT, ACTION_OTV_ID, PLACE_NAME, REF_NORM_DOC_ID, PAB_ID, ERROR_POINT FROM AMICUM_LOOKOUT_ACTION_MV where REG_DAN_EFFECT_ID is not null");
//            $query = oci_parse($conn_oracle, "SELECT max(DATE_CHECK) as DATE_CHECK FROM AMICUM_LOOKOUT_ACTION_MV where REG_DAN_EFFECT_ID is not null");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {

//                Assistant::PrintR($row);die;
                $search = '.';
                $replace = '-';
//                $subrow_start = '20'.substr($row['DATE_CHECK'], 6);
//                $subrow_end = substr($row['DATE_CHECK'], 0, 5);
//                Assistant::PrintR($subrow_start);
//                Assistant::PrintR($subrow_end);

                $row['DATE_CHECK'] = '20' . $row['DATE_CHECK'];
                $test_row = str_replace($search, $replace, $row['DATE_CHECK']);
                $row['DATE_CHECK'] = date("Y-m-d", strtotime($test_row));
//                Assistant::PrintR($row);die;
//                $subrow_start = substr($row['DATA_CREATED_REG_DAN_EFFECT'], 0, 6);
//                $subrow_end = substr($row['DATA_CREATED_REG_DAN_EFFECT'], 6);
                $row['DATA_CREATED_REG_DAN_EFFECT'] = '20' . $row['DATA_CREATED_REG_DAN_EFFECT'];
                $test_row = str_replace($search, $replace, $row['DATA_CREATED_REG_DAN_EFFECT']);
////                Assistant::PrintR($test_row);
                $row['DATA_CREATED_REG_DAN_EFFECT'] = date("Y-m-d", strtotime($test_row));
                $query_result[] = $row;
            }
//            Assistant::PrintR($query_result);die;

            SapAmicumLookuotActionMv::deleteAll();
            $insert_full = Yii::$app->db->createCommand()->batchInsert('sap_amicum_lookuot_action_mv', ['REG_DAN_EFFECT_ID', 'DATE_CHECK', 'HRSROOT_ID_A', 'DATA_CREATED_REG_DAN_EFFECT', 'PRIS_PR', 'INSTRUCTION_ID', 'DATA_INSTRUCTION', 'LOOKOUT_ACTION_ID', 'LOOKOUT_ID', 'DAN_EFFECT', 'ACTION_OTV_ID', 'PLACE_NAME', 'REF_NORM_DOC_ID', 'PAB_ID', 'ERROR_POINT'], $query_result)->execute();
            if ($insert_full === 0) {
                throw new Exception('Записи в таблицу sap_amicum_lookuot_action_mv не добавлены');
            } else {
                $warnings[] = "pabCopyAmicumLookoutActMV добавил $insert_full записей в таблицу sap_amicum_lookuot_action_mv";
            }


            $query_result = array();
            $query = oci_parse($conn_oracle, "SELECT INSTRUCTION_ROSTEX_ID , STRUCT_ID, ROSTEX_NOMER, ROSTEX_DATE, ROSTEX_FIO, ROSTEX_OTV_ID, FIO_OTV, PROF_OTV, DESC_ERROR,  DESC_ACTION, DATE_PLAN, DATE_FACT, DEF_WORK, INT_DOK, STOP_WORK, DATE_STOP_WORK, REF_ERROR_DIRECTION_ID, COLOR from amicum_rostext_mv where INSTRUCTION_ROSTEX_ID is not null");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $search = '.';
                $replace = '-';
                $subrow_start = substr($row['ROSTEX_DATE'], 0, 6);
                $subrow_end = substr($row['ROSTEX_DATE'], 6);
                $row['ROSTEX_DATE'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['ROSTEX_DATE']);
                $row['ROSTEX_DATE'] = date("Y-m-d", strtotime($test_row));
                $subrow_start = substr($row['DATE_PLAN'], 0, 6);
                $subrow_end = substr($row['DATE_PLAN'], 6);
                $row['DATE_PLAN'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['DATE_PLAN']);
                $row['DATE_PLAN'] = date("Y-m-d", strtotime($test_row));
                $subrow_start = substr($row['DATE_FACT'], 0, 6);
                $subrow_end = substr($row['DATE_FACT'], 6);
                $row['DATE_FACT'] = $subrow_start . '20' . $subrow_end;
                $test_row = str_replace($search, $replace, $row['DATE_FACT']);
                $row['DATE_FACT'] = date("Y-m-d", strtotime($test_row));
                $query_result[] = $row;


            }
//            Assistant::PrintR($query_result);die;
            SapAmicumRostextMv::deleteAll();
            $insert_rostext = Yii::$app->db->createCommand()->batchInsert('sap_amicum_rostext_mv', ['instruction_rostext_id', 'struct_id', 'rostex_nomer', 'rostex_date', 'rostex_fio', 'rostex_otv_id', 'fio_otv', 'prof_otv', 'desc_error', 'desc_action', 'date_plan', 'date_fact', 'def_work', 'int_doc', 'stop_work', 'date_stop_work', 'ref_error_direction_id', 'color'], $query_result)->execute();
            if ($insert_rostext === 0) {
                throw new Exception('Записи в таблицу sap_amicum_rostext_mv не добавлены');
            } else {
                $warnings[] = "pabCopyAmicumLookoutActMV добавил $insert_rostext записей в таблицу sap_amicum_rostext_mv";
            }
            $query_result = array();
            $query = oci_parse($conn_oracle, "Select REF_ERROR_DIRECTION_ID, NAME, DATE_BEG, DATE_END, CREATED_BY, DATE_CREATED, MODIFIED_BY, DATE_MODIFIED, PARENT_ID, SORT_ORDER from ref_error_direction_mv");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
                if ($row['DATE_BEG'] !== StatusEnumController::SET_NULL) {
                    $search = '.';
                    $replace = '-';
//                    $subrow_start = substr($row['DATE_BEG'], 0, 6);
//                    $subrow_end = substr($row['DATE_BEG'], 6);
                    $row['DATE_BEG'] = '20' . $row['DATE_BEG']; // $row['DATE_BEG'] = '20' . $subrow_start . '-' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['DATE_BEG']);
                    $row['DATE_BEG'] = date("Y-m-d", strtotime($test_row));
                }
                if ($row['DATE_END'] !== StatusEnumController::SET_NULL) {
                    $search = '.';
                    $replace = '-';
//                    $subrow_start = substr($row['DATE_END'], 0, 6);
//                    $subrow_end = substr($row['DATE_END'], 6);
                    $row['DATE_END'] = '20' . $row['DATE_END']; //  $row['DATE_END'] = '20' . $subrow_start . '-' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['DATE_END']);
                    $row['DATE_END'] = date("Y-m-d", strtotime($test_row));
                }
                if ($row['DATE_CREATED'] !== StatusEnumController::SET_NULL) {
                    $search = '.';
                    $replace = '-';
//                    $subrow_start = substr($row['DATE_CREATED'], 0, 6);
//                    $subrow_end = substr($row['DATE_CREATED'], 6);
                    $row['DATE_CREATED'] = '20' . $row['DATE_CREATED'];  // $row['DATE_CREATED'] = '20' . $subrow_start . '-' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['DATE_CREATED']);
                    $row['DATE_CREATED'] = date("Y-m-d", strtotime($test_row));
                }
                if ($row['DATE_MODIFIED'] !== StatusEnumController::SET_NULL) {
                    $search = '.';
                    $replace = '-';
//                    $subrow_start = substr($row['DATE_MODIFIED'], 0, 6);
//                    $subrow_end = substr($row['DATE_MODIFIED'], 6);
                    $row['DATE_MODIFIED'] = '20' . $row['DATE_MODIFIED']; //$row['DATE_MODIFIED'] = '20' . $subrow_start . '-' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['DATE_MODIFIED']);
                    $row['DATE_MODIFIED'] = date("Y-m-d", strtotime($test_row));
                }
                $query_result[] = $row;
            }
//            Assistant::PrintR($query_result);die;
            SapRefErrorDirectionMv::deleteAll();
            $insert_error_direction = Yii::$app->db->createCommand()->batchInsert('sap_ref_error_direction_mv', ['REF_ERROR_DIRECTION_ID', 'NAME', 'DATE_BEG', 'DATE_END', 'CREATED_BY', 'DATE_CREATED', 'MODIFIED_BY', 'DATE_MODIFIED', 'PARENT_ID', 'SORT_ORDER'], $query_result)->execute();
            if ($insert_error_direction === 0) {
                throw new Exception('Записи в таблицу sap_ref_error_direction_mv не добавлены');
            } else {
                $warnings[] = "pabCopyAmicumLookoutActMV добавил $insert_error_direction записей в таблицу sap_ref_error_direction_mv";
            }

            $query_result = array();
            $query = oci_parse($conn_oracle, "Select REF_NORM_DOC_ID, PARENT_ID, NAME, DATE_BEG, DATE_END, CREATED_BY, DATE_CREATED, MODIFIED_BY, DATE_MODIFIED from ref_norm_doc_mv where ref_norm_doc_id is not null");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
                if ($row['DATE_BEG'] !== StatusEnumController::SET_NULL) {
                    $search = '.';
                    $replace = '-';
                    $subrow_start = substr($row['DATE_BEG'], 0, 6);
                    $subrow_end = substr($row['DATE_BEG'], 6);
                    $row['DATE_BEG'] = $subrow_start . '20' . $subrow_end;    // $row['DATE_BEG'] = '20' . $subrow_start . '-' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['DATE_BEG']);
                    $row['DATE_BEG'] = date("Y-m-d", strtotime($test_row));
                }
                if ($row['DATE_END'] !== StatusEnumController::SET_NULL) {
                    $search = '.';
                    $replace = '-';
                    $subrow_start = substr($row['DATE_END'], 0, 6);
                    $subrow_end = substr($row['DATE_END'], 6);
                    $row['DATE_END'] = $subrow_start . '20' . $subrow_end;   //$row['DATE_END'] = '20' . $subrow_start . '-' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['DATE_END']);
                    $row['DATE_END'] = date("Y-m-d", strtotime($test_row));
                }
                if ($row['DATE_CREATED'] !== StatusEnumController::SET_NULL) {
                    $search = '.';
                    $replace = '-';
                    $subrow_start = substr($row['DATE_CREATED'], 0, 6);
                    $subrow_end = substr($row['DATE_CREATED'], 6);
                    $row['DATE_CREATED'] = $subrow_start . '20' . $subrow_end;   // $row['DATE_CREATED'] = '20' . $subrow_start . '-' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['DATE_CREATED']);
                    $row['DATE_CREATED'] = date("Y-m-d", strtotime($test_row));
                }
                if ($row['DATE_MODIFIED'] !== StatusEnumController::SET_NULL) {
                    $search = '.';
                    $replace = '-';
                    $subrow_start = substr($row['DATE_MODIFIED'], 0, 6);
                    $subrow_end = substr($row['DATE_MODIFIED'], 6);
                    $row['DATE_MODIFIED'] = $subrow_start . '20' . $subrow_end;   // $row['DATE_MODIFIED'] = '20' . $subrow_start . '-' . $subrow_end;
                    $test_row = str_replace($search, $replace, $row['DATE_MODIFIED']);
                    $row['DATE_MODIFIED'] = date("Y-m-d", strtotime($test_row));
                }
                $query_result[] = $row;
            }
            SapRefNormDocMv::deleteAll();
            $insert_ref_norm_doc = Yii::$app->db->createCommand()->batchInsert('sap_ref_norm_doc_mv', ['REF_NORM_DOC_ID', 'PARENT_ID', 'NAME', 'DATE_BEG', 'DATE_END', 'CREATED_BY', 'DATE_CREATED', 'MODIFIED_BY', 'DATE_MODIFIED'], $query_result)->execute();
            if ($insert_ref_norm_doc === 0) {
                throw new Exception('Записи в таблицу sap_ref_norm_doc_mv не добавлены');
            } else {
                $warnings[] = "pabCopyAmicumLookoutActMV добавил $insert_ref_norm_doc записей в таблицу sap_ref_norm_doc_mv";
            }
            $query_result = array();
            $query = oci_parse($conn_oracle, "Select INSTRUCTION_GIVERS_ID, INSTRUCTION_ID, HRSROOT_ID from instruction_givers_mv where INSTRUCTION_GIVERS_ID is not null");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $query_result[] = $row;
            }
            SapInstructionGiversMv::deleteAll();
            $insert_instruction_givers = Yii::$app->db->createCommand()->batchInsert('sap_instruction_givers_mv', ['INSTRUCTION_GIVERS_ID', 'INSTRUCTION_ID', 'HRSROOT_ID'], $query_result)->execute();
            if ($insert_instruction_givers === 0) {
                throw new Exception('Записи в таблицу sap_instruction_givers_mv не добавлены');
            } else {
                $warnings[] = "pabCopyAmicumLookoutActMV добавил $insert_instruction_givers записей в таблицу sap_instruction_givers_mv";
            }
        } catch (Throwable $ex) {
            $errors[] = 'sizTables. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    /**
     * Метод partUpdateEmployeeDel() - удаляет дубли из таблицы Employee. Оставляет тех, кто соответствует своим табельным номерам
     * @return array
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 11:29
     */
    public
    function partUpdateEmployeeDel()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $update_table = array();
        $update_element = array();
        $now_sync = 0;
        $status = 1;
        $microtime_start = microtime(true);
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $max_value = SapEmployeeUpdate::find()//получение максимального номера синхронизации
            ->max('num_sync');
            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }
            $min_num_sync = SapEmployeeUpdate::find()//получение минимального номера синхронизации
            ->min('num_sync');
            $get_min_num_sync = Yii::$app->db->createCommand('SELECT min(num_sync) FROM sap_position_update where status is NULL')->queryScalar();
            $now_sync = SapEmployeeUpdate::find()
                ->select(['num_sync'])
                ->where(['is', 'status', new Expression('NULL')])
                ->limit(1)
                ->asArray()
                ->scalar();
            $data_for_insert = SapEmployeeUpdate::find()
                ->where(['status' => 2])
//                ->andWhere(['num_sync'=> $now_sync])
//                ->limit(150)
                ->asArray()
                ->all();

            $null_stell = SapEmployeeUpdate::find()
                ->where(['is', 'STELL', new Expression('NULL')])
                ->asArray()
                ->all();
            foreach ($data_for_insert as $item) {
                $update_status = SapEmployeeUpdate::findOne(['PERNR' => $item['PERNR'], 'num_sync' => $now_sync]);
                $update = StatusEnumController::NOT_DONE;
                if ($update_status) {
                    $update_status->status = $update;
                    if ($update_status->save()) {
                        $warnings[] = "partUpdateEmployeeDel обновил статус в sap_employee_update на 0 на $now_sync синхронизаци  " . $duration_method = round(microtime(true) - $microtime_start, 6);
                    } else {
                        $errors[] = $update_status->errors;
                        throw new Exception('partUpdateEmployeeDel. Ошибка сохранения модели SapEmployeeUpdate');
                    }
                }
                $employee = Employee::find()
                    ->where(['last_name' => $item['NACHN'], 'first_name' => $item['VORNA'], 'patronymic' => $item['MIDNM']])
                    ->asArray()
                    ->all();
//                $pers = Employee::findOne(['last_name'=> $employee['NACHN'], 'first_name'=> $employee['VORNA'],'patronymic' => $employee['MIDNM']])
                $warnings[] = 'Список на удаление:';
                $warnings[] = $employee;
                foreach ($employee as $person) {
                    if ($person['id'] != $item['PERNR']) {
                        $delete_tabel = Yii::$app->db->createCommand()->delete('employee', ['id' => $person['id'], 'last_name' => $person['last_name'], 'first_name' => $person['first_name']])->execute();
                    }
                }
                $upd_status = SapEmployeeUpdate::findOne(['id' => $item['id']]);
                $upd_status->status = StatusEnumController::SET_NULL;
                $employee = array();
                $employee = Employee::find()
                    ->where(['last_name' => $item['NACHN'], 'first_name' => $item['VORNA'], 'patronymic' => $item['MIDNM']])
                    ->asArray()
                    ->all();
                $warnings[] = 'Остались после удаления:';
                $warnings[] = $employee;
            }
            $warnings[] = 'partUpdateEmployeeDel закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $ex) {
            $errors[] = 'partUpdateEmployeeDel. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
//            $errors[] = $item['id'];
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }


    public
    function partUpdateWorkereDel()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $update_table = array();
        $update_element = array();
        $now_sync = 0;
        $status = 1;
        $microtime_start = microtime(true);
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");


            $now_sync = SapEmployeeUpdate::find()
                ->select(['num_sync'])
                ->where(['is', 'status', new Expression('NULL')])
                ->limit(1)
                ->asArray()
                ->scalar();
            $data_for_insert = SapEmployeeUpdate::find()
                ->where(['status' => StatusEnumController::SET_NULL])
//                ->where(['status' => 1])
//                ->andWhere(['num_sync'=> $now_sync])
//                ->limit(150)
                ->asArray()
                ->all();

            foreach ($data_for_insert as $item) {
                $update_status = SapEmployeeUpdate::findOne(['PERNR' => $item['PERNR'], 'num_sync' => $now_sync]);
                $update = StatusEnumController::NOT_DONE;
                if ($update_status) {
                    $update_status->status = $update;
                    if ($update_status->save()) {
                        $warnings[] = "partUpdateWorkereDel обновил статус в sap_employee_update на 0 на $now_sync синхронизаци  " . $duration_method = round(microtime(true) - $microtime_start, 6);
                    } else {
                        $errors[] = $update_status->errors;
                        throw new Exception('partUpdateWorkereDel. Ошибка сохранения модели SapEmployeeUpdate');
                    }
                }
                $worker = Worker::find()
                    ->where(['tabel_number' => $item['PERNR']])
                    ->asArray()
                    ->all();

//                $pers = Employee::findOne(['last_name'=> $employee['NACHN'], 'first_name'=> $employee['VORNA'],'patronymic' => $employee['MIDNM']])
                $warnings[] = 'Список на удаление:';
                $warnings[] = $worker;
                foreach ($worker as $person) {
                    if ($person['id'] != $item['PERNR'] or $person['employee_id'] != $item['PERNR']) {
                        $delete_tabel = Yii::$app->db->createCommand()->delete('worker', ['id' => $person['id'], 'employee_id' => $person['employee_id'], 'tabel_number' => $person['tabel_number']])->execute();
                    }
                }
                $upd_status = SapEmployeeUpdate::findOne(['id' => $item['id']]);
                $upd_status->status = 2;
                if ($upd_status->save()) {
                    $warnings[] = 'Сотрудник обработан';
                }
                $worker = 0;
                $worker = Worker::find()
                    ->where(['tabel_number' => $item['PERNR']])
                    ->asArray()
                    ->all();
                $warnings[] = 'Остались после удаления:';
                $warnings[] = $worker;
            }
            $warnings[] = 'partUpdateWorkereDel закончил выполнение ' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $ex) {
            $errors[] = 'partUpdateWorkereDel. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
//            $errors[] = $item['id'];
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод selectDataCMAS() - синхронизация скуд. используются методы, не совсем подходящие к задаче
     * @return array
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 24.09.2019 13:58
     */
    public static function selectDataSKUD()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $status = 1;
        $session = Yii::$app->session;
        $microtime_start = microtime(true);
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            /******************* Вычисление номера синхронизации *******************/
            $now_sync = SapSkudUpdate::find()
                ->select(['num_sync'])
                ->where(['is', 'status', new Expression('NULL')])
//                ->limit(3)
                ->asArray()
                ->scalar();
            $max_value = SapSkudUpdate::find()//получение максимального номера синхронизации
            ->max('num_sync');
            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }
            $min_num_sync = SapSkudUpdate::find()//получение минимального номера синхронизации
            ->min('num_sync');
            $get_min_num_sync = Yii::$app->db->createCommand('SELECT min(num_sync) FROM sap_skud_update where status is NULL')->queryScalar();
            $now_sync = SapSkudUpdate::find()
                ->select(['num_sync'])
                ->where(['is', 'status', new Expression('NULL')])
                ->limit(1)
                ->asArray()
                ->scalar();

            /******************* Вычисление времени последнего обновления *******************/
            $new_data_to_update = WorkerParameterValue::find()
                ->select('worker_parameter_value.date_time')
                ->where(['worker_parameter.parameter_id' => 529])
                ->rightJoin('worker_parameter', 'worker_parameter.id=worker_parameter_value.worker_parameter_id')
                ->orderBy('worker_parameter_value.date_time DESC')
                ->scalar();
            $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));
            /**
             * Массив воркеров с которым будем сверяться для проверки на существование в базе
             */
            $workers_ids_db = (new Query())
                ->select('id')
                ->from('worker')
                ->column();

            $query_result = array();
            $conn_oracle = oci_connect('Amicum_PS', 'y62#yZfl$U$e', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST = ' . SKUD_HOST_NAME . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =' . SKUD_SERVICE_NAME . ')))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $query = oci_parse($conn_oracle,
                "Select CARDS.FTABN_SAP,
                TO_CHAR(SOURCE.DATE_OTM, 'YYYY-MM-DD HH24:MI:SS') as DATE_OTM,
                SOURCE.ID_TYPE
                from PMS.SOURCE
                join PMS.CARDS on SOURCE.ID_KART=CARDS.FNCARD where DATE_OTM >= TO_DATE('$new_data_to_update','YYYY-MM-DD HH24:MI:SS')");
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $first_letter = (int)substr($row['FTABN_SAP'], 0, 1);
                if ($first_letter === 0) {
                    $row['FTABN_SAP'] = substr($row['FTABN_SAP'], 1, 9);
                }
                if (!in_array($row['FTABN_SAP'], $workers_ids_db)) {
                    continue;
                }
                //$row['DATE_OTM']="20".date("y-m-d", strtotime($row['DATE_OTM']));
                $row['worker_id'] = $row['FTABN_SAP'];
                $row['date_time'] = $row['DATE_OTM'];
                $row['type_skud'] = $row['ID_TYPE'];
                $row['num_sync'] = $num_sync;
                unset($row['FTABN_SAP']);
                unset($row['DATE_OTM']);
                unset($row['ID_TYPE']);
                $query_result[] = $row;
            }
            $insert_sql_skud = Yii::$app->db->createCommand()->batchInsert('sap_skud_update', ['worker_id', 'date_time', 'type_skud', 'num_sync'], $query_result)->execute();
            if ($insert_sql_skud != null) {
                $warnings[] = 'selectDataSKUD. Вставлены строки в sap_skud_update ' . $duration_method = round(microtime(true) - $microtime_start, 6);
            } else {
                throw new Exception('selectDataSKUD. Ошибка добавления записей в sap_skud_update');
            }
            $warnings[] = 'Загружаю данные из базы';
            $updated_data = SapSkudUpdate::find()
                ->select(['worker_id', 'date_time', 'type_skud'])
                ->where(['status' => StatusEnumController::SET_NULL])
                ->andWhere(['num_sync' => $num_sync])
                ->asArray()
                ->all();
            $client = new Client("ws://" . AMICUM_CONNECT_STRING_WEBSOCKET . "/ws");
            foreach ($updated_data as $item_result) {
                $send_message = array(
                    'ClientType' => 'server',
                    'ActionType' => 'publish',
                    'SubPubList' => ["worker_skud_in_out"],
                    'MessageToSend' => json_encode($date = array(
                        "type" => 'setStatusSkudInOrder',
                        "message" => json_encode([$item_result]))
                    ));

                /******************* Отправка на websocket  ******************/
                if ($client) {
                    $client->send(json_encode($send_message));
                    $warnings[] = 'Отправил данные';
                } else {
                    throw new Exception("actionWebSocket не смог подключиться к: ", AMICUM_CONNECT_STRING_WEBSOCKET, ". Проверьте доступ к WebSocket");
                }
            }
            /******************* Сохранение параметра *******************/
            $data_cache = array();
            $data_db = array();
            $worker_cache = new WorkerCacheController();
            foreach ($updated_data as $item) {
                $response = StrataJobController::saveWorkerParameter($item['worker_id'], 2, 529, $item['type_skud'], $item['date_time'], 1);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    //$errors[] = $response['errors'];
                    /*if ($response['date_to_cache']) {
                        $data_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $data_db[] = $response['date_to_db'];
                    }*/
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    continue;
                    //throw new \Exception(__FUNCTION__ . '. Ошибка сохранения параметра 529');
                }

                /*                $response = $worker_cache->setWorkerParameterValue($item['worker_id'], -1, 529, 2, $item['date_time'], $item['type_skud'], 1);
                                if ($response['status'] == 1) {
                                    $warnings[] = $response['warnings'];
                                    //$errors[] = $response['errors'];
                                } else {
                                    $warnings[] = $response['warnings'];
                //                    $errors[] = $response['errors'];
                                    $errors[] = "Для работника с табельным номером {$item['worker_id']}, отметкой {$item['type_skud']} и временем смены статуса {$item['date_time']} запись уже существует ";
                                    //throw new \Exception(__FUNCTION__ . '. Ошибка сохранения параметра 529 cache');
                                }*/
            }
            //$warnings['data'] = $data_db;
            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            /*if (isset($data_db)) {
                Yii::$app->db->createCommand()->batchInsert('worker_parameter_value',
                    ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'],
                    $data_db)->execute();
            }*/

            /**
             * блок массовой вставки значений в кеш
             */
            /*if (isset($data_cache)) {
                $warnings[] = $worker_sensor['worker_id'];
                $ask_from_method = (new WorkerCacheController)->multiSetWorkerParameterValue($worker_sensor['worker_id'], $data_cache);
                if ($ask_from_method['status'] == 1) {
                    $warnings[] = $ask_from_method['warnings'];
                    $warnings[] = 'saveLocationPacketWorkerParameters. обновил параметры работника в кеше';
                } else {
                    $warnings[] = $ask_from_method['warnings'];
                    $errors[] = $ask_from_method['errors'];
                    throw new \Exception('saveLocationPacketWorkerParameters. Не смог обновить параметры в кеше работника' . $worker_sensor['worker_id']);
                }
            }*/
            /***** Логирование в БД *****/
            $tabel_number = $session['userStaffNumber'];
//            $post = json_encode($post);
            $errors_insert = json_encode($errors);
            $result[] = $warnings;
            $duration_method = round(microtime(true) - $microtime_start, 6);                                              //расчет времени выполнения метода
            LogAmicum::LogEventAmicum(                                                                                      //записываем в журнал сведения о выполнении метода
                'WebsocketServerController/index',
                date("y.m.d H:i:s"), $duration_method, json_encode($result), $errors_insert, $tabel_number);
        } catch (Throwable $ex) {
            $errors[] = 'selectDataCMAS. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $errors[] = $ex->getFile();

            ///         $errors[] = $item['id'];
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод newSynchronizationSKUD() - переписанный метод по синхронизации СКУд + создает параметр для алкотеста
     * @return array
     * @package backend\controllers\serviceamicum
     * @example
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 03.10.2019 11:34
     * рефакторинг метода
     * алгоритм работы метода:
     * Проверка на незаконченные синхронизации
     * Вычисление следующего номера синхронизации
     * Вычисление времени последнего обновления
     * Получение массива работников для проверки на сущетвование работника в базе
     * Подключение к базе Oracle и получение статусов СКУД из PMS.SOURCE - берутся только поверхностные карты FTYPECARD=1 (т.к. подземные карты STARTA FtypeCard=2 создают много лишних записей, по той причине, что люди ходят возле ствола(надшахтного здания)
     * Вставка данных в промежуточную таблицу sap_skud_update
     * Отправка данных на websocket
     * Отправка на websocket
     * Сохранение параметра
     * Вставка 529 (Статус человека СКУД) параметра в амикум
     * Вставка  параметра 684 (Алкотестер) в амикум в
     * Составление массива для вставки в worker_parameter_value
     * Добавление данных в таблицу worker_parameter_value
     * Вставка значений в кэш
     *
     * Значения статусов СКУД
     * 1 - зашел в АБК
     * 2 - вышел с АБК
     * 3 - взял свет
     * 4 - отдал свет
     * 5 - отметка от светильника на поверхности
     * 6 - отметка от светильника в шахте
     * входные параметры:
     * $host - хост сервера СКУД (если не передат по берет по умолчанию)
     * $name_host - имя сервера СКУД (если не передат по берет по умолчанию)
     * $mine_id - идентификатор шахты с которого нужно снять данные (если не передат по берет по умолчанию с ЕСМО)
     * оптимизировать метод можно, если не писать историю в таблицу SapSkudUpdate
     * рефакторинг выполнил Якимов М.Н.
     * дата 28.01.2020
     */
    public static function newSynchronizationSKUD($host = null, $name_host = null, $mine_id = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("newSynchronizationSKUD");

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $log->addLog("Начало выполнения метода");
            $log->addLog("host: " . $host);
            $log->addLog("name_host: " . $name_host);
            $log->addLog("mine_id: " . $mine_id);

            if (!isset($host)) {
                $host = SKUD_HOST_NAME;
            }
            if (!isset($mine_id)) {
                $mine_id = ESMO_MINE;
            }
            if (!isset($name_host)) {
                $name_host = SKUD_SERVICE_NAME;
            }

            $status_sync = SapSkudUpdate::find()
                ->select(['num_sync'])
                ->where(['mine_id' => $mine_id])
                ->andWhere(['status' => 0])
                ->scalar();
            if ($status_sync) {
                throw new Exception('Есть не завершенные синхронизации SapSkudUpdate');
            }

            //  throw new \Exception($method_name . '. debugStop');
            /******************* Вычисление следующего номера синхронизации  *******************/
            $max_value = SapSkudUpdate::find()                                                                          //получение максимального номера синхронизации
            ->where(['mine_id' => $mine_id])
                ->max('num_sync');
            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }

            $log->addLog("Прошел проверки на последнюю синхронизацию и получил номер синхронизации");

            /******************* Вычисление времени последнего обновления *******************/
            $new_data_to_update = SapSkudUpdate::find()
                ->select('max(sap_skud_update.date_time)')
                ->where(['mine_id' => $mine_id])
                ->scalar();
            $new_data_to_update = date("Y-m-d H:i:s", strtotime($new_data_to_update));                           //приведение к типу date
            $new_data_now = date("Y-m-d H:i:s", strtotime(Assistant::GetDateNow()));                             //приведение к типу date

            $log->addData($new_data_to_update, 'Дата последней синхронизации: ', __LINE__);
            $log->addData($max_value, 'Номер синхронизации последний: ', __LINE__);
            $log->addData($num_sync, 'Номер синхронизации следующий: ', __LINE__);

            /******************* Получение массива работников для проверки на сущетвование работника в базе *******************/
            $workers_ids_db = (new Query())
                ->select('id')
                ->from('worker')
                ->indexBy('id')
                ->all();
            //                                     throw new \Exception($method_name . '. debugStop');

            $log->addLog("Получил справочник работников для проверки их существования в системе и последнюю добавленную дату");

            /******************* Подключение к базе Oracle *******************/
            $conn_oracle = oci_connect('Amicum_PS', 'y62#yZfl$U$e', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST =' . $host . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =' . $name_host . ')))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $log->addData(oci_error(), 'oci_error', __LINE__);
                $log->addLog('Соединение с Oracle не выполнено');
            } else {
                $log->addLog("Соединение с Oracle установлено");
            }
            $query_text = "SELECT CARDS.FTABN_SAP, TO_CHAR(SOURCE.DATE_OTM, 'YYYY-MM-DD HH24:MI:SS') as DATE_OTM, SOURCE.ID_TYPE FROM PMS.SOURCE
                        JOIN PMS.CARDS on SOURCE.ID_KART=CARDS.FNCARD AND (FBDTIME<TO_DATE('$new_data_now','YYYY-MM-DD HH24:MI:SS')) AND (FEDTIME>TO_DATE('$new_data_now','YYYY-MM-DD HH24:MI:SS'))
                        WHERE DATE_OTM > TO_DATE('$new_data_to_update','YYYY-MM-DD HH24:MI:SS') and ftypecard=1";

            $log->addData($query_text, '$query_text', __LINE__);

            $query = oci_parse($conn_oracle, $query_text);
            oci_execute($query);

            $log->addLog("Получил данные со СКУД Оракал");

            $count_break_ids = 0;
            $count_record = 0;
            $log->addLog("выполнил запрос к бд");

//                throw new \Exception($method_name . '. debugStop');
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $count_record++;


                $item['worker_id'] = (int)$row['FTABN_SAP'];
                if (!isset($workers_ids_db[$item['worker_id']])) {
                    $count_break_ids++;
                    $log->addLog('Ключ работника, которого нет в AMICUM: ' . $item['worker_id']);
                    continue;
                }

                $item['date_time'] = $row['DATE_OTM'];
                $item['type_skud'] = $row['ID_TYPE'];
                $item['num_sync'] = $num_sync;
                $item['mine_id'] = $mine_id;
                $item['status'] = 0;
                $skud_updates[] = $item;
            }
            $log->addLog('количество работников которых нет в AMICUM ' . $count_break_ids);
            $log->addLog('Все записи ' . $count_record);

            unset($row);
            unset($item);

            $log->addLog("Обработал данные с СКУД");

            if (isset($skud_updates)) {
                /******************* Вставка данных в промежуточную таблицу sap_skud_update *******************/
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('sap_skud_update', ['worker_id', 'date_time', 'type_skud', 'num_sync', 'mine_id', 'status'], $skud_updates)->execute();

                $log->addLog('Записей вставлено: ' . $insert_result_to_MySQL);

                // индексируем массив вставки скуд по работникам
                $skud_updates_by_worker = [];
                foreach ($skud_updates as $skud_update) {
                    $skud_updates_by_worker[$skud_update['worker_id']] = $skud_update;
                }

                unset($skud_updates);

                $log->addLog("Массовая вставка данных с СКУД в АМИКУМ");

                /******************* Сохранение параметра *******************/
                $worker_for_search = array();
                foreach ($skud_updates_by_worker as $item) {
                    $worker_parameter_insert[] = array(
                        'worker_object_id' => $item['worker_id'],
                        'parameter_id' => 529,
                        'parameter_type_id' => 2,
                    );

                    $worker_parameter_insert[] = array(
                        'worker_object_id' => $item['worker_id'],
                        'parameter_id' => 684,
                        'parameter_type_id' => 2,
                    );

                    $worker_for_search[] = $item['worker_id'];
                }
                /******************* Вставка 529/684 параметра *******************/

                if (!empty($worker_parameter_insert)) {
                    $global_insert = Yii::$app->db->queryBuilder->batchInsert('worker_parameter', ['worker_object_id', 'parameter_id', 'parameter_type_id'], $worker_parameter_insert);
                    $update_on_duplicate = Yii::$app->db->createCommand($global_insert . ' ON DUPLICATE KEY UPDATE
                `worker_object_id` = VALUES (`worker_object_id`), `parameter_id` = VALUES (`parameter_id`), `parameter_type_id` = VALUES (`parameter_type_id`)')->execute();
                    if ($update_on_duplicate !== 0) {
                        $log->addLog("Добавил/обновил данные в таблице worker_parameter");
                    }
                }

                $log->addLog("массово создал или обновил параметры работника");

                /******************* Составление массива для вставки в worker_parameter_value *******************/
// TODO - здесь может быть касяк!!!! worker_id!=worker_object!!! может влиять на отображение прошедших через турникет
                $worker_parameterObj_skud = WorkerParameter::find()
                    ->select(['id', 'worker_object_id'])
                    ->where(['parameter_type_id' => 2])
                    ->andWhere(['parameter_id' => 529])
                    ->andWhere(['IN', 'worker_object_id', $worker_for_search])
                    ->indexBy('worker_object_id')
                    ->asArray()
                    ->all();

                $worker_parameterObj_alc = WorkerParameter::find()
                    ->select(['id', 'worker_object_id'])
                    ->where(['parameter_type_id' => 2])
                    ->andWhere(['parameter_id' => 684])
                    ->andWhere(['IN', 'worker_object_id', $worker_for_search])
                    ->indexBy('worker_object_id')
                    ->asArray()
                    ->all();

                $log->addLog("Нашел айдишники конкретных параметров работника");

                $worker_parameter_value_alcotest = array();
                $worker_parameter_value_array_cache = array();

                foreach ($skud_updates_by_worker as $item) {
                    $date_time_work = date("Y-m-d", strtotime($item['date_time']));
                    $worker_parameter_value_array_cache[] = array(
                        'worker_parameter_id' => $worker_parameterObj_skud[$item['worker_id']]['id'],
                        'worker_id' => $item['worker_id'],
                        'date_time' => $item['date_time'],
                        'value' => $item['type_skud'],
                        'parameter_id' => 529,
                        'parameter_type_id' => 2,
                        'status_id' => 1,
                        'date_work' => $date_time_work,
                    );

                    $worker_parameter_value_array_db[] = array(
                        'worker_parameter_id' => $worker_parameterObj_skud[$item['worker_id']]['id'],
                        'date_time' => $item['date_time'],
                        'value' => $item['type_skud'],
                        'status_id' => 1,
                        'date_work' => $date_time_work,
                    );

                    $worker_parameter_value_array_cache[] = array(
                        'worker_parameter_id' => $worker_parameterObj_alc[$item['worker_id']]['id'],
                        'worker_id' => $item['worker_id'],
                        'date_time' => $item['date_time'],
                        'value' => $item['type_skud'],
                        'parameter_id' => 684,
                        'parameter_type_id' => 2,
                        'status_id' => 1,
                        'date_work' => $date_time_work,
                    );

                    $worker_parameter_value_array_db[] = array(
                        'worker_parameter_id' => $worker_parameterObj_alc[$item['worker_id']]['id'],
                        'date_time' => $item['date_time'],
                        'value' => $item['type_skud'],
                        'status_id' => 1,
                        'date_work' => $date_time_work,
                    );
                }

                $log->addLog("нашёл соответствия в worker_parameter и вытащил id параметров");

                /******************* Добавление данных в таблицу worker_parameter_value *******************/

                if (!empty($worker_parameter_value_array_db)) {
                    $global_insert_param_val = Yii::$app->db->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'date_work'], $worker_parameter_value_array_db);
                    $update_on_duplicate_value = Yii::$app->db->createCommand($global_insert_param_val . " ON DUPLICATE KEY UPDATE
                `worker_parameter_id` = VALUES (`worker_parameter_id`), `date_time` = VALUES (`date_time`), `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `date_work` = VALUES (`date_work`)")->execute();
                    if ($update_on_duplicate_value !== 0) {
                        $log->addLog("Добавил/обновил данные в таблице worker_parameter_value");
                    }
                }

                $log->addLog("Массово вставил 529 Статус СКУД");


                /******************* Вставка значений в кэш *******************/
                $worker_cache_controller = new WorkerCacheController();
                $insert_into_cache = $worker_cache_controller->multiSetWorkerParameterHash($worker_parameter_value_array_cache);
                if ($insert_into_cache !== 0) {
                    $log->addLog("Добавил данные в кэш");
                }

                $log->addLog("Закончил основной код");

                $update_sap_skud_update = SapSkudUpdate::UpdateAll(['status' => 1], 'mine_id=' . $mine_id . ' and num_sync=' . $num_sync);
                $log->addLog("Обновил статус синхронизации скуд на 1. Количество записей: " . $update_sap_skud_update);

                /******************* Отправка данных на websocket *******************/
                $client = new Client("ws://" . AMICUM_CONNECT_STRING_WEBSOCKET . "/ws");

                foreach ($skud_updates_by_worker as $item_result) {
                    $payload_item['worker_id'] = $item_result['worker_id'];
                    $payload_item['date_time'] = $item_result['date_time'];
                    $payload_item['type_skud'] = $item_result['type_skud'];

                    $temp_message = json_encode($payload_item);
                    $send_message = array(
                        'ClientType' => 'server',
                        'ActionType' => 'publish',
                        'SubPubList' => ["worker_skud_in_out"],
                        'MessageToSend' => json_encode($date = array(
                            "type" => 'setStatusSkudInOrder',
                            "message" => $temp_message)
                        ));
                    /******************* Отправка на websocket  ******************/
                    if ($client) {
                        $client->send(json_encode($send_message));
                    } else {
                        throw new Exception("actionWebSocket не смог подключиться к: ", AMICUM_CONNECT_STRING_WEBSOCKET, ". Проверьте доступ к WebSocket");
                    }
                }


                $log->addLog("отправил данные на вебсокет");

            } else {
                $log->addLog("Нет данных для вставки при синхронизации");
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Метод ppkSyncInjuctionMain() - метод, синхоронизирующий данных по предписанию ВНУТРЕННЕМУ - ГЛАВНЫЙ МЕТОД
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     *  (Метод не требует входных данных)
     *
     *  Выходные параметры:
     *  {
     *      "Items":{}
     *      "errors":{}                    // массив ошибок
     *      "status":1                    // статус выполнения метода
     *      "warnings":{}                // массив предупреждений
     *      "debug":{}                    // массив для отладки
     *  }
     *
     * АЛГОРИТМ:
     * 1. Вызов метода синхронизации главной таблицы проверок
     * 2. Получить все проверкис : идеинтификатор проверки и идентификатор внешнего предписания инлексируя по идеинтификатору внешнего предписания
     * 3. Вызвов метода синхронизации проверяющих
     * 4. Вызов метода синхронизации  раскидывание по таблицам (injunction, injunction_status, injunction_violation, injunction_violation_status, checking_place, correct_measures)
     *
     * ВАЖНО: при выполении метода должны обязательно присутствовать place_id=1 (Не заполнено) и violation_id=1 (Не заполнено)
     * @package backend\controllers\serviceamicum
     *Входные обязательные параметры: JSON со структрой:
     * @example
     *
     * @author
     * Created date: on 15.10.2019 10:02
     */
    public static function ppkSyncInjuctionMain()
    {
        $errors = array();
        $result = array();
        $warnings = array();
        $debug = array();
        $status = 1;
        $method = 'ppkSyncInjuction';
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;
        $result_dep = array();
        $updated = array();
        $get_full_data = array();
        $microtime_start = microtime(true);
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            // TODO вынести в дальнейшем в отдельный метод
            $mine_handbook = Mine::findOne(['id' => 1]);
            if (!$mine_handbook) {
                $new_mine = new Mine();
                $new_mine->id = 1;
                $new_mine->title = "Прочее";
                $new_mine->object_id = 40;
                $new_mine->company_id = 101;
                $new_mine->version_scheme = 1;
                if (!$new_mine->save()) {
                    $errors[] = $new_mine->errors;
                    throw new Exception($method . '. Ошибка инициализации дефолтных параметров для синхронизации Mine=1');
                }
            }
            unset($mine_handbook);
            unset($new_mine);

            $plast_handbook = Plast::findOne(['id' => 2109]);
            if (!$plast_handbook) {
                $new_plast = new Plast();
                $new_plast->id = 2109;
                $new_plast->title = "Прочее";
                $new_plast->object_id = 180;
                if (!$new_plast->save()) {
                    $errors[] = $new_plast->errors;
                    throw new Exception($method . '. Ошибка инициализации дефолтных параметров для синхронизации Plast=2109');
                }
            }
            unset($plast_handbook);
            unset($new_plast);

            $place_handbook = Place::findOne(['id' => 1]);
            if (!$place_handbook) {
                $new_place = new Place();
                $new_place->id = 1;
                $new_place->title = "Не заполнено";
                $new_place->mine_id = 1;
                $new_place->object_id = 180;
                $new_place->plast_id = 2109;
                if (!$new_place->save()) {
                    $errors[] = $new_place->errors;
                    throw new Exception($method . '. Ошибка инициализации дефолтных параметров для синхронизации Place=1');
                }
                HandbookCachedController::clearPlaceCache();
            }
            unset($place_handbook);
            unset($new_place);

            $violation_handbook = Violation::findOne(['id' => 1]);
            if (!$violation_handbook) {
                $new_violation = new Violation();
                $new_violation->id = 1;
                $new_violation->title = "Не заполнено";
                $new_violation->violation_type_id = 128;
                if (!$new_violation->save()) {
                    $errors[] = $new_violation->errors;
                    throw new Exception($method . '. Ошибка инициализации дефолтных параметров для синхронизации Violation=1');
                }
            }
            unset($violation_handbook);
            unset($new_violation);

            $checkingSapSpr = Checking::find()->select('id, instruct_id')
                ->andWhere('instruct_id is not null')
                ->indexBy('instruct_id')
                ->all();

            $response = self::ppkSynhChecking($checkingSapSpr);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $maxDateDocument = $response['maxDateDocument'];
                $debug['ppkSynhChecking'] = $response['debug'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method . '. Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }

            $checkingSapSpr = Checking::find()->select('id, instruct_id')
                ->andWhere('instruct_id is not null')
                ->indexBy('instruct_id')
                ->all();

            /** УКЛАДЫВАЕМ АУДИТОРОВ */
            $response = self::ppkSynhCheckingWorker($checkingSapSpr);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $auditorChecking = $response['auditorChecking'];
                $debug['ppkSynhCheckingWorker'] = $response['debug'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method . '. Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }

            /** УКЛАДЫВАЕМ ПРЕДПИСАНИЕ/НАРУШЕНИЕ */
            $response = self::ppkSynhInjunction($checkingSapSpr);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $debug['ppkSynhInjunction'] = $response['debug'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method . '. Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }

            $warnings[] = $method . '. закончил выполнять метод ' . $duration_method = round(microtime(true) - $microtime_start, 6);
        } catch
        (Throwable $ex) {
            $errors[] = $method . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }


        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    // RemoveDoubleEmployee - метод по удалению дубликатов после синхронизации сап и АМИКУМ ( у кого табельный номер не равен worker_id)
    public static function RemoveDoubleEmployee()
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $workers = Worker::find()
            ->where('worker.id!=worker.tabel_number')
            ->asArray()
            ->all();
        foreach ($workers as $worker) {
            try {
                Worker::deleteAll(['id' => $worker['id']]);
                Employee::deleteAll(['id' => $worker['id']]);
            } catch (Throwable $ex) {
                $errors[] = 'newSynchronizationSKUD. Исключение';
                $errors[] = 'newSynchronizationSKUD. Не смог удалить табельный' . $worker['id'];
                $errors[] = $ex->getMessage();
                $errors[] = $ex->getLine();
                $errors[] = $ex->getFile();
                $status = 0;
            }
        }
        $employees = Employee::find()
            ->leftJoin('worker', 'worker.employee_id=employee.id')
            ->where('worker.id is null')
            ->asArray()
            ->all();
        foreach ($employees as $employee) {
            try {
                Employee::deleteAll(['id' => $employee['id']]);
            } catch (Throwable $ex) {
                $errors[] = 'newSynchronizationSKUD. Исключение';
                $errors[] = 'newSynchronizationSKUD. Не смог удалить человека' . $worker['id'];
                $errors[] = $ex->getMessage();
                $errors[] = $ex->getLine();
                $errors[] = $ex->getFile();
                $status = 0;
            }
        }

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);
    }

// метод по удаления уволенных сотрудников
    public static function RemoveFireEmployee()
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $today = Assistant::GetDateFormatYMD();
        $workers = Worker::find()
            ->where('date_end<="' . $today . '"')
            ->andWhere('date_end is not null')
            ->asArray()
            ->all();
        foreach ($workers as $worker) {
            try {
                Worker::deleteAll(['id' => $worker['id']]);
                Employee::deleteAll(['id' => $worker['id']]);
            } catch (Throwable $ex) {
                $errors[] = 'RemoveFireEmployee. Исключение';
                $errors[] = 'RemoveFireEmployee. Не смог удалить табельный' . $worker['id'];
                $errors[] = $ex->getMessage();
                $errors[] = $ex->getLine();
                $errors[] = $ex->getFile();
                $status = 0;
            }
        }
        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);

        return $result_main;
    }

    public function updateYagok()
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        try {
            /******************* Подключение к базе Oracle *******************/
            $conn_oracle = oci_connect('SYSTEM', 'Administrator5', 'XE', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                 //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $query = oci_parse($conn_oracle, 'Select * from contact_view_yagok');
            oci_execute($query);
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {
                $fio = explode(" ", $row['FIO']);                                                                      //разделение строки ФИО
                $row['last_name'] = $fio[0];
                $row['first_name'] = $fio[1];
                $row['patronymic'] = $fio[2] ?? NULL;
                $row['HIRE_DATE'] = date('Y-m-d', strtotime($row['DATE_START']));                                      //изменение формата даты
                $row['GBDAT'] = date('Y-m-d', strtotime($row['GBDAT']));                                      //изменение формата даты
//                $rows['END'] = date('Y-m-d', strtotime($rows['END']));
                unset($row['FIO'], $row['DATE_START']);

                $query_result[] = $row;
            }
            Assistant::PrintR($query_result);
            die;
            $warnings[] = 'updateYagok обработал запрос из Oracle. ';

            /******************* Вставка данных в промежуточную таблицу sap_employee_full *******************/
//            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('sap_employee_full', ['worker_id', 'date_time', 'type_skud', 'num_sync'], $query_result)->execute();


        } catch (Throwable $ex) {
            $errors[] = 'updateYagok. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $errors[] = $ex->getFile();
            $status = 0;
        }

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings);

        return $result_main;

    }

    /**
     * Метод ppkCopyRefCheckmv() - копирование данных СПРАВОЧНИКА ВИДОВ ПРОВЕРОК из Oracle в промежуточные таблицы MySQL
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyRefCheckmv()
    {

        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkCopyRefCheckmv");

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнения метода");

            $response = $this->ppkCopyTable("IDB01.REF_CHECK_MV", "REF_CHECK_MV",
                [
                    "REF_CHECK_ID",
                    "NAME",
                    "SHORT_NAME",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"
                ],
                [
                    'REF_CHECK_ID',
                    'NAME',
                    'SHORT_NAME',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED'
                ]
            );
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('При выполнении метода копирования данных REF_CHECK_MV');
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        return array_merge(['Items' => 1], $log->getLogAll());
    }

    /**
     * Метод ppkCopyRefInstructionmv() - копирование данных внутренних предписаний из Oracle в промежуточные таблицы MySQL
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyInstructionmv()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyInstructionmv. ";

        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyInstructionmv. Начал выполнять метод";

            $response = $this->ppkCopyTable("IDB01.AMICUM_INSTRUCTION_MV", "AMICUM_INSTRUCTION_MV",
                [
                    "INSTRUCTION_ID",
                    "STRUCT_ID",
                    "TO_CHAR(DATE_INSTRUCTION, 'YYYY-MM-DD HH24:MI:SS') AS DATE_INSTRUCTION",
                    "CREATED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED",
                    "PAB_ID",
                    "DOP_TEXT1",
                    "DOP_TEXT2",
                    "ABPO_GR_ID",
                    "REF_INSTRUCTION_OPO_ID",
                    "IND_TYPE",
                    "HRSROOT_ID",
                    "TO_CHAR(DATE_SUSPEND_WORK, 'YYYY-MM-DD HH24:MI:SS') AS DATE_SUSPEND_WORK",
                    "TO_CHAR(DATE_RESUME_WORK, 'YYYY-MM-DD HH24:MI:SS') AS DATE_RESUME_WORK",
                    "WORKERS_PROSECUTED",
                    "INSTRUCTION_GIVERS_ID",
                    "INSTRUCTION_ID_IG",
                    "HRSROOT_ID_IG",
                    "CREATED_BY_IG",
                    "TO_CHAR(DATE_CREATED_IG, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED_IG",
                    "INSTRUCTION_POINT_ID",
                    "INSTRUCTION_ID_IP",
                    "NUM_POINT",
                    "DAN_EFFECT",
                    "DOC_LINK",
                    "ERROR_POINT",
                    "ACTION_NAME",
                    "TO_CHAR(DATE_PLAN, 'YYYY-MM-DD HH24:MI:SS') AS DATE_PLAN",
                    "TO_CHAR( DATE_FAKT, 'YYYY-MM-DD HH24:MI:SS') AS  DATE_FAKT",
                    "HRSROOT_ID_IP",
                    "LOOKOUT_ACTION_ID",
                    "ABPO_REPORT_ID",
                    "REF_ERROR_DIRECTION_ID",
                    "INSTRUCTION_RECEIVERS_ID",
                    "INSTRUCTION_ID_IR",
                    "HRSROOT_ID_IR",
                    "INSTRUCTION_RESULT_ID",
                    "INSTRUCTION_ID_IRES",
                    "NUM_RESULT",
                    "RESULT_CHECK",
                    "CREATED_BY_IRES",
                    "TO_CHAR( DATE_CREATED_IRES, 'YYYY-MM-DD HH24:MI:SS') AS  DATE_CREATED_IRES",
                    "TO_CHAR( DATE_RESULT, 'YYYY-MM-DD HH24:MI:SS') AS  DATE_RESULT"
                ],
                [
                    'INSTRUCTION_ID',
                    'STRUCT_ID',
                    'DATE_INSTRUCTION',
                    'CREATED_BY',
                    'DATE_MODIFIED',
                    'PAB_ID',
                    'DOP_TEXT1',
                    'DOP_TEXT2',
                    'ABPO_GR_ID',
                    'REF_INSTRUCTION_OPO_ID',
                    'IND_TYPE',
                    'HRSROOT_ID',
                    'DATE_SUSPEND_WORK',
                    'DATE_RESUME_WORK',
                    'WORKERS_PROSECUTED',
                    'INSTRUCTION_GIVERS_ID',
                    'INSTRUCTION_ID_IG',
                    'HRSROOT_ID_IG',
                    'CREATED_BY_IG',
                    'DATE_CREATED_IG',
                    'INSTRUCTION_POINT_ID',
                    'INSTRUCTION_ID_IP',
                    'NUM_POINT',
                    'DAN_EFFECT',
                    'DOC_LINK',
                    'ERROR_POINT',
                    'ACTION_NAME',
                    'DATE_PLAN',
                    'DATE_FAKT',
                    'HRSROOT_ID_IP',
                    'LOOKOUT_ACTION_ID',
                    'ABPO_REPORT_ID',
                    'REF_ERROR_DIRECTION_ID',
                    'INSTRUCTION_RECEIVERS_ID',
                    'INSTRUCTION_ID_IR',
                    'HRSROOT_ID_IR',
                    'INSTRUCTION_RESULT_ID',
                    'INSTRUCTION_ID_IRES',
                    'NUM_RESULT',
                    'RESULT_CHECK',
                    'CREATED_BY_IRES',
                    'DATE_CREATED_IRES',
                    'DATE_RESULT'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных AMICUM_INSTRUCTION_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyInstructionmv. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyInstructionmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyPabmv() - копирование данных ПАБ из Oracle в промежуточные таблицы MySQL
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyPabmv()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyPabmv. ";

        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyPabmv. Начал выполнять метод";

            $response = $this->ppkCopyTable("IDB01.AMICUM_PAB_N_N_MV", "AMICUM_PAB_N_N_MV",
                [
                    "PAB_ID",
                    "TO_CHAR(DT_BEG_AUDIT, 'YYYY-MM-DD HH24:MI:SS') AS DT_BEG_AUDIT",
                    "TO_CHAR(DT_END_AUDIT, 'YYYY-MM-DD HH24:MI:SS') AS DT_END_AUDIT",
                    "REF_PLACE_AUDIT_ID",
                    "HRSROOT_ID_IN_PAB",
                    "CREATED_BY_IN_PAB",
                    "TO_CHAR(DATE_CREATED_IN_PAB, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED_IN_PAB",
                    "METOD",
                    "LOOKOUT_ID_IN_L",
                    "NUM_LOOKOUT",
                    "WORK_HAND",
                    "DAN_EFFECT_IN_L",
                    "POSSIBLE_CONS",
                    "ACCEPT_EFFECT",
                    "MAKE_SUG",
                    "REF_FAILURE_EFFECT_ID",
                    "REF_DEC_MANAGER_ID",
                    "PAB_ID_IN_L",
                    "PR",
                    "REF_SITUATION_ID",
                    "MANAGER_HRSROOT_ID",
                    "REF_ERROR_DIRECTION_ID",
                    "DOC_LINK",
                    "ERROR_POINT",
                    "CHECK_RESULT",
                    "REF_NORM_DOC_ID",
                    "LOOKOUT_ACTION_ID",
                    "LOOKOUT_ID_IN_LA",
                    "ACTION_NAME",
                    "TO_CHAR(ACTION_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ACTION_DATE",
                    "HRSROOT_ID_IN_LA",
                    "INDUSTRIAL_OBJECT",
                    "TO_CHAR(DATE_STOP, 'YYYY-MM-DD HH24:MI:SS') AS DATE_STOP",
                    "WORKER_ID",
                    "HRSROOT_ID_IN_W",
                    "LOOKOUT_ID_IN_W",
                    "HRSROOT_ID_R_IN_W",
                    "REG_DAN_EFFECT_ID",
                    "TO_CHAR(DATE_CHECK, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CHECK",
                    "HRSROOT_ID_W",
                    "HRSROOT_ID_A",
                    "DAN_EFFECT_IN_RDE",
                    "TO_CHAR(DATE_TALK, 'YYYY-MM-DD HH24:MI:SS') AS DATE_TALK",
                    "NOTE_REPRES",
                    "REF_REPRES_ID",
                    "REF_CHECK_ID",
                    "REF_PLACE_AUDIT_ID_IN_RDE",
                    "TYPE_LOOKOUT_NAME",
                    "LOOKOUT_ID_IN_RDE",
                    "LOOKOUT_ACTION_ID_IN_RDE",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"
                ],
                [
                    'PAB_ID',
                    'DT_BEG_AUDIT',
                    'DT_END_AUDIT',
                    'REF_PLACE_AUDIT_ID',
                    'HRSROOT_ID_IN_PAB',
                    'CREATED_BY_IN_PAB',
                    'DATE_CREATED_IN_PAB',
                    'METOD',
                    'LOOKOUT_ID_IN_L',
                    'NUM_LOOKOUT',
                    'WORK_HAND',
                    'DAN_EFFECT_IN_L',
                    'POSSIBLE_CONS',
                    'ACCEPT_EFFECT',
                    'MAKE_SUG',
                    'REF_FAILURE_EFFECT_ID',
                    'REF_DEC_MANAGER_ID',
                    'PAB_ID_IN_L',
                    'PR',
                    'REF_SITUATION_ID',
                    'MANAGER_HRSROOT_ID',
                    'REF_ERROR_DIRECTION_ID',
                    'DOC_LINK',
                    'ERROR_POINT',
                    'CHECK_RESULT',
                    'REF_NORM_DOC_ID',
                    'LOOKOUT_ACTION_ID',
                    'LOOKOUT_ID_IN_LA',
                    'ACTION_NAME',
                    'ACTION_DATE',
                    'HRSROOT_ID_IN_LA',
                    'INDUSTRIAL_OBJECT',
                    'DATE_STOP',
                    'WORKER_ID',
                    'HRSROOT_ID_IN_W',
                    'LOOKOUT_ID_IN_W',
                    'HRSROOT_ID_R_IN_W',
                    'REG_DAN_EFFECT_ID',
                    'DATE_CHECK',
                    'HRSROOT_ID_W',
                    'HRSROOT_ID_A',
                    'DAN_EFFECT_IN_RDE',
                    'DATE_TALK',
                    'NOTE_REPRES',
                    'REF_REPRES_ID',
                    'REF_CHECK_ID',
                    'REF_PLACE_AUDIT_ID_IN_RDE',
                    'TYPE_LOOKOUT_NAME',
                    'LOOKOUT_ID_IN_RDE',
                    'LOOKOUT_ACTION_ID_IN_RDE',
                    'DATE_MODIFIED'
                ]
//                , "DATE_MODIFIED<TO_DATE('2018-01-01','YYYY-MM-DD HH24:MI:SS')"
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных AMICUM_PAB_N_N_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyPabmv. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyPabmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyRtnmv() - копирование данных ПРЕДПИСАНИЙ РТН из Oracle в промежуточные таблицы MySQL
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyRtnmv()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyRtnmv. ";
        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyRtnmv. Начал выполнять метод";

            $response = $this->ppkCopyTable("IDB01.AMICUM_ROSTEX_MV", "AMICUM_ROSTEX_MV",
                [
                    "INSTRUCTION_ROSTEX_ID",
                    "STRUCT_ID",
                    "ROSTEX_NOMER",
                    "TO_CHAR(ROSTEX_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ROSTEX_DATE",
                    "ROSTEX_FIO",
                    "HRSROOT_ID",
                    "DESC_ERROR",
                    "DESC_ACTION",
                    "TO_CHAR(DATE_PLAN, 'YYYY-MM-DD HH24:MI:SS') AS DATE_PLAN",
                    "TO_CHAR(DATE_FACT, 'YYYY-MM-DD HH24:MI:SS') AS DATE_FACT",
                    "TO_CHAR(DATE_TRANSFER, 'YYYY-MM-DD HH24:MI:SS') AS DATE_TRANSFER",
                    "TO_CHAR(DATE_RECIPIENT, 'YYYY-MM-DD HH24:MI:SS') AS DATE_RECIPIENT",
                    "DEF_WORK",
                    "INT_DOK",
                    "STOP_WORK",
                    "TO_CHAR(DATE_STOP_WORK, 'YYYY-MM-DD HH24:MI:SS') AS DATE_STOP_WORK",
                    "REF_ERROR_DIRECTION_ID",
                    "COLOR",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"
                ],
                [
                    'INSTRUCTION_ROSTEX_ID',
                    'STRUCT_ID',
                    'ROSTEX_NOMER',
                    'ROSTEX_DATE',
                    'ROSTEX_FIO',
                    'HRSROOT_ID',
                    'DESC_ERROR',
                    'DESC_ACTION',
                    'DATE_PLAN',
                    'DATE_FACT',
                    'DATE_TRANSFER',
                    'DATE_RECIPIENT',
                    'DEF_WORK',
                    'INT_DOK',
                    'STOP_WORK',
                    'DATE_STOP_WORK',
                    'REF_ERROR_DIRECTION_ID',
                    'COLOR',
                    'DATE_MODIFIED'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных AMICUM_ROSTEX_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyRtnmv. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyRtnmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyGiltyManager() - копирование данных СПРАВОЧНИК РЕШЕНИЙ РУКОВОДИТЕЛЯ из Oracle в промежуточные таблицы MySQL
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyGiltyManager()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyGiltyManager. ";

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            $warnings[] = "ppkCopyGiltyManager. Начал выполнять метод";
            $response = $this->ppkCopyTable("IDB01.REF_DEC_MANAGER_MV", "REF_DEC_MANAGER_MV",
                [
                    "REF_DEC_MANAGER_ID",
                    "NAME",
                    "SHORT_NAME",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"
                ],
                [
                    'REF_DEC_MANAGER_ID',
                    'NAME',
                    'SHORT_NAME',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных REF_DEC_MANAGER_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyGiltyManager. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyGiltyManager. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyErrorDirection() - копирование данных СПРАВОЧНИК НАПРАВЛЕНИЙ НАРУШЕНИЙ из Oracle в промежуточные таблицы MySQL
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyErrorDirection()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            $warnings[] = "ppkCopyErrorDirection. Начал выполнять метод";
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'ppkCopyErrorDirection. Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'ppkCopyErrorDirection. Соединение с Oracle установлено';
            }
            $query = oci_parse($conn_oracle, "SELECT   REF_ERROR_DIRECTION_ID
, NAME
,TO_CHAR(DATE_BEG, 'YYYY-MM-DD HH24:MI:SS') AS DATE_BEG 
,TO_CHAR(DATE_END, 'YYYY-MM-DD HH24:MI:SS') AS DATE_END 
, CREATED_BY
,TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED 
, MODIFIED_BY
,TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED 
, PARENT_ID
, SORT_ORDER FROM IDB01.REF_ERROR_DIRECTION_MV");
//            $query = oci_parse($conn_oracle, "SELECT max(DATE_CHECK) as DATE_CHECK FROM AMICUM_LOOKOUT_ACTION_MV where REG_DAN_EFFECT_ID is not null");
            oci_execute($query);
            $count = 0;
            $count_all = 0;
            $del_full_count = Yii::$app->db->createCommand()->delete('REF_ERROR_DIRECTION_MV')->execute();        // очитка промежуточной таблицы
            $warnings[] = "ppkCopyErrorDirection. удалил $del_full_count записей из таблицы REF_DEC_MANAGER_MV";
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $query_result[] = $row;
                $count++;
                $count_all++;
                /**
                 * Значение счётчика = 2000
                 *      да?     Массово добавить данные в промежуточную таблицу(REF_ERROR_DIRECTION_MV)
                 *              Очистить массив для вставки данных
                 *              Обнулить счётчик
                 *      нет?    Пропусить
                 */
                if ($count == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert('REF_ERROR_DIRECTION_MV', ['REF_ERROR_DIRECTION_ID'
                        , 'NAME'
                        , 'DATE_BEG'
                        , 'DATE_END'
                        , 'CREATED_BY'
                        , 'DATE_CREATED'
                        , 'MODIFIED_BY'
                        , 'DATE_MODIFIED'
                        , 'PARENT_ID'
                        , 'SORT_ORDER'], $query_result)->execute();
                    if ($insert_full === 0) {
                        throw new Exception('ppkCopyErrorDirection. Записи в таблицу REF_ERROR_DIRECTION_MV не добавлены');
                    } else {
                        $warnings[] = "ppkCopyErrorDirection. добавил - $insert_full - записей в таблицу REF_ERROR_DIRECTION_MV";
                    }
                    $query_result = [];
                    $count = 0;
                }
            }
            if (isset($query_result) && !empty($query_result)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert('REF_ERROR_DIRECTION_MV', ['REF_ERROR_DIRECTION_ID'
                    , 'NAME'
                    , 'DATE_BEG'
                    , 'DATE_END'
                    , 'CREATED_BY'
                    , 'DATE_CREATED'
                    , 'MODIFIED_BY'
                    , 'DATE_MODIFIED'
                    , 'PARENT_ID'
                    , 'SORT_ORDER'], $query_result)->execute();
                if ($insert_full === 0) {
                    throw new Exception('ppkCopyErrorDirection. Записи в таблицу REF_ERROR_DIRECTION_MV не добавлены');
                } else {
                    $warnings[] = "ppkCopyErrorDirection. добавил - $insert_full - записей в таблицу REF_ERROR_DIRECTION_MV";
                }
            }
            $warnings[] = "ppkCopyErrorDirection. количество добавляемых записей: " . $count_all;
            $warnings[] = "ppkCopyErrorDirection. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyErrorDirection. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
        return $result;
    }

    /**
     * Метод ppkCopyRefFailureEffectmv() - копирование данных СПРАВОЧНИК ПОСЛЕДСТВИЙ из Oracle в промежуточные таблицы MySQL
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyRefFailureEffectmv()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyRefFailureEffectmv. ";
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyRefFailureEffectmv. Начал выполнять метод";

            $response = $this->ppkCopyTable("IDB01.REF_FAILURE_EFFECT_MV", "REF_FAILURE_EFFECT_MV",
                [
                    "REF_FAILURE_EFFECT_ID",
                    "NAME",
                    "SHORT_NAME",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"
                ],
                [
                    'REF_FAILURE_EFFECT_ID',
                    'NAME',
                    'SHORT_NAME',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных REF_FAILURE_EFFECT_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyRefFailureEffectmv. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyRefFailureEffectmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод SyncBlobMain() - Синхронизация Файлов
     * @return array
     *
     * @package backend\controllers\serviceamicum
     * ОПИСАНИЕ ПОЛЕЙ: USER_BLOB_ID            идентификатор таблицы
     *                 BLOB_OBJ                Файл пользователя
     *                 FILE_NAME            Имя файла
     *                 CREATED_BY            Кем создан
     *                 DATE_CREATED            Дата создания
     *                 MODIFIED_BY            Кем модифицирован
     *                 DATE_MODIFIED        Дата модификации
     *                 TNAME                Имя таблицы, которой принадлежит BLOB (UPPER)
     *                 TID                    Уникальный идентификатор строки таблицы, которой принадлежит BLOB
     *
     * Алгоритм работы:
     *      Копирование:
     *              1. Из таблицы USERBLOBMV берётся максимальная дата модификации (max_date_sync)
     *              2. Выгрузить из оракла файлы у которых максимальная дата модификации выше полученной из USERBLOBMV
     *              3. Перебор выгруженных данных
     *                  3.1. Если поле блоб объекта не пусто
     *                      3.1.1. Проверить размер файла если он меньше 100 Мб
     *                              3.1.1.1. Загрузить содержимое блоба в переменную, инкремент счётчик добавления
     *                              3.1.1.2. Добавитьв  массив на добавление
     *                      3.1.2. Иначе добавить в массив на добавление
     *                  3.2. Если название файла больше 200 символов
     *                      3.2.1. Обрезать название файла до 200 символов
     *                  3.3. Если значение счётчика на добавление равен 100, массово вставить в таблицу синхронизации файлов
     *              4. Конец перебора
     *              5. Если в массиве на добавление остались данные, то массово вставить в таблицу синхронизации файлов
     *
     *      Синхронизация файлов:
     *              1. Выгрузить из таблицы синхронизации файлов по дате последней модификации (max_date_sync)
     *              2. Перебор полученных данных
     *                  2.1. Поиск документа по TID из таблицы синхронизации и ref_norm_doc_id из таблицы Докуметов
     *                      2.1.1. Если документ найден
     *                          2.1.1.1. Найти вложение по USER_BLOB_ID
     *                              2.1.1.1.1. Если не найдено тогда создать новое вложение
     *                              2.1.1.1.2. Положить блоб на сервер
     *                              2.1.1.1.3. сохранить вложение
     *                          2.1.1.2. Найти связку документа и вложения
     *                              2.1.1.2.1. Если связка найдена то меняем идентификатор вложения
     *                              2.1.1.2.2. Если связка не найдена тогда добавляем в массив на сохранение вложений документов
     *              3. Конец перебора полученных данных из таблицы синхронизации
     *              4. Если массив на добавление вложений дркументо не пуст то массово сохранить вложения документов
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 14.02.2020 13:39
     */
    public static function SyncBlobMain()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SyncBlobMain");

        try {
            $log->addLog("Начало выполнения метода");

            $oracle_controller = new SynchronizationController();
            $response = $oracle_controller->ppkCopyUserBlob();
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception(' Ошибка при  копировании вложений');
            }

            $maxDateDocument = $response['last_date_modified'];

            unset($response);
//            $maxDateDocument = '1970-01-01 03:00:00';
            $response = $oracle_controller->ppkSynhBlobDoc($maxDateDocument);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при  копировании вложений');
            }

            $log->addLog("Окончание выполнения метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод ppkCopyUserBlob() - копирование данных СПРАВОЧНИК ДОКУМЕНТОВ из Oracle в промежуточные таблицы MySQL
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyUserBlob()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkCopyUserBlob");

        $max_date = null;
        try {
            $log->addLog("Начало выполнения метода");
            Yii::$app->db->createCommand('SET SESSION wait_timeout = 28800;')->execute();
//            ini_set('max_execution_time', -1);
//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");


            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $log->addData(oci_error(), 'oci_error', __LINE__);
                $log->addLog('Соединение с Oracle не выполнено');
            } else {
                $log->addLog('Соединение с Oracle установлено');
            }

            $max_date = (new Query())
                ->select('max(DATE_MODIFIED)')
                ->from("USER_BLOB_MV")
                ->scalar();

            $max_date = date('Y-m-d H:i:s', strtotime($max_date));
            $query = oci_parse($conn_oracle, "SELECT
                    USER_BLOB_ID,
                    BLOB_OBJ,
                    FILE_NAME,
                    CREATED_BY,
                    TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED ,
                    MODIFIED_BY,
                    TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED,
                    TNAME,
                    TID
                    FROM IDB01.USER_BLOB_MV
                    where DATE_MODIFIED > TO_DATE('$max_date','YYYY-MM-DD HH24:MI:SS')");
            $log->addData($max_date, '$max_date', __LINE__);

            oci_execute($query);
            $count = 0;
            $count_all = 0;

            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                /**
                 * Есть документ
                 *      да?     Проверить размер документа
                 *                      больше 100МБ?   Пропустить
                 *                      меньше 100МБ?   Добавитьв  массив на добавление
                 *      нет?    Добавитьв  массив на добавление
                 */
                if ($row['BLOB_OBJ'] != null) {
                    if ($row['BLOB_OBJ']->size() < 104857600) {
                        $row['BLOB_OBJ'] = $row['BLOB_OBJ']->load();
                        if (!empty($row['BLOB_OBJ'])) {
                            $query_result[] = $row;
                            $count++;
                            $count_all++;
                        }
                    }
                } else {
                    $query_result[] = $row;
                    $count++;
                    $count_all++;
                }
                /**
                 * Если наименование файла больше 200 символов
                 *      да?     Обрезать до 200 символов
                 *      нет?    Пропустить
                 */
                if (strlen($row['FILE_NAME']) > 200) {
                    $row['FILE_NAME'] = mb_substr($row['FILE_NAME'], 0, 200);
                }

                /**
                 * Если набралось 100 записей тогда массовая вставка 100 записей
                 */
                if ($count == 5) {

                    $insert_full = Yii::$app->db->createCommand()->batchInsert('USER_BLOB_MV',
                        [
                            'USER_BLOB_ID',
                            'BLOB_OBJ',
                            'FILE_NAME',
                            'CREATED_BY',
                            'DATE_CREATED',
                            'MODIFIED_BY',
                            'DATE_MODIFIED',
                            'TNAME',
                            'TID'
                        ],
                        $query_result)->execute();
                    if ($insert_full === 0) {
                        throw new Exception('Записи в таблицу USER_BLOB_MV не добавлены');
                    } else {
                        $log->addLog("добавил - записи в таблицу USER_BLOB_MV", $insert_full);
                    }

                    $query_result = [];
                    $count = 0;
                }
            }
            /**
             * Если в массиве на добавление остались записи тогда добавляем их
             */
            if (!empty($query_result)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert('USER_BLOB_MV',
                    [
                        'USER_BLOB_ID',
                        'BLOB_OBJ',
                        'FILE_NAME',
                        'CREATED_BY',
                        'DATE_CREATED',
                        'MODIFIED_BY',
                        'DATE_MODIFIED',
                        'TNAME',
                        'TID'
                    ],
                    $query_result)->execute();
                if ($insert_full === 0) {
                    throw new Exception('Записи в таблицу USER_BLOB_MV не добавлены');
                } else {
                    $log->addLog("добавил - записи в таблицу USER_BLOB_MV", $insert_full);
                }
            }
            $log->addLog("количество добавляемых записей: ", $count_all);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result, 'last_date_modified' => $max_date], $log->getLogAll());
    }


    /**
     * Метод ppkCopyRefOPONumber() - копирование данных СПРАВОЧНИК РЕГИСТРАЦИОННЫХ НОМЕРОВ ОПО из Oracle в промежуточные таблицы MySQL REF_INSTRUCTION_OPO_MV
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyRefOPONumber()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyRefOPONumber. ";
        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyRefOPONumber. Начал выполнять метод";
            $response = $this->ppkCopyTable("IDB01.REF_INSTRUCTION_OPO_MV", "REF_INSTRUCTION_OPO_MV",
                [
                    "REF_INSTRUCTION_OPO_ID",
                    "OBJ_NAME",
                    "OPO_NOMER",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED",
                    "TO_CHAR(DATE_BEG, 'YYYY-MM-DD HH24:MI:SS') AS DATE_BEG",
                    "TO_CHAR(DATE_END, 'YYYY-MM-DD HH24:MI:SS') AS DATE_END"
                ],
                [
                    'REF_INSTRUCTION_OPO_ID',
                    'OBJ_NAME',
                    'OPO_NOMER',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED',
                    'DATE_BEG',
                    'DATE_END'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных REF_INSTRUCTION_OPO_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }


            $warnings[] = "ppkCopyRefOPONumber. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyRefOPONumber. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyRefNormDocmv() - копирование данных СПРАВОЧНИК Нормативных документов  из Oracle в промежуточные таблицы MySQL REF_NORM_DOC_MV
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyRefNormDocmv()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyRefNormDocmv. ";
        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyRefNormDocmv. Начал выполнять метод";

            $response = $this->ppkCopyTable("IDB01.REF_NORM_DOC_MV", "REF_NORM_DOC_MV",
                [
                    "REF_NORM_DOC_ID",
                    "PARENT_ID",
                    "NAME",
                    "TO_CHAR(DATE_BEG, 'YYYY-MM-DD HH24:MI:SS') AS DATE_BEG",
                    "TO_CHAR(DATE_END, 'YYYY-MM-DD HH24:MI:SS') AS DATE_END",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"
                ],
                [
                    'REF_NORM_DOC_ID',
                    'PARENT_ID',
                    'NAME',
                    'DATE_BEG',
                    'DATE_END',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных REF_NORM_DOC_MV ');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyRefNormDocmv. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyRefNormDocmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyRefPlaceAuditmv() - копирование данных СПРАВОЧНИК Мест аудита  из Oracle в промежуточные таблицы MySQL REF_PLACE_AUDIT_MV
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyRefPlaceAuditmv()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyRefPlaceAuditmv. ";
        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyRefPlaceAuditmv. Начал выполнять метод";

            $response = $this->ppkCopyTable("IDB01.REF_PLACE_AUDIT_MV", "REF_PLACE_AUDIT_MV",
                [
                    "REF_PLACE_AUDIT_ID",
                    "PARENT_ID",
                    "FN",
                    "NAME",
                    "SHORT_NAME",
                    "TO_CHAR(DATE_BEG, 'YYYY-MM-DD HH24:MI:SS') AS DATE_BEG",
                    "TO_CHAR(DATE_END, 'YYYY-MM-DD HH24:MI:SS') AS DATE_END",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED",
                    "METOD"
                ],
                [
                    'REF_PLACE_AUDIT_ID',
                    'PARENT_ID',
                    'FN',
                    'NAME',
                    'SHORT_NAME',
                    'DATE_BEG',
                    'DATE_END',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED',
                    'METOD'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных REF_PLACE_AUDIT_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyRefPlaceAuditmv. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyRefPlaceAuditmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyRefRepresmv() - копирование данных СПРАВОЧНИК ВЗЫСКАНИЙ  из Oracle в промежуточные таблицы MySQL REF_REPRES_MV
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyRefRepresmv()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyRefRepresmv. ";
        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyRefRepresmv. Начал выполнять метод";

            $response = $this->ppkCopyTable("IDB01.REF_REPRES_MV", "REF_REPRES_MV",
                [
                    "REF_REPRES_ID",
                    "NAME",
                    "SHORT_NAME",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"
                ],
                [
                    'REF_REPRES_ID',
                    'NAME',
                    'SHORT_NAME',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных REF_REPRES_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyRefRepresmv. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyRefRepresmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyRefSituationmv() - копирование данных СПРАВОЧНИК ОБСТОЯТЕЛЬСТВ  из Oracle в промежуточные таблицы MySQL REF_SITUATION_MV
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyRefSituationmv()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyRefSituationmv. ";
        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");

            $warnings[] = "ppkCopyRefSituationmv. Начал выполнять метод";
            $response = $this->ppkCopyTable("IDB01.REF_SITUATION_MV", "REF_SITUATION_MV",
                [
                    "REF_SITUATION_ID",
                    "NAME",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED",
                    "TO_CHAR(DATE_BEG, 'YYYY-MM-DD HH24:MI:SS') AS DATE_BEG",
                    "TO_CHAR(DATE_END, 'YYYY-MM-DD HH24:MI:SS') AS DATE_END",
                    "IND_ANALIZ",
                    "METOD"
                ],
                [
                    'REF_SITUATION_ID',
                    'NAME',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED',
                    'DATE_BEG',
                    'DATE_END',
                    'IND_ANALIZ',
                    'METOD'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных REF_SITUATION_MV');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyRefSituationmv. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyRefSituationmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkSynchRefNormDocmv() - Метод синхронизации справочника нормативных документов выгруженного из Оракл в АМИКУМ ППК ПАБ
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public static function ppkSynchRefNormDocmv()
    {
        // Стартовая отладочная информация
        $method_name = 'ppkSynchRefNormDocmv';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;

        //базовые параметры скрипта
        $errors = array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            // ПРЕДУСТАНОВКИ
            $filter = null;                                                   // фильтр для выборки данных для синхронизации
            $fieldArray = array('id', 'ref_norm_doc_id', 'parent_document_id', 'title', 'date_start', 'date_end', 'status_id', 'vid_document_id', 'date_time_sync', 'worker_id');
            $table_insert = 'document';
            $table_source = 'REF_NORM_DOC_MV';
            // фильтр для выборки данных для синхронизации
            // находим последнюю дату синхронизации по справочнику
            $maxDateDocument = (new Query())
                ->select('max(date_time_sync)')
                ->from($table_insert)
                ->scalar();
            $warnings[] = "ppkSynchRefNormDocmv. максимальная дата для обработки записи" . $maxDateDocument;
            if ($maxDateDocument) {
                $filter = "DATE_MODIFIED>='" . $maxDateDocument . "'";
            }
            $warnings[] = "ppkSynchRefNormDocmv. максимальная дата для обработки записи" . $filter;
            // получаем справочник документов ППК ПАБ нормативных документов для синхронизации
            $refDocuments = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filter)
                ->all();
            if (!$refDocuments) {
                throw new Exception('ppkSynchRefNormDocmv. Справочник для синхронизации пуст');
            }

            /** Отладка */
            $description = 'Выгрузили справочник нормативных документов';                                                                      // описание текущей отладочной точки
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

//            $warnings[] = "ppkSynchRefNormDocmv. Справочник ППК ПАБ получен";
//            $warnings[] = $refDocuments;

            // начинаем проверять документы на обновление добавление
            $date_now = Assistant::GetDateNow();
            foreach ($refDocuments as $refDocument) {
                $amicumDocument = Document::find()->where(['ref_norm_doc_id' => $refDocument['REF_NORM_DOC_ID']])->limit(1)->one();
                if (!$amicumDocument) {
                    $batch_insert_item['id'] = $refDocument['REF_NORM_DOC_ID'];
                    $batch_insert_item['ref_norm_doc_id'] = $refDocument['REF_NORM_DOC_ID'];
                    $batch_insert_item['parent_document_id'] = $refDocument['PARENT_ID'];
                    $batch_insert_item['title'] = $refDocument['NAME'];
                    if ($refDocument['DATE_BEG']) {
                        $batch_insert_item['date_start'] = $refDocument['DATE_BEG'];
                    } else {
                        $batch_insert_item['date_start'] = $date_now;
                    }
                    if ($refDocument['DATE_BEG']) {
                        $batch_insert_item['date_end'] = $refDocument['DATE_BEG'];
                    } else {
                        $batch_insert_item['date_end'] = $date_now;
                    }
                    $batch_insert_item['status_id'] = 1;
                    $batch_insert_item['vid_document_id'] = 1;
                    $batch_insert_item['date_time_sync'] = $refDocument['DATE_MODIFIED'];
                    $batch_insert_item['worker_id'] = 1;
                    $batch_insert_array[] = $batch_insert_item;
                    $count_add++;
                    $count_add_full++;
                } else {
                    $amicumDocument->id = $refDocument['REF_NORM_DOC_ID'];
                    $amicumDocument->ref_norm_doc_id = $refDocument['REF_NORM_DOC_ID'];
                    $amicumDocument->parent_document_id = $refDocument['PARENT_ID'];
                    $amicumDocument->title = $refDocument['NAME'];
                    if ($refDocument['DATE_BEG']) {
                        $amicumDocument->date_start = $refDocument['DATE_BEG'];
                    }
                    if ($refDocument['DATE_END']) {
                        $amicumDocument->date_end = $refDocument['DATE_END'];
                    }
                    $amicumDocument->status_id = 1;
                    $amicumDocument->vid_document_id = VidDocumentEnumController::NORMATIVE_DOCUMENT;
                    $amicumDocument->date_time_sync = $refDocument['DATE_MODIFIED'];
                    if ($amicumDocument->save()) {
                        $count_update++;
                    } else {
                        $errors[] = "ppkSynchRefNormDocmv. Не смог обновить запись";
                        $errors[] = $refDocument;
                        $errors[] = $amicumDocument->errors;
                    }
                }
                // делаем массовую вставку данных справочника
                if ($count_add == 2000) {
                    $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert($table_insert, $fieldArray, $batch_insert_array);
                    $insert_full = Yii::$app->db
                        ->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `id` = VALUES (`id`),`parent_document_id` = VALUES (`parent_document_id`),`title` = VALUES (`title`),`date_start` = VALUES (`date_start`),`date_end` = VALUES (`date_end`),`status_id` = VALUES (`status_id`),`ref_norm_doc_id` = VALUES (`ref_norm_doc_id`),`date_time_sync` = VALUES (`date_time_sync`)')
                        ->execute();
                    if ($insert_full === 0) {
                        throw new Exception('ppkSynchRefNormDocmv. Записи в таблицу documents не добавлены');
                    } else {
                        $warnings[] = "ppkSynchRefNormDocmv. добавил - $insert_full - записей в таблицу documents";
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }

            /** Отладка */
            $description = 'Выполнили перебор и зкинули основную часть данных';                                                                      // описание текущей отладочной точки
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

            // доталкиваем остатки
            // делаем массовую вставку данных справочника
            if (!empty($batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert($table_insert, $fieldArray, $batch_insert_array);
                $insert_full = Yii::$app->db
                    ->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `id` = VALUES (`id`),`parent_document_id` = VALUES (`parent_document_id`),`title` = VALUES (`title`),`date_start` = VALUES (`date_start`),`date_end` = VALUES (`date_end`),`status_id` = VALUES (`status_id`),`ref_norm_doc_id` = VALUES (`ref_norm_doc_id`),`date_time_sync` = VALUES (`date_time_sync`)')
                    ->execute();
                if ($insert_full === 0) {
                    throw new Exception('ppkSynchRefNormDocmv. Записи в таблицу documents не добавлены');
                } else {
                    $warnings[] = "ppkSynchRefNormDocmv. добавил - $insert_full - записей в таблицу documents";
                }
                unset($batch_insert_array);
            }

            /** Отладка */
            $description = 'Докинули остальные данные по нормативным документам';                                                                      // описание текущей отладочной точки
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

        } catch (Throwable $ex) {
            $errors[] = 'ppkSynchRefNormDocmv. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        // запись в БД окончания выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
        return $result;
    }

    /**
     * Метод ppkSynchRefErrorDirectionmv() - Метод синхронизации справочника направлений нарушений выгруженного из Оракл в АМИКУМ ППК ПАБ
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public static function ppkSynchRefErrorDirectionmv()
    {
        // Стартовая отладочная информация
        $method_name = 'ppkSynchRefErrorDirectionmv';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;

        //параметры скрипта
        $errors = array();
        $status = 1;
        $warnings = array();
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;
        $method = 'ppkSynchRefErrorDirectionmv';
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $warnings[] = "$method. Начал выполнять метод";


            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            // ПРЕДУСТАНОВКИ
            $filter1 = null;                                                   // фильтр для выборки данных для синхронизации
            $filter2 = null;                                                   // фильтр для выборки данных для синхронизации
            $fieldArray1 = array('ref_error_direction_id', 'id', 'title', 'date_time_sync');
            $fieldArray2 = array('ref_error_direction_id', 'id', 'title', 'kind_violation_id', 'date_time_sync');
            $table_insert1 = 'kind_violation';
            $table_insert2 = 'violation_type';
            $table_source = 'REF_ERROR_DIRECTION_MV';

            // т.к. в синхронизации со стороны АМИКУМ УЧАВСТВУЕТ 2 таблицы то будем искать максимальную дату в двух таблицах
            // а затем сравнивать какая правильная
            // фильтр для выборки данных для синхронизации
            // находим последнюю дату синхронизации по справочнику
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync)')
                ->from($table_insert1)
                ->scalar();
            $warnings[] = "$method. максимальная дата для обработки записи" . $maxDateDocument1;
            if ($maxDateDocument1) {
                $filter1 = "DATE_MODIFIED>='" . $maxDateDocument1 . "' and PARENT_ID is null";
            } else {
                $filter1 = "PARENT_ID is null";
            }

            $maxDateDocument2 = (new Query())
                ->select('max(date_time_sync)')
                ->from($table_insert2)
                ->scalar();
            $warnings[] = "$method. максимальная дата для обработки записи" . $maxDateDocument2;
            if ($maxDateDocument1) {
                $filter2 = "DATE_MODIFIED>='" . $maxDateDocument2 . "' and PARENT_ID is not null";
            } else {
                $filter2 = "PARENT_ID is not null";
            }


            $warnings[] = "$method. максимальная дата для обработки записи" . $filter1;
            $warnings[] = "$method. максимальная дата для обработки записи" . $filter2;
            // получаем справочник документов ППК ПАБ нормативных документов для синхронизации
            $errorDirections = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filter1)
                ->all();
            if (!$errorDirections) {
                throw new Exception($method . '. Справочник для синхронизации пуст');
            }
            /** Отладка */
            $description = 'Выгрузили справочник направлений нарушений';                                                                      // описание текущей отладочной точки
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

            // начинаем проверять документы на обновление добавление
            foreach ($errorDirections as $errorDirection) {
                $kindViolation = KindViolation::find()->where(['ref_error_direction_id' => $errorDirection['REF_ERROR_DIRECTION_ID']])->limit(1)->one();
                if (!$kindViolation) {
                    $batch_insert_item['ref_error_direction_id'] = $errorDirection['REF_ERROR_DIRECTION_ID'];
                    $batch_insert_item['id'] = $errorDirection['REF_ERROR_DIRECTION_ID'];
                    $batch_insert_item['title'] = $errorDirection['NAME'];
                    $batch_insert_item['date_time_sync'] = $errorDirection['DATE_MODIFIED'];
                    $batch_insert_array[] = $batch_insert_item;
                    unset($batch_insert_item);
                    $count_add++;
                    $count_add_full++;
                } else {
                    $kindViolation->ref_error_direction_id = $errorDirection['REF_ERROR_DIRECTION_ID'];
                    $kindViolation->id = $errorDirection['REF_ERROR_DIRECTION_ID'];
                    $kindViolation->title = $errorDirection['NAME'];
                    $kindViolation->date_time_sync = $errorDirection['DATE_MODIFIED'];
                    if ($kindViolation->save()) {
                        $count_update++;
                    } else {
                        $errors[] = "ppkSynchRefNormDocmv. Не смог обновить запись kindViolation";
                        $errors[] = $errorDirection;
                        $errors[] = $kindViolation->errors;
                    }
                }
                // делаем массовую вставку данных справочника
                if ($count_add == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insert1, $fieldArray1, $batch_insert_array)->execute();
                    if ($insert_full === 0) {
                        throw new Exception($method . '. Записи в таблицу kindViolation не добавлены');
                    } else {
                        $warnings[] = "$method. добавил - $insert_full - записей в таблицу kindViolation";
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }

            /** Отладка */
            $description = 'Выполнили перебор и закинули основную часть данных по видам нарушений';                                                                      // описание текущей отладочной точки
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

            // доталкиваем остатки
            // делаем массовую вставку данных справочника
            if (!empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insert1, $fieldArray1, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception($method . '. Записи в таблицу kindViolation не добавлены');
                } else {
                    $warnings[] = "$method. добавил - $insert_full - записей в таблицу kindViolation";
                }
                unset($batch_insert_array);
            }
            $batch_insert_array = [];

            /** Отладка */
            $description = 'Докинули остаточный массив данных видов нарушений';                                                                      // описание текущей отладочной точки
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

            $warnings[] = "$method. количество последних добавляемых записей: " . $count_add;
            $warnings[] = "$method. количество добавляемых записей: " . $count_add_full;
            $warnings[] = "$method. количество обновленных записей: " . $count_update;
            $count_add = 0;
            $count_update = 0;
            // получаем справочник документов ППК ПАБ нормативных документов для синхронизации СТАДИЯ 2
            $errorDirections = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filter2)
                ->andWhere('')
                ->all();
            if (!$errorDirections) {
                throw new Exception($method . '. Справочник для синхронизации пуст');
            }

            /** Отладка */
            $description = 'Выгрузили справочник типов проверок';                                                                      // описание текущей отладочной точки
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

            $warnings[] = "$method. Справочник ППК ПАБ получен";
//            $warnings[] = $errorDirections;
            // начинаем проверять документы на обновление добавление
            foreach ($errorDirections as $errorDirection) {
                $violationType = ViolationType::find()->where(['ref_error_direction_id' => $errorDirection['REF_ERROR_DIRECTION_ID']])->limit(1)->one();
                if (!$violationType) {
                    $batch_insert_item['ref_error_direction_id'] = $errorDirection['REF_ERROR_DIRECTION_ID'];
                    $batch_insert_item['id'] = $errorDirection['REF_ERROR_DIRECTION_ID'];
                    $batch_insert_item['title'] = $errorDirection['NAME'];
                    $batch_insert_item['kind_violation_id'] = $errorDirection['PARENT_ID'];
                    $batch_insert_item['date_time_sync'] = $errorDirection['DATE_MODIFIED'];
                    $batch_insert_array[] = $batch_insert_item;
                    unset($batch_insert_item);
                    $count_add++;
                    $count_add_full++;
                } else {
                    $violationType->ref_error_direction_id = $errorDirection['REF_ERROR_DIRECTION_ID'];
                    $violationType->id = $errorDirection['REF_ERROR_DIRECTION_ID'];
                    $violationType->title = $errorDirection['NAME'];
                    $violationType->kind_violation_id = $errorDirection['PARENT_ID'];
                    $violationType->date_time_sync = $errorDirection['DATE_MODIFIED'];
                    if ($violationType->save()) {
                        $count_update++;
                    } else {
                        $errors[] = "ppkSynchRefNormDocmv. Не смог обновить запись ViolationType";
                        $errors[] = $errorDirection;
                        $errors[] = $violationType->errors;
                    }
                }
                // делаем массовую вставку данных справочника
                if ($count_add == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insert2, $fieldArray2, $batch_insert_array)->execute();
                    if ($insert_full === 0) {
                        throw new Exception($method . '. Записи в таблицу violation_type не добавлены');
                    } else {
                        $warnings[] = "$method. добавил - $insert_full - записей в таблицу violation_type";
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }

            /** Отладка */
            $description = 'Выполнили перебор и закинули основную часть данных по типам нарушений';                                                                      // описание текущей отладочной точки
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

            // доталкиваем остатки
            // делаем массовую вставку данных справочника
            if (!empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insert2, $fieldArray2, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception($method . '. Записи в таблицу violation_type не добавлены');
                } else {
                    $warnings[] = "$method. добавил - $insert_full - записей в таблицу violation_type";
                }
                unset($batch_insert_array);
            }

            /** Отладка */
            $description = 'Докинули остаточные данные по типам проверок';                                                                      // описание текущей отладочной точки
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


//            $warnings[] = $refDocument;

            $warnings[] = "$method. количество последних добавляемых записей: " . $count_add;
            $warnings[] = "$method. количество добавляемых записей: " . $count_add_full;
            $warnings[] = "$method. количество обновленных записей: " . $count_update;
            $warnings[] = "$method. Закончил выполнять метод";
            $kind_violation_other = KindViolation::findOne(['id' => 128]);
            if (isset($kind_violation_other) && empty($kind_violation_other)) {
                $add_violation_type = new ViolationType();
                $add_violation_type->id = 128;
                $add_violation_type->title = 'Прочее';
                $add_violation_type->kind_violation_id = 128;
                $add_violation_type->date_time_sync = $kind_violation_other->date_time_sync;
                $add_violation_type->ref_error_direction_id = $kind_violation_other->ref_error_direction_id;
                if ($add_violation_type->save()) {
                    $warnings[] = $method . '. Тип нарушения "Прочее" успешно создан';
                } else {
                    $errors[] = $add_violation_type->errors;
                    throw new Exception($method . '. Ошибка при добавлении Типа нарушения "Прочее"');
                }
            }
        } catch (Throwable $ex) {
            $errors[] = $method . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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

        // запись в БД окончания выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);
        $result = array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
        return $result;
    }

    /**
     * Метод ppkCopyHCMStructObjidView() - копирование данных СПРАВОЧНИК соответствий структурных подразделений сап и ППК ПАБ из Oracle в промежуточные таблицы MySQL HCM_STRUCT_OBJID_VIEW
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyHCMStructObjidView()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyHCMStructObjidView. ";
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyHCMStructObjidView. Начал выполнять метод";
            $response = $this->ppkCopyTableOnDuplicate("AMICUM.HCM_STRUCT_OBJID_VIEW", "HCM_STRUCT_OBJID_VIEW",
                [
                    "HCM_STRUCT_OBJID_ID",
                    "ZZORG",
                    "STRUCT_ID",
                    "OBJID",
                    "IND_TOP",
                    "FN",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED"
                ],
                [
                    'HCM_STRUCT_OBJID_ID',
                    'ZZORG',
                    'STRUCT_ID',
                    'OBJID',
                    'IND_TOP',
                    'FN',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED'
                ],
                "",
                [
                    'HCM_STRUCT_OBJID_ID'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных HCM_STRUCT_OBJID_VIEW');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyHCMStructObjidView. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyReppkCopyHCMStructObjidViewfOPONumber. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkCopyHCMHRSRootPernrView() - копирование данных СПРАВОЧНИК соответствий людей SAP и ППК ПАБ из Oracle в промежуточные таблицы MySQL HCM_HRSROOT_PERNR_VIEW
     * Входные параметры:
     * (метод не требует входных параметров)
     *
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     * @authorHCM_HRSROOT_PERNR_VIEW
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyHCMHRSRootPernrView()
    {
        $errors = array();
        $status = 1;
        $warnings = array();
        $debug = array();
        $method_name = "ppkCopyHCMHRSRootPernrView. ";
        try {
//            ini_set('max_execution_time', -1);

//            ini_set('memory_limit', "10500M");
            $warnings[] = "ppkCopyHCMStructObjidView. Начал выполнять метод";

            $response = $this->ppkCopyTable("AMICUM.HCM_HRSROOT_PERNR_VIEW", "HCM_HRSROOT_PERNR_VIEW",
                [
                    "HCM_HRSROOT_PERNR_ID",
                    "ZZORG",
                    "HRSROOT_ID",
                    "PERNR",
                    "CREATED_BY",
                    "TO_CHAR(DATE_CREATED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_CREATED",
                    "MODIFIED_BY",
                    "TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED",
                    "IND_CANDIDAT",
                    "IND_A6"
                ],
                [
                    'HCM_HRSROOT_PERNR_ID',
                    'ZZORG',
                    'HRSROOT_ID',
                    'PERNR',
                    'CREATED_BY',
                    'DATE_CREATED',
                    'MODIFIED_BY',
                    'DATE_MODIFIED',
                    'IND_CANDIDAT',
                    'IND_A6'
                ]
            );
            if ($response['status'] === 0) {
                $errors = array_merge($errors, $response['errors']);
                $warnings = array_merge($errors, $response['warnings']);
                $debug = array_merge($errors, $response['debug']);
                throw new Exception($method_name . 'при выполнении метода копирования данных HCM_HRSROOT_PERNR_VIEW');
            } else {
                $debug = array_merge($errors, $response['debug']);
                $warnings = array_merge($errors, $response['warnings']);
            }

            $warnings[] = "ppkCopyHCMHRSRootPernrView. Закончил выполнять метод";
        } catch (Throwable $ex) {
            $errors[] = 'ppkCopyHCMHRSRootPernrView. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => 1, 'errors' => $errors, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод ppkSynhChecking() - метод синхронизации главной таблицы проверок
     * @param $checkingSapSpr - справочник предписаний
     * @return array
     *
     * @package backend\controllers\serviceamicum
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     *{
     *      "Items":                    // фильтр
     *      "filterChecking":            // фильтр для поиска
     *      "maxDateDocument":            // максимальная дата обработки документа
     *      "errors":[]                    // массив ошибок
     *      "status":1                    // статус выполнения метода
     *      "warnings":[]                // массив предупреждений
     *      "debug":[]                    // массив для отладки
     *  }
     * АЛГОРИТМ:
     * 1. Находим максимальную дату синхронизации таблицы проверок (checking)
     * 2. Из представления выгрузить данные по полученной последней дате синхронизации
     * 3. Перебор полученных данных
     *      3.1 Поиск проверки по instruct_id (идентификатор предписания промежуточной таблицы)
     *      3.2 Проверка найдена
     *          да?     Изменяем данные проверки и сохраняем
     *          нет?    Добавляем в массив на массовую вставку
     *                  Увеличиваем счётчика на добавление
     *      3.2 Счётчик на добавление равен 2000?
     *          да?     Массово вставить данные в таблицу проверок (checking)
     *                  Очистить массив
     *                  Обнулить счётчик на добавление
     *          нет?    Пропустить
     * 4. Конец перебора
     * 5. Массив на вставку не пуст?
     *          да?     Массово вставить данные в таблицу проверок (checking)
     *          нет?    Пропустить
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 14.04.2020 8:33
     */
    public static function ppkSynhChecking($checkingSapSpr)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSynhChecking");

        $count_add = 0;
        $count_record = 0;
        $count_add_full = 0;
        $count_update = 0;
        $maxDateDocument1 = "";

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнение метода");

            /** СИНХРОНИЗАЦИЯ ПРОВЕРКИ **/
            $filterCheckingSap = null;                                                                                  // фильтр для выборки данных для синхронизации
            $fieldArrayChecking = array('title', 'date_time_start', 'date_time_end', 'checking_type_id', 'company_department_id', 'instruct_id', 'date_time_sync');
            $table_insertChecking = 'checking';
            $table_source = 'view_cheking';

            // находим последнюю дату синхронизации по справочнику
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync)')
                ->from($table_insertChecking)
                ->scalar();

            $log->addLog("Максимальная дата для обработки записи: " . $maxDateDocument1);

            if ($maxDateDocument1) {
                $filterCheckingSap = "DATE_MODIFIED>'" . $maxDateDocument1 . "'";
            }

            $log->addLog("Максимальная дата для обработки записи: " . $filterCheckingSap);

            // получаем справочник документов ППК ПАБ нормативных документов для синхронизации
            $view_checkings = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checkings) {
                throw new Exception('Справочник для синхронизации пуст');
            }

            $log->addLog("Выгрузили список проверок");

            // начинаем проверять документы на обновление добавление
            foreach ($view_checkings as $view_checking) {
                $count_record++;

                if (!isset($checkingSapSpr[$view_checking['INSTRUCTION_ID']])) {
                    $batch_insert_item['title'] = 'Предписание ' . $view_checking['DATE_MODIFIED'];
                    $batch_insert_item['date_time_start'] = $view_checking['DATE_INSTRUCTION'];
                    $batch_insert_item['date_time_end'] = $view_checking['DATE_INSTRUCTION'];
                    $batch_insert_item['checking_type_id'] = 1;
                    $batch_insert_item['company_department_id'] = $view_checking['OBJID'];
                    $batch_insert_item['instruct_id'] = $view_checking['INSTRUCTION_ID'];
                    $batch_insert_item['date_time_sync'] = $view_checking['DATE_MODIFIED'];
                    $batch_insert_array[] = $batch_insert_item;
                    unset($batch_insert_item);
                    $count_add++;
                    $count_add_full++;
                } else {
                    $checking = Checking::findOne(['instruct_id' => $view_checking['INSTRUCTION_ID']]);
                    $checking->date_time_start = $view_checking['DATE_INSTRUCTION'];
                    $checking->date_time_end = $view_checking['DATE_INSTRUCTION'];
                    $checking->company_department_id = $view_checking['OBJID'];
                    $checking->instruct_id = $view_checking['INSTRUCTION_ID'];
                    $checking->date_time_sync = $view_checking['DATE_MODIFIED'];
                    if (!$checking->save()) {
                        $log->addLog("Не смог обновить запись checking");
                        $log->addData($view_checking, '$view_checking', __LINE__);
                        $log->addData($checking->errors, '$checking->errors', __LINE__);
                    }
                    $count_update++;
                }
                // делаем массовую вставку данных справочника
                if ($count_add == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                    if ($insert_full === 0) {
                        throw new Exception('Записи в таблицу checking не добавлены');
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу checking");
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }
            $log->addLog("Выполнили перебор и добавили основную часть проверок");

            // доталкиваем остатки
            // делаем массовую вставку данных справочника
            if (!empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception('Записи в таблицу checking не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу checking");
                }
                unset($batch_insert_array);
            }


            $log->addLog("Добавили остаточный массив данных проверок");

            $log->addData([
                "количество последних добавляемых записей: " . $count_add,
                "количество добавляемых записей: " . $count_add_full,
                "количество обновленных записей: " . $count_update,
                "количество обработанных записей: " . $count_record,
            ], '$warnings:', __LINE__);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => 1, 'maxDateDocument' => $maxDateDocument1], $log->getLogAll());
    }

// ppkSynhCheckingWorker - метод синхронизации проверяющих

    /**
     * Метод ppkSynhCheckingWorker() - Метод синхронизации проверяющих
     * @param $checkingSapSpr - массив синхронизированных проверок (checking_id - идентификатор проверки, instruct_id - идентификатор предписания промежуточной таблицы)
     * @param $maxDateDocument - максимальная дата синхронизации
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * $checkingSapSpr - массив синхронизированных проверок (checking_id - идентификатор проверки, instruct_id - идентификатор предписания промежуточной таблицы)
     * $maxDateDocument - максимальная дата синхронизации
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     *  {
     *      "Items":1,
     *      "auditorChecking":{}                // массив аудиторов на проверке
     *      "errors":{}                            // массив ошибок
     *      "status":{}                            // статус выполнения метода
     *      "warnings":{}                        // массив предупреждений (ход выполнения метода)
     *      "debug":{}                            // массив отладки
     *  }
     *
     * АЛГОРИТМ:
     * 1. Получаем все данные из представления с последний даты синхроинзации
     * 2. Перебор полученных данных
     *      2.1 Провеяем наличие аудитора в таблице работников участвующих в проверке (checking_worker_type)
     *          Нету?    Добавляем в массив на массовую вставку
     *                  Инкримент счётчика на добавление
     *          Есть?    Изменяем идентификатор работника
     *                           идентификатор для синхроинзации
     *                           дату и время синхроинзации
     *      2.2 Если счётчик на добавление равен 2000?
     *          Да?        Массово вставить данные в таблицу работников участвующих в проверке (checking_worker_type)
     *                  Очистить массив
     *                  Обнулить счётчик на добавление
     *          Нет?    Пропустить
     * 3. Конец перебора
     * 4. Массив на добавление не пуст?
     *          Да?        Массово вставить данные в таблицу работников участвующих в проверке (checking_worker_type)
     *          Нет?    Пропустить
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * @retrun
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 14.04.2020 8:54
     */
    public static function ppkSynhCheckingWorker($checkingSapSpr): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSynhCheckingWorker");

        $count_add = 0;
        $count_all = 0;
        $count_add_full = 0;
        $count_update = 0;
        $auditorChecking = [];

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнение метода");

            $filterChecking = null;                                                                                     // фильтр для выборки данных для синхронизации
            $filterCheckingSap = null;                                                                                  // фильтр для выборки данных для синхронизации
            $fieldArrayChecking = array('worker_id', 'worker_type_id', 'checking_id', 'instruct_givers_id', 'date_time_sync');
            $table_insertChecking = 'checking_worker_type';
            $table_source = 'view_checking_worker_sap';

            // находим последнюю дату синхронизации по справочнику
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync)')
                ->from($table_insertChecking)
                ->scalar();

            $log->addLog("Максимальная дата для обработки записи: " . $maxDateDocument1);

            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync>='" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_CREATED_IG>='" . $maxDateDocument1 . "'";
            }


            $log->addLog("Максимальная дата для обработки записи: " . $filterChecking);

            // получаем справочник документов ППК ПАБ нормативных документов для синхронизации
            $view_checkings = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checkings) {
                throw new Exception('Справочник для синхронизации пуст');
            }

            $log->addLog("Выгрузили список типов работников на проверки");

            // начинаем проверять документы на обновление добавление
            foreach ($view_checkings as $view_checking) {
                $count_all++;
                $checkingWorker = CheckingWorkerType::findOne(['instruct_givers_id' => $view_checking['INSTRUCTION_GIVERS_ID']]);
                $checking_id = $checkingSapSpr[$view_checking['INSTRUCTION_ID_IG']]['id'];
                $auditorChecking[$checking_id]['worker_id'] = $view_checking['AUDITOR_ID'];
                $auditorChecking[$checking_id]['checking_id'] = $checking_id;
                if (!$checkingWorker) {
                    $batch_insert_item['worker_id'] = $view_checking['AUDITOR_ID'];
                    $batch_insert_item['worker_type_id'] = 1;
                    $batch_insert_item['checking_id'] = $checking_id;
                    $batch_insert_item['instruct_givers_id'] = $view_checking['INSTRUCTION_GIVERS_ID'];
                    $batch_insert_item['date_time_sync'] = $view_checking['DATE_CREATED_IG'];
                    $batch_insert_array[] = $batch_insert_item;
                    unset($batch_insert_item);
                    $count_add++;
                    $count_add_full++;
                } else {
                    $checkingWorker->worker_id = $view_checking['AUDITOR_ID'];
                    $checkingWorker->instruct_givers_id = $view_checking['INSTRUCTION_GIVERS_ID'];
                    $checkingWorker->date_time_sync = $view_checking['DATE_CREATED_IG'];
                    if ($checkingWorker->save()) {
                        $count_update++;
                    } else {
                        $errors[] = "Не смог обновить запись checkingWorker";
                        $errors[] = $view_checking;
                        $errors[] = $checkingWorker->errors;
                    }
                }
                // делаем массовую вставку данных справочника
                if ($count_add == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                    if ($insert_full === 0) {
                        throw new Exception('. Записи в таблицу ' . $table_insertChecking . ' не добавлены');
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу $table_insertChecking");
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }

            $log->addLog("Выполнили перебор и добавили основную часть проверяющих");

            // доталкиваем остатки
            // делаем массовую вставку данных справочника
            if (!empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception('. Записи в таблицу ' . $table_insertChecking . ' не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу $table_insertChecking");
                }
                unset($batch_insert_array);
            }

            $log->addLog("Докинули остаток массива проверяющих");

            $log->addData([
                "количество последних добавляемых записей: " . $count_add,
                "количество добавляемых записей: " . $count_add_full,
                "количество обновленных записей: " . $count_update,
                "количество обработанных записей: " . $count_all,
            ], '$warnings:', __LINE__);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_all);

        return array_merge(['Items' => 1, 'auditorChecking' => $auditorChecking], $log->getLogAll());
    }

    /**
     * Метод ppkSynhInjunction() - Расскидывание по таблицам данных промежуточной таблицы внутрненних предписаний
     * @param $checkingSapSpr
     * @param $maxDateDocument
     * @param $auditorChecking
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * $checkingSapSpr       - массив синхронизированных проверок (checking_id - идентификатор проверки, instruct_id - идентификатор предписания промежуточной таблицы)
     * $maxDateDocument      - максимальная дата синхронизации
     * $auditorChecking      - Массив аудиторов на проверке
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     *  {
     *      "Items":1,
     *      "errors":{}                            // массив ошибок
     *      "status":{}                            // статус выполнения метода
     *      "warnings":{}                        // массив предупреждений (ход выполнения метода)
     *      "debug":{}                            // массив отладки
     *  }
     *
     * АЛГОРИТМ:
     * 1.  Находим максимальную дату синхронизации
     * 2.  По максимальной дате синхронизации выгружаем данные из представления
     * 3.  Выгружаем виды нарушений идексирую по ref_error_direction_id
     * 4.  Выгружаем типы нарушений идексирую по ref_error_direction_id
     * 5.  Перебор полученных данных
     *      5.1 Получаем идентификатор проверки по идентификатору предписаний из представления
     *      5.2 Обработка места по наименованию, передача наименования в базовый контроллер, на возрват получаем идентификатор
     *      5.3 Удаляем все места проверки
     *      5.4 Создаём массив массив места проверки
     *      5.5 Создаём массив людей ответственных за устранение
     *      5.6 Получить предписание по instruct_id_ip (идентификатору предписания промежуточной таблицы)
     *              Найдено?    Находим предписание по: месту, работнику, виду документа, идентификатору проверки отсортированном в порядке убывания observation_number
     *                  instruct_id_ip найденного (по месту ...) предписания = найденному предписанию по instruct_id_ip
     *                      да?                Записываем в массив данных предписания найденного предписания (по месту...) идентификатор работника
     *                                      номер наблюдения = 0
     *                                      флаг изменения = true
     *                      нет?            Записываем в массив данных предписания найденного (по месту...) номер наблюдения
     *                                      идентификатор работника из представления
     *                                      флаг изменения = true
     *              Не найдено?    Находим предписание по: месту, работнику, виду документа, идентификатору проверки, номеру наблюдения = 0
     *                      Найдено?        флаг изменения = true
     *                                      идентификатор работника из представления
     *                      Не найдено?        Находим предписание по: месту, виду документа, идентификатору проверки, номеру наблюдения = 0
     *                                              Найдено?        флаг изменения = true
     *                                                              идентификатор работника из представления
     *                                              Не найдено?        флаг изменения = false
     *      5.7  Флаг изменения true?
     *              Да?        Заполняем данные предписания для изменения: плановую дату, фактическую даты, INSTRUCTION_POINT_ID, дату последней модификации
     *              Нет?    Заполняем данные предписания для сохранения: идентификатор места, идентификатор работника, идентификатор проверки, плановую дату, фактическую дату, участок, INSTRUCTION_POINT_ID, дату последней модификации
     *      5.8  Вызывам метод базового контроллера для сохранения/изменения предписания и передаём: данные предписания, флаг, модель предписания, номер наблюдения
     *      5.9  Если в результате выполнения базового контроллера есть ошибки вызываем исключение
     *      5.10 Записываем в массив статус предписания
     *      5.11 Поиск нарушения по описанию нарушения
     *              Найдено?                Взять идентификатор нарушения
     *              Не найдено?                Создать новое нарушение
     *                                      (Получение типа нарушения)
     *                                              Не пустой ли идентификатор направления нарушения
     *                                                  не пустой?    ищем вид нарушения по REF_ERROR_DIRECTION_ID
     *                                                      нашли?       ищем тип нарушения
     *                                                                   нашли?        берём идентификатор типа нарушения
     *                                                                   не нашли?    создаём новый тип нарушения
     *                                                      не нашли?    ищем тип нарушения
     *                                                                   нашли?        берём идентификатор типа нарушения
     *                                                                   не нашли?    значение по умолчанию
     *                                                  пустой?    значение по умолчанию
     *      5.12 Вызываем базовый метод для получения документа предписания по наименованию
     *      5.13 Если при выполнении базового метода получения документа были ошибки вызвать исключение
     *      5.14 Вызываем базовый метод получения пункта документа по нименованию
     *      5.15 Если при выполнении базового метода получения пункта документа были ошибки вызвать исключение
     *      5.16 Поиск нарушения предписания по: месту, нарушению, предписани, документу
     *              Найдено?        Изменяем данные нарушения предписания
     *              Не найдено?        Заполняем данные нарушения предписания
     *              Сохраняем
     *      5.17 Заполняем массив статусов нарушений предписаний
     *      5.18 Заполняем массив нарушителей предписания
     *      5.19 Вызываем базовый метод получения операции по наименованию
     *      5.20 Если при выполнении базового места получения операции по наименованию произошла ошибка вызываем исключение
     *      5.21 Удаляем все корректирующие мероприятия по идентификатору нарушения предписания
     *      5.22 Записываем в массив корректирующих мероприятий данные
     * 6.  Конец перебора
     * 7.  Перебор массива корректирующих мероприятий
     *      7.1     Формирование массива на добавление корректирующих мероприятий
     *      7.2     Увеличение счётчика на добавление
     *      7.3  Значение счётчика равно 2000?
     *              Да?                Массвово добавляем корректирующие мероприятия
     *                              Очистить массив на добавление корректирующих мероприятий
     *                              Обнулить счётчик на добавление
     *              Нет?            Пропустить
     * 8.  Конец перебора  массива корректирующих мероприятий
     * 9.  Массив на добавление корректирующих мероприятий не пустой
     * Да?                Массвово добавляем корректирующие мероприятия
     * Нет?            Пропусть
     * 10. Перебор массива нарушителей
     *      10.1  Формирование массива на добавление нарушителей
     *      10.2  Увеличение счётчика на добавление
     *      10.3  Значение счётчика равно 2000?
     *              Да?                Массвово добавляем нарушителей
     *                              Очистить массив на добавление нарушителей
     *                              Обнулить счётчик на добавление
     *              Нет?            Пропустить
     * 11. Конец перебора  массива нарушителей
     * 12. Массив на добавление нарушителей не пустой
     *              Да?                Массвово добавляем нарушителей
     *              Нет?            Пропусть
     * 13. Перебор массива мест проверок
     *      13.1  Формирование массива на добавление мест проверок
     *      13.2  Увеличение счётчика на добавление
     *      13.3  Значение счётчика равно 2000?
     *              Да?                Массвово добавляем места проверок
     *                              Очистить массив на добавление мест проверок
     *                              Обнулить счётчик на добавление
     *              Нет?            Пропустить
     * 14. Конец перебора  массива мест проверок
     * 15. Массив на добавление мест проверок не пустой
     *              Да?                Массвово добавляем места роверок
     *              Нет?            Пропусть
     * 16. Перебор массива ответственных
     *      16.1  Формирование массива на добавление ответственных
     *      16.2  Увеличение счётчика на добавление
     *      16.3  Значение счётчика равно 2000?
     *              Да?                Массвово добавляем ответственных
     *                              Очистить массив на добавление ответственных
     *                              Обнулить счётчик на добавление
     *              Нет?            Пропустить
     * 17. Конец перебора  массива ответственных
     * 18. Массив на добавление ответственных не пустой
     *              Да?                Массвово добавляем ответственных
     *              Нет?            Пропусть
     * 19. Перебор массива статусов предписаний
     *      19.1  Формирование массива на добавление статусов предписаний
     *      19.2  Увеличение счётчика на добавление
     *      19.3  Значение счётчика равно 2000?
     *              Да?                Массвово добавляем статусы предписаний
     *                              Очистить массив на добавление статусов предписаний
     *                              Обнулить счётчик на добавление
     *              Нет?            Пропустить
     * 20. Конец перебора  массива статусов предписаний
     * 21. Массив на добавление статусов предписаний не пустой
     *              Да?                Массвово добавляем статусы предписаний
     *              Нет?            Пропусть
     * 22. Перебор массива статусов нарушений предписаний
     *      22.1  Формирование массива на добавление статусов нарушений предписаний
     *      22.2  Увеличение счётчика на добавление
     *      22.3  Значение счётчика равно 2000?
     *              Да?                Массвово добавляем статусы нарушений предписаний
     *                              Очистить массив на добавление статусов нарушений предписаний
     *                              Обнулить счётчик на добавление
     *              Нет?            Пропустить
     * 23. Конец перебора  массива статусов нарушений предписаний
     * 24. Массив на добавление статусов нарушений предписаний не пустой
     *              Да?                Массвово добавляем статусы нарушений предписаний
     *              Нет?            Пропусть
     *
     * @package backend\controllers\serviceamicum
     *
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 14.04.2020 9:23
     */
    public static function ppkSynhInjunction($checkingSapSpr)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSynhInjunction");

        //параметры скрипта
        $gilty_array = array();                         // список выиновных ответственных за устранение нарушения
        $injunction_status = array();                   // список статусов предписаний
        $place_array = array();                         // список мест, в которых производилась проверка
        $violator_array = array();                      // список нарушителей для вставки в VIOLATOR
        $coorect_meassure_array = array();              // список корректирующих мероприятий
        $count_add = 0;                                 // количество добавленных записей
        $count_all = 0;                                 // количество обновленных записей
        $count_add_full = 0;                            // полное количество добавленных записей
        $count_update = 0;                              // количество обновленных записей
        $dateNow = Assistant::GetDateFormatYMD();       // текущая дата и время
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнение метода");


            $filterChecking = null;                                                                                     // фильтр для выборки данных для синхронизации основной таблицы в которую будем вставлять данные
            $filterCheckingSap = null;                                                                                  // фильтр для выборки данных для синхронизации по таблице из ППК ПАБ
            $fieldArrayChecking = array('place_id', 'worker_id', 'kind_document_id', 'rtn_statistic_status_id', 'checking_id', 'description', 'status_id', 'observation_number', 'company_department_id', 'instruct_id_ip', 'date_time_sync');
            $table_insertChecking = 'injunction';                                                                       // таблица в которую вставляем
            $table_source = 'view_checking_injunction_sap';                                                             // таблица из которой берем
//TODO касяк зашит не было поля создания предписания - потому взял поле дата создания проверки
            // находим последнюю дату синхронизации по сиску предписаний
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync)')
                ->from($table_insertChecking)
                ->scalar();

            $log->addLog("Максимальная дата для обработки записи: " . $maxDateDocument1);

            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync>'" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_MODIFIED>'" . $maxDateDocument1 . "'";
            }

            $log->addLog("Максимальная дата для обработки записи: " . $filterChecking);

            // получаем справочник документов ППК ПАБ нормативных документов для синхронизации
            $view_injunctions = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
//                ->andWhere("DATE_MODIFIED<='2021-01-01'")
                ->all();
            if (!$view_injunctions) {
                throw new Exception('СПИОК ПРЕДПИСАНИЙ для синхронизации пуст');
            }
            $injunction_controller = new InjunctionController(1, false);
            $kind_violations = KindViolation::find()
                ->select('id,title,ref_error_direction_id,date_time_sync')
                ->indexBy('ref_error_direction_id')
                ->asArray()
                ->all();

            $violation_types = ViolationType::find()
                ->select('id,ref_error_direction_id')
                ->indexBy('ref_error_direction_id')
                ->asArray()
                ->all();

            $log->addLog("Выгрузили предписания для внутренних проверок");

            // начинаем проверять документы на обновление добавление

            // перебираем предписания
            foreach ($view_injunctions as $view_injunction) {
                $count_all++;
                $injunctions = Injunction::findOne(['instruct_id_ip' => $view_injunction['INSTRUCTION_POINT_ID']]);
                // этот блок нужен для получения ключа работника внесшего предписание - т.к. в основной таблице САП сейчас  пусто, а так должно быть сразу готовое поле
                $checking_id = $checkingSapSpr[$view_injunction['INSTRUCTION_ID']]['id'];                               // ключ проверки

                $worker_id = $view_injunction['CREATOR_ID'];
                /**
                 * Обработка места
                 */
                $response = $injunction_controller::GetPlaceByTitle($view_injunction['PLACE_TITLE']);
                if ($response['status'] != 1) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении идентификатора места');
                }
                $place_id = $response['Items'];
                unset($response);
                /**
                 * Обработка места проверки
                 */
                $del_checking_place = CheckingPlace::deleteAll(['checking_id' => $checking_id]);
                unset($del_checking_place);
                $place_array[$checking_id][$place_id]['place_id'] = $place_id;                                          // ключ места, в котором производилась проверка
                $place_array[$checking_id][$place_id]['checking_id'] = $checking_id;                                    // ключ проверки

                if (!empty($view_injunction['RESPONSIBILITY_ID'])) {
                    $gilty_array[$checking_id][$place_id]['worker_id'] = $view_injunction['RESPONSIBILITY_ID'];// ответственный работник за устраненние нарушения
                    $gilty_array[$checking_id][$place_id]['checking_id'] = $checking_id;// ключ проверки
                }
                /**
                 * Обработка предписания
                 */
                $observation_number = null;
                if (empty($injunctions)) {
                    $injunctions = Injunction::findOne([
                        'place_id' => $place_id,
                        'worker_id' => $worker_id,
                        'kind_document_id' => 1,
                        'checking_id' => $checking_id,
                        'observation_number' => 0
                    ]);
                    if (empty($injunctions)) {
                        $injunctions = Injunction::findOne([
                            'place_id' => $place_id,
                            'kind_document_id' => 1,
                            'checking_id' => $checking_id,
                            'observation_number' => 0
                        ]);
                        if (empty($injunctions)) {
                            $change = false;
                        } else {
                            $change = true;
                            $injunction_data['worker_id'] = $worker_id;
                        }
                    } else {
                        $change = true;
                        $injunction_data['worker_id'] = $worker_id;
                    }
                } else {
                    $another_injunction = Injunction::find()
                        ->where([
                            'place_id' => $place_id,
                            'worker_id' => $worker_id,
                            'kind_document_id' => 1,
                            'checking_id' => $checking_id,
                        ])
                        ->orderBy('observation_number desc')
                        ->limit(1)
                        ->one();

                    $another_instruct_id_ip = isset($another_injunction->instruct_id_ip) ? $another_injunction->instruct_id_ip : null;
                    if ($injunctions->instruct_id_ip == $another_instruct_id_ip) {
                        $injunction_data['worker_id'] = $another_injunction->worker_id;
                        $observation_number = null;
                        $change = true;
                    } else {
                        $change = true;
                        $observation_number = isset($another_injunction->observation_number) ? $another_injunction->observation_number : null;
                        $injunction_data['worker_id'] = $worker_id;
                    }
                    unset($another_injunction);
                }
                if ($change) {
//                    $injunction_data['worker_id'] = $worker_id;
                    $injunction_data['date_plan'] = $view_injunction['DATE_PLAN'];
                    $injunction_data['date_fact'] = $view_injunction['DATE_FAKT'];
                    $injunction_data['INSTRUCTION_POINT_ID'] = $view_injunction['INSTRUCTION_POINT_ID'];
                    $injunction_data['DATE_MODIFIED'] = $view_injunction['DATE_MODIFIED'];
                } else {
                    $injunction_data['place_id'] = $place_id;
                    $injunction_data['worker_id'] = $worker_id;
                    $injunction_data['checking_id'] = $checking_id;
                    $injunction_data['date_plan'] = $view_injunction['DATE_PLAN'];
                    $injunction_data['date_fact'] = $view_injunction['DATE_FAKT'];
                    $injunction_data['company_department_id'] = $view_injunction['COMPANY_DEPARTMENT_ID'];
                    $injunction_data['INSTRUCTION_POINT_ID'] = $view_injunction['INSTRUCTION_POINT_ID'];
                    $injunction_data['DATE_MODIFIED'] = $view_injunction['DATE_MODIFIED'];
                }
                $response = $injunction_controller::ChangeOrSaveInjunction(
                    'InnerInjunction',
                    $change,
                    $injunction_data,
                    $injunctions,
                    $observation_number);
                if (!empty($response['errors'])) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка сохранения предписания Injunction');
                }

                $injunction_id = $response['injunction_id'];
                $inj_status_id = $response['injunction_status_id'];
                unset($response, $injunction_data, $injunctions, $observation_number, $change);
                /**
                 * записываем статусы
                 */
                $injunction_status[$injunction_id][$worker_id]['injunction_id'] = $injunction_id;
                $injunction_status[$injunction_id][$worker_id]['worker_id'] = $worker_id;
                $injunction_status[$injunction_id][$worker_id]['status_id'] = $inj_status_id;
                $injunction_status[$injunction_id][$worker_id]['date_time'] = $view_injunction['DATE_INSTRUCTION'];

                // ОБРАБАТЫВАЕМ ПУНКТЫ НАРУШЕНИЙ
                // описание нарушения может быть больше 1000 символов потому мы его обрезаем
                $str_len_violation = strlen($view_injunction['DAN_EFFECT']);                                            // вычисляем длину нарушения

                if ($str_len_violation > 1000) {
                    $violation_saving_title = mb_substr($view_injunction['DAN_EFFECT'], 0, 1000);                       // если длина больше 1000 символов, то мы ее обрезаем
                } else {
                    $violation_saving_title = $view_injunction['DAN_EFFECT'];                                           // иначе оставляем так как есть
                }

                unset($str_len_violation);

                $violation_saving_title = trim($violation_saving_title);                                                //убираем пробелы с начала и конца строки для того, что бы обеспечить гарантированный поиск совпадения

                // сперва создаем сами нарушения
                $violation = Violation::findOne(['title' => $violation_saving_title]);
                if (!$violation) {

                    $violation = new Violation();

                    if (!empty($violation_saving_title)) {
                        $violation->title = $violation_saving_title;
                        unset($violation_saving_title);
                        /**
                         *  Не пустой ли идентификатор направления нарушения
                         *    не пустой?    ищем вид нарушения по REF_ERROR_DIRECTION_ID
                         *                    нашли?        ищем тип нарушения
                         *                                    нашли?        берём идентификатор типа нарушения
                         *                                    не нашли?    создаём новый тип нарушения
                         *                    не нашли?    ищем тип нарушения
                         *                                    нашли?        берём идентификатор типа нарушения
                         *                                    не нашли?    значение по умолчанию
                         *    пустой?    значение по умолчанию
                         */
                        if ($view_injunction['REF_ERROR_DIRECTION_ID']) {
                            if (isset($kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['id']) && !empty($kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'])) {
                                if (isset($violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id']) && !empty($violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'])) {
                                    $violation_type_for_violation = $violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'];
                                } else {
                                    $response = $injunction_controller::ViolationTypeWithoutJson(
                                        $kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'],
                                        $kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['title'],
                                        $view_injunction['REF_ERROR_DIRECTION_ID'],
                                        $kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['date_time_sync']
                                    );
                                    if ($response['status'] != 1) {
                                        $log->addLogAll($response);
                                        throw new Exception('Ошибка при сохранении типа нарушения');
                                    }
                                    $violation_type_for_violation = $response['Items'];
                                    unset($response, $json);
                                }
                            } else {
                                if (isset($violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id']) && !empty($violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'])) {
                                    $violation_type_for_violation = $violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'];
                                } else {
                                    $violation_type_for_violation = 128;
                                }
                            }
                            $violation->violation_type_id = $violation_type_for_violation;
                        } else {
                            $violation->violation_type_id = 128;
                        }
                        if ($violation->save()) {
                            $violation->refresh();
                            $violation_id = $violation->id;
                        } else {
                            $log->addData($view_injunction['REF_ERROR_DIRECTION_ID'], '$view_injunction[REF_ERROR_DIRECTION_ID]:', __LINE__);
                            $log->addData($violation_type_for_violation, '$violation_type_for_violation:', __LINE__);
                            $log->addData($violation->errors, '$violation->errors', __LINE__);
                            throw new Exception('Ошибка сохранения Описания нарушения Violation');
                        }
                    } else {
                        $violation_id = 1;
                    }
                } else {
                    $violation_id = $violation->id;
                }
                unset($violation);
                // проверяем наличие нормативного документа
                // затем пункт/параграф нарушения

                $response = $injunction_controller::GetDocumentByTitleWitoutJson($view_injunction['DOC_LINK'], $worker_id);
                if ($response['status'] != 1) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении/создании документа');
                }
                $document_id = $response['Items'];
                unset($response, $json);

                // затем пункт/параграф нарушения
                $response = $injunction_controller::GetParagraphPbByTextWithoutJson($view_injunction['ERROR_POINT'], $document_id);
                if ($response['status'] != 1) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении/создании пункта документа');
                }
                $paragraph_pb_id = $response['Items'];
                unset($response, $json);

                // затем сами нарушения

                //ищем нарушение если они есть то обновляем, если нет то создаем
//                $inj_violation = InjunctionViolation::find()->where(['instruct_id_ip' => $view_injunction['INSTRUCTION_POINT_ID']])->limit(1)->one();
                $inj_violation = InjunctionViolation::findOne([
                    'place_id' => $place_id,
                    'violation_id' => $violation_id,
                    'injunction_id' => $injunction_id,
                    'document_id' => $document_id]);
                if (!$inj_violation) {                                                                                    // создаем с 0 если такого еще не было
                    $inj_violation = new InjunctionViolation();
                    $count_add++;
                    $count_add_full++;
                }                                                                                                       // обновляем если такое уже было
                $inj_violation->probability = 5;                                                                        // вероятность плохого исхода
                $inj_violation->gravity = 5;                                                                            // тяжесть плохого исхода
                $inj_violation->correct_period = $view_injunction['DATE_PLAN'];
                $inj_violation->injunction_id = $injunction_id;                                                         // ключ предписания
                $inj_violation->place_id = $place_id;                                                                   // ключ места в котором выписано предписание
                $inj_violation->violation_id = $violation_id;                                                           // ключ описания нарушения с привязкой к направлению нарушений
                $inj_violation->paragraph_pb_id = $paragraph_pb_id;                                                     // ключ параграфа нормативного документа
//                    $inj_violation->reason_danger_motion_id = null;                                                   // ключ причины опасного действия - используется в ПАБ
                $inj_violation->document_id = $document_id;                                                             // ключ нормативного документа, требования которого были нарушены
                // ключ работника внесшего предписание


                $inj_violation->instruct_id_ip = $view_injunction['INSTRUCTION_POINT_ID'];                                 // ключ проверки САП
                $inj_violation->date_time_sync = $view_injunction['DATE_MODIFIED'];                                      // дата создания проверки САП
                if ($inj_violation->save()) {
                    $inj_violation->refresh();
                    // формируем блок массовой вставки статусов предписания в таблицу injunction_status
                    $inj_violation_id = $inj_violation->id;
                    $count_update++;
                } else {
                    $log->addData($view_injunction, '$view_injunction', __LINE__);
                    $log->addData($inj_violation->errors, '$inj_violation->errors', __LINE__);
                    throw new Exception('Ошибка сохранения пункта нарушения документа InjunctionViolation');
                }
                unset($inj_violation);
                // ЗАТЕМ ПИШЕМ СТАТУСЫ НАРУШЕНИЙ  если у нарушения нет статуса, то мы его создаем, если есть то проверяем на изменение и если изменеилось то пишем новый статус
                $inj_violation_statuses[$inj_violation_id]['injunction_violation_id'] = $inj_violation_id;
                $inj_violation_statuses[$inj_violation_id]['status_id'] = $inj_status_id;
                $inj_violation_statuses[$inj_violation_id]['date_time'] = $dateNow;

                //затем нарушители - утрамбовываем их сперва в массив, для исключения дубляжа
                $gilty_worker_id = $view_injunction['RESPONSIBILITY_ID'];
                if (!empty($gilty_worker_id)) {
                    $del_violators = Violator::deleteAll(['injunction_violation_id' => $inj_violation_id]);
                    unset($del_violators);
                    $violator_array[$inj_violation_id][$gilty_worker_id]['worker_id'] = $gilty_worker_id;
                    $violator_array[$inj_violation_id][$gilty_worker_id]['injunction_violation_id'] = $inj_violation_id;
                }

                // затем делаем корректирующие мероприятия на массовую вставку
                // создаем операцию - корректирующее мероприятие
                $json = json_encode(['operation_title' => $view_injunction['ACTION_NAME']]);
                $response = $injunction_controller::GetOperationByTitle($json);
                if ($response['status'] != 1) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении/создании операции');
                }
                $operation_id = $response['Items'];
                unset($response, $json);

                if (!empty($view_injunction['RESPONSIBILITY_ID'])) {
                    $gilty_worker_id = $view_injunction['RESPONSIBILITY_ID'];
                } else {
                    $gilty_worker_id = 1;
                }
                $del_corr = CorrectMeasures::deleteAll(['injunction_violation_id' => $inj_violation_id]);
                unset($del_corr);
                $coorect_meassure_array[$inj_violation_id][$operation_id]['injunction_violation_id'] = $inj_violation_id;
                $coorect_meassure_array[$inj_violation_id][$operation_id]['worker_id'] = $gilty_worker_id;
                $coorect_meassure_array[$inj_violation_id][$operation_id]['operation_id'] = $operation_id;
                if (isset($view_injunction['DATE_PLAN']) && !empty($view_injunction['DATE_PLAN'])) {
                    $date_correct_measures = $view_injunction['DATE_PLAN'];
                } else {
                    $date_correct_measures = date('Y-m-d H:i:s', strtotime($view_injunction['DATE_INSTRUCTION'] . '+1 day'));
                }
                $status_correct = $injunction_controller::NewStatusForInjunction($view_injunction['DATE_PLAN'], $view_injunction['DATE_FAKT']);
                $coorect_meassure_array[$inj_violation_id][$operation_id]['date_time'] = $date_correct_measures;
                $coorect_meassure_array[$inj_violation_id][$operation_id]['status_id'] = $status_correct;
                $coorect_meassure_array[$inj_violation_id][$operation_id]['correct_measures_value'] = 1;
                unset ($status_correct);
            }
            unset($injunction_controller, $violation_types, $kind_violations);

            $log->addLog("Выполнили перебор и добавили основные данные по внутренним предписаниям");

            /** СОХРАНЕНИЕ КОРРЕКТИРУЮЩИХ МЕРОПРИЯТИЙ **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $coorect_meassure_count = 0;
            foreach ($coorect_meassure_array as $inj_viol_item) {
                foreach ($inj_viol_item as $operation_item) {
                    $coorect_meassure_batch_insert_item['injunction_violation_id'] = $operation_item['injunction_violation_id'];
                    $coorect_meassure_batch_insert_item['worker_id'] = $operation_item['worker_id'];
                    $coorect_meassure_batch_insert_item['operation_id'] = $operation_item['operation_id'];
                    $coorect_meassure_batch_insert_item['date_time'] = $operation_item['date_time'];
                    $coorect_meassure_batch_insert_item['status_id'] = $operation_item['status_id'];
                    $coorect_meassure_batch_insert_item['correct_measures_value'] = $operation_item['correct_measures_value'];
                    $coorect_meassure_batch_insert_array[] = $coorect_meassure_batch_insert_item;
                    $coorect_meassure_count++;

                    if ($coorect_meassure_count == 2000) {
                        $log->addLog("Начал вставку Корректирующих мероприятий");
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('correct_measures', ['injunction_violation_id', 'worker_id', 'operation_id', 'date_time', 'status_id', 'correct_measures_value'], $coorect_meassure_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `worker_id` = VALUES (`worker_id`), `operation_id` = VALUES (`operation_id`), `date_time` = VALUES (`date_time`), `status_id` = VALUES (`status_id`), `correct_measures_value` = VALUES (`correct_measures_value`)')->execute();

                        if ($insert_full === 0) {
                            $log->addLog("Записи в таблицу correct_measures не добавлены");
                        } else {
                            $log->addLog("добавил - $insert_full - записей в таблицу correct_measures");
                        }
                        $coorect_meassure_count = 0;
                        unset($coorect_meassure_batch_insert_array);
                        $coorect_meassure_batch_insert_array = array();
                    }
                }
            }
            unset($coorect_meassure_array);

            $log->addLog("Итоговый массив для массовой вставки в correct_measures");

            if (!empty($coorect_meassure_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('correct_measures', ['injunction_violation_id', 'worker_id', 'operation_id', 'date_time', 'status_id', 'correct_measures_value'], $coorect_meassure_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `worker_id` = VALUES (`worker_id`), `operation_id` = VALUES (`operation_id`), `date_time` = VALUES (`date_time`), `status_id` = VALUES (`status_id`), `correct_measures_value` = VALUES (`correct_measures_value`)')->execute();

                if ($insert_full === 0) {
                    $log->addLog("Записи в таблицу correct_measures не добавлены");
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу correct_measures");
                }
                unset($coorect_meassure_batch_insert_array);
            }
            $coorect_meassure_batch_insert_array = [];
            unset($inj_viol_item);
            unset($operation_item);
            unset($coorect_meassure_batch_insert_item);

            $log->addLog("Добавили корректирующие мероприятия");

            /** СОХРАНЕНИЕ НАРУШИТЕЛЕЙ в VIOLATOR **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $gilty_violator_count = 0;
            foreach ($violator_array as $inj_viol_item) {
                foreach ($inj_viol_item as $worker_item) {
                    $gilty_violator_batch_insert_item['injunction_violation_id'] = $worker_item['injunction_violation_id'];
                    $gilty_violator_batch_insert_item['worker_id'] = $worker_item['worker_id'];
                    $gilty_violator_batch_insert_array[] = $gilty_violator_batch_insert_item;
                    $gilty_violator_count++;

                    if ($gilty_violator_count == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('violator', ['injunction_violation_id', 'worker_id'], $gilty_violator_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `worker_id` = VALUES (`worker_id`)')->execute();

                        if ($insert_full === 0) {
                            $log->addLog("Записи в таблицу violator не добавлены");
                        } else {
                            $log->addLog("добавил - $insert_full - записей в таблицу violator");
                        }
                        $gilty_violator_count = 0;
                        unset($gilty_violator_batch_insert_array);
                        $gilty_violator_batch_insert_array = array();
                    }
                }
            }
            unset($violator_array);

            $log->addLog("Итоговый массив для массовой вставки в violator");

            if (!empty($gilty_violator_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('violator', ['injunction_violation_id', 'worker_id'], $gilty_violator_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `worker_id` = VALUES (`worker_id`)')->execute();

                if ($insert_full === 0) {
                    $log->addLog("Записи в таблицу violator не добавлены");
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу violator");
                }
                unset($gilty_violator_batch_insert_array);
            }
            $gilty_violator_batch_insert_array = [];
            unset($inj_viol_item);
            unset($worker_item);
            unset($gilty_violator_batch_insert_item);

            $log->addLog("Сохранили нарушителей внутренних предписаний");

            /** СОХРАНЕНИЕ МЕСТ ПРОВЕРОК МАССОВО **/
            $place_count = 0;
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            foreach ($place_array as $cheking_item) {
                foreach ($cheking_item as $place_item) {
                    $place_batch_insert_item['checking_id'] = $place_item['checking_id'];
                    $place_batch_insert_item['place_id'] = $place_item['place_id'];
                    $place_batch_insert_array[] = $place_batch_insert_item;

                    $place_count++;
                    if ($place_count == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_place', ['checking_id', 'place_id'], $place_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `place_id` = VALUES (`place_id`)')->execute();
                        if ($insert_full === 0) {
                            $log->addLog("Записи в таблицу checking_place не добавлены");
                        } else {
                            $log->addLog("добавил - $insert_full - записей в таблицу checking_place");
                        }
                        $place_count = 0;
                        unset($place_batch_insert_array);
                        $place_batch_insert_array = array();
                    }
                }
            }
            unset($place_array);

            $log->addLog("Итоговый массив для массовой вставки в checking_place");

            if (!empty($place_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_place', ['checking_id', 'place_id'], $place_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `place_id` = VALUES (`place_id`)')->execute();

                if ($insert_full === 0) {
                    $log->addLog("Записи в таблицу checking_place не добавлены");
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу checking_place");
                }
                unset($place_batch_insert_array);
            }
            $place_batch_insert_array = [];
            unset($cheking_item);
            unset($place_item);
            unset($place_batch_insert_item);

            $log->addLog("Сохраили места проверок");

            /** СОХРАНЕНИЕ НАРУШИТЕЛЕЙ ОТВЕСТВЕННЫХ ЗА УСТРАНЕНИЕ МАССОВО **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $gilty_count = 0;
            foreach ($gilty_array as $cheking_item) {
                foreach ($cheking_item as $worker_item) {
                    $gilty_batch_insert_item['checking_id'] = $worker_item['checking_id'];
                    $gilty_batch_insert_item['worker_id'] = $worker_item['worker_id'];
                    $gilty_batch_insert_item['worker_type_id'] = 2;
                    $gilty_batch_insert_array[] = $gilty_batch_insert_item;
                    $gilty_count++;

                    if ($gilty_count == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_worker_type', ['checking_id', 'worker_id', 'worker_type_id'], $gilty_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `worker_id` = VALUES (`worker_id`), `worker_type_id` = VALUES (`worker_type_id`)')->execute();

                        if ($insert_full === 0) {
                            $log->addLog("Записи в таблицу checking_worker_type не добавлены");
                        } else {
                            $log->addLog("добавил - $insert_full - записей в таблицу checking_worker_type");
                        }
                        $gilty_count = 0;
                        unset($gilty_batch_insert_array);
                        $gilty_batch_insert_array = array();
                    }
                }
            }
            unset($gilty_array);

            $log->addLog("Итоговый массив для массовой вставки в checking_worker_type");

            if (!empty($gilty_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_worker_type', ['checking_id', 'worker_id', 'worker_type_id'], $gilty_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `worker_id` = VALUES (`worker_id`), `worker_type_id` = VALUES (`worker_type_id`)')->execute();

                if ($insert_full === 0) {
                    $log->addLog("Записи в таблицу checking_worker_type не добавлены");
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу checking_worker_type");
                }
                unset($gilty_batch_insert_array);
            }
            $gilty_batch_insert_array = [];
            unset($cheking_item);
            unset($worker_item);
            unset($gilty_batch_insert_item);

            $log->addLog("Сохранили нарушителей ответсвтенных за устранение");

            /** СОХРАНЕНИЕ СТАУСОВ ПРЕДПИСАНИЙ МАССОВО **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $inj_status = 0;
            foreach ($injunction_status as $injunction_item) {
                foreach ($injunction_item as $worker_item) {
                    $inj_status_batch_insert_item['injunction_id'] = $worker_item['injunction_id'];
                    $inj_status_batch_insert_item['worker_id'] = $worker_item['worker_id'];
                    $inj_status_batch_insert_item['status_id'] = $worker_item['status_id'];
                    $inj_status_batch_insert_item['date_time'] = $worker_item['date_time'];
                    $inj_status_batch_insert_array[] = $inj_status_batch_insert_item;
                    $inj_status++;
                    if ($inj_status == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_status_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_id` = VALUES (`injunction_id`), `worker_id` = VALUES (`worker_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                        if ($insert_full === 0) {
                            $log->addLog("Записи в таблицу injunction_status не добавлены");
                        } else {
                            $log->addLog("добавил - $insert_full - записей в таблицу injunction_status");
                        }
                        $inj_status = 0;
                        unset($inj_status_batch_insert_array);
                        $inj_status_batch_insert_array = array();
                    }
                }
            }
            unset($injunction_status);
            unset($inj_status_batch_insert_item);
            unset($worker_item);
            unset($injunction_item);

            $log->addLog("Итоговый массив для массовой вставки в checking_place");

            if (!empty($inj_status_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_status_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_id` = VALUES (`injunction_id`), `worker_id` = VALUES (`worker_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();

                if ($insert_full === 0) {
                    $log->addLog("Записи в таблицу injunction_status не добавлены");
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу injunction_status");
                }
                unset($inj_status_batch_insert_array);
            }
            $inj_status_batch_insert_array = [];

            $log->addLog("Сохранили статусы внутренних предписаний");

            /******************** СОХРАНЕНИЕ СТАТУСОВ НАРУШЕНИЙ ПРЕДПИСАНИЙ ********************/
            $inj_viol_status_count = 0;
            foreach ($inj_violation_statuses as $inj_violation_status) {
                $inj_viol_status_batch_insert_item['injunction_violation_id'] = $inj_violation_status['injunction_violation_id'];
                $inj_viol_status_batch_insert_item['status_id'] = $inj_violation_status['status_id'];
                $inj_viol_status_batch_insert_item['date_time'] = $inj_violation_status['date_time'];
                $inj_viol_status_batch_insert_array[] = $inj_viol_status_batch_insert_item;
                $inj_viol_status_count++;
                if ($inj_viol_status_count == 2000) {
                    $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_violation_status', ['injunction_violation_id', 'status_id', 'date_time'], $inj_viol_status_batch_insert_array);
                    $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                    if ($insert_full === 0) {
                        $log->addLog("Записи в таблицу injunction_violation_status не добавлены");
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу injunction_violation_status");
                    }
                    $inj_viol_status_count = 0;
                    unset($inj_viol_status_batch_insert_array);
                    $inj_viol_status_batch_insert_array = array();
                }
            }
            unset($inj_violation_statuses);


            if (!empty($inj_viol_status_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_violation_status', ['injunction_violation_id', 'status_id', 'date_time'], $inj_viol_status_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                if ($insert_full === 0) {
                    $log->addLog("Записи в таблицу injunction_violation_status не добавлены");
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу injunction_violation_status");
                }
                unset($inj_viol_status_batch_insert_array);
            }

            $log->addLog("Добавили статусы нарушений предписаний");

            $log->addData([
                "количество последних добавляемых записей: " . $count_add,
                "количество добавляемых записей: " . $count_add_full,
                "количество обновленных записей: " . $count_update,
                "количество обработанных записей: " . $count_all,
            ], '$warnings:', __LINE__);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_all);

        return array_merge(['Items' => 1], $log->getLogAll());
    }

    /**
     * Метод ppkSynhBlobDoc() - метод синхронизации  вложений и связки (вложений и документов) по ref_norm_doc_id из таблицы USER_BLOB_MV
     * @return array
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 18.11.2019 13:20
     */
    public function ppkSynhBlobDoc($maxDateDocument)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $inserted_doc_attachment = array();
        $method_name = 'ppkSynhBlobDoc';
        $warnings[] = 'ppkSynhBlobDoc. Начало метода';
        $table_source = 'USER_BLOB_MV';
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $user_blobs = USERBLOBMV::find()
                ->where(['>', 'DATE_MODIFIED', $maxDateDocument]);
            /******************** Выгрзка и перебор по 100 записей (иначе ломается) ********************/
            foreach ($user_blobs->each(3) as $user_blob) {
                $date_now = date('d-m-Y H-i-s.U');
                /**
                 * Ищем документ по внешнему идентификатору строки которой принадлежит документ
                 */
                $blob_doc = Document::findOne(['ref_norm_doc_id' => $user_blob['TID']]);
                if ($blob_doc) {
                    $document_id = $blob_doc->id;
                    unset($blob_doc);
                    $file_name = $user_blob['FILE_NAME'];
                    if ($user_blob['BLOB_OBJ'] != null) {
//                        $normalize_path = FrontendAssistant::UploadFile($user_blob['BLOB_OBJ'], $substr_file_name, 'attachment', $type);
                        /**
                         * Найти вложение по идентификатору синхронизируемого блоба
                         *      не нашли?     Создаём новое вложение, заполняем часть данных
                         *      нашли?        Редактируем вложение
                         * Обрезать наименование до 100 символов начиная с конца строки
                         * Положить файл по указанному пути
                         *      file_put_contents вернула false
                         *            да?     Вызвать исключение
                         *            нет?    Добавить вложение
                         *      Есть ли связка документа и вложения
                         *            да?     Привязать документу только что сохранённое вложение
                         *            нет?    Добавить в массив на добавление
                         */
                        $attachment = Attachment::findOne(['USER_BLOB_ID' => $user_blob['USER_BLOB_ID']]);
                        if (empty($attachment)) {
                            $attachment = new Attachment();
                            $attachment->section_title = 'Синхронизация';
                            $attachment->attachment_type = null;
                            $attachment->sketch = null;
                            $attachment->worker_id = 1;
                        }
                        $attachment->title = $file_name;
                        $subst_for_save_file = mb_substr($file_name, -100, 100);
                        $uploaded_file_path = Yii::getAlias('@app') . '/web/img/attachment/' . $date_now . '_' . $subst_for_save_file;
                        $file = file_put_contents($uploaded_file_path, $user_blob['BLOB_OBJ']);
                        if (empty($file)) {
                            throw new Exception($method_name . '. Возникла ошибка при попытке положить файл (' . $file_name . ') по пути:' . $uploaded_file_path);
                        }
                        unset($file);
                        $path = '/img/attachment/' . $date_now . '_' . $subst_for_save_file;
                        $attachment->date = Assistant::GetDateFormatYMD();
                        $attachment->path = $path;
                        $attachment->date_modified = $user_blob['DATE_MODIFIED'];
                        $attachment->USER_BLOB_ID = $user_blob['USER_BLOB_ID'];
                        if ($attachment->save()) {
                            $attachment_id = $attachment->id;
                            $warnings[] = 'ppkSynhBlobDoc. Вложение успешно сохранено';
                        } else {
                            $errors[] = $attachment->errors;
                            throw new Exception('ppkSynhBlobDoc. Ошибка при сохранении вложения');
                        }
                        unset($attachment);
                        $doc_attachment = DocumentAttachment::findOne(['document_id' => $document_id]);
                        if (!empty($doc_attachment)) {
                            $doc_attachment->attachment_id = $attachment_id;
                            if (!$doc_attachment->save()) {
                                throw new Exception($method_name . '. Ошибка при редактировании связки документа и вложения');
                            }
                        } else {
                            $inserted_doc_attachment[] = [$document_id, $attachment_id];
                        }
                        unset($doc_attachment);
                    }
                }
            }
            if (!empty($inserted_doc_attachment)) {
                $result_inserted_doc_attchment = Yii::$app->db
                    ->createCommand()
                    ->batchInsert(
                        'document_attachment',
                        ['document_id', 'attachment_id'],
                        $inserted_doc_attachment
                    )
                    ->execute();
                if ($result_inserted_doc_attchment != 0) {
                    $warnings[] = 'ppkSynhBlobDoc. Связка вложений и документов на эти вложения успешно установлена';
                } else {
                    throw new Exception('ppkSynhBlobDoc. Ошибка при сохранении связки вложений и документов на эти вложения');
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'ppkSynhBlobDoc. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ppkSynhBlobDoc. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод ppkSynhInjunctionRTNMain() - Главный метод по сохранению предписаний РТН
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ6
     * (стандартный массив выходных данных)
     *
     * АЛГОРИТМ:
     * 1.    Вызов метода синхронизации главной таблицы проверок
     * 2.    Получить все проверки с: идентификатор проверки и идентификатор предписания РТН индексируя по идентификатору предписания РТН
     * 3.    Вызов метода синхронизации проверяющих
     * 4.    Вызов метода синхронизации раскидывайте по таблицам (injunction, injunction_status, injunction_violation, injunction_violation_status, checking_place, correct_measures)
     *
     * @package backend\controllers\serviceamicum
     *
     * @example http://amicum/synchronization-front/synchronization (при условии что там закоменчены остальные методы)
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.12.2019 7:43
     */
    public static function ppkSynhInjunctionRTNMain()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'ppkSynhInjunctionRTNMain';
        $warnings[] = $method_name . '. Начало метода';
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

//            Checking::deleteAll(['is not', 'rostex_number', null]);
            # region Синхронизация проверок РТН
            $response = self::ppkSynhCheckingRostex();
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $maxDateDocument = $response['maxDateDocument'];
                $debugMethod = $response['debug'];

            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $debugMethod = $response['debug'];
                throw new Exception($method_name . '. Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }
            unset($response);
            #endregion

            $checkingSapRTN = Checking::find()
                ->select(['id', 'UPPER(TRIM(rostex_number)) as rostex_number', 'date_time_sync_rostex', 'company_department_id'])
                ->andWhere(['is not', 'rostex_number', null])
                ->asArray()
                ->indexBy(function ($row) {
                    return $row['rostex_number'] . '_' . $row['company_department_id'];
                })
//                ->indexBy('rostex_number')
                ->all();

            # region Синхронизация аудиторов и ответственных РТН
            $response = self::ppkSynchCheckingWorkerRostex($checkingSapRTN);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $auditorChecking = $response['auditorChecking'];
                $debugMethod = $response['debug'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $debugMethod = $response['debug'];
            }
            unset($response);
            #endregion

            # region Синхронизация предписаний РТН
            $response = self::ppkSynhInjunctionRTN($checkingSapRTN, $maxDateDocument, $auditorChecking);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $debugMethod = $response['debug'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $debugMethod = $response['debug'];
                throw new Exception($method_name . '. Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }
            unset($response);
            #endregion
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debugMethod);
    }

    /**
     * Метод ppkSynhCheckingRostex() - синхронизация проверки РТН
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (метод не требует выходных параметров)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     *  {
     *      "Items":                    // фильтр
     *      "filterChecking":            // фильтр для поиска
     *      "maxDateDocument":            // максимальная дата обработки документа
     *      "errors":{}                    // массив ошибок
     *      "status":1                    // статус выполнения метода
     *      "warnings":{}                // массив предупреждений
     *      "debug":{}                    // массив для отладки
     *  }
     *
     * АЛГОРИТМ:
     *  1. Получить максимальную дату и время последней синхронизации
     *  2. По максимальной дате синхронизации выгрузить данные из представления
     *  3. Перебор полученных данных
     *      3.1     Участок пуст?
     *          Да?        Получить участок по ответственному
     *          Нет?    Взять идентификатор участка
     *      3.2     Поиск проверки по rostex_number (идентификатор предписания РТН)
     *      3.3  Проверка найдена?
     *          Да?        Изменяем данные проверки
     *                  Сохраняем
     *          Нет?    Добавляем в массив на добавление проверок
     *                  Увеличиваем счётчик на добавление
     *      3.4  Счётчик на добавление  = 2000
     *          Да?        Массово сохраняем проверки
     *                  Очищаем массив на добавление
     *                  Обнуляем счётчик
     *          Нет?    Пропустить
     *  4. Конец перебора
     *  5. Пустой массив на добавление
     *          Да?        Пропустить
     *          Нет?    Массово сохраняем проверки
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.12.2019 15:45
     */
    public static function ppkSynhCheckingRostex()
    {
        // Стартовая отладочная информация
        $method_name = 'ppkSynhCheckingRostex';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();
        $result = array();
        $warnings = array();
        $status = 1;
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;
        $result_dep = array();
        $updated = array();
        $get_full_data = array();
        $start = microtime(true);
        $memory_size = array();
        $start_mem = null;
        $company_department_id = null;

        $result_duration = null;
        $max_memory_peak = null;
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** СИНХРОНИЗАЦИЯ ПРОВЕРКИ **/
            $filterChecking = null;                                                   // фильтр для выборки данных для синхронизации
            $filterCheckingSap = null;                                                   // фильтр для выборки данных для синхронизации
            $fieldArrayChecking = array('title', 'date_time_start', 'date_time_end', 'checking_type_id', 'company_department_id', 'rostex_number', 'date_time_sync_rostex');
            $table_insertChecking = 'checking';
            $table_source = 'view_checking_rostex';

            // находим последнюю дату синхронизации РТН по справочнику
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_rostex)')
                ->from($table_insertChecking)
                ->scalar();
            $warnings[] = "$method_name. максимальная дата для обработки записи" . $maxDateDocument1;
            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync_rostex>'" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_MODIFIED>'" . $maxDateDocument1 . "'";
            }

            $warnings[] = "$method_name. максимальная дата для обработки записи" . $filterChecking;

            $view_checkings = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();

            /** Отладка */
            $description = 'Выгрузили список проверок РТН';                                                                      // описание текущей отладочной точки
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

            if (!$view_checkings) {
                throw new Exception($method_name . '. Справочник для синхронизации пуст');
            }
            $warnings[] = "$method_name. Проверки были получен";
            // начинаем проверять документы на обновление добавление
            foreach ($view_checkings as $view_checking) {
                $count_all++;
                $rostex_nomer = trim($view_checking['ROSTEX_NOMER']);
                if ((empty($view_checking['OBJID']) && empty($view_checking['company_department_id'])) ||
                    (!empty($view_checking['OBJID']) && !empty($view_checking['company_department_id']))) {

                    if (!empty($view_checking['OBJID'])) {
                        $company_department_id = $view_checking['OBJID'];
                    } else {
                        $worker_comp_dep = Worker::find()
                            ->select('company_department_id')
                            ->where(['id' => $view_checking['HRSROOT_ID']])
                            ->scalar();
                        if (!empty($worker_comp_dep)) {
                            $company_department_id = $worker_comp_dep;
                        } else {
                            $company_department_id = 101;
                        }
                        unset($worker_comp_dep);
                    }
                    $checking = Checking::find()->where(['rostex_number' => $rostex_nomer, 'company_department_id' => $company_department_id])->limit(1)->one();
                    if (!$checking) {
                        $batch_insert_item['title'] = 'Предписание РТН от ' . $view_checking['ROSTEX_DATE'];
                        $batch_insert_item['date_time_start'] = $view_checking['ROSTEX_DATE'];
                        $batch_insert_item['date_time_end'] = $view_checking['ROSTEX_DATE'];
                        $batch_insert_item['checking_type_id'] = 1;

                        $batch_insert_item['company_department_id'] = $company_department_id;
                        $batch_insert_item['rostex_number'] = $rostex_nomer;
                        $batch_insert_item['date_time_sync_rostex'] = $view_checking['DATE_MODIFIED'];
                        $batch_insert_array[] = $batch_insert_item;
                        unset($batch_insert_item);
                        unset($company_department_id);
                        $count_add++;
                    } else {
                        $checking->date_time_start = $view_checking['ROSTEX_DATE'];
                        $checking->date_time_end = $view_checking['ROSTEX_DATE'];
                        $checking->company_department_id = $company_department_id;
                        $checking->rostex_number = (string)$view_checking['ROSTEX_NOMER'];
                        $checking->date_time_sync_rostex = $view_checking['DATE_MODIFIED'];
                        if ($checking->save()) {
                            $count_update++;
                        } else {
                            $errors[] = "$method_name. Не смог обновить запись checking";
                            $errors[] = $view_checking;
                            $errors[] = $checking->errors;
                        }
                        unset($checking);
                    }// делаем массовую вставку данных справочника
                    if ($count_add == 2000) {
                        $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                        if ($insert_full === 0) {
                            throw new Exception($method_name . '. Записи в таблицу Checking не добавлены');
                        } else {
                            $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу Checking";
                        }
                        unset($batch_insert_array);
                        $count_add = 0;
                    }
                }
            }
            unset($view_checkings, $view_checking);

            /** Отладка */
            $description = 'Выполнили перебор и сделалил массовыую вставку для записей кратных 2000';                   // описание текущей отладочной точки
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

            // доталкиваем остатки
            // делаем массовую вставку данных справочника
            if (!empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception($method_name . '. Записи в таблицу Checking не добавлены');
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу Checking";
                }
                unset($batch_insert_array);
            }

            /** Отладка */
            $description = 'Докинули остальные проверки';                                                                      // описание текущей отладочной точки
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

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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

        // запись в БД окончания выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return array('Items' => $filterChecking,
            'filterChecking' => $filterChecking,
            'maxDateDocument' => $maxDateDocument1,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    /**
     * Метод ppkSynchCheckingWorkerRostex() - Синхронизация связки проверок и работников участвующих в проверке
     * @param $checkingSapRTN
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * checkingSapRTN - массив проверок с номером РТН
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     *{
     *      "Items":1,
     *      "auditorChecking":{}                // массив аудиторов на проверке
     *      "errors":{}                            // массив ошибок
     *      "status":{}                            // статус выполнения метода
     *      "warnings":{}                        // массив предупреждений (ход выполнения метода)
     *      "debug":{}                            // массив отладки
     * }
     *
     *
     * АЛГОРИТМ:
     * 1. Получаем максимальную дату и время синхронизации таблицы checking_worker_type
     * 2. Выгружаем данные из представления (промежуточной таблицы где хранятся предписания РТН)
     * 3. Получаем максимальный идентификатор работника в промежутке от 100 0000 до 200 000
     * 4. Если такого не найдено установить идентификатор = 100 001
     * 5. Перебор полученных данных
     *      5.1  Фамилия выдавшего предписание РТН больше 50 символов?
     *          Да?            Обрезать фамилию выдавшего предписание до 50 символов
     *          Нет?        Пропустить
     *      5.2 Фамилия выдавшего предписание не пуста
     *          Да?            Ищем человека с такой фамилией в базе данных
     *                          Найден?        Взять идентификатор человека
     *                          Не найден?    Создать нового человека с такой фамилией
     *          Нет?        Пропустить
     *      5.3 Получаем проверку по идентификатору РТН
     *      5.4 Получаем аудитора по: идентификатору работника, проверке, типу работника = 1
     *          Нашли?        Меняем идентификатор работника
     *                      Меняем дату и время синхронизации
     *                      Сохраняем
     *          Не нашли?    Ищем по ключаем в массиве на добавление
     *                          Найдено?        Пропустить
     *                          Не найдено?        Добавить в массив на добавление
     *                                          Инкремент счётчика на добавление
     *      5.5 Получаем ответственного по: идентификатору работника, проверке, типу работника = 2
     *          Нашли?        Меняем идентификатор работника
     *                      Меняем дату и время синхронизации
     *                      Сохраняем
     *          Не нашли?    Ищем по ключаем в массиве на добавление
     *                          Найдено?        Пропустить
     *                          Не найдено?        Добавить в массив на добавление
     *                                          Инкремент счётчика на добавление
     *      5.6 Счётчик на добавление = 2000
     *          Да?            Массово сохраняем работников участвующих в проверках
     *                      Очищаем массив на добавление
     *                      Обнуляем счётчик на добавление
     *          Нет?        Пропустить
     * 6. Конец перебора
     * 7. Массив на добавление работников участвующих в проверках не пуст?
     *          Да?        Массово сохраняем работников участвующих в проверках
     *          Нет?    Пропустить
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 23.12.2019 9:43
     */
    public static function ppkSynchCheckingWorkerRostex($checkingSapRTN)
    {
        // Стартовая отладочная информация
        $method_name = 'ppkSynchCheckingWorkerRostex';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;

        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $auditorChecking = array();
        $batch_insert_array = array();
        $find_worker = null;
        $count_update = 0;
        $count_add_full = 0;
        $count_add = 0;
        $counter_workers = 0;
        $maxDateDocument1 = null;
        $auditorChecking = null;
        $number_row_affected = 0;
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            $filterChecking = null;                                                   // фильтр для выборки данных для синхронизации
            $filterCheckingSap = null;                                                   // фильтр для выборки данных для синхронизации
            $fieldArrayChecking = array('worker_id', 'worker_type_id', 'checking_id', 'instruct_rtn_id', 'date_time_sync_rostex');
            $table_insertChecking = 'checking_worker_type';
            $table_source = 'view_checking_worker_responisble_RTN';
            // находим последнюю дату синхронизации по справочнику
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_rostex)')
                ->from($table_insertChecking)
                ->scalar();

            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync_rostex>'" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_MODIFIED>'" . $maxDateDocument1 . "'";
            }

            $view_checkings = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checkings) {
                throw new Exception($method_name . '. Справочник для синхронизации пуст');
            }

            /** Отладка */
            $description = 'Выгрузили справочник связки РТН и работников участвующих в проверке\'';                                                                      // описание текущей отладочной точки
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

            $max_needed_worker_id = Worker::find()
                ->select('max(worker.id) as max_worker_id')
                ->where(['>', 'id', 100000])
                ->andWhere(['<', 'id', 200000])
                ->scalar();
            if (empty($max_needed_worker_id)) {
                $max_needed_worker_id = 100001;
            }
            foreach ($view_checkings as $checking) {
                $count_all++;
                if ((empty($checking['OBJID']) && empty($checking['company_department_id'])) ||
                    (!empty($checking['OBJID']) && !empty($checking['company_department_id']))) {
                    $fio_givers = trim($checking['FIO_GIVERS']);
                    if (strlen($fio_givers) > 50) {
                        $fio_givers = mb_substr($fio_givers, -50, 50);
                    }
                    if (!empty($checking['ROSTEX_NOMER'])) {
                        $rostex_number = trim($checking['ROSTEX_NOMER']);
                        $upper_rostex_number = mb_strtoupper($rostex_number);
                    } else {
                        $upper_rostex_number = null;
                    }
                    if (!empty($checking['OBJID'])) {
                        $company_department_id = $checking['OBJID'];
                    } else {
                        if (!empty($checking['WORKER_COMPANY_DEPARTMENT_ID'])) {
                            $company_department_id = $checking['WORKER_COMPANY_DEPARTMENT_ID'];
                        } else {
                            $company_department_id = 101;
                        }
                    }
                    $index = $upper_rostex_number . '_' . $company_department_id;
                    if (!isset($checkingSapRTN[$index]['id'])) {
                        $errors[] = $checking;
                        $errors[] = $checkingSapRTN;
                        $errors[$upper_rostex_number] = $index;
                        throw new Exception($method_name . '. Не получилось взять идентификатор проверки');
                    }
                    unset($rostex_number, $upper_rostex_number);
                    $checking_id = $checkingSapRTN[$index]['id'];
                    $rostex_date = $checkingSapRTN[$index]['date_time_sync_rostex'];

                    if (!empty($fio_givers)) {
                        $find_worker = Worker::find()
                            ->select('worker.id')
                            ->innerJoin('employee e', 'e.id = worker.employee_id')
                            ->where(['like', 'e.last_name', $fio_givers])
                            ->andWhere(['e.first_name' => '-'])
                            ->andWhere(['e.patronymic' => '-'])
                            ->limit(1)
                            ->one();
                        if (isset($find_worker) && !empty($find_worker)) {
                            $auditor_id = $find_worker->id;
                        } else {
                            $max_needed_worker_id++;
                            $add_employee = new Employee();
                            $add_employee->id = $max_needed_worker_id;
                            $add_employee->last_name = $fio_givers;
                            $add_employee->first_name = '-';
                            $add_employee->patronymic = '-';
                            $add_employee->gender = 'М';
                            $add_employee->birthdate = '1970-01-01';
                            if (!$add_employee->save()) {
                                $errors[] = $add_employee->errors;
                                throw new Exception($method_name . '. Ошибка при попытке создать инспектора Ростехнадзора');
                            }
                            $employee_id = $add_employee->id;
                            unset($add_employee);
                            $add_worker = new Worker();
                            $add_worker->id = $employee_id;
                            $add_worker->employee_id = $employee_id;
                            $add_worker->position_id = self::DEFAULT_POSITION;
                            $add_worker->company_department_id = self::COMPANY_DEPARTMENT_ID_RTN;
                            $add_worker->tabel_number = (string)$employee_id;
                            $add_worker->date_start = '1970-01-01';
                            $add_worker->date_end = '2099-12-31';
                            if (!$add_worker->save()) {
                                $errors[] = $add_worker->errors;
                                throw new Exception($method_name . '. Ошибка при создании работника Ростехналзора');
                            }
                            $add_worker->refresh();
                            $auditor_id = $add_worker->id;
                            unset($add_worker);

                            $add_worker_object = new WorkerObject();
                            $add_worker_object->id = $auditor_id;
                            $add_worker_object->worker_id = $auditor_id;
                            $add_worker_object->role_id = 10;
                            $add_worker_object->object_id = 192;//Ростехнадзор
                            if (!$add_worker_object->save()) {
                                $errors[] = $add_worker_object->errors;
                                throw new Exception($method_name . '. Ошибка при создании worker_object для работника Ростехнадзора');
                            }
                            unset($add_worker_object);
                        }
                        unset($find_worker);
                        $auditorChecking[$checking_id]['worker_id'] = $auditor_id;
                        $auditorChecking[$checking_id]['checking_id'] = $checking_id;
                        $checking_worker_type = CheckingWorkerType::find()
                            ->where(['instruct_rtn_id' => $auditor_id, 'checking_id' => $checking_id, 'worker_type_id' => 1])
                            ->limit(1)
                            ->one();
                        if (isset($checking_worker_type) && !empty($checking_worker_type)) {
                            $checking_worker_type->worker_id = $auditor_id;
                            $checking_worker_type->instruct_rtn_id = (string)$auditor_id;

                            $checking_worker_type->date_time_sync_rostex = $rostex_date;
                            if (!$checking_worker_type->save()) {
                                $errors[] = $method_name . '. Не смог обновить запись checkingWorker';
                                $errors[] = $checking;
                                $errors[] = $checking_worker_type->errors;
                            }
                        } else {
                            //                        $get_checking_worker_type = CheckingWorkerType::findOne(['checking_id'=>$checking_id,'worker_id'=>$auditor_id,'worker_type_id'=>1]);
                            $result_key = array_keys($batch_insert_array, ['checking_id' => $checking_id, 'worker_id' => $auditor_id, 'worker_type_id' => 1, 'instruct_rtn_id' => $auditor_id, 'date_time_sync_rostex' => $rostex_date]);
                            if (empty($result_key)) {
                                $batch_insert_item['worker_id'] = $auditor_id;
                                $batch_insert_item['worker_type_id'] = 1;
                                $batch_insert_item['checking_id'] = $checking_id;
                                $batch_insert_item['instruct_rtn_id'] = (string)$auditor_id;
                                $batch_insert_item['date_time_sync_rostex'] = $rostex_date;
                                $batch_insert_array[] = $batch_insert_item;
                                unset($batch_insert_item);
                                $count_add++;
                            }
                            unset($result_key);
                        }
                        unset($checking_worker_type);
                    }
                    $responsible_id = $checking['RESPONSIBLE'];
                    if (!empty($responsible_id)) {
                        $checkingWorkerResposnible = CheckingWorkerType::find()
                            ->where(['instruct_rtn_id' => $responsible_id, 'checking_id' => $checking_id, 'worker_type_id' => 2])
                            ->limit(1)
                            ->one();
                        if (!$checkingWorkerResposnible) {
                            $result_key = array_keys($batch_insert_array, ['checking_id' => $checking_id, 'worker_id' => $responsible_id, 'worker_type_id' => 2, 'instruct_rtn_id' => $responsible_id, 'date_time_sync_rostex' => $rostex_date]);
                            if (empty($result_key)) {
                                $batch_insert_item['worker_id'] = $responsible_id;
                                $batch_insert_item['worker_type_id'] = 2;
                                $batch_insert_item['checking_id'] = $checking_id;
                                $batch_insert_item['instruct_rtn_id'] = (string)$responsible_id;
                                $batch_insert_item['date_time_sync_rostex'] = $rostex_date;
                                $batch_insert_array[] = $batch_insert_item;
                                unset($batch_insert_item);
                                $count_add++;
                            }
                            unset($result_key);
                        } else {
                            $checkingWorkerResposnible->worker_id = $responsible_id;
                            $checkingWorkerResposnible->instruct_rtn_id = (string)$responsible_id;
                            $checkingWorkerResposnible->date_time_sync_rostex = $checking['DATE_MODIFIED'];
                            if (!$checkingWorkerResposnible->save()) {
                                $errors[] = $method_name . '. Не смог обновить запись checkingWorker';
                                $errors[] = $checking;
                                $errors[] = $checkingWorkerResposnible->errors;
                            }
                        }
                        unset($checkingWorkerResposnible);
                    }
                    // делаем массовую вставку данных справочника
                    if ($count_add == 2000) {
                        $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                        if ($insert_full === 0) {
                            throw new Exception($method_name . '. Записи в таблицу ' . $table_insertChecking . ' не добавлены');
                        } else {
                            $warnings[] = $method_name . '. добавил - $insert_full - записей в таблицу $table_insertChecking';
                        }
                        $batch_insert_array = [];
                        $count_add = 0;
                    }
                }
            }
            unset($view_checkings, $checking, $max_needed_worker_id);
            /** Отладка */
            $description = 'Выполнили перебор и добавили основную часть данных';                                                                      // описание текущей отладочной точки
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

            if (isset($batch_insert_array) && !empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception($method_name . '. Записи в таблицу ' . $table_insertChecking . ' не добавлены');
                } else {
                    $warnings[] = $method_name . '. добавил - $insert_full - записей в таблицу $table_insertChecking';
                }
                unset($batch_insert_array);
            }


            /** Отладка */
            $description = 'Добавили остаток данных типов работников участвующих в проверке РТН';                                                                      // описание текущей отладочной точки
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
            HandbookCachedController::clearWorkerCache();
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
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

        // запись в БД окончания выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array('Items' => 1,
            'auditorChecking' => $auditorChecking,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод ppkSynhInjunctionRTN() - Синхроиназция предписаний РТН
     * @param $checkingSapSpr
     * @param $maxDateDocument
     * @param $auditorChecking
     * @return array
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * checkingSapSpr - массив проверок с номером РТН
     * maxDateDocument - максимальная дата обработки документа
     * auditorChecking - массив аудиторов на проверке
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     *  {
     *      "Items":1,
     *      "auditorChecking":{}                // массив аудиторов на проверке
     *      "errors":{}                            // массив ошибок
     *      "status":{}                            // статус выполнения метода
     *      "warnings":{}                        // массив предупреждений (ход выполнения метода)
     *      "debug":{}                            // массив отладки
     *  }
     *
     * АЛГОРИТМ:
     *
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 23.12.2019 9:52
     */
    public static function ppkSynhInjunctionRTN($checkingSapSpr, $maxDateDocument, $auditorChecking)
    {
        // Стартовая отладочная информация
        $method_name = 'ppkSynhInjunctionRTN';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;

        $injunction_status = array();                   // список статусов предписаний
        $place_array = array();                         // список мест, в которых производилась проверка
        $violator_array = array();                      // список нарушителей для вставки в VIOLATOR
        $coorect_meassure_array = array();              // список корректирующих мероприятий
        $errors = array();                              // массив ошибок
        $warnings = array();                            // массив предупреждений
        $status = 1;                                    // статус при выполнении метода
        $count_add = 0;                                 // количество добавленных записей
        $count_add_full = 0;                            // полное количество добавленных записей
        $dateNow = Assistant::GetDateFormatYMD();       // текущая дата и время

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            $filterChecking = null;                                                     // фильтр для выборки данных для синхронизации основной таблицы в которую будем вставлять данные
            $filterCheckingSap = null;                                                  // фильтр для выборки данных для синхронизации по таблице из ППК ПАБ
            $fieldArrayChecking = array('place_id', 'worker_id', 'kind_document_id', 'rtn_statistic_status_id', 'checking_id', 'description', 'status_id', 'observation_number', 'company_department_id', 'instruct_id_ip', 'date_time_sync');
            $table_insertChecking = 'injunction';                                       // таблица в которую вставляем
            $table_source = 'view_injunction_sap_rtn';                             // таблица из которой берем


            // находим последнюю дату синхронизации по сиску предписаний
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_rostex)')
                ->from($table_insertChecking)
                ->scalar();
            $warnings[] = $method_name . '. максимальная дата для обработки записи' . $maxDateDocument1;
            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync_rostex>'" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_MODIFIED>'" . $maxDateDocument1 . "'";
            }
            // получаем справочник документов ППК ПАБ нормативных документов для синхронизации
            $view_injunctions = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_injunctions) {
                throw new Exception($method_name . '. СПИОК ПРЕДПИСАНИЙ для синхронизации пуст');
            }
            $injunction_controller = new InjunctionController(1, false);
            $kind_violations = KindViolation::find()
                ->select('id,title,ref_error_direction_id,date_time_sync')
                ->indexBy('ref_error_direction_id')
                ->asArray()
                ->all();

            $violation_types = ViolationType::find()
                ->select('id,ref_error_direction_id')
                ->indexBy('ref_error_direction_id')
                ->asArray()
                ->all();
            /** Отладка */
            $description = 'Выгрузили предписания РТН';                                                                      // описание текущей отладочной точки
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

            // начинаем проверять документы на обновление добавление
            // перепираем пердписания
            foreach ($view_injunctions as $view_injunction) {
                $count_all++;
                if ((empty($view_injunction['OBJID']) && empty($view_injunction['company_department_id'])) ||
                    (!empty($view_injunction['OBJID']) && !empty($view_injunction['company_department_id']))) {
                    if (!empty($view_injunction['ROSTEX_NOMER'])) {
                        $rostex_number = trim($view_injunction['ROSTEX_NOMER']);
                        $upper_rostex_number = mb_strtoupper($rostex_number);
                        unset($rostex_number);
                    } else {
                        $upper_rostex_number = null;
                    }
                    if (!empty($view_injunction['OBJID'])) {
                        $company_department_id = $view_injunction['OBJID'];
                        $place_title = $view_injunction['PLACE_TITLE'];
                    } else {
                        $company_department_id = $view_injunction['WORKER_COMPANY_DEPARTMENT_ID'];
                        $place_title = $view_injunction['WORKER_PLACE_TITLE'];
                    }
                    $index = $upper_rostex_number . '_' . $company_department_id;
                    unset($upper_rostex_number);
                    $checking_id = $checkingSapSpr[$index]['id'];// ключ проверки
                    if (isset($view_injunction['RESPONSIBILITY_ID']) && !empty($view_injunction['RESPONSIBILITY_ID'])) {
                        $worker_id = $view_injunction['RESPONSIBILITY_ID'];
                    } else {
                        $worker_id = 1;
                    }
                    /******************** ПОЛУЧАЕМ ИДЕНТИФИКАТОР МЕСТА ********************/
                    $response = $injunction_controller::GetPlaceByTitle($place_title);
                    if ($response['status'] == 1) {
                        $place_id = $response['Items'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception($method_name . '. Ошибка при получении идентификатора места');
                    }
                    unset($response, $json);

                    $del_checking_place = CheckingPlace::deleteAll(['checking_id' => $checking_id]);
                    unset($del_checking_place);
                    $place_array[$checking_id][$place_id]['place_id'] = $place_id;// ключ места, в котором производилась проверка
                    $place_array[$checking_id][$place_id]['checking_id'] = $checking_id;// ключ проверки
                    /**
                     * Обработка предписания
                     */
                    $injunction = Injunction::find()->where(['instruct_rtn_id' => $view_injunction['INSTRUCTION_ROSTEX_ID']])->limit(1)->one();// БЛОК ОБРАБОТКИ СОЗДАНИЯ ОБНОВЛЕНИЯ ПРЕДПИСАНИЯ
                    $change = false;
                    $observation_number = null;
                    if (empty($injunction)) {
                        $injunction = Injunction::findOne([
                            'place_id' => $place_id,
                            'worker_id' => $worker_id,
                            'kind_document_id' => 3,
                            'checking_id' => $checking_id,
                            'observation_number' => 0
                        ]);
                        if (empty($injunction)) {
                            $injunction = Injunction::findOne([
                                'place_id' => $place_id,
                                'kind_document_id' => 3,
                                'checking_id' => $checking_id,
                                'observation_number' => 0
                            ]);
                            if (empty($injunction)) {
                                $change = false;
                            } else {
                                $change = true;
                                $injunction_data['worker_id'] = $worker_id;
                            }
                        } else {
                            $change = true;
                            $injunction_data['worker_id'] = $worker_id;
                        }
                    } else {
                        $another_injunction = Injunction::find()
                            ->where([
                                'place_id' => $place_id,
                                'worker_id' => $worker_id,
                                'kind_document_id' => 3,
                                'checking_id' => $checking_id,
                            ])
                            ->orderBy('observation_number desc')
                            ->limit(1)
                            ->one();
                        $another_instruct_rtn_id = isset($another_injunction->instruct_rtn_id) ? $another_injunction->instruct_rtn_id : null;
                        if ($injunction->instruct_rtn_id == $another_instruct_rtn_id) {
                            $injunction_data['worker_id'] = $another_injunction->worker_id;
                            $observation_number = null;
                            $change = true;
                        } else {
                            $change = true;
                            $observation_number = isset($another_injunction->observation_number) ? $another_injunction->observation_number : null;
                            $injunction_data['worker_id'] = $worker_id;
                        }
                        unset($another_injunction);
                    }
                    if ($change) {
//                    $injunction_data['worker_id'] = $worker_id;
                        $injunction_data['date_plan'] = $view_injunction['DATE_PLAN'];
                        $injunction_data['date_fact'] = $view_injunction['DATE_FACT'];
                        $injunction_data['INSTRUCTION_ROSTEX_ID'] = $view_injunction['INSTRUCTION_ROSTEX_ID'];
                        $injunction_data['DATE_MODIFIED'] = $view_injunction['DATE_MODIFIED'];
                    } else {
                        $injunction_data['place_id'] = $place_id;
                        $injunction_data['worker_id'] = $worker_id;
                        $injunction_data['checking_id'] = $checking_id;
                        $injunction_data['date_plan'] = $view_injunction['DATE_PLAN'];
                        $injunction_data['date_fact'] = $view_injunction['DATE_FACT'];
                        $injunction_data['company_department_id'] = $company_department_id;
                        $injunction_data['INSTRUCTION_ROSTEX_ID'] = $view_injunction['INSTRUCTION_ROSTEX_ID'];
                        $injunction_data['DATE_MODIFIED'] = $view_injunction['DATE_MODIFIED'];
                    }
                    $response = $injunction_controller::ChangeOrSaveInjunction(
                        'RTNInjunction',
                        $change,
                        $injunction_data,
                        $injunction,
                        $observation_number);
                    if (!empty($response['errors'])) {
                        $errors[] = "$method_name. Не смог добавить или обновить запись injunctions";
                        $errors[] = "RTNInjunction";
                        $errors[] = "view_injunction:";
                        $errors[] = $view_injunction;
                        $errors[] = "change:";
                        $errors[] = $change;
                        $errors[] = "injunction_data:";
                        $errors[] = $injunction_data;
                        $errors[] = "injunction:";
                        $errors[] = $injunction;
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception($method_name . '. Ошибка сохранения предписания Injunction');
                    }
                    $injunction_id = $response['injunction_id'];
                    $injunction_status_id = $response['injunction_status_id'];
                    unset($response, $injunction_data, $injunction, $observation_number, $change);
                    /**
                     * ДОБАВЛЯЕМ СТАТУСЫ ПРЕДПИСАНИЙ
                     */
                    $injunction_status[$injunction_id][$worker_id]['injunction_id'] = $injunction_id;
                    $injunction_status[$injunction_id][$worker_id]['worker_id'] = $worker_id;
                    $injunction_status[$injunction_id][$worker_id]['status_id'] = $injunction_status_id;
                    $injunction_status[$injunction_id][$worker_id]['date_time'] = $view_injunction['ROSTEX_DATE'];
                    /******************** ОБРАБОТКА НАРУШЕНИЯ ********************/
                    // описание нарушения может быть больше 1000 символов потому мы его обрезаем
                    $str_len_violation = strlen($view_injunction['VIOLATION']);                                            // вычисляем длину нарушения

                    if ($str_len_violation > 1000) {
                        $violation_saving_title = mb_substr($view_injunction['VIOLATION'], 0, 1000);                       // если длина больше 1000 символов, то мы ее обрезаем
                    } else {
                        $violation_saving_title = $view_injunction['VIOLATION'];                                           // иначе оставляем так как есть
                    }

                    unset($str_len_violation);

                    $violation_saving_title = trim($violation_saving_title);                                                //убираем пробелы с начала и конца строки для того, что бы обеспечить гарантированный поиск совпадения

                    $violation = Violation::find()->where(['title' => $violation_saving_title])->limit(1)->one();
                    if (!$violation) {

                        $violation = new Violation();

                        if (!empty($violation_saving_title)) {
                            $violation->title = $violation_saving_title;
                            unset($violation_saving_title);
                            /**
                             *  Не пустой ли идентификатор направления нарушения
                             *    не пустой?    ищем вид нарушения по REF_ERROR_DIRECTION_ID
                             *                    нашли?        ищем тип нарушения
                             *                                    нашли?        берём идентификатор типа нарушения
                             *                                    не нашли?    создаём новый тип нарушения
                             *                    не нашли?    ищем тип нарушения
                             *                                    нашли?        берём идентификатор типа нарушения
                             *                                    не нашли?    значение по умолчанию
                             *    пустой?    значение по умолчанию
                             */
                            if ($view_injunction['REF_ERROR_DIRECTION_ID']) {
                                if (isset($kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['id']) && !empty($kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'])) {
                                    if (isset($violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id']) && !empty($violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'])) {
                                        $violation_type_for_violation = $violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'];
                                    } else {
                                        $json = json_encode(['kind_violation_id' => $kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'],
                                            'kind_violation_title' => $kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['title'],
                                            'ref_error_direction_id' => $view_injunction['REF_ERROR_DIRECTION_ID'],
                                            'date_time_sync' => $kind_violations[$view_injunction['REF_ERROR_DIRECTION_ID']]['date_time_sync']]);
                                        $response = $injunction_controller::ViolationType($json);
                                        if ($response['status'] == 1) {
                                            $violation_type_for_violation = $response['Items'];
                                        } else {
                                            $warnings[] = $response['warnings'];
                                            $errors[] = $response['errors'];
                                            throw new Exception($method_name . '. Ошибка при сохранении типа нарушения');
                                        }
                                        unset($response, $json);
                                    }
                                } else {
                                    if (isset($violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id']) && !empty($violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'])) {
                                        $violation_type_for_violation = $violation_types[$view_injunction['REF_ERROR_DIRECTION_ID']]['id'];
                                    } else {
                                        $violation_type_for_violation = 128;
                                    }
                                }
                                $violation->violation_type_id = $violation_type_for_violation;
                            } else {
                                $violation->violation_type_id = 128;
                            }
                            if ($violation->save()) {
                                $violation->refresh();
                                $violation_id = $violation->id;
                            } else {
                                $errors[] = $view_injunction['REF_ERROR_DIRECTION_ID'];
                                $errors[] = $violation_type_for_violation;
                                $errors[] = $violation->errors;
                                throw new Exception($method_name . '. Ошибка сохранения Описания нарушения Violation');
                            }
                        } else {
                            $violation_id = 1;// нарушение по умолчанию "Не заполнено" c типом "Прочее"
                        }
                    } else {
                        $violation_id = $violation->id;
                    }
                    unset($violation);
                    $document_id = 20079;// документ по умолчанию (Прочее)
                    $paragraph_pb_id = null;// параграф по умолчанию пуст
                    //ищем нарушение если они есть то обновляем, если нет то создаем
                    $inj_violation = InjunctionViolation::find()->where(['instruct_rtn_id' => $view_injunction['INSTRUCTION_ROSTEX_ID']])->limit(1)->one();
                    if (!$inj_violation) {                                                                                    // создаем с 0 если такого еще не было
                        $inj_violation = new InjunctionViolation();
                    }
                    $inj_violation->probability = 5;                                                                    // вероятность плохого исхода
                    $inj_violation->gravity = 5;                                                                        // тяжесть плохого исхода
                    $inj_violation->correct_period = $view_injunction['DATE_PLAN'];
                    $inj_violation->injunction_id = $injunction_id;                                                     // ключ предписания
                    $inj_violation->place_id = $place_id;                                                               // ключ места в котором выписано предписание
                    $inj_violation->violation_id = $violation_id;                                                       // ключ описания нарушения с привязкой к направлению нарушений
                    $inj_violation->paragraph_pb_id = $paragraph_pb_id;                                                 // ключ параграфа нормативного документа
                    $inj_violation->document_id = $document_id;                                                         // ключ нормативного документа, требования которого были нарушены
                    $inj_violation->instruct_rtn_id = $view_injunction['INSTRUCTION_ROSTEX_ID'];// ключ проверки САП
                    $inj_violation->date_time_sync_rostex = $view_injunction['DATE_MODIFIED'];// дата создания проверки САП
                    if (!$inj_violation->save()) {
                        $errors[] = $method_name . '. Не смог добавить или обновить запись InjunctionViolation';
                        $errors[] = $view_injunction;
                        $errors[] = $inj_violation->errors;
                        throw new Exception($method_name . '. Ошибка сохранения пункта нарушения документа InjunctionViolation');
                    }
                    $inj_violation->refresh();
                    $inj_violation_id = $inj_violation->id;
                    unset($inj_violation);
                    // ЗАТЕМ ПИШЕМ СТАТУСЫ НАРУШЕНИЙ  если у нарушения нет статуса, то мы его создаем, если есть то проверяем на изменение и если изменеилось то пишем новый статус
                    $inj_viol_batch_array[$inj_violation_id]['injunction_violation_id'] = $inj_violation_id;
                    $inj_viol_batch_array[$inj_violation_id]['status_id'] = $injunction_status_id;
                    $inj_viol_batch_array[$inj_violation_id]['date_time'] = $dateNow;
                    // затем делаем корректирующие мероприятия на массовую вставку
                    /******************** ПОЛУЧАЕМ ИДЕНТИФИКАТОР ОПЕРАЦИИ ********************/
                    $json = json_encode(['operation_title' => $view_injunction['DESC_ACTION']]);
                    $response = $injunction_controller::GetOperationByTitle($json);
                    if ($response['status'] == 1) {
                        $operation_id = $response['Items'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception($method_name . '. Ошибка при получении/создании операции');
                    }
                    unset($response, $json);
                    /******************** ЗАПИСЫВАЕМ НАРУШИТЕЛЕЙ ********************/
                    //затем нарушители - утрамбовываем их сперва в массив, для исключения дубляжа
                    $gilty_worker_id = $view_injunction['RESPONSIBILITY_ID'];
                    if (!empty($gilty_worker_id)) {
                        $del_violators = Violator::deleteAll(['injunction_violation_id' => $inj_violation_id]);
                        unset($del_violators);
                        $violator_array[$inj_violation_id][$gilty_worker_id]['worker_id'] = $gilty_worker_id;
                        $violator_array[$inj_violation_id][$gilty_worker_id]['injunction_violation_id'] = $inj_violation_id;
                    }
                    /******************** ДОБАВЛЯЕМ КОРРЕКТИРУЮЩИЕ МЕРОПРТИЯТИЯ ********************/
                    $del_corr = CorrectMeasures::deleteAll(['injunction_violation_id' => $inj_violation_id]);
                    unset($del_corr);
                    if (!empty($view_injunction['RESPONSIBILITY_ID'])) {
                        $correct_measures_worker_id = $view_injunction['RESPONSIBILITY_ID'];
                    } else {
                        $correct_measures_worker_id = 1;
                    }
                    $coorect_meassure_array[$inj_violation_id][$operation_id]['injunction_violation_id'] = $inj_violation_id;
                    $coorect_meassure_array[$inj_violation_id][$operation_id]['worker_id'] = $correct_measures_worker_id;
                    $coorect_meassure_array[$inj_violation_id][$operation_id]['operation_id'] = $operation_id;
                    if (empty($view_injunction['DATE_PLAN'])) {
                        $plan_date = date('Y-m-d', strtotime($view_injunction['ROSTEX_DATE'] . '+1 day'));
                    } else {
                        $plan_date = date('Y-m-d', strtotime($view_injunction['DATE_PLAN']));
                    }
                    $coorect_meassure_array[$inj_violation_id][$operation_id]['date_time'] = $plan_date;
                    $status_correct = $injunction_controller::NewStatusForInjunction($view_injunction['DATE_PLAN'], $view_injunction['DATE_FACT']);
                    $coorect_meassure_array[$inj_violation_id][$operation_id]['status_id'] = $status_correct;
                    $coorect_meassure_array[$inj_violation_id][$operation_id]['correct_measures_value'] = 1;
                    /******************** ЕСЛИ ЕСТЬ ПРИОСТАНОВКИ РАБОТ ТО ЗАПИСЫВАЕМ ИХ ********************/
                    if (!empty($view_injunction['DATE_STOP_WORK'])) {
                        $del_stop_pb = StopPb::deleteAll(['injunction_violation_id' => $inj_violation_id]);
                        unset($del_stop_pb);
                        $stop_pb_array[$inj_violation_id]['injunction_violation_id'] = $inj_violation_id;
                        $stop_pb_array[$inj_violation_id]['kind_stop_pb_id'] = 1;
                        $stop_pb_array[$inj_violation_id]['kind_duration_id'] = 1;
                        $stop_pb_array[$inj_violation_id]['place_id'] = $place_id;
                        $stop_pb_array[$inj_violation_id]['date_time_start'] = $view_injunction['DATE_STOP_WORK'];
                    }
                }
            }
            unset($injunction_controller, $kind_violations, $violation_types, $view_injunctions, $view_injunction);
            /** Отладка */
            $description = 'Добавили основную часть данных по предписаниям РТН';                                                                      // описание текущей отладочной точки
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


            /** СОХРАНЕНИЕ КОРРЕКТИРУЮЩИХ МЕРОПРИЯТИЙ **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $coorect_meassure_count = 0;
            if (isset($coorect_meassure_array) && !empty($coorect_meassure_array)) {
                foreach ($coorect_meassure_array as $inj_viol_item) {
                    foreach ($inj_viol_item as $operation_item) {
                        $coorect_meassure_batch_insert_item['injunction_violation_id'] = $operation_item['injunction_violation_id'];
                        $coorect_meassure_batch_insert_item['worker_id'] = $operation_item['worker_id'];
                        $coorect_meassure_batch_insert_item['operation_id'] = $operation_item['operation_id'];
                        $coorect_meassure_batch_insert_item['date_time'] = $operation_item['date_time'];
                        $coorect_meassure_batch_insert_item['status_id'] = $operation_item['status_id'];
                        $coorect_meassure_batch_insert_item['correct_measures_value'] = $operation_item['correct_measures_value'];
                        $coorect_meassure_batch_insert_array[] = $coorect_meassure_batch_insert_item;
                        $coorect_meassure_count++;
                        $coorect_meassure_batch_insert_item = [];
                        if ($coorect_meassure_count == 2000) {
                            $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('correct_measures', ['injunction_violation_id', 'worker_id', 'operation_id', 'date_time', 'status_id', 'correct_measures_value'], $coorect_meassure_batch_insert_array);
                            $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `worker_id` = VALUES (`worker_id`), `operation_id` = VALUES (`operation_id`), `date_time` = VALUES (`date_time`), `status_id` = VALUES (`status_id`), `correct_measures_value` = VALUES (`correct_measures_value`)')->execute();

                            if ($insert_full === 0) {
                                $warnings[] = $method_name . '. Записи в таблицу correct_measures не добавлены';
                            } else {
                                $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу correct_measures";
                            }
                            $coorect_meassure_count = 0;
                            unset($coorect_meassure_batch_insert_array);
                            $coorect_meassure_batch_insert_array = array();
                        }
                    }
                }
                unset($coorect_meassure_array, $inj_viol_item, $inj_viol_item);
            }

            if (isset($coorect_meassure_batch_insert_array) && !empty($coorect_meassure_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('correct_measures', ['injunction_violation_id', 'worker_id', 'operation_id', 'date_time', 'status_id', 'correct_measures_value'], $coorect_meassure_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `worker_id` = VALUES (`worker_id`), `operation_id` = VALUES (`operation_id`), `date_time` = VALUES (`date_time`), `status_id` = VALUES (`status_id`), `correct_measures_value` = VALUES (`correct_measures_value`)')->execute();

                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу correct_measures не добавлены';
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу correct_measures";
                }
                unset($coorect_meassure_batch_insert_array);
            }

            /** Отладка */
            $description = 'Сохранили корректирующие мероприятия';                                                                      // описание текущей отладочной точки
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

            /******************** СОХРАНЕНИЕ ПРИОСТАНОВОК РАБОТ ********************/
            $stop_pb_count = 0;
            if (isset($stop_pb_array) && !empty($stop_pb_array)) {
                foreach ($stop_pb_array as $stop_pb_item) {
                    $stop_pb_batch_insert['injunction_violation_id'] = $stop_pb_item['injunction_violation_id'];
                    $stop_pb_batch_insert['kind_stop_pb_id'] = $stop_pb_item['kind_stop_pb_id'];
                    $stop_pb_batch_insert['kind_duration_id'] = $stop_pb_item['kind_duration_id'];
                    $stop_pb_batch_insert['place_id'] = $stop_pb_item['place_id'];
                    $stop_pb_batch_insert['date_time_start'] = $stop_pb_item['date_time_start'];
                    $stop_pb_batch_insert['date_time_end'] = null;
                    $stop_pb_batch_insert_array[] = $stop_pb_batch_insert;
                    $stop_pb_count++;
                    if ($stop_pb_count == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('stop_pb', ['injunction_violation_id', 'kind_stop_pb_id', 'kind_duration_id', 'place_id', 'date_time_start', 'date_time_end'], $stop_pb_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `kind_stop_pb_id` = VALUES (`kind_stop_pb_id`), `kind_duration_id` = VALUES (`kind_duration_id`), `place_id` = VALUES (`place_id`), `date_time_start` = VALUES (`date_time_start`), `date_time_end` = VALUES (`date_time_end`)')->execute();
                        if ($insert_full === 0) {
                            $warnings[] = $method_name . '. Записи в таблицу stop_pb не добавлены';
                        } else {
                            $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу stop_pb";
                        }
                        $stop_pb_count = 0;
                        unset($stop_pb_batch_insert_array);
                        $stop_pb_batch_insert_array = array();
                    }
                }
                unset($stop_pb_array, $stop_pb_item);
            }

            if (isset($stop_pb_batch_insert_array) && !empty($stop_pb_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('stop_pb', ['injunction_violation_id', 'kind_stop_pb_id', 'kind_duration_id', 'place_id', 'date_time_start', 'date_time_end'], $stop_pb_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `kind_stop_pb_id` = VALUES (`kind_stop_pb_id`), `kind_duration_id` = VALUES (`kind_duration_id`), `place_id` = VALUES (`place_id`), `date_time_start` = VALUES (`date_time_start`), `date_time_end` = VALUES (`date_time_end`)')->execute();

                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу stop_pb не добавлены';
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу stop_pb";
                }
                unset($coorect_meassure_batch_insert_array);
            }

            /** Отладка */
            $description = 'Добавили приостановки работ';                                                                      // описание текущей отладочной точки
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

            /** СОХРАНЕНИЕ НАРУШИТЕЛЕЙ в VIOLATOR **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $gilty_violator_count = 0;
            if (isset($violator_array) && !empty($violator_array)) {
                foreach ($violator_array as $inj_viol_item) {
                    foreach ($inj_viol_item as $worker_item) {
                        $gilty_violator_batch_insert_item['injunction_violation_id'] = $worker_item['injunction_violation_id'];
                        $gilty_violator_batch_insert_item['worker_id'] = $worker_item['worker_id'];
                        $gilty_violator_batch_insert_array[] = $gilty_violator_batch_insert_item;
                        $gilty_violator_count++;

                        if ($gilty_violator_count == 2000) {
                            $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('violator', ['injunction_violation_id', 'worker_id'], $gilty_violator_batch_insert_array);
                            $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `worker_id` = VALUES (`worker_id`)')->execute();

                            if ($insert_full === 0) {
                                $warnings[] = $method_name . '. Записи в таблицу violator не добавлены';
                            } else {
                                $warnings[] = $method_name . '. добавил - $insert_full - записей в таблицу violator';
                            }
                            $gilty_violator_count = 0;
                            unset($gilty_violator_batch_insert_array);
                            $gilty_violator_batch_insert_array = array();
                        }
                    }
                }
                unset($violator_array, $inj_viol_item, $inj_viol_item, $worker_item);
            }


            if (isset($gilty_violator_batch_insert_array) && !empty($gilty_violator_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('violator', ['injunction_violation_id', 'worker_id'], $gilty_violator_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `worker_id` = VALUES (`worker_id`)')->execute();

                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу violator не добавлены';
                } else {
                    $warnings[] = $method_name . '. добавил - $insert_full - записей в таблицу violator';
                }
                unset($gilty_violator_batch_insert_array);
            }

            $gilty_violator_batch_insert_array = [];

            /** Отладка */
            $description = 'Добавили данные по нарушителям';                                                                      // описание текущей отладочной точки
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

            /** СОХРАНЕНИЕ МЕСТ ПРОВЕРОК МАССОВО **/
            $place_count = 0;
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            if (isset($place_array) && !empty($place_array)) {
                foreach ($place_array as $cheking_item) {
                    foreach ($cheking_item as $place_item) {
                        $place_batch_insert_item['checking_id'] = $place_item['checking_id'];
                        $place_batch_insert_item['place_id'] = $place_item['place_id'];
                        $place_batch_insert_array[] = $place_batch_insert_item;

                        $place_count++;
                        if ($place_count == 2000) {
                            $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_place', ['checking_id', 'place_id'], $place_batch_insert_array);
                            $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `place_id` = VALUES (`place_id`)')->execute();
                            if ($insert_full === 0) {
                                $warnings[] = $method_name . '. Записи в таблицу checking_place не добавлены';
                            } else {
                                $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу checking_place";
                            }
                            $place_count = 0;
                            unset($place_batch_insert_array);
                            $place_batch_insert_array = array();
                        }
                    }
                }
            }


//            $warnings[]=$place_batch_insert_array;
            if (isset($place_batch_insert_array) && !empty($place_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_place', ['checking_id', 'place_id'], $place_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `place_id` = VALUES (`place_id`)')->execute();

                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу checking_place не добавлены';
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу checking_place";
                }
                unset($place_batch_insert_array);
            }
            $place_batch_insert_array = [];
            unset($cheking_item);
            unset($place_item);
            unset($place_batch_insert_item);

            /** Отладка */
            $description = 'Добавили места проверок';                                                                      // описание текущей отладочной точки
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

            /** СОХРАНЕНИЕ СТАУСОВ ПРЕДПИСАНИЙ МАССОВО **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $inj_status = 0;
            foreach ($injunction_status as $injunction_item) {
                foreach ($injunction_item as $worker_item) {
                    $inj_status_batch_insert_item['injunction_id'] = $worker_item['injunction_id'];
                    $inj_status_batch_insert_item['worker_id'] = $worker_item['worker_id'];
                    $inj_status_batch_insert_item['status_id'] = $worker_item['status_id'];
                    $inj_status_batch_insert_item['date_time'] = $worker_item['date_time'];
                    $inj_status_batch_insert_array[] = $inj_status_batch_insert_item;
                    $inj_status++;
                    if ($inj_status == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_status_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_id` = VALUES (`injunction_id`), `worker_id` = VALUES (`worker_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                        if ($insert_full === 0) {
                            $warnings[] = $method_name . '. Записи в таблицу injunction_status не добавлены';
                        } else {
                            $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу injunction_status";
                        }
                        unset($inj_status_batch_insert_array);
                        $inj_status_batch_insert_array = array();
                        $inj_status = 0;
                    }
                }
            }
            unset($inj_status_batch_insert_item, $injunction_status, $injunction_item, $injunction_item, $worker_item);

//            $warnings[] = $inj_status_batch_insert_array;
            if (isset($inj_status_batch_insert_array) && !empty($inj_status_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_status_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_id` = VALUES (`injunction_id`), `worker_id` = VALUES (`worker_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();

                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу injunction_status не добавлены';
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу injunction_status";
                }
                unset($inj_status_batch_insert_array);
            }

            /** Отладка */
            $description = 'Добавили статусы предписаний';                                                                      // описание текущей отладочной точки
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

            /******************** СОХРАНЕНИЕ СТАТУСОВ ПРЕДПИСАНИЙ НАРУШЕНИЙ ********************/
            $inj_viol_status = 0;
            foreach ($inj_viol_batch_array as $injunction_violation_status) {
                $inj_viol_status_batch_insert_item['injunction_violation_id'] = $injunction_violation_status['injunction_violation_id'];
                $inj_viol_status_batch_insert_item['status_id'] = $injunction_violation_status['status_id'];
                $inj_viol_status_batch_insert_item['date_time'] = $injunction_violation_status['date_time'];
                $inj_viol_status_batch_insert_array[] = $inj_viol_status_batch_insert_item;
                $inj_viol_status++;
                if ($inj_viol_status == 2000) {
                    $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_violation_status', ['injunction_violation_id', 'status_id', 'date_time'], $inj_viol_status_batch_insert_array);
                    $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                    if ($insert_full === 0) {
                        $warnings[] = $method_name . '. Записи в таблицу injunction_violation_status не добавлены';
                    } else {
                        $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу injunction_violation_status";
                    }
                    unset($inj_viol_status_batch_insert_array);
                    $inj_viol_status_batch_insert_array = array();
                    $inj_viol_status = 0;
                }
            }
            unset($inj_viol_batch_array, $inj_viol_status_batch_insert_item, $injunction_violation_status);
            if (isset($inj_viol_status_batch_insert_array) && !empty($inj_viol_status_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_violation_status', ['injunction_violation_id', 'status_id', 'date_time'], $inj_viol_status_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу injunction_violation_status не добавлены';
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу injunction_violation_status";
                }
                unset($inj_viol_status_batch_insert_array);
                $inj_viol_status_batch_insert_array = array();
            }

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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

        // запись в БД окончания выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);
        $result = array('Items' => 1,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result;
    }

    /**
     * Метод CopyWorkerPass() - Копирование из oracle  в нашу таблицу (sap_worker_card)
     * @return array
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.12.2019 11:42
     */
    public static function CopyWorkerPass()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("CopyWorkerPass");

        try {
            $log->addLog("Начало выполнение метода");

            $conn_oracle = oci_connect('Amicum_PS', 'y62#yZfl$U$e', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST = ' . SKUD_HOST_NAME . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =' . SKUD_SERVICE_NAME . ')))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $log->addData(oci_error(), 'oci_error', __LINE__);
                $log->addLog('Соединение с Oracle не выполнено');
            } else {
                $log->addLog('Соединение с Oracle установлено');
            }

            $query = oci_parse($conn_oracle, "SELECT 
            ID,
            FPEOPLEGID,
            FBDATE,
            FEDATE,
            FNCARD,
            FTYPECARD,
            FTYPE,
            TO_CHAR(FBDTIME, 'YYYY-MM-DD HH24:MI:SS') AS FBDTIME,
            TO_CHAR(FEDTIME, 'YYYY-MM-DD HH24:MI:SS') AS FEDTIME,
            FTABN_SAP,
            FEDITOR,
            FORG,
            FNHELP,
            FDEPGID,
            FTYPESYSTEM,
            TO_CHAR(LAST_CHANGE, 'YYYY-MM-DD HH24:MI:SS') AS LAST_CHANGE
            FROM PMS.CARDS WHERE FTYPECARD = 1 and current_date >= FBDTIME and current_date <= FEDTIME");
            oci_execute($query);
            SapWorkerCard::deleteAll();
            $count = 0;
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $count_record++;
                $worker_cards_batch_array[] = $row;

                $count++;
                if ($count == 2000) {
                    $worker_card_inserted_array = Yii::$app->db->createCommand()->batchInsert('sap_worker_card',
                        ['ID',
                            'FPEOPLEGID',
                            'FBDATE',
                            'FEDATE',
                            'FNCARD',
                            'FTYPECARD',
                            'FTYPE',
                            'FBDTIME',
                            'FEDTIME',
                            'FTABN_SAP',
                            'FEDITOR',
                            'FORG',
                            'FNHELP',
                            'FDEPGID',
                            'FTYPESYSTEM',
                            'LAST_CHANGE'
                        ], $worker_cards_batch_array)->execute();
                    if ($worker_card_inserted_array != 0) {
                        $log->addLog('Добавлено: ' . $worker_card_inserted_array . ' записей');
                    } else {
                        throw new Exception('Ошибка при добавлении карт работников');
                    }
                    $worker_cards_batch_array = array();
                    $count = 0;
                }
            }
            if (isset($worker_cards_batch_array) && !empty($worker_cards_batch_array)) {
                $worker_card_inserted_array = Yii::$app->db->createCommand()->batchInsert('sap_worker_card',
                    ['ID',
                        'FPEOPLEGID',
                        'FBDATE',
                        'FEDATE',
                        'FNCARD',
                        'FTYPECARD',
                        'FTYPE',
                        'FBDTIME',
                        'FEDTIME',
                        'FTABN_SAP',
                        'FEDITOR',
                        'FORG',
                        'FNHELP',
                        'FDEPGID',
                        'FTYPESYSTEM',
                        'LAST_CHANGE'
                    ], $worker_cards_batch_array)->execute();
                if ($worker_card_inserted_array != 0) {
                    $log->addLog('Добавлено: ' . $worker_card_inserted_array . ' записей');
                } else {
                    throw new Exception('Ошибка при добавлении карт работников');
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод FullWorkerCard() - Метод полной загрузки пропусков сотрудников
     * @return array
     *
     * @package backend\controllers\serviceamicum
     * АЛГОРИТМ:
     * 1. Выгрузить данные пропусков работинков из таблицы (SapWorkerCard)
     * 2. Выгрузить всех работников
     * 3. Удалить все данные в таблице пропусков работников
     * 4. Перебор выгруженных данных пропусков работинков
     *      4.1. Проверка на существование работника в нашей базе
     *              существует? Добавить в массив на добавление пропусков работников
     *                          Если набралось 2000 записей массово вставить в базу (worker_card)
     * 5. Конец перебора
     * 6. Если в массиве на добавление пропусков работников остались данные добавить в базу (worker_card)
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.01.2020 16:25
     */
    public static function FullWorkerCard()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'FullWorkerCard';
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;

        $warnings[] = $method_name . '. Начало метода';
        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            $sap_workers_card = SapWorkerCard::find()
                ->asArray()
                ->all();
            $workers = Worker::find()
                ->select('id')
                ->asArray()
                ->indexBy('id')
                ->all();
            $del_worker_card = WorkerCard::deleteAll();
            $counter = 0;
            if (!empty($sap_workers_card)) {
                foreach ($sap_workers_card as $sap_worker_card) {
                    if (isset($workers[$sap_worker_card['FTABN_SAP']])) {
                        $workers_card[] = [$sap_worker_card['FTABN_SAP'], $sap_worker_card['FNCARD'], $sap_worker_card['LAST_CHANGE']];
                        $counter++;
                        if ($counter == 2000) {
                            if (isset($workers_card) && !empty($workers_card)) {
                                $worker_card_inserted = Yii::$app->db->queryBuilder
                                    ->batchInsert('worker_card',
                                        ['worker_id', 'card_number', 'date_time_sync'], $workers_card);
                                $sql_full = Yii::$app->db->createCommand($worker_card_inserted . 'ON DUPLICATE KEY UPDATE
                                                `card_number` = VALUES (`card_number`),`date_time_sync` = VALUES (`date_time_sync`)')->execute();
                                if ($sql_full != 0) {
                                    $warnings[] = $method_name . '. Номера пропусков успешно добавлены';
                                } else {
                                    $warnings[] = $method_name . '. Ошибка при добавлении номеров пропусков';
                                }
                            }
                            $counter = 0;
                            $workers_card = array();
                        }
                    }
                }
            }
            if (isset($workers_card) && !empty($workers_card)) {
                $worker_card_inserted = Yii::$app->db->queryBuilder
                    ->batchInsert('worker_card',
                        ['worker_id', 'card_number', 'date_time_sync'], $workers_card);
                $sql_full = Yii::$app->db->createCommand($worker_card_inserted . 'ON DUPLICATE KEY UPDATE
                                                `card_number` = VALUES (`card_number`),`date_time_sync` = VALUES (`date_time_sync`)')->execute();
                if ($sql_full != 0) {
                    $warnings[] = $method_name . '. Номера пропусков успешно добавлены';
                } else {
                    $warnings[] = $method_name . '. Ошибка при добавлении номеров пропусков';
                }
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
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

        // запись в БД окончания выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод PartUpdateWorkerCard() - Метод частичной загрузки пропусков сотрудников
     * @return array
     *
     * @package backend\controllers\serviceamicum
     *
     * АЛГОРИТМ:
     * 1. Получить максимальную дату последнего изменения записи
     * 2. Выгрузить все записи из таблицы синхронизации где дата последнего изменения выше полученной из таблицы пропусков работников
     * 3. Выгрузить всех работников идексируя по идентификатору
     * 4. Перебор выгруженных данных из таблицы синхронизации
     *      4.1. Проверка на существование работника в нашей базе
     *      4.1. Проверка на существование работника в нашей базе
     *              существует? Добавить в массив на добавление пропусков работников
     *                          Если набралось 2000 записей массово вставить в базу (worker_card) если были дубликаты обновит: номер карты и дату синхронизации
     * 5. Конец перебора
     * 6. Если в массиве на добавление пропусков работников остались данные добавить в базу (worker_card) если были дубликаты обновит: номер карты и дату синхронизации
     *
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.01.2020 17:21
     */
    public static function PartUpdateWorkerCard()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("PartUpdateWorkerCard");

        try {
            $log->addLog("Начало выполнение метода");
            /**
             * Получаем максимальную дату синхронизации пропусков работников
             */
            $max_date_sync = WorkerCard::find()
                ->select('max(date_time_sync) as max_date_time')
                ->scalar();
            $max_date = date('Y-m-d H:i:s', strtotime($max_date_sync));
            /**
             * Выгрузить все данные где дата изменеия записи выше полученной максимальной даты из пропусков работников
             */
            $sap_workers_card = SapWorkerCard::find()
                ->where(['>', 'LAST_CHANGE', $max_date])
                ->all();
            $workers = Worker::find()
                ->select('id')
                ->asArray()
                ->indexBy('id')
                ->all();
            $counter = 0;
            if (!empty($sap_workers_card)) {
                /******************** ПЕРЕБОР ВЫГРУЖЕННЫХ ДАННЫХ ********************/
                foreach ($sap_workers_card as $sap_worker_card) {
                    $count_record++;
                    /**
                     * Проверка на существование человека в нашей базе
                     */
                    if (isset($workers[$sap_worker_card['FTABN_SAP']])) {
                        $workers_card[] = [$sap_worker_card['FTABN_SAP'], $sap_worker_card['FNCARD'], $sap_worker_card['LAST_CHANGE']];
                        $counter++;
                        /**
                         * Если набралось 2000 записей вставить в таблицу пропусков сотрудников данные и обновить номер карты и дату синхронизации если возникли дубликаты
                         */
                        if ($counter == 2000) {
                            if (isset($workers_card) && !empty($workers_card)) {
                                $worker_card_inserted = Yii::$app->db->queryBuilder
                                    ->batchInsert('worker_card',
                                        ['worker_id', 'card_number', 'date_time_sync'], $workers_card);
                                $sql_full = Yii::$app->db->createCommand($worker_card_inserted . 'ON DUPLICATE KEY UPDATE
                                                    `card_number` = VALUES (`card_number`),`date_time_sync` = VALUES (`date_time_sync`)')->execute();
                                if ($sql_full != 0) {
                                    $log->addLog("Номера пропусков успешно добавлены: " . $sql_full);
                                } else {
                                    $log->addLog("Ошибка при добавлении номеров пропусков");
                                }
                            }
                            $counter = 0;
                            $workers_card = array();
                        }
                    }
                }
                /**
                 * Если массив на добавление не пуст вставить в таблицу пропусков сотрудников данные и обновить номер карты и дату синхронизации если возникли дубликаты
                 */
                if (isset($workers_card) && !empty($workers_card)) {
                    $worker_card_inserted = Yii::$app->db->queryBuilder
                        ->batchInsert('worker_card',
                            ['worker_id', 'card_number', 'date_time_sync'], $workers_card);
                    $sql_full = Yii::$app->db->createCommand($worker_card_inserted . 'ON DUPLICATE KEY UPDATE
                                                `card_number` = VALUES (`card_number`),`date_time_sync` = VALUES (`date_time_sync`)')->execute();
                    if ($sql_full != 0) {
                        $log->addLog("Номера пропусков успешно добавлены: " . $sql_full);
                    } else {
                        $log->addLog("Ошибка при добавлении номеров пропусков");
                    }
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод WorkerPass() - Разовое копирование данных из Oracle в нашу бд
     * @return array
     *
     * @throws Exception
     * @example
     *
     * @package backend\controllers\serviceamicum
     *
     * Входные обязательные параметры:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 13.01.2020 15:41
     */
    public static function WorkerPass()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $worker_for_batch_array = array();
        $method_name = 'WorkerPass';
        $warnings[] = $method_name . '. Начало метода';
        $date_now = new DateTime(Assistant::GetDateFormatYMD());
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $conn_oracle = oci_connect('Amicum_PS', 'y62#yZfl$U$e', '(DESCRIPTION =(ADDRESS = (PROTOCOL = TCP)(HOST = ' . SKUD_HOST_NAME . ')(PORT = 1521))(CONNECT_DATA =(SERVER =default)(SERVICE_NAME =' . SKUD_SERVICE_NAME . ')))', 'AL32UTF8');
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                 //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'Соединение с Oracle не выполнено';
            } else {
                $warnings [] = 'Соединение с Oracle установлено';
            }
            $query = oci_parse($conn_oracle, "SELECT 
            ID,
            FPEOPLEGID,
            FBDATE,
            FEDATE,
            FNCARD,
            FTYPECARD,
            FTYPE,
            TO_CHAR(FBDTIME, 'YYYY-MM-DD HH24:MI:SS') AS FBDTIME,
            TO_CHAR(FEDTIME, 'YYYY-MM-DD HH24:MI:SS') AS FEDTIME,
            FTABN_SAP,
            FEDITOR,
            FORG,
            FNHELP,
            FDEPGID,
            FTYPESYSTEM,
            TO_CHAR(LAST_CHANGE, 'YYYY-MM-DD HH24:MI:SS') AS LAST_CHANGE
            FROM PMS.CARDS WHERE FTYPECARD = 1 and current_date >= FBDTIME and current_date <= FEDTIME");
            oci_execute($query);
//            WorkerCard::deleteAll();
            $workers = Worker::find()
                ->select('id')
                ->asArray()
                ->indexBy('id')
                ->all();
            $count = 0;
            WorkerCard::deleteAll();
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $int_tabn_number = (int)$row['FTABN_SAP'];
                if (isset($workers[$int_tabn_number]['id'])) {
                    $worker_for_batch_array[] = [$workers[$int_tabn_number]['id'], $row['FNCARD']];
                    $count++;
                    if ($count == 2000) {
                        if (!empty($worker_for_batch_array)) {
                            $worker_card_inserted = Yii::$app->db->queryBuilder
                                ->batchInsert('worker_card',
                                    ['worker_id', 'card_number'], $worker_for_batch_array);
                            $sql_full = Yii::$app->db->createCommand($worker_card_inserted . 'ON DUPLICATE KEY UPDATE
                                                `card_number` = VALUES (`card_number`)')->execute();
                            if ($sql_full != 0) {
                                $warnings[] = $method_name . '. 2000 записей успешно добавлены';
                            } else {
                                $warnings[] = $method_name . '. Ошибка при добавлении 2000 записей';
                            }
                        }
                    }
                }
            }
            if (!empty($worker_for_batch_array)) {
                $worker_card_inserted = Yii::$app->db->queryBuilder
                    ->batchInsert('worker_card',
                        ['worker_id', 'card_number'], $worker_for_batch_array);
                $sql_full = Yii::$app->db->createCommand($worker_card_inserted . 'ON DUPLICATE KEY UPDATE
                                                `card_number` = VALUES (`card_number`)')->execute();
                if ($sql_full != 0) {
                    $warnings[] = $method_name . '. 2000 записей успешно добавлены';
                } else {
                    $warnings[] = $method_name . '. Ошибка при добавлении 2000 записей';
                }
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // ppkSynhPABNNMain - главный метод по синхронизации ПАБ (н/н - не делаются)
    public static function ppkSynhPABNNMain()
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSynhPABNNMain");

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $log->addLog("Начало выполнения метода");
//            Checking::deleteAll(['is not', 'pab_id', null]);                                                       //TODO 20.12.2019 rudov: удаление синхронизированных предписаний перед их добавлением

            $response = self::ppkSynhCheckingPab();
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }
            unset($response);
            $log->addLog("Синхронизировал Checking");
//            /**
//             * Пришёл пустой фильтр
//             *   да?    меняем тип переменной на массив чтобы не участвовало в выборке andFilterWhere(так как принимает пустыми только массив)
//             *   нет?   Пропустить
//             */
//            if ($filterChecking == '()' || empty($filterChecking || !$filterChecking)) {
//                $filterChecking = array();
//            }

            $checkingSapPAB = Checking::find()
                ->select(['id', 'pab_id', 'date_time_sync_pab'])
                ->where(['is not', 'pab_id', null])                 // фильтр по максимальной дате синхронизации
                ->asArray()
                ->indexBy('pab_id')
                ->all();
            $response = self::ppkSynhCkeckingWorkerPab($checkingSapPAB);
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка выполнения синхронизации  CkeckingWorkerPab');
            }
            unset($response);

            $log->addLog("Синхронизировал CkeckingWorkerPab");

            $response = self::ppkSynhInjunctionPab($checkingSapPAB);
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка выполнения синхронизации InjunctionPab');
            }
            unset($response);
            $log->addLog("Синхронизировал InjunctionPab");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => []], $log->getLogAll());
    }

// ppkSynhCheckingPab - метод синхронизации таблицы проверок (ПАБ)

    /**
     * Метод ppkSynhCheckingPab() - Метод синхронизации проверок ПАБ
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *        "Items":                    // фильтр
     *      "filterChecking":            // фильтр для поиска
     *      "maxDateDocument":            // максимальная дата обработки документа
     *      "errors":[]                    // массив ошибок
     *      "status":1                    // статус выполнения метода
     *      "warnings":[]                // массив предупреждений
     *      "debug":[]                    // массив для отладки
     * }
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * Алгоритм работы метода:
     *      1. Выборка из view данных по синхронизации
     *      2. Сформировать массив участков
     *      3. Получить для этого массива идентификаторы участков второго уровня
     *      4. Перебор полученных из view данных
     *      5. Поиск в таблице проверок с таким pab_id и участком
     *         а. если нашли то изменяем: дату начала и окончания проверки, pab_id, дату и время синхронизации
     *         б. если не нашли то закидываем в массив на добавление
     *      6. Если набралось 2 000 записей то закидываем массовой вставкоой
     *      7. После перебора если в массиве остались записи то докидываем
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.01.2020 8:51
     */
    public static function ppkSynhCheckingPab()
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSynhCheckingPab");
        $filterChecking = [];
        $maxDateDocument1 = "";

        // переменные этого метода
        $count_all = 0;
        $count_add = 0;
        $count_add_full = 0;
        $count_update = 0;
        $checking_company_departments = array();

        try {
            $log->addLog("Начало выполнения метода");

            $filterChecking = array();                                                   // фильтр для выборки данных для синхронизации
            $filterCheckingSap = null;                                                   // фильтр для выборки данных для синхронизации
            $fieldArrayChecking = array('title', 'date_time_start', 'date_time_end', 'checking_type_id', 'company_department_id', 'pab_id', 'date_time_sync_pab');
            $table_insertChecking = 'checking';
            $table_source = 'view_checking_pab';
            // находим последнюю дату синхронизации по справочнику
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_pab)')
                ->from($table_insertChecking)
                ->scalar();

            $log->addLog("максимальная дата для обработки записи maxDateDocument1: " . $maxDateDocument1);

            if ($maxDateDocument1) {
                $filterChecking = array('>', 'date_time_sync_pab', $maxDateDocument1);
                $filterCheckingSap = "DATE_MODIFIED_FROM_PAB > '" . $maxDateDocument1 . "'";
            }

            // получаем справочник проверок
            $view_checkings = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checkings) {
                throw new Exception('Справочник для синхронизации пуст');
            }

            $log->addLog("Выгрузили список проверок");

            foreach ($view_checkings as $checking) {
                if (!in_array($checking['company_department_id'], $checking_company_departments)) {
                    $checking_company_departments[] = $checking['company_department_id'];
                }
            }
            if (empty($checking_company_departments)) {
                throw new Exception('Не удалось сформировать массив участков');
            }

            $log->addLog("Сформирован массив участков для поиска второго уровня");

            /**
             * Получаем второй уровень участков по переданному массиву участком нарушителей
             */
            $second_level_company_departments = DepartmentController::GetUpperCompanies($checking_company_departments);
            unset($checking_company_departments);

            $log->addLog("Получили второй уровень участка по участкам нарушителей");

            // начинаем проверять записи на обновление добавление
            foreach ($view_checkings as $view_checking) {
                $count_all++;
                $checking = Checking::findOne([
                    'pab_id' => $view_checking['PLACE_PAB_COMPANY_DEPARTMENT']
                ]);
                if (!$checking) {
                    $batch_insert_item['title'] = 'ПАБ от ' . $view_checking['DT_BEG_AUDIT'];
                    $batch_insert_item['date_time_start'] = $view_checking['DT_BEG_AUDIT'];
                    $batch_insert_item['date_time_end'] = $view_checking['DATE_END'];
                    $batch_insert_item['checking_type_id'] = 1;
                    $batch_insert_item['company_department_id'] = isset($second_level_company_departments[$view_checking['company_department_id']]) ? $second_level_company_departments[$view_checking['company_department_id']] : $view_checking['company_department_id'];
                    $batch_insert_item['pab_id'] = $view_checking['PLACE_PAB_COMPANY_DEPARTMENT'];
                    $batch_insert_item['date_time_sync_pab'] = $view_checking['DATE_MODIFIED_FROM_PAB'];
                    $batch_insert_array[] = $batch_insert_item;
                    unset($batch_insert_item);
                    $count_add++;
//                    $count_add_full++;
                } else {
                    $count_update++;
                    $checking->date_time_start = $view_checking['DT_BEG_AUDIT'];
                    $checking->date_time_end = $view_checking['DATE_END'];
                    $checking->date_time_sync_pab = $view_checking['DATE_MODIFIED_FROM_PAB'];
                    if (!$checking->save()) {
                        $log->addData($view_checking, '$view_checking', __LINE__);
                        $log->addData($checking->errors, '$checking_errors', __LINE__);
                        throw new Exception('Не смог обновить запись checking');
                    }
                }
                unset($checking);
                // делаем массовую вставку данных справочника
                if ($count_add == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                    if ($insert_full === 0) {
                        throw new Exception('Записи в таблицу checking не добавлены');
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу checking");
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }
            unset($view_checkings, $second_level_company_departments);

            $log->addLog("Закинули основной массив проверок");

            // доталкиваем остатки
            // делаем массовую вставку данных справочника
            if (!empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception('Записи в таблицу checking не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу checking");
                }
                unset($batch_insert_array);
            }
            $log->addLog("обновил - $count_update - записей в таблице checking");
            $log->addLog("Докинули остатки");


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
//        $log->saveLogSynchronization($count_all);
        return array_merge(['Items' => $filterChecking, 'filterChecking' => $filterChecking, 'maxDateDocument' => $maxDateDocument1], $log->getLogAll());
    }

    /**
     * Метод actionUpdatePosition() - Обновление профессий
     *
     * Разработка метода:
     * причина: предоставленные справочники САП не подзходят т.к. содержат классификацию должностей согласно государственного справочника должностей
     * вводные данные: данные по должностям храняться в таблице Оракл ContactView в денормализованном виде без ключа, ввиде текста.
     * по сути у каждого человека задана должность, и задача получить весь список людей и их должностей и после этого сделать полную группировку должжостей
     * данный метод выполняется единоразово, последующее пополнение должностей осуществляется при синхронизации работников
     * алгоритм:
     *      Отладка котроль производительности: записать в БД началов выполнения запроса, получить айди лога. Старт отсчетов и контроль памяти
     * sapCopyPositionFromContactView
     *  1. получить сгруппированный список должностей из представления Оракл Contact_view (группировка по полю PLANS_STEXT),
     *  2. Очистить промежуточную таблицу sap_position_group_from_contact_view системы амикум
     *  3. положить группированные должности Оракл в промежуточную таблицу sap_position_group_from_contact_view системы амикум
     *
     * sapUpdatePosition
     *  1. соединить промежуточную таблицу системы амикум со справчоников position системы Амикум по полю title и выбрать все записи, которых нет в position
     *  2. уложить новые должности в таблицу position.
     *      Отладка котроль производительности: записать в БД окончание выполнения запроса по ранее полученному айди
     *
     * Рефакторинг выполнил:
     * Якимов М.Н. 19.01.2020
     */

    public
    static function sapUpdatePosition()
    {
        // Стартовая отладочная информация
        $method_name = 'actionUpdatePosition';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                           // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
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
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */
            $positions = (new Query())
                ->select('
                PLANS_STEXT as position_title,
                sap_position_group_from_contact_view.QUALIFICATION as qualification,
                STEXT as short_title_sap
                ')
                ->from('sap_position_group_from_contact_view')
                ->leftJoin('position', 'position.title = sap_position_group_from_contact_view.PLANS_STEXT')
                ->leftJoin('sap_position_update', 'sap_position_update.STELL = sap_position_group_from_contact_view.STELL')
                ->where('position.id is null')
                ->all();

            /** Отладка */
            $description = 'Получил данные с БД для обработки с таблицы sap_position_group_from_contact_view';                                                                      // описание текущей отладочной точки
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

            if ($positions) {
                $insert_count = Yii::$app->db->createCommand()->batchInsert('position', [
                    'title', 'qualification', 'short_title'
                ], $positions)->execute();
                if ($insert_count === 0) {
                    throw new Exception($method_name . '. Записи в таблицу position не добавлены');
                } else {
                    $warnings[] = $method_name . ". добавил - $insert_count - записей в таблицу position";
                }
                $count_all = $insert_count;
            }

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод sapCopyPositionFromContactView() - копирование сгруппированных должностей из представления CONTACT_VIEW - ORACLE
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 19.01.2020 11:17
     */
    public
    function sapCopyPositionFromContactView()
    {
        // Стартовая отладочная информация
        $method_name = 'sapCopyPositionFromContactView';                                                                // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = 0;                                                                                    // количество обработанных строк первичных данных
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
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                           // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */
            // очищаем таблицу назначения
            $del_full_count = Yii::$app->db->createCommand()->delete('sap_position_group_from_contact_view')->execute();
            $warnings[] = $method_name . ". удалил $del_full_count записей из таблицы sap_position_group_from_contact_view";

            // получаем сгруппированные должности с представления CONTACT_VIEW
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                throw new Exception($method_name . '. Соединение с Oracle не выполнено');
            } else {
                $warnings [] = $method_name . '. Соединение с Oracle установлено';
            }
            $query = oci_parse($conn_oracle, "SELECT PLANS_STEXT, STELL FROM AMICUM.CONTACT_VIEW GROUP BY PLANS_STEXT, STELL");
            oci_execute($query);

            $count = 0;                                                                                                 // текущее количество обработанных записей
            $count_all = 0;                                                                                             // общее количество обработанных записей

            /** Отладка */
            $description = 'Получил данные из Оракт';                                                                   // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 0;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            // обрабатываем копируемые данные
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                 //цикл по массиву строк запроса
            {
                // обрабатываем записи из оракла, если ключ больше 6 символов (STELL) значит в нем есть разрад (квалификация)
                if (!empty($row['PLANS_STEXT']) || $row['STELL'] === ' ') {
                    $sap_position_item['PLANS_STEXT'] = $row['PLANS_STEXT'];                                             // ключ государственной долности
                    $sap_position_item['STELL'] = (int)$row['STELL'];                                                  // наименование должности
                    if (strlen($row['STELL']) > 6) {
                        $sap_position_item['QUALIFICATION'] = (int)substr($row['STELL'], -2);                     // квалификация должности
                    } else {
                        $sap_position_item['QUALIFICATION'] = null;
                    }
                    $query_result[] = $sap_position_item;
                    $count++;
                    $count_all++;
                } else {
                    $null_value[] = $row;
                }

                if ($count == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert('sap_position_group_from_contact_view', [
                        'PLANS_STEXT'
                        , 'STELL'
                        , 'QUALIFICATION'
                    ], $query_result)->execute();
                    if ($insert_full === 0) {
                        throw new Exception($method_name . '. Записи в таблицу sap_position_group_from_contact_view не добавлены');
                    } else {
                        $warnings[] = $method_name . ". добавил - $insert_full - записей в таблицу sap_position_group_from_contact_view";
                    }
                    $query_result = [];
                    $count = 0;
                }
            }
            // вставляем остатки данных если они есть
            if ($count !== 0) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert('sap_position_group_from_contact_view', [
                    'PLANS_STEXT'
                    , 'STELL'
                    , 'QUALIFICATION'
                ], $query_result)->execute();
                if ($insert_full === 0) {
                    throw new Exception($method_name . '. Записи в таблицу sap_position_group_from_contact_view не добавлены');
                } else {
                    $warnings[] = $method_name . ". добавил - $insert_full - записей в таблицу sap_position_group_from_contact_view";
                }
                $warnings[] = $method_name . ". количество добавляемых записей: " . $count_all;
            }

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод ppkSynhCkeckingWorkerPab() - Синхронизация проверяющих ПАБ
     * @return array
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * checkingSapPAB - массив проверок с номером ПАБ
     * maxDateDocument - максимальная дата обработки документа
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "Items":1,
     *      "auditorChecking":{}                // массив аудиторов на проверке
     *      "errors":{}                            // массив ошибок
     *      "status":{}                            // статус выполнения метода
     *      "warnings":{}                        // массив предупреждений (ход выполнения метода)
     *      "debug":{}                            // массив отладки
     * }
     *
     * @package backend\controllers\serviceamicum
     *
     * Алгоритм работы метода:
     *       1. Выгрузить данные из представления
     *       2. При переборе находить соответсвующую проверку (checking) и добавлять в массив на массовую вставку нарушителя, ответственного, аудитора
     *       3. Если количество элементов в массиве = 2 000 тогда добавляем и очищаем массив
     *       4. После перебора если массив на добавление не пуст тогда докидываем то что осталось
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.01.2020 17:25
     */
    public static function ppkSynhCkeckingWorkerPab($checkingSapPAB)
    {
        $count_all = 0;                                                                                           // количество вставленных записей
        $count_record = 0;                                                                                              // количество обработанных записей
        $maxDateDocument1 = "";
        $filterChecking = null;

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSynhCkeckingWorkerPab");

        // переменные этого метода
        $count_update = 0;
        $batch_insert_array = array();
        $count_add = 0;
        $count_add_full = 0;
        $violatorChecking = array();                                                                                    //нарушители в проверке
        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $log->addLog("Начало выполнения метода");

            /** Метод начало */
            $filterChecking = null;
            $filterCheckingSap = null;
            $fields = ['worker_id', 'worker_type_id', 'checking_id', 'instruct_pab_id', 'date_time_sync_pab'];
            $table_insertChecking = 'checking_worker_type';
            $table_source = 'view_checking_worker_pab';

            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_pab)')
                ->from($table_insertChecking)
                ->scalar();

            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync_pab >'" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_MODIFIED > '" . $maxDateDocument1 . "'";
            }
            $log->addLog("Максимальная дата обработки документа: " . $maxDateDocument1);

            $view_checking_worker = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checking_worker) {
                throw new Exception('данных для синхронизации нет');
            }

            $log->addLog("Выгрузили данные из представления типов работников");

            foreach ($view_checking_worker as $item) {
                $worker_types[$item['PLACE_PAB_COMPANY_DEPARTMENT']]['violators'] = array();
                $worker_types[$item['PLACE_PAB_COMPANY_DEPARTMENT']]['auditors'] = array();
                $worker_types[$item['PLACE_PAB_COMPANY_DEPARTMENT']]['responsibles'] = array();
            }

            $log->addLog("Сформировали массивы: нарушителей, аудиторов, ответственных по ключу PLACE_PAB_COMPANY_DEPARTMENT");


            foreach ($view_checking_worker as $checking_worker) {
                $count_all++;
                $checking_id = $checkingSapPAB[$checking_worker['PLACE_PAB_COMPANY_DEPARTMENT']]['id'];
                $date_pab = $checkingSapPAB[$checking_worker['PLACE_PAB_COMPANY_DEPARTMENT']]['date_time_sync_pab'];
                $auditorChecking[$checking_id]['worker_id'] = $checking_worker['worker_auditor_id'];
                $auditorChecking[$checking_id]['checking_id'] = $checking_id;
                /******************** Аудитор ********************/
                if (!empty($checking_worker['worker_auditor_id'])) {
                    if (!in_array($checking_worker['worker_auditor_id'], array_keys($worker_types[$checking_worker['PLACE_PAB_COMPANY_DEPARTMENT']]['auditors']))) {
                        $worker_types[$checking_worker['PLACE_PAB_COMPANY_DEPARTMENT']]['auditors'][$checking_worker['worker_auditor_id']]['worker_id'] = $checking_worker['worker_auditor_id'];
                        $checkingWorkerResposnible = CheckingWorkerType::findOne(['instruct_pab_id' => $checking_worker['worker_auditor_id'], 'checking_id' => $checking_id]);
                        $auditor_id = $checking_worker['worker_auditor_id'];
                        if (!$checkingWorkerResposnible) {
                            $batch_insert_item['worker_id'] = $auditor_id;
                            $batch_insert_item['worker_type_id'] = 1;
                            $batch_insert_item['checking_id'] = $checking_id;
                            $batch_insert_item['instruct_pab_id'] = $auditor_id;
                            $batch_insert_item['date_time_sync_pab'] = $date_pab;
                            $batch_insert_array[] = $batch_insert_item;
                            unset($batch_insert_item);
                            $count_add++;
//                            $count_add_full++;
                        } else {
                            $checkingWorkerResposnible->worker_id = $checking_worker['worker_auditor_id'];
                            $checkingWorkerResposnible->instruct_pab_id = $auditor_id;
                            $checkingWorkerResposnible->date_time_sync_pab = $checking_worker['DATE_MODIFIED'];
                            if (!$checkingWorkerResposnible->save()) {
                                $log->addData($checking_worker, '$checking_worker', __LINE__);
                                $log->addData($checkingWorkerResposnible->errors, '$checkingWorkerResposnible_errors', __LINE__);
                                throw new Exception('Не смог обновить запись checkingWorker');
                            }
                        }
                        unset($checkingWorkerResposnible);
                    }
                }
                /******************** Нарушитель ********************/
                if (!in_array($checking_worker['worker_violator_id'], array_keys($worker_types[$checking_worker['PLACE_PAB_COMPANY_DEPARTMENT']]['violators']))) {
                    $worker_types[$checking_worker['PLACE_PAB_COMPANY_DEPARTMENT']]['violators'][$checking_worker['worker_violator_id']]['worker_id'] = $checking_worker['worker_violator_id'];
                    $checkingWorkerResposnible = CheckingWorkerType::findOne(['instruct_pab_id' => $checking_worker['worker_violator_id'], 'checking_id' => $checking_id]);
                    $violator_id = $checking_worker['worker_violator_id'];
                    $violatorChecking[$checking_id]['worker_id'] = $violator_id;
                    $violatorChecking[$checking_id]['checking_id'] = $checking_id;
                    if (!$checkingWorkerResposnible) {
                        $batch_insert_item['worker_id'] = $violator_id;
                        $batch_insert_item['worker_type_id'] = 4;
                        $batch_insert_item['checking_id'] = $checking_id;
                        $batch_insert_item['instruct_pab_id'] = $violator_id;
                        $batch_insert_item['date_time_sync_pab'] = $date_pab;
                        $batch_insert_array[] = $batch_insert_item;
                        unset($batch_insert_item);
                        $count_add++;
//                        $count_add_full++;
                    } else {
                        $checkingWorkerResposnible->worker_id = $checking_worker['worker_violator_id'];
                        $checkingWorkerResposnible->instruct_pab_id = $violator_id;
                        $checkingWorkerResposnible->date_time_sync_pab = $checking_worker['DATE_MODIFIED'];
                        if (!$checkingWorkerResposnible->save()) {
                            $log->addData($checking_worker, '$checking_worker', __LINE__);
                            $log->addData($checkingWorkerResposnible->errors, '$checkingWorkerResposnible_errors', __LINE__);
                            throw new Exception('Не смог обновить запись checkingWorker');
                        }
                    }
                    unset($checkingWorkerResposnible);
                }

                /******************** Ответственный ********************/
                if (!empty($checking_worker['worker_responsible'])) {
                    if (!in_array($checking_worker['worker_responsible'], array_keys($worker_types[$checking_worker['PLACE_PAB_COMPANY_DEPARTMENT']]['responsibles']))) {
                        $worker_types[$checking_worker['PLACE_PAB_COMPANY_DEPARTMENT']]['responsibles'][$checking_worker['worker_responsible']]['worker_id'] = $checking_worker['worker_responsible'];
                        $checkingWorkerResposnible = CheckingWorkerType::findOne(['instruct_pab_id' => $checking_worker['worker_responsible'], 'date_time_sync_pab' => $date_pab]);
                        $responsible_id = $checking_worker['worker_responsible'];
                        if (!$checkingWorkerResposnible) {
                            $batch_insert_item['worker_id'] = $responsible_id;
                            $batch_insert_item['worker_type_id'] = 2;
                            $batch_insert_item['checking_id'] = $checking_id;
                            $batch_insert_item['instruct_pab_id'] = $responsible_id;
                            $batch_insert_item['date_time_sync_pab'] = $date_pab;
                            $batch_insert_array[] = $batch_insert_item;
                            unset($batch_insert_item);
                            $count_add++;
//                            $count_add_full++;
                        } else {
                            $checkingWorkerResposnible->worker_id = $checking_worker['worker_responsible'];
                            $checkingWorkerResposnible->instruct_pab_id = $responsible_id;
                            $checkingWorkerResposnible->date_time_sync_pab = $checking_worker['DATE_MODIFIED'];
                            if (!$checkingWorkerResposnible->save()) {
                                $log->addData($checking_worker, '$checking_worker', __LINE__);
                                $log->addData($checkingWorkerResposnible->errors, '$checkingWorkerResposnible_errors', __LINE__);
                                throw new Exception('Не смог обновить запись checkingWorker');
                            }
                        }
                        unset($checkingWorkerResposnible);
                    }
                }

                if ($count_add >= 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fields, $batch_insert_array)->execute();
                    if ($insert_full === 0) {
                        throw new Exception("Записи в таблицу ' . $table_insertChecking . ' не добавлены");
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу $table_insertChecking");
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }
            unset($view_checking_worker, $worker_types);

            $log->addLog("Выполнили перебор и закинули основной массив данных");

            if (isset($batch_insert_array) && !empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fields, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception('Записи в таблицу ' . $table_insertChecking . ' не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу $table_insertChecking");
                }
                unset($batch_insert_array);
            }

            $log->addLog("Докинули остальные данные массива типов работников");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
//        $log->saveLogSynchronization($count_all);

        return array_merge(['Items' => [], 'filterChecking' => $filterChecking, 'maxDateDocument' => $maxDateDocument1, 'violatorChecking' => $violatorChecking], $log->getLogAll());
    }

    /**
     * Метод ppkSynhInjunctionPab() - Синхронизция
     * @param $checkingPabs - проверки паб идексированые по месту и дате
     * @param $violatorChecking - массив нарушителей проверки
     * @param $maxDateDocument - максимальная дата обработки записи
     * @return array
     *
     * Алгоритм работы метода:
     *          1. Выгрузить ПАБы
     *          2. Сформировать 2 массива:
     *                       2.1) участки ответственных  - для получения первого уровня участка для получения id шахты
     *                       2.2) массив вида место->[pab,observation_number] - для заполенения поля observation_number
     *          3. Получить массив участков первого уровня
     *          4. Получить список шахт идексированный по компании
     *          5. Найти место по названию если нету то создать
     *                       5.1) если поле не заполенно по умолчанию ставиться место "Не заполнено"
     *          6. Добавитьв массив по идентификатору проверке место (для заполенния мест проверок)
     *          7. Найти ПАБ по идентификатору
     *                       7.1) если не найдено, искать по: месту, нарушителю, проверке, 2 типу документа (ПАБ)
     *                       7.2) если не найдено, создать предписание, статус расчитать по дате последнего изменения к дате создания
     *                       7.3) если найдено меняем нарушителя и статус рассчитанный по дате последней модификации записи
     *          8. Найти нарушение по наименованию
     *                       8.1) если нарушение не найдено
     *                                  8.1.1) проверить длину строки нарушения если больше 1000 символов, обрезать
     *                                  8.1.2) Убрать пробелы с начала и конца строки
     *                                  8.1.3) Если не пусто то создать нарушение
     *                                              8.1.3.1) Найти вид нарушения по REF_ERROR_DIRECTION_ID, если найдено
     *                                                          8.1.3.1.1) Найти тип нарушения по найденому виду нарушения, если не найдено создать тип нарушения
     *                                              8.1.3.2) Заполняем типом "Прочее"
     *          9. Найти документ по наименованию
     *                       9.1) Если не найдено, обрезаем пробелы с начала и конца строки, проверка на пустоту
     *                                  9.1.1) Если не пусто, тогда создаём новый документ
     *                                  9.1.2) Если пусто заполянем "Без документа"
     *                       9.2) Если найдено то берём идентификатор документа
     *           10. Найти параграф документа по тексту
     *                       10.1) Если не найдено, обрезаем пробелы с начала и конца строки
     *                                  10.1.1) Если не пусто, тогда создаём новый пункт
     *                                  10.1.2) Если пусто, тогда по умолчанию "Без пункта"
     *                       10.2) Если найдено, берём идентификатор найденой записи
     *           11. Найти нарушение ПАБа
     *                       11.1) Если не найдено
     *                                  11.1.1) Если наименование тяжести нарушения не пусто,
     *                                                                          низкая тяжесть - вероятность и опасность = 1
     *                                                                          средняя тяжесть - вероятность и опасность = 3
     *                                  11.1.2) Если наименование тяжести нарушения пусто вероятность и опасность = 5
     *                                  11.1.3) Сохраняем нарушение ПАБа
     *                       11.2) Если найдено
     *                                  11.2.1) Обновляем данные: вероятность, тяжесть, ид ПАБа, ид места, ид пункта и ид документа
     *           12. Сохранить статусы наруния предписания
     *           13. Добавить в массив нарушителей: ид нарушителя и ид нарушения ПАБа
     *           14. Перебрать и сохранить собранные массивы
     *                       14.1) Места проверок
     *                       14.2) Статусы предписаний
     *                       14.3) Нарушителей
     *
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 29.01.2020 9:30
     */
    public static function ppkSynhInjunctionPab($checkingPabs)
    {
        $count_record = 0;                                                                                              // количество обработанных записей
        $count_all = 0;                                                                                           // количество вставленных записей
        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSynhInjunctionPab");


        // параметры этого метода синхронизации
        $gilty_array = array();                         // список выиновных ответственных за устранение нарушения
        $injunction_status = array();                   // список статусов предписаний
        $place_array = array();                         // список мест, в которых производилась проверка
        $violator_array = array();                      // список нарушителей для вставки в VIOLATOR
        $to_get_first_level = array();                  // массив участков первого уровня
        $place_pabs = array();                           // массив пабов для места
        $coorect_meassure_array = array();              // список корректирующих мероприятий
        $count_add = 0;                                 // количество добавленных записей
        $count_add_full = 0;                            // полное количество добавленных записей
        $count_update = 0;                              // количество обновленных записей
        $dateNow = Assistant::GetDateFormatYMD();       // текущая дата и время
        try {
            $log->addLog("Начало выполнения метода");

//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");

            /** Метод начало */
            $filterChecking = null;
            $filterCheckingSap = null;
            $fields = array('place_id', 'worker_id', 'kind_document_id', 'rtn_statistic_status_id', 'checking_id', 'description', 'status_id', 'observation_number', 'company_department_id', 'instruct_pab_id', 'date_time_sync_pab');
            $table_insertChecking = 'injunction';
            $table_source = 'view_checking_injunction_pab';
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_pab)')
                ->from($table_insertChecking)
                ->scalar();

            $log->addLog("максимальная дата для обработки записи" . $maxDateDocument1);

            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync_pab > '" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_MODIFIED > '" . $maxDateDocument1 . "'";
            }

            $log->addLog('. Максимальная дата обработки записи: ' . $maxDateDocument1);

            $view_checking_pabs = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checking_pabs) {
                throw new Exception('данных для синхронизации нет');
            }
            $injunction_controller = new InjunctionController(1, false);
            $kind_violations = KindViolation::find()
                ->select('id,title,ref_error_direction_id,date_time_sync')
                ->indexBy('ref_error_direction_id')
                ->asArray()
                ->all();

            $violation_types = ViolationType::find()
                ->select('id,ref_error_direction_id')
                ->indexBy('ref_error_direction_id')
                ->asArray()
                ->all();

            $log->addLog('Выгрузили ПАБы');

            foreach ($view_checking_pabs as $pab) {
                /**
                 * Подгатавливает массив для передачи в функцию, для получения высшего уровня идентификатора участка
                 */
                if (!empty($pab['responsible_company_department'])) {
                    if (!in_array($pab['responsible_company_department'], $to_get_first_level)) {
                        $to_get_first_level[] = $pab['responsible_company_department'];
                    }
                }
                /**
                 * Этот кусок необходим для observation_number
                 * Делает массив следующего вида:   [(ид места)_(дата начала проверки))]
                 *                                               [pab_id]
                 *                                                    pab_id:
                 *                                                    observation_number:
                 */
                if (isset($place_pabs[$pab['PLACE_PAB_COMPANY_DEPARTMENT']])) {
                    if (!in_array($pab['worker_violator_id'], array_keys($place_pabs[$pab['PLACE_PAB_COMPANY_DEPARTMENT']]))) {
                        $observation_number = end($place_pabs[$pab['PLACE_PAB_COMPANY_DEPARTMENT']])['observation_number'];
                        $place_pabs[$pab['PLACE_PAB_COMPANY_DEPARTMENT']][$pab['worker_violator_id']]['worker_violator_id'] = $pab['worker_violator_id'];
                        $place_pabs[$pab['PLACE_PAB_COMPANY_DEPARTMENT']][$pab['worker_violator_id']]['observation_number'] = (int)$observation_number + 1;
                        unset($observation_number);
                    }
                } else {
                    $place_pabs[$pab['PLACE_PAB_COMPANY_DEPARTMENT']][$pab['worker_violator_id']]['worker_violator_id'] = $pab['worker_violator_id'];
                    $place_pabs[$pab['PLACE_PAB_COMPANY_DEPARTMENT']][$pab['worker_violator_id']]['observation_number'] = 1;
                }
            }
            unset($pab);

            $log->addLog('Подготовлены 2 массива: 1) для получения высшего уровня участка; 2)Для заполнения observation_number');

            /**
             * Получение высшего уровня участка участка возврощает массив вида:
             *       [переданный company_department_id] => company_department_id (первого уровня)
             */
            $first_level_company_departments = DepartmentController::GetFirstLevelCompanies($to_get_first_level);
            unset($to_get_first_level);

            $log->addLog('Получили первый уровень участков');

            foreach ($view_checking_pabs as $view_pab) {
                $count_all++;
                /**
                 * Получаем идентификатор проверки по: месту, пабу и участку ответственного
                 */
//                $index = $view_pab['REF_PLACE_AUDIT_ID'] . '_' . $view_pab['DT_BEG_AUDIT'];
                $checking_id = $checkingPabs[$view_pab['PLACE_PAB_COMPANY_DEPARTMENT']]['id'];
                /**
                 * Получаем идентификатор нарушителя
                 */
//                if (isset($violatorChecking[$checking_id]['worker_id']) && !empty($violatorChecking[$checking_id]['worker_id'])) {
//                    $worker_id = $violatorChecking[$checking_id]['worker_id'];
//                } else {
//                    $worker_id = 1;
//                }
                $worker_id = $view_pab['worker_violator_id'];
                /******************** Обрабатываем место ********************/
                $response = $injunction_controller::GetPlaceByTitle($view_pab['PLACE_TITLE']);
                if ($response['status'] != 1) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении идентификатора места');
                }

                $place_id = $response['Items'];

                unset($response);
                /**
                 * Добавялем в массив для записи мест проверок
                 */
                $place_array[$checking_id][$place_id]['checking_id'] = $checking_id;
                $place_array[$checking_id][$place_id]['place_id'] = $place_id;

                /******************** Обработка предписания ********************/
                $pab = Injunction::findOne(['instruct_pab_id' => $view_pab['PLACE_PAB_COMPANY_DEPARTMENT'], 'worker_id' => $worker_id]);
                if (!$pab) {
                    if (isset($place_pabs[$view_pab['PLACE_PAB_COMPANY_DEPARTMENT']][$worker_id]['observation_number'])) {
                        $observation_number = $place_pabs[$view_pab['PLACE_PAB_COMPANY_DEPARTMENT']][$worker_id]['observation_number'];
                    } else {
                        $observation_number = 1;
                    }
                    $pab = Injunction::findOne([
                        'place_id' => $place_id,
                        'worker_id' => $worker_id,
                        'kind_document_id' => 2,
                        'checking_id' => $checking_id,
                        'observation_number' => $observation_number
                    ]);
                    if (!$pab) {
                        $pab = new Injunction();
                        $pab->place_id = $place_id;
                        $pab->worker_id = $worker_id;
                        $pab->kind_document_id = 2;
                        $pab->rtn_statistic_status_id = 56;
                        $pab->description = null;
                        $pab->checking_id = $checking_id;
                        $pab->status_id = 59;
                        $pab->observation_number = $observation_number;
                        $pab->company_department_id = $view_pab['company_department_id'];
                        $count_add++;
                        $count_add_full++;
                    } else {
                        $pab->worker_id = $worker_id;
                        $pab->status_id = 59;
                    }
                } else {
                    $pab->worker_id = $worker_id;
                    $pab->status_id = 59;
                }
                $pab->instruct_pab_id = $view_pab['PLACE_PAB_COMPANY_DEPARTMENT'];
                $pab->date_time_sync_pab = $view_pab['DATE_MODIFIED'];
                if ($pab->save()) {
                    $pab->refresh();
                    $pab_id = $pab->id;
                    $injunction_status[$pab_id][$worker_id]['injunction_id'] = $pab_id;
                    $injunction_status[$pab_id][$worker_id]['worker_id'] = $worker_id;
                    $injunction_status[$pab_id][$worker_id]['status_id'] = $pab->status_id;
                    $injunction_status[$pab_id][$worker_id]['date_time'] = $view_pab['DT_BEG_AUDIT'];
                    $count_update++;
                } else {
                    $log->addData($view_pab, '$view_pab:', __LINE__);
                    $log->addData($pab->errors, '$pab->errors:', __LINE__);
                    throw new Exception('Ошибка при сохраненении предписания Injunction');
                }
                $pab_status_id = $pab->status_id;
                unset($pab);

                /******************** Обработка нарушения ********************/
                // описание нарушения может быть больше 1000 символов потому мы его обрезаем
                $str_len_violation = strlen($view_pab['DAN_EFFECT_IN_RDE']);                                            // вычисляем длину нарушения

                if ($str_len_violation > 1000) {
                    $violation_saving_title = mb_substr($view_pab['DAN_EFFECT_IN_RDE'], 0, 1000);                       // если длина больше 1000 символов, то мы ее обрезаем
                } else {
                    $violation_saving_title = $view_pab['DAN_EFFECT_IN_RDE'];                                           // иначе оставляем так как есть
                }

                unset($str_len_violation);

                $violation_saving_title = trim($violation_saving_title);                                                //убираем пробелы с начала и конца строки для того, что бы обеспечить гарантированный поиск совпадения

                $violation = Violation::find()->where(['title' => $violation_saving_title])->limit(1)->one();
                if (!$violation) {

                    $violation = new Violation();

                    if (!empty($violation_saving_title)) {
                        $violation->title = $violation_saving_title;
                        unset($violation_saving_title);
                        /**
                         *  Не пустой ли идентификатор направления нарушения
                         *    не пустой?    ищем вид нарушения по REF_ERROR_DIRECTION_ID
                         *                    нашли?        ищем тип нарушения
                         *                                    нашли?        берём идентификатор типа нарушения
                         *                                    не нашли?    создаём новый тип нарушения
                         *                    не нашли?    ищем тип нарушения
                         *                                    нашли?        берём идентификатор типа нарушения
                         *                                    не нашли?    значение по умолчанию
                         *    пустой?    значение по умолчанию
                         */
                        if ($view_pab['REF_ERROR_DIRECTION_ID']) {
                            if (isset($kind_violations[$view_pab['REF_ERROR_DIRECTION_ID']]['id']) && !empty($kind_violations[$view_pab['REF_ERROR_DIRECTION_ID']]['id'])) {
                                if (isset($violation_types[$view_pab['REF_ERROR_DIRECTION_ID']]['id']) && !empty($violation_types[$view_pab['REF_ERROR_DIRECTION_ID']]['id'])) {
                                    $violation_type_for_violation = $violation_types[$view_pab['REF_ERROR_DIRECTION_ID']]['id'];
                                } else {
                                    $json = json_encode(['kind_violation_id' => $kind_violations[$view_pab['REF_ERROR_DIRECTION_ID']]['id'],
                                        'kind_violation_title' => $kind_violations[$view_pab['REF_ERROR_DIRECTION_ID']]['title'],
                                        'ref_error_direction_id' => $view_pab['REF_ERROR_DIRECTION_ID'],
                                        'date_time_sync' => $kind_violations[$view_pab['REF_ERROR_DIRECTION_ID']]['date_time_sync']]);
                                    $response = $injunction_controller::ViolationType($json);
                                    if ($response['status'] != 1) {
                                        $log->addLogAll($response);
                                        throw new Exception('Ошибка при сохранении типа нарушения');
                                    }
                                    $violation_type_for_violation = $response['Items'];
                                    unset($response, $json);
                                }
                            } else {
                                if (isset($violation_types[$view_pab['REF_ERROR_DIRECTION_ID']]['id']) && !empty($violation_types[$view_pab['REF_ERROR_DIRECTION_ID']]['id'])) {
                                    $violation_type_for_violation = $violation_types[$view_pab['REF_ERROR_DIRECTION_ID']]['id'];
                                } else {
                                    $violation_type_for_violation = 128;
                                }
                            }
                            $violation->violation_type_id = $violation_type_for_violation;
                        } else {
                            $violation->violation_type_id = 128;
                        }
                        if (!$violation->save()) {
                            $log->addData($view_pab['REF_ERROR_DIRECTION_ID'], '$view_pab[REF_ERROR_DIRECTION_ID]:', __LINE__);
                            $log->addData($violation_type_for_violation, '$violation_type_for_violation', __LINE__);
                            $log->addData($violation->errors, '$violation->errors:', __LINE__);

                            throw new Exception('Ошибка сохранения Описания нарушения Violation');
                        }
                        $violation->refresh();
                        $violation_id = $violation->id;
                    } else {
                        $violation_id = 1;
                    }
                } else {
                    $violation_id = $violation->id;
                }
                unset($violation, $violation_type_for_violation);

                /******************** Обработка документа ********************/
                $json = json_encode(['document_title' => $view_pab['DOC_LINK'], 'worker_id' => $worker_id]);
                $response = $injunction_controller::GetDocumentByTitle($json);
                if ($response['status'] != 1) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении/создании документа');
                }
                $document_id = $response['Items'];
                unset($response, $json);

                /******************** Обработка пункта документа ********************/
                $json = json_encode(['paragraph_pb_title' => $view_pab['ERROR_POINT'], 'document_id' => $document_id]);
                $response = $injunction_controller::GetParagraphPbByText($json);
                if ($response['status'] != 1) {
                    $log->addLogAll($response);
                    throw new Exception('Ошибка при получении/создании пункта документа');
                }
                $paragraph_pb_id = $response['Items'];
                unset($response, $json);

                /******************** Добавляем/Изменяем нарушение предписания ********************/
                if (!empty($view_pab['FAILURE_NAME'])) {
                    if ($view_pab['FAILURE_NAME'] == 'низкая') {
                        $probability = 1;
                        $gravity = 1;
                    } elseif ($view_pab['FAILURE_NAME'] == 'средняя') {
                        $probability = 3;
                        $gravity = 3;
                    } else {
                        $probability = 5;
                        $gravity = 5;
                    }
                } else {
                    $probability = 5;
                    $gravity = 5;
                }
                $injunction_violation = InjunctionViolation::findOne(['instruct_pab_id' => $view_pab['PLACE_PAB_COMPANY_DEPARTMENT'], 'injunction_id' => $pab_id]);
                if (empty($injunction_violation)) {
                    $injunction_violation = new InjunctionViolation();

                    $injunction_violation->injunction_id = $pab_id;
                    $injunction_violation->place_id = $place_id;                                                               // ключ места в котором выписано предписание
                    $injunction_violation->violation_id = $violation_id;
                    $injunction_violation->paragraph_pb_id = $paragraph_pb_id;                                                 // ключ параграфа нормативного документа
                    $injunction_violation->document_id = $document_id;                                                         // ключ нормативного документа, требования которого были нарушены
//                    $count_add++;
//                    $count_add_full++;
                } else {                                                                                                // обновляем если такое уже было
                    Violator::deleteAll(['injunction_violation_id' => $injunction_violation->id]);
                    $injunction_violation->injunction_id = $pab_id;                                                     // ключ предписания
                    $injunction_violation->place_id = $place_id;                                                               // ключ места в котором выписано предписание
                    $injunction_violation->violation_id = $violation_id;                                                       // ключ описания нарушения с привязкой к направлению нарушений
                    $injunction_violation->paragraph_pb_id = $paragraph_pb_id;                                                 // ключ параграфа нормативного документа
//                    $inj_violation->reason_danger_motion_id = null;                                                     // ключ причины опасного действия - используется в ПАБ
                    $injunction_violation->document_id = $document_id;                                                         // ключ нормативного документа, требования которого были нарушены
                }
                $injunction_violation->probability = $probability;
                $injunction_violation->gravity = $gravity;
                $injunction_violation->instruct_pab_id = $view_pab['PLACE_PAB_COMPANY_DEPARTMENT'];
                $injunction_violation->date_time_sync_pab = $view_pab['DATE_MODIFIED'];
                if ($injunction_violation->save()) {
                    $injunction_violation->refresh();
                    // формируем блок массовой вставки статусов предписания в таблицу injunction_status
                    $inj_violation_id = $injunction_violation->id;
                    $count_update++;
                } else {
                    $log->addData($view_pab, '$view_pab:', __LINE__);
                    $log->addData($injunction_violation->errors, '$injunction_violation->errors:', __LINE__);
                    throw new Exception('Ошибка сохранения пункта нарушения документа InjunctionViolation');
                }
                unset($injunction_violation, $gravity, $probability);

                /******************** Обработки статусов предписаний нарушения ********************/
                // ЗАТЕМ ПИШЕМ СТАТУСЫ НАРУШЕНИЙ  если у нарушения нет статуса, то мы его создаем, если есть то проверяем на изменение и если изменеилось то пишем новый статус
                $inj_violation_statuses[$inj_violation_id]['injunction_violation_id'] = $inj_violation_id;
                $inj_violation_statuses[$inj_violation_id]['status_id'] = $pab_status_id;
                $inj_violation_statuses[$inj_violation_id]['date_time'] = $view_pab['DT_BEG_AUDIT'];
//                $inj_violation_status = InjunctionViolationStatus::find(['injunction_violation_id' => $inj_violation_id])->orderBy(['date_time' => SORT_DESC])->limit(1)->one();
//                if (!$inj_violation_status and $inj_violation_status['status_id'] != $pab_status_id) {
//                    $inj_violation_status = new InjunctionViolationStatus();
//                    $inj_violation_status->injunction_violation_id = $inj_violation_id;                                   // нарушения
//                    $inj_violation_status->status_id = $pab_status_id;                                         // текущий статус устранения нарушения
//                    $inj_violation_status->date_time = $dateNow;                                                        // дата на который статус является актуальным
//                    if (!$inj_violation_status->save()) {
//                        $errors[] = "$method_name. Не смог добавить или обновить запись InjunctionViolationStatus";
//                        $errors[] = $view_pab;
//                        $errors[] = $inj_violation_status->errors;
//                        throw new \Exception($method_name . '. Ошибка сохранения статуса нарушения InjunctionViolationStatus');
//                    }
//                }
//                unset($inj_violation_status);
                $violator_array[$inj_violation_id]['injunction_violation_id'] = $inj_violation_id;
                $violator_array[$inj_violation_id]['worker_id'] = (int)$view_pab['worker_violator_id'];
            }
            unset($place_pabs, $first_level_company_departments, $view_checking_pabs, $view_pab);

            $log->addLog('Выполнили перебор и закинули основные данные');

            /**
             * Сохранение нарушителей
             */
//            $gilty_violator_batch_insert=[];
//            foreach ($violator_array as $inj_viol_item) {
//                $gilty_violator_batch_insert[$inj_viol_item['worker_id']][$inj_viol_item['injunction_violation_id']]=$inj_viol_item;
//            }
//            unset($violator_array);
//            $violator_array=[];
//            foreach ($gilty_violator_batch_insert as $worker_id) {
//                foreach ($worker_id as $injunction_violation_id) {
//                    $violator_array[]=$injunction_violation_id;
//                }
//            }
//            unset($gilty_violator_batch_insert);

//            Yii::$app->db->createCommand('SET GLOBAL wait_timeout = 288000;')->execute();
            $gilty_violator_count = 0;
            foreach ($violator_array as $inj_viol_item) {
                $gilty_violator_batch_insert_array[] = $inj_viol_item;
                $gilty_violator_count++;

                if ($gilty_violator_count == 2000) {
                    $log->addLog("Начал вставку основной части нарушителей");

                    $insert_full = Yii::$app->db->createCommand()->batchInsert('violator', ['injunction_violation_id', 'worker_id'], $gilty_violator_batch_insert_array)->execute();

                    if ($insert_full === 0) {
                        $log->addLog('Записи в таблицу violator не добавлены');
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу violator");
                    }
                    $gilty_violator_count = 0;
                    unset($gilty_violator_batch_insert_array);
                    $gilty_violator_batch_insert_array = array();
                }
            }
            unset($violator_array);
            $log->addLog('Добавили основную часть нарушителей');


            if (!empty($gilty_violator_batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert('violator', ['injunction_violation_id', 'worker_id'], $gilty_violator_batch_insert_array)->execute();
                if ($insert_full === 0) {
                    $log->addLog('Записи в таблицу violator не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу violator");
                }
                unset($gilty_violator_batch_insert_array);
            }
            $gilty_violator_batch_insert_array = [];
            unset($inj_viol_item);
            unset($worker_item);
            unset($gilty_violator_batch_insert_item);

            $log->addLog('Добавили остаток нарушителей');

            /**
             * Сохранение мест
             */
            $place_count = 0;
            /** СОХРАНЕНИЕ МЕСТ ПРОВЕРОК МАССОВО **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            foreach ($place_array as $cheking_item) {
                foreach ($cheking_item as $place_item) {
                    $place_batch_insert_array[] = $place_item;

                    $place_count++;
                    if ($place_count == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_place', ['checking_id', 'place_id'], $place_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `place_id` = VALUES (`place_id`)')->execute();
                        if ($insert_full === 0) {
                            $log->addLog('Записи в таблицу checking_place не добавлены');
                        } else {
                            $log->addLog("добавил - $insert_full - записей в таблицу checking_place");
                        }
                        $place_count = 0;
                        unset($place_batch_insert_array);
                        $place_batch_insert_array = array();
                    }
                }
            }
            unset($place_array);

            $log->addLog('Добавили основную часть мест проверок');

            if (!empty($place_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_place', ['checking_id', 'place_id'], $place_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `place_id` = VALUES (`place_id`)')->execute();

                if ($insert_full === 0) {
                    $log->addLog('Записи в таблицу checking_place не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу checking_place");
                }
                unset($place_batch_insert_array);
            }
            $place_batch_insert_array = [];
            unset($cheking_item);
            unset($place_item);
            unset($place_batch_insert_item);

            $log->addLog('Добавили остаточные места проверок');

            /** СОХРАНЕНИЕ СТАУСОВ ПРЕДПИСАНИЙ МАССОВО **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $inj_status = 0;
            foreach ($injunction_status as $injunction_item) {
                foreach ($injunction_item as $worker_item) {
                    $inj_status_batch_insert_array[] = $worker_item;
                    $inj_status++;
                    if ($inj_status == 1000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_status_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_id` = VALUES (`injunction_id`), `worker_id` = VALUES (`worker_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                        if ($insert_full === 0) {
                            $log->addLog('Записи в таблицу injunction_status не добавлены');
                        } else {
                            $log->addLog("добавил - $insert_full - записей в таблицу injunction_status");
                        }
                        $inj_status = 0;
                        unset($inj_status_batch_insert_array);
                        $inj_status_batch_insert_array = array();
                    }
                }
            }
            unset($injunction_status);

            $log->addLog('Добавили основной массив статусов');


            unset($inj_status_batch_insert_item);
            unset($worker_item);
            unset($injunction_item);

//            $warnings[] = $inj_status_batch_insert_array;
            if (!empty($inj_status_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_status_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_id` = VALUES (`injunction_id`), `worker_id` = VALUES (`worker_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();

                if ($insert_full === 0) {
                    $log->addLog('Записи в таблицу injunction_status не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу injunction_status");
                }
                unset($inj_status_batch_insert_array);
            }

            $log->addLog('Добавили остсток статусов ПАБов');

            $inj_viol_status_count = 0;
            foreach ($inj_violation_statuses as $inj_violation_status) {
                $inj_viol_status_batch_insert_array[] = $inj_violation_status;
                $inj_viol_status_count++;
                if ($inj_viol_status_count == 2000) {
                    $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_violation_status', ['injunction_violation_id', 'status_id', 'date_time'], $inj_viol_status_batch_insert_array);
                    $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                    if ($insert_full === 0) {
                        $log->addLog('Записи в таблицу injunction_status не добавлены');
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу injunction_status");
                    }
                    $inj_viol_status_count = 0;
                    unset($inj_viol_status_batch_insert_array);
                    $inj_viol_status_batch_insert_array = array();
                }
            }
            unset($inj_violation_statuses);

            $log->addLog('Добавили основной массив статусов нарушений предписаний');


            if (!empty($inj_viol_status_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_violation_status', ['injunction_violation_id', 'status_id', 'date_time'], $inj_viol_status_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                if ($insert_full === 0) {
                    $log->addLog('Записи в таблицу injunction_violation_status не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу injunction_violation_status");
                }
                unset($inj_viol_status_batch_insert_array);
            }

            $log->addLog('Докуинули остатки статусов нарушений предписаний');


            /** Метод окончание */
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончание выполнения метода');
//        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * Метод ppkSyncNNMain() - Главный метод синхронизации Нарушений несоответствий
     * @param null $data_post
     * @return array
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (метод не требует входных параметров)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив выходных данных)
     *
     * АЛГОРИТМ:
     * 1. Вызвать метод синхронизации проверок НН
     *      статус 1?   заполнить массивы: ошибок, предупреждений, отладки
     *                  заполнить фильтр
     *                  заполнить максимальную дату синхронизации
     *      статус 0?   заполнить массивы: ошибок, предупреждений, отладки
     *                  вызвать исключение
     * 2. Выгрузить все проверки НН (идентификатор проверки, идентификатор предписания НН из промежуточной тоаблицы)
     * 3. Вызвать метод синхронизации работников участвующих в проверке НН
     *      статус 1?   заполнить массивы: ошибок, предупреждений, отладки, работников участвующих в проверке
     *                  заполнить фильтр
     *                  заполнить максимальную дату синхронизации
     *      статус 0?   заполнить массивы: ошибок, предупреждений, отладки
     *                  вызвать исключение
     * 4. Вызвать метод синхронизации предписаний НН (раскидываение по таблицам)
     *      статус 1?   заполнить массивы: ошибок, предупреждений, отладки
     *      статус 0?   заполнить массивы: ошибок, предупреждений, отладки
     *                  вызвать исключение
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.04.2020 8:23
     */
    public static function ppkSyncNNMain($data_post = NULL)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSyncNNMain");

        try {
            $log->addLog("Начало выполнения метода");

//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $response = self::ppkSyncCheckingNN();
            if ($response['status'] != 1) {
                $log->addLogAll($response);
//                throw new Exception('Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }
            $filterChecking = $response['filterChecking'];
            $maxDateDocument = $response['maxDateDocument'];
            unset($response);
//            /**
//             * Пришёл пустой фильтр
//             *   да?    меняем тип переменной на массив чтобы не участвовало в выборке andFilterWhere(так как принимает пустыми только массив)
//             *   нет?   Пропустить
//             */
//            if ($filterChecking == '()' || empty($filterChecking)) {
//                $filterChecking = array();
//            }
            $checkingSapNN = Checking::find()
                ->select(['id', 'nn_id', 'date_time_sync_nn'])
                ->where(['is not', 'nn_id', null])
//                ->andFilterWhere($filterChecking)
                ->asArray()
                ->indexBy('nn_id')
                ->all();
            $response = self::ppkSyncCheckingWorkerNN($checkingSapNN, $maxDateDocument);
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }
            $filterChecking = $response['filterChecking'];
            $maxDateDocument = $response['maxDateDocument'];
            $violatorChecking = $response['violatorChecking'];
            unset($response);

            $response = self::ppkSyncCheckingInjunctionNN($checkingSapNN, $violatorChecking);
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка выполнения синхронизации главного таблицы проверок Checking');
            }
            unset($response);
        } catch (\Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => []], $log->getLogAll());
    }

    /**
     * Метод ppkSyncCheckingNN() - Синхронизация проверок нарушений несоответствий
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "Items":                    // фильтр
     *      "filterChecking":            // фильтр для поиска
     *      "maxDateDocument":            // максимальная дата обработки документа
     *      "errors":[]                    // массив ошибок
     *      "status":1                    // статус выполнения метода
     *      "warnings":[]                // массив предупреждений
     *      "debug":[]                    // массив для отладки
     * }
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     * АЛГОРИТМ РАБОТЫ МЕТОДА:
     * 1. Получить данные из представления
     * 2. Получить данные из таблицы связки мест и учасков индексируя по идентификатор места
     * 3. Перебор данных представления
     *      3.1. Есть ответственный?
     *              да?     Берём его участок
     *              нет?    Получаем идентификатор места по наименованию из базового контроллера
     *                      В связке мест участков есть такое место?
     *                          да?     Взять идентификатор участка
     *                          нет?    Идентификатор участка по умолчанию (Прочее = 101)
     *      3.2 Проверить наличие проверки по PLACE_PAB
     *              есть?   Изменить проверку: company_department_id, date_time_sync_nn
     *              нету?   Проверить наличие проверки по данным в массиве на добавление проверок
     *                          нету?       добавить в массив на добавление проверок
     *                                      увеличить счётчик на добавление
     *                          есть?       Пропустить
     *      3.3 Счётчик на добавление больше либо равен 2000?
     *              да?     Добавить массив проверок
     *                      Очистить массив и счётчик
     *              нет?    Пропустить
     * 4. Конец перебора
     * 5. Записать результат логов
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.03.2020 11:27
     */
    public static function ppkSyncCheckingNN()
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSyncCheckingNN");

        $count_all = null;                                                                                           // количество вставленных записей
        $filterChecking = array();                                                   // фильтр для выборки данных для синхронизации
        $maxDateDocument1 = "";
        $checkings_array = array();
        $count_add = 0;
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            $log->addLog("Начало выполнения метода");

            /** Метод начало */

            $filterCheckingSap = null;                                                   // фильтр для выборки данных для синхронизации
            $fieldArrayChecking = array('title', 'date_time_start', 'date_time_end', 'checking_type_id', 'company_department_id', 'nn_id', 'date_time_sync_nn');
            $table_insertChecking = 'checking';
            $table_source = 'view_checking_nn';
            // находим последнюю дату синхронизации по справочнику
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_nn)')
                ->from($table_insertChecking)
                ->scalar();

            $log->addLog(" максимальная дата для обработки записи" . $maxDateDocument1);

            if ($maxDateDocument1) {
                $filterChecking = array('>', 'date_time_sync_nn', $maxDateDocument1);
                $filterCheckingSap = "DATE_MODIFIED > '" . $maxDateDocument1 . "'";
            }

            // получаем справочник проверок
            $view_checkings = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checkings) {
                throw new Exception('Справочник для синхронизации пуст');
            }

            $log->addLog("Получили второй уровень участка по участкам нарушителей");

            /**
             * Получаем все места по участкам
             */
            $injunction_controller = new InjunctionController(1, false);
            $place_company_department = PlaceCompanyDepartment::find()
                ->select(['company_department_id', 'place_id'])
                ->indexBy('place_id')
                ->asArray()
                ->all();
            foreach ($view_checkings as $view_checking) {
                $count_all++;
                /**
                 * АЛГОРИТМ ПОЛУЧЕНИЯ УЧАСТКА:
                 * 1. Есть ответственный?
                 *      да?     Берём его участок
                 *      нет?    Получаем идентификатор места по наименованию из базового контроллера
                 *              В связке мест участков есть такое место?
                 *                  да?     Взять идентификатор участка
                 *                  нет?    Идентификатор участка по умолчанию (Прочее = 101)
                 */
                if (empty($view_checking['company_department_id'])) {
                    $response = $injunction_controller::GetPlaceByTitle($view_checking['PLACE_TITLE']);
                    if ($response['status'] != 1) {
                        $log->addLogAll($response);
                        throw new Exception('Ошибка при получении идентификатора места');
                    }
                    $place_id = $response['Items'];
                    unset($response);
                    if (isset($place_company_department[$place_id]) && !empty($place_company_department[$place_id])) {
                        $company_department_id = $place_company_department[$place_id]['company_department_id'];
                    } else {
                        $company_department_id = 101;                                                               //идентификатор участка "Прочее"
                    }
                } else {
                    $company_department_id = $view_checking['company_department_id'];
                }
                /**
                 * Проверяем наличие такой проверки в таблице
                 */
                $checking = Checking::findOne(['nn_id' => $view_checking['PLACE_PAB']]);
                if (!empty($checking)) {
                    /**
                     * Если найдена то изменяем
                     */
                    $checking->company_department_id = $company_department_id;
                    $checking->date_time_sync_nn = $view_checking['DATE_MODIFIED'];
                    if (!$checking->save()) {
                        $log->addData($checking->errors, '$checking->errors', __LINE__);
                        throw new Exception('Ошибка при редактировании проверки');
                    }
                } else {
                    $batch_insert_item['title'] = 'Нарушение несоответствие от ' . $view_checking['DT_BEG_AUDIT'];
                    $batch_insert_item['date_time_start'] = $view_checking['DT_BEG_AUDIT'];
                    $batch_insert_item['date_time_end'] = $view_checking['DATE_END'];
                    $batch_insert_item['checking_type_id'] = 1;
                    $batch_insert_item['company_department_id'] = $company_department_id;
                    $batch_insert_item['nn_id'] = $view_checking['PLACE_PAB'];
                    $batch_insert_item['date_time_sync_nn'] = $view_checking['DATE_MODIFIED'];
                    $batch_insert_array[] = $batch_insert_item;
                    unset($batch_insert_item);
                    $count_add++;
                }
                unset($checking);
                if ($count_add == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                    if ($insert_full === 0) {
                        throw new Exception('Записи в таблицу checking не добавлены');
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу checking");
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }
            unset($view_checkings, $view_checking);

            $log->addLog("Законичил пребор и втсавили большую часть проверок");

            if (isset($batch_insert_array) && !empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fieldArrayChecking, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception('Записи в таблицу checking не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу checking");
                }
                unset($batch_insert_array);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => [], 'filterChecking' => $filterChecking, 'maxDateDocument' => $maxDateDocument1], $log->getLogAll());
    }

    /**
     * Метод ppkSyncCheckingWorkerNN() - Синхронизация типов работников нарушения несоответствия
     * @param $checkingSapNN - массив проверок инедксированный по PLACE_PAB (nn_id)
     * @param $maxDateDocument - максимальная дата обработки записи
     * @return array
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * checkingSapNN - массив проверок инедксированный по PLACE_PAB (nn_id)
     * maxDateDocument - максимальная дата обработки записи
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     *  {
     *      "Items":1,
     *      "auditorChecking":{}                // массив аудиторов на проверке
     *      "errors":{}                            // массив ошибок
     *      "status":{}                            // статус выполнения метода
     *      "warnings":{}                        // массив предупреждений (ход выполнения метода)
     *      "debug":{}                            // массив отладки
     *  }
     *
     * @package backend\controllers\serviceamicum
     * АЛГОРИТМ РАБОТЫ МЕТОДА:
     * 1. Выгрузить данные из представления
     * 2. перебрать данные из представления сделав массив вида:
     *                                                          [PLACE_PAB]
     *                                                              [auditors]
     *                                                              [responsibles]
     *
     * 3. Перебор данных из представления
     *          3.1 Получить идентификатор проверки и дату синхронизации по PLACE_PAB
     *          3.2 Идентификатор аудитора пуст
     *                  да?     Пропустить
     *                  нет?    Аудитор есть в подготовленном массиве (2 пункт)
     *                                  да?     Пропустить
     *                                  нет?    Добавить в массив данные на добавление - аудитора
     *                                          Увеличить счётчик элементов на добавление
     *          3.3 Идентификатор ответственного пуст
     *                  да?     Пропустить
     *                  нет?    Ответственнный есть в подготовленном массиве (2 пункт)
     *                                  да?     Пропустить
     *                                  нет?    Добавить в массив данные на добавление - ответственного
     *                                          Увеличить счётчик элементов на добавление
     *          3.4 Счётчик элементов на добавление больше либо равено 2 000
     *                  да?     Добавить массив сформированных данных в таблицу checking_worker_type
     *                          Очистить массив на добавление
     *                          Обнулить счётчик количества жоементов на добавление
     *                  нет?    Пропустить
     * 4. Конец перебора
     * 5. Массив на добавление не пуст
     *      да?     Добавить данные в таблицу checking_worekr_type
     *      нет?    Пропустить
     * 6. Записть окончание метода в логи
     *
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.03.2020 14:03
     */
    public static function ppkSyncCheckingWorkerNN($checkingSapNN, $maxDateDocument)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $count_all = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkSyncCheckingWorkerNN");

        $filterChecking = null;
        $maxDateDocument1 = null;
        $count_add = 0;
        $violatorChecking = array();

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнения метода");

            /** Метод начало */
            $filterChecking = null;
            $filterCheckingSap = null;
            $fields = ['worker_id', 'worker_type_id', 'checking_id', 'instruct_nn_id', 'date_time_sync_nn'];
            $table_insertChecking = 'checking_worker_type';
            $table_source = 'view_checking_worker_nn';

            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_nn)')
                ->from($table_insertChecking)
                ->scalar();

            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync_nn >'" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_MODIFIED > '" . $maxDateDocument1 . "'";
            }

            $log->addLog("Максимальная дата обработки документа: $maxDateDocument1");

            $view_checking_worker = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checking_worker) {
                throw new Exception('данных для синхронизации нет');
            }

            $log->addLog("Выгрузили данные представления");

            foreach ($view_checking_worker as $item) {
                $worker_types[$item['PLACE_PAB']]['auditors'] = array();
                $worker_types[$item['PLACE_PAB']]['responsibles'] = array();
            }

            foreach ($view_checking_worker as $checking_worker) {
                $count_all++;
                $checking_id = $checkingSapNN[$checking_worker['PLACE_PAB']]['id'];
                $date_sync_nn = $checkingSapNN[$checking_worker['PLACE_PAB']]['date_time_sync_nn'];
                $auditor_id = $checking_worker['worker_auditor_id'];
                $violatorChecking[$checking_id]['worker_id'] = $auditor_id;
                $violatorChecking[$checking_id]['checking_id'] = $checking_id;
                /******************** Аудитор ********************/
                if (!empty($checking_worker['worker_auditor_id'])) {
                    if (!isset($worker_types[$checking_worker['PLACE_PAB']]['auditors'][$checking_worker['worker_auditor_id']])) {
                        $worker_types[$checking_worker['PLACE_PAB']]['auditors'][$checking_worker['worker_auditor_id']]['worker_id'] = $checking_worker['worker_auditor_id'];
                        $checkingWorkerAuditor = CheckingWorkerType::findOne(['instruct_nn_id' => $checking_worker['worker_auditor_id'], 'checking_id' => $checking_id]);
                        $auditor_id = $checking_worker['worker_auditor_id'];
                        if (!$checkingWorkerAuditor) {
                            $batch_insert_item['worker_id'] = $auditor_id;
                            $batch_insert_item['worker_type_id'] = 1;
                            $batch_insert_item['checking_id'] = $checking_id;
                            $batch_insert_item['instruct_nn_id'] = $auditor_id;
                            $batch_insert_item['date_time_sync_nn'] = $date_sync_nn;
                            $batch_insert_array[] = $batch_insert_item;
                            unset($batch_insert_item);
                            $count_add++;
                        } else {
                            $checkingWorkerAuditor->worker_id = $checking_worker['worker_auditor_id'];
                            $checkingWorkerAuditor->instruct_nn_id = $auditor_id;
                            $checkingWorkerAuditor->date_time_sync_nn = $checking_worker['DATE_MODIFIED'];
                            if (!$checkingWorkerAuditor->save()) {
                                $log->addData($checking_worker, '$checking_worker', __LINE__);
                                $log->addData($checkingWorkerAuditor->errors, '$checkingWorkerAuditor->errors', __LINE__);
                                throw new Exception('Не смог обновить запись checkingWorker');
                            }
                        }
                        unset($checkingWorkerAuditor);
                    }
                }
                /******************** Ответственный ********************/
                if (!empty($checking_worker['worker_responsible'])) {
                    if (!isset($worker_types[$checking_worker['PLACE_PAB']]['responsibles'][$checking_worker['worker_responsible']])) {
                        $worker_types[$checking_worker['PLACE_PAB']]['responsibles'][$checking_worker['worker_responsible']]['worker_id'] = $checking_worker['worker_responsible'];
                        $checkingWorkerResposnible = CheckingWorkerType::findOne(['instruct_nn_id' => $checking_worker['worker_responsible'], 'date_time_sync_nn' => $date_sync_nn]);
                        $responsible_id = $checking_worker['worker_responsible'];
                        if (!$checkingWorkerResposnible) {
                            $batch_insert_item['worker_id'] = $responsible_id;
                            $batch_insert_item['worker_type_id'] = 2;
                            $batch_insert_item['checking_id'] = $checking_id;
                            $batch_insert_item['instruct_nn_id'] = $responsible_id;
                            $batch_insert_item['date_time_sync_nn'] = $date_sync_nn;
                            $batch_insert_array[] = $batch_insert_item;
                            unset($batch_insert_item);
                            $count_add++;
                        } else {
                            $checkingWorkerResposnible->worker_id = $checking_worker['worker_responsible'];
                            $checkingWorkerResposnible->instruct_nn_id = $responsible_id;
                            $checkingWorkerResposnible->date_time_sync_nn = $checking_worker['DATE_MODIFIED'];
                            if (!$checkingWorkerResposnible->save()) {
                                $log->addData($checking_worker, '$checking_worker', __LINE__);
                                $log->addData($checkingWorkerResposnible->errors, '$checkingWorkerResposnible->errors', __LINE__);
                                throw new Exception('Не смог обновить запись checkingWorker');
                            }
                        }
                        unset($checkingWorkerResposnible);
                    }
                }

                if ($count_add >= 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fields, $batch_insert_array)->execute();
                    if ($insert_full === 0) {
                        throw new Exception("Записи в таблицу ' . $table_insertChecking . ' не добавлены");
                    } else {
                        $log->addLog("добавил - $insert_full - записей в таблицу $table_insertChecking");
                    }
                    unset($batch_insert_array);
                    $count_add = 0;
                }
            }
            unset($view_checking_worker, $worker_types);

            $log->addLog("Выполнили перебор и закинули основной массив данных");

            if (isset($batch_insert_array) && !empty($batch_insert_array)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_insertChecking, $fields, $batch_insert_array)->execute();
                if ($insert_full === 0) {
                    throw new Exception('Записи в таблицу ' . $table_insertChecking . ' не добавлены');
                } else {
                    $log->addLog("добавил - $insert_full - записей в таблицу $table_insertChecking");
                }
                unset($batch_insert_array);
            }

            $log->addLog("Докинули остальные данные массива типов работников");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => [], 'filterChecking' => $filterChecking, 'maxDateDocument' => $maxDateDocument1, 'violatorChecking' => $violatorChecking], $log->getLogAll());
    }

    /**
     * Метод ppkSyncCheckingInjunctionNN() - Синхронизация нарушений несоответствий (расскидывает данные по таблицам)
     * @param $checkingSapNN - массив проверок синдексированный по PLACE_PAB
     * @param $violatorChecking - массив аудиторов
     * @return array
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * checkingSapNN - массив проверок синдексированный по PLACE_PAB
     * violatorChecking - массив аудиторов
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     *  {
     *      "Items":1,
     *      "auditorChecking":{}                // массив аудиторов на проверке
     *      "errors":{}                            // массив ошибок
     *      "status":{}                            // статус выполнения метода
     *      "warnings":                            // массив предупреждений (ход выполнения метода)
     *      "debug":{}                            // массив отладки
     *  }
     *
     * @package backend\controllers\serviceamicum
     *
     * АЛГОРИТМ РАБОТЫ МЕТОДА:
     * 1. получить максимальную дату обработки записи (дату и время последней синхронизации) таблицы injunction
     * 2. По полученной дате выгрузить данные из представления
     * 3. Выгрузить данные связки места и участка place_company_department, индексируя по идентификатору места
     * 4. Перебор полученных данных
     *      4.1  Получаем идентификатор места по наименованию (если небыло создать)
     *      4.2  Идентификатор участка ответственного пуст?
     *              да?             Поиск участка по идентификатору места
     *                                  найдено?        Взять идентификатор учатка
     *                                  не найдено?     Взять идентификатор участка по умолчанию (101 - Прочее)
     *              нет?            Взять идентификатор участка ответственного
     *      4.3  Взять идентификатор проверки по PLACE_PAB
     *      4.4  Добавить место в массив связки мест проверок
     *      4.5  Найти предписание по PLACE_PAB
     *              найдено?        Поиск предписания по: checking_id, place_id, observation_number, kind_document_id, worker_id
     *                                          найдено?        Равны ли nn_id
     *                                                                  да?     Взять идентификатор человека найденной записи
     *                                                                          Флаг: изменить, observation_number = null
     *                                                                  нет?    Флаг: изменить, observation_number взять у найденой записи
     *                                          не найдено?     Флаг: изменить, observation_number = null
     *              не найдено?     Поиск предписания по: checking_id, place_id, observation_number, kind_document_id, worker_id
     *                                          найдено?        Флаг: изменить
     *                                          не найдено?     Флаг: добавить
     *      4.6  Обновить или добавить предписание в зависимости от флага
     *      4.7  Добавить в массив для записи статусов
     *      4.8  Получить идентификатр нарушения по наименованию
     *              найдено?        Взять идентификатор нарушения
     *              не найдено?     Не пустой ли идентификатор направления нарушения
     *                                        не пустой?    ищем вид нарушения по REF_ERROR_DIRECTION_ID
     *                                                        нашли?        ищем тип нарушения
     *                                                                        нашли?        берём идентификатор типа нарушения
     *                                                                        не нашли?    создаём новый тип нарушения
     *                                                        не нашли?    ищем тип нарушения
     *                                                                        нашли?        берём идентификатор типа нарушения
     *                                                                        не нашли?    значение по умолчанию
     *                                        пустой?    значение по умолчанию
     *      4.9  Наименование документа пусто?
     *              да?             взять идентификатор документа по умолчанию (20000)
     *              нет?            Передакть наименование в функцию для получения идентификатора документа
     *      4.10 Наименование пункта документа пусто?
     *              да?             Взять идентификатор пункта документа по умолчанию (347)
     *              нет?            Передать наименование пункта документа в фукнцию для получение идентификатора пункта документа
     *      4.11 Проверяем пустая ли FAILURE_NAME (степень тяжести)
     *              да?             заполняем вероятность и тяжесть по умолчанию (вероятность = 1, тяжесть = 1)
     *              нет?            Низкая = вероятность = 1, тяжесть = 1
     *                              Средняя = вероятность = 3, тяжесть = 3
     *                              Высокая = вероятность = 5, тяжесть = 5
     *      4.12 Ищем предписание нарушения по PLACE_PAB
     *              найдено?        Изменяем нарушение предписания
     *              не найдено?     создаём новое нарушение предписания
     *      4.13 Добавляем в массив статусов нарушений предписаний
     * 5. Конце перебора
     * 6. Массово добавить: Статусы предписаний, связку мест проверок, статусы нарушений предписаний
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 03.03.2020 8:49
     */
    public static function ppkSyncCheckingInjunctionNN($checkingSapNN, $violatorChecking)
    {
        // Стартовая отладочная информация
        $method_name = 'ppkSyncCheckingInjunctionNN';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                           // количество вставленных записей
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
        $count_add = 0;
        $injunction_worker_id = null;

        try {
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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

            /** Метод начало */
            $filterChecking = null;
            $filterCheckingSap = null;
            $fields = array('place_id', 'worker_id', 'kind_document_id', 'rtn_statistic_status_id', 'checking_id', 'description', 'status_id', 'observation_number', 'company_department_id', 'instruct_nn_id', 'date_time_sync_nn');
            $table_insertChecking = 'injunction';
            $table_source = 'view_checking_injunction_nn';
            $maxDateDocument1 = (new Query())
                ->select('max(date_time_sync_nn)')
                ->from($table_insertChecking)
                ->scalar();
            $warnings[] = "$method_name. максимальная дата для обработки записи" . $maxDateDocument1;
            if ($maxDateDocument1) {
                $filterChecking = "date_time_sync_nn > '" . $maxDateDocument1 . "'";
                $filterCheckingSap = "DATE_MODIFIED > '" . $maxDateDocument1 . "'";
            }
            $warnings[] = $method_name . '. Максимальная дата обработки записи: ' . $maxDateDocument1;

            $view_checking_nns = (new Query())
                ->select('*')
                ->from($table_source)
                ->where($filterCheckingSap)
                ->all();
            if (!$view_checking_nns) {
                throw new Exception($method_name . '. данных для синхронизации нет');
            }
            $injunction_controller = new InjunctionController(1, false);
            /** Отладка */
            $description = 'Выгрузили НН';                                                                      // описание текущей отладочной точки
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
            $kind_violations = KindViolation::find()
                ->select('id,title,ref_error_direction_id,date_time_sync')
                ->indexBy('ref_error_direction_id')
                ->asArray()
                ->all();

            $violation_types = ViolationType::find()
                ->select('id,ref_error_direction_id')
                ->indexBy('ref_error_direction_id')
                ->asArray()
                ->all();

            $place_company_department = PlaceCompanyDepartment::find()
                ->select(['company_department_id', 'place_id'])
                ->indexBy('place_id')
                ->asArray()
                ->all();
            foreach ($view_checking_nns as $view_nn) {
                $count_all++;
                /******************** Обрабатываем место ********************/
                $response = $injunction_controller::GetPlaceByTitle($view_nn['PLACE_TITLE']);
                if ($response['status'] == 1) {
                    $place_id = $response['Items'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception($method_name . '. Ошибка при получении идентификатора места');
                }
                unset($response);
                /**
                 * Получаем участок:
                 *  Идентификатор ответственного пустой:
                 *      да?     Найти  участок по месту
                 *                  найдено?        Взять идентификатор участка
                 *                  не найдено?     Идентификатор участка по умолчанию
                 *      нет?    Взять идентификатор участка ответственного
                 */
                if (!empty($view_nn['responsible_company_department'])) {
                    $company_department_id = $view_nn['responsible_company_department'];
                } else {
                    if (isset($place_company_department[$place_id]) && !empty($place_company_department[$place_id])) {
                        $company_department_id = $place_company_department[$place_id]['company_department_id'];
                    } else {
                        $company_department_id = 101; // идентификатор участка по умолчанию
                    }
                }
                $checking_id = $checkingSapNN[$view_nn['PLACE_PAB']]['id'];                                         // Получиаем идентификатор проверки по PLACE_PAB
                if (isset($violatorChecking[$checking_id]['worker_id']) && !empty($violatorChecking[$checking_id]['worker_id'])) {
                    $worker_id = $violatorChecking[$checking_id]['worker_id'];                                          // получаем идентификатор работника
                } else {
                    $worker_id = 1;
                }

                /**
                 * Добавляем связку места и проверки
                 */
                $del_checking_place = CheckingPlace::deleteAll(['checking_id' => $checking_id]);
                unset($del_checking_place);
                $place_array[$checking_id][$place_id]['place_id'] = $place_id;
                $place_array[$checking_id][$place_id]['checking_id'] = $checking_id;

                $change = false;
                $observation_number = null;
                $injunction_worker_id = $worker_id;
                $violatoion_disconformity = Injunction::findOne(['instruct_nn_id' => $view_nn['PLACE_PAB']]);
                if (empty($violatoion_disconformity)) {
                    $violatoion_disconformity = Injunction::findOne([
                        'place_id' => $place_id,
                        'worker_id' => $worker_id,
                        'kind_document_id' => 4,
                        'checking_id' => $checking_id,
                        'observation_number' => 0
                    ]);
                    if (empty($violatoion_disconformity)) {
                        $violatoion_disconformity = Injunction::findOne([
                            'place_id' => $place_id,
                            'kind_document_id' => 4,
                            'checking_id' => $checking_id,
                            'observation_number' => 0
                        ]);
                        if (empty($violatoion_disconformity)) {
                            $change = false;
                        } else {
                            $change = true;
                            $injunction_worker_id = $worker_id;
                        }
                    } else {
                        $change = true;
                        $injunction_worker_id = $worker_id;
                    }
                } else {
                    $another_injunction = Injunction::find()
                        ->where([
                            'place_id' => $place_id,
                            'worker_id' => $worker_id,
                            'kind_document_id' => 4,
                            'checking_id' => $checking_id,
                        ])
                        ->orderBy('observation_number desc')
                        ->limit(1)
                        ->one();
                    if (empty($another_injunction)) {
                        $change = true;
                    } else {
                        $another_injunction_instruct_nn_id = $another_injunction->instruct_nn_id;
                        if ($violatoion_disconformity->instruct_nn_id == $another_injunction_instruct_nn_id) {
                            $injunction_worker_id = $another_injunction->worker_id;
                            $observation_number = null;
                            $change = true;
                        } else {
                            $change = true;
                            $observation_number = $another_injunction->observation_number;
                            $injunction_worker_id = $worker_id;
                        }
                        unset($another_injunction);
                    }
                }
                if (empty($observation_number)) {
                    $observation_number = 0;
                } else {
                    $observation_number++;
                }
                if (!$change) {
                    $violatoion_disconformity = new Injunction();
                    $violatoion_disconformity->place_id = $place_id;
                    $violatoion_disconformity->kind_document_id = 4;
                    $violatoion_disconformity->rtn_statistic_status_id = 56;
                    $violatoion_disconformity->description = null;
                    $violatoion_disconformity->checking_id = $checking_id;
                    $violatoion_disconformity->status_id = 59;
                    $violatoion_disconformity->observation_number = $observation_number;
                    $violatoion_disconformity->company_department_id = $company_department_id;
                }
                $violatoion_disconformity->instruct_nn_id = $view_nn['PLACE_PAB'];
                if (empty($injunction_worker_id)) {
                    $injunction_worker_id = $worker_id;
                }
                $violatoion_disconformity->worker_id = $injunction_worker_id;
                $violatoion_disconformity->date_time_sync_nn = $view_nn['DATE_MODIFIED'];
                if (!$violatoion_disconformity->save()) {
                    $errors[] = $violatoion_disconformity->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении/редактировании нарушения несоответствия');
                }
                $violatoion_disconformity->refresh();
                $injunction_id = $violatoion_disconformity->id;
                $injunction_status_id = $violatoion_disconformity->status_id;
                unset($violatoion_disconformity, $change, $observation_number, $injunction_worker_id);
                /**
                 * Записваем статусы
                 */
                $injunction_status[$injunction_id][$worker_id]['injunction_id'] = $injunction_id;
                $injunction_status[$injunction_id][$worker_id]['worker_id'] = $worker_id;
                $injunction_status[$injunction_id][$worker_id]['status_id'] = $injunction_status_id;
                $injunction_status[$injunction_id][$worker_id]['date_time'] = $view_nn['DT_BEG_AUDIT'];
                /******************** Обработка нарушения ********************/
                // описание нарушения может быть больше 1000 символов потому мы его обрезаем
                $str_len_violation = strlen($view_nn['DAN_EFFECT_IN_RDE']);                                            // вычисляем длину нарушения

                if ($str_len_violation > 1000) {
                    $violation_saving_title = mb_substr($view_nn['DAN_EFFECT_IN_RDE'], 0, 1000);                       // если длина больше 1000 символов, то мы ее обрезаем
                } else {
                    $violation_saving_title = $view_nn['DAN_EFFECT_IN_RDE'];                                           // иначе оставляем так как есть
                }

                unset($str_len_violation);

                $violation_saving_title = trim($violation_saving_title);                                                //убираем пробелы с начала и конца строки для того, что бы обеспечить гарантированный поиск совпадения

                $violation = Violation::find()->where(['title' => $violation_saving_title])->limit(1)->one();
                if (!$violation) {

                    $violation = new Violation();

                    if (!empty($violation_saving_title)) {
                        $violation->title = $violation_saving_title;
                        unset($violation_saving_title);
                        /**
                         *  Не пустой ли идентификатор направления нарушения
                         *    не пустой?    ищем вид нарушения по REF_ERROR_DIRECTION_ID
                         *                    нашли?        ищем тип нарушения
                         *                                    нашли?        берём идентификатор типа нарушения
                         *                                    не нашли?    создаём новый тип нарушения
                         *                    не нашли?    ищем тип нарушения
                         *                                    нашли?        берём идентификатор типа нарушения
                         *                                    не нашли?    значение по умолчанию
                         *    пустой?    значение по умолчанию
                         */
                        if ($view_nn['REF_ERROR_DIRECTION_ID']) {
                            if (isset($kind_violations[$view_nn['REF_ERROR_DIRECTION_ID']]['id']) && !empty($kind_violations[$view_nn['REF_ERROR_DIRECTION_ID']]['id'])) {
                                if (isset($violation_types[$view_nn['REF_ERROR_DIRECTION_ID']]['id']) && !empty($violation_types[$view_nn['REF_ERROR_DIRECTION_ID']]['id'])) {
                                    $violation_type_for_violation = $violation_types[$view_nn['REF_ERROR_DIRECTION_ID']]['id'];
                                } else {
                                    $json = json_encode(['kind_violation_id' => $kind_violations[$view_nn['REF_ERROR_DIRECTION_ID']]['id'],
                                        'kind_violation_title' => $kind_violations[$view_nn['REF_ERROR_DIRECTION_ID']]['title'],
                                        'ref_error_direction_id' => $view_nn['REF_ERROR_DIRECTION_ID'],
                                        'date_time_sync' => $kind_violations[$view_nn['REF_ERROR_DIRECTION_ID']]['date_time_sync']]);
                                    $response = $injunction_controller::ViolationType($json);
                                    if ($response['status'] == 1) {
                                        $violation_type_for_violation = $response['Items'];
                                    } else {
                                        $warnings[] = $response['warnings'];
                                        $errors[] = $response['errors'];
                                        throw new Exception($method_name . '. Ошибка при сохранении типа нарушения');
                                    }
                                    unset($response, $json);
                                }
                            } else {
                                if (isset($violation_types[$view_nn['REF_ERROR_DIRECTION_ID']]['id']) && !empty($violation_types[$view_nn['REF_ERROR_DIRECTION_ID']]['id'])) {
                                    $violation_type_for_violation = $violation_types[$view_nn['REF_ERROR_DIRECTION_ID']]['id'];
                                } else {
                                    $violation_type_for_violation = 128;
                                }
                            }
                            $violation->violation_type_id = $violation_type_for_violation;
                        } else {
                            $violation->violation_type_id = 128;
                        }
                        if ($violation->save()) {
                            $violation->refresh();
                            $violation_id = $violation->id;
                        } else {
                            $errors[] = $view_nn['REF_ERROR_DIRECTION_ID'];
                            $errors[] = $violation_type_for_violation;
                            $errors[] = $violation->errors;
                            throw new Exception($method_name . '. Ошибка сохранения Описания нарушения Violation');
                        }
                    } else {
                        $violation_id = 1;
                    }
                } else {
                    $violation_id = $violation->id;
                }
                unset($violation, $violation_type_for_violation);

                /******************** Обработка документа ********************/
                if (empty($view_nn['DOC_LINK'])) {
                    $document_id = 20000;
                } else {
                    $json = json_encode(['document_title' => $view_nn['DOC_LINK'], 'worker_id' => $worker_id]);
                    $response = $injunction_controller::GetDocumentByTitle($json);
                    if ($response['status'] == 1) {
                        $document_id = $response['Items'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception($method_name . '. Ошибка при получении/создании документа');
                    }
                    unset($response, $json);
                }

                /******************** Обработка пункта документа ********************/
                if (empty($view_nn['ERROR_POINT'])) {
                    $paragraph_pb_id = 347;
                } else {
                    $json = json_encode(['paragraph_pb_title' => $view_nn['ERROR_POINT'], 'document_id' => $document_id]);
                    $response = $injunction_controller::GetParagraphPbByText($json);
                    if ($response['status'] == 1) {
                        $paragraph_pb_id = $response['Items'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception($method_name . '. Ошибка при получении/создании пункта документа');
                    }
                    unset($response, $json);
                }
                /******************** Обработки степени тяжести ********************/
                if (!empty($view_nn['FAILURE_NAME'])) {
                    if ($view_nn['FAILURE_NAME'] == 'низкая') {
                        $probability = 1;
                        $gravity = 1;
                    } elseif ($view_nn['FAILURE_NAME'] == 'средняя') {
                        $probability = 3;
                        $gravity = 3;
                    } else {
                        $probability = 5;
                        $gravity = 5;
                    }
                } else {
                    $probability = 1;
                    $gravity = 1;
                }
                /******************** Добавляем/Изменяем нарушение предписания ********************/
                $injunction_violation = InjunctionViolation::findOne(['instruct_nn_id' => $view_nn['PLACE_PAB'], 'injunction_id' => $injunction_id]);
                if (empty($injunction_violation)) {
                    $injunction_violation = new InjunctionViolation();
                }
                $injunction_violation->injunction_id = $injunction_id;
                $injunction_violation->place_id = $place_id;                                                               // ключ места в котором выписано предписание
                $injunction_violation->violation_id = $violation_id;
                $injunction_violation->paragraph_pb_id = $paragraph_pb_id;                                                 // ключ параграфа нормативного документа
                $injunction_violation->document_id = $document_id;                                                         // ключ нормативного документа, требования которого были нарушены
                $injunction_violation->probability = $probability;
                $injunction_violation->gravity = $gravity;
                $injunction_violation->instruct_nn_id = $view_nn['PLACE_PAB'];
                $injunction_violation->date_time_sync_nn = $view_nn['DATE_MODIFIED'];
                if ($injunction_violation->save()) {
                    $injunction_violation->refresh();
                    $inj_violation_id = $injunction_violation->id;
                } else {
                    $errors[] = "$method_name. Не смог добавить или обновить запись InjunctionViolation";
                    $errors[] = $view_nn;
                    $errors[] = $injunction_violation->errors;
                    throw new Exception($method_name . '. Ошибка сохранения пункта нарушения документа InjunctionViolation');
                }
                unset($injunction_violation, $gravity, $probability);

                /******************** Обработка статусов предписаний нарушения ********************/
                $inj_violation_statuses[$inj_violation_id]['injunction_violation_id'] = $inj_violation_id;
                $inj_violation_statuses[$inj_violation_id]['status_id'] = $injunction_status_id;
                $inj_violation_statuses[$inj_violation_id]['date_time'] = $view_nn['DT_BEG_AUDIT'];
            }
            unset($view_checking_nns, $view_nn, $place_company_department, $violation_types, $kind_violations);


            /** Отладка */
            $description = 'Выполнили пербор и закинули основную часть данных';                                                                      // описание текущей отладочной точки
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

            $place_count = 0;
            /** СОХРАНЕНИЕ МЕСТ ПРОВЕРОК МАССОВО **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            foreach ($place_array as $cheking_item) {
                foreach ($cheking_item as $place_item) {
                    $place_batch_insert_item['checking_id'] = $place_item['checking_id'];
                    $place_batch_insert_item['place_id'] = $place_item['place_id'];
                    $place_batch_insert_array[] = $place_batch_insert_item;

                    $place_count++;
                    if ($place_count == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_place', ['checking_id', 'place_id'], $place_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `place_id` = VALUES (`place_id`)')->execute();
                        if ($insert_full === 0) {
                            $warnings[] = $method_name . '. Записи в таблицу checking_place не добавлены';
                        } else {
                            $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу checking_place";
                        }
                        $place_count = 0;
                        unset($place_batch_insert_array);
                        $place_batch_insert_array = array();
                    }
                }
            }
            unset($place_array, $cheking_item, $place_item);

            /** Отладка */
            $description = 'Добавили основную часть мест проверок';                                               // описание текущей отладочной точки
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

            if (!empty($place_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('checking_place', ['checking_id', 'place_id'], $place_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `checking_id` = VALUES (`checking_id`), `place_id` = VALUES (`place_id`)')->execute();

                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу checking_place не добавлены';
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу checking_place";
                }
                unset($place_batch_insert_array);
            }
            $place_batch_insert_array = [];
            unset($place_batch_insert_item);

            /** Отладка */
            $description = 'Добавили остаточные места проверок';                                                                      // описание текущей отладочной точки
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

            /** СОХРАНЕНИЕ СТАУСОВ ПРЕДПИСАНИЙ МАССОВО **/
            // Записываем места проверок в БД массово, для этого готовим данные в плоский вид
            $inj_status = 0;
            foreach ($injunction_status as $injunction_item) {
                foreach ($injunction_item as $worker_item) {
                    $inj_status_batch_insert_item['injunction_id'] = $worker_item['injunction_id'];
                    $inj_status_batch_insert_item['worker_id'] = $worker_item['worker_id'];
                    $inj_status_batch_insert_item['status_id'] = $worker_item['status_id'];
                    $inj_status_batch_insert_item['date_time'] = $worker_item['date_time'];
                    $inj_status_batch_insert_array[] = $inj_status_batch_insert_item;
                    $inj_status++;
                    if ($inj_status == 2000) {
                        $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_status_batch_insert_array);
                        $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_id` = VALUES (`injunction_id`), `worker_id` = VALUES (`worker_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                        if ($insert_full === 0) {
                            $warnings[] = $method_name . '. Записи в таблицу injunction_status не добавлены';
                        } else {
                            $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу injunction_status";
                        }
                        $inj_status = 0;
                        unset($inj_status_batch_insert_array);
                        $inj_status_batch_insert_array = array();
                    }
                }
            }
            unset($injunction_status, $injunction_item, $worker_item);

            /** Отладка */
            $description = 'Добавили основной массив статусов';                                                                      // описание текущей отладочной точки
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

            unset($inj_status_batch_insert_item);
            $warnings[] = "$method_name. Итоговый массив для массовой вставки в checking_place";
//            $warnings[] = $inj_status_batch_insert_array;
            if (!empty($inj_status_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_status_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_id` = VALUES (`injunction_id`), `worker_id` = VALUES (`worker_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();

                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу injunction_status не добавлены';
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу injunction_status";
                }
                unset($inj_status_batch_insert_array);
            }

            /** Отладка */
            $description = 'Добавили остсток статусов ПАБов';                                                                      // описание текущей отладочной точки
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

            $inj_viol_status_count = 0;
            foreach ($inj_violation_statuses as $inj_violation_status) {
                $inj_viol_status_batch_insert_item['injunction_violation_id'] = $inj_violation_status['injunction_violation_id'];
                $inj_viol_status_batch_insert_item['status_id'] = $inj_violation_status['status_id'];
                $inj_viol_status_batch_insert_item['date_time'] = $inj_violation_status['date_time'];
                $inj_viol_status_batch_insert_array[] = $inj_viol_status_batch_insert_item;
                $inj_viol_status_count++;
                if ($inj_viol_status_count == 2000) {
                    $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_violation_status', ['injunction_violation_id', 'status_id', 'date_time'], $inj_viol_status_batch_insert_array);
                    $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                    if ($insert_full === 0) {
                        $warnings[] = $method_name . '. Записи в таблицу injunction_violation_status не добавлены';
                    } else {
                        $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу injunction_violation_status";
                    }
                    $inj_viol_status_count = 0;
                    unset($inj_viol_status_batch_insert_array);
                    $inj_viol_status_batch_insert_array = array();
                }
            }
            unset($inj_violation_statuses);

            /** Отладка */
            $description = 'Добавили основной массив статусов нарушений предписаний';                                                                      // описание текущей отладочной точки
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

            if (!empty($inj_viol_status_batch_insert_array)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert('injunction_violation_status', ['injunction_violation_id', 'status_id', 'date_time'], $inj_viol_status_batch_insert_array);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . ' ON DUPLICATE KEY UPDATE `injunction_violation_id` = VALUES (`injunction_violation_id`), `status_id` = VALUES (`status_id`), `date_time` = VALUES (`date_time`)')->execute();
                if ($insert_full === 0) {
                    $warnings[] = $method_name . '. Записи в таблицу injunction_violation_status не добавлены';
                } else {
                    $warnings[] = "$method_name. добавил - $insert_full - записей в таблицу injunction_violation_status";
                }
                unset($inj_viol_status_batch_insert_array);
            }

            /** Отладка */
            $description = 'Докуинули остатки статусов нарушений предписаний';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    /**
     * Метод CopyUserData() - Копирование данных пользователей из oracle в нашу таблицу (sap_user_copy)
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * (Метод не требует входных данных)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (Стандартный массив выходных данных)
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * Алгоритм работы метода:
     * 1. Подключиться к ораклу
     * 2. Получить данные из оракла у которых не пустой user_id
     * 3. Очистить таблицу sap_user_copy
     * 4. Перебор полченных данных
     *      4.1 Формирование массива на добавление в таблицу sap_user_copy
     *      4.2 увеличиваем счётчик
     *      4.2 Значение счётчика больше либо равно 2 000
     *              да?     Добавление в таблицу sap_user_copy
     *                      Обнуляем счётчик и массив данных на добавление
     *              нет?    Пропустить
     * 5. Конец перебора
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.03.2020 11:52
     */
    public function CopyUserData()
    {
        // Стартовая отладочная информация
        $method_name = 'CopyUserData';                                                                          // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                           // количество вставленных записей
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
        $count_add = 0;

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $errors = oci_error();                                                                                  //заполнение массива ошибок в случае ее наступления
                $warnings[] = 'ppkCopyRefCheckmv. Соединение с Oracle не выполнено';
                throw new Exception($method_name . '. Ошибка при подключении к Oracle');
            } else {
                $warnings [] = 'ppkCopyRefCheckmv. Соединение с Oracle установлено';
            }
            /** Отладка */
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
                $date_time_debug_start, $date_time_debug_end, $log_id,
                $duration_summary, $max_memory_peak, $count_all);
            if ($response['status'] === 1) {
                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
            } else {
                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
            }

            /** Метод начало */
            /**
             * выгружаем даннные из оракла по условию: логин пользователя из AD неу пуст
             */
            $query = oci_parse($conn_oracle, "
                SELECT PERNR, EMAIL_ADDRESS,USERID,USERPRINCIPALNAME,TO_CHAR(DATE_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DATE_MODIFIED
                FROM AMICUM.contact_view 
                where USERID is not null
                ");
            oci_execute($query);
            /**
             * Очищаем таблицу sap_user_copy
             */
            $del_full_count = Yii::$app->db->createCommand()->delete('sap_user_copy')->execute();
            /** Отладка */
            $description = 'Выгрузили данные из оракла';                                                                      // описание текущей отладочной точки
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
            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS)) {                            //цикл по массиву строк запроса
                $query_result[] = $row;
                $count_add++;
                if ($count_add >= 2000) {
                    $inserted_user_data = Yii::$app->db
                        ->createCommand()
                        ->batchInsert('sap_user_copy', ['PERNR', 'EMAIL_ADDRESS', 'USERID', 'USERPRINCIPALNAME', 'DATE_MODIFIED'], $query_result)
                        ->execute();
                    if ($inserted_user_data == 0) {
                        throw new Exception($method_name . '. Ошибка при сохранении данных пользователей');
                    }
                    $count_add = 0;
                    $query_result = array();
                }
            }

            /** Отладка */
            $description = 'Выполнили перебор и закинули основной массив данных';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_add . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            if (isset($query_result) && !empty($query_result)) {
                $inserted_user_data = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('sap_user_copy', ['PERNR', 'EMAIL_ADDRESS', 'USERID', 'USERPRINCIPALNAME', 'DATE_MODIFIED'], $query_result)
                    ->execute();
                if ($inserted_user_data == 0) {
                    throw new Exception($method_name . '. Ошибка при сохранении данных пользователей');
                }
            }

            /** Отладка */
            $description = 'Докинули остаточный массив данных пользователей';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_add . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
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
        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
            $date_time_debug_start, $date_time_debug_end, $log_id,
            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Метод UpdateUserData() - Обновление данных пользователей
     * @return array
     *
     * @package backend\controllers\serviceamicum
     *
     * @example
     *
     * Алгоритм работы метода:
     * 1. Получить максимальную дату синхронизации таблицы user ($date)
     * 2. Выгрузить все учётные записи
     * 3. Перебор данных учётных записей
     *      3.1 Формирование массива работников у которых есть учётка
     *      3.2 По каждому работнику формирование массив учёток
     * 4. Конец перебора учтёных записей
     * 5. Выборка данных из справочника копирования (sap_user_copy)По условиям
     *      Дата модификации больше чем полученная из таблицы user
     *      PERNR (табельный номер работника) находиться в сформированном ранее массиве работников у которых есть учётная запись
     * 6. В выборке есть данные
     *      нет?    Вызвать исключение: массив данных для синхронизации пуст
     * 7. Перебор полученных данных
     *      7.1 Находим человека в массиве учётных записей работников
     *              не найдено? Пропустить
     *              найдено?    Перебор учётных записей работника
     *                                  Для каждой учётной записи обновляем данные
     *                          Конец перебора
     * 8. Конец перебора
     *
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.03.2020 13:34
     */
    public static function UpdateUserData()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("UpdateUserData");

        try {
            $count_all = 0;                                                                                           // количество вставленных записей
            $count_upd = 0;                                                                                           // количество вставленных записей
            $workers = array();
            $pass_to_insert=[];

            $log->addLog("Начало выполнения метода");

            /** Метод начало */
            $last_modified = (new Query())
                ->select('max(date_time_sync)')
                ->from('user')
                ->scalar();
            $date = date('Y-m-d H:i:s', strtotime($last_modified));

            $log->addLog("Выгрузили все учётные записи по найденному массиву работников");

            $workers = Worker::find()
                ->select('id')
                ->indexBy('id')
                ->asArray()
                ->all();

            $log->addLog("Выгрузили массив работников");

            $users_update_data = (new Query())
                ->select('*')
                ->from('sap_user_copy')
                ->where(['>', 'DATE_MODIFIED', $date])
                ->all();
            if (empty($users_update_data)) {
                throw new Exception('Справочник для синхронизации пуст');
            }

            $log->addLog('Сформировали массив работников для поиска в user. Количество записей для обновления: ' . count($users_update_data));

            $count_add = 0;                                                                                             // количество добавляемых паролей для пользователей
            foreach ($users_update_data as $new_user_data) {
                if (isset($workers[$new_user_data['PERNR']])) {
                    $flag = false;                                                                                      // флаг, true - новый пользователь, false - найденный
                    $count_all++;
                    $new_data_for_user = User::find()
                        ->where(['worker_id' => $new_user_data['PERNR']])
                        ->orWhere(['login' => $new_user_data['USERID']])
                        ->one();
                    if (empty($new_data_for_user)) {
                        $new_data_for_user = new User();

                        $new_data_for_user->workstation_id = 12;                                                            // гостевая
                        $new_data_for_user->default = 1;
                        $new_data_for_user->worker_id = $new_user_data['PERNR'];
                        $flag = true;
                    }
                    $new_data_for_user->login = $new_user_data['USERID'];                                                              // логином будет логин от AD точнее то что идёт после указания сервера
                    $new_data_for_user->email = $new_user_data['EMAIL_ADDRESS'];
                    $new_data_for_user->user_ad_id = $new_user_data['USERID'];
                    $new_data_for_user->props_ad_upd = $new_user_data['USERPRINCIPALNAME'];
                    $new_data_for_user->date_time_sync = $new_user_data['DATE_MODIFIED'];
                    if (!$new_data_for_user->save()) {
                        $log->addData($new_user_data, '$new_user_data', __LINE__);
                        $log->addData($new_data_for_user->errors, '$new_data_for_user->errors', __LINE__);
                        throw new Exception('Ошибка при изменении данных по работнику');
                    }
                    $count_upd++;
                    $new_data_for_user->refresh();
                    $user_id = $new_data_for_user->id;
                    $user_login = $new_data_for_user->login;
                    unset($new_data_for_user);
                    if ($flag) {
                        $guest_pass = ' ';
                        $pass = crypt($guest_pass, '$5$rounds=5000$' . dechex(crc32($user_login)) . '$') . "\n";        //Выполнить хеширование пароля методом SHA-256
                        $check_sum = dechex(crc32($guest_pass));
                        $date = date('Y-m-d H:i:s');
                        $pass_to_insert[] = [
                            $user_id,
                            $date,
                            $pass,
                            $check_sum
                        ];
                        $count_add++;
                    }
                    if ($count_add == 2000) {
                        if (!empty($pass_to_insert)) {
                            $log->addLog("Количество добавленных записей: " . $count_add);

                            $batch_inserted = Yii::$app->db->createCommand()
                                ->batchInsert('user_password', ['user_id', 'date_time', 'password', 'check_sum'], $pass_to_insert)
                                ->execute();
                            if ($batch_inserted == 0) {
                                throw new Exception('Ошибка при сохранении массива паролей');
                            }
                            unset($batch_inserted);
                            $pass_to_insert = array();
                            $count_add = 0;
                        }
                    }
                }
            }
            unset($users_update_data);
            unset($workers);
            unset($new_user_data);

            $log->addLog("Добавили данные для пользователей / Создали новых пользователей, добавили основный массив паролей");
            $log->addLog("Количество обновленных записей: " . $count_upd);

            if (!empty($pass_to_insert)) {
                $log->addLog("Количество добавленных записей: " . $count_add);
                $batch_inserted = Yii::$app->db->createCommand()
                    ->batchInsert('user_password', ['user_id', 'date_time', 'password', 'check_sum'], $pass_to_insert)
                    ->execute();
                if ($batch_inserted == 0) {
                    throw new Exception('Ошибка при сохранении массива паролей');
                }
                unset($pass_to_insert);
            }

            $log->addLog("Добавили остатки паролей");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончил выполнение метода");

        $log->saveLogSynchronization($count_all);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод ppkCopyTable() - универсальный метод копирования данных из Oracle в промежуточные таблицы MySQL
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @param string $table_name_from - имя таблицы, из которой копируем
     * @param string $table_name_to - имя таблицы, в которую копируем
     * @param array $columns_from - столбцы таблицы, из которой надо скопировать
     * @param array $columns_to - столбцы таблицы, в которую надо скопировать
     * @param string $where - условие выборки
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyTable($table_name_from = "", $table_name_to = "", $columns_from = [], $columns_to = [], $where = "")
    {

        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkCopyTable");

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнение метода");

            $maxDateUpdate = (new Query())
                ->select('max(DATE_MODIFIED)')
                ->from($table_name_to)
                ->scalar();
            $log->addData($maxDateUpdate, 'Максимальная дата для обработки записи:', __LINE__);


            if ($where != "") {
                $where = "WHERE " . $where;
            }

            if ($maxDateUpdate) {
                if ($where != "") {
                    $where .= " AND ";
                } else {
                    $where = "WHERE ";
                }
                $where .= "DATE_MODIFIED>TO_DATE('" . $maxDateUpdate . "','YYYY-MM-DD HH24:MI:SS')";
            }

            $log->addData($where, '$where:', __LINE__);

            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $log->addData(oci_error(), 'oci_error', __LINE__);
                $log->addLog('Соединение с Oracle не выполнено');
            } else {
                $log->addLog('Соединение с Oracle установлено');
            }
            $query_string = "SELECT " . implode(',', $columns_from) . " FROM " . $table_name_from . " " . $where;


            $log->addData($query_string, '$query_string:', __LINE__);

            $query = oci_parse($conn_oracle, $query_string);
            oci_execute($query);
            $count = 0;
            $count_all = 0;

            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $query_result[] = $row;
                $count++;
                $count_all++;
                /**
                 * Значение счётчика = 3000
                 *      да?     Массово добавить данные в промежуточную таблицу($table_name_to)
                 *              Очистить массив для вставки данных
                 *              Обнулить счётчик
                 *      нет?    Пропусить
                 */
                if ($count == 2000) {
                    $insert_full = Yii::$app->db->createCommand()->batchInsert($table_name_to, $columns_to, $query_result)->execute();
                    if ($insert_full === 0) {
                        throw new Exception('Записи в таблицу ' . $table_name_to . ' не добавлены');
                    } else {
                        $log->addLog("добавил - записи в таблицу $table_name_to", $insert_full);
                    }
                    $query_result = [];
                    $count = 0;
                }
            }

            if (isset($query_result) && !empty($query_result)) {
                $insert_full = Yii::$app->db->createCommand()->batchInsert($table_name_to, $columns_to, $query_result)->execute();
                if ($insert_full === 0) {
                    throw new Exception('Записи в таблицу ' . $table_name_to . ' не добавлены');
                } else {
                    $log->addLog("добавил - записи в таблицу $table_name_to", $insert_full);
                }
            }
            $log->addLog("количество добавляемых записей: ", $count_all);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод ppkCopyTableOnDuplicate() - универсальный метод копирования данных таблиц из Oracle в промежуточные таблицы MySQL с проверкой на дубликаты
     * Выходные параметры:
     * (стандартный массив выходных данных)
     *
     * @param string $table_name_from - имя таблицы, из которой копируем
     * @param string $table_name_to - имя таблицы, в которую копируем
     * @param array $columns_from - столбцы таблицы, из которой надо скопировать
     * @param array $columns_to - столбцы таблицы, в которую надо скопировать
     * @param string $where - условие выборки
     * @param array $on_duplicate - столбцы проверки дубликатов
     * @package backend\controllers\serviceamicum
     * @author
     * Created date: on 03.10.2019 11:17
     */
    public function ppkCopyTableOnDuplicate($table_name_from = "", $table_name_to = "", $columns_from = [], $columns_to = [], $where = "", $on_duplicate = [])
    {

        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ppkCopyTableOnDuplicate");

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнение метода");

            $maxDateUpdate = (new Query())
                ->select('max(DATE_MODIFIED)')
                ->from($table_name_to)
                ->scalar();
            $log->addData($maxDateUpdate, 'Максимальная дата для обработки записи:', __LINE__);


            if ($where != "") {
                $where = "WHERE " . $where;
            }

            if ($maxDateUpdate) {
                if ($where != "") {
                    $where .= " AND ";
                } else {
                    $where = "WHERE ";
                }
                $where .= "DATE_MODIFIED>TO_DATE('" . $maxDateUpdate . "','YYYY-MM-DD HH24:MI:SS')";
            }

            $log->addData($where, '$where:', __LINE__);

            $conn_oracle = $this->oracle_db;
            if (!$conn_oracle) {                                                                                        //проверка наличия подключения с сервером оракл
                $log->addData(oci_error(), 'oci_error', __LINE__);
                $log->addLog('Соединение с Oracle не выполнено');
            } else {
                $log->addLog('Соединение с Oracle установлено');
            }
            $on_duplicate_string = "";
            if (!empty($on_duplicate)) {
                $on_duplicate_string = " ON DUPLICATE KEY UPDATE ";
                foreach ($on_duplicate as $field) {
                    $on_duplicate_string .= "`" . $field . "` = VALUES(`" . $field . "`), ";
                }
                $on_duplicate_string = substr($on_duplicate_string, 0, -2);

            }

            $query_string = "SELECT " . implode(',', $columns_from) . " FROM " . $table_name_from . " " . $where;

            $log->addData($query_string, '$query_string:', __LINE__);

            $query = oci_parse($conn_oracle, $query_string);
            oci_execute($query);
            $count = 0;
            $count_all = 0;

            while ($row = oci_fetch_array($query, OCI_ASSOC + OCI_RETURN_NULLS))                                        //цикл по массиву строк запроса
            {
                $query_result[] = $row;
                $count++;
                $count_all++;
                /**
                 * Значение счётчика = 3000
                 *      да?     Массово добавить данные в промежуточную таблицу($table_name_to)
                 *              Очистить массив для вставки данных
                 *              Обнулить счётчик
                 *      нет?    Пропусить
                 */

                if ($count == 2000) {
                    $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert($table_name_to, $columns_to, $query_result);
                    $insert_full = Yii::$app->db->createCommand($sql_insert_full . $on_duplicate_string)->execute();
                    if ($insert_full === 0) {
                        throw new Exception('Записи в таблицу ' . $table_name_to . ' не добавлены');
                    } else {
                        $log->addLog("добавил - записи в таблицу $table_name_to", $insert_full);
                    }
                    $query_result = [];
                    $count = 0;
                }
            }

            if (isset($query_result) && !empty($query_result)) {
                $sql_insert_full = Yii::$app->db->queryBuilder->batchInsert($table_name_to, $columns_to, $query_result);
                $insert_full = Yii::$app->db->createCommand($sql_insert_full . $on_duplicate_string)->execute();
                if ($insert_full === 0) {
                    throw new Exception('Записи в таблицу ' . $table_name_to . ' не добавлены');
                } else {
                    $log->addLog("добавил - записи в таблицу $table_name_to", $insert_full);
                }
            }
            $log->addLog("количество добавляемых записей: ", $count_all);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Метод synchSKUD() - метод синхронизации СКУД на прямую из Firebird
     * @return array
     * @package backend\controllers\serviceamicum
     * @example
     *
     * Значения статусов СКУД
     * 1 - зашел в АБК
     * 2 - вышел с АБК
     * 3 - взял свет
     * 4 - отдал свет
     * 5 - отметка от светильника на поверхности
     * 6 - отметка от светильника в шахте
     * Разработал: Якимов М.Н.
     * дата 28.01.2020
     */
    public static function synchSKUD()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("synchSKUD");

        try {
//            ini_set('max_execution_time', -1);
//            ini_set('memory_limit', "10500M");
            $log->addLog("Начало выполнения метода");

            $status_sync = SapSkudUpdate::find()
                ->select(['num_sync'])
                ->andWhere(['status' => 0])
                ->scalar();
            if ($status_sync) {
                throw new Exception('Есть не завершенные синхронизации SapSkudUpdate');
            }

            /******************* Вычисление следующего номера синхронизации  *******************/
            $max_value = SapSkudUpdate::find()                                                                          //получение максимального номера синхронизации
            ->max('num_sync');
            if ($max_value === NULL) {                                                                                  //получение текущего номера синхронизации
                $num_sync = 1;
            } else {
                $num_sync = $max_value + 1;
            }

            $log->addLog("Прошел проверки на последнюю синхронизацию и получил номер синхронизации");

            /******************* Вычисление времени последнего обновления *******************/
            $new_data_to_update = SapSkudUpdate::find()
                ->select('max(sap_skud_update.date_time)')
                ->scalar();
            if ($new_data_to_update) {
                $new_time_to_update = date("H:i:s", strtotime($new_data_to_update));                           //приведение к типу date
                $new_data_to_update = date("d.m.Y", strtotime($new_data_to_update));                           //приведение к типу date
            } else {
                $new_data_to_update = date("d.m.Y", strtotime("2021-04-01"));
                $new_time_to_update = date("H:i:s", strtotime("2021-04-01"));
            }
            $new_data_now = date("d.m.Y", strtotime(Assistant::GetDateNow() . ' +1 day'));                             //приведение к типу date

            $log->addData($new_data_to_update, 'Дата последней синхронизации: ', __LINE__);
            $log->addData($max_value, 'Номер синхронизации последний: ', __LINE__);
            $log->addData($num_sync, 'Номер синхронизации следующий: ', __LINE__);

            /******************* Получение массива работников для проверки на сущетвование работника в базе *******************/
            $workers_ids_db = [];
            $workers_ids_db_row = (new Query())
                ->select('id, tabel_number')
                ->from('worker')
                ->all();

            foreach ($workers_ids_db_row as $worker) {
                $tabel_number = trim($worker['tabel_number']);
                $workers_ids_db[$tabel_number]=$worker;
            }

            unset($workers_ids_db_row);

            $log->addLog("Получил справочник работников для проверки их существования в системе и последнюю добавленную дату");
//            throw new Exception("stop");
            /******************* Подключение к базе Oracle *******************/
            // SELECT STAFF.TABEL_ID,  REG_EVENTS.DATE_EV, REG_EVENTS.TIME_EV, REG_EVENTS.TYPE_PASS, AREAS_TREE.N_LEVEL FROM REG_EVENTS JOIN STAFF ON STAFF.ID_STAFF=REG_EVENTS.STAFF_ID JOIN AREAS_TREE ON AREAS_TREE.ID_AREAS_TREE=REG_EVENTS.AREAS_ID WHERE STAFF.TABEL_ID IS NOT NULL AND (CAST(REG_EVENTS.DATE_EV AS DATE) > CAST('28.04.2021' AS DATE)) AND (CAST(REG_EVENTS.TIME_EV AS TIME) > CAST('00:00:00' AS TIME)) AND (CAST(REG_EVENTS.DATE_EV AS DATE) <= CAST('28.04.2021' AS DATE)) AND (CAST(REG_EVENTS.TIME_EV AS TIME) <= CAST('16:30:21' AS TIME))
            $query_text = "SELECT STAFF.TABEL_ID,  REG_EVENTS.DATE_EV, REG_EVENTS.TIME_EV, REG_EVENTS.TYPE_PASS, AREAS_TREE.N_LEVEL FROM REG_EVENTS JOIN STAFF ON STAFF.ID_STAFF=REG_EVENTS.STAFF_ID JOIN AREAS_TREE ON AREAS_TREE.ID_AREAS_TREE=REG_EVENTS.AREAS_ID WHERE STAFF.TABEL_ID IS NOT NULL AND 
                                                                                                                                                                                                                                                STAFF.TABEL_ID != '                 ---' AND
                                                                                                                                                                                                                                                (REG_EVENTS.DATE_EV >= CAST('" . $new_data_to_update . "' AS DATE)) AND 
                                                                                                                                                                                                                                                (REG_EVENTS.TIME_EV > CAST('" . $new_time_to_update . "' AS TIME)) AND 
                                                                                                                                                                                                                                                (REG_EVENTS.DATE_EV  <= CAST('" . $new_data_now . "' AS DATE)) ";
            $log->addData($query_text, '$query_text', __LINE__);

            $skuds = Yii::$app->firebird->createCommand($query_text)->queryAll();

//            $log->addData($skuds, '$skuds', __LINE__);
//
//            throw new Exception("stop");
            $log->addLog("Получил данные со СКУД");

            $count_break_ids = 0;
            $count_record = 0;
            $log->addLog("выполнил запрос к бд");

//                throw new \Exception($method_name . '. debugStop');
            foreach ($skuds as $row) {
                $count_record++;
                $tabel_number = trim($row['tabel_id']);
                if (!isset($workers_ids_db[$tabel_number])) {
                    $count_break_ids++;
                    $log->addLog('Ключ работника, которого нет в AMICUM: ' . $tabel_number);
                    continue;
                }

                $item['worker_id'] = (int)$workers_ids_db[$tabel_number];
                $item['date_time'] = date("Y-m-d H:i:s", strtotime($row['date_ev'] . ' ' . $row['time_ev']));
                $item['type_skud'] = $row['n_level'];
                $item['num_sync'] = $num_sync;
                $item['status'] = 0;
                $skud_updates[] = $item;
            }
            $log->addLog('количество работников которых нет в AMICUM ' . $count_break_ids);
            $log->addLog('Все записи ' . $count_record);

            unset($row);
            unset($item);

            $log->addLog("Обработал данные с СКУД");

            if (isset($skud_updates)) {
                /******************* Вставка данных в промежуточную таблицу sap_skud_update *******************/
                $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('sap_skud_update', ['worker_id', 'date_time', 'type_skud', 'num_sync', 'status'], $skud_updates)->execute();

                $log->addLog('Записей вставлено: ' . $insert_result_to_MySQL);

                // индексируем массив вставки скуд по работникам
                $skud_updates_by_worker = [];
                foreach ($skud_updates as $skud_update) {
                    $skud_updates_by_worker[$skud_update['worker_id']] = $skud_update;
                }

                unset($skud_updates);

                $log->addLog("Массовая вставка данных с СКУД в АМИКУМ");

                /******************* Сохранение параметра *******************/
                $worker_for_search = array();
                foreach ($skud_updates_by_worker as $item) {
                    $worker_parameter_insert[] = array(
                        'worker_object_id' => $item['worker_id'],
                        'parameter_id' => 529,
                        'parameter_type_id' => 2,
                    );

                    $worker_parameter_insert[] = array(
                        'worker_object_id' => $item['worker_id'],
                        'parameter_id' => 684,
                        'parameter_type_id' => 2,
                    );

                    $worker_for_search[] = $item['worker_id'];
                }
                /******************* Вставка 529/684 параметра *******************/

                if (!empty($worker_parameter_insert)) {
                    $global_insert = Yii::$app->db->queryBuilder->batchInsert('worker_parameter', ['worker_object_id', 'parameter_id', 'parameter_type_id'], $worker_parameter_insert);
                    $update_on_duplicate = Yii::$app->db->createCommand($global_insert . ' ON DUPLICATE KEY UPDATE
                `worker_object_id` = VALUES (`worker_object_id`), `parameter_id` = VALUES (`parameter_id`), `parameter_type_id` = VALUES (`parameter_type_id`)')->execute();
                    if ($update_on_duplicate !== 0) {
                        $log->addLog("Добавил/обновил данные в таблице worker_parameter");
                    }
                }

                $log->addLog("массово создал или обновил параметры работника");

                /******************* Составление массива для вставки в worker_parameter_value *******************/
// TODO - здесь может быть касяк!!!! worker_id!=worker_object!!! может влиять на отображение прошедших через турникет
                $worker_parameterObj_skud = WorkerParameter::find()
                    ->select(['id', 'worker_object_id'])
                    ->where(['parameter_type_id' => 2])
                    ->andWhere(['parameter_id' => 529])
                    ->andWhere(['IN', 'worker_object_id', $worker_for_search])
                    ->indexBy('worker_object_id')
                    ->asArray()
                    ->all();

                $worker_parameterObj_alc = WorkerParameter::find()
                    ->select(['id', 'worker_object_id'])
                    ->where(['parameter_type_id' => 2])
                    ->andWhere(['parameter_id' => 684])
                    ->andWhere(['IN', 'worker_object_id', $worker_for_search])
                    ->indexBy('worker_object_id')
                    ->asArray()
                    ->all();

                $log->addLog("Нашел айдишники конкретных параметров работника");

                $worker_parameter_value_alcotest = array();
                $worker_parameter_value_array_cache = array();

                foreach ($skud_updates_by_worker as $item) {
                    $date_time_work = date("Y-m-d", strtotime($item['date_time']));
                    $worker_parameter_value_array_cache[] = array(
                        'worker_parameter_id' => $worker_parameterObj_skud[$item['worker_id']]['id'],
                        'worker_id' => $item['worker_id'],
                        'date_time' => $item['date_time'],
                        'value' => $item['type_skud'],
                        'parameter_id' => 529,
                        'parameter_type_id' => 2,
                        'status_id' => 1,
                        'date_work' => $date_time_work,
                    );

                    $worker_parameter_value_array_db[] = array(
                        'worker_parameter_id' => $worker_parameterObj_skud[$item['worker_id']]['id'],
                        'date_time' => $item['date_time'],
                        'value' => $item['type_skud'],
                        'status_id' => 1,
                        'date_work' => $date_time_work,
                    );

                    $worker_parameter_value_array_cache[] = array(
                        'worker_parameter_id' => $worker_parameterObj_alc[$item['worker_id']]['id'],
                        'worker_id' => $item['worker_id'],
                        'date_time' => $item['date_time'],
                        'value' => $item['type_skud'],
                        'parameter_id' => 684,
                        'parameter_type_id' => 2,
                        'status_id' => 1,
                        'date_work' => $date_time_work,
                    );

                    $worker_parameter_value_array_db[] = array(
                        'worker_parameter_id' => $worker_parameterObj_alc[$item['worker_id']]['id'],
                        'date_time' => $item['date_time'],
                        'value' => $item['type_skud'],
                        'status_id' => 1,
                        'date_work' => $date_time_work,
                    );
                }

                $log->addLog("нашёл соответствия в worker_parameter и вытащил id параметров");

                /******************* Добавление данных в таблицу worker_parameter_value *******************/

                if (!empty($worker_parameter_value_array_db)) {
                    $global_insert_param_val = Yii::$app->db->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'date_work'], $worker_parameter_value_array_db);
                    $update_on_duplicate_value = Yii::$app->db->createCommand($global_insert_param_val . " ON DUPLICATE KEY UPDATE
                `worker_parameter_id` = VALUES (`worker_parameter_id`), `date_time` = VALUES (`date_time`), `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `date_work` = VALUES (`date_work`)")->execute();
                    if ($update_on_duplicate_value !== 0) {
                        $log->addLog("Добавил/обновил данные в таблице worker_parameter_value");
                    }
                }

                $log->addLog("Массово вставил 529 Статус СКУД");


                /******************* Вставка значений в кэш *******************/
                $worker_cache_controller = new WorkerCacheController();
                $insert_into_cache = $worker_cache_controller->multiSetWorkerParameterHash($worker_parameter_value_array_cache);
                if ($insert_into_cache !== 0) {
                    $log->addLog("Добавил данные в кэш");
                }

                $log->addLog("Закончил основной код");

                $update_sap_skud_update = SapSkudUpdate::UpdateAll(['status' => 1], 'num_sync=' . $num_sync);
                $log->addLog("Обновил статус синхронизации скуд на 1. Количество записей: " . $update_sap_skud_update);

                /******************* Отправка данных на websocket *******************/
                $client = new Client("ws://" . AMICUM_CONNECT_STRING_WEBSOCKET . "/ws");

                foreach ($skud_updates_by_worker as $item_result) {
                    $payload_item['worker_id'] = $item_result['worker_id'];
                    $payload_item['date_time'] = $item_result['date_time'];
                    $payload_item['type_skud'] = $item_result['type_skud'];

                    $temp_message = json_encode($payload_item);
                    $send_message = array(
                        'ClientType' => 'server',
                        'ActionType' => 'publish',
                        'SubPubList' => ["worker_skud_in_out"],
                        'MessageToSend' => json_encode(array(
                                "type" => 'setStatusSkudInOrder',
                                "message" => $temp_message)
                        ));
                    /******************* Отправка на websocket  ******************/
                    if ($client) {
                        $client->send(json_encode($send_message));
                    } else {
                        throw new Exception("actionWebSocket не смог подключиться к: ", AMICUM_CONNECT_STRING_WEBSOCKET, ". Проверьте доступ к WebSocket");
                    }
                }


                $log->addLog("отправил данные на вебсокет");

            } else {
                $log->addLog("Нет данных для вставки при синхронизации");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}


