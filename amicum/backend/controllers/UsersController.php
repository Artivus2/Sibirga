<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;
//ob_start();

use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Mine;
use frontend\models\User;
use frontend\models\UserAccess;
use frontend\models\UserPassword;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Response;

class UsersController extends \yii\web\Controller
{
    // actionEditPassword   - Функция редактирования пароля пользователя
    // buildUsers           - Построение списка пользователей
    // actionCreateUser     - Функция добавления учетной записи пользователя
    // actionDeleteUser     - Функция удаления пользователя
    // actionEditUser       - Функция редактирования пользователя
    // actionSendUserRights - Отправка списка прав пользователя
    // actionAddUserAccess  - Добавление прав пользователя
    // actionEditPassword   - Функция редактирования пароля пользователя
    // actionLogout         - Метод разлогинивания на бэке
    // EditPassword         - Метод изменения пароля пользователя
    // actionPassNumber     - Функция редактирования номера пропуска пользователя
    // EditPassNumber       - Метод изменения номера пропуска пользователя

    public $adminOrDeveloperAccess = array();                                                                                                //администратор или разработчик
    public $dispatcherAccess = array(1, 2, 3);                                                                          //диспетчер
    public $mainMechanicAccess = array(48, 49, 50);                                                                     //главный инженер или главный механик
    public $mainEngineerAccess = array();
    public $mainEnergyEngineerAccess = array(3);                                                                        //главный энергетик
    public $lampAccess = array(80, 81);                                                                                   //ламповая
    public $MFSSMechanicAccess = array(45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 75, 76, 77, 78, 79, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94);                                                                         //механик МФСБ
    public $mineSurveyorAccess = array(75, 76, 77);                                                                              //маркшейдерский отдел
    public $ASChiefAccess = array(51, 52, 53);

    /*Функция начального отображения информации*/
    public function actionIndex()
    {
//        Assistant::PrintR($this); die;
        $workers_array = (new Query())
            ->select([
                'employee.last_name',
                'employee.first_name',
                'employee.patronymic',
                'position.title as position',
                'worker.id as id'
            ])
            ->from('worker')
            ->leftJoin('employee', 'employee.id = worker.employee_id')
            ->leftJoin('position', 'position.id = worker.position_id')
            ->orderBy(['employee.last_name' => SORT_ASC])
            ->all();

        $users_array = $this->buildUsers();
//        Assistant::PrintR($users_array);
        $workstations = (new Query())
            ->select([
                'id',
                'title'
            ])
            ->from('workstation')
            ->orderBy(['title' => SORT_ASC])
            ->all();

        $mines = Mine::find()->asArray()->all();

        return $this->render('index',
            [
                'workers' => $workers_array,
                'users' => $users_array,
                'workstations' => $workstations,
                'mines' => $mines
            ]);                                                                                                         //Вернуть данные на страницу
    }

    function buildUsers()
    {
        $users_array = (new Query())
            ->select([
                'user.id as id',
                'user.worker_id as worker_id',
                'user.mine_id as mine_id',
                'mine.title as mine_title',
                'user.login',
                'user.workstation_id as workstation',
                'workstation.title as workstation_title',
                'user.default',
                'employee.last_name',
                'employee.first_name',
                'employee.patronymic',
                'position.title as position'
            ])
            ->from('user')
            ->leftJoin('worker', 'worker.id = user.worker_id')
            ->leftJoin('employee', 'employee.id = worker.employee_id')
            ->leftJoin('position', 'position.id = worker.position_id')
            ->leftJoin('mine', 'mine.id = user.mine_id')
            ->leftJoin('workstation', 'workstation.id = user.workstation_id')
            ->orderBy(['employee.last_name' => SORT_ASC])
            ->all();
        return $users_array;
    }

