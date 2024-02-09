<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\handbooks;

use backend\controllers\Assistant;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Company;
use frontend\models\PlanShift;
use frontend\models\ProdGraphicWork;
use frontend\models\Shift;
use frontend\models\ShiftSchedule;
use frontend\models\ShiftType;
use frontend\models\WorkMode;
use frontend\models\WorkModeCompany;
use frontend\models\WorkModeShift;
use frontend\models\WorkModeWorker;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;


class HandbookShiftController extends Controller
{
    // GetShift                                     - Получение справочника смен
    // SaveShift                                    - Сохранение новой смены
    // DeleteShift                                  - Удаление смены

    // GetShiftType()                               - Получение справочника типов смен
    // SaveShiftType()                              - Сохранение справочника типов смен
    // DeleteShiftType()                            - Удаление справочника типов смен

    // actionAddShiftMode                           - Функция добавления режима работы
    // actionShowPlanShift                          - получить список режимов работы

    // GetWorkModes                                 - метод получения списка режимов работы
    // AddWorkMode                                  - метод добавления режима работы
    // AddWorkModeWorker                            - метод добавления режима работы на работника
    // AddWorkModeWorkers                           - метод добавления режима работы по массиву работников
    // AddWorkModeCompany                           - метод добавления режима работы на подразделение
    // DelWorkMode                                  - метод удаления режима работы

    // GetProdGraphicWork                           - метод получения производственного календаря

    // buildArray                                   - массив режимов работы
    // actionAddShiftMode                           - Функция добавления режима работы

    /* Метод передачи данных на страницу справочника
     * Входных параметров нет
     * Выходные параметры:
     * - $model - (array) массив, содержащий все выводимые в справочнике данные
     * - $type_shift - (array) массив названий предприятий (нумерация по id)
     * - $plan_shift - (array) массив названий подразделений (нумерация по id)
     * - $shift - (array) массив названий типов подразделений (нумерация по id)
     */
    public function actionIndex()
    {
        //Запросить массив всех режимов работы отдельным списком
        $plan_shifts = PlanShift::find()
            ->select(['title', 'id'])
            ->indexBy('id')
            ->column();
        //Запросить массив всех типов смен отдельным списком
        $shift_types = ShiftType::find()
            ->select(['title', 'id'])
            ->indexBy('id')
            ->column();
        //Вызвать функцию построения массива данных buildArray() и присвоить результат выполнения функции в массив
        $model = $this->buildArray();
        //Вернуть массивы на страницу справочника
        return $this->render
        (
            'index',
            [
                'model' => $model,
                'shift_types' => $shift_types,
                'plan_shifts' => $plan_shifts/*,
                'shifts' => $shifts,*/
            ]
        );
    }

    /* buildArray  - массив режимов работы
     * Входные параметры отсутствуют
     * Выходные параметры:
     * - $shift_modes - (array) массив предприятий, дочерних для $upper и информации о них
     */
    public function buildArray()
    {
        //Запросить все режимы работы (PlanShift)
        $plan_shifts = PlanShift::find()
            ->joinWith('shiftSchedules')
            ->joinWith('shiftSchedules.shiftType')
            ->all();
        //Объявить массив для сохранения данных
        $shift_modes = array();
        //Для каждого режима работы
        $i = 0;
        foreach ($plan_shifts as $plan_shift) {
            //Сохранить id и название режима работы
            $shift_modes[$i]['id'] = $plan_shift->id;
            $shift_modes[$i]['title'] = $plan_shift->title;
            //Сохранить id и название режима работы
            $j = 0;
            //Получить смены текущего режима работы. Для каждой смены
            if ($plan_shift->shiftSchedules) {
                foreach ($plan_shift->shiftSchedules as $shift) {
                    //Сохранить id и название смены
                    $shift_modes[$i]['shifts'][$j]['id'] = $shift->id;
                    $shift_modes[$i]['shifts'][$j]['title'] = $shift->title;
                    $shift_modes[$i]['shifts'][$j]['type']['id'] = $shift->shiftType->id;
                    $shift_modes[$i]['shifts'][$j]['type']['title'] = $shift->shiftType->title;
                    //Сохранить время начала смены (отдельно часы, минуты и секунды)
                    $shift_modes[$i]['shifts'][$j]['tStartHour'] = date('H', strtotime($shift->time_start));
                    $shift_modes[$i]['shifts'][$j]['tStartMin'] = date('i', strtotime($shift->time_start));
                    $shift_modes[$i]['shifts'][$j]['tStartSec'] = date('s', strtotime($shift->time_start));
                    //Сохранить время окончания смены (отдельно часы, минуты и секунды)
                    $shift_modes[$i]['shifts'][$j]['tEndHour'] = date('H', strtotime($shift->time_end));
                    $shift_modes[$i]['shifts'][$j]['tEndMin'] = date('i', strtotime($shift->time_end));
                    $shift_modes[$i]['shifts'][$j]['tEndSec'] = date('s', strtotime($shift->time_end));
                    $j++;
                }
            }
            $i++;
        }
        return $shift_modes;
    }

    // actionShowPlanShift - получить список режимов работы
    // Пример: 127.0.0.1/handbooks/handbook-shift/show-plan-shift
    public function actionShowPlanShift()
    {
        //Вызвать функцию buildArray
        $plan_shifts = $this->buildArray();
        //Вернуть данные в формате json
        echo json_encode($plan_shifts);
    }

