<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

//  GetListWorker()                 - Вывод списка всех сотрудников в подразделении и их дата рождения
//  SaveBriefing()                  - Метод сохранения инструктажа и инструктируемых.
//  ChangeStatusBriefing()          - метод изменения статуса у инструктажа
//  ChangeStatusBriefer()           - метод изменения статуса у инструктируемого
//  GetByDate()                     - метод по дате-подразделению-инструктируемому выбирает инструктаж.
//  GetDate()                       - Метод, получения дат всех инструктажей для определенного сотрудника
//  GetHistoryDate()                - Получение списка дат инструктажей для каждого сотрудника данного подразделения
//  GetJournalBriefing              - журнал инструктажей по фильтру подразделение
//  GetListTypeBriefing             - справочник типов инструктажей
//  GetIndicator()                  - Метод определения  тип (цвета ) идентификатора.  Определяет типы: 1. если осталось меньше 2-х недель, если прошло больше 3-х месяцев или осталось
//  GetListTypeBriefing             - получить справочник причин проведения стажировки
//  SaveInternship()                - Метод сохранения стажировки
//  GetListBriefingReason           - получить справочник причин проведения инструктажа
//  DelBriefing                     - удалить инструктаж
//  DelBriefing                     - удалить инструктируемого
//  GetHandbookKindFirePrevention   - Справочник видов инструктажей
//  GetHandbookTypeAccident         - Метод получения справочника видов происшествий приведших к несчастным случаям на производстве
// GetBriefings                     - Метод получения инструктажей работников по дате и типу инструктажа

// GetBriefingReason()      - Получение справочника причин инструктажей
// SaveBriefingReason()     - Сохранение справочника причин инструктажей
// DeleteBriefingReason()   - Удаление справочника причин инструктажей

// GetKindAccident()      - Получение справочника видов несчастных случаев
// SaveKindAccident()     - Сохранение справочника видов несчастных случаев
// DeleteKindAccident()   - Удаление справочника видов несчастных случаев

// GetKindIncident()      - Получение справочника видов инцидентов
// SaveKindIncident()     - Сохранение справочника видов инцидентов
// DeleteKindIncident()   - Удаление справочника видов инцидентов

// GetTypeAccident()      - Получение справочника типов несчастных случаев
// SaveTypeAccident()     - Сохранение справочника типов несчастных случаев
// DeleteTypeAccident()   - Удаление справочника типов несчастных случаев

// GetTypeBriefing()      - Получение справочника типов инструктажей
// SaveTypeBriefing()     - Сохранение справочника типов инструктажей
// DeleteTypeBriefing()   - Удаление справочника типов инструктажей

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\const_amicum\BriefingReasonEnumController;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\const_amicum\TypeBriefingEnumController;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Attachment;
use frontend\models\Briefer;
use frontend\models\Briefing;
use frontend\models\BriefingReason;
use frontend\models\Document;
use frontend\models\DocumentAttachment;
use frontend\models\InternshipReason;
use frontend\models\KindAccident;
use frontend\models\KindFirePreventionInstruction;
use frontend\models\KindIncident;
use frontend\models\TypeAccident;
use frontend\models\TypeBriefing;
use frontend\models\Worker;
use Throwable;
use yii\web\Controller;


class BriefingController extends Controller
{
    /**@var int тип уведомления 1. разница между текущей датой и датой последнего Инструктажа > 76 */
    const TYPE_ONE = 1;

    /**@var int тип уведомления 2. разница между текущей датой и датой последнего Инструктажа > 90 */
    const TYPE_TWO = 2;

    /** @var int тип индикатора зеленый - срок до следующего больше 2-х недель */
    const TYPE_THREE = 3;

    /**@var int количество дней для первого типа инструктажа */
    const DAY_TYPE_ONE = 76;
    const DAY_TYPE_TWO = 90;


    public function actionIndex()
    {
        return $this->render('index');
    }

    // GetListTypeBriefing - получить типов инструктажей
    // пример: http://127.0.0.1/read-manager-amicum?controller=Briefing&method=GetListTypeBriefing&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListTypeBriefing($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $type_briefing_list = TypeBriefing::find()
                ->limit(100)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$type_briefing_list) {
                $warnings[] = 'GetListTypeBriefing. Справочник типов инструктажей пуст';
                $result = (object)array();
            } else {
                $result = $type_briefing_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListTypeBriefing. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListTypeBriefing - получить справочник причин проведения стажировки
    // пример: http://127.0.0.1/read-manager-amicum?controller=Briefing&method=GetListInternShipReason&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListInternShipReason($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $internship_reason_list = InternshipReason::find()
                ->limit(100)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$internship_reason_list) {
                $warnings[] = 'GetListInternShipReason. Справочник причин стажировки пуст';
                $result = (object)array();
            } else {
                $result = $internship_reason_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListInternShipReason. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListBriefingReason - получить справочник причин проведения инструктажа
    // пример: http://127.0.0.1/read-manager-amicum?controller=Briefing&method=GetListBriefingReason&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListBriefingReason($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок
        $briefing_reason_list = array();
        $list_briefing_reason = array();
        $reason_by_parent_brif_reason = array();
        try {
            $briefing_reasons = BriefingReason::find()
                ->where('parent_id is null')
                ->asArray()
                ->all();

            $briefing_reason_list = BriefingReason::find()
                ->indexBy('id')
                ->where('parent_id is not null')
                ->asArray()
                ->all();

            foreach ($briefing_reason_list as $briefing_reason) {
                $reason_by_parent_brif_reason[$briefing_reason['parent_id']][] = $briefing_reason;
            }
            unset($briefing_reason_item);
            unset($briefing_reason_list);

            foreach ($briefing_reasons as $briefing_reason_item) {
                $list_briefing_reason[] = self::BriefingReasonParent($briefing_reason_item, $reason_by_parent_brif_reason);
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListBriefingReason. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        if (empty($list_briefing_reason)) {
            $result = (object)array();
        } else {
            $result = $list_briefing_reason;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод BriefingReasonParent() - Используется в методе GetListBriefingReason для получения рекурсивного справочника
     * @param $reason
     * @param $reason_by_parent_brif_reason
     * @return mixed
     *
     * @package frontend\controllers
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 15.11.2019 16:31
     */
    public static function BriefingReasonParent($reason, $reason_by_parent_brif_reason)
    {
        $list_briefing_reason['id'] = $reason['id'];
        $list_briefing_reason['title'] = $reason['title'];
        $list_briefing_reason['is_chosen'] = 2;

        if (isset($reason_by_parent_brif_reason[$reason['id']])) {

            foreach ($reason_by_parent_brif_reason[$reason['id']] as $child_briefing_reason) {
                $list_briefing_reason['children'][] = self::BriefingReasonParent($child_briefing_reason, $reason_by_parent_brif_reason);

            }
        }
        return $list_briefing_reason;
    }

    /**
     * Метод GetListWorker() - Вывод списка всех сотрудников в подразделении и их дата рождения
     *
     * @param null $data_post
     * @return array
     *
     *Выходные параметры: массив по кажждому сотруднику в подразделении вида:
     *                                 |---id []
     *                                      |__[
     *                                          worker_id     - ключ сотрудника
     *                                          birthdate     - дата рождения сотрудника
     *                                          ]
     *
     * @package frontend\controllers
     *Входные обязательные параметры:
     * @example   http://localhost/read-manager-amicum?controller=Briefing&method=GetListWorker&subscribe=&data={%22company_department_id%22:%224029860%22}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 12.09.2019 10:19
     */
    public static function GetListWorker($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_worker = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetListWorker. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetListWorker. Данные с фронта не получены');
            }
            $warnings[] = 'GetListWorker. Данные успешно переданы';
            $warnings[] = 'GetListWorker. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetListWorker. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('GetListWorker. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $company_department_id = $post_dec->company_department_id;
            $warnings[] = 'GetListWorker. Данные с фронта получены';

            //$today = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));

            //изменила чтобы список брался из графика вых-в "на сегодня", чтобы можно было провести инструктаж для тех, кто с других подразделений пришли
            // получилось, что людей намного меньше, чем есть в подразделении, поэтому не знаю как правильнее
            $workers = Worker::find()
                ->joinWith('employee')
                ->where(['company_department_id' => $company_department_id])
                ->asArray()
                ->all();

            //Assistant::PrintR($workers);die;
            foreach ($workers as $worker) {
                $list_worker[$worker['id']]['worker_id'] = $worker['id'];
                $list_worker[$worker['id']]['birthdate'] = $worker['employee']['birthdate'];
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetListWorker. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetListWorker. Конец метода';
        $result = $list_worker;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // DelBriefing - удалить инструктаж
    //
    //
    public static function DelBriefing($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_worker = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'DelBriefing. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetListWorker. Данные с фронта не получены');
            }
            $warnings[] = 'DelBriefing. Данные успешно переданы';
            $warnings[] = 'DelBriefing. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'DelBriefing. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'briefing_info'))
            ) {
                throw new Exception('DelBriefing. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $briefing_info = $post_dec->briefing_info;
            $warnings[] = 'DelBriefing. Данные с фронта получены';

            $count_del = Briefing::deleteAll(['id' => $briefing_info->briefing_id]);

        } catch (Throwable $exception) {
            $errors[] = 'DelBriefing. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'DelBriefing. Конец метода';
        if (isset($count_del)) {
            $result = $count_del;
        } else {
            $result = -1;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // DelBriefing - удалить инструктируемого
    //
    //
    public static function DelBriefer($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_worker = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'DelBriefing. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('DelBriefer. Данные с фронта не получены');
            }
            $warnings[] = 'DelBriefer. Данные успешно переданы';
            $warnings[] = 'DelBriefer. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'DelBriefer. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'briefing_info')) ||
                !(property_exists($post_dec, 'briefing_id'))
            ) {
                throw new Exception('DelBriefer. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $briefing_info = $post_dec->briefing_info;
            $briefing_id = $post_dec->briefing_id;
//            $warnings[] = 'DelBriefing. Данные с фронта получены';

            $count_del['briefer'] = Briefer::deleteAll(['id' => $briefing_info->briefer_id]);

            $briefings = Briefing::find()
                ->innerJoinWith('briefers')
                ->where(['briefing.id' => $briefing_id])
                ->all();
            if (!$briefings) {
                $warnings[] = 'DelBriefer. Удалил инструктаж.т.к не было инструктируемых';
                $count_del['briefing'] = Briefing::deleteAll(['id' => $briefing_id]);
            }

        } catch (Throwable $exception) {
            $errors[] = 'DelBriefer. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'DelBriefer. Конец метода';
        if (isset($count_del)) {
            $result = $count_del;
        } else {
            $result = -1;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // тестовый набор  {"date_time":"2019-04-25","worker_id":70018761,"company_department_id":4029860,"briefer_id":["70018976","70018977","70018993"]}
    // http://localhost/read-manager-amicum?controller=Briefing&method=SaveInternship&subscribe=&data={}
    /**
     * Метод SaveInternship() - Метод сохранения стажировки
     *
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:
     *
     * @package frontend\controllers
     *
     *
     * @author Якимов М.Н.
     * Created date: on 30.09.2019
     */
    public static function SaveInternship($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'SaveInternship. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveInternship. Данные с фронта не получены');
            }
            $warnings[] = 'SaveInternship. Данные успешно переданы';
            $warnings[] = 'SaveInternship. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveInternship. Декодировал входные параметры';

            if (
                !property_exists($post_dec, 'briefer')
            ) {
                throw new Exception('SaveInternship. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'SaveInternship. Данные с фронта получены';

            //Входные данные переводим в переменне
            $newBriefer = $post_dec->briefer;
            $new_attachment_id = null;

            /****************** Делаем проверку, что такого инструктажа нет ******************/
            $briefer = Briefer::findOne(['id' => $newBriefer->briefer_id]);
            if (!$briefer) {
                throw new Exception('SaveInternship. Такого инструктажа не существует');
            }

            $warnings[] = 'SaveInternship. Такая стажировка существовала';

            $briefer->internship_reason_id = $newBriefer->internship->internship_reason_id;
            $briefer->internship_worker_id = $newBriefer->internship->internship_worker_id;
            $briefer->internship_position_id = $newBriefer->internship->internship_position_id;
            $briefer->internship_taken_status_id = $newBriefer->internship->internship_taken_status_id;
            $briefer->internship_end_status_id = $newBriefer->internship->internship_end_status_id;
            $briefer->duration_day = $newBriefer->internship->duration_day;
            if (!empty($newBriefer->internship->internship_start)) {
                $briefer->internship_start = date("Y-m-d", strtotime($newBriefer->internship->internship_start));
            } else {
                $briefer->internship_start = null;
            }
            if (!empty($newBriefer->internship->internship_end)) {
                $briefer->internship_end = date("Y-m-d", strtotime($newBriefer->internship->internship_end));
            } else {
                $briefer->internship_end = null;
            }

            if (!empty($newBriefer->internship->internship_end_fact_date)) {
                $briefer->internship_end_fact_date = date("Y-m-d", strtotime($newBriefer->internship->internship_end_fact_date));
            } else {
                $briefer->internship_end_fact_date = null;
            }


            //сохраняем инструктаж
            if ($briefer->save()) {                                                                       //сохранение
                $warnings[] = 'SaveInternship. Успешное сохранение повторного инструктажа';
                $briefer->refresh();
                $briefer_Id = $briefer->id;
            } else {
                $errors[] = $briefer->errors;
                throw new Exception('SaveInternship. Ошибка при сохранении Briefer');
            }
            $warnings[] = 'SaveInternship.  стажировка сохранена';
            $result = $newBriefer;
        } catch (Throwable $exception) {
            $errors[] = 'SaveInternship. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        if (!$result) {
            $result = (object)array();
        }
        $warnings[] = 'SaveInternship. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // тестовый набор  {"date_time":"2019-04-25","worker_id":70018761,"company_department_id":4029860,"briefer_id":["70018976","70018977","70018993"]}
    // http://localhost/read-manager-amicum?controller=Briefing&method=SaveBriefing&subscribe=&data={}
    /**
     * Метод SaveBriefing() - Метод сохранения инструктажа и инструктируемых.
     * ps/ если такой инструктаж уже существует - то берется его ключ.
     *
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:
     *
     * @package frontend\controllers
     *
     *
     * @author Якимов М.Н.
     * Created date: on 30.09.2019
     */
    public static function SaveBriefing($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                               // Промежуточный результирующий массив
        $shift_id = null;
        $session = \Yii::$app->session;
        $warnings[] = 'SaveBriefing. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveBriefing. Данные с фронта не получены');
            }
            $warnings[] = 'SaveBriefing. Данные успешно переданы';
            $warnings[] = 'SaveBriefing. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            //$post_dec = json_decode('{"date_time":"2019-04-25","instructor_id":1,"company_department_id":20028766,"type_briefing_id":2,"briefer_ids":["2051446","2051881","2052640"]}');                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveBriefing. Декодировал входные параметры';

            if (
                !(property_exists($post_dec, 'newBriefing') or
                    property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('SaveBriefing. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'SaveBriefing. Данные с фронта получены';

            //Входные данные переводим в переменне
            $newBriefing = $post_dec->newBriefing;
            $company_department_id = $newBriefing->company_department_id;
            $new_attachment_id = null;


            if (
                property_exists($newBriefing->attachmentObj, 'attachment_id') and
                $newBriefing->attachmentObj->title != ""
            ) {
                $attachment_id = $newBriefing->attachmentObj->attachment_id;
                $attachmentObj = $newBriefing->attachmentObj;
                $new_attachment = Attachment::findOne(['id' => $attachment_id]);
                if (!$new_attachment and $attachmentObj->attachment_blob != "" and $newBriefing->attachmentObj->attachment_type != "") {
                    $new_attachment = new Attachment();
                    $new_attachment->path = Assistant::UploadFile($attachmentObj->attachment_blob, $attachmentObj->title, 'attachment', $attachmentObj->attachment_type);
                    $new_attachment->title = $attachmentObj->title;
                    $new_attachment->date = BackendAssistant::GetDateFormatYMD();
                    $new_attachment->worker_id = $session['worker_id'];
                    $new_attachment->section_title = 'Инструктаж';
                    $new_attachment->attachment_type = $attachmentObj->attachment_type;
                    $new_attachment->sketch = $attachmentObj->sketch;
                    if ($new_attachment->save()) {
                        $new_attachment->refresh();
                        $new_attachment_id = $new_attachment->id;
                        $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель InquiryPb';
                    } else {
                        $errors[] = $new_attachment->errors;
                        throw new Exception('SaveJournalInquiry. Ошибка сохранения модели InquiryPb');
                    }
                } else if (!$new_attachment and $attachmentObj->attachment_blob == "") {
                    $warnings[] = "SaveJournalInquiry. вложение не существует ";
                    $new_attachment_id = null;
                } else {
                    $warnings[] = "SaveJournalInquiry. вложение уже было ";
                    $new_attachment_id = $attachment_id;
                }
            } else {
                $warnings[] = "SaveJournalInquiry. нет вложения при сохранении";
            }
            $warnings[] = "Тута";
            /****************** Делаем проверку, что такого инструктажа нет ******************/
            $briefing = Briefing::findOne(['id' => $newBriefing->briefing_id]);
            if (!$briefing) {
                $briefing = new Briefing();
                $warnings[] = 'SaveBriefing. Такой инструктаж не существовал';
            } else {
                $warnings[] = 'SaveBriefing. Такой инструктаж существовал';
            }


            $briefing->date_time = date("Y-m-d H-i-s", strtotime($newBriefing->date_time));
            $briefing->type_briefing_id = $newBriefing->type_briefing_id;
            $briefing->status_id = $newBriefing->status_id;
            $briefing->company_department_id = $newBriefing->company_department_id;
            $briefing->instructor_id = $newBriefing->instructor_id;
            $briefing->instructor_position_id = $newBriefing->instructor_position_id;
            $briefing->briefing_reason = $newBriefing->briefing_reason;
            $briefing->briefing_reason_id = $newBriefing->briefing_reason_id;
            $briefing->document_id = $newBriefing->document_id;
            $briefing->attachment_id = $new_attachment_id;
            $briefing->kind_fire_prevention_id = $newBriefing->kind_fire_prevention_id;


            //сохраняем инструктаж
            if ($briefing->save()) {                                                                       //сохранение
                $warnings[] = 'SaveBriefing. Успешное сохранение повторного инструктажа';
                $briefing->refresh();
                $briefing_id = $briefing->id;
            } else {
                $errors[] = $briefing->errors;
                throw new Exception('SaveBriefing. Ошибка при сохранении  повторного инструктажа');
            }
            $warnings[] = 'SaveBriefing.  инструктаж сохранен';

            foreach ($newBriefing->workers as $worker) {
                $briefer_item = Briefer::findOne(['id' => $worker->briefer_id]);
                if (!$briefer_item) {
                    $briefer_item = new Briefer();
                }
                $briefer_item->briefing_id = $briefing_id;
                if ($worker->date_time == "") {
                    $briefer_item->date_time = null;
                } else {
                    $briefer_item->date_time = date('Y-m-d', strtotime($worker->date_time));
                }
                if ($worker->date_time_first == "") {
                    $briefer_item->date_time_first = null;
                } else {
                    $briefer_item->date_time_first = $worker->date_time_first;
                }
                if ($worker->date_time_second == "") {
                    $briefer_item->date_time_second = null;
                } else {
                    $briefer_item->date_time_second = $worker->date_time_second;
                }
                if ($worker->date_time_third == "") {
                    $briefer_item->date_time_third = null;
                } else {
                    $briefer_item->date_time_third = $worker->date_time_third;
                }

                $briefer_item->position_id = $worker->position_id;
                $briefer_item->status_id = $worker->status_id;
                $briefer_item->worker_id = $worker->worker_id;
                if (property_exists($worker->internship, "internship_reason_id")) {
                    $briefer_item->internship_reason_id = $worker->internship->internship_reason_id;
                    $briefer_item->internship_worker_id = $worker->internship->internship_worker_id;
                    $briefer_item->internship_position_id = $worker->internship->internship_position_id;
                    $briefer_item->internship_taken_status_id = $worker->internship->internship_taken_status_id;
                    $briefer_item->internship_end_status_id = $worker->internship->internship_end_status_id;
                    $briefer_item->internship_start = $worker->internship->internship_start;
                    $briefer_item->internship_end = $worker->internship->internship_end;
                    $briefer_item->internship_end_fact_date = $worker->internship->internship_end_fact_date;
                    $briefer_item->duration_day = $worker->internship->duration_day;
                } else {
                    $briefer_item->internship_reason_id = null;
                    $briefer_item->internship_worker_id = null;
                    $briefer_item->internship_position_id = null;
                    $briefer_item->internship_taken_status_id = null;
                    $briefer_item->internship_end_status_id = null;
                    $briefer_item->internship_start = null;
                    $briefer_item->internship_end = null;
                    $briefer_item->internship_end_fact_date = null;
                    $briefer_item->duration_day = null;
                }
//                $briefers[] = $briefer_item;
//                unset($briefer_item);
                if ($briefer_item->save()) {                                                                       //сохранение
                    $warnings[] = 'SaveBriefing. Успешное сохранение инструктируемых';
                    $briefer_item->refresh();
                    $briefer_id = $briefer_item->id;
                } else {
                    $errors[] = $briefer_item->errors;
                    throw new Exception('SaveBriefing. Ошибка при сохранении инструктируемых Briefer');
                }
                $warnings[] = 'SaveBriefing.  инструктаж сохранен';
            }

//            if (isset($briefers)) {
//                $result_briefers = Yii::$app->db->createCommand()
//                    ->batchInsert('briefer',
//                        [
//                            'briefing_id', 'date_time',
//                            'date_time_first', 'date_time_second',
//                            'date_time_third', 'position_id',
//                            'status_id', 'worker_id',
//                            'internship_reason_id', 'internship_worker_id',
//                            'internship_position_id', 'internship_taken_status_id',
//                            'internship_end_status_id', 'internship_start',
//                            'internship_end', 'internship_end_fact_date', 'duration_day'
//                        ], $briefers)//массовая вставка в БД
//                    ->execute();
//                if ($result_briefers != 0) {
//                    $warnings[] = 'SaveBriefing. Успешное сохранение списка инструктируемых';
//                } else {
//                    throw new Exception('SaveBriefing. Ошибка при добавлении списка инструктируемых');
//                }
//            }
//            $get_current_shift = self::GetCurrentShift();
//            if ($get_current_shift ['status'] == 1) {
//                $warnings[] = $get_current_shift['warnings'];
//                $shift_id = $get_current_shift['Items'];
//            } else {
//                $warnings[] = $get_current_shift['warnings'];
//                $errors[] = $get_current_shift['errors'];
//            }
            $get_current_shift = Assistant::GetShiftByDateTime();
            $shift_id = $get_current_shift['shift_id'];

            $response = self::GetJournalBriefing(json_encode(array('company_department_id' => $company_department_id, 'shift_id' => $shift_id)));
            if ($response['status'] == 1) {
                $journal_briefing = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('SaveBriefing. Не удалось получить обновленный список');
            }
            if (!$journal_briefing) {
                $result = (object)array();
            } else {
                $result = $journal_briefing;
            }
        } catch (Throwable $exception) {
            $errors[] = 'SaveBriefing. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveBriefing. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод ChangeStatusBriefing() - метод изменения статуса у инструктажа
     * @param null $data_post
     * @return array
     *
     *Выходные параметры: -
     *
     * @package frontend\controllers
     *Входные обязательные параметры: ключ инструктажа     {"briefing_id":1}
     * @example  http://localhost/read-manager-amicum?controller=Briefing&method=ChangeStatusBriefing&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 12.09.2019 16:46
     */
    public static function ChangeStatusBriefing($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $status_briefing = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'ChangeStatusBriefing. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('ChangeStatusBriefing. Данные с фронта не получены');
            }
            $warnings[] = 'ChangeStatusBriefing. Данные успешно переданы';
            $warnings[] = 'ChangeStatusBriefing. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'ChangeStatusBriefing. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'instructor_status_id') or
                !property_exists($post_dec, 'date_time') or
                !property_exists($post_dec, 'briefing_id')
            ) {
                throw new Exception('ChangeStatusBriefing. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'ChangeStatusBriefing. Данные с фронта получены';
            $briefing_id = $post_dec->briefing_id;
            $date_time = $post_dec->date_time;
            $instructor_status_id = $post_dec->instructor_status_id;


            $briefing = Briefing::findOne(['id' => $briefing_id]);
            if (empty($briefing)) {
                throw new Exception('ChangeStatusBriefing. Такой инструктаж не найден в бд');
            }
            $briefing->date_time = date("Y-m-d H-i-s", strtotime($date_time));                                                         //меняем на неактивный
            $briefing->status_id = $instructor_status_id;                                                         //меняем на неактивный
            /****************** Проверка на сохранение и сохранение ******************/
            if ($briefing->save()) {
                $warnings[] = 'ChangeStatusBriefing. Успешное сохранение изменненого данных о статусе инструктажа';
            } else {
                $errors[] = $briefing->errors;
                throw new Exception('ChangeStatusBriefing. Ошибка при сохранении изменении статуса инструктажа');
            }

        } catch (Throwable $exception) {
            $errors[] = 'ChangeStatusBriefing. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'ChangeStatusBriefing. Конец метода';
        $result = $status_briefing;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод ChangeStatusBriefer() - метод изменения статуса у инструктируемого
     * Возможно не по briefer_id нужен поиск, а по worker_id и briefing_id
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:  -
     *
     * @package frontend\controllers
     *Входные обязательные параметры:  ключ инструктируемого  JSON со структрой:  {"briefer_id":1}
     * @example  http://localhost/read-manager-amicum?controller=Briefing&method=ChangeStatusBriefer&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 03.09.2019 16:47
     */
    public static function ChangeStatusBriefer($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $status_briefer = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'ChangeStatusBriefer. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('ChangeStatusBriefer. Данные с фронта не получены');
            }
            $warnings[] = 'ChangeStatusBriefer. Данные успешно переданы';
            $warnings[] = 'ChangeStatusBriefer. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'ChangeStatusBriefer. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'briefer_status_id') or
                !property_exists($post_dec, 'date_time') or
                !property_exists($post_dec, 'date_time_first') or
                !property_exists($post_dec, 'date_time_second') or
                !property_exists($post_dec, 'date_time_third') or
                !property_exists($post_dec, 'briefing_id') or
                !property_exists($post_dec, 'company_department_id') or
                !property_exists($post_dec, 'worker_id') or
                !property_exists($post_dec, 'briefer_id')
            ) {
                throw new Exception('ChangeStatusBriefer. Переданы некорректные входные параметры');
            }
            // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'ChangeStatusBriefer. Данные с фронта получены';
            $briefer_id = $post_dec->briefer_id;
            $briefer_status_id = $post_dec->briefer_status_id;

            $briefer = Briefer::findOne(['id' => $briefer_id]);
            if (empty($briefer)) {
                throw new Exception('ChangeStatusBriefer. Такой инструктируемый не найден в бд');
            }

            $briefer->status_id = $briefer_status_id;                                                         //меняем на неактивный
            if ($post_dec->date_time == "" or !$post_dec->date_time) {
                $briefer->date_time = null;                                              //меняем на неактивный
            } else {
                $briefer->date_time = date("Y-m-d H-i-s", strtotime($post_dec->date_time));
            }
            if ($post_dec->date_time_first == "" or !$post_dec->date_time_first) {
                $briefer->date_time_first = null;                                              //меняем на неактивный
            } else {
                $briefer->date_time_first = date("Y-m-d H-i-s", strtotime($post_dec->date_time_first));
            }
            if ($post_dec->date_time_second == "" or !$post_dec->date_time_second) {
                $briefer->date_time_second = null;                                              //меняем на неактивный
            } else {
                $briefer->date_time_second = date("Y-m-d H-i-s", strtotime($post_dec->date_time_second));
            }
            if ($post_dec->date_time_third == "" or !$post_dec->date_time_third) {
                $briefer->date_time_third = null;                                              //меняем на неактивный
            } else {
                $briefer->date_time_third = date("Y-m-d H-i-s", strtotime($post_dec->date_time_third));
            }

            $warnings[] = 'ChangeStatusBriefer. Поменяли статус ';
            /****************** Проверка на сохранение и сохранение ******************/
            if ($briefer->save()) {
                $warnings[] = 'ChangeStatusBriefer. Успешное сохранение изменненого данных о статусе инструктажа';
            } else {
                $errors[] = $briefer->errors;
                throw new Exception('ChangeStatusBriefer. Ошибка при сохранении изменении статуса инструктажа');
            }

        } catch (Throwable $exception) {
            $errors[] = 'ChangeStatusBriefer. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'ChangeStatusBriefer. Конец метода';
        $result = $status_briefer;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetByDate() - метод по дате-подразделению-инструктируемому выбирает инструктаж.
     *
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:     date_time                - дата инструктажа
     *                        instructor               - ключ инструктирующего
     *                        company_department_id    - клюс подразделения
     *                        attachment  [            - данные/путь к инструктажу(документ)
     *                             |--------id         - ключ пути
     *                             |--------path ]     - путь
     *                        briefer_id               - список инструктируемых по данному инструктажу
     *                             |--------[id]       - ключ инструктируемого
     *
     * @package frontend\controllers
     *Входные обязательные параметры: worker_id, company_department_id, date_time
     *                       JSON:
     *                           |-----worker_id
     *                           |-----company_department_id
     *                           |-----date_time
     *
     * @example   http://localhost/read-manager-amicum?controller=Briefing&method=GetByDate&subscribe=&data={}
     * {"worker_id":70016154,"company_department_id":20028766,"date_time":"2019-06-25"}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 13.09.2019 9:22
     */
    public static function GetByDate($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $get_briefing = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetByDate. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetByDate. Данные с фронта не получены');
            }
            $warnings[] = 'GetByDate. Данные успешно переданы';
            $warnings[] = 'GetByDate. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            //$post_dec = json_decode('{"worker_id":70016016,"company_department_id":20028766,"date_time":"2019-06-25"}');                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetByDate. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'worker_id') ||
                    property_exists($post_dec, 'company_department_id') ||
                    property_exists($post_dec, 'date_time')
                )
            ) {
                throw new Exception('GetByDate. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'GetByDate. Данные с фронта получены';
            $worker_id = $post_dec->worker_id;
            $company_department_id = $post_dec->company_department_id;
            $date_time = $post_dec->date_time;

            $briefing = Briefing::find()
                ->innerJoin('briefer', 'briefing.id = briefer.briefing_id')
                ->where(['date_time' => $date_time, 'briefing.company_department_id' => $company_department_id, 'briefer.worker_id' => $worker_id])
                ->all();
            if (!($briefing)) {
                throw new Exception('GetBriefing. Инструктаж по такой дате-подразделению-сотруднику не найден');
            }
            if (count($briefing) > 1) {
                throw new Exception('GetByDate. Найдено больше одного инструктажа. Что-то пошло не так');
            }
            $warnings[] = 'GetByDate. Инструктаж найден';
            $briefing_id = $briefing[0]['id'];

            $list_briefer = Briefer::find()
                ->joinWith('worker')
                ->where(['briefing_id' => $briefing_id])
                ->asArray()
                ->all();
            //Assistant::PrintR($briefing); die;
            foreach ($list_briefer as $briefer) {
                $briefers[$briefer['id']] = ['worker_id' => $briefer['worker_id'], 'positon_id' => $briefer['worker']['position_id'], 'status_id' => $briefer['status_id']];
            }
            //$get_briefing['type_briefing_id']['type_briefing_id'] = ['type_briefing_id'=>$briefing[0]['type_briefing_id']];
            $get_briefing[$briefing[0]['type_briefing_id']]['data_time'] = $date_time;
            $get_briefing[$briefing[0]['type_briefing_id']]['status_id'] = $briefing[0]['status_id'];
            $get_briefing[$briefing[0]['type_briefing_id']]['instructor'] = $briefing[0]['instructor_id'];
            $get_briefing[$briefing[0]['type_briefing_id']]['company_department_id'] = $company_department_id;
            $get_briefing[$briefing[0]['type_briefing_id']]['attachment'] = $briefing[0]['attachment'];
            $get_briefing[$briefing[0]['type_briefing_id']]['briefer_id'] = $briefers;

        } catch (Throwable $exception) {
            $errors[] = 'GetByDate. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetByDate. Конец метода';
        $result = $get_briefing;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetDate() - Метод, получения дат всех инструктажей для определенного сотрудника
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:  worker_id           - ключ сотрудника
     *                          [date_time]    - список дат инструктажей
     *
     * @package frontend\controllers
     *Входные обязательные параметры: JSON со структрой:   {"worker_id":70018976,"company_department_id":4029860}
     * @example  127.0.0.1/read-manager-amicum?controller=Briefing&method=GetDate&subscribe=&data={"worker_id":1801,"type_briefing_id":1}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 17.09.2019 11:02
     */
    public static function GetDate($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_date = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetDate. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetDate. Данные с фронта не получены');
            }
            $warnings[] = 'GetDate. Данные успешно переданы';
            $warnings[] = 'GetDate. Входной массив данных' . $data_post;
            //$post_dec = json_decode('{"worker_id":70018976,"company_department_id":4029860}');                                                                        // Декодируем входной массив данных
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetDate. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'worker_id') ||
                    property_exists($post_dec, 'company_department_id')
                )
            ) {
                throw new Exception('GetDate. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'GetDate. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $worker_id = $post_dec->worker_id;

            $dates = Briefing::find()
                ->select('date_time')
                ->innerJoin('briefer', 'briefing.id = briefer.briefing_id')
                ->where(['briefing.company_department_id' => $company_department_id, 'briefer.worker_id' => $worker_id])
                ->orderBy('date_time')
                ->asArray()
                ->all();
            $list_date['company_department_id'] = $company_department_id;
            $list_date['worker_id'] = $worker_id;
            //$list_date['date'] = $dates['date_time'];
            foreach ($dates as $date) {
                $date_time[] = $date['date_time'];
            }
            $list_date['date_time'] = $date_time;

        } catch (Throwable $exception) {
            $errors[] = 'GetDate. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetDate. Конец метода';
        $result = $list_date;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetHistoryDate() - Получение списка дат инструктажей для конкретного сотрудника по конкретному виду инструктажа
     * @param null $data_post company_department_id
     * @return array  Json вида:
     *                               [
     *                                  worker_id        - ключ сотрудника
     *                                     [date_time]   - дата инструктажа
     *                                ]
     *Выходные параметры:
     *
     * @package frontend\controllers
     *Входные обязательные параметры: company_department_id
     * @example  http://127.0.0.1/read-manager-amicum?controller=Briefing&method=GetHistoryDate&subscribe=&data={"worker_id":1801,"type_briefing_id":1}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 17.09.2019 10:58
     */
    public static function GetHistoryDate($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_history = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetHistoryDate. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetHistoryDate. Данные с фронта не получены');
            }
            $warnings[] = 'GetHistoryDate. Данные успешно переданы';
            $warnings[] = 'GetHistoryDate. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);
            //  4029860
            //$post_dec = json_decode('{"company_department_id":20028766}');                 // Декодируем входной массив данных
            $warnings[] = 'GetHistoryDate. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'worker_id') ||
                    property_exists($post_dec, 'type_briefing_id'))
            ) {
                throw new Exception('GetHistoryDate. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'GetHistoryDate. Данные с фронта получены';
            $type_briefing_id = $post_dec->type_briefing_id;
            $worker_id = $post_dec->worker_id;
            if ($type_briefing_id == 0) {
                $filter_type_briefing = array();
            } else {
                $filter_type_briefing = array('type_briefing_id' => $type_briefing_id);
            }
            $briefings = Briefer::find()
                ->select(
                    '
                    briefing.date_time as briefing_date_time,
                    briefing.type_briefing_id as type_briefing_id,
                    type_briefing.title as type_briefing_title,
                    employee.last_name as last_name,
                    employee.first_name as first_name,
                    employee.patronymic as patronymic,
                    worker.tabel_number as tabel_number,
                    position.title as position_title,
                    company.title as company_title
                    '
                )
                ->innerJoin('briefing', 'briefer.briefing_id =briefing.id')
                ->innerJoin('type_briefing', 'type_briefing.id =briefing.type_briefing_id')
                ->innerJoin('worker', 'worker.id =briefing.instructor_id')
                ->innerJoin('employee', 'worker.employee_id =employee.id')
                ->innerJoin('position', 'worker.position_id =position.id')
                ->innerJoin('company_department', 'worker.company_department_id =company_department.id')
                ->innerJoin('company', 'company_department.company_id =company.id')
                ->where(['briefer.worker_id' => (int)$worker_id])
                ->andFilterWhere($filter_type_briefing)
                ->asArray()
                ->all();
//            $warnings[] = $briefings;
            $index = 1;
            // для каждого сотрудника делаем список дат
            foreach ($briefings as $briefing) {

                $briefer_item['index'] = $index;
                if ($briefing['briefing_date_time']) {
                    $briefer_item['briefing_date_time_format'] = date('d.m.Y', strtotime($briefing['briefing_date_time']));
                } else {
                    $briefer_item['briefing_date_time_format'] = null;
                }
                $briefer_item['briefing_date_time'] = $briefing['briefing_date_time'];
                $briefer_item['type_briefing_title'] = $briefing['type_briefing_title'];
                $briefer_item['instructor_full_name'] = $briefing['last_name'] . ' ' . $briefing['first_name'] . ' ' . $briefing['patronymic'] . ' / ' . $briefing['tabel_number'];
                $briefer_item['instructor_tabel_number'] = $briefing['tabel_number'];
                $briefer_item['instructor_position_title'] = $briefing['position_title'];
                $briefer_item['instructor_company_department_title'] = $briefing['company_title'];
                $briefer_array[] = $briefer_item;
                $index++;
                //$warnings[] = $briefer_item;

            }
        } catch (Throwable $exception) {
            $errors[] = 'GetHistoryDate. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        if (!isset($briefer_array)) {
            $result = (object)array();
        } else {
            $result = $briefer_array;
        }
        $warnings[] = 'GetHistoryDate. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetIndicator() - Метод определения  тип (цвета ) идентификатора.  Определяет типы : 1. если осталось меньше 2х недель, если прошло больше 3х месяцев или осталось
     * больше 3х месяцев (3 типа в итоге). Для сотрудников у кого не было еще инструктажей - пишет что инструктажа еще не былло, цвет - красный
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:   workers[]                   список сотрудников и данные по каждому
     *                          |---worker_id               - ключ сотрудника
     *                              |---name                - фамилия и инициалы сотрудника
     *                              |---tabel_nomer         - табельный номер
     *                              |---position            - профессия данного сотрудника
     *                              |---date                - дата последнего инструктажа / либо сообщение что инструктаж еще не проводился
     *                              |---type_notification   - тип уведомления(красный/желтый/зеленый)
     *
     * @package frontend\controllers
     *Входные обязательные параметры: company_department_id
     * @example  http://localhost/read-manager-amicum?controller=Briefing&method=GetIndicator&subscribe=&data={"company_department_id":20028766}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 30.09.2019 14:19
     */
    public static function GetIndicator($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $duration = array();  // Массив ошибок
        $type_notification = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetIndicator. Начало метода';
        try {
            $microtime_start = microtime(true);
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetIndicator. Данные с фронта не получены');
            }
            $warnings[] = 'GetIndicator. Данные успешно переданы';
            $warnings[] = 'GetIndicator. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetIndicator. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('GetIndicator. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetIndicator. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $duration['GetIndicator. Время до взятия даты '] = round(microtime(true) - $microtime_start, 6);
            $today = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
            //$today = '2019-09-15';
            //находим нижележащие участки
            $duration['GetIndicator. Время До поиска нижележащих подразделений'] = round(microtime(true) - $microtime_start, 6);
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("GetIndicator. Не смог получить список вложенных подразделений");
            }
            $duration['GetIndicator. Время после нахождения подразделений и до поиска людей '] = round(microtime(true) - $microtime_start, 6);
            //берем людей, которые работают на данном подразделении
            $workers = Worker::find()
                ->select('worker.id, worker.employee_id, employee.last_name, employee.first_name, employee.patronymic, 
                worker.tabel_number, position.title')
                ->where(['in', 'company_department_id', $company_departments])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $today],
                    ['is', 'worker.date_end', null]
                ])
                ->innerJoin('employee', 'worker.employee_id = employee.id')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->asArray()
                ->all();

            foreach ($workers as $worker) {
                $worker_ids[] = $worker['id'];
            }
            $duration['GetIndicator. Время после поиска списка людей'] = round(microtime(true) - $microtime_start, 6);

            //вытаскиваем по этим сотрудникам последние графики
            $briefings = Briefing::find()
                ->select(' briefer.worker_id, max(briefing.date_time) as max_date_briefing, worker.employee_id,
                 employee.last_name, employee.first_name, employee.patronymic, worker.tabel_number,position.title')
                ->andWhere(['in', 'briefer.worker_id', $worker_ids])
                ->andWhere(['briefing.type_briefing_id' => TypeBriefingEnumController::REPEAT])
                ->innerJoin('briefer', 'briefer.briefing_id = briefing.id')
                ->innerJoin('worker', 'briefer.worker_id = worker.id')
                ->innerJoin('employee', 'worker.employee_id = employee.id')
                ->innerJoin('position', 'worker.position_id = position.id')
                ->groupBy('briefer.worker_id')
                ->asArray()
                ->all();
            $duration['GetIndicator. Время после поиска последних инструктажей'] = round(microtime(true) - $microtime_start, 6);

            $list_briefers = array();
            // считаем индикаторы для тех, у кого есть последний инструктаж
            foreach ($briefings as $briefing) {
                array_push($list_briefers, $briefing['worker_id']);                                          //запоминаем сотрудников, которые прошли инструктаж
                $first_name = mb_substr($briefing['first_name'], 0, 1);                                                //делаем красиво фамилию и инициалы
                $patronymic = mb_substr($briefing['patronymic'], 0, 1);
                $name = "{$briefing['last_name']} {$first_name}.{$patronymic}.";
                $between_date = (strtotime($today) - strtotime($briefing['max_date_briefing'])) / (60 * 60 * 24);
                if ($between_date > self::DAY_TYPE_TWO) {                                                               // если индикатор желтый
                    $type_notification[$briefing['tabel_number']] = ['name' => $name, 'tabel_number' =>
                        $briefing['tabel_number'], 'position' => $briefing['title'],
                        'date' => date('d.m.Y', strtotime($briefing['max_date_briefing'])),
                        'type_notification' => self::TYPE_TWO];
                } else if ($between_date > self::DAY_TYPE_ONE) {                                                        //если индикатор красный
                    $type_notification[$briefing['tabel_number']] = ['name' => $name, 'tabel_number' =>
                        $briefing['tabel_number'], 'position' => $briefing['title'],
                        'date' => date('d.m.Y', strtotime($briefing['max_date_briefing'])),
                        'type_notification' => self::TYPE_ONE];
                } else {                                                                                                //если индикатор зеленый
                    $type_notification[$briefing['tabel_number']] = ['name' => $name, 'tabel_number' =>
                        $briefing['tabel_number'], 'position' => $briefing['title'],
                        'date' => date('d.m.Y', strtotime($briefing['max_date_briefing'])),
                        'type_notification' => self::TYPE_THREE];
                }
            }
            // считаем индикаторы для тех, у кого нет последнего инструктажа
            $duration['GetIndicator. Время после определения цвета идентификатора у кого были инструктажи'] = round(microtime(true) - $microtime_start, 6);
            $list_worker_without_briefing = array_diff($worker_ids, $list_briefers);
            foreach ($workers as $worker) {
                if (in_array($worker['id'], $list_worker_without_briefing)) {
                    $first_name = mb_substr($worker['first_name'], 0, 1);                                                //делаем красиво фамилию и инициалы
                    $patronymic = mb_substr($worker['patronymic'], 0, 1);
                    $name = "{$worker['last_name']} {$first_name}.{$patronymic}.";
                    $type_notification[$worker['tabel_number']] = ['name' => $name, 'tabel_number' => $worker['tabel_number'], 'position' => $worker['title'],
                        'date' => 'Инструктаж не проводился', 'type_notification' => self::TYPE_ONE];
                }
            }
            $duration['GetIndicator. Время после определения цвета идентификатора у всех'] = round(microtime(true) - $microtime_start, 6);
        } catch (Throwable $exception) {
            $errors[] = 'GetIndicator. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetIndicator. Конец метода';
        $warnings[] = $duration;
        $result = $type_notification;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetJournalBriefing - метод получения списка инструктажей
    // пример: http://127.0.0.1/read-manager-amicum?controller=Briefing&method=GetJournalBriefing&subscribe=&data={"company_department_id":802}
    public static function GetJournalBriefing($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $duration = array();                                                                                            // Расчет продолжительности выполнения метода
        $errors = array();                                                                                              // Массив ошибок
        $shift_id = null;
        $workers_by_grafic_shift = array();
        $warnings[] = 'GetJournalBriefing. Начало метода';
        try {
            $microtime_start = microtime(true);
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetJournalBriefing. Данные с фронта не получены');
            }
            $warnings[] = 'GetJournalBriefing. Данные успешно переданы';
            $warnings[] = 'GetJournalBriefing. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'GetJournalBriefing. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('GetJournalBriefing. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetJournalBriefing. Данные с фронта получены и они правильные';
            $company_department_id = $post_dec->company_department_id;


            // поиск вложенных подразделений
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("GetJournalBriefing. Не смог получить список вложенных подразделений");
            }
            $duration['GetJournalBriefing. ПОиск вложенных подразделений'] = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "GetJournalBriefing. Список вложенных департаментов получен";
            $warnings[] = $company_departments;


            // получаем текущую дату, для выборки работающих работников
            $today = BackendAssistant::GetDateFormatYMD();
            $warnings[] = "GetJournalBriefing. Текущая дата " . $today;

            $list_workers = array();
            // получение всех работников из данного подразделения
            $workers_by_department = Worker::find()
                ->select('worker.id as worker_id, worker.position_id as position_id')
                ->where(['IN', 'company_department_id', $company_departments])
                ->indexBy('worker_id')
                ->asArray()
                ->all();
            $duration['GetJournalBriefing. Поиск всех работников в данном подразделении'] = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "GetJournalBriefing. Список работников по данному подразделению";
//            $warnings[] = $workers_by_department;
            if (!$workers_by_department) {
                $result['workers_by_department'] = (object)array();
            } else {
                $result['workers_by_department'] = $workers_by_department;
            }
            //укладываем в общий список работников
            foreach ($workers_by_department as $worker) {
                $list_workers[$worker['worker_id']] = $worker['worker_id'];
            }
            unset($worker);
            unset($workers_by_department);


            // получение всех работающих работников из данного подразделения
            $workers_work_by_department = Worker::find()
                ->select('worker.id as worker_id, worker.position_id as position_id')
                ->Where(['IN', 'company_department_id', $company_departments])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $today],
                    ['is', 'worker.date_end', null]
                ])
                ->indexBy('worker_id')
                ->asArray()
                ->all();
            $duration['GetJournalBriefing. Поиск всех РАБОТАЮЩИХ работников в данном подразделении'] = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "GetJournalBriefing. Список РАБОТАЮЩИХ работников по данному подразделению";