    /* Функция добавления учетной записи пользователя
     * Входные параметры:
     * $post['worker_id'] - id работника
     * $post['login'] - логин пользвователя
     * $post['workstation_id'] - id рабочего места
     * $post['default'] - флаг учетной записи по умолчанию
     * $post['password'] - пароль пользователя
    */
    public function actionCreateUser()
    {

        $errors = array();
        $user_array = array();
        $rights = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
//        if (isset($session['sessionLogin'])) {                                                                        //если в сессии есть логин
//            if (AccessCheck::checkAccess($session['sessionLogin'], 7)) {                                              //если пользователю разрешен доступ к функции
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        if (isset($post['worker_id']) and $post['worker_id'] != "" && isset($post['login']) && $post['login'] != "") {                                   //Если заданы worker_id и login
            $user = User::findOne(['login' => $post['login']]);                                                         // найти пользователя с таким же логином
            if (!$user) {                                                                                               // Если пользователя нет
                $user = new User();                                                                                     // создать пользователя
                $user->login = $post['login'];                                                                          // логин пользователя
                $user->mine_id = $post['mine_id'];                                                                      // шахтное поле по умолчанию у пользователя
                $user->worker_id = (int)$post['worker_id'];
                if (isset($post['workstation_id'])) {                                                                   // Если не передано рабочее место, оно задается по умолчанию
                    $user->workstation_id = (int)$post['workstation_id'];
                } else {
                    $user->workstation_id = 12;                                                                         // гостевая
                }
                if (isset($post['default'])) {                                                                          // Если не передан флаг по умолчаню, он считается = 1
                    $user->default = (int)$post['default'];
                } else {
                    $user->default = 1;
                }
                if ($user->save()) {                                                                                    // Если пользователь сохранился
                    if (isset($post['password']) && $post['password'] != "") {                                          // если передан пароль
                        $pass = new UserPassword();                                                                     // Создать экземпляр можели ПарольПользователя
                        $pass->user_id = $user->id;                                                                     // Заполниь поля
                        $pass->password =
                            crypt($post['password'], '$5$rounds=5000$' . dechex(crc32($post['login'])) . '$') . "\n";        //Выполнить хеширование пароля методом SHA-256
                        $pass->check_sum = dechex(crc32($post['password']));                                            // и crc32
                        $pass->date_time = date("Y-m-d H:i:s");
                        if (!$pass->save()) {                                                                           // Сохранить запись
                            $errors[] = "не удалось сохранить пароль пользователя";
                        } else {
                            DepartmentController::SetDepartmentParameterSettings($post['worker_id']);
                            switch ($user->workstation_id) {                                                            // разделяем права в зависимости от рабочего места
                                case 1:                                                                                 // Диспетчер
                                    $rights = $this->dispatcherAccess;
                                    break;
                                case 2:                                                                                 // Директор
                                    $rights = array();
                                    break;
                                case 3:                                                                                 // Администартор
                                    $rights = (new Query())
                                        ->select('id')
                                        ->from('access')
                                        ->where('id > 3')
                                        ->all();
                                    break;
                                case 4:                                                                                 // Главный инженер
                                    $rights = $this->mainEngineerAccess;
                                    break;
                                case 5:                                                                                 // Главный механик
                                    $rights = $this->mainMechanicAccess;
                                    break;
                                case 6:                                                                                 // Главный энергетик
                                    $rights = $this->mainEnergyEngineerAccess;
                                    break;
                                case 7:                                                                                 // Ламповая
                                    $rights = $this->lampAccess;
                                    break;
                                case 8:                                                                                 // Механик МФСБ
                                    $rights = $this->MFSSMechanicAccess;
                                    break;
                                case 9:                                                                                 // Маркшейдерский отдел
                                    $rights = $this->mineSurveyorAccess;
                                    break;
                                case 10:                                                                                // Инженер-оператор АБ
                                    $rights = $this->ASChiefAccess;
                                    break;
                                case 11:                                                                                // Участок вентиляции
                                    $rights = array();
                                    break;
                                case 12:                                                                                // Гостевая
                                    $rights = array();
                                    break;
                                case 13:                                                                                // Разработчик
                                    $rights = (new Query())
                                        ->select('id')
                                        ->from('access')
                                        ->where('id > 3')
                                        ->all();
                                    break;
                                case 14:                                                                                // Главный механик
                                    $rights = $this->mainEnergyEngineerAccess;
                                    break;
                                case 15:                                                                                // Главный механик
                                    $rights = $this->mainEnergyEngineerAccess;
                                    break;
                                case 16:                                                                                // Главный механик
                                    $rights = $this->mainEnergyEngineerAccess;
                                    break;
                            }
                            $user_array = $this->buildUsers();
                            if (!empty($rights)) {
                                foreach ($rights as $right) {                                                           // для каждого права доступа
                                    $userRight = new UserAccess();
                                    $userRight->access_id = (int)$right;
                                    $userRight->user_id = $user->id;
                                    if (!$userRight->save()) {
                                        $errors[] = "Ошибка сохранения прав пользователя";
                                    }
                                }
                            }
                        }
                    } else {
                        $errors[] = "Пароль не передан";
                    }
                } else {
                    $errors[] = "Не удалось добавить пользователя";
                }
            } else {
                $errors[] = "Такой логин уже существует";
            }
        } else {
            $errors[] = "Не передан worker_id или логин ";
        }
//            }
//            else {
//                $errors[] = "Нет доступа";
//            }
//        } else {
//            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
//        }
        $result = array('errors' => $errors, 'users' => $user_array);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /* Функция удаления пользователя
     * Входные параметры
     * $post["login"] - логин удаляемого пользователя
    */
    public function actionDeleteUser()
    {
        $msg = "";
        $users_array = array();
        $errors = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 9)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post["user_id"])) {                                                                                     //Если логин передан
                    $user = User::findOne($post["user_id"]);                                                           //найти пользователя по id
                    if ($user) {                                                                                                //если найден
                        UserAccess::deleteAll(['user_id' => $user->id]);
                        if ($user->delete()) {                                                                                    //удалить его
                            $msg = "Пользователь удалён";
                        } else {
                            $errors[] = "Не удалось удалить пользователя";
                        }
                    } else {
                        $errors[] = "Пользователь не найден";
                    }
                } else {
                    $errors[] = "Не передан идентификатор пользователя";
                }
            } else {
                $errors[] = "Нет доступа";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $users_array = $this->buildUsers();
        $result = array('errors' => $errors, 'users' => $users_array, 'status' => $msg);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /* Функция редактирования пользователя
     * Входные параметры:
     * $post["login"] - логин пользователя
     * $post['workstation_id'] - рабочее место (новое)
     * $post['default'] - флаг учетки по умолчанию (новый)
     * $post['password'] - пароль (новый)
    */
    public function actionEditUser()
    {
        $msg = "";
        $errors = array();
        $user_array = array();
        $session = Yii::$app->session;                                                                                  // старт сессии
        $session->open();                                                                                               // открыть сессию
        if (isset($session['sessionLogin'])) {                                                                          // если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 8)) {                                        // если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (
                    isset($post["user_id"]) and $post['user_id'] != "" and
                    isset($post["worker_id"]) and $post['worker_id'] != "" and
                    isset($post["login"]) and $post['login'] != "" and
                    isset($post["mine_id"]) and $post['mine_id'] != "" and
                    isset($post["workstation_id"]) and $post['workstation_id'] != "" and
                    isset($post["default"]) and $post['default'] != ""
                ) {                                               // если передан логин
                    $user = User::findOne($post["user_id"]);                                                            // наййти пользователя с этим логином
                    if ($user) {                                                                                        // если пользователь найден
                        $user->worker_id = (int)$post['worker_id'];                                                     // ключ работника
                        $user->login = $post['login'];                                                                  // логин
                        $user->default = (int)$post['default'];                                                         // учетка по умолчанию
                        $user->workstation_id = (int)$post['workstation_id'];                                           // рабочая станция
                        $user->mine_id = (int)$post['mine_id'];                                                         // шахтное поле

                        if (!$user->save()) {
                            $errors[] = $user->errors;
                            $errors[] = "Ошибка сохранения пользователя";
                        }
                        $user_array = $this->buildUsers();
                    } else {
                        $errors[] = "Пользователь не найден";
                    }
                } else {
                    $errors[] = "Не передан идентификатор пользователя";
                }
            } else {
                $errors[] = "Не переданы входные параметры";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'message' => $msg, 'users' => $user_array);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function actionSendUserRights()
    {
        $errors = array();
        $new_accesses = array();
        $post = Yii::$app->request->post();
        if (isset($post['user_id']) and $post['user_id'] != "") {
            $user_id = $post['user_id'];
            $accesses = (new Query())
                ->select('access.id as id, description, page.title as page')
                ->from('access')
                ->leftJoin('page', 'page.id=access.page_id')
                ->orderBy('page DESC')
                ->all();                                                                                                // получить список прав доступа с описанием
            $i = -1;                                                                                                    // для первого уровня 'page', чтобы сгруппировать по 'page'
            $j = 0;                                                                                                     // для второго уровня функций
            foreach ($accesses as $right) {
                if ($i == -1 or $new_accesses[$i]['page'] != $right['page']) {                                          // если $i == -1 или в старом массиве нет такой 'page'
                    $i++;                                                                                               // увеличиваем итератор 'page'
                    $new_accesses[$i]['page'] = $right['page'];
                    $j = 0;
                }
                $new_accesses[$i]['rights'][$j]['id'] = $right['id'];
                $new_accesses[$i]['rights'][$j]['description'] = $right['description'];
                $user_access = UserAccess::findOne(['user_id' => $user_id, 'access_id' => $right['id']]);

                if (isset($user_access)) {
                    $new_accesses[$i]['rights'][$j]['has_this_right'] = true;
                } else {
                    $new_accesses[$i]['rights'][$j]['has_this_right'] = false;
                }
                $j++;
            }
        } else {
            $errors[] = "Не передан id пользователя";
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('errors' => $errors, 'accesses_array' => $new_accesses);
    }

    public function actionAddUserAccess()
    {
        $errors = array();
        $error_ids = null;
        $msg = '';
        $session = Yii::$app->session;                                                                                  // старт сессии
        $session->open();                                                                                               // открыть сессию
        if (isset($session['sessionLogin'])) {                                                                          // если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 10)) {                                       // если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
//                $errors[] = $post['user_rights'];
                if (isset($post['user_id']) and $post['user_id'] != "" and isset($post['user_rights'])) {               // если задан логин
                    $user_id = (int)$post['user_id'];
                    $rights = $post['user_rights'];
                    $user = User::findOne(['id' => $user_id]);                                                          //найти пользователя с таким логином
                    if ($user) {                                                                                        //если пользователь есть
                        UserAccess::deleteAll(['user_id' => $user_id]);
                        if (is_array($rights) and !empty($rights)) {
                            foreach ($rights as $right) {                                                               //для каждого права доступа
                                //если не найдено
                                $userRight = new UserAccess();                                                          //привязать право доступа
                                $userRight->access_id = (int)$right;
                                $userRight->user_id = $user->id;
                                if (!$userRight->save()) {
                                    $error_ids .= $right;
                                }
                            }
                        } else {
                            $msg = 'Права были удалены';
                        }
                    } else {
                        $errors[] = "Пользователь не найден";
                    }
                } else {
                    $errors[] = "Не передан идентификатор пользователя или массив прав";
                }
            } else {
                $errors[] = "Нет доступа";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }

        $result = array('errors' => $errors, 'error_ids' => $error_ids, 'msg' => $msg);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /* actionEditPassword - Функция редактирования пароля пользователя
     * Входные параметры:
     * $post["user_id"] - ключ пользователя
     * $post['login'] - логин пользователя
     * $post['password'] - пароль (новый)
    */
    public function actionEditPassword()
    {
        $msg = "Пароль успешно изменен";
        $errors = array();
        $user_array = array();

        $log = new LogAmicumFront("actionEditPassword");
        $result = null;

        try {
            $session = Yii::$app->session;                                                                                  // старт сессии
            $session->open();                                                                                               // открыть сессию

            if (!isset($session['sessionLogin'])) {                                                                          // если в сессии есть логин
                throw new Exception("Время сессии закончилось. Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 8)) {                                        // если пользователю разрешен доступ к функции
                throw new Exception("Не достаточно прав для выполнения запроса");
            }

            $post = Assistant::GetServerMethod();
            $log->addData($post, '$post', __LINE__);

            if (
                !isset($post["user_id"]) or $post['user_id'] == "" and
                !isset($post["login"]) or $post['login'] == "" and
                !isset($post["password"]) or $post['password'] == ""
            ) {                                               // если передан логин
                throw new Exception("Не передан идентификатор пользователя");
            }

            $user_id = $post["user_id"];
            $login = $post["login"];
            $password = $post["password"];

            $user = User::findOne(['id' => $user_id]);                                                                  // найти пользователя с этим ключом

            if (!$user) {                                                                                        // если пользователь найден
                throw new Exception("Пользователь не найден");
            }

            $response = self::EditPassword($user_id, $login, $password);
            $log->addLogAll($response);

            if ($response['status'] != 1) {
                throw new Exception("Ошибка сохранения пароля");
            }

            $user_array = $this->buildUsers();


        } catch (Throwable $ex) {
            $msg = $ex->getMessage();
            $log->addError($ex->getMessage(), $ex->getLine());
//            $this->redirect('/');
        }

        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(["Items" => $result, 'message' => $msg, 'users' => $user_array], $log->getLogAll());
    }

    /**
     * EditPassword - Метод изменения пароля пользователя
     * @param $user_id - ключ пользователя
     * @param $login - логин пользователя
     * @param $password - пароль пользователя
     * @return array|null[]
     */
    public static function EditPassword($user_id, $login, $password)
    {
        $log = new LogAmicumFront("EditPassword");
        $result = null;
        try {
            $log->addLog("Начало выполнения метода");
            $user = User::findOne(['id' => $user_id]);                                                                  // найти пользователя с этим ключом
            if ($user) {                                                                                                // если пользователь найден
                $pass = UserPassword::findOne(['user_id' => $user_id]);                                                 // найти старый пароль пользователя с этим ключом пользователя
                if (!$pass) {
                    $pass = new UserPassword();                                                                         // создать новый экземпляр модели UserPassword
                }
                $pass->user_id = $user->id;                                                                             // сохранить в него поля
                $pass->password = crypt($password, '$5$rounds=5000$' . dechex(crc32($login)) . '$') . "\n";
                $pass->check_sum = dechex(crc32($password));
                $pass->date_time = Assistant::GetDateTimeNow();
                if (!$pass->save()) {                                                                                   // сохранить пароль
                    $log->addData($pass->errors, '$pass->errors', __LINE__);
                    throw new Exception("Не удалось изменить пароль");
                }
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(["Items" => $result], $log->getLogAll());
    }

    /**
     * actionLogout - метод разлогинивания на бэке
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * actionPassNumber - Функция редактирования номера пропуска пользователя
     * Входные параметры:
     *  $post["user_id"] - ключ пользователя
     *  $post["pass_number"] - номер пропуска (новый)
     * @return array
     * @example http://127.0.0.1/admin/users/edit-pass-number?user_id=1&pass_number=9999999999
     */
    public function actionEditPassNumber()
    {
        $log = new LogAmicumFront("actionPassNumber");
        $result = null;

        try {
            $session = Yii::$app->session;
            $session->open();

            if (!isset($session['sessionLogin'])) {
                throw new Exception("Время сессии закончилось. Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 8)) {
                throw new Exception("Не достаточно прав для выполнения запроса");
            }

            $post = Assistant::GetServerMethod();
            $log->addData($post, '$post', __LINE__);

            if (
                !isset($post["user_id"]) or $post['user_id'] == "" and
                !isset($post["pass_number"]) or $post['pass_number'] == ""
            ) {
                throw new Exception("Не передан идентификатор пользователя");
            }

            $user_id = $post["user_id"];
            $pass_number = $post["pass_number"];

            $response = self::EditPassNumber($user_id, $pass_number);
            $log->addLogAll($response);

            if ($response['status'] != 1) {
                throw new Exception("Ошибка сохранения pass_number");
            }

            $result['user'] = $this->buildUsers();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(["Items" => $result], $log->getLogAll());
    }

    /**
     * EditPassNumber - Метод изменения номера пропуска пользователя
     * @param $user_id - ключ пользователя
     * @param $pass_number - UID карты пользователя
     * @return array|null[]
     */
    public static function EditPassNumber($user_id, $pass_number)
    {
        $log = new LogAmicumFront("EditPassNumber");
        $result = null;
        try {
            $log->addLog("Начало выполнения метода");

            $user = User::find()
                ->where(['user.id' => $user_id])
                ->innerJoinWith('worker')
                ->one();

            if ($user->worker) {

                $response = WorkerMainController::getOrSetWorkerParameter($user->worker->id, 2, 1);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Не смог найти WorkerParameter');
                }
                if (!isset($response['worker_parameter_id'])) {
                    $response = WorkerMainController::createWorkerParameter($user->worker->id, 2, 1);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Не смог создать WorkerParameter');
                    }
                }

                $response = WorkerBasicController::addWorkerParameterHandbookValue($response['worker_parameter_id'], $pass_number, 1, Assistant::GetDateTimeNow());
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Не смог создать WorkerParameterHandbookValue');
                }

            } else {
                throw new Exception("Пользователь не найден");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(["Items" => $result], $log->getLogAll());
    }
}