    /* actionAddShiftMode - Функция добавления режима работы
     * Входные параметры:
     * - $post['title'] – (string) название режима работы
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionAddShiftMode()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 54)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //Запросить режим работы с полученным названием
                $plan_shift = PlanShift::findOne(['title' => $post['title']]);
                //Если такого режима работы не существует
                if (!$plan_shift) {
                    //Создать новый режим работы
                    $plan_shift = new PlanShift();
                    //Сохранить название
                    $plan_shift->title = $post['title'];
                    //Сохранить текущую дату
                    $plan_shift->date = date("Y-m-d");
                    //Сохранить режим работы
                    if (!$plan_shift->save()) {
                        $errors[] = $plan_shift->errors;
                        $errors[] = "ошибка сохранения модели PlanShift";
                    }
                    //Вызвать функцию buildArray
                    $model = $this->buildArray();
                } else {
                    $errors[] = "Такой режим работы уже существует";
                    $model = $this->buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /* Функция добавления смены
     * Входные параметры
     * - $post['title'] – (string) название смены
     * - $post['shift_mode'] – (int) идентификатор режима работы
     * - $post['type'] – (int) идентификатор типа смены
     * - $post['start_hour'] – (int) час времени начала смены
     * - $post['start_min'] – (int) минута времени начала смены
     * - $post['start_sec'] – (int) секунда времени начала смены
     * - $post['end_hour'] – (int) час времени окончания смены
     * - $post['end_min'] – (int) минута времени окончания смены
     * - $post['end_sec'] – (int) секунда времени начала смены
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionAddShift()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 55)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //Запросить смену с полученным названием для полученного режима работы
                $shift = ShiftSchedule::findOne(['title' => $post['title'], 'plan_shift_id' => $post['shift_mode']]);
                //Если такой смены не существует
                if (!$shift) {
                    //Создать новую смену
                    $shift = new ShiftSchedule();
                    //Сохранить название
                    $shift->title = $post['title'];
                    //Привязать режим работы
                    $shift->plan_shift_id = $post['shift_mode'];
                    //Привязать тип смены
                    $shift->shift_type_id = $post['type'];
                    //Собрать в переменную типа Time полученные час, минуту и секунду времени начала смены
                    $start_time = $post['start_hour'] . ':' . $post['start_min'] . ':' . $post['start_sec'];
                    //Собрать в переменную типа Time полученные час, минуту и секунду времени окончания смены
                    $end_time = $post['end_hour'] . ':' . $post['end_min'] . ':' . $post['end_sec'];
                    //Сохранить время начала и окончания смены
                    $shift->time_start = $start_time;
                    $shift->time_end = $end_time;
                    if (!$shift->save()) {
                        $errors[] = $shift->errors;
                        $errors[] = "ошибка сохранения модели ShiftSchedule";
                    }
                }
                //Вызвать функцию buildArray
                $model = $this->buildArray();
                //Вернуть данные в формате json
//                echo json_encode($plan_shifts);
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /* Функция редактирования режима работы
     * Входные параметры:
     * - $post['id'] – (int) идентификатор редактируемого режима работы
     * - $post['title'] – (string) новое название режима работы
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionEditShiftMode()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 56)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //Запросить режим работы по полученному идентификатору
                $plan_shift = PlanShift::findOne($post['id']);
                //Если такой режим работы есть
                if ($plan_shift) {
                    //Если передано название режима работы, сохранить его
                    if ($post['title']) {
                        $plan_shift->title = $post['title'];
                    }
                    //Сохранить режим работы
                    $plan_shift->save();
                }
                //Вызвать функцию buildArray
                $model = $this->buildArray();
                //Вернуть данные в формате json
//                echo json_encode($plan_shifts);
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /* Функция редактирования смены
     * Входные параметры:
     * - $post['id'] – (int) идентификатор редактируемой смены
     * - $post['title'] – (string) новое название смены
     * - $post['type'] – (int) идентификатор нового типа смены
     * - $post['start_hour'] – (int) новый час времени начала смены
     * - $post['start_min] – (int) новая минута времени начала смены
     * - $post['start_sec'] – (int) новая секунда времени начала смены
     * - $post['end_hour'] – (int) новый час времени окончания смены
     * - $post['end_min] – (int) новая минута времени окончания смены
     * - $post['end_sec'] – (int) новая секунда времени начала смены
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionEditShift()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 57)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //Запросить смену по полученному идентификатору
                $shift = ShiftSchedule::findOne($post['id']);
                //Если смена существует
                if ($shift) {
                    //Если передано название, сохранить его
                    /*if($post['title']){
                        $shift->title = $post['title'];
                    }*/
                    //Если передан тип смены, привязать его
                    if ($post['type']) {
                        $shift->shift_type_id = $post['type'];
                    }
                    //Если переданы час, минута и секунда времени начала смены
                    if ($post['start_hour'] && $post['start_min'] && $post['start_sec']) {
                        //Собрать их в переменную типа Time
                        $start_time = $post['start_hour'] . ':' . $post['start_min'] . ':' . $post['start_sec'];
                        //Сохранить время начала смены
                        $shift->time_start = $start_time;
                    }
                    //Если переданы час, минута и секунда времени окончания смены
                    if ($post['end_hour'] && $post['end_min'] && $post['end_sec']) {
                        //Собрать их в переменную типа Time
                        $end_time = $post['end_hour'] . ':' . $post['end_min'] . ':' . $post['end_sec'];
                        //Сохранить время окончания смены
                        $shift->time_end = $end_time;
                    }
                    //Сохранить смену
                    $shift->save();
                }
                //Вызвать функцию buildArray
                $model = $this->buildArray();
                //Вернуть данные в формате json