//            $warnings[] = $workers_work_by_department;
            if (!$workers_work_by_department) {
                $result['workers_work_by_department'] = (object)array();
            } else {
                $result['workers_work_by_department'] = $workers_work_by_department;
            }
            //укладываем в общий список работников тех кто работает
            foreach ($workers_work_by_department as $worker) {
                $list_workers[$worker['worker_id']] = $worker['worker_id'];
            }
            unset($worker);
            unset($workers_work_by_department);


            // получение работников по графику выходов с учтом вложености подразделений, работающих сегодня
            $workers_by_grafic = Worker::find()
                ->select('worker.id as worker_id, worker.position_id as position_id')
                ->innerJoin('grafic_tabel_date_plan', 'grafic_tabel_date_plan.worker_id = worker.id')
                ->innerJoin('grafic_tabel_main', 'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id')
                ->Where(['grafic_tabel_date_plan.date_time' => $today])
                ->andwhere(['IN', 'grafic_tabel_main.company_department_id', $company_departments])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $today],
                    ['is', 'worker.date_end', null]
                ])
                ->indexBy('worker_id')
                ->asArray()
                ->all();
            $duration['GetJournalBriefing. Поиск всех РАБОТАЮЩИХ работников по графику'] = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "GetJournalBriefing. Список РАБОТАЮЩИХ работников по ГРАФИКУ";
//            $warnings[] = $workers_by_grafic;
            if (!$workers_by_grafic) {
                $result['workers_by_grafic'] = (object)array();
            } else {
                $result['workers_by_grafic'] = $workers_by_grafic;
            }

            if (property_exists($post_dec, 'shift_id')) {
                $shift_id = $post_dec->shift_id;
                $workers_by_grafic_shift = Worker::find()
                    ->select('worker.id as worker_id, worker.position_id as position_id')
                    ->innerJoin('grafic_tabel_date_plan', 'grafic_tabel_date_plan.worker_id = worker.id')
                    ->innerJoin('grafic_tabel_main', 'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id')
                    ->Where(['grafic_tabel_date_plan.date_time' => $today])
                    ->andwhere(['IN', 'grafic_tabel_main.company_department_id', $company_departments])
                    ->andWhere(['or',
                        ['>', 'worker.date_end', $today],
                        ['is', 'worker.date_end', null]
                    ])
                    ->andWhere(['grafic_tabel_date_plan.shift_id' => $shift_id])
                    ->indexBy('worker_id')
                    ->asArray()
                    ->all();
            }


            $duration['GetJournalBriefing. Поиск всех РАБОТАЮЩИХ работников по графику на текущую смену'] = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "GetJournalBriefing. Список РАБОТАЮЩИХ работников по ГРАФИКУ на текущую смену";
