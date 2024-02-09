<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

//ob_start();

use backend\controllers\Alias;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Mine;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;

//Todo 1. Добавления работника в вебсокет сервер после успешного входа в систему
class SiteController extends Controller
{

    // actionCheckSessionClient - Метод проверки активности сессии пользователя


    use Alias;

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод actionLogin() - Метод входа в систему АМИКУМ
     * @return false|string
     *
     * AD - Active Directory
     *
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * login – логин пользователя
     * password – пароль пользователя
     * activeDirectoryFlag – флаг вида true/false, если true – авторизация через Active Directory, false – авторизация через АМИКУМ.
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "errors":{},                                                        // массив ошибок
     *      "session_id": (число),                                              // идентификатор сессии
     *      "Items": (данные для заполнения сессии),                            // данные сессиии
     *      "socket_errors": (ошибки сокет сервера),                            // ошибки сокет сервера
     *      "worker_data": (данные работника: роль и табельный номер)           // данные работника
     * }
     *
     * АЛГОРИТМ:
     * 1. Получить даные post запросом
     * 2. Зашифровать пароль
     * 3. Получить хэш пароля
     * 4. Логин пустой или равен 'asmo'
     *      да?     записать ошибку
     *      нет?    Флаг AD равен 1
     *                  да?     Вызвать метод авторизации в AD
     *                          Найти пользователя в системе по логину AD
     *                              Не найдено?         Вызвать исключение
     *                  Нет?    Найти пользователя в системе по логину
     *                              Не найдено?         Вызвать исключение
     *                          Получить пароль пользователя
     *                              Пароль пуст?        Вызвать исключение
     *                          Проверить на сходство пароль и хэш сумму
     *                              Пароль не верен?    Вызвать исключение
     * 5. Заполнить сессию
     * 6. Заполнить массив данными для сессии для возврата на фронт
     *
     * @package frontend\controllers
     *
     * @example amicum/site/login?login=admin&password=WHZ7gm&activeDirectoryFlag=0
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.04.2020 17:43
     */
    public function actionLogin()
    {
        $log = new LogAmicumFront("actionLogin");
        $worker_data = [];
        $session_id = null;
        $session_send = [];
        try {
            $post = Assistant::GetServerMethod();
            $login = $post['login'];
            if (!isset($login) || $login == "" || $login == "asmo") {                                            // Если логин не задан
                $log->addError("Заполните поля", __LINE__);
            } else if (isset($login) && $login !== "") {
                $response = UserAutorizationController::actionLogin(
                    json_encode(array(
                        "login" => $login,
                        "password" => $post['password'],
                        "activeDirectoryFlag" => (int)$post['activeDirectoryFlag']
                    ))
                );
                $log->addLogAll($response);
                $worker_data = $response['worker_data'];
                $session_id = $response['session_id'];
                $session_send = $response['Items'];

            }
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $session_send, 'session_id' => $session_id, 'worker_data' => $worker_data], $log->getLogAll());
    }

    public function actionCheckSession()
    {
        $result = "";
        $session = Yii::$app->session;
        if (isset($session['sessionLogin'])) {                                                                          // Если в сессии задан логин
            if (isset($session['sessionPassword'])) {                                                                   // Если в сессии задан пароль
                $result = "Сессия активна";                                                                                  // Вывод лога
            } else {                                                                                                    // Если пароль не задан
                $result = "Пароль сессии не задан";                                                                          // Вывод лога
            }
        } else {                                                                                                        // Если пароль не задан
            $result = "Логин сессии не задан";                                                                               // Вывод лога
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * actionCheckSessionClient - Метод проверки активности сессии пользователя
     * @example http://127.0.0.1/site/check-session-client
     * @return void
     */
    public function actionCheckSessionClient()
    {
        $log = new LogAmicumFront("actionCheckSessionClient", false);
        try {
            $session = Yii::$app->session;
            if (!isset($session['sessionLogin']) or !isset($session['sessionPassword'])) {
                throw new Exception("Сессия не активна");
            }
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => null], $log->getLogShort());
    }

    /**
     * Метод разавторизации пользователя
     * @return void
     * @example http://127.0.0.1/site/logout
     */
    public function actionLogout()
    {
        $result = "";
        $session = Yii::$app->session;
        if (isset($session['sessionLogin'])) {
            if (isset($session['sessionPassword'])) {
                $session->destroy();
                $session->close();
                $succ = "success";
                $result = $succ;
            } else {
                $result = "Пароль сессии не задан";
            }
        } else {
            $result = "Логин сессии не задан";
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function actionDate()
    {
        $result = "";
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        date_default_timezone_set("Asia/Novokuznetsk");
        if ($post['en']) {
            $result = date("Y-m-d H:i:s");
        } else {
            $result = date("d.m.Y H:i:s");
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * Название метода: actionChangeMineInSession()
     * @package app\controllers
     * Метод изменения шахты пользователя в сессии при выборе другой шахты
     * Входные обязательные параметры:
     * $post['mine_id'] - идентификатор шахты
     *
     * @url http://localhost/change-mine-in-session?mine_id=290
     * @url http://localhost/change-mine-in-session
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 22.01.2019 11:07
     * @since ver1.0
     */
    public function actionChangeMineInSession()
    {
        $log = new LogAmicumFront("actionChangeMineInSession");
        $mine_id = -1;
        $mine_id_old = -1;
        $mine_company_id = null;
        $refer = false;

        try {

            $post = Assistant::GetServerMethod();

            if (!isset($post['mine_id']) or $post['mine_id'] == '') {
                throw new Exception("Идентификатор шахты не передан или имеет пустое значение");
            }

            $mine_id = $post['mine_id'];
            $session = Yii::$app->session;
            $session->open();

            if (!isset($session['sessionLogin']) or !isset($session['sessionPassword'])) {
                $refer = true;
                throw new Exception("Пароль или логин сессии не задан");
            }

            $mine_id_old = $session['userMineId'];

            if ($mine_id != -1 and $mine_id != "*") {
                $mine = Mine::findOne($mine_id);
                if (!$mine) {
                    throw new Exception("Указанной шахты не в БД!");
                }

                $mine_company_id = $mine->company_id;

                $session['userMineId'] = $mine->id;
                $session['userMineTitle'] = $mine->title;
                $session['mineCompanyId'] = $mine->company_id;

            } else {
                $session['userMineId'] = -1;
                $session['userMineTitle'] = "Все шахты";
                $session['mineCompanyId'] = null;
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $mine_id, 'mine_id_new' => $mine_id, 'mine_id_old' => $mine_id_old, 'mine_company_id' => $mine_company_id, 'redirect' => $refer], $log->getLogAll());;
    }

    /**
     * Название метода: actionGetSessionData()
     * @package app\controllers
     * Метод проверки сессии на актуальность
     * Входные обязательные параметры:
     *
     * Входные необязательные параметры
     *
     * @url
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 07.02.2019 9:57
     * @since ver0.1
     */
    public function actionGetSessionData()
    {
        $session = Yii::$app->session;
        $errors = "";
        $response = array();
        if (isset($session['sessionLogin']) && isset($session['sessionPassword'])) {
            $response = $session;
        } else {
            $errors = "Время сессии закончилось, или пользователь не авторизован";
        }
        $result = array('response' => $response, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * Метод автоматической генерации табельного номера из существующего табельного номера
     * Название метода: GenerateTabelNumber()
     * @param $tabel_number - табельный номер
     *
     * @return string
     * Документация на портале:
     * @package app\controllers
     *
     * Входные обязательные параметры:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 25.03.2019 10:33
     */
    public static function GenerateTabelNumber($tabel_number)
    {
        return rand(100, 1000) . $tabel_number . rand(1000, 2000);
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

}