//                echo json_encode($plan_shifts);
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /* Функция удаления режима работы
     * Входные параметры
     * - $post['id'] – (int) идентификатор удаляемого режима работы
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionDeletePlanShift()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 58)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //Запросить режим работы по идентификатору
                $plan_shift = PlanShift::findOne($post['id']);
                //Если такой режим работы существует, удалить его
                if ($plan_shift) {
                    $plan_shift->delete();
                }
                //Вызвать функцию buildArray
                $model = $this->buildArray();
                //Вернуть данные в формате json
//                echo json_encode($plan_shifts);
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Смена неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /* Функция удаления смены
     * Входные параметры
     * - $post['id'] – (int) идентификатор удаляемой смены
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionDeleteShift()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 59)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //Запросить смену по идентификатору
                $shift = ShiftSchedule::findOne($post['id']);
                //Если такая смена существует, удалить ее
                if ($shift) {
                    $shift->delete();
                }
                //Вызвать функцию buildArray
                $model = $this->buildArray();
                //Вернуть данные в формате json
//                echo json_encode($plan_shifts);
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionGetShiftHandbook()
     * Метод получения справочника смен
     * @return string
     *
     * Входные необязательные параметры
     *
     * @package app\controllers
     *
     * Входные обязательные параметры:
     * @see
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.04.2019 9:18
     * @since ver
     */
    public function actionGetShiftHandbook()
    {
        $errors = array();
        // Получение списка всех смен из справочника смен
        $shifts_list = Shift::getShiftList();
        if (empty($shifts_list))
            $errors[] = "Данных о сменах нет в БД";
        $result = array('errors' => $errors, 'model' => $shifts_list);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionInsertShiftHandbook()
     * Метод добавления смены в справочник смен
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @see
     * @example
     *
     * @package app\controllers
     *
     * Входные обязательные параметры:
     *
     * Входные необязательные параметры
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.04.2019 11:02
     * @since ver
     */
    public function actionInsertShiftHandbook()
    {
        $errors = array();
        /** @var $insertedArray - массив для добалвения */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') $post = Yii::$app->request->post();
        else if ($_SERVER['REQUEST_METHOD'] == 'GET') $post = Yii::$app->request->get();
        if (isset($post['title']) and !empty($post['title'])) {
            $errors = Shift::insertShift($post['title']);
        }
        /** Получаем информацию сменах */
        $shifts_list = Shift::getShiftList();
        if (empty($shifts_list))
            $errors[] = "Данных о сменах нет в БД";
        $result = array('errors' => $errors, 'model' => $shifts_list);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionDeleteShiftHandbook()
     * Метод удаления смены из справочника смен shift
     *
     * @package app\controllers
     *
     * Входные обязательные параметры:
     *
     * Входные необязательные параметры
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.04.2019 10:00
     * @since ver
     */
    public function actionDeleteShiftHandbook()
    {
        $errors = array();
        /** @var $insertedArray - массив для добавления */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') $post = Yii::$app->request->post();
        else if ($_SERVER['REQUEST_METHOD'] == 'GET') $post = Yii::$app->request->get();
        if (isset($post['shift_id']) and !empty($post['shift_id'])) {
            $errors = Shift::deleteShift($post['shift_id']);
        } else {
            $errors[] = "Данные получены не корректно";
        }
        /** Получаем информацию сменах */
        $shifts_list = Shift::getShiftList();
        $result = array('errors' => $errors, 'model' => $shifts_list);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionUdpateShiftHandbook()
     * Метод для обновления смены из справочника смен shift
     *
     * @package app\controllers
     *
     * Входные обязательные параметры:
     *
     * Входные необязательные параметры
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.04.2019 10:04
     * @since ver
     */
    public function actionUpdateShiftHandbook()
    {
        $errors = array();
        /** @var $insertedArray - массив для добавления */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') $post = Yii::$app->request->post();
        else if ($_SERVER['REQUEST_METHOD'] == 'GET') $post = Yii::$app->request->get();
        if (isset($post['shift_id'], $post['title']) and !empty($post['shift_id']) and !empty($post['title'])) {
            $errors = Shift::updateShift($post['shift_id'], $post['title']);
        } else {
            $errors[] = "Данные получены не корректно";
        }
        /** Получаем информацию сменах */
        $shifts_list = Shift::getShiftList();
        if (empty($shifts_list))
            $errors[] = "Данных о сменах нет в БД";
        $result = array('errors' => $errors, 'model' => $shifts_list);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Метод GetShift() - Получение справочника смен
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * [
     *      "id": 5,
     *      "short": "Без смены",
     *      "short_title": "-"
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookShift&method=GetShift&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 10:53
     */
    public static function GetShift()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetShift';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $shift_data = Shift::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($shift_data)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник смен пуст';
            } else {
                $result = $shift_data;
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
     * Метод SaveShift() - Сохранение новой смены
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "shift":
     *    {
     *        "shift_id":-1,                                    // идентификатор смены (-1 =  новый тип смены)
     *        "title":"SHIFT_TEST",                            // наименование смены
     *        "short_title":"6"                                // сокращённое наименование смены (1 СИМВОЛ!!!!!!)
     *    }
     *
     * ВЫХОДНОЙ МАССИВ:
     * Items:{
     *    "shift_id":6,                                        // идентификатор смены
     *  "title":"SHIFT_TEST",                                // наименование смены
     *    "short_title":"6"                                    // сокращённое наименование смены
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookShift&method=SaveShift&subscribe=&data={"shift":{"shift_id":-1,"title":"SHIFT_TEST_1","short_title":"S_T_1"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 11:00
     */
    public static function SaveShift($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveShift';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'shift'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $shift_id = $post_dec->shift->shift_id;
            $title = $post_dec->shift->title;
            $short_title = $post_dec->shift->short_title;
            $shift = Shift::findOne(['id' => $shift_id]);
            if (empty($shift)) {
                $shift = new Shift();
                /**
                 * В базе данных идентификатор смены не автоинкрементный (так как много таблиц завязано на смене сделать его автоинкрементным =  надо удалить все связи, поставить автоинкемент и вернуть связи)
                 */
                $max_shift_id = Shift::find()->orderBy('id desc')->limit(1)->scalar();
                $shift->id = ++$max_shift_id;
            }
            $shift->title = $title;
            $shift->short_title = $short_title;
            if ($shift->save()) {
                $shift->refresh();
                $chat_type_data['shift_id'] = $shift->id;
                $chat_type_data['title'] = $shift->title;
                $chat_type_data['short_title'] = $shift->short_title;
            } else {
                $errors[] = $shift->errors;
                throw new Exception($method_name . '. Ошибка при сохранении новой смены');
            }
            unset($shift);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteShift() - Удаление смены
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "shift_id":6
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив данных)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookShift&method=DeleteShift&subscribe=&data={"shift_id":6}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 11:15
     */
    public static function DeleteShift($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteShift';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'shift_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $shift_id = $post_dec->shift_id;
            Shift::deleteAll(['id' => $shift_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // GetShiftType()      - Получение справочника типов смен
    // SaveShiftType()     - Сохранение справочника типов смен
    // DeleteShiftType()   - Удаление справочника типов смен

    /**
     * Метод GetShiftType() - Получение справочника типов смен
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
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetShiftType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetShiftType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetShiftType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_shift_type = ShiftType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_shift_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник типов смен пуст';
            } else {
                $result = $handbook_shift_type;
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
     * Метод SaveShiftType() - Сохранение справочника типов смен
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "shift_type":
     *  {
     *      "shift_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "shift_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveShiftType&subscribe=&data={"shift_type":{"shift_type_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveShiftType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveShiftType';
        $handbook_shift_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'shift_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_shift_type_id = $post_dec->shift_type->shift_type_id;
            $title = $post_dec->shift_type->title;
            $new_handbook_shift_type_id = ShiftType::findOne(['id' => $handbook_shift_type_id]);
            if (empty($new_handbook_shift_type_id)) {
                $new_handbook_shift_type_id = new ShiftType();
            }
            $new_handbook_shift_type_id->title = $title;
            if ($new_handbook_shift_type_id->save()) {
                $new_handbook_shift_type_id->refresh();
                $handbook_shift_type_data['shift_type_id'] = $new_handbook_shift_type_id->id;
                $handbook_shift_type_data['title'] = $new_handbook_shift_type_id->title;
            } else {
                $errors[] = $new_handbook_shift_type_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника типов смен');
            }
            unset($new_handbook_shift_type_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $handbook_shift_type_data, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteShiftType() - Удаление справочника типов смен
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "shift_type_id": 98             // идентификатор справочника типов смен
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteShiftType&subscribe=&data={"shift_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteShiftType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив результирующий
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteShiftType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'shift_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_shift_type_id = $post_dec->shift_type_id;
            ShiftType::deleteAll(['id' => $handbook_shift_type_id]);
            $result = $post_dec;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';


        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // AddWorkMode - метод добавления режима работы
    // Выходной объект:
    //      work_mode:                      - список режимов работы
    //              work_mode_id                    - ключ режима работы
    //              work_mode_title                 - назавние режима работы
    //              type_work_mode_id               - ключ типа режима работы
    //              type_work_mode_title            - название типа режима работы (праздничный/предпраздничный/рабочий)
    //              count_hours                     - количество часов
    //              count_norm_hours                - количество нормированных часов
    //              shifts:                         - список смен
    //                  {work_mode_shift_id}
    //                      work_mode_shift_id              - ключ связки режима работы и смены
    //                      shift_id                        - ключ смены
    //                      shift_title                     - название смены
    //                      time_start                      - время начала
    //                      time_end                        - время окончания
    //                      shift_type_id                   - ключ типа смены
    //                      shift_type_title                - название типа смены
    // 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookShift&method=AddWorkMode&subscribe=&data={"work_mode":{"work_mode_id":5,"work_mode_title":"Двусменка","type_work_mode_id":1,"type_work_mode_title":"Рабочий","count_hours":12,"shifts":{"5":{"work_mode_shift_id":"5","shift_id":"1","shift_title":"Смена 1","time_start":"08:00:00","time_end":"20:00:00","shift_type_id":"1","shift_type_title":"Ремонтная"},"6":{"work_mode_shift_id":"6","shift_id":"2","shift_title":"Смена 2","time_start":"20:00:00","time_end":"08:00:00","shift_type_id":"2","shift_type_title":"ПРоизводственная"}}}}
    public static function AddWorkMode($data_post = null)
    {
        $log = new LogAmicumFront("AddWorkMode", true);
        $result = array();
        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'work_mode')
            ) {                                                                                                         // Проверяем наличие в нем нужных нам полей
                throw new Exception('Переданы некорректные входные параметры');
            }

            $work_mode = $post_dec->work_mode;

            $new_work_mode = WorkMode::findOne(["id" => $work_mode->work_mode_id]);
            if (!$new_work_mode) {
                $new_work_mode = new WorkMode();
            }

            $new_work_mode->title = $work_mode->work_mode_title;
            $new_work_mode->type_work_mode_id = $work_mode->type_work_mode_id;
            $new_work_mode->count_hours = $work_mode->count_hours;
            $new_work_mode->count_norm_hours = $work_mode->count_norm_hours;

            if (!$new_work_mode->save()) {
                $log->addData($new_work_mode->errors, '$new_work_mode->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели режимов работы WorkMode");
            }

            $work_mode_id = $new_work_mode->id;
            $post_dec->work_mode->work_mode_id = $work_mode_id;

            WorkModeShift::deleteAll(['work_mode_id' => $work_mode_id]);
            if (property_exists($work_mode, 'shifts')) {
                foreach ($work_mode->shifts as $key => $shift) {
                    $new_work_mode_shift = new WorkModeShift();
                    $new_work_mode_shift->work_mode_id = $work_mode_id;
                    $new_work_mode_shift->time_start = date("H:i:s", strtotime($shift->time_start));
                    $new_work_mode_shift->time_end = date("H:i:s", strtotime($shift->time_end));
                    $new_work_mode_shift->shift_id = $shift->shift_id;
                    $new_work_mode_shift->shift_type_id = $shift->shift_type_id;

                    if (!$new_work_mode_shift->save()) {
                        $log->addData($new_work_mode_shift->errors, '$new_work_mode_shift->errors', __LINE__);
                        throw new Exception("Ошибка сохранения модели смен режимов работы WorkModeShift");
                    }
                    $work_mode_shift_id = $new_work_mode_shift->id;
                    $post_dec->work_mode->shifts->{$key}->work_mode_shift_id = $work_mode_shift_id;
                }
            }

            $result = $post_dec;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        //Вернуть данные в формате json
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // AddWorkModeWorker - метод добавления режима работы на работника
    // Выходной объект:
    //      work_mode_worker:                  - список режимов работы рабоника
    //              worker_id                       - ключ работника
    //              full_name                       - ФИО
    //              position_id                     - ключ должности
    //              position_title                  - название должности
    //              company_department_id           - ключ подразделения
    //              work_mode_workers:               - список режимов работы работника
    //                  {work_mode_worker_id}           - ключ режима работы
    //                      work_mode_worker_id             - ключ режима работы работника
    //                      work_mode_id                    - ключ режима работы
    //                      work_mode_title                 - назавние режима работы
    //                      type_work_mode_id               - ключ типа режима работы
    //                      type_work_mode_title            - название типа режима работы (праздничный/предпраздничный/рабочий)
    //                      count_hours                     - количество часов
    //                      count_norm_hours                - количество нормированных часов
    //                      creater_worker_id               - ключ работника применившего режим работы
    //                      creater_full_name               - ФИО
    //                      creater_position_id             - ключ должности
    //                      creater_position_title          - название должности
    //                      creater_company_department_id   - ключ подразделения
    //                      status_id                       - ключ статуса режима работы (действует или нет1/19)
    //                      date_time_start                 - дата, с которой действует режим работы
    //                      date_time_end                   - дата по который действует режим работы
    //                      status                          - если равен del, то удалить, если add, то добавить, если edit, то редактировать
    // 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookShift&method=AddWorkModeWorker&subscribe=&data={"work_mode_worker":{}}
    public static function AddWorkModeWorker($data_post = null)
    {
        $log = new LogAmicumFront("AddWorkModeWorker", true);
        $result = array();
        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'work_mode_worker')
            ) {                                                                                                         // Проверяем наличие в нем нужных нам полей
                throw new Exception('Переданы некорректные входные параметры');
            }

            $work_mode_worker_post = $post_dec->work_mode_worker;

            if (property_exists($work_mode_worker_post, 'work_mode_workers')) {
                foreach ($work_mode_worker_post->work_mode_workers as $key => $work_mode_worker) {
                    if ($work_mode_worker->status == "del") {
                        WorkModeWorker::deleteAll(['id' => $work_mode_worker->work_mode_worker_id]);
                        unset($post_dec->work_mode_worker->work_mode_workers->{$key});
                    } else if ($work_mode_worker->status == "add" or $work_mode_worker->status == "edit") {
                        if ($work_mode_worker->status == "add") {
                            $new_work_mode_worker = new WorkModeWorker();
                        } else {
                            $new_work_mode_worker = WorkModeWorker::findOne(['id' => $work_mode_worker->work_mode_worker_id]);
                        }
                        $new_work_mode_worker->work_mode_id = $work_mode_worker->work_mode_id;
                        $new_work_mode_worker->date_time_start = date("Y-m-d H:i:s", strtotime($work_mode_worker->date_time_start));
                        $new_work_mode_worker->date_time_end = date("Y-m-d H:i:s", strtotime($work_mode_worker->date_time_end));
                        $new_work_mode_worker->date_time_create = Assistant::GetDateNow();
                        $new_work_mode_worker->status_id = $work_mode_worker->status_id;
                        $new_work_mode_worker->worker_id = $work_mode_worker_post->worker_id;
                        $session = Yii::$app->session;
                        $session->open();
                        $new_work_mode_worker->creater_worker_id = $session['worker_id'];

                        if (!$new_work_mode_worker->save()) {
                            $log->addData($new_work_mode_worker->errors, '$new_work_mode_shift->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели режимов работы работника WorkModeWorker");
                        }
                        $new_work_mode_worker->refresh();
                        $work_mode_worker_id = $new_work_mode_worker->id;
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->work_mode_worker_id = $work_mode_worker_id;
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_worker_id = $session['worker_id'];
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_full_name = $session['userFullName'];
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_position_id = $session['position_id'];
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_position_title = $session['position_title'];
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_company_department_id = $session['userCompanyDepartmentId'];
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->status = "";
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_tabel_number = $session['tabel_number'];
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_full_name = $session['userFullName'];
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_position_title = $session['position_title'];
                        $post_dec->work_mode_worker->work_mode_workers->{$key}->creater_position_id = $session['position_id'];
                    }
                }
            }

            $result = $post_dec;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        //Вернуть данные в формате json
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // AddWorkModeWorkers - метод добавления режима работы по массиву работников
    // Входной объект:
    //   work_mode_workers:
    //      workers:                        - список работников
    //          {worker_id}                     - ключ работника
    //              worker_id                       - ключ работника
    //      work_modes:                     - список устанавливаемых режимов работы
    //          {work_mode_id}
    //              work_mode_id                    - ключ режима работы
    //              date_time_start                 - дата начал действия режима работы
    //              date_time_end                   - дата окончания действия режима работы
    //              status_id                       - статус режима работы
    // 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookShift&method=AddWorkModeWorkers&subscribe=&data={"work_mode_workers":{"workers":{},"work_modes":{}}}
    public static function AddWorkModeWorkers($data_post = null)
    {
        $log = new LogAmicumFront("AddWorkModeWorkers", true);
        $result = array();
        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'work_mode_workers')
            ) {                                                                                                         // Проверяем наличие в нем нужных нам полей
                throw new Exception('Переданы некорректные входные параметры');
            }

            $work_mode_workers_post = $post_dec->work_mode_workers;
            if (property_exists($work_mode_workers_post, 'worker_work_modes_delete')) {
                $worker_work_modes_delete = $work_mode_workers_post->worker_work_modes_delete;
            } else {
                $worker_work_modes_delete = [];
            }
            WorkModeWorker::deleteAll(["id" => $worker_work_modes_delete]);

            $date_create = Assistant::GetDateNow();
            $work_mode_workers = [];
            $session = Yii::$app->session;
            $session->open();
            $session_worker_id = $session['worker_id'];

            foreach ($work_mode_workers_post->workers as $worker) {
                foreach ($work_mode_workers_post->work_modes as $work_mode) {
//                    if (
//                        !WorkModeWorker::findOne(
//                            [
//                                'worker_id' => $worker->worker_id,
//                                'work_mode_id' => $work_mode->work_mode_id,
//                                'date_time_start' => date("Y-m-d H:i:s", strtotime($work_mode->date_time_start))
//                            ]
//                        )
//                    ) {
                    $new_work_mode_worker['creater_worker_id'] = $session_worker_id;
                    $new_work_mode_worker['work_mode_id'] = $work_mode->work_mode_id;
                    $new_work_mode_worker['date_time_start'] = date("Y-m-d H:i:s", strtotime($work_mode->date_time_start));
                    $new_work_mode_worker['date_time_end'] = date("Y-m-d H:i:s", strtotime($work_mode->date_time_end));
                    $new_work_mode_worker['date_time_create'] = $date_create;
                    $new_work_mode_worker['status_id'] = $work_mode->status_id;
                    $new_work_mode_worker['worker_id'] = $worker->worker_id;
                    $work_mode_workers[] = $new_work_mode_worker;
//                    }
                }
            }

            if (count($work_mode_workers)) {
                $insert_work_mode_worker = Yii::$app->db_amicum2->queryBuilder->batchInsert('work_mode_worker', ['creater_worker_id', 'work_mode_id', 'date_time_start', 'date_time_end', 'date_time_create', 'status_id', 'worker_id'], $work_mode_workers);
                $count_insert_param_val = Yii::$app->db_amicum2->createCommand($insert_work_mode_worker)->execute();
                $log->addData($count_insert_param_val, "Вставил в БД данных");
            }

            $response = self::GetWorkModes();
            $log->addLogAll($response);
            $result = $response["Items"];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        //Вернуть данные в формате json
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // AddWorkModeCompany - метод добавления режима работы на подразделение
    // Выходной объект:
    //      work_mode_company:                  - список режимов работы компании
    //              company_id                      - ключ подразделения
    //              company_title                   - название компании
    //              path_company_title              - полное название компании
    //              upper_company_id                - ключ родителя
    //              work_mode_companies             - список режимов работы подразделения
    //                  {work_mode_company_id}         - ключ режима работы подразделения
    //                      work_mode_company_id            - ключ режима работы подразделения
    //                      work_mode_id                    - ключ режима работы
    //                      work_mode_title                 - назавние режима работы
    //                      type_work_mode_id               - ключ типа режима работы
    //                      type_work_mode_title            - название типа режима работы (праздничный/предпраздничный/рабочий)
    //                      count_hours                     - количество часов
    //                      count_norm_hours                - количество нормированных часов
    //                      creater_worker_id               - ключ работника применившего режим работы
    //                      creater_full_name               - ФИО
    //                      creater_position_id             - ключ должности
    //                      creater_position_title          - название должности
    //                      creater_company_department_id   - ключ подразделения
    //                      status_id                       - ключ статуса режима работы (действует или нет1/19)
    //                      date_time_start                 - дата, с которой действует режим работы
    //                      date_time_end                   - дата по который действует режим работы
    //                      status                          - если равен del, то удалить, если add, то добавить, если edit, то редактировать
    // 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookShift&method=AddWorkModeCompany&subscribe=&data={"work_mode_company":{}}
    public static function AddWorkModeCompany($data_post = null)
    {
        $log = new LogAmicumFront("AddWorkModeCompany", true);
        $result = array();
        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'work_mode_company')
            ) {                                                                                                         // Проверяем наличие в нем нужных нам полей
                throw new Exception('Переданы некорректные входные параметры');
            }

            $work_mode_company_post = $post_dec->work_mode_company;

            if (property_exists($work_mode_company_post, 'work_mode_companies')) {
                foreach ($work_mode_company_post->work_mode_companies as $key => $work_mode_company) {
                    if ($work_mode_company->status == "del") {
                        WorkModeCompany::deleteAll(['id' => $work_mode_company->work_mode_company_id]);
                        unset($post_dec->work_mode_company->work_mode_companies->{$key});
                    } else if ($work_mode_company->status == "add" or $work_mode_company->status == "edit") {
                        if ($work_mode_company->status == "add") {
                            $new_work_mode_company = new WorkModeCompany();
                        } else {
                            $new_work_mode_company = WorkModeCompany::findOne(['id' => $work_mode_company->work_mode_company_id]);
                        }
                        $new_work_mode_company->work_mode_id = $work_mode_company->work_mode_id;
                        $new_work_mode_company->date_time_start = date("Y-m-d H:i:s", strtotime($work_mode_company->date_time_start));
                        $new_work_mode_company->date_time_end = date("Y-m-d H:i:s", strtotime($work_mode_company->date_time_end));
                        $new_work_mode_company->date_time_create = Assistant::GetDateNow();
                        $new_work_mode_company->status_id = $work_mode_company->status_id;
                        $new_work_mode_company->company_id = $work_mode_company_post->company_id;
                        $session = Yii::$app->session;
                        $session->open();
                        $new_work_mode_company->creater_worker_id = $session['worker_id'];

                        if (!$new_work_mode_company->save()) {
                            $log->addData($new_work_mode_company->errors, '$new_work_mode_shift->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели режимов работы работника WorkModeWorker");
                        }
                        $new_work_mode_company->refresh();
                        $work_mode_company_id = $new_work_mode_company->id;
                        $post_dec->work_mode_company->work_mode_companies->{$key}->work_mode_company_id = $work_mode_company_id;
                        $post_dec->work_mode_company->work_mode_companies->{$key}->creater_worker_id = $session['worker_id'];
                        $post_dec->work_mode_company->work_mode_companies->{$key}->creater_full_name = $session['userFullName'];
                        $post_dec->work_mode_company->work_mode_companies->{$key}->creater_tabel_number = $session['tabel_number'];
                        $post_dec->work_mode_company->work_mode_companies->{$key}->creater_position_id = $session['position_id'];
                        $post_dec->work_mode_company->work_mode_companies->{$key}->creater_position_title = $session['position_title'];
                        $post_dec->work_mode_company->work_mode_companies->{$key}->creater_company_department_id = $session['userCompanyDepartmentId'];
                        $post_dec->work_mode_company->work_mode_companies->{$key}->date_time_create = $new_work_mode_company->date_time_create;
                        $post_dec->work_mode_company->work_mode_companies->{$key}->status = "";
                    }
                }
            }

            $result = $post_dec;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        //Вернуть данные в формате json
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // DelWorkMode - метод удаления режима работы
    // Выходной объект:
    //              work_mode_id                    - ключ режима работы
    // 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookShift&method=DelWorkMode&subscribe=&data={"work_mode_id":5}
    public static function DelWorkMode($data_post = null)
    {
        $log = new LogAmicumFront("DelWorkMode", true);
        $result = array();
        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'work_mode_id')
            ) {                                                                                                         // Проверяем наличие в нем нужных нам полей
                throw new Exception('Переданы некорректные входные параметры');
            }

            $work_mode_id = $post_dec->work_mode_id;

            WorkMode::deleteAll(["id" => $work_mode_id]);

            $result = $post_dec;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        //Вернуть данные в формате json
        return array_merge(['Items' => $result], $log->getLogAll());
    }


    // GetWorkModes - метод получения списка режимов работы
    // Выходной объект:
    //      all_companies:                  - список всех компаний
    //          {company_id}                    - ключ компании
    //              company_id                      - ключ компании
    //              title                           - название компании
    //              upper_company_id                - ключ вышестоящей компании
    //      departments:                    - список подразделений
    //          {company_department_id}         - ключ подразделения
    //              company_id                      - ключ подразделения
    //              company_title                   - название компании
    //              path_company_title              - полное название компании
    //              upper_company_id                - ключ родителя
    //              work_mode_companies             - список режимов работы подразделения
    //                  {work_mode_company_id}         - ключ режима работы подразделения
    //                      work_mode_company_id            - ключ режима работы подразделения
    //                      work_mode_id                    - ключ режима работы
    //                      work_mode_title                 - назавние режима работы
    //                      type_work_mode_id               - ключ типа режима работы
    //                      type_work_mode_title            - название типа режима работы (праздничный/предпраздничный/рабочий)
    //                      count_hours                     - количество часов
    //                      count_norm_hours                - количество нормированных часов
    //                      creater_worker_id               - ключ работника применившего режим работы
    //                      creater_full_name               - ФИО
    //                      creater_position_id             - ключ должности
    //                      creater_position_title          - название должности
    //                      creater_company_department_id   - ключ подразделения
    //                      status_id                       - ключ статуса режима работы (действует или нет1/19)
    //                      date_time_start                 - дата, с которой действует режим работы
    //                      date_time_end                   - дата по который действует режим работы
    //                      status                          - статус удаления режима работы
    //      workers:                    - список работников
    //          {worker_id}                 - ключ работника
    //              worker_id                       - ключ работника
    //              full_name                       - ФИО
    //              position_id                     - ключ должности
    //              position_title                  - название должности
    //              company_department_id           - ключ подразделения
    //              work_mode_workers:               - список режимов работы работника
    //                  {work_mode_worker_id}           - ключ режима работы
    //                      work_mode_worker_id             - ключ режима работы работника
    //                      work_mode_id                    - ключ режима работы
    //                      work_mode_title                 - назавние режима работы
    //                      type_work_mode_id               - ключ типа режима работы
    //                      type_work_mode_title            - название типа режима работы (праздничный/предпраздничный/рабочий)
    //                      count_hours                     - количество часов
    //                      count_norm_hours                - количество нормированных часов
    //                      creater_worker_id               - ключ работника применившего режим работы
    //                      creater_full_name               - ФИО
    //                      creater_position_id             - ключ должности
    //                      creater_position_title          - название должности
    //                      creater_company_department_id   - ключ подразделения
    //                      status_id                       - ключ статуса режима работы (действует или нет1/19)
    //                      date_time_start                 - дата, с которой действует режим работы
    //                      date_time_end                   - дата по который действует режим работы
    //                      status                          - статус удаления режима работы
    //      work_modes:                      - список режимов работы
    //          {work_mode_id}                  - ключ режима работы
    //              work_mode_id                    - ключ режима работы
    //              work_mode_title                 - назавние режима работы
    //              type_work_mode_id               - ключ типа режима работы
    //              type_work_mode_title            - название типа режима работы (праздничный/предпраздничный/рабочий)
    //              count_hours                     - количество часов
    //              count_norm_hours                - количество нормированных часов
    //              shifts:                         - список смен
    //                  {work_mode_shift_id}
    //                      work_mode_shift_id              - ключ связки режима работы и смены
    //                      shift_id                        - ключ смены
    //                      shift_title                     - название смены
    //                      time_start                      - время начала
    //                      time_end                        - время окончания
    //                      shift_type_id                   - ключ типа смены
    //                      shift_type_title                - название типа смены
    // 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookShift&method=GetWorkModes&subscribe=&data={}
    public static function GetWorkModes($data_post = null)
    {
        $log = new LogAmicumFront("GetWorkModes", true);
        $result = array(
            "work_modes" => null,
            "workers" => null,
            "departments" => null,
            "all_companies" => null,
        );
        try {
            $all_companies = Company::find()->indexBy('id')->asArray()->all();
            if (!$all_companies) {
                throw new Exception('Справочник компаний пуст. Поиск невозможен');
            }
            $result['all_companies'] = $all_companies;

            $work_modes = WorkMode::find()
                ->joinWith('typeWorkMode')
                ->joinWith('typeWorkMode')
                ->joinWith('workModeShifts.shift')
                ->joinWith('workModeShifts.shiftType')
                ->joinWith('workModeCompanies.company')
                ->joinWith('workModeCompanies.createrWorker.employee1')
                ->joinWith('workModeCompanies.createrWorker.position1')
                ->joinWith('workModeWorkers.worker.employee')
                ->joinWith('workModeWorkers.worker.position')
                ->joinWith('workModeWorkers.createrWorker.employee1')
                ->joinWith('workModeWorkers.createrWorker.position1')
                ->asArray()
                ->all();

            foreach ($work_modes as $work_mode) {
                $work_mode_id = $work_mode['id'];
                $result["work_modes"][$work_mode_id]['work_mode_id'] = $work_mode_id;
                $result["work_modes"][$work_mode_id]['work_mode_title'] = $work_mode['title'];
                $result["work_modes"][$work_mode_id]['work_mode_type_id'] = $work_mode['type_work_mode_id'];
                $result["work_modes"][$work_mode_id]['type_work_mode_title'] = $work_mode['typeWorkMode']['title'];
                $result["work_modes"][$work_mode_id]['count_hours'] = $work_mode['count_hours'];
                $result["work_modes"][$work_mode_id]['count_norm_hours'] = $work_mode['count_norm_hours'];

                if (isset($work_mode['workModeShifts'])) {
                    foreach ($work_mode['workModeShifts'] as $work_mode_shift) {
                        $result["work_modes"][$work_mode_id]['shifts'][$work_mode_shift['id']]['work_mode_shift_id'] = $work_mode_shift['id'];
                        $result["work_modes"][$work_mode_id]['shifts'][$work_mode_shift['id']]['shift_id'] = $work_mode_shift['shift_id'];
                        $result["work_modes"][$work_mode_id]['shifts'][$work_mode_shift['id']]['shift_title'] = $work_mode_shift['shift']['title'];
                        $result["work_modes"][$work_mode_id]['shifts'][$work_mode_shift['id']]['time_start'] = $work_mode_shift['time_start'];
                        $result["work_modes"][$work_mode_id]['shifts'][$work_mode_shift['id']]['time_end'] = $work_mode_shift['time_end'];
                        $result["work_modes"][$work_mode_id]['shifts'][$work_mode_shift['id']]['shift_type_id'] = $work_mode_shift['shift_type_id'];
                        $result["work_modes"][$work_mode_id]['shifts'][$work_mode_shift['id']]['shift_type_title'] = $work_mode_shift['shiftType']['title'];
                    }
                }

                if (isset($work_mode['workModeCompanies'])) {
                    foreach ($work_mode['workModeCompanies'] as $work_mode_company) {
                        $work_mode_company_id = $work_mode_company['id'];
                        $result["departments"][$work_mode_company['company_id']]["company_id"] = $work_mode_company['company_id'];
                        $result["departments"][$work_mode_company['company_id']]["company_title"] = $work_mode_company['company']['title'];
                        $result["departments"][$work_mode_company['company_id']]["path_company_title"] = HandbookDepartmentController::GetParentCompanyWidthAttachment($work_mode_company['company_id'], $all_companies, 0)['path'];
                        $result["departments"][$work_mode_company['company_id']]["upper_company_id"] = $work_mode_company['company']['upper_company_id'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]["creater_worker_id"] = $work_mode_company['creater_worker_id'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]["creater_full_name"] = $work_mode_company['createrWorker']['employee1']['last_name'] . " " . $work_mode_company['createrWorker']['employee1']['first_name'] . " " . $work_mode_company['createrWorker']['employee1']['patronymic'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]["creater_position_id"] = $work_mode_company['createrWorker']['position_id'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]["creater_tabel_number"] = $work_mode_company['createrWorker']['tabel_number'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]["creater_position_title"] = $work_mode_company['createrWorker']['position1']['title'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]["creater_company_department_id"] = $work_mode_company['createrWorker']['company_department_id'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['work_mode_company_id'] = $work_mode_company_id;
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['work_mode_id'] = $work_mode_id;
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['work_mode_title'] = $work_mode['title'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['work_mode_type_id'] = $work_mode['type_work_mode_id'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['type_work_mode_title'] = $work_mode['typeWorkMode']['title'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['count_hours'] = $work_mode['count_hours'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['count_norm_hours'] = $work_mode['count_norm_hours'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['status_id'] = $work_mode_company['status_id'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['date_time_create'] = $work_mode_company['date_time_create'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['date_time_start'] = $work_mode_company['date_time_start'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['date_time_end'] = $work_mode_company['date_time_end'];
                        $result["departments"][$work_mode_company['company_id']]["work_mode_companies"][$work_mode_company_id]['status'] = "";

                    }
                }

                if (isset($work_mode['workModeWorkers'])) {
                    foreach ($work_mode['workModeWorkers'] as $work_mode_worker) {

                        $work_mode_worker_id = $work_mode_worker['id'];
                        $result["workers"][$work_mode_worker['worker_id']]["worker_id"] = $work_mode_worker['worker_id'];
                        $result["workers"][$work_mode_worker['worker_id']]["full_name"] = $work_mode_worker['worker']['employee']['last_name'] . " " . $work_mode_worker['worker']['employee']['first_name'] . " " . $work_mode_worker['worker']['employee']['patronymic'];
                        $result["workers"][$work_mode_worker['worker_id']]["position_id"] = $work_mode_worker['worker']['position_id'];
                        $result["workers"][$work_mode_worker['worker_id']]["tabel_number"] = $work_mode_worker['worker']['tabel_number'];
                        $result["workers"][$work_mode_worker['worker_id']]["position_title"] = $work_mode_worker['worker']['position']['title'];
                        $result["workers"][$work_mode_worker['worker_id']]["company_department_id"] = $work_mode_worker['worker']['company_department_id'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]["creater_worker_id"] = $work_mode_worker['creater_worker_id'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]["creater_full_name"] = $work_mode_worker['createrWorker']['employee1']['last_name'] . " " . $work_mode_worker['createrWorker']['employee1']['first_name'] . " " . $work_mode_worker['createrWorker']['employee1']['patronymic'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]["creater_position_id"] = $work_mode_worker['createrWorker']['position_id'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]["creater_tabel_number"] = $work_mode_worker['createrWorker']['tabel_number'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]["creater_position_title"] = $work_mode_worker['createrWorker']['position1']['title'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]["creater_company_department_id"] = $work_mode_worker['createrWorker']['company_department_id'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['work_mode_worker_id'] = $work_mode_worker_id;
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['work_mode_id'] = $work_mode_id;
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['work_mode_title'] = $work_mode['title'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['work_mode_type_id'] = $work_mode['type_work_mode_id'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['type_work_mode_title'] = $work_mode['typeWorkMode']['title'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['count_hours'] = $work_mode['count_hours'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['count_norm_hours'] = $work_mode['count_norm_hours'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['status_id'] = $work_mode_worker['status_id'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['date_time_create'] = $work_mode_worker['date_time_create'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['date_time_start'] = $work_mode_worker['date_time_start'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['date_time_end'] = $work_mode_worker['date_time_end'];
                        $result["workers"][$work_mode_worker['worker_id']]["work_mode_workers"][$work_mode_worker_id]['status'] = "";
                    }
                }
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        //Вернуть данные в формате json
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetProdGraphicWork - метод получения производственного календаря
    // Выходной объект:
    //          {year}                    - год
    //              1	                    "1,2,3,4,6*,7,9,10,16,17,23,24,30,31"   - январь
    //              2	                    "6,7,13,14,20,21,27,28"                 - февраль
    //              3	                    "6,7,8,13,14,20,21,27,28"               - март
    //              4	                    "3,4,10,11,17,18,24,25,30*"             - апрель
    //              5	                    "1,2,3,4,8,9,10,15,16,22,23,29,30"      - май
    //              6	                    "5,6,11*,12,13,14,19,20,26,27"          - июнь
    //              7	                    "3,4,10,11,17,18,24,25,31"              - июль
    //              8	                    "1,7,8,14,15,21,22,28,29"               - август
    //              9	                    "4,5,11,12,18,19,25,26"                 - сентябрь
    //              10	                    "2,3,9,10,16,17,23,24,30,31"            - октябрь
    //              11	                    "6,7,8,13,14,20,21,27,28"               - ноябрь
    //              12	                    "4,5,11,12,13,18,19,25,26,31*"          - декабрь
    //              id	                    "1"                                     - ключ режима работы
    //              year	                "1999"                                  - год
    //              all_work_day	        "251"                                   - Всего рабочих дней
    //              all_week_end	        "114"                                   - Всего праздничных и выходных дней
    //              count_work_hours_40	    "2004"                                  - Количество рабочих часов при 40-часовой рабочей неделе
    //              count_work_hours_36	    "1807"                                  - Количество рабочих часов при 36-часовой рабочей неделе
    //              count_work_hours_24	    "1205"                                  - Количество рабочих часов при 24-часовой рабочей неделе

    // 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookShift&method=GetProdGraphicWork&subscribe=&data={}
    public static function GetProdGraphicWork($data_post = null)
    {
        $log = new LogAmicumFront("GetProdGraphicWork", true);
        $result = (object)array();
        try {

            $result = ProdGraphicWork::find()
                ->indexBy('year')
                ->asArray()
                ->all();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        //Вернуть данные в формате json
        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
