<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

use backend\controllers\Assistant;
use backend\controllers\EventMainController;
use backend\controllers\serviceamicum\ImportController;
use backend\controllers\serviceamicum\SyncFromRabbitMQController;
use backend\controllers\serviceamicum\SynchronizationController;
use Exception;
use frontend\controllers\reports\SummaryReportEmployeeForbiddenZonesController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Checking;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class SynchronizationFrontController extends Controller
{
    // Р - рефакторинг проведен (можно пользоваться)

    // actionPartUpdateEmployee()   - Метод для ручного запуска синхронизации людей САП и АМИКУМ
    // actionRemoveFireEmployee()   - Метод для удаления уволенных сотрудников
    // actionRemoveDubleEmployee()  - метод по удалению дубликатов после синхронизации сап и АМИКУМ ( у кого табельный номер не равен worker_id)

    /**  СИНХРОНИЗАЦИЯ ДОЛЖНОСТЕЙ */
    // Р actionCopyPosition()         - Метод копирование сгруппированных должэнастей из САП таблицы CONTACT_VIEW
    // Р actionUpdatePosition()       - Метод синхронизация справочника должностей (сгруппированный CONTACT_VIEW)
    // actionUpdatePositionAmicum() - Метод обоновления справочника должностей ГОСУДАРСТВЕННЫЙ

    /**  СИНХРОНИЗАЦИЯ СВЕДЕНИЙ О РАБОТНИКАХ */
    // Р actionCopyEmployee()         - Метод копирования сведений о работниках из САП таблицы CONTACT_VIEW
    // Р actionUpdateEmployee()       - Метод синхронизация сведений о работниках
    // actionSyncEmployee()           - синхронизация персонала RabbitMQ

    /**  СИНХРОНИЗАЦИЯ СВЕДЕНИЙ О КОМПАНИЯХ */
    // Р actionCopyCompany()          - Метод копирования сведений о подразделения из САП таблицы
    // Р actionUpdateCompany()        - Метод синхронизация сведений о подразделения
    // actionSyncDivision()           - синхронизация подразделений RabbitMQ

    /**  СИНХРОНИЗАЦИЯ СВЕДЕНИЙ СКУД */
    // Р actionUpdateSkud             - синхронизация СКУД работника
    // Р actionSynchSkud              - синхронизация СКУД работника напрямую из firebird

    // actionSynchronizationWorkerCard  - главный метод синхронизации пропусков работников
    // actionCopyWorkerPass             - метод копирования пропусков работника из САП - нужный нам
    // actionPartUpdateWorkerCard       - метод частичного обнволения пропусков работников - нужный нам
    // actionWorkerPass                 - разовое копирование пропусков работника в нашу БД - при инициализации
    // actionFullWorkerCard             - метод полного обновления пропусков работника - при инициализации

    /**  СИНХРОНИЗАЦИЯ СВЕДЕНИЙ О СИЗ */
    // Р actionCopyWorkerSiz          - копирование истории сиз работника
    // Р actionCopySiz                - копирвоание сиз справочника
    // Р actionUpdateSiz              - обновление СИЗ справочника
    // Р actionUpdateWorkerSiz        - обновление истории выдачи/списания СИЗ работника

    /**  СИНХРОНИЗАЦИЯ СВЕДЕНИЙ О ППК ПАБ */
    // actionCopyPk                             - копирование справочников ППК ПАБ, а так же самих ПАБов, внутренних предписаний и преписаний РТН
    // actionUpdateInjunction                   - метод синхронизации обновления внутренних предписаний
    // actionUpdateRtn                          - метод синхронизации обновления предписаний РТН
    // actionUpdatePpk                          - метод синхронизации обновления ППК ПАБ
    // actionUpdateNn                           - метод синхронизации обновления н/н
    // actionFullUpdatePk                       - метод полной синхронизации обновления Полная синхронизация ПАБ, н/н, РТН, Предписания
    // actionInjunctionCheckingWorkerUpdatePk   - метод запуска синхронизации аудиторов Предписания
    // actionInjunctionUpdatePk                 - метод запуска синхронизации самих внутренних предписаний

    /**  СИНХРОНИЗАЦИЯ СВЕДЕНИЙ ОБ ЭСМО */
    // actionUpdateEsmo               - синхронизация сведений о прохождении предсменных ЭСМО

    /** ПЛАНИРОВЩИК СИТУАЦИЙ */
    // actionCronSituation            - планировщик отправки сообщений при возникновении ситуации

    /** СИНХРОНИЗАЦИЯ УЧЕТНЫХ ЗАПИСЕЙ */
    // actionCopyAd()                 - копирование учетных записей AD
    // actionUpdateAd()               - синхронизация справочника учетных записей
    // actionSyncGetAccountAd()       - запрос синхронизации персонала RabbitMQ
    // actionSyncAccountAd()          - синхронизация персонала RabbitMQ

    /** ЗАПРЕТНЫЕ ЗОНЫ */
    // actionCreateReportForbiddenZonesTableData() - расчет запретных зон в сводную таблицу

    /** СИНХРОНИЗАЦИЯ ВСЕХ СОБЫТИЙ 1С */
    // actionSyncAll()                  - синхронизация всех событий из 1С RabbitMQ

    /** ИМПОРТ ДАННЫХ ИЗ EXCEL */
    // actionImportEmployeeFromExcel()  - Метод импорта данных о работниках из Excel

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод actionPartUpdateEmployee() - Метод для ручного запуска синхронизации людей САП и АМИКУМ
     * @example http://127.0.0.1/synchronization-front/part-update-employee
     */
    public static function actionPartUpdateEmployee()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $response = SynchronizationController::sapUpdateEmployee();
            /****************** Формирование ответа ******************/
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $status = $response['status'];
                $errors = $response['errors'];
                $warnings = $response['warnings'];
            } else {
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                throw new \yii\db\Exception('actionPartUpdateEmployee. метод FindDepartment завершлся с ошибкой');
            }
        } catch (Throwable $exception) {
            $errors[] = "actionPartUpdateEmployee. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    /**
     * Метод actionRemoveDubleEmployee() - Метод для удаления дублированных и не верных сотрудников из БД
     * @example http://127.0.0.1/synchronization-front/remove-double-employee
     */
    public static function actionRemoveDoubleEmployee()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $response = SynchronizationController::RemoveDoubleEmployee();
            /****************** Формирование ответа ******************/
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $status = $response['status'];
                $errors = $response['errors'];
                $warnings = $response['warnings'];
            } else {
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                throw new \yii\db\Exception('actionRemoveDoubleEmployee. метод RemoveDubleEmployee завершлся с ошибкой');
            }
        } catch (Throwable $exception) {
            $errors[] = "actionRemoveDoubleEmployee. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionRemoveFireEmployee() - Метод для удаления уволенных сотрудников
     * @example http://127.0.0.1/synchronization-front/remove-fire-employee
     */
    public static function actionRemoveFireEmployee()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $response = SynchronizationController::RemoveFireEmployee();
            /****************** Формирование ответа ******************/
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $status = $response['status'];
                $errors = $response['errors'];
                $warnings = $response['warnings'];
            } else {
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                throw new \yii\db\Exception('actionRemoveFireEmployee. метод RemoveFireEmployee завершлся с ошибкой');
            }
        } catch (Throwable $exception) {
            $errors[] = "actionRemoveDubleEmployee. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionCopyPositionSapToAmicum() - Метод копирования данных из САП в АМИКУМ по должностям
     * @example http://127.0.0.1/synchronization-front/copy-position-sap-to-amicum
     */
    public static function actionCopyPositionSapToAmicum()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $response = SynchronizationController::partUpdateDataOraclePositionSecondInsert();
            /***************** Формирование ответа *****************/
            if ($response['status'] == 1) {
                $result[] = $response['Items'];
                $status = $response['status'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new \Exception('actionCopyPositionSapToAmicum. метод copyPartUpdateEmployeeTable завершлся с ошибкой');

            }
        } catch (Throwable $exception) {
            $errors[] = "actionCopyPositionSapToAmicum. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdatePositionAmicum() - Метод обоновления справочника должностей
     * @example http://127.0.0.1/synchronization-front/update-position-sap-to-amicum
     */
    public static function actionUpdatePositionAmicum()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $response = SynchronizationController::partUpdateDataOraclePositionSecondUpdate();
            /***************** Формирование ответа *****************/
            if ($response['status'] == 1) {
                $result[] = $response['Items'];
                $status = $response['status'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new \Exception('actionCopyPositionSapToAmicum. метод copyPartUpdateEmployeeTable завершлся с ошибкой');

            }
        } catch (Throwable $exception) {
            $errors[] = "actionCopyPositionSapToAmicum. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionCopyPk() - копирование справочников ППК ПАБ, а так же самих ПАБов, внутренних предписаний и преписаний РТН
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/copy-pk
     *
     * @author Якимов М.Н.
     * Created date: on 30.07.2019 18:32
     */
    public function actionCopyPk()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $debug = array();
        $result = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {
            $oracle_controller = new SynchronizationController();

            /******************* ПАБы Предписания*******************/

            $result[] = $oracle_controller->ppkCopyRefCheckmv();                                                        // справочник видов проверок
            $result[] = $oracle_controller->ppkCopyInstructionmv();                                                     // внутренние предписания
            $result[] = $oracle_controller->ppkCopyPabmv();                                                             // ПАБы
            $result[] = $oracle_controller->ppkCopyRtnmv();                                                             // предписания РТН
            $result[] = $oracle_controller->ppkCopyGiltyManager();                                                      // справочник решений руководителей по наказанию виновных
            $result[] = $oracle_controller->ppkCopyErrorDirection();                                                    // справочник направлений нарушений
            $result[] = $oracle_controller->ppkCopyRefFailureEffectmv();                                                // справочник последствий
            $result[] = $oracle_controller->ppkCopyRefOPONumber();                                                      // справочник ОПО
            $result[] = $oracle_controller->ppkCopyRefNormDocmv();                                                      // справочник нормативных документов
            $result[] = $oracle_controller->ppkCopyRefPlaceAuditmv();                                                   // справочник мест аудита
            $result[] = $oracle_controller->ppkCopyRefRepresmv();                                                       // справочник взысканий
            $result[] = $oracle_controller->ppkCopyRefSituationmv();                                                    // справочник обстоятельств
            $result[] = $oracle_controller->ppkCopyHCMStructObjidView();                                                // справочник подразделений ППК ПАБ
            $result[] = $oracle_controller->ppkCopyHCMHRSRootPernrView();                                               // справочник персонала ППК ПАБ
            $result[] = $oracle_controller->SyncBlobMain();                                                           // справочник вложений


//            /******************* БЛОК СИНХРОНИЗАЦИИ ВНУТРЕННИХ ПРЕДПИСАНИЙ*******************/
//            $result[] = SynchronizationController::ppkSynchRefNormDocmv();                                                  // синхронизация справочника нормативных документов
//            $result[] = SynchronizationController::ppkSynchRefErrorDirectionmv();                                           // синхронизация справочника направлений нарушений
//            $result[] = SynchronizationController::ppkSyncInjuctionMain();                                                  // синхронизация справочника направлений нарушений
//            /******************** СИНХРОНИЗАЦИЯ РТН ********************/
//            $result[] = SynchronizationController::ppkSynhInjunctionRTNMain();                                                  // главный метод синхронизации предписаний РТН
//            /******************** СИНХРОНИЗАЦИЯ ПАБ ********************/
//            $result[] = SynchronizationController::ppkSynhPABNNMain();
//            /******************** СИНХРОНИЗАЦИЯ НН ********************/
//            $result[] = SynchronizationController::ppkSyncNNMain();
//
//
//            $result[] = $oracle_controller->ppkCopyRefCheckmv();
//            $result[] = $oracle_controller->ppkCopyInstructionmv();
//            $result[] = $oracle_controller->ppkCopyPabmv();
//            $result[] = $oracle_controller->ppkCopyRtnmv();
//            $result[] = $oracle_controller->ppkCopyGiltyManager();
//            $result[] = $oracle_controller->ppkCopyErrorDirection();
//            $result[] = $oracle_controller->ppkCopyRefFailureEffectmv();
//            $result[] = $oracle_controller->ppkCopyRefOPONumber();
//            $result[] = $oracle_controller->ppkCopyRefNormDocmv();
//            $result[] = $oracle_controller->ppkCopyRefPlaceAuditmv();
//            $result[] = $oracle_controller->ppkCopyRefRepresmv();
//            $result[] = $oracle_controller->ppkCopyRefSituationmv();
//////            $result[] = $oracle_controller->ppkCopyUserBlob();
//////            $result[] = $oracle_controller->ppkSynhBlobDoc();
//            $result[] = $oracle_controller->ppkCopyHCMStructObjidView();
//            $result[] = $oracle_controller->ppkCopyHCMHRSRootPernrView();
////            $result[] = $oracle_controller->CopyUserData();

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $errors = array_merge($errors, $item['errors']);
                $debug = array_merge($debug, $item['debug']);
                $status *= $item['status'];

            }
            $result_items = $result_middle;
            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $result_items;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /**
     * Метод actionUpdateInjunction - метод синхронизации обновления внутренних предписаний
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-injunction
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 18:32
     */
    public function actionUpdateInjunction()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* ПАБы Предписания*******************/
            $result[] = SynchronizationController::ppkSynchRefNormDocmv();              // синхронизация справочника нормативных документов
            $result[] = SynchronizationController::ppkSynchRefErrorDirectionmv();       // синхронизация справочника направлений нарушений
            $result[] = SynchronizationController::ppkSyncInjuctionMain();              // синхронизация справочника направлений нарушений

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $debug = array_merge($warnings, $item['debug']);
                $errors = array_merge($errors, $item['errors']);
            }

            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionUpdateInjunction. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateRtn - метод синхронизации обновления предписаний РТН
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-rtn
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 18:32
     */
    public function actionUpdateRtn()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* ПАБы Предписания*******************/
            $result[] = SynchronizationController::ppkSynhInjunctionRTNMain();                                          // главный метод синхронизации предписаний РТН

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $debug = array_merge($warnings, $item['debug']);
                $errors = array_merge($errors, $item['errors']);
            }

            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionUpdateRtn. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdatePpk - метод синхронизации обновления ППК ПАБ
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-ppk
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 18:32
     */
    public function actionUpdatePpk()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* ПАБы Предписания*******************/
            $result[] = SynchronizationController::ppkSynhPABNNMain();                                                  // главный метод синхронизации ППК ПАБ

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $errors = array_merge($errors, $item['errors']);
            }

            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionUpdatePpk. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateNn - метод синхронизации обновления н/н
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-nn
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 18:32
     */
    public function actionUpdateNn()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* ПАБы Предписания*******************/
            $result[] = SynchronizationController::ppkSyncNNMain();                                                     // главный метод синхронизации нн

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $debug = array_merge($warnings, $item['debug']);
                $errors = array_merge($errors, $item['errors']);
            }

            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionUpdateNn. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionFullUpdatePk  метод полной синхронизации обновления Полная синхронизация ПАБ, н/н, РТН, Предписания
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/full-update-pk
     *
     */
    public function actionFullUpdatePk()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionFullUpdatePk");
        try {

//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнения метода");

            /****** БЛОК КОПИРОВАНИЯ СПРАВОЧНИКОВ ППК ПАБ И САМИХ ПАБов, ВНУТРЕННИХ ПРЕДПИСАНИЙ и ПРЕДПИСАНИЙ РТН ****/

            $oracle_controller = new SynchronizationController();

            $result[] = $oracle_controller->ppkCopyRefCheckmv();                                                        // справочник видов проверок

            $result[] = $oracle_controller->ppkCopyInstructionmv();                                                     // внутренние предписания

            $result[] = $oracle_controller->ppkCopyPabmv();                                                             // ПАБы

            $result[] = $oracle_controller->ppkCopyRtnmv();                                                             // предписания РТН

            $result[] = $oracle_controller->ppkCopyGiltyManager();                                                      // справочник решений руководителей по наказанию виновных

            $result[] = $oracle_controller->ppkCopyErrorDirection();                                                    // справочник направлений нарушений

            $result[] = $oracle_controller->ppkCopyRefFailureEffectmv();                                                // справочник последствий

            $result[] = $oracle_controller->ppkCopyRefOPONumber();                                                      // справочник ОПО

            $result[] = $oracle_controller->ppkCopyRefNormDocmv();                                                      // справочник нормативных документов

            $result[] = $oracle_controller->ppkCopyRefPlaceAuditmv();                                                   // справочник мест аудита

            $result[] = $oracle_controller->ppkCopyRefRepresmv();                                                       // справочник взысканий

            $result[] = $oracle_controller->ppkCopyRefSituationmv();                                                    // справочник обстоятельств

            $result[] = $oracle_controller->ppkCopyHCMStructObjidView();                                                // справочник подразделений ППК ПАБ

            $result[] = $oracle_controller->ppkCopyHCMHRSRootPernrView();                                               // справочник персонала ППК ПАБ

            $result[] = $oracle_controller->SyncBlobMain();                                                             // справочник вложений


            /******************* БЛОК СИНХРОНИЗАЦИИ ВНУТРЕННИХ ПРЕДПИСАНИЙ*******************/
            $result[] = SynchronizationController::ppkSynchRefNormDocmv();                                              // синхронизация справочника нормативных документов
            $result[] = SynchronizationController::ppkSynchRefErrorDirectionmv();                                       // синхронизация справочника направлений нарушений
            $result[] = SynchronizationController::ppkSyncInjuctionMain();                                              // синхронизация справочника направлений нарушений
            /******************** СИНХРОНИЗАЦИЯ РТН ********************/
            $result[] = SynchronizationController::ppkSynhInjunctionRTNMain();                                          // главный метод синхронизации предписаний РТН
            /******************** СИНХРОНИЗАЦИЯ ПАБ ********************/
            $result[] = SynchronizationController::ppkSynhPABNNMain();
            /******************** СИНХРОНИЗАЦИЯ НН ********************/
            $result[] = SynchronizationController::ppkSyncNNMain();


            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $log->addLogAll($item);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization();

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => 1], $log->getLogAll());
    }

    /**
     * Метод actionInjunctionCheckingWorkerUpdatePk - метод запуска синхронизации аудиторов Предписания
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/injunction-checking-worker-update-pk
     *
     */
    public function actionInjunctionCheckingWorkerUpdatePk()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionInjunctionCheckingWorkerUpdatePk");
        try {

//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнения метода");
            $checkingSapSpr = Checking::find()->select('id, instruct_id')
                ->andWhere('instruct_id is not null')
                ->indexBy('instruct_id')
                ->all();
            /******************** СИНХРОНИЗАЦИЯ АУДИТОРОВ ПРЕДПИСАНИЙ ********************/
            $result[] = SynchronizationController::ppkSynhCheckingWorker($checkingSapSpr);

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $log->addLogAll($item);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization();

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => 1], $log->getLogAll());
    }

    /**
     * Метод actionInjunctionUpdatePk - метод запуска синхронизации самих внутренних предписаний
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/injunction-update-pk
     *
     */
    public function actionInjunctionUpdatePk()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionInjunctionUpdatePk");
        try {

//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");

            $log->addLog("Начало выполнения метода");
            $checkingSapSpr = Checking::find()->select('id, instruct_id')
                ->andWhere('instruct_id is not null')
                ->indexBy('instruct_id')
                ->all();
            /******************** СИНХРОНИЗАЦИЯ АУДИТОРОВ ПРЕДПИСАНИЙ ********************/
            $result[] = SynchronizationController::ppkSynhInjunction($checkingSapSpr);

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $log->addLogAll($item);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization();

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => 1], $log->getLogAll());
    }


    /**
     * Метод actionSynchronization() - последовательный вызов методов из контроллера Synchronization для моделирования работы синхронизации данных между Oracle и MySQL
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/synchronization
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 30.07.2019 18:32
     */
    public function actionSynchronization()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************** ПРЕДПИСАИНЯ РТН ********************/
//            $result[] = SynchronizationController::ppkSynhInjunctionRTNMain();

            /******************** ПАБ/ НАРУШЕНИЯ НЕСООТВЕТСТВИЯ ********************/
//            $result[] = SynchronizationController::ppkSynhPABNNMain();
            $result[] = SynchronizationController::ppkSyncNNMain();

            /******************** СИНХРОНИЗАЦИЯ ФАЙЛОВ ********************/
//            $result[] = SynchronizationController::SyncBlobMain();
            /******************** СИНХРОНИЗАЦИЯ УЧЁТНЫХ ЗАПИСЕЙ ********************/
//            $result[] = SynchronizationController::UpdateUserData();

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $errors = array_merge($errors, $item['errors']);
            }
            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionSynchronization. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }
//        $result = $result_items;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionSynchronizationWorkerCard - главный метод синхронизации пропусков работников
    // пример: http://127.0.0.1/synchronization-front/synchronization-worker-card
    public function actionSynchronizationWorkerCard()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* Карточка сотрудника *******************/
            $result[] = SynchronizationController::CopyWorkerPass();
            $result[] = SynchronizationController::PartUpdateWorkerCard();
//            $result[] = SynchronizationController::FullWorkerCard();
//            $result[] = SynchronizationController::WorkerPass();

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $errors = array_merge($errors, $item['errors']);
            }
            $result_items = $result_middle;
            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionSynchronizationWorkerCard. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }
//        $result = $result_items;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionCopyWorkerPass - метод копирования пропусков работника из САП
    public function actionCopyWorkerPass()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* Карточка сотрудника *******************/
            $result[] = SynchronizationController::CopyWorkerPass();


            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $errors = array_merge($errors, $item['errors']);
            }
            $result_items = $result_middle;
            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionCopyWorkerPass. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }
//        $result = $result_items;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionFullWorkerCard - метод полного обновления пропусков работника
    public function actionFullWorkerCard()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* Карточка сотрудника *******************/
            $result[] = SynchronizationController::FullWorkerCard();

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $errors = array_merge($errors, $item['errors']);
            }
            $result_items = $result_middle;
            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionFullWorkerCard. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }
//        $result = $result_items;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionPartUpdateWorkerCard - метод частичного обнволения пропусков работников
    public function actionPartUpdateWorkerCard()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* Карточка сотрудника *******************/

            $result[] = SynchronizationController::PartUpdateWorkerCard();


            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $errors = array_merge($errors, $item['errors']);
            }
            $result_items = $result_middle;
            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionPartUpdateWorkerCard. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }
//        $result = $result_items;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionWorkerPass - разовое копирование пропусков работника в нашу БД
    public function actionWorkerPass()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $result_items = array();
        $result_middle = array();
        $microtime_start = microtime(true);
        try {

            /******************* Карточка сотрудника *******************/

            $result[] = SynchronizationController::WorkerPass();

            /******************* Сбор результатов из методов в один массив *******************/
            foreach ($result as $item) {
                $result_middle['Result_Item'][] = $item['status'];
                $status *= $item['status'];
                $warnings = array_merge($warnings, $item['warnings']);
                $errors = array_merge($errors, $item['errors']);
            }
            $result_items = $result_middle;
            $warnings[] = "Общее время выполнения " . $duration_method = round(microtime(true) - $microtime_start, 6);


        } catch (Throwable $exception) {
            $errors[] = 'actionWorkerPass. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }
//        $result = $result_items;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public static function Test($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'test';
        $test = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            $test[] = SynchronizationController::CopyWorkerPass();
            $warnings[] = $method_name . '. Данные с фронта получены';

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $test;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод actionCopyPosition() - копирование сгруппированных должэнастей из САП таблицы CONTACT_VIEW
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/copy-position
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionCopyPosition()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = (new SynchronizationController)->sapCopyPositionFromContactView();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdatePosition() - синхронизация справочника должностей
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-position
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionUpdatePosition()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = SynchronizationController::sapUpdatePosition();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionCopyEmployee() - копирование сведений о работниказ из САП таблицы CONTACT_VIEW
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/copy-employee
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionCopyEmployee()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = (new SynchronizationController)->sapCopyEmployeeContactView();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /**
     * Метод actionUpdateEmployee() - синхронизация сведений о работниках
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-employee
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionUpdateEmployee()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = SynchronizationController::sapUpdateEmployee();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionCopyCompany() - копирование сведений о подразделениях из САП таблицы
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/copy-company
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionCopyCompany()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = (new SynchronizationController)->partCompanyDepartmentInsert();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateCompany() - синхронизация сведений о подразделениях
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-company
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionUpdateCompany()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = SynchronizationController::partCompanyDepartmentUpdate();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionCopyWorkerSiz() - копирование истории сиз работника
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/copy-worker-siz
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionCopyWorkerSiz()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = (new SynchronizationController)->copyFullDataWorkWear();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionCopySiz() - копирвоание сиз справочника
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/copy-siz
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionCopySiz()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = (new SynchronizationController)->copyFullDataSIZ();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateSiz() - обновление СИЗ справочника
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-siz
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionUpdateSiz()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = SynchronizationController::partUpdateSiz();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateWorkerSiz() - обновление истории выдачи/списания СИЗ работника
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-worker-siz
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionUpdateWorkerSiz()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = SynchronizationController::partUpdateWorkerSiz();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateSkud() - синхронизация СКУД работника
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-skud
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionUpdateSkud()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {
            $result[] = SynchronizationController::newSynchronizationSKUD(SKUD_HOST_NAME_ZAP, SKUD_SERVICE_NAME_ZAPT, '290');
            $result[] = SynchronizationController::newSynchronizationSKUD(SKUD_HOST_NAME_KOMS, SKUD_SERVICE_NAME_KOMS, '270');
            $result[] = SynchronizationController::newSynchronizationSKUD(SKUD_HOST_NAME_VORG, SKUD_SERVICE_NAME_VORG, '250');
            $result[] = SynchronizationController::newSynchronizationSKUD(SKUD_HOST_NAME_VORK, SKUD_SERVICE_NAME_VORK, '291');

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionSynchSkud() - синхронизация СКУД работника напрямую из firebird
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/synch-skud
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionSynchSkud()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionSynchSkud");

        try {
            $response = SynchronizationController::synchSKUD();
            $log->addLogAll($response);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionSynchronizationEsmo      - синхронизация сведений о прохождении предсменных ЭСМО
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-esmo
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionUpdateEsmo()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = IntegrationController::SynchronizationEsmo();
            if ($response['status'] == 0) {
                throw new Exception("Ошибка выполнения синхронизации ЕСМО");
            }
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];
            $debug_data = $response['debug_data'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug, 'debug_data' => $debug_data);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionCronSituation            - планировщик отправки сообщений при возникновении ситуации
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/cron-situation?mine_id=270&mine_title='ш.Заполярная-2'
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionCronSituation()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $post = Assistant::GetServerMethod();

            $mine_id = $post['mine_id'];
            $mine_title = $post['mine_title'];

            $response = EventMainController::CronSituation($mine_id, $mine_title);
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateAd() - синхронизация справочника учетных записей
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/update-ad
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionUpdateAd()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = SynchronizationController::UpdateUserData();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionCopyAd() - копирование учетных записей AD
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/copy-ad
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionCopyAd()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = (new SynchronizationController)->CopyUserData();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionCreateReportForbiddenZonesTableData() - расчет запретных зон в сводную таблицу
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/create-report-forbidden-zones-table-data
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionCreateReportForbiddenZonesTableData()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $debug = array();

        try {

            $response = SummaryReportEmployeeForbiddenZonesController::CreateReportForbiddenZonesTableData();

            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];
            if ($response['status'] != 1) {
                throw new \Exception("actionCreateReportForbiddenZonesTableData. Ошибка обработки данных");
            }

        } catch (Throwable $exception) {
            $errors[] = 'actionCreateReportForbiddenZonesTableData. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionSyncDivision() - синхронизация подразделений RabbitMQ
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/sync-division
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionSyncDivision()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionSyncDivision");
        try {
            $response = SyncFromRabbitMQController::syncDivision();
            $log->addLogAll($response);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод actionSyncEmployee() - синхронизация персонала RabbitMQ
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/sync-employee
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionSyncEmployee()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionSyncEmployee");
        try {
            $response = SyncFromRabbitMQController::syncEmployee();
            $log->addLogAll($response);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод actionSyncGetAccountAd() - запрос синхронизации персонала RabbitMQ
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/sync-get-account-ad
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionSyncGetAccountAd()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionSyncGetAccountAd");
        try {
            $response = SyncFromRabbitMQController::syncGetAccountAD();
            $log->addLogAll($response);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод actionSyncAccountAd() - синхронизация персонала RabbitMQ
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/sync-account-ad
     *
     * @author Якимов М.Н.
     * Created date: on 19.01.2020
     */
    public function actionSyncAccountAd()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionSyncAccountAd");
        try {
            $response = SyncFromRabbitMQController::syncAccountAD();
            $log->addLogAll($response);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Метод actionSyncAll() - синхронизация всех событий из 1С RabbitMQ
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/sync-all
     * @author Якимов М.Н.
     * Created date: on 08.03.2022
     */
    public function actionSyncAll()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionSyncAll", true);
        try {
            $response = (new SyncFromRabbitMQController())->SyncAll();
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка синхронизации');
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод actionImportEmployeeFromExcel() - Метод импорта данных о работниках из Excel
     * @package frontend\controllers
     * @example 127.0.0.1/synchronization-front/sync-all
     * @author Якимов М.Н.
     * Created date: on 08.03.2022
     */
    public function actionImportEmployeeFromExcel()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionImportEmployeeFromExcel", true);
        try {
            $response = ImportController::ImportEmployeeFromExcel();
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка синхронизации');
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод actionImportEmployeeFromExcel() - Метод импорта данных о работниках из Excel
     * @package frontend\controllers
     * @example http://127.0.0.1/read-manager-amicum?controller=SynchronizationFront&method=ImportEmployeeFromExcel&subscribe=&data={}
     * @author Якимов М.Н.
     * Created date: on 04.05.2022
     */
    public static function ImportEmployeeFromExcel($data_post)
    {
        $response = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("ImportEmployeeFromExcel", true);
        try {
            $response = ImportController::ImportEmployeeFromExcel($data_post);
            $log->addLogAll($response);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return $response;
    }
}