//            $warnings[] = $workers_by_grafic;
            if (!$workers_by_grafic_shift) {
                $result['workers_by_grafic_shift'] = (object)array();
            } else {
                $result['workers_by_grafic_shift'] = $workers_by_grafic_shift;
            }

            //укладываем в общий список работников тех кто в графике
            foreach ($workers_by_grafic as $worker) {
                $list_workers[$worker['worker_id']] = $worker['worker_id'];
            }
            unset($worker);
            unset($workers_by_grafic);

            // создаем линейный массив для поиска в базе без повторения айдишников
            foreach ($list_workers as $worker) {
                $workers[] = $worker;
            }
            unset($list_workers);
            unset($worker);

            if (!isset($workers)) {
                $workers = [];
                //                throw new Exception("GetJournalBriefing. Список всех работников в департаменте пуст, список работающих работников в департаменте пуст, и в графике выходов пуст");
            }
            $duration['GetJournalBriefing. Создал список работников для поиска инструктажей'] = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "GetJournalBriefing. Список Работников для поиска инструктажей";


            // ищем статусы инструктажей для каждого работника
            $briefers_db = Briefer::find()
                ->joinWith('briefing.attachment')
                ->joinWith('briefing.kindFirePrevention')
                ->where(['IN', 'briefer.worker_id', $workers])
                ->orderBy(['briefing.date_time' => SORT_ASC])
                ->all();
            foreach ($briefers_db as $briefer) {
                $worker_id = $briefer->worker_id;
                $briefers[$worker_id]['type_briefing_id'] = $briefer->briefing->type_briefing_id;
//                $briefers[$worker_id]['kind_fire_prevention_instruction_id'] = $briefer->briefing->kind_fire_prevention_instruction_id;
                $briefers[$worker_id]['briefing_date_time'] = date("d.m.Y", strtotime($briefer->briefing->date_time));
                $briefers[$worker_id]['instructor_id'] = $briefer->briefing->instructor_id;
                $briefers[$worker_id]['company_department_id'] = $briefer->briefing->company_department_id;
                $briefers[$worker_id]['instructor_status_id'] = $briefer->briefing->status_id;
                $briefers[$worker_id]['briefing_reason'] = $briefer->briefing->briefing_reason;
                $briefers[$worker_id]['briefing_reason_id'] = $briefer->briefing->briefing_reason_id;
                $briefers[$worker_id]['document_id'] = $briefer->briefing->document_id;
                $briefers[$worker_id]['instructor_position_id'] = $briefer->briefing->instructor_position_id;
                $briefers[$worker_id]['briefing_id'] = $briefer->briefing->id;
                $briefers[$worker_id]['briefer_id'] = $briefer->id;
                $briefers[$worker_id]['worker_id'] = $briefer->worker_id;
                $briefers[$worker_id]['position_id'] = $briefer->position_id;
                $briefers[$worker_id]['briefer_status_id'] = $briefer->status_id;
                $briefers[$worker_id]['kind_fire_prevention_id'] = $briefer->briefing->kind_fire_prevention_id;
                if (isset($briefer->briefing->kindFirePrevention->title)) {
                    $kind_fire_prevention_title = $briefer->briefing->kindFirePrevention->title;
                } else {
                    $kind_fire_prevention_title = null;
                }
                $briefers[$worker_id]['kind_fire_prevention_title'] = $kind_fire_prevention_title;
                $briefers[$worker_id]['attachment'] = array();
                if (empty($briefer->briefing->attachment)) {
                    $briefers[$worker_id]['attachment']['attachment_path'] = '';
                    $briefers[$worker_id]['attachment']['attachment_id'] = -1;
                    $briefers[$worker_id]['attachment']['attachment_blob'] = (object)array();
                    $briefers[$worker_id]['attachment']['title'] = '';
                    $briefers[$worker_id]['attachment']['attachment_type'] = '';
                    $briefers[$worker_id]['attachment']['sketch'] = (object)array();
                    $briefers[$worker_id]['attachment']['attachment_status'] = '';
                } else {
                    $briefers[$worker_id]['attachment']['attachment_path'] = $briefer->briefing->attachment->path;
                    $briefers[$worker_id]['attachment']['attachment_id'] = $briefer->briefing->attachment->id;
                    $briefers[$worker_id]['attachment']['attachment_blob'] = (object)array();
                    $briefers[$worker_id]['attachment']['title'] = $briefer->briefing->attachment->title;
                    $briefers[$worker_id]['attachment']['attachment_type'] = $briefer->briefing->attachment->attachment_type;
                    $briefers[$worker_id]['attachment']['sketch'] = $briefer->briefing->attachment->sketch;
                    $briefers[$worker_id]['attachment']['attachment_status'] = "";
                }
                $briefers[$worker_id]['internship'] = array();
                $briefers[$worker_id]['internship']['internship_reason_id'] = $briefer['internship_reason_id'];
                $briefers[$worker_id]['internship']['internship_worker_id'] = $briefer['internship_worker_id'];
                $briefers[$worker_id]['internship']['internship_position_id'] = $briefer['internship_position_id'];
                $briefers[$worker_id]['internship']['internship_taken_status_id'] = $briefer['internship_taken_status_id'];
                $briefers[$worker_id]['internship']['internship_end_status_id'] = $briefer['internship_end_status_id'];
                $briefers[$worker_id]['internship']['duration_day'] = $briefer['duration_day'];
                if ($briefer['internship_end_fact_date']) {
                    $briefers[$worker_id]['internship']['internship_end_fact_date'] = $briefer['internship_end_fact_date'];
                    $briefers[$worker_id]['internship']['internship_end_fact_date_format'] = date("d.m.Y", strtotime($briefer['internship_end_fact_date']));
                } else {
                    $briefers[$worker_id]['internship']['internship_end_fact_date'] = null;
                    $briefers[$worker_id]['internship']['internship_end_fact_date_format'] = null;
                }
                if ($briefer['internship_start']) {
                    $briefers[$worker_id]['internship']['internship_start'] = $briefer['internship_start'];
                    $briefers[$worker_id]['internship']['internship_start_format'] = date("d.m.Y", strtotime($briefer['internship_start']));
                } else {
                    $briefers[$worker_id]['internship']['internship_start'] = null;
                    $briefers[$worker_id]['internship']['internship_start_format'] = null;
                }
                if ($briefer['internship_end']) {
                    $briefers[$worker_id]['internship']['internship_end'] = $briefer['internship_start'];
                    $briefers[$worker_id]['internship']['internship_end_format'] = date("d.m.Y", strtotime($briefer['internship_end']));
                } else {
                    $briefers[$worker_id]['internship']['internship_end'] = null;
                    $briefers[$worker_id]['internship']['internship_end_format'] = null;
                }


                if ($briefer->date_time) {
                    $briefers[$worker_id]['date_time'] = date("d.m.Y", strtotime($briefer->date_time));
                } else {
                    $briefers[$worker_id]['date_time'] = null;
                }
                if ($briefer->date_time_first) {
                    $briefers[$worker_id]['date_time_first'] = date("d.m.Y", strtotime($briefer->date_time_first));
                } else {
                    $briefers[$worker_id]['date_time_first'] = null;
                }
                if ($briefer->date_time_second) {
                    $briefers[$worker_id]['date_time_second'] = date("d.m.Y", strtotime($briefer->date_time_second));
                } else {
                    $briefers[$worker_id]['date_time_second'] = null;
                }
                if ($briefer->date_time_third) {
                    $briefers[$worker_id]['date_time_third'] = date("d.m.Y", strtotime($briefer->date_time_third));
                } else {
                    $briefers[$worker_id]['date_time_third'] = null;
                }
            }

            if (!isset($briefers)) {
                $result['briefers'] = (object)array();
            } else {
                $result['briefers'] = $briefers;
            }
            $duration['GetJournalBriefing. список статусов инструктажей получен'] = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "GetJournalBriefing. список статусов инструктажей получен";
//            $warnings[] = $briefers;
            unset($briefers);

            // ищем сами инструктажи и работников в них
            $briefings = Briefing::find()
                ->joinWith('attachment')
                ->joinWith('briefers')
                ->joinWith('kindFirePrevention')
                ->Where(['IN', 'company_department_id', $company_departments])
                ->orderBy(['briefing.date_time' => SORT_DESC])
                ->all();

            // формируем структуру инструктажей
            foreach ($briefings as $briefing) {
                $type_briefing_id = $briefing['type_briefing_id'];
                $date_time_brif = date("d.m.Y", strtotime($briefing['date_time']));
                $briefing_id = $briefing['id'];
                $briefing_res[$type_briefing_id]['type_briefing_id'] = $type_briefing_id;
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['date_time'] = $date_time_brif;
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['briefing_id'] = $briefing_id;
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['instructor_status_id'] = $briefing['status_id'];
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['document_id'] = $briefing['document_id'];
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['company_department_id'] = $briefing['company_department_id'];
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['instructor_id'] = $briefing['instructor_id'];
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['instructor_position_id'] = $briefing['instructor_position_id'];
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['briefing_reason'] = $briefing['briefing_reason'];
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['briefing_reason_id'] = $briefing['briefing_reason_id'];
                $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['kind_fire_prevention_id'] = $briefing['kind_fire_prevention_id'];
                if (isset($briefing['kindFirePrevention']['title'])) {
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['kind_fire_prevention_title'] = $briefing['kindFirePrevention']['title'];
                } else {
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['kind_fire_prevention_title'] = "";
                }


                //проверяем наличие вложения
                if (!$briefing->attachment) {
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_path'] = "";
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_id'] = -1;
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_blob'] = (object)array();
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['title'] = "";
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_type'] = "";
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['sketch'] = (object)array();
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_status'] = "";
                } else {
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_id'] = $briefing->attachment->id;
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_path'] = $briefing->attachment->path;
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_blob'] = (object)array();
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['title'] = $briefing->attachment->title;
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_type'] = $briefing->attachment->attachment_type;
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['sketch'] = $briefing->attachment->sketch;
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['attachment']['attachment_status'] = "";
                }

                // формируем список инструктируемых в рамках инструктажей
                foreach ($briefing->briefers as $briefer) {
                    $worker_id = $briefer['worker_id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['briefer_id'] = $briefer['id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['worker_id'] = $briefer['worker_id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['position_id'] = $briefer['position_id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['briefer_status_id'] = $briefer['status_id'];
                    if ($briefer['date_time']) {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['date_time'] = date("d.m.Y H:i:s", strtotime($briefer['date_time']));
                    } else {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['date_time'] = null;
                    }
                    if ($briefer['date_time_first']) {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['date_time_first'] = date("d.m.Y", strtotime($briefer['date_time_first']));
                    } else {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['date_time_first'] = null;
                    }
                    if ($briefer['date_time_second']) {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['date_time_second'] = date("d.m.Y", strtotime($briefer['date_time_second']));
                    } else {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['date_time_second'] = null;
                    }
                    if ($briefer['date_time_third']) {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['date_time_third'] = date("d.m.Y", strtotime($briefer['date_time_third']));
                    } else {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['date_time_third'] = null;
                    }
                    // блок стажировки
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_reason_id'] = $briefer['internship_reason_id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_worker_id'] = $briefer['internship_worker_id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_position_id'] = $briefer['internship_position_id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_taken_status_id'] = $briefer['internship_taken_status_id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end_status_id'] = $briefer['internship_end_status_id'];
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['duration_day'] = $briefer['duration_day'];

                    if ($briefer['internship_end_fact_date']) {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end_fact_date'] = $briefer['internship_end_fact_date'];
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end_fact_date_format'] = date("d.m.Y", strtotime($briefer['internship_end_fact_date']));
                    } else {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end_fact_date'] = null;
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end_fact_date_format'] = null;
                    }
                    if ($briefer['internship_start']) {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_start'] = $briefer['internship_start'];
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_start_format'] = date("d.m.Y", strtotime($briefer['internship_start']));
                    } else {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_start'] = null;
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_start_format'] = null;
                    }
                    if ($briefer['internship_end']) {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end'] = $briefer['internship_start'];
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end_format'] = date("d.m.Y", strtotime($briefer['internship_end']));
                    } else {
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end'] = null;
                        $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'][$worker_id]['internship']['internship_end_format'] = null;
                    }

                }

                if (!isset($briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'])) {
                    $briefing_res[$type_briefing_id]['date_times'][$date_time_brif]['briefings'][$briefing_id]['workers'] = (object)array();
                }
            }
            if (!isset($briefing_res)) {
                $result['briefings'] = (object)array();
            } else {
                $result['briefings'] = $briefing_res;
            }
            $duration['GetJournalBriefing. список инструктажей получен'] = round(microtime(true) - $microtime_start, 6);
            $warnings[] = "GetJournalBriefing. список инструктажей получен";
//            $warnings[] = $briefing_res;
            unset($briefing_res);
        } catch (Throwable $exception) {
            $errors[] = 'GetJournalBriefing. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $duration["GetJournalBriefing. Закончил выполнять метод"] = round(microtime(true) - $microtime_start, 6);
        $warnings[] = $duration;
        $warnings[] = 'GetJournalBriefing. Конец метода';
        if (!isset($result)) {
            $result = (object)array();
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'duration' => $duration);
        return $result_main;
    }

    // GetListDocumentBriefing - получить список видов документов ПБ в несчастных случаях
    // пример: http://127.0.0.1/read-manager-amicum?controller=Briefing&method=GetListDocumentBriefing&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListDocumentBriefing($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $document_list = Document::find()
                ->joinWith('documentAttachments')
                ->joinWith('vidDocument')
                ->joinWith('documentAttachments.attachment')
                ->limit(20000)
//                ->where(['vid_document_id'=>20])                                                                        // ключ инструктажей
                ->all();
            foreach ($document_list as $document) {
                $warnings[] = $document;
                $briefing_document_list[$document->id]['document_id'] = $document->id;
                $briefing_document_list[$document->id]['parent_document_id'] = $document->parent_document_id;
                $briefing_document_list[$document->id]['title'] = $document->title;
                $briefing_document_list[$document->id]['date_start'] = date("d.m.Y", strtotime($document->date_start));
                $briefing_document_list[$document->id]['date_end'] = date("d.m.Y", strtotime($document->date_end));
                $briefing_document_list[$document->id]['status_id'] = $document->status_id;
                $briefing_document_list[$document->id]['vid_document_id'] = $document->vid_document_id;
                $briefing_document_list[$document->id]['vid_document_title'] = $document->vidDocument->title;
                $briefing_document_list[$document->id]['jsondoc'] = $document->jsondoc;
                foreach ($document->documentAttachments as $documentAttachment) {
                    $briefing_document_list[$document->id]['document_attachment_id'] = $documentAttachment->id;
                    $briefing_document_list[$document->id]['attachment_id'] = $documentAttachment->attachment_id;
                    if ($documentAttachment->attachment) {
                        $briefing_document_list[$document->id]['path'] = $documentAttachment->attachment->path;
                        $briefing_document_list[$document->id]['attachment_title'] = $documentAttachment->attachment->title;
                        $briefing_document_list[$document->id]['attachment_type'] = $documentAttachment->attachment->attachment_type;
                    } else {
                        $briefing_document_list[$document->id]['path'] = "";
                        $briefing_document_list[$document->id]['attachment_title'] = "";
                        $briefing_document_list[$document->id]['attachment_type'] = "";
                    }
                    $briefing_document_list[$document->id]['sketch'] = "";
                    $briefing_document_list[$document->id]['attachment_blob'] = null;
                    $briefing_document_list[$document->id]['attachment_status'] = "";

                }
                if (!isset($briefing_document_list[$document->id]['document_attachment_id'])) {
                    $briefing_document_list[$document->id]['document_attachment_id'] = null;
                    $briefing_document_list[$document->id]['attachment_id'] = null;
                    $briefing_document_list[$document->id]['path'] = "";
                    $briefing_document_list[$document->id]['attachment_title'] = "";
                    $briefing_document_list[$document->id]['attachment_type'] = "";
                    $briefing_document_list[$document->id]['sketch'] = "";
                    $briefing_document_list[$document->id]['attachment_blob'] = null;
                    $briefing_document_list[$document->id]['attachment_status'] = "";
                }
            }

            if (!isset($briefing_document_list)) {
                $warnings[] = 'GetListDocumentBriefing. Справочник видов документов пуст';
                $result = (object)array();
            } else {
                $result = $briefing_document_list;
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetListDocumentBriefing. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // http://localhost/read-manager-amicum?controller=Briefing&method=SaveDocumentPB&subscribe=&data={}

    /**
     * Метод SaveDocumentPB() - Метод сохранения нормативного документа в БД
     * ps/ если такой инструктаж уже существует - то берется его ключ.
     *
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:
     *
     * @package frontend\controllers
     *
     *
     * @author Якимов М.Н.
     * Created date: on 30.09.2019
     */
    public static function SaveDocumentPB($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                               // Промежуточный результирующий массив
        $session = \Yii::$app->session;
        $warnings[] = 'SaveDocumentPB. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveDocumentPB. Данные с фронта не получены');
            }
            $warnings[] = 'SaveDocumentPB. Данные успешно переданы';
            $warnings[] = 'SaveDocumentPB. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = 'SaveDocumentPB. Декодировал входные параметры';

            if (
                !property_exists($post_dec, 'document')
            ) {
                throw new Exception('SaveDocumentPB. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'SaveDocumentPB. Данные с фронта получены';

            //Входные данные переводим в переменне
            $document_obj = $post_dec->document;

            $new_document_id = $document_obj->document_id;
            $new_document = Document::findOne(['id' => $new_document_id]);
            if (!$new_document) {
                $new_document = new Document();
                $warnings[] = 'SaveDocumentPB. Такой документ не существовал';
            } else {
                $warnings[] = 'SaveDocumentPB. Такой документ уже существовал';
            }

            $new_document->worker_id = $session['worker_id'];
            $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $new_document->number_document = substr(str_shuffle($permitted_chars), 0, 6);
            $new_document->parent_document_id = $document_obj->parent_document_id;
            $new_document->title = $document_obj->title;
            $new_document->date_start = date("Y-m-d", strtotime($document_obj->date_start));
            $new_document->date_end = date("Y-m-d", strtotime($document_obj->date_end));
            $new_document->status_id = $document_obj->status_id;
            $new_document->vid_document_id = $document_obj->vid_document_id;
            $new_document->jsondoc = $document_obj->jsondoc;
            //сохраняем документ
            if ($new_document->save()) {                                                                       //сохранение
                $warnings[] = 'SaveDocumentPB. Успешное сохранение повторного инструктажа';
                $new_document->refresh();
                $new_document_id = $new_document->id;
                $document_obj->document_id = $new_document_id;
            } else {
                $errors[] = $new_document->errors;
                throw new Exception('SaveDocumentPB. Ошибка при сохранении  документа Document');
            }
            $new_attachment_id = null;
            if ($document_obj->attachment_id) {
                $attachment_id = $document_obj->attachment_id;
                if ($document_obj->attachment_status == "del") {
                    Attachment::deleteAll(['id' => $attachment_id]);
                    DocumentAttachment::deleteAll(['attachment_id' => $attachment_id]);
                    $new_attachment_id = null;
                    $document_obj->document_attachment_id = null;
                    $document_obj->attachment_id = null;
                    $document_obj->path = "";
                    $document_obj->attachment_type = "";
                    $document_obj->sketch = null;
                    $document_obj->attachment_blob = null;
                    $document_obj->attachment_title = "";
                } else {
                    $new_attachment = Attachment::findOne(['id' => $attachment_id]);
                    if (!$new_attachment and $document_obj->attachment_blob != "") {
                        $new_attachment = new Attachment();
                        $path = Assistant::UploadFile($document_obj->attachment_blob, $document_obj->attachment_title, 'attachment', $document_obj->attachment_type);
                        $new_attachment->path = $path;
                        $new_attachment->date = BackendAssistant::GetDateFormatYMD();
                        $new_attachment->worker_id = $session['worker_id'];
                        $new_attachment->section_title = 'ОТ и ПБ/Нормативный документа';
                        $new_attachment->title = $document_obj->attachment_title;
                        $new_attachment->attachment_type = $document_obj->attachment_type;
                        $new_attachment->sketch = $document_obj->sketch;
                        if ($new_attachment->save()) {
                            $new_attachment->refresh();
                            $new_attachment_id = $new_attachment->id;
                            $document_obj->attachment_id = $new_attachment_id;
                            $document_obj->path = $path;
                            $warnings[] = 'SaveDocumentPB. Данные успешно сохранены в модель Attachment';
                        } else {
                            $errors[] = $new_attachment->errors;
                            throw new Exception('SaveDocumentPB. Ошибка сохранения модели Attachment');
                        }
                    } else if ($document_obj->attachment_blob == "") {
                        $warnings[] = "SaveDocumentPB. вложение не существует ";
                        $new_attachment_id = null;
                    } else {
                        $warnings[] = "SaveDocumentPB. вложение уже было ";
                        $new_attachment_id = $attachment_id;
                    }
                }
            }

            if ($new_attachment_id) {
                $new_document_attachment = DocumentAttachment::findOne(['attachment_id' => $new_attachment_id, 'document_id' => $new_document_id]);
                if (!$new_document_attachment) {
                    $new_document_attachment = new DocumentAttachment();
                }
                $new_document_attachment->attachment_id = $new_attachment_id;
                $new_document_attachment->document_id = $new_document_id;
                if ($new_document_attachment->save()) {
                    $new_document_attachment->refresh();
                    $new_document_attachment_id = $new_document_attachment->id;
                    $document_obj->document_attachment_id = $new_document_attachment_id;
                    $warnings[] = 'SaveDocumentPB. Данные успешно сохранены в модель DocumentAttachment';
                } else {
                    $errors[] = $new_document_attachment->errors;
                    throw new Exception('SaveDocumentPB. Ошибка сохранения модели DocumentAttachment');
                }
            }
            $result = $document_obj;

        } catch (Throwable $exception) {
            $errors[] = 'SaveDocumentPB. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SaveDocumentPB. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetHandbookKindFirePrevention() - Справочник видов инструктажей
     * @return array - [kind_fire_prevention_instruction_id]
     *                                  id:
     *                                  title:
     *
     * @package frontend\controllers
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=Briefing&method=GetHandbookKindFirePreventionInstruction&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.10.2019 9:17
     */
    public static function GetHandbookKindFirePreventionInstruction()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetHandbookKindFirePreventionInstruction. Начало метода';
        try {
            $result = KindFirePreventionInstruction::find()
                ->select(['id', 'title'])
                ->asArray()
                ->indexBy('id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetHandbookKindFirePreventionInstruction. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetHandbookKindFirePreventionInstruction. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetHandbookTypeAccident() - Метод получения справочника видов происшествий приведших к несчастным случаям на производстве
     * @return array - массив со структурой: [kind_accident_id]
     *                                                  kind_accident_id:
     *                                                  kind_accident_title:
     *                                                  [types]
     *                                                       type_accident_id:
     *                                                       type_accident_title:
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Briefing&method=GetHandbookTypeAccident&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.11.2019 9:50
     */
    public static function GetHandbookTypeAccident()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $kind_accident_by_parent_kind_accident = array();
        $list_kind_accident = array();
        $warnings[] = 'GetHandbookTypeAccident. Начало метода';
        try {
            /*
             * Получаем все родительские классификаторы
             */
            $accidents = KindAccident::find()
                ->where('parent_id is null')
                ->asArray()
                ->all();

            /*
             * Получаем все классификаторы у которых есть родитель
             */
            $attachment_kind_accidents = KindAccident::find()
                ->indexBy('id')
                ->where('parent_id is not null')
                ->asArray()
                ->all();

            /*
             * Перебор классификаторов у которых есть родитель формируя структуру: ид родителя
             *                                                                          объекты всех детей родителя
             */
            foreach ($attachment_kind_accidents as $attachment_kind_accident) {
                $kind_accident_by_parent_kind_accident[$attachment_kind_accident['parent_id']][] = $attachment_kind_accident;
            }
            unset($accident);
            unset($attachment_kind_accidents);

            /*
             * Перебор родителей классификаторов с целью формирования конечного массива
             */
            foreach ($accidents as $accident) {
                /*
                 * Передаём объект родителя и всех детей
                 */
                $list_kind_accident[] = self::AccidentParent($accident, $kind_accident_by_parent_kind_accident);
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetHandbookTypeAccident. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        if (empty($list_kind_accident)) {
            $result = (object)array();
        } else {
            $result = $list_kind_accident;
        }
        $warnings[] = 'GetHandbookTypeAccident. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод AccidentParent() - Используется в методе GetHandbookTypeAccident для получения рекурсивного справочника
     * @param $arr
     * @return mixed
     *
     * @package frontend\controllers
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.11.2019 15:44
     */
    public static function AccidentParent($accident, $kind_accident_by_parent_kind_accident)
    {
        $list_kind_accident['id'] = $accident['id'];
        $list_kind_accident['title'] = $accident['title'];
        $list_kind_accident['is_chosen'] = 2;
        /*
         * Если у родителя есть дети то получаем всех детей
         */
        if (isset($kind_accident_by_parent_kind_accident[$accident['id']])) {
            /*
             * Перебор детей родителя по типу "дерево"
             * Используется рекурсивный вызов этой же функции в которую передётся объект классификатора и дети родителей
             * для того чтобы получить структуру дерева
             */
            foreach ($kind_accident_by_parent_kind_accident[$accident['id']] as $child_accident) {
                $list_kind_accident['children'][] = self::AccidentParent($child_accident, $kind_accident_by_parent_kind_accident);

            }
        }
        /*
         * Возвращаем итоговую структуру дерева (родитель, дети)
         */
        return $list_kind_accident;
    }

    // GetBriefingReason()      - Получение справочника причин инструктажей
    // SaveBriefingReason()     - Сохранение справочника причин инструктажей
    // DeleteBriefingReason()   - Удаление справочника причин инструктажей

    /**
     * Метод GetBriefingReason() - Получение справочника причин инструктажей
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "parent_id":"-1",                // ключ родителя
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=GetBriefingReason&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetBriefingReason()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetBriefingReason';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_briefing_reason = BriefingReason::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_briefing_reason)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник причин инструктажей пуст';
            } else {
                $result = $handbook_briefing_reason;
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

    /**
     * Метод SaveBriefingReason() - Сохранение справочника причин инструктажей
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "briefing_reason":
     *  {
     *      "briefing_reason_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "parent_id":"-1",                // ключ родителя
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "briefing_reason_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "parent_id":"-1",                // ключ родителя
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=SaveBriefingReason&subscribe=&data={"briefing_reason":{"briefing_reason_id":-1,"title":"ACTION","parent_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveBriefingReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveBriefingReason';
        $handbook_briefing_reason_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'briefing_reason'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_briefing_reason_id = $post_dec->briefing_reason->briefing_reason_id;
            $title = $post_dec->briefing_reason->title;
            $parent_id = $post_dec->briefing_reason->parent_id;
            $new_handbook_briefing_reason_id = BriefingReason::findOne(['id' => $handbook_briefing_reason_id]);
            if (empty($new_handbook_briefing_reason_id)) {
                $new_handbook_briefing_reason_id = new BriefingReason();
            }
            $new_handbook_briefing_reason_id->title = $title;
            $new_handbook_briefing_reason_id->parent_id = $parent_id;
            if ($new_handbook_briefing_reason_id->save()) {
                $new_handbook_briefing_reason_id->refresh();
                $handbook_briefing_reason_data['briefing_reason_id'] = $new_handbook_briefing_reason_id->id;
                $handbook_briefing_reason_data['title'] = $new_handbook_briefing_reason_id->title;
                $handbook_briefing_reason_data['parent_id'] = $new_handbook_briefing_reason_id->parent_id;
            } else {
                $errors[] = $new_handbook_briefing_reason_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника причин инструктажей');
            }
            unset($new_handbook_briefing_reason_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_briefing_reason_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteBriefingReason() - Удаление справочника причин инструктажей
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "briefing_reason_id": 98             // идентификатор справочника причин инструктажей
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=DeleteBriefingReason&subscribe=&data={"briefing_reason_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteBriefingReason($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteBriefingReason';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'briefing_reason_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_briefing_reason_id = $post_dec->briefing_reason_id;
            $del_handbook_briefing_reason = BriefingReason::deleteAll(['id' => $handbook_briefing_reason_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // GetKindAccident()      - Получение справочника видов несчастных случаев
    // SaveKindAccident()     - Сохранение справочника видов несчастных случаев
    // DeleteKindAccident()   - Удаление справочника видов несчастных случаев

    /**
     * Метод GetKindAccident() - Получение справочника видов несчастных случаев
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=GetKindAccident&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetKindAccident()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindAccident';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_kind_accident = KindAccident::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_kind_accident)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник видов несчастных случаев пуст';
            } else {
                $result = $handbook_kind_accident;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveKindAccident() - Сохранение справочника видов несчастных случаев
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_accident":
     *  {
     *      "kind_accident_id":-1,           // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "parent_id":"-1",                // ключ родителя
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_accident_id":-1,           // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "parent_id":"-1",                // ключ родителя
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=SaveKindAccident&subscribe=&data={"kind_accident":{"kind_accident_id":-1,"title":"ACTION","parent_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveKindAccident($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindAccident';
        $handbook_kind_accident_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_accident'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_kind_accident_id = $post_dec->kind_accident->kind_accident_id;
            $title = $post_dec->kind_accident->title;
            $parent_id = $post_dec->kind_accident->parent_id;
            $new_handbook_kind_accident_id = KindAccident::findOne(['id' => $handbook_kind_accident_id]);
            if (empty($new_handbook_kind_accident_id)) {
                $new_handbook_kind_accident_id = new KindAccident();
            }
            $new_handbook_kind_accident_id->title = $title;
            $new_handbook_kind_accident_id->parent_id = $parent_id;
            if ($new_handbook_kind_accident_id->save()) {
                $new_handbook_kind_accident_id->refresh();
                $handbook_kind_accident_data['kind_accident_id'] = $new_handbook_kind_accident_id->id;
                $handbook_kind_accident_data['title'] = $new_handbook_kind_accident_id->title;
                $handbook_kind_accident_data['parent_id'] = $new_handbook_kind_accident_id->parent_id;
            } else {
                $errors[] = $new_handbook_kind_accident_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника видов несчастных случаев');
            }
            unset($new_handbook_kind_accident_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_kind_accident_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteKindAccident() - Удаление справочника видов несчастных случаев
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_accident_id": 98             // идентификатор справочника видов несчастных случаев
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=DeleteKindAccident&subscribe=&data={"kind_accident_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteKindAccident($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindAccident';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_accident_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_kind_accident_id = $post_dec->kind_accident_id;
            $del_handbook_kind_accident = KindAccident::deleteAll(['id' => $handbook_kind_accident_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // GetKindIncident()      - Получение справочника видов инцидентов
    // SaveKindIncident()     - Сохранение справочника видов инцидентов
    // DeleteKindIncident()   - Удаление справочника видов инцидентов

    /**
     * Метод GetKindIncident() - Получение справочника видов инцидентов
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=GetKindIncident&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetKindIncident()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindIncident';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_kind_incident = KindIncident::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_kind_incident)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник видов инцидентов пуст';
            } else {
                $result = $handbook_kind_incident;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveKindIncident() - Сохранение справочника видов инцидентов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_incident":
     *  {
     *      "kind_incident_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_incident_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=SaveKindIncident&subscribe=&data={"kind_incident":{"kind_incident_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveKindIncident($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindIncident';
        $handbook_kind_incident_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_incident'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_kind_incident_id = $post_dec->kind_incident->kind_incident_id;
            $title = $post_dec->kind_incident->title;
            $new_handbook_kind_incident_id = KindIncident::findOne(['id' => $handbook_kind_incident_id]);
            if (empty($new_handbook_kind_incident_id)) {
                $new_handbook_kind_incident_id = new KindIncident();
            }
            $new_handbook_kind_incident_id->title = $title;
            if ($new_handbook_kind_incident_id->save()) {
                $new_handbook_kind_incident_id->refresh();
                $handbook_kind_incident_data['kind_incident_id'] = $new_handbook_kind_incident_id->id;
                $handbook_kind_incident_data['title'] = $new_handbook_kind_incident_id->title;
            } else {
                $errors[] = $new_handbook_kind_incident_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника видов инцидентов');
            }
            unset($new_handbook_kind_incident_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_kind_incident_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteKindIncident() - Удаление справочника видов инцидентов
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_incident_id": 98             // идентификатор справочника видов инцидентов
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=DeleteKindIncident&subscribe=&data={"kind_incident_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteKindIncident($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindIncident';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_incident_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_kind_incident_id = $post_dec->kind_incident_id;
            $del_handbook_kind_incident = KindIncident::deleteAll(['id' => $handbook_kind_incident_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // GetTypeAccident()      - Получение справочника типов несчастных случаев
    // SaveTypeAccident()     - Сохранение справочника типов несчастных случаев
    // DeleteTypeAccident()   - Удаление справочника типов несчастных случаев

    /**
     * Метод GetTypeAccident() - Получение справочника типов несчастных случаев
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ несчастного случая
     *      "title":"ACTION",                // название несчастного случая
     *      "kind_accident_id":"-1",         // название вида несчастного случая
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=GetTypeAccident&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetTypeAccident()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetTypeAccident';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_type_accident = TypeAccident::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_type_accident)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник типов несчастных случаев пуст';
            } else {
                $result = $handbook_type_accident;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveTypeAccident() - Сохранение справочника типов несчастных случаев
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "type_accident":
     *  {
     *      "type_accident_id":-1,           // ключ типа несчастного случая
     *      "title":"ACTION",                // название несчастного случая
     *      "kind_accident_id":"-1",         // название вида несчастного случая
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "type_accident_id":-1,           // ключ типа несчастного случая
     *      "title":"ACTION",                // название несчастного случая
     *      "kind_accident_id":"-1",         // название вида несчастного случая
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=SaveTypeAccident&subscribe=&data={"type_accident":{"type_accident_id":-1,"title":"ACTION","kind_accident_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveTypeAccident($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveTypeAccident';
        $handbook_type_accident_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_accident'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_accident_id = $post_dec->type_accident->type_accident_id;
            $title = $post_dec->type_accident->title;
            $kind_accident_id = $post_dec->type_accident->kind_accident_id;
            $new_handbook_type_accident_id = TypeAccident::findOne(['id' => $handbook_type_accident_id]);
            if (empty($new_handbook_type_accident_id)) {
                $new_handbook_type_accident_id = new TypeAccident();
            }
            $new_handbook_type_accident_id->title = $title;
            $new_handbook_type_accident_id->kind_accident_id = $kind_accident_id;
            if ($new_handbook_type_accident_id->save()) {
                $new_handbook_type_accident_id->refresh();
                $handbook_type_accident_data['type_accident_id'] = $new_handbook_type_accident_id->id;
                $handbook_type_accident_data['title'] = $new_handbook_type_accident_id->title;
                $handbook_type_accident_data['kind_accident_id'] = $new_handbook_type_accident_id->kind_accident_id;
            } else {
                $errors[] = $new_handbook_type_accident_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника типов несчастных случаев');
            }
            unset($new_handbook_type_accident_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_type_accident_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteTypeAccident() - Удаление справочника типов несчастных случаев
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "type_accident_id": 98             // идентификатор справочника типов несчастных случаев
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=DeleteTypeAccident&subscribe=&data={"type_accident_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteTypeAccident($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteTypeAccident';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_accident_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_accident_id = $post_dec->type_accident_id;
            $del_handbook_type_accident = TypeAccident::deleteAll(['id' => $handbook_type_accident_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // GetTypeBriefing()      - Получение справочника типов инструктажей
    // SaveTypeBriefing()     - Сохранение справочника типов инструктажей
    // DeleteTypeBriefing()   - Удаление справочника типов инструктажей

    /**
     * Метод GetTypeBriefing() - Получение справочника типов инструктажей
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=GetTypeBriefing&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetTypeBriefing()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetTypeBriefing';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_type_briefing = TypeBriefing::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_type_briefing)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник типов инструктажей пуст';
            } else {
                $result = $handbook_type_briefing;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveTypeBriefing() - Сохранение справочника типов инструктажей
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "type_briefing":
     *  {
     *      "type_briefing_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "type_briefing_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=SaveTypeBriefing&subscribe=&data={"type_briefing":{"type_briefing_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveTypeBriefing($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveTypeBriefing';
        $handbook_type_briefing_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_briefing'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_briefing_id = $post_dec->type_briefing->type_briefing_id;
            $title = $post_dec->type_briefing->title;
            $new_handbook_type_briefing_id = TypeBriefing::findOne(['id' => $handbook_type_briefing_id]);
            if (empty($new_handbook_type_briefing_id)) {
                $new_handbook_type_briefing_id = new TypeBriefing();
            }
            $new_handbook_type_briefing_id->title = $title;
            if ($new_handbook_type_briefing_id->save()) {
                $new_handbook_type_briefing_id->refresh();
                $handbook_type_briefing_data['type_briefing_id'] = $new_handbook_type_briefing_id->id;
                $handbook_type_briefing_data['title'] = $new_handbook_type_briefing_id->title;
            } else {
                $errors[] = $new_handbook_type_briefing_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника типов инструктажей');
            }
            unset($new_handbook_type_briefing_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $handbook_type_briefing_data, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteTypeBriefing() - Удаление справочника типов инструктажей
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "type_briefing_id": 98             // идентификатор справочника типов инструктажей
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=Briefing&method=DeleteTypeBriefing&subscribe=&data={"type_briefing_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteTypeBriefing($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteTypeBriefing';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'type_briefing_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_type_briefing_id = $post_dec->type_briefing_id;
            $del_handbook_type_briefing = TypeBriefing::deleteAll(['id' => $handbook_type_briefing_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /** GetBriefings - Метод получения инструктажей работников по дате и типу инструктажа
     * используется для аналитики неудачных прохождений предсменного экзаменатора
     * @param array $workers - массив ключей работников
     * @param $type_briefing_id - ключ типа инструктажа
     * @param $date_start - дата начала выборки
     * @param $date_end - дата окончания выборки
     * @return array|null[]
     */
    public static function GetBriefings($workers, $date_start, $date_end, $type_briefing_id = TypeBriefingEnumController::UNPLANNED)
    {
        $log = new LogAmicumFront("GetBriefings");
        $result = null;
        try {
            $log->addLog("Начал выполнять метод");
            $briefers = Briefer::find()
                ->select([
                    'briefer.worker_id',
                    'briefer.date_time'
                ])
                ->innerJoinWith('briefing')
                ->where(['briefer.worker_id' => $workers])
                ->andWhere(['briefing_reason_id' => BriefingReasonEnumController::DOUBLE_FAIL_EXAM])
                ->andWhere(['briefing.status_id' => StatusEnumController::BRIEFING_CONDUCTED])
                ->andWhere(['briefer.status_id' => StatusEnumController::BRIEFING_FAMILIAR])
                ->andWhere(['type_briefing_id' => $type_briefing_id])
                ->andWhere(['between', 'briefer.date_time', $date_start, $date_end])
                ->all();

            foreach ($briefers as $briefer) {
                $date = date("Y-m-d", strtotime($briefer['date_time']));
                $result[$briefer['worker_id'] . "_" . $date] = $briefer;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
