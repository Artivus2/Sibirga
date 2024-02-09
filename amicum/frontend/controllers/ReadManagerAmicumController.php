<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers;


use backend\controllers\LogAmicum;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;


require_once __DIR__ . '/../controllers/../../DebugCode.php';

class ReadManagerAmicumController extends Controller
{


    // Центральные метод по обработке входящих запросов на запись и чтение от клиента
    // принимает запросы от клиента для последующего вызова запрашиваемого метода
    // логирует свои действия в таблицу user_action_log , в том числе и возникающие ошибки.
    // ошибки пишутся только логические/обрабатываемые, связанные с самим методом
    // исключения не пишутся
    // ['controller'],  - название вызываемого контроллера со стороны фронтенда(ОБЯЗАТЕЛЬНО без суффикса Controller, например НЕ SensorInfoController, а SensorInfo)
    // $post['method'],      - название вызываемого метода контроллера со стороны фронтенда
    // JSON['data'],         - входные параметеры метода в виде JSON строки
    // TEXT['subscribe']     - на какой канал оповещать, в виде обычного текста - это фактически ключ подписки у веб сокета
    //  вызова: http://192.168.2.4/read-manager-amicum?controller=HandbookUnit&method=buildArray&subscribe=handbook-unit&data=[]
    // все входные параметры должны существовать, но могут быть пустыми только data и subscribe
    // описание способа взаимодействия:
    // клиент отправляет запрос в менеджер ReadManagerAmicum, данный менеджер вызывает запрашиваемый контроллер controller и
    // метод method с фильтром data. После чего результат работы этого метода отправляется на сервер redis(PUB/SUB) в конкретную
    // подписку subscribe, если она задана. В обратно данный метод временно дублирует результирующие данные, ошибки и предупреждения
    // Клиент должен быть подключен к веб сокету, к конкретной интересующей его подписке или к нескольким одновременно
    // после успешной отработки данного метода он получит интересующие его данные в сообщении от веб сокета.
    public function actionIndex()
    {
        $post = Assistant::GetServerMethod();                                                                           // получение данных из POST/GET
        $microtime_start = microtime(true);                                                                     // задаем начало времени выполнения запроса
        $status = 1;                                                                                                    // статус выполнения метода приравниваем к 1, по мере выполнения может обнулятся, в случае если подметоды возвращают 0, притом сам метод может выполняться полностью, но с логическими ошибками
        $warnings = array();                                                                                            // массив предупреждений
        $method = "";                                                                                                   // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $debug = array();                                                                                               // массив отладочных сообщений
        $debug_data = array();                                                                                          // массив отладочных данных
        $result = array();                                                                                              // промежуточный результирующий массив
        $session = Yii::$app->session;                                                                                  // проверяем достаточно ли прав пользователю для выполнения метода, на основе данных сессиии по табельном номеру
        try {
            if (!isset($session['userStaffNumber']) ||
                is_null($session['userStaffNumber']) ||
                $session['userStaffNumber'] == ""
            ) {
                if (isset($post['controller']) and $post['controller'] != "UserAutorization") {                         //проверка на первичную авторизацию, в том случае если это первая авторизация, то метод должен запустить на авторизацию - нужно мобильным устройствам
                    $result = array(
                        'Items' => $result,
                        'status' => 0,
                        'errors' => array("Недостаточно прав для выполнения запроса. Выполните повторную авторизацию"),
                        'warnings' => $warnings
                    );

                    LogAmicum::LogAccessAmicum(Assistant::GetDateTimeNow(), $session);                                      // записываем в журнал сведения о нарушении доступа
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    Yii::$app->response->data = $result;
                    return $result;
                }
            }

            $tabel_number = $session['tabel_number'];
            $namespace = 'frontend\controllers\\';                                                                      // пространство имен Yii2
            if (                                                                                                        // проверка входных параметров
                isset
                (
                    $post['controller'],                                                                                // название вызываемого контроллера со стороны фронтенда(ОБЯЗАТЕЛЬНО без суффикса Controller, например НЕ SensorInfoController, а SensorInfo)
                    $post['method'],                                                                                    // название вызываемого метода контроллера со стороны фронтенда
                    $post['data'],                                                                                      // входные параметеры метода
                    $post['subscribe']                                                                                  // на какой канал оповещать
                ) &&
                $post['controller'] != '' &&
                $post['method'] != ''
            ) {
                $check_method_name = $post['method'];
                if ($check_method_name == "actionLogin") {
                    $response = UserAutorizationController::actionLogin($post['data']);
                    if ($response['status'] != 1) {
                        $result = array(
                            'Items' => null,
                            'status' => 0,
                            'errors' => $response['errors'],
                            'warnings' => $response['warnings']
                        );

                        LogAmicum::LogAccessAmicum(Assistant::GetDateNow(), $session);                                  // записываем в журнал сведения о нарушении доступа
                        Yii::$app->response->format = Response::FORMAT_JSON;
                        Yii::$app->response->data = $result;
                        return $result;
                    }
                    $result = $response["Items"];
                } else {
                    $user_id = $session['user_id'];

                    $response = UserAutorizationController::checkPermissionUser($user_id, $check_method_name);
                    if (!$response['Items']) {
                        LogAmicum::LogAccessAmicum(Assistant::GetDateNow(), $session, $tabel_number, 0);    // записываем в журнал сведения о нарушении доступа
                        $result = array(
                            'Items' => null,
                            'status' => 0,
                            'errors' => array("Недостаточно прав для выполнения запроса. Обратитесь к системному администратору для получения прав"),
                            'warnings' => $warnings,
                            'debug' => $debug
                        );
                        Yii::$app->response->format = Response::FORMAT_JSON;
                        Yii::$app->response->data = $result;
                        return $result;
                    }
//
                    $controller = $post['controller'];                                                                  // название вызываемого контроллера со стороны фронтенда
                    $method = $post['method'];                                                                          // название вызываемого метода контроллера со стороны фронтенда
                    $data = $post['data'];                                                                              // входные параметры метода
                    $subscribe = $post['subscribe'];                                                                    // на какой канал оповещать

                    $controller .= 'Controller';
                    $controller = $namespace . $controller;
                    if (method_exists($controller, $method)) {                                                          // проверка контроллера или метода на существование перед его вызовом
                        $response_amicum_method = $controller::$method($data);                                          // вызываем метод запрашиваемый клиентом
                        $result = $response_amicum_method['Items'];                                                     // конвертим результат как есть в строку для отправки в БД логов
                        if ($post['subscribe'] != '') {
                            //Yii::$app->redis_service->publish($subscribe, json_encode($result));                      // опубликуем данные
                        } else
                            $warnings[] = "ReadManagerAmicum. Входной параметр названия подписки не задан";
                        $status = $response_amicum_method['status'];
                        $warnings = $response_amicum_method['warnings'];
                        $errors = $response_amicum_method['errors'];
                        if (isset($response_amicum_method['debug'])) {
                            $debug[] = $response_amicum_method['debug'];
                        }
                        if (isset($response_amicum_method['debug_data'])) {
                            $debug_data[] = $response_amicum_method['debug_data'];
                        }
                    } else {
                        $status = 0;
                        $errors[] = "ReadManagerAmicum. Вызываемый контроллер $controller или метод $method не существуют, проверьте входные параметры";
                    }
                }
            } else {
                $status = 0;
                $errors[] = "ReadManagerAmicum. Входные параметры не переданы";
            }

            /***** Логирование в БД *****/
            $tabel_number = $session['userStaffNumber'];
            $post = json_encode($post);
            $errors_insert = json_encode($errors);
            $duration_method = round(microtime(true) - $microtime_start, 6);                        //расчет времени выполнения метода
            if (
                $method != "GetDepartmentListWithWorkers" and
                $method != "GetDepartmentList" and
                $method != "GetUndergroundPlaceList" and
                $method != "GetPlacesList" and
                $method != "GetWorkersForHandbook" and
                $method != "actionGetRoomsByWorker" and
                $method != "GetShiftList" and
                $method != "GetWorkingTimeList" and
                $method != "getZipperJournal" and
                $method != "GetDepartmentListRecursiv" and
                $method != "GetUnitList" and
                $method != "GetKindWorkingTimeList" and
                $method != "GetWorkModes" and
                $method != "GetEquipmentList" and
                $method != "GetRoleList" and
                $method != "GetListRoleWorkersSearch" and
                $method != "GetListStatus" and
                $method != "TypeOperationsList" and
                $method != "GetTemplateOrderList" and
                $method != "GetPlaceRoute" and
                $method != "GetInstructionPB" and
                $method != "GetObjectType" and
                $method != "GetProdGraphicWork" and
                $method != "GetRouteTemplateList" and
                $method != "GetListBrigade" and
                $method != "GetPassports" and
                $method != "GetPlacePassports" and
                $method != "GetTypicalObjects" and
                $method != "GetOrderVtbAb" and
                $method != "GetShiftType" and
                $method != "GetWorkersWithCompany" and
                $method != "UpdateProject" and
                $method != "GetListPosition" and
                $method != "GetTechWorksActual" and
                $method != "GetNewUpdateArchives" and
                $method != "GetPlaceList" and
                $method != "GetDepParameter" and
                $method != "GetAllParameters" and
                $method != "GetListPlaceWithHandbook" and
                $method != "GetOperationsList"
            ) {
                LogAmicum::LogEventAmicum($method, Assistant::GetDateTimeNow(), $duration_method, $post, json_encode($result), $errors_insert, $tabel_number);//записываем в журнал сведения о выполнении метода
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = $e->getLine();
            $errors[] = $e->getMessage();
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug, 'debug_data' => $debug_data);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }
}
