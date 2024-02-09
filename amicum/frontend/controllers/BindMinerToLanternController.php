<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers;


use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\WorkerBasicController;
use backend\controllers\WorkerMainController;
use frontend\models\AccessCheck;
use frontend\models\SensorParameter;
use frontend\models\SensorParameterHandbookValue;
use frontend\models\ViewWorkerSensorMaxDate;
use frontend\models\ViewWorkerSensorParameterMaxDate;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameter;
use frontend\models\WorkerParameterSensor;
use Yii;
use yii\db\Query;
use yii\web\Response;


class BindMinerToLanternController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    /*функция поиска сотрудников по табельному номеру*/
    public function actionSearchEmployee()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $employees_array = array();
        $errors = array();
        $staff_number = "";
        $debug_flag = 0;
        //echo "staff number is ".$post['staff_number']." and typeof is ". gettype($post['staff_number'])."\n";
        if (isset($post['staff_number']) and $post['staff_number'] != "") {
            $staff_number = $post['staff_number'];
            $employees_array = array_merge(array(), self::buildSearchResult($staff_number));
        } else {
            $errors[] = "не передан параметр поиска";
        }
        $result = array('errors' => $errors, 'employees' => $employees_array);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public static function buildSearchResult($search_query)
    {
        $final_employees_array = array();
        $lamp_attached_array = array();
        $employees_array_source = (new Query())
            ->select('*')
            ->from('view_worker_with_sensor_all')
            ->where("staff_number like '%" . (string)$search_query . "%'")
            ->orWhere("full_name like '%" . (string)$search_query . "%'")
            ->orderBy(['staff_number' => SORT_ASC])
            ->all();
        return $employees_array_source;
    }


    /*Функция сортировки с учетом наличия числа в наименовании объекта*/
    public static function compareStringsWithNumbers($a, $b)
    {
//        if ($a['state'] === $b['state']) {
        if (isset($a['title']) and isset($b['title'])) {
            preg_match_all("/\d+/", $a['title'], $ma);
            preg_match_all("/\d+/", $b['title'], $mb);
            if (isset($ma[0][1]) && isset($mb[0][1])) {
                if ($ma[0][1] == $mb[0][1]) {
                    return 0;
                }
                return $ma[0][1] - $mb[0][1];
            } else {
                return ($a['title'] < $b['title']) ? -1 : 1;
            }
        }
//        }

    }


    /**
     * Метод получения список ламп с их типами
     * Created by: Одилов О.У. on 24.10.2018 16:53
     */
    public function actionGetSensors()
    {
        $regular_sensors = (new Query())
            ->select([
                'id', 'title', 'attached', 'sensor_type'])
            ->from('view_workers_and_attached_sensors')
            ->where('sensor_type = "Постоянная"')
            ->all();
        $reserve_sensors = (new Query())
            ->select([
                'id', 'title', 'attached', 'sensor_type'])
            ->from('view_workers_and_attached_sensors')
            ->where('sensor_type = "Резервная"')
            ->all();
        $post = Yii::$app->request->post();
        $errors = array();
        $last_regular_sensor = null;
        $last_reserve_sensor = null;
        $debug = false;
        if (isset($post['worker_object_id']) and $post['worker_object_id'] != "") {
            $worker_object_id = (int)$post['worker_object_id'];
            $last_regular_sensor = (new Query())
                ->select('view_worker_parameter_sensor_maxDate_regular_with_workers.sensor_id id, view_worker_parameter_sensor_maxDate_regular_with_workers.sensor_title title')
                ->from('view_worker_parameter_sensor_maxDate_regular_with_workers')
                ->where(['view_worker_parameter_sensor_maxDate_regular_with_workers.worker_object_id' => $worker_object_id])
                ->one();
            if ($last_regular_sensor == false or $last_regular_sensor['id'] == -1) {
                $last_regular_sensor = null;
            }
            $last_reserve_sensor = (new Query())
                ->select('view_worker_parameter_sensor_maxDate_reserve_with_workers.sensor_id id, view_worker_parameter_sensor_maxDate_reserve_with_workers.sensor_title title')
                ->from('view_worker_parameter_sensor_maxDate_reserve_with_workers')
                ->where(['view_worker_parameter_sensor_maxDate_reserve_with_workers.worker_object_id' => $worker_object_id])
                ->one();
            if ($last_reserve_sensor == false or $last_reserve_sensor['id'] == -1) {
                $last_reserve_sensor = null;
            }
        } else {
            $errors[] = 'Не передан worker_object_id';
        }
        usort($regular_sensors, "self::compareStringsWithNumbers");
        usort($reserve_sensors, "self::compareStringsWithNumbers");
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('regular' => $regular_sensors, 'reserve' => $reserve_sensors, 'attached_regular_sensor' => $last_regular_sensor, 'attached_reserve_sensor' => $last_reserve_sensor);
    }


    /**
     * Метод привязки лампы к конкретному работнику
     * если regular_sensor_id (reserve_sensor_id=null), то записываем его в базу и в кеш
     * если reserve_sensor_id (regular_sensor_id=null), то записываем его в базу и кеш
     * если пришли оба параметра regular_sensor_id и reserve_sensor_id, то сперва смотрим какая была до этого постоянная лампа и если она другая, то записываем ее.
     * если лампа постоянная та же, то записываем резервную лампу в базу и кеш. если резервная лампа последняя и таже, то ни чего не делаем
     * входные параметры
     * regular_sensor_id - постоянная лампа
     * reserve_sensor_id - резервная лампа
     * worker_object_id - уникальный идентификатор работника
     * пример использования метода:
     * http://localhost/bind-miner-to-lantern/bind-employee-to-sensor?regular_sensor_id=7380&reserve_sensor_id=7839&worker_object_id=8614&staff_number=8614
     * http://localhost/bind-miner-to-lantern/bind-employee-to-sensor?regular_sensor_id=empty&reserve_sensor_id=7839&worker_object_id=8614&staff_number=8614
     * http://localhost/bind-miner-to-lantern/bind-employee-to-sensor?regular_sensor_id=7380&reserve_sensor_id=empty&worker_object_id=8614&staff_number=8614
     * Метод разработал Фидченок М. - это его первый нормальный метод!!! почти сделал сам!
     */
    public function actionBindEmployeeToSensor()
    {
        $errors = array();    //объявляем пусстой массив ошибок
        $response = null;
        $result = null;
        $warnings = array();
        $status = 1;
        $employee_array = array();
        $flag_reserve = 0;
        $flag_save = 0;
        try {
            /**
             * блок проверки прав пользователя
             */
            $session = Yii::$app->session;
            $session->open();
            $warnings[] = "actionBindEmployeeToSensor. Начинаю проверять права пользователя";
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                $warnings[] = "actionBindEmployeeToSensor. Сессия существует";
            } else {
                $this->redirect('/');
                throw new \Exception("actionBindEmployeeToSensor. Время сессии закончилось. Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 81)) {
                throw new \Exception("actionBindEmployeeToSensor. Недостаточно прав для совершения данной операции");
            }

            $warnings[] = "actionBindEmployeeToSensor. Права на редактирование присутствуют";

            /**
             * блок проверки наличия входных параметров и их распарсивание
             */
            $post = Yii::$app->request->post(); //получение данных от ajax-запроса
            if (isset($post['worker_object_id']) && isset($post['worker_object_id']) &&
                isset($post['regular_sensor_id']) && isset($post['regular_sensor_id']) &&
                isset($post['reserve_sensor_id']) && isset($post['reserve_sensor_id']) &&
                isset($post['staff_number']) && isset($post['staff_number'])
            ) {                                                         //массив параметров и их значений
                $worker_object_id = (int)$post['worker_object_id'];
                $regular_sensor_id = (int)$post['regular_sensor_id'];
                $reserve_sensor_id = (int)$post['reserve_sensor_id'];
                $staff_number = $post['staff_number'];
                $warnings[] = "actionBindEmployeeToSensor. Получен входной массив worker_object_id $worker_object_id regular_sensor_id $regular_sensor_id reserve_sensor_id $reserve_sensor_id staff_number $staff_number";
            } else {
                throw new \Exception('actionBindEmployeeToSensor. Входные параметры со страницы фронт энд не переданы');
            }

            $response = self::BindEmployeeToSensor($regular_sensor_id, $reserve_sensor_id, $worker_object_id, $staff_number);
            if ($response['status'] == 1) {
                $result = $response['response'];
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                $employee_array = $response['employees'];
            } else {
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                throw new \Exception("actionBindEmployeeToSensor. Привязка светильника закончилось ошибкой");
            }
        } catch (\Throwable $ex) {
            $status = 0;
            $errors[] = 'actionBindEmployeeToSensor. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = 'строка с ошибкой ' . $ex->getLine();
        }

        $warnings[] = "actionBindEmployeeToSensor. Вышел с метода";
        $result_main = array('response' => $result, 'errors' => $errors, 'warnings' => $warnings, 'employees' => $employee_array);//составить результирующий массив как массив полученных массивов         //вернуть AJAX-запросу данные и ошибки
        return $this->asJson($result_main);
    }
    /**
     * Метод привязки лампы к конкретному работнику
     * если regular_sensor_id (reserve_sensor_id=null), то записываем его в базу и в кеш
     * если reserve_sensor_id (regular_sensor_id=null), то записываем его в базу и кеш
     * если пришли оба параметра regular_sensor_id и reserve_sensor_id, то сперва смотрим какая была до этого постоянная лампа и если она другая, то записываем ее.
     * если лампа постоянная та же, то записываем резервную лампу в базу и кеш. если резервная лампа последняя и таже, то ни чего не делаем
     * входные параметры
     * regular_sensor_id - постоянная лампа
     * reserve_sensor_id - резервная лампа
     * worker_object_id - уникальный идентификатор работника
     * пример использования метода:
     * http://localhost/bind-miner-to-lantern/bind-employee-to-sensor?regular_sensor_id=7380&reserve_sensor_id=7839&worker_object_id=8614&staff_number=8614
     * http://localhost/bind-miner-to-lantern/bind-employee-to-sensor?regular_sensor_id=empty&reserve_sensor_id=7839&worker_object_id=8614&staff_number=8614
     * http://localhost/bind-miner-to-lantern/bind-employee-to-sensor?regular_sensor_id=7380&reserve_sensor_id=empty&worker_object_id=8614&staff_number=8614
     * Метод разработал Фидченок М. - это его первый нормальный метод!!! почти сделал сам!
     */
    public static function BindEmployeeToSensor($regular_sensor_id, $reserve_sensor_id, $worker_object_id, $staff_number)
    {
        $errors = array();    //объявляем пусстой массив ошибок
        $response = null;
        $warnings = array();
        $status = 1;
        $employee_array = array();
        $flag_reserve = 0;
        $flag_save = 0;
        try {
            /**
             * блок поиска привязанного шахтера к переданной постоянной лампе.
             */
            if ($regular_sensor_id != 0) {
                $warnings[] = "BindEmployeeToSensor. Проверяем к кому привязана постоянная лампа.";
                $worker_sensor = (new Query())//поиск постоянной лампы в уже привязанных светильниках
                ->select('*')
                    ->from('view_worker_parameter_sensor_maxDate_regular_with_workers')
                    ->where("sensor_id=" . $regular_sensor_id)
                    ->one();
                if ($worker_sensor) {
                    $worker_object_last_lamp = $worker_sensor['worker_object_id'];

                    $warnings[] = "BindEmployeeToSensor. Получили worker_object_last  к кому в БД привязана данная лампа и он равен $worker_object_last_lamp";
                    if ($worker_object_id == $worker_object_last_lamp) {
                        $response[] = 'BindEmployeeToSensor. Постоянный светильник не изменен. Т.К. уже привязан к этому человеку';
                        $response[] = 'BindEmployeeToSensor. Флаг проверки резервной лампы на наличие равен 1';
                        $flag_reserve = 1;
                    } else {
                        throw new \Exception('BindEmployeeToSensor. Постоянный светильник привязан к другому сотруднику');
                    }
                } else {
                    /**
                     * получаем значение worker_id для последующей привязки светильника к работнику
                     */
                    if ($reserve_sensor_id == 0) {
                        $worker_obj = WorkerObject::find()->where(['id' => $worker_object_id])->limit(1)->one();
                        if ($worker_obj) {
                            $worker_id = $worker_obj['worker_id'];
                            $warnings[] = "BindEmployeeToSensor.Конкретный воркерОбджект найдени и он равен $worker_id";
                        } else {
                            throw new \Exception("BindEmployeeToSensor. не нашел конкретного воркерОбджекта $worker_object_id в таблице worker_object");
                        }
                        $sensor_id = $regular_sensor_id;
                        $type_relation_sensor = 1; //постоянный светильник
                        $flag_save = 1;
                        $flag_reserve = 0;
                        $warnings[] = "BindEmployeeToSensor.Постоянная не привязана. Можно сохранять $flag_save к воркеру $worker_id сенсор $sensor_id.
                     Резервная лампа не сохраняется и не проверяется( флаг проверки резервной лампы равен $flag_reserve";
                    } else {
                        throw new \Exception('BindEmployeeToSensor. Нельзя привязать постоянную лампу, при привязанной резервной');
                    }
                }
            } else {
                $warnings[] = "BindEmployeeToSensor.Постоянная лампа не передана.Ищем резервную";
                $flag_reserve = 1;
            }

            $warnings[] = "BindEmployeeToSensor. Идем в поиск резервной лампы. Флаг резервный равен: $flag_reserve";
            /**
             * блок поиска привязанного шахтера к переданной резервной лампе. Выполнится только в том случае если не нашли постоянную лампу
             */
            if ($flag_reserve == 1 and $reserve_sensor_id != 0) {
                $warnings[] = "BindEmployeeToSensor. Ищем к кому привзяана резервная лампа";
                $worker_sensor = (new Query())//поиск резервой лампы в уже привязанных светильниках
                ->select('*')
                    ->from('view_worker_parameter_sensor_maxDate_reserve_with_workers')
                    ->where("sensor_id=" . $reserve_sensor_id)
                    ->one();
                if ($worker_sensor) {
                    $worker_object_last_lamp = $worker_sensor['worker_object_id'];
                    if ($worker_object_id == $worker_object_last_lamp) {
                        $response[] = "BindEmployeeToSensor.Резервный светильник не изменен";
                        $warnings[] = 'BindEmployeeToSensor.Резервный светильник уже привязан к переданному сотруднику. Сохранение не было';
                    } else {
                        throw new \Exception('BindEmployeeToSensor. Резервный светильник привязан к другому сотруднику');
                    }
                } else {
                    $worker_obj = WorkerObject::find()->where(['id' => $worker_object_id])->limit(1)->one();
                    if ($worker_obj) {
                        $worker_id = $worker_obj['worker_id'];
                        $warnings[] = "BindEmployeeToSensor.Конкретный воркерОбджект найдени и он равен $worker_id";
                    } else {
                        throw new \Exception("BindEmployeeToSensor. не нашел конкретного воркерОбджекта $worker_object_id в таблице worker_object");
                    }
                    $sensor_id = $reserve_sensor_id;
                    $type_relation_sensor = 0;  //резервный светильник
                    $flag_save = 1;
                    $warnings[] = "BindEmployeeToSensor. Резервная не привязана. Можно сохранять сенсор айди  $sensor_id воркер айди $worker_id";
                }
            } else {
                $warnings[] = "BindEmployeeToSensor. Резервной лампы не передано $reserve_sensor_id или флаг разрешения проверки резервной лампы равен $flag_reserve";
            }
            /**
             * Блок сохранения привязки лампы к сотруднику
             */
            if ($flag_save == 1) {
                $warnings[] = "BindEmployeeToSensor. Ищем worker_parameter_id для привязки параметра к светильнику";
                /**
                 * блок сохранения в БД привязки сенсора к работнику
                 */
                $result_work_param = WorkerMainController::getOrSetWorkerParameter($worker_id, 83, 2);
                if ($result_work_param['status'] == 1) {
                    $warnings[] = $result_work_param['warnings'];
                    $worker_parameter_id = $result_work_param['worker_parameter_id'];
                    $warnings[] = "BindEmployeeToSensor. Получил 83 параметр воркера $worker_parameter_id";
                } else {
                    $warnings[] = $result_work_param['warnings'];
                    $errors[] = $result_work_param['errors'];
                    throw new \Exception("BindEmployeeToSensor. Не смог получить 83 параметр воркера");
                }
                $result_work_param_sensor = WorkerBasicController::addWorkerParameterSensor($worker_parameter_id, $sensor_id, $type_relation_sensor);
                if ($result_work_param_sensor['status'] == 1) {
                    $warnings[] = $result_work_param_sensor['warnings'];
                    $response[] = "BindEmployeeToSensor. Светильник привязан. все ОК";
                    $warnings[] = "BindEmployeeToSensor. Создал запись в моделе WorkerParameterSensor";
                } else {
                    $warnings[] = $result_work_param_sensor['warnings'];
                    $errors[] = $result_work_param_sensor['errors'];
                    throw new \Exception("BindEmployeeToSensor. Не смог создать запись в моделе WorkerParameterSensor");
                }
                /**
                 * блок привязки сенсора к работнику в КЕШЕ
                 */
                $ask_response = (new WorkerCacheController())->setSensorWorker($sensor_id, $worker_id);
                if ($ask_response) {
                    $warnings[] = "BindEmployeeToSensor. Привязал светильник к работнику в кеше";
                } else {
                    throw new \Exception("BindEmployeeToSensor. Не смог привязать светильник к работнику в кеше");
                }


            }
            $employee_array = self::buildSearchResult($staff_number);
        } catch (\Throwable $ex) {
            $status = 0;
            $errors[] = 'BindEmployeeToSensor. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = 'строка с ошибкой ' . $ex->getLine();
        }

        $warnings[] = "BindEmployeeToSensor. Вышел с метода";
        $result_main = array('status' => $status, 'response' => $response, 'errors' => $errors, 'warnings' => $warnings, 'employees' => $employee_array);//составить результирующий массив как массив полученных массивов         //вернуть AJAX-запросу данные и ошибки
        return $result_main;
    }

    /**
     * Название метода: actionUnbindLamp2()
     * Метод отвязки лампы
     * ВАЖНО. ЭТОТ МЕТОД СТАРЫЙ ОСТАВИЛ ПО ПРОСЬБЕ МАКСИМ НИКОЛАЕВИЧА
     * @package frontend\controllers
     *
     * Входные обязательные параметры:
     *
     * Входные необязательные параметры
     *
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 25.06.2019 8:01
     * @since ver
     */
    public function actionUnbindLamp2()
    {
        $errors = array();
        $warnings = array();
        $employees_array = array();
        $arr = array();
        try {
            $session = Yii::$app->session;                                                                                  //старт сессии
            $session->open();                                                                                               //открыть сессию
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                if (AccessCheck::checkAccess($session['sessionLogin'], 82)) {                                                //если пользователю разрешен доступ к функции
                    $post = Assistant::GetServerMethod();
                    if (isset($post['worker_object_id']) and $post['worker_object_id'] != ""
                        and isset($post['sensor_id']) and $post['sensor_id'] != "") {
                        $worker_object_id = $post['worker_object_id'];
                        $sensor_id = $post['sensor_id'];
                        $worker_parameter = WorkerParameter::findOne(['worker_object_id' => $worker_object_id, 'parameter_id' => 83, 'parameter_type_id' => 2]);
                        if ($worker_parameter) {
                            $sensor_parameter = SensorParameter::findOne(['sensor_id' => $sensor_id, 'parameter_id' => 459, 'parameter_type_id' => 1]);
                            if ($sensor_parameter) {
                                $sensor_parameter_handbook_value = SensorParameterHandbookValue::find()->where(['sensor_parameter_id' => $sensor_parameter->id])->orderBy(['date_time' => SORT_DESC])->one();
//                            print_r($sensor_parameter_handbook_value);die;
                                if ($sensor_parameter_handbook_value) {

                                    if ($sensor_parameter_handbook_value->value == 'Постоянная') {
                                        $worker_parameter_sensor = new WorkerParameterSensor();
                                        $worker_parameter_sensor->worker_parameter_id = $worker_parameter->id;
                                        $worker_parameter_sensor->date_time = date('Y-m-d H:i:s');
                                        $worker_parameter_sensor->sensor_id = -1;
                                        $worker_parameter_sensor->type_relation_sensor = 1;
                                        if (!$worker_parameter_sensor->save()) {
                                            $errors[] = "Не удалось отвязать светильник";
                                        }
                                        /**
                                         * блок отвязки сенсора от работника
                                         */
                                        $ask_response = (new WorkerCacheController())->delSensorWorker($sensor_id);
                                        if ($ask_response) {
                                            $warnings[] = "actionUnbindLamp. Удалил привязку сенсора к воркеру в кеше $sensor_id";
                                        } else {
                                            throw new \Exception("actionUnbindLamp. Не смог удалить привязку сенсора к воркеру в кеше $sensor_id");
                                        }
                                    } else if ($sensor_parameter_handbook_value->value == 'Резервная') {
                                        $worker_parameter_sensor = new WorkerParameterSensor();
                                        $worker_parameter_sensor->worker_parameter_id = $worker_parameter->id;
                                        $worker_parameter_sensor->date_time = date('Y-m-d H:i:s');
                                        $worker_parameter_sensor->sensor_id = -1;
                                        $worker_parameter_sensor->type_relation_sensor = 0;
                                        if (!$worker_parameter_sensor->save()) {
                                            $errors[] = "Не удалось отвязать светильник";
                                        }
                                        /** ТАК КАК ОТВЯЗАЛИ РЕЗЕРВНУЮ ЛАМПУ НАДО В КЕШ ДОБАВИТЬ ПОСТОЯНУЮ ЕСЛИ ОНА ЕСТЬ */
                                        $worker_sensor = (new Query())                                                  //поиск постоянной лампы даного воркера по worker_obj_id
                                        ->select('*')
                                            ->from('view_worker_parameter_sensor_maxDate_regular_with_workers')
                                            ->where("worker_object_id=" . $worker_object_id)
                                            ->one();
                                        if ($worker_sensor && $worker_sensor['sensor_id'] != -1) {
                                            $worker_obj = WorkerObject::find()->where(['id' => $worker_object_id])->limit(1)->one();
                                            if ($worker_obj) {
                                                $worker_id = $worker_obj['worker_id'];
                                                /**
                                                 * блок отвязки сенсора резервного и привязки постоянной лампы
                                                 */
                                                $ask_response = (new WorkerCacheController())->setSensorWorker((int)$worker_sensor['sensor_id'], $worker_id);
                                                if ($ask_response) {
                                                    $warnings[] = "actionUnbindLamp. Удалил привязку резервного сенсора к воркеру в кеше $sensor_id  ";
                                                    $warnings[] = "actionUnbindLamp. и добавил постоянную лампу $sensor_id для worker_id   $worker_id ";
                                                } else {
                                                    throw new \Exception("actionUnbindLamp. Не смог удалить привязку сенсора к воркеру в кеше $sensor_id");
                                                }
                                                $warnings[] = "actionUnbindLamp.Конкретный воркерОбджект найдени и он равен $worker_id";
                                            } else {
                                                throw new \Exception("actionUnbindLamp. не нашел конкретного воркерОбджекта $worker_object_id в таблице worker_object");
                                            }

                                        } else {
                                            $warnings[] = "У данного worker_object_id $worker_object_id Нет постоянной лампы поэтому в кеш ничего не привязываем";
                                            $ask_response = (new WorkerCacheController())->delSensorWorker($sensor_id);
                                            if ($ask_response) {
                                                $warnings[] = "actionUnbindLamp. Удалил привязку сенсора к воркеру в кеше $sensor_id";
                                            } else {
                                                throw new \Exception("actionUnbindLamp. Не смог удалить привязку сенсора к воркеру в кеше $sensor_id");
                                            }
                                        }
                                    } else {
                                        $errors[] = "У данного сенсора" . $post['sensor_id'] . "не указано тип лампы";
                                    }

                                } else {
                                    $errors[] = "У данного сенсора" . $post['sensor_id'] . "не найдено значение параметра тип лампы в справочнике SensorParameterHandbookValue";
                                }
                            } else {
                                $errors[] = "У данного сенсора" . $post['sensor_id'] . "не найдено параметр тип лампы в SensorParameter";
                            }
                        } else {
                            $errors[] = "Не найден worker_parameter";
                        }
                    } else {
                        $errors[] = "Не передан sensor_id = " . $post['sensor_id'] . " или worker_object_id = " . $post['worker_object_id'];
                    }
                } else {
                    $errors[] = "Недостаточно прав для совершения данной операции";
                }
            } else {
                $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
                $this->redirect('/');
            }
            if (isset($post['search']) and $post['search'] != "") {
                $staff_number = $post['search'];
                $employees_array = array_merge(array(), self::buildSearchResult($staff_number));
            } else {
                $errors[] = "не передан параметр поиска";
            }
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "actionUnbindLamp.Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result = array('errors' => $errors, 'employees' => $employees_array, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function actionUnbindLamp()
    {
        $errors = array();    //объявляем пусстой массив ошибок
        $result = null;
        $warnings = array();
        $status = 1;
        $employees_array = array();
        try {
            /**
             * блок проверки прав пользователя
             */
            $session = Yii::$app->session;
            $session->open();
            $warnings[] = "actionUnbindLamp. Начинаю проверять права пользователя";
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                $warnings[] = "actionUnbindLamp. Сессия существует";
            } else {
                $this->redirect('/');
                throw new \Exception("actionUnbindLamp. Время сессии закончилось. Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 82)) {
                throw new \Exception("actionUnbindLamp. Недостаточно прав для совершения данной операции");
            }

            $warnings[] = "actionUnbindLamp. Права на редактирование присутствуют";

            /**
             * блок проверки наличия входных параметров и их распарсивание
             */
            $post = Yii::$app->request->post(); //получение данных от ajax-запроса
            if (isset($post['worker_object_id']) && $post['worker_object_id'] != ""
                && isset($post['sensor_id']) && $post['sensor_id'] != "") {                                                         //массив параметров и их значений
                $worker_object_id = $post['worker_object_id'];
                $sensor_id = $post['sensor_id'];
                $staff_number = $post['search'];
                $warnings[] = "actionUnbindLamp. Получен входной массив worker_object_id $worker_object_id sensor_id $sensor_id staff_number $staff_number";
            } else {
                throw new \Exception('actionUnbindLamp. Входные параметры со страницы фронт энд не переданы');
            }

//            /**
//             * Блок поиска worker_id по worker_object_id
//             */
//            $worker_object = WorkerObject::find()->where(['id' => $worker_object_id])->one();
//            if ($worker_object) {
//                $worker_id = $worker_object['worker_id'];
//                $warnings[] = "actionUnbindLamp. Нашел worker_id равный $worker_id";
//            } else {
//                throw new \Exception("actionUnbindLamp. Не смог найти worker_id по worker_object_id = $worker_object_id");
//            }

            /**
             * блок поиска резервной и постоянной лампы
             */
            $result_search_sensors = self::WorkerSearchLamps($worker_object_id);
            if ($result_search_sensors['status'] == 1) {
                $warnings[] = $result_search_sensors['warnings'];
                $regular_id = $result_search_sensors['regular_id'];
                $reserve_id = $result_search_sensors['reserve_id'];
                $worker_object_id = $result_search_sensors['worker_object_id'];
                $warnings[] = "actionUnbindLamp. Нашел лампы workerа. regilar_id = $regular_id reserve_id = $reserve_id worker_object_id = $worker_object_id";
            } else {
                $warnings[] = $result_search_sensors['warnings'];
                $errors[] = $result_search_sensors['errors'];
                throw new \Exception("actionUnbindLamp. Не смог найти лампы для worker_object_id = $worker_object_id");
            }

            /**
             * Блок отвязки лампы
             */
            $result_unbind_lamp = self::UnbindLampFromWorker($sensor_id, $regular_id, $reserve_id, $worker_object_id);
            if ($result_unbind_lamp['status'] == 1) {
                $warnings[] = $result_unbind_lamp['warnings'];
                $warnings[] = "actionUnbindLamp. Отвязал лампу.";
            } else {
                $warnings[] = $result_unbind_lamp['warnings'];
                $errors[] = $result_unbind_lamp['errors'];
                throw new \Exception("actionUnbindLamp. Не смог отвязать лампу для worker_object_id = $worker_object_id");
            }
            $employees_array = array_merge(array(), self::buildSearchResult($staff_number));
        } catch (\Throwable $ex) {
            $status = 0;
            $errors[] = "actionUnbindLamp.Исключение: ";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'employees' => $employees_array, 'status' => $status, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public static function UnbindLampFromWorker($sensor_id, $regular_id, $reserve_id, $worker_object_id)
    {
        $errors = array();    //объявляем пусстой массив ошибок
        $result = null;
        $warnings = array();
        $status = 1;
        try {
            /**
             * Блок поиска worker_id по worker_object_id
             */
            $worker_object = WorkerObject::find()->where(['id' => $worker_object_id])->one();
            if ($worker_object) {
                $worker_id = $worker_object['worker_id'];
                $warnings[] = "UnbindLampFromWorker. Нашел worker_id равный $worker_id";
            } else {
                throw new \Exception("UnbindLampFromWorker. Не смог найти worker_id по worker_object_id = $worker_object_id");
            }
            /**
             * Блок отвязки постоянной лампы
             */
            if ($sensor_id == $regular_id) {
                $worker_parameter = WorkerParameter::findOne(['worker_object_id' => $worker_object_id, 'parameter_id' => 83, 'parameter_type_id' => 2]);
                if ($worker_parameter) {
                    $warnings[] = "UnbindLampFromWorker. Нашел worker_parameter $worker_parameter->id";
                } else {
                    $errors[] = "UnbindLampFromWorker. Не смог найти worker_parameter";
                    throw new \Exception("UnbindLampFromWorker. Не смог найти worker_parameter");
                }
                $worker_parameter_sensor = WorkerBasicController::addWorkerParameterSensor($worker_parameter->id, -1, 1, -1);
                if ($worker_parameter_sensor['status'] == 1) {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $warnings[] = "UnbindLampFromWorker. Отвязал постояный светильник";
                } else {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $errors[] = $worker_parameter_sensor['errors'];
                    throw new \Exception("actionUnbindLamp. Не удалось отвязать постояныый светильник");
                }
                /** ОТВЯЗЫВАЕМ ЛАМПУ ИЗ КЕША */
                $ask_response = (new WorkerCacheController())->delSensorWorker($sensor_id);
                if ($ask_response) {
                    $warnings[] = "UnbindLampFromWorker. Удалил привязку сенсора к воркеру в кеше $sensor_id";
                } else {
                    $errors[] = "UnbindLampFromWorker. Не смог удалить привязку сенсора к воркеру в кеше $sensor_id";
                    throw new \Exception("UnbindLampFromWorker. Не смог удалить привязку сенсора к воркеру в кеше $sensor_id");
                }
            }
            /**
             * Блок отвязки резервной лампы
             */
            if ($sensor_id == $reserve_id) {
                $worker_parameter = WorkerParameter::findOne(['worker_object_id' => $worker_object_id, 'parameter_id' => 83, 'parameter_type_id' => 2]);
                if ($worker_parameter) {
                    $warnings[] = "UnbindLampFromWorker. Нашел worker_parameter $worker_parameter->id";
                } else {
                    $errors[] = "UnbindLampFromWorker. Не смог найти worker_parameter";
                    throw new \Exception("UnbindLampFromWorker. Не смог найти worker_parameter");
                }
                $worker_parameter_sensor = WorkerBasicController::addWorkerParameterSensor($worker_parameter->id, -1, 0, -1);
                if ($worker_parameter_sensor['status'] == 1) {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $warnings[] = "UnbindLampFromWorker. Отвязал резервный светильник";
                } else {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $errors[] = $worker_parameter_sensor['errors'];
                    throw new \Exception("actionUnbindLamp. Не удалось отвязать резервный светильник");
                }
                /** Удаляем лампу из кеша */
                $ask_response = (new WorkerCacheController())->delSensorWorker($sensor_id);
                if ($ask_response) {
                    $warnings[] = "UnbindLampFromWorker. Удалил привязку сенсора к воркеру в кеше $sensor_id";
                } else {
                    $errors[] = "UnbindLampFromWorker.Не смог удалить привязку сенсора к воркеру в кеше $sensor_id";
                    throw new \Exception("UnbindLampFromWorker. Не смог удалить привязку сенсора к воркеру в кеше $sensor_id");
                }
                /** ТАК КАК ОТВЯЗАЛИ РЕЗЕРВНУЮ ЛАМПУ НАДО В КЕШ ДОБАВИТЬ ПОСТОЯНУЮ ЕСЛИ ОНА ЕСТЬ */
                if ($regular_id != null) {
                    $ask_response = (new WorkerCacheController())->setSensorWorker((int)$regular_id, $worker_id);
                    if ($ask_response) {
                        $warnings[] = "UnbindLampFromWorker. Удалил привязку резервного сенсора к воркеру в кеше $sensor_id  ";
                        $warnings[] = "UnbindLampFromWorker. и добавил постоянную лампу $regular_id для worker_id   $worker_id ";
                    } else {
                        throw new \Exception("UnbindLampFromWorker. Не смог записать постояную лампу в кеш $regular_id");
                    }
                }
            }
        } catch (\Throwable $ex) {
            $status = 0;
            $errors[] = "UnbindLampFromWorker.Исключение: ";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }
    //UnbindLamp - метод отвязки лампы от воркера
    //Метод создан для использования в /admin/serviceamicum/amicum-service/automatic-binding-sensors
    //Выше есть аналогичеый метод только с action
    // Разработала: Кендялова М.И.
    public static function UnbindLamp($regular_id, $reserve_id, $worker_object_id)
    {
        $errors = array();    //объявляем пусстой массив ошибок
        $result = null;
        $warnings = array();
        $status = 1;
        try {
            /**
             * Блок поиска worker_id по worker_object_id
             */
            $worker_object = WorkerObject::find()->where(['id' => $worker_object_id])->one();
            if ($worker_object) {
                $worker_id = $worker_object['worker_id'];
                $warnings[] = "UnbindLampFromWorker. Нашел worker_id равный $worker_id";
            } else {
                throw new \Exception("UnbindLampFromWorker. Не смог найти worker_id по worker_object_id = $worker_object_id");
            }
            /**
             * Блок отвязки постоянной лампы
             */
            if ($regular_id!=null) {
                $worker_parameter = WorkerParameter::findOne(['worker_object_id' => $worker_object_id, 'parameter_id' => 83, 'parameter_type_id' => 2]);
                if ($worker_parameter) {
                    $warnings[] = "UnbindLampFromWorker. Нашел worker_parameter $worker_parameter->id";
                } else {
                    $errors[] = "UnbindLampFromWorker. Не смог найти worker_parameter";
                    throw new \Exception("UnbindLampFromWorker. Не смог найти worker_parameter");
                }
                $worker_parameter_sensor = WorkerBasicController::addWorkerParameterSensor($worker_parameter->id, -1, 1, -1);
                if ($worker_parameter_sensor['status'] == 1) {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $warnings[] = "UnbindLampFromWorker. Отвязал постояный светильник";
                } else {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $errors[] = $worker_parameter_sensor['errors'];
                    throw new \Exception("actionUnbindLamp. Не удалось отвязать постояныый светильник");
                }
                /** ОТВЯЗЫВАЕМ ЛАМПУ ИЗ КЕША */
                $ask_response = (new WorkerCacheController())->delSensorWorker($regular_id);
                if ($ask_response) {
                    $warnings[] = "UnbindLampFromWorker. Удалил привязку сенсора к воркеру в кеше $regular_id";
                } else {
                    $errors[] = "UnbindLampFromWorker. Не смог удалить привязку сенсора к воркеру в кеше $regular_id";
                 //   throw new \Exception("UnbindLampFromWorker. Не смог удалить привязку сенсора к воркеру в кеше $regular_id");
                }
            }
            /**
             * Блок отвязки резервной лампы
             */
            if ($reserve_id!= null) {
                $worker_parameter = WorkerParameter::findOne(['worker_object_id' => $worker_object_id, 'parameter_id' => 83, 'parameter_type_id' => 2]);
                if ($worker_parameter) {
                    $warnings[] = "UnbindLampFromWorker. Нашел worker_parameter $worker_parameter->id";
                } else {
                    $errors[] = "UnbindLampFromWorker. Не смог найти worker_parameter";
                    throw new \Exception("UnbindLampFromWorker. Не смог найти worker_parameter");
                }
                $worker_parameter_sensor = WorkerBasicController::addWorkerParameterSensor($worker_parameter->id, -1, 0, -1);
                if ($worker_parameter_sensor['status'] == 1) {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $warnings[] = "UnbindLampFromWorker. Отвязал резервный светильник";
                } else {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $errors[] = $worker_parameter_sensor['errors'];
                    throw new \Exception("actionUnbindLamp. Не удалось отвязать резервный светильник");
                }
                /** Удаляем лампу из кеша */
                $ask_response = (new WorkerCacheController())->delSensorWorker($reserve_id);
                if ($ask_response) {
                    $warnings[] = "UnbindLampFromWorker. Удалил привязку сенсора к воркеру в кеше $reserve_id";
                } else {
                    $errors[] = "UnbindLampFromWorker.Не смог удалить привязку сенсора к воркеру в кеше $reserve_id";
                    throw new \Exception("UnbindLampFromWorker. Не смог удалить привязку сенсора к воркеру в кеше $reserve_id");
                }
                /** ТАК КАК ОТВЯЗАЛИ РЕЗЕРВНУЮ ЛАМПУ НАДО В КЕШ ДОБАВИТЬ ПОСТОЯНУЮ ЕСЛИ ОНА ЕСТЬ */
                if ($regular_id != null) {
                    $ask_response = (new WorkerCacheController())->setSensorWorker((int)$regular_id, $worker_id);
                    if ($ask_response) {
                        $warnings[] = "UnbindLampFromWorker. Удалил привязку резервного сенсора к воркеру в кеше $reserve_id  ";
                        $warnings[] = "UnbindLampFromWorker. и добавил постоянную лампу $regular_id для worker_id   $worker_id ";
                    } else {
                        throw new \Exception("UnbindLampFromWorker. Не смог записать постояную лампу в кеш $reserve_id");
                    }
                }
            }
        } catch (\Throwable $ex) {
            $status = 0;
            $errors[] = "UnbindLampFromWorker.Исключение: ";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }

    //SearchLampOnSensor - метод поиска воркера, к которому привязана лампа
    //Метод создан для использования в /admin/serviceamicum/amicum-service/automatic-binding-sensors
    //Разработала: Кендялова М.И. 29.09.2019
    public static function SearchLampOnSensor($sensor_id)
    {
        $errors = array();
        $result = null;
        $warnings = array();
        $status = 1;
        $regular_id = null;
        $reserve_id = null;
        $worker_object_id= null;
        try {
            /**
             * Блок поиска  worker_object_id, к которому привязана постоянная лампа
             */
            $warnings[] = "SearchLampOnSensor. Начинаем искать воркера с постоянной лампой";
            $worker_sensor = (new Query())                                                                              //поиск постоянной лампы в уже привязанных светильниках
            ->select('*')
                ->from('view_worker_parameter_sensor_maxDate_regular_with_workers')
                ->where("sensor_id=" . $sensor_id)
                ->one();
            if ($worker_sensor && $worker_sensor['worker_object_id'] != -1) {
                $worker_object_id = $worker_sensor['worker_object_id'];
                $regular_id = $sensor_id;
                $warnings[] = "SearchLampOnSensor. Нашли воркера $worker_object_id с постоянной лампой";
            }
            unset($worker_sensor);
            /**
             * Блок поиска worker_object_id, к которому привязана резервная лампа
             */
            $warnings[] = "SearchLampOnSensor. Начинаем искать воркера с резервной лампой";
            $worker_sensor = (new Query())                                                                              //поиск постоянной лампы в уже привязанных светильниках
            ->select('*')
                ->from('view_worker_parameter_sensor_maxDate_reserve_with_workers')
                ->where("sensor_id=" . $sensor_id)
                ->one();
            if ($worker_sensor && $worker_sensor['worker_object_id'] != -1) {
                $worker_object_id = $worker_sensor['worker_object_id'];
                $reserve_id = $sensor_id;
                $warnings[] = "SearchLampOnSensor. Нашли $worker_object_id с резервной лампой";
            }
        } catch (\Throwable $ex) {
            $status = 0;
            $errors[] = "SearchLampOnSensor.Исключение: ";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'worker_object_id' => $worker_object_id, 'regular_id' => $regular_id, 'reserve_id' => $reserve_id, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }


    public static function WorkerSearchLamps($worker_object_id)
    {
        $errors = array();    //объявляем пусстой массив ошибок
        $result = null;
        $warnings = array();
        $status = 1;
        $regular_id = null;
        $reserve_id = null;
        try {
//            /**
//             * Блок поиска worker_object_id по переданному worker_id
//             */
//            $worker_object = WorkerObject::find()->where(['worker_id' => $worker_id])->one();
//            if ($worker_object) {
//                $worker_object_id = $worker_object['id'];
//                $warnings[] = "WorkerSearchLamps. Нашел worker_object_id равный $worker_object_id";
//            } else {
//                throw new \Exception("WorkerSearchLamps. Не смог найти worker_object_id для worker_id = $worker_id");
//            }

            /**
             * Блок поиска постоянной лампы по worker_object_id
             */
            $warnings[] = "WorkerSearchLamps. Начинаем искать постоянную лампу у worker_object_id $worker_object_id";
            $worker_sensor = (new Query())                                                                              //поиск постоянной лампы в уже привязанных светильниках
            ->select('*')
                ->from('view_worker_parameter_sensor_maxDate_regular_with_workers')
                ->where("worker_object_id=" . $worker_object_id)
                ->one();
            if ($worker_sensor && $worker_sensor['sensor_id'] != -1) {
                $regular_id = $worker_sensor['sensor_id'];
                $warnings[] = "WorkerSearchLamps. Нашли постоянную лампу $regular_id";
            }

            /**
             * Блок поиска резервной лампы по worker_object_id
             */
            $warnings[] = "WorkerSearchLamps. Начинаем искать резервную лампу у worker_object_id $worker_object_id";
            $worker_sensor = (new Query())                                                                              //поиск постоянной лампы в уже привязанных светильниках
            ->select('*')
                ->from('view_worker_parameter_sensor_maxDate_reserve_with_workers')
                ->where("worker_object_id=" . $worker_object_id)
                ->one();
            if ($worker_sensor && $worker_sensor['sensor_id'] != -1) {
                $reserve_id = $worker_sensor['sensor_id'];
                $warnings[] = "WorkerSearchLamps. Нашли резервную лампу $reserve_id";
            }
        } catch (\Throwable $ex) {
            $status = 0;
            $errors[] = "WorkerSearchLamps.Исключение: ";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'worker_object_id' => $worker_object_id, 'regular_id' => $regular_id, 'reserve_id' => $reserve_id, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }


    /**
     * Название метода: UnbindReserveLampForStrata()
     * Метод отвязки резервной лампы от сотрудника с привязкой постоянной если она есть.
     * Это нужно что б при следующей регистрации работника у него в кеше имелась лампа
     *
     * @param $worker_parameter_id - id параметра работника
     * @param $sensor_id - id лампы которую нужно отвязать
     * @param $worker_id - id работника
     *
     * Входные необязательные параметры
     *
     * @package frontend\controllers
     *
     * Входные обязательные параметры:
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 25.06.2019 10:42
     * @since ver
     */
    public static function UnbindReserveLampForStrata($worker_parameter_id, $sensor_id, $worker_id)
    {
        $errors = array();    //объявляем пусстой массив ошибок
        $result = null;
        $warnings = array();
        $status = 1;
        try {
            $worker_parameter_sensor = WorkerBasicController::addWorkerParameterSensor($worker_parameter_id, -1, 0, -1);
            if ($worker_parameter_sensor['status'] == 1) {
                $warnings[] = $worker_parameter_sensor['warnings'];
                $warnings[] = "UnbindReserveLampForStrata. Отвязал резервный светильник";
            } else {
                $warnings[] = $worker_parameter_sensor['warnings'];
                $errors[] = $worker_parameter_sensor['errors'];
                throw new \Exception("UnbindReserveLampForStrata. Не удалось отвязать резервный светильник");
            }

            /** Удаляем лампу из кеша */
            $ask_response = (new WorkerCacheController())->delSensorWorker($sensor_id);
            if ($ask_response) {
                $warnings[] = "UnbindReserveLampForStrata. Удалил привязку сенсора к воркеру в кеше $sensor_id";
            } else {
                $errors[] = "UnbindReserveLampForStrata.Не смог удалить привязку сенсора к воркеру в кеше $sensor_id";
                throw new \Exception("UnbindReserveLampForStrata. Не смог удалить привязку сенсора к воркеру в кеше $sensor_id");
            }

            /**
             * Блок поиска постоянной лампы по worker_parameter_id
             */
            $warnings[] = "UnbindReserveLampForStrata. Начинаем искать постоянную лампу по $worker_parameter_id";
            $worker_sensor = (new Query())                                                                              //поиск постоянной лампы в уже привязанных светильниках
            ->select('*')
                ->from('view_worker_parameter_sensor_maxDate_regular_with_workers')
                ->where("worker_parameter_id=" . $worker_parameter_id)
                ->one();
            if ($worker_sensor && $worker_sensor['sensor_id'] != -1) {
                $regular_id = $worker_sensor['sensor_id'];
                $warnings[] = "UnbindReserveLampForStrata. Нашли постоянную лампу $regular_id";

                /** ТАК КАК ОТВЯЗАЛИ РЕЗЕРВНУЮ ЛАМПУ НАДО В КЕШ ДОБАВИТЬ ПОСТОЯНУЮ ЕСЛИ ОНА ЕСТЬ
                 *  И СОЗДАТЬ ЕЩЕ РАЗ ЗАПИСЬ В БД ЧТОБ У НЕЕ ДАТА БЫЛА САМАЯ СВЕЖАЯ
                 */
                $ask_response = (new WorkerCacheController())->setSensorWorker((int)$regular_id, $worker_id);
                if ($ask_response) {
                    $warnings[] = "UnbindReserveLampForStrata. Добавил постоянную лампу $regular_id для worker_id   $worker_id ";
                } else {
                    throw new \Exception("UnbindReserveLampForStrata. Не смог записать постояную лампу в кеш $regular_id");
                }
                $worker_parameter_sensor = WorkerBasicController::addWorkerParameterSensor($worker_parameter_id, $regular_id, 1, -1);
                if ($worker_parameter_sensor['status'] == 1) {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $warnings[] = "UnbindReserveLampForStrata. Добавил резервный светильник в БД";
                } else {
                    $warnings[] = $worker_parameter_sensor['warnings'];
                    $errors[] = $worker_parameter_sensor['errors'];
                    throw new \Exception("UnbindReserveLampForStrata. Не удалось добавить резервный светильник в БД");
                }
            }
        } catch (\Throwable $ex) {
            $status = 0;
            $errors[] = "UnbindReserveLampForStrata.Исключение: ";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }
        $result = array('errors' => $errors, 'status' => $status, 'warnings' => $warnings);
        return $result;
    }
}