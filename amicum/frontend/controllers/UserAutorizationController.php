<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/**
 * Created by PhpStorm.
 * User: Ingener401
 * Date: 25.10.2018
 * Time: 13:49
 */

namespace frontend\controllers;

use backend\controllers\Assistant;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\LogAmicum;
use Exception;
use frontend\controllers\handbooks\HandbookDepartmentController;
use frontend\models\Access;
use frontend\models\ModulAmicum;
use frontend\models\Page;
use frontend\models\User;
use frontend\models\UserWorkstation;
use frontend\models\Workstation;
use frontend\models\WorkstationPage;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

/**
 * Класс для авторизации пользователей в системе АМИКУМ
 * @package frontend\controllers
 */
class UserAutorizationController extends Controller
{
    // UserLoginWithAd                              - Авторизация пользователя с аутентификацией в Active Directory
    // GetSystemAmicumRole                          - метод получения ролей доступа в систему
    // SaveSystemAmicumRole                         - метод сохранения ролей доступа в систему
    // DeleteSystemAmicumRole                       - метод удаления роли доступа в систему

    // GetWorkstation                               - Получение справочника рабочих мест
    // SaveWorkstation                              - Сохранение справочника ролей
    // DeleteWorkstation                            - Удаление справочника ролей

    // GetPage                                      - Получение справочника разделов/страниц АМИКУМ
    // GetAccess                                    - Получение справочника методов АМИКУМ

    // GetUsers                                     - Получение справочника пользоателей системы АМИКУМ
    // SaveUser                                     - Сохранение справочника пользователей
    // DeleteUser                                   - Удаление справочника пользователей

    // GetUserAmicumRole                            - метод получения ролей доступа пользователя в систему
    // SaveUserAmicumRole                           - метод сохранения прав доступа пользователей в систему
    // DeleteUserAmicumRole                         - метод удаления прав доступа пользователей в систему

    // GetModuleAmicum                              - Получение справочника модулей АМИКУМ
    // SaveModulAmicum()                            - Сохранение справочника модулей амикум
    // DeleteModulAmicum()                          - Удаление справочника модулей амикум


    /**
     * Метод проверки прав пользователя на основе шифрования
     * @param $login - логин пользователя
     * @param $password - пароль пользователя
     * @return $status            - статус выполнения 0 есть ошибки метод не выполнен, 1 выполнено без ошибок. Параметр текстовый
     * @return $errors            - массив ошибок в виде текстовых строк
     * @return $warnings          - массив предупреждений
     * параметры хранящиеся в сессии:
     * ['sessionLogin']                    - логин пользователя
     * ['sessionPassword']                 - пароль
     * ['userWorkstation']                 - рабочее место
     * ['employee_id']                     - уникальный ключ человека
     * ['userName']                        - ФИО пользователя
     * ['last_name']                       - Фамилия пользователя
     * ['first_name']                      - Имя пользователя
     * ['patronymic']                      - Отчество пользователя
     * ['position_id']                     - ключ должности пользователя
     * ['position_title']                  - Должность пользователя
     * ['position_qualification']          - квалификация пользователя
     * ['birthdate']                       - день рождения пользователя
     * ['worker_date_start']                      - дата начала работы
     * ['worker_date_end']                        - дата окончания работы
     * ['userShift']                       - смену
     * ['userCompany']                     - наименование предприятия
     * ['userCompanyId']                   - id Предприятия
     * ['userMineId']                      - id Шахты
     * ['userMineTitle']                   - наименование Шахты
     * ['userDepartmentId']                - уникальный ключ департамента
     * ['userDepartmentTitle']             - название департамента справочное
     * ['userDepartmentPath']              - путь до подразделения пользователя
     * ['userWorkCompanyDepartmentId']     - записываем главный ключ департамента подразделения (в котором он работает, меняться не может)
     * ['userCompanyDepartmentId']         - записываем главный ключ департамента подразделения (выбранный департамент в менюшке)
     * ['userStaffNumber']                 - табельный номер сотрудника
     * ['worker_id']                       - ключ работника
     * ['workerObject_ids']                - массив ключей конкретных воркеров (подземный/поверхностный)
     * ['session_id']                      - ключ сессии
     * ['user_id']                         - ключ пользователя
     * ['worker_role']                     - список ролей сотрудника (id, title)
     * ['socket_key']                      - случайный ключ сессии для вебсокета
     * ['user_image_src']                  - путь до фотографии пользователя
     * ['userTheme']                       - тема пользователя
     */
    // http://127.0.0.1/read-manager-amicum?controller=UserAutorization&method=actionLogin&subscribe=login&data={"uid":"0D EF 3A 79"}
    // http://127.0.0.1/read-manager-amicum?controller=UserAutorization&method=actionLogin&subscribe=login&data={"login":"ingener401","password":"Pupkin","activeDirectoryFlag":false}
    // http://127.0.0.1/admin/read-manager-amicum?controller=UserAutorization&method=actionLogin&subscribe=&data={"login":"ingener401","password":"Pupkin","activeDirectoryFlag":false}
    public static function actionLogin($data_post = null)
    {
        $worker_data = array();
        $worker_role_id = null;
        $session_amicum = Yii::$app->session;                                                                           // получаем текущую сессию пользователя
        $session_id = null;                                                                                             // уникальный идентификатор сессии
        $status = 1;                                                                                                    // флаг успешного выполнения метода
        $method_name = 'actionLogin';                                                                                   // наименование метода
        $warnings = array();                                                                                            // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = null;                                                                                              // промежуточный результирующий массив
        $worker_role_list = null;
        $workerObject = array();                                                                                        // массив конкретных работников (подземных или поверхностных)
        if (!is_null($data_post) and $data_post != "") {
            $warnings[] = $method_name . ". данные успешно переданы";
            $warnings[] = $method_name . ". Входной массив данных" . $data_post;
            try {
                $login_passwd = json_decode($data_post);                                                                // декодируем входной массив данных
                $warnings[] = $method_name . ". декодировал входные параметры";
                if (property_exists($login_passwd, 'login') and property_exists($login_passwd, 'password')
                    and property_exists($login_passwd, 'activeDirectoryFlag'))                                  // и проверяем наличие в нем нужных нам полей
                {
                    $login = $login_passwd->login;                                                                          // логин пользователя
                    $active_directory_flag = $login_passwd->activeDirectoryFlag;                                            // флаг через что авторизоваться: true - через AD, false - через систему
                }else if (property_exists($login_passwd, 'uid')) {
                    $pass_number = $login_passwd->uid;
                } else {
                    throw new Exception($method_name . '. Ошибка в наименование параметра во входных данных');
                }

                $warnings[] = $method_name . ". Проверил входные данные";

                if (isset($login) && $active_directory_flag) {
                    $warnings[] = $method_name . '. Авторизация пользователя через AD';
                    $response = self::AutorizationAD($login, $login_passwd->password);                                  // вызов метода авторизации в AD

                    if ($response['status'] == 1) {
                        $status_autorization = $response['Items'];
                        if (!$status_autorization) {
                            throw new Exception($method_name . '. Не верный логин и пароль)');
                        }
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new Exception($method_name . '. Ошибка при авторизации в Active Directory (метод)');
                    }

                    $split_login = explode("\\", $login);
                    $login_split = null;
                    if (count($split_login) == 2) {
                        $login_split = $split_login[1];
                    }

//                    $user = User::findOne(['user_ad_id' => $login]);
                    $user = User::find()
                        ->where(['login' => $login_split])
                        ->orWhere(['user_ad_id' => $login])
                        ->orWhere(['user_ad_id' => $login_split])
                        ->one();
                    if (empty($user)) {
                        throw new Exception('Не найдена учётная запись пользователя в системе Амикум');
                    }
                } else if (isset($login)) {
                    $user = User::findOne(['login' => $login]);                                                         // Ищем совпадения в пользователях
                    if (!$user) {                                                                                       // Если нет совпадение
                        throw new Exception($method_name . '. Ошибка. Такого пользователя не существует');     // вызов исключения
                    }
                    $warnings[] = $method_name . ". Нашел пользователя user_id =" . $user->id;
                    $warnings[] = $method_name . '. Авторизация пользователя через систему Amicum';
                    $userPass = $user->getUserPasswords()->orderBy(['date_time' => SORT_DESC])->one();                  // Ищем последний по дате пароль пользователя
                    if ($userPass) {
                        $password = crypt($login_passwd->password, '$5$rounds=5000$' . dechex(crc32($login)) . '$') . "\n"; //получаем хеш пароля пользователя
                        $check_sum = dechex(crc32($login_passwd->password));                                            //получение контрольной суммы по паролю
                        $pass = $userPass->password;
                        $check = $userPass->check_sum;
                        if ($pass != $password && $check != $check_sum) {                                               // Если пароли не совпали
                            LogAmicum::LogAccessAmicum(                                                                 // записываем в журнал сведения о нарушении доступа
                                date("y.m.d H:i:s"), $data_post);
                            throw new Exception($method_name . '. Ошибка. Пароль передан не верно');
                        }
                    } else {
                        throw new Exception($method_name . '. Для пользователя не задан пароль в БД');         // вывод ошибки: Нет пароля для пользователя в БД
                    }
                } else {
                    $user_pass_number = (new Query())
                        ->select('worker_id')
                        ->from('view_initWorkerParameterHandbookValue')
                        ->where([
                            'value' => $pass_number,
                            'parameter_id' => 2,
                            'parameter_type_id' => 1
                        ])
                        ->one();

                    if (!isset($user_pass_number['worker_id'])) {
                        throw new Exception($method_name . '. Ошибка. UID передан не верно');
                    }

                    $user = User::findOne(['worker_id' => $user_pass_number['worker_id']]);

                    if (!$user) {
                        throw new Exception($method_name . '. Ошибка. Такого пользователя не существует');
                    }
                }

                if (!$user->auth_key) {
                    $user->generateAuthKey();
                    $user->save();
                }

                Yii::$app->user->login($user, 3600 * 24 * 30);

                $warnings[] = $method_name . ". проверку авторизации прошел. Заполняю сессию";
                $employee_id = $user->worker->employee->id;                                                             // Уникальный ключ человека
                $user_id = $user->id;                                                                                   // Уникальный ключ пользователя
                $lastName = $user->worker->employee->last_name;                                                         // Записываем фамилию
                $firstName = $user->worker->employee->first_name;                                                       // Записываем имя
                $patronymic = $user->worker->employee->patronymic;                                                      // Записываем отчество
                $gender = $user->worker->employee->gender;                                                              // Записываем гендерный признак
                $birthdate = $user->worker->employee->birthdate;                                                        // Записываем день рождение пользователя
                $fullname = $lastName . ". " . mb_substr($firstName, 0, 1, "UTF-8") . ". " . mb_substr($patronymic, 0, 1, "UTF-8") . ".";   // Оставляем в имени и отчестве по одной букве
                $shift = "";                                                                                            // Записываем смену
                $company = $user->worker->companyDepartment->company->title;                                            // Записываем название компании
                $company_id = $user->worker->companyDepartment->company->id;                                            // Записываем id компании
                $department_id = $user->worker->companyDepartment->department_id;
                $department_title = $user->worker->companyDepartment->department->title;                                // название компании
                $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($company_id);               // путь до департамента работника
                if ($response['status'] == 1) {
                    $department_path = $response['Items'];                                                              // путь до департамента работника
                } else {
                    $department_path = "";
                }
                $company_department_id = $user->worker->companyDepartment->id;                                          // записываем главный ключ департамента подразделения
                $department_type_id = $user->worker->companyDepartment->department_type_id;                             // записываем тип департамента подразделения
                $tabel_number = $user->worker->tabel_number;
                $worker_id = $user->worker->id;
                $position_id = $user->worker->position_id;                                                              // ключ должности пользователя
                $position_title = $user->worker->position->title;                                                       // должность пользователя
                $position_qualification = $user->worker->position->qualification;                                       // квалификация пользователя
                $worker_date_start = $user->worker->date_start;                                                         // дата начала работы пользоавтеля в данной должности
                $worker_date_end = $user->worker->date_end;                                                             // дата окончания работы пользоавтеля в данной должности
                foreach ($user->worker->workerObjects as $workerObjects) {
                    $workerObject[] = $workerObjects['id'];
                }
                //todo сделать заполнение роли при создании сотрудника иначе работать не будет в таблицу worker_object
                $worker_roles = $user->worker->workerObjects;
                foreach ($worker_roles as $worker_role) {

                    $worker_role_list[$worker_role->role_id] = $worker_role->role ? $worker_role->role->title : "Прочее";                              //роль пользователя
                    $worker_role_id = $worker_role->role_id;
                }
                // старый метод определения шахты - теперь используется метод по определению шахты заданной по умолчанию в common/config AMICUM_DEFAULT_MINE
//                                $mine = Mine::findOne(['company_id' => $company_id]);                                 // Ищем шахту по company_id
//                                if ($mine) {                                                                          // Если есть совпадение
//                                    $mine_id = $mine->id;                                                             // Записываем id шахты
//                                    $mine_title = $mine->title;                                                       // Записываем id шахты
//                                } else $mine_id = "-1";
                $mine_id = $user->mine_id;
                if (!$mine_id) {
                    $mine_id = AMICUM_DEFAULT_MINE;
                    $mine_title = AMICUM_DEFAULT_MINE_TITLE;
                    $mine_company_id = AMICUM_DEFAULT_MINE_COMPANY_ID;
                } else {
                    $mine_title = $user->mine->title;
                    $mine_company_id = $user->mine->company_id;
                }

                $response = ConfigAmicumController::GetParametersConfigAmicum(json_encode(['parameters' => ["session_amicum_userTheme_$user_id"]]));
                if (isset($response['Items']["session_amicum_userTheme_$user_id"])) {
                    $userTheme = $response['Items']["session_amicum_userTheme_$user_id"]['value'];
                } else {
                    $userTheme = "light";
                }

                //фото - 2D модель
                $worker_value = (new WorkerCacheController())->getParameterValueHash($worker_id, 3, 1);
                $user_image_src = $worker_value ? $worker_value['value'] : "";

                $session_amicum->open();                                                                                // Открываем сессию
                $session_id = session_id();                                                                             // Выводим сообщение об успешной авторизации
                $session_amicum['sessionLogin'] = $user->login;                                                               // Записываем в сессию логин
                $session_amicum['sessionPassword'] = "";                                                                // Записываем в сессию пароль
                $session_amicum['userWorkstation'] = $user->getWorkstation()->one()->title;                             // Записываем в сессию рабочее место
                $session_amicum['employee_id'] = $employee_id;                                                          // Записываем в сессию уникальный ключ человека
                $session_amicum['userName'] = $fullname;                                                                // Записываем в сессию ФИО пользователя
                $session_amicum['userFullName'] = $lastName . " " . $firstName . " " . ($patronymic ? $patronymic : '');// Записываем в сессию ФИО пользователя
                $session_amicum['last_name'] = $lastName;                                                               // Записываем в сессию Ф пользователя
                $session_amicum['first_name'] = $firstName;                                                             // Записываем в сессию И пользователя
                $session_amicum['patronymic'] = $patronymic;                                                            // Записываем в сессию О пользователя
                $session_amicum['gender'] = $gender;                                                                    // гендерный признак пользователя
                $session_amicum['birthdate'] = $birthdate;                                                              // день рождения
                $session_amicum['position_id'] = $position_id;                                                          // ключ должности пользователя
                $session_amicum['position_title'] = $position_title;                                                    // должность пользователя
                $session_amicum['position_qualification'] = $position_qualification;                                    // квалификация пользователя
                $session_amicum['worker_date_start'] = $worker_date_start;                                              // дата начала работы пользователя
                $session_amicum['worker_date_end'] = $worker_date_end;                                                  // дата окончания работы пользователя
                $session_amicum['userShift'] = $shift;                                                                  // Записываем в сессию смену
                $session_amicum['userCompany'] = $company;                                                              // Записываем в сессию наименование предприятия
                $session_amicum['userCompanyId'] = $company_id;                                                         // Записываем в сессию id Предприятия
                $session_amicum['userMineId'] = $mine_id;                                                               // Записываем в сессию id Шахты
                $session_amicum['userMineTitle'] = $mine_title;                                                         // Записываем в сессию наименование Шахты
                $session_amicum['mineCompanyId'] = $mine_company_id;                                                    // Записываем в сессию ключ подразделения Шахты
                $session_amicum['userDepartmentId'] = $department_id;                                                   // Записываем в сессию ключ департамента справочник
                $session_amicum['userDepartmentTitle'] = $department_title;                                             // Записываем в сессию название департамента справочник
                $session_amicum['userDepartmentPath'] = $department_path;                                               // Записываем в сессию путь до департамента пользователя
                $session_amicum['userDepartmentTypeId'] = $department_type_id;                                          // Записываем в сессию тип департамента
                $session_amicum['userCompanyDepartmentId'] = $company_department_id;                                    // Записываем в сессию ключ конкретного департамента (текущий выбранный в фильтре департамент)
                $session_amicum['userWorkCompanyDepartmentId'] = $company_department_id;                                // Записываем в сессию ключ текущего департамента пользователя (в котором он работает - во время работы системы меняться не может)
                $session_amicum['worker_id'] = $worker_id;                                                              // Записываем в сессию ключ работника
                $session_amicum['user_id'] = $user_id;                                                                  // Записываем в сессию ключ пользователя
                $session_amicum['workerObject_ids'] = $workerObject;                                                    // Записываем в сессию ключ работника
                $session_amicum['userStaffNumber'] = $tabel_number;                                                     // Записываем в сессию табельный номер работника
                $session_amicum['tabel_number'] = $tabel_number;                                                        // Записываем в сессию табельный номер работника
                $session_amicum['session_id'] = $session_id;                                                            // Записываем в сессию табельный номер работника
                $session_amicum['worker_role'] = $worker_role_list;                                                     // роль пользователя
                $session_amicum['user_image_src'] = $user_image_src;                                                    // путь до фотографии пользователя
                $session_amicum['userTheme'] = $userTheme;                                                              // Записываем в сессию тему пользователя

                //по какой то причине добавленные в сессию переменные не передаются на фронт, но при этомдоступны из среды php
                $session_send['sessionLogin'] = $user->login;                                                                 // Записываем в сессию логин
                $session_send['sessionPassword'] = "";                                                                  // Записываем в сессию пароль
                $session_send['userWorkstation'] = $user->getWorkstation()->one()->title;                               // Записываем в сессию рабочее место
                $session_send['employee_id'] = $employee_id;                                                            // Записываем в сессию уникальный ключ человека
                $session_send['userName'] = $fullname;                                                                  // Записываем в сессию ФИО пользователя
                $session_send['userFullName'] = $lastName . " " . $firstName . " " . ($patronymic ? $patronymic : '');  // Записываем в сессию ФИО пользователя
                $session_send['last_name'] = $lastName;                                                                 // Записываем в сессию Ф пользователя
                $session_send['first_name'] = $firstName;                                                               // Записываем в сессию И пользователя
                $session_send['patronymic'] = $patronymic;                                                              // Записываем в сессию О пользователя
                $session_send['gender'] = $gender;                                                                      // гендерный признак пользователя
                $session_send['birthdate'] = $birthdate;                                                                // день рождения
                $session_send['position_id'] = $position_id;                                                            // ключ должности пользователя
                $session_send['position_title'] = $position_title;                                                      // должность пользователя
                $session_send['position_qualification'] = $position_qualification;                                      // квалификация пользователя
                $session_send['worker_date_start'] = $worker_date_start;                                                // дата начала работы пользователя
                $session_send['worker_date_end'] = $worker_date_end;                                                    // дата окончания работы пользователя
                $session_send['userShift'] = $shift;                                                                    // Записываем в сессию смену
                $session_send['userCompany'] = $company;                                                                // Записываем в сессию наименование предприятия
                $session_send['userCompanyId'] = $company_id;                                                           // Записываем в сессию id Предприятия
                $session_send['userMineId'] = $mine_id;                                                                 // Записываем в сессию id Шахты
                $session_send['userMineTitle'] = $mine_title;                                                           // Записываем в сессию наименование Шахты
                $session_send['mineCompanyId'] = $mine_company_id;                                                      // Записываем в сессию ключ подразделения Шахты
                $session_send['userDepartmentId'] = $department_id;                                                     // Записываем в сессию ключ департамента справочник
                $session_send['userDepartmentTitle'] = $department_title;                                               // Записываем в сессию название департамента справочник
                $session_send['userDepartmentPath'] = $department_path;                                                 // Записываем в сессию путь до департамента пользователя
                $session_send['userDepartmentTypeId'] = $department_type_id;                                            // Записываем в сессию тип департамента
                $session_send['userCompanyDepartmentId'] = $company_department_id;                                      // Записываем в сессию ключ конкретного департамента (текущий выбранный в фильтре департамент)
                $session_send['userWorkCompanyDepartmentId'] = $company_department_id;                                  // Записываем в сессию ключ текущего департамента пользователя (в котором он работает - во время работы системы меняться не может)
                $session_send['worker_id'] = $worker_id;                                                                // Записываем в сессию ключ работника
                $session_send['user_id'] = $user_id;                                                                    // Записываем в сессию ключ пользователя
                $session_send['workerObject_ids'] = $workerObject;                                                      // Записываем в сессию ключ конкретных работника
                $session_send['userStaffNumber'] = $tabel_number;                                                       // Записываем в сессию табельный номер работника
                $session_send['tabel_number'] = $tabel_number;                                                          // Записываем в сессию табельный номер работника
                $session_send['session_id'] = $session_id;                                                              // Записываем в сессию табельный номер работника
                $session_send['worker_role'] = $worker_role_list;                                                       // роль пользователя
                $session_send['user_image_src'] = $user_image_src;                                                      // путь до фотографии пользователя
                $session_send['userTheme'] = $userTheme;                                                                // Записываем в сессию тему пользователя

                //генерируем уникальный ключ для общения с сокет сервером данного клиента
                $secure_id = substr(password_hash(Assistant::RandomString(20), PASSWORD_DEFAULT), 6, 21);
                $session_amicum['socket_key'] = $secure_id;
                $session_send['socket_key'] = $secure_id;
                $cache = Yii::$app->cache;
                $cache->set("WebSocket_" . $secure_id, $session_id);                                                // записываем ключ вебсокет сервера, чтобы потом при вызове метода проверку делали, на то что авторизован ли вебсокет сервер
                $result = $session_send;
                $status *= 1;
                $warnings[] = $method_name . ". Заполнил сессию и отправляю данные клиенту";

                $worker_data = array('role_id' => $worker_role_id, 'tabel_number' => $tabel_number);

            } catch (Exception $e) {
                $status = 0;
                $errors[] = $e->getLine();
                $errors[] = $e->getMessage();
            }
        } else {
            $errors[] = $method_name . ". Входной массив обязательных данных пуст. Имя пользователя не передано.";
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => [], 'session_id' => $session_id, 'worker_data' => $worker_data);
    }

    //
    //actionGetCsrf - метод для получения токена CSRF на авторизацию
    // пример вызова http://127.0.0.1/user-autorization/get-csrf
    // user-autorization/get-csrf
    public function actionGetCsrf()
    {
        $request = Yii::$app->getRequest();
        $csrf_token = $request->getCsrfToken();
        $csrfParam = $request->csrfParam;
        $result_main = array('csrf-param' => $csrfParam, 'csrf-token' => $csrf_token);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод AutorizationAD() - Авторизация в Active Directory
     * @param $login_ad - логин пользователя из Active Directory
     * @param $password - введённый пароль пользователя
     * @return array - успешное подключение или нет
     *
     * @package frontend\controllers
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 23.03.2020 15:35
     */
    public static function AutorizationAD($login_ad, $password)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'AutorizationAD';
        $warnings[] = $method_name . '. Начало метода';
        try {
            /**
             * Подключение к серверу: severstalgroup.com, с портом: 389
             * VRKDC01.vrk.severstalgroup.com
             * VRKDC02.vrk.severstalgroup.com
             * STAL-VRK-DC01.severstal.severstalgroup.com
             * severstalgroup.com
             */
            putenv('LDAPTLS_REQCERT=allow');
            $ad = ldap_connect(AD_HOST, AD_PORT);
            if (!isset($ad) || empty($ad)) {
                throw new Exception($method_name . ". Не смог подключиться к службе авторизации АМИКУМ");
            }
            /**
             * Опции подключение к серверу
             */
            ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, AD_VERSION_PROTOCOL);
            ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
            /**
             * Авторизация на сервере по: подключению, логину и паролю
             */
            $ldapbind = ldap_bind($ad, $login_ad, $password);
            if ($ldapbind) {
                $result = true;
            } else {
                $result = false;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
            $result = false;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // GetSystemAmicumRole - метод получения ролей доступа в систему
    // выходные данные:
    //  system_role:
    //      {workstation_id}
    //              workstation_id          -   ключ рабочего места (роль пользователя)
    //              workstation_title       -   название рабочего места
    //              modules:                -   модули АМИКУМ
    //                  {module_amicum_id}           - ключ модуля
    //                      module_amicum_id         - ключ модуля
    //                      module_amicum_title      - название раздела АМИКУМ
    //                      workstation_pages:       - разделы АМИКУМ (страницы)
    //                          {workstation_page_id}       - ключ разрешения
    //                                  workstation_page_id         - ключ разрешения
    //                                  page_id                     - ключ раздела АМИКУМ (страницы)
    //                                  page_title                  - название раздела АМИКУМ (страницы)
    //                                  page_url                    - адресс страницы
    //                                  access_id                   - ключ метода
    //                                  access_title                - название метода
    //                                  access_description          - описание метода
    //                                  permission_amicum           - права доступа (1/да/не разрешено - 0/нет/запрет)
    //
    // разработал: Якимов М.Н.
    // пример: 127.0.0.1/read-manager-amicum?controller=UserAutorization&method=GetSystemAmicumRole&subscribe=&data={}
    public static function GetSystemAmicumRole()
    {
        // Стартовая отладочная информация
        $method_name = 'GetSystemAmicumRole';                                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
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

            $workstation_pages = WorkstationPage::find()
                ->with('workstation')
                ->with('page')
                ->with('access')
                ->with('modulAmicum')
                ->all();

            foreach ($workstation_pages as $workstation_page) {
                $system_role[$workstation_page['workstation_id']]['workstation_id'] = $workstation_page['workstation_id'];
                $system_role[$workstation_page['workstation_id']]['workstation_title'] = $workstation_page['workstation']['title'];
                $system_role[$workstation_page['workstation_id']]['workstation_default'] = $workstation_page['workstation']['default'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['module_amicum_id'] = $workstation_page['modul_amicum_id'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['module_amicum_title'] = $workstation_page['modulAmicum']['title'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['workstation_pages'][$workstation_page['id']]['workstation_page_id'] = $workstation_page['id'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['workstation_pages'][$workstation_page['id']]['page_id'] = $workstation_page['page_id'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['workstation_pages'][$workstation_page['id']]['page_title'] = $workstation_page['page']['title'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['workstation_pages'][$workstation_page['id']]['page_url'] = $workstation_page['page']['url'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['workstation_pages'][$workstation_page['id']]['access_id'] = $workstation_page['access_id'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['workstation_pages'][$workstation_page['id']]['access_title'] = $workstation_page['access']['title'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['workstation_pages'][$workstation_page['id']]['access_description'] = $workstation_page['access']['description'];
                $system_role[$workstation_page['workstation_id']]['modules'][$workstation_page['modul_amicum_id']]['workstation_pages'][$workstation_page['id']]['permission_amicum'] = $workstation_page['permission_amicum'];
            }

            if (isset($system_role)) {
                $result = $system_role;
            } else {
                $result = (object)array();
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                   // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                         // общая продолжительность выполнения скрипта
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


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveSystemAmicumRole - метод сохранения ролей доступа в систему
    // выходные данные:
    //  system_role:
    //              workstation_id          -   ключ рабочего места (роль пользователя)
    //              workstation_title       -   название рабочего места
    //              modules:                -   модули АМИКУМ
    //                  {module_amicum_id}           - ключ модуля
    //                      module_amicum_id         - ключ модуля
    //                      module_amicum_title      - название раздела АМИКУМ
    //                      workstation_pages:       - разделы АМИКУМ (страницы)
    //                          {workstation_page_id}       - ключ разрешения
    //                                  workstation_page_id         - ключ разрешения
    //                                  page_id                     - ключ раздела АМИКУМ (страницы)
    //                                  page_title                  - название раздела АМИКУМ (страницы)
    //                                  access_id                   - ключ метода
    //                                  access_title                - название метода
    //                                  access_description          - описание метода
    //                                  permission_amicum           - права доступа (1/да/не разрешено - 0/нет/запрет)
    //
    // разработал: Якимов М.Н.
    // пример: 127.0.0.1/read-manager-amicum?controller=UserAutorization&method=SaveSystemAmicumRole&subscribe=&data={}
    public static function SaveSystemAmicumRole($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SaveSystemAmicumRole';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'system_role'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $system_role = $post_dec->system_role;

            // ищем рабочее место, если такого айди нет, то создаем с нуля
            $workstation_id = $system_role->workstation_id;
            $workstation_title = $system_role->workstation_title;
            $workstation = Workstation::findOne(['id' => $workstation_id]);
            if (!$workstation) {
                $workstation = Workstation::findOne(['title' => $workstation_title]);
                if (!$workstation) {
                    $workstation = new Workstation();
                    $workstation->title = $system_role->workstation_title;
                    $workstation->default = 1;
                    if (!$workstation->save()) {
                        $errors[] = $workstation->errors;
                        throw new Exception($method_name . '. Ошибка сохранения модели Workstation');
                    }
                }
                $workstation_id = $workstation->id;
                $system_role->workstation_id = $workstation_id;
            }

            // удаляем ранее созданные права у роли и записываем новые права
            WorkstationPage::deleteAll(['workstation_id' => $workstation_id]);

            // получаем справочники за раз из БД для ускорения записи и поиска
            $page_hand = Page::find()->indexBy('id')->all();
            $access_hand = Access::find()->indexBy('id')->all();
            $module_hand = ModulAmicum::find()->indexBy('id')->all();
            // перебираем модули амикум, если модуля нет, то создаем его
            foreach ($system_role->modules as $module) {
                $module_amicum_id = $module->module_amicum_id;
                $module_title = $module->module_amicum_title;
//                $module_amicum = ModulAmicum::findOne(['id' => $module_amicum_id]);
                if (!isset($module_hand[$module_amicum_id])) {
                    $module_amicum = ModulAmicum::findOne(['title' => $module_title]);
                    if (!$module_amicum) {
                        $module_amicum = new ModulAmicum();
                        $module_amicum->title = $module_title;
                        if (!$module_amicum->save()) {
                            $errors[] = $module_amicum->errors;
                            throw new Exception($method_name . '. Ошибка сохранения модели ModulAmicum');
                        }
                    }
                    $module_amicum_id = $module_amicum->id;
                    $module->module_amicum_id = $module_amicum_id;
                    $module_hand[$module_amicum_id] = array('id' => $module_amicum_id, 'title' => $module_title);
//                    $warnings[]=$method_name . "module создан с нуля";
                }

                // перебираем сами права пользователя, если права такого нет, то создаем его, иначе обновляем
                foreach ($module->workstation_pages as $workstation_page) {
                    //                          {workstation_page_id}       - ключ разрешения
                    //                                  workstation_page_id         - ключ разрешения
                    //                                  page_id                     - ключ раздела АМИКУМ (страницы)
                    //                                  page_title                  - название раздела АМИКУМ (страницы)
                    //                                  page_url                    - адресс страницы
                    //                                  access_id                   - ключ метода
                    //                                  access_title                - название метода
                    //                                  access_description          - описание метода
                    //                                  permission_amicum           - права доступа (1/да/не разрешено - 0/нет/запрет)

                    // готовим разделы АМИКУМ
                    $page_id = $workstation_page->page_id;
                    $page_title = $workstation_page->page_title;
//                    $page = Page::findOne(['id' => $page_id]);
                    if (!isset($page_hand[$page_id])) {
                        $page = Page::findOne(['title' => $page_title]);
                        if (!$page) {
                            $page = new Page();
                            $page->title = $page_title;
                            $page->url = $workstation_page->page_url;
                            if (!$page->save()) {
                                $errors[] = $page->errors;
                                throw new Exception($method_name . '. Ошибка сохранения модели Page');
                            }
                        }
                        $page_id = $page->id;
                        $workstation_page->page_id = $page_id;
                        $page_hand[$page_id] = array('id' => $page_id, 'title' => $page_title, 'url' => $workstation_page->page_url);
//                        $warnings[]=$method_name . "page создан с нуля";
                    }
                    // готовим методы АМИКУМ
                    $access_id = $workstation_page->access_id;
                    $access_title = $workstation_page->access_title;
//                    $access = Access::findOne(['id' => $access_id]);
                    if (!isset($access_hand[$access_id])) {
                        $access = Access::findOne(['title' => $access_title]);
                        if (!$access) {
                            $access = new Access();
                            $access->title = $access_title;
                            $access->description = $workstation_page->access_description;
                            $access->page_id = $page_id;
                            if (!$access->save()) {
                                $errors[] = $access->errors;
                                throw new Exception($method_name . '. Ошибка сохранения модели Page');
                            }
                        }
                        $access_id = $access->id;
                        $workstation_page->access_id = $access_id;
                        $access_hand[$access_id] = array('id' => $access_id, 'title' => $access_title, 'description' => $workstation_page->access_description, 'page_id' => $page_id);
//                        $warnings[]=$method_name . "access создан с нуля";
                    }
                    // сохраняем права роли АМИКУМ

//                    $workstation_page_id = $workstation_page->workstation_page_id;
//                    $workstation_page_add = WorkstationPage::findOne(['id' => $workstation_page_id]);
//                    if (!$workstation_page_add) {
//                        $workstation_page_add = new WorkstationPage();
//                    }
//                    $workstation_page_add->workstation_id = $workstation_id;
//                    $workstation_page_add->access_id = $access_id;
//                    $workstation_page_add->page_id = $page_id;
//                    $workstation_page_add->modul_amicum_id = $module_amicum_id;
//                    $workstation_page_add->permission_amicum = $workstation_page->permission_amicum;
//                    if (!$workstation_page_add->save()) {
//                        $errors[] = $workstation_page_add->errors;
//                        throw new \Exception($method_name . '. Ошибка сохранения модели WorkstationPage');
//                    }
//                    $workstation_page_add->refresh();
//                    $workstation_page_id = $workstation_page_add->id;
//
//                    $workstation_page->workstation_page_id = $workstation_page_id;
                    $workstation_page_adds[] = array(
                        'workstation_id' => $workstation_id,
                        'access_id' => $access_id,
                        'page_id' => $page_id,
                        'modul_amicum_id' => $module_amicum_id,
                        'permission_amicum' => $workstation_page->permission_amicum,
                    );
                }
            }
            if (isset($workstation_page_adds)) {
                $insert_array = Yii::$app->db->createCommand()->batchInsert('workstation_page', ['workstation_id', 'access_id', 'page_id', 'modul_amicum_id', 'permission_amicum'], $workstation_page_adds)->execute();
                if ($insert_array !== 0) {
                    $warnings[] = $method_name . 'вставил данные в таблицу workstation_page';
                } else {
                    $warnings[] = $method_name . 'не вставил данные в таблицу workstation_page';
                }
            }
            $result = $system_role;
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

// запись в БД начала выполнения скрипта
// TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод GetWorkstation() - Получение справочника рабочих мест
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                // идентификатор рабочего места
     *      "title":"Главный механик"               // наименование рабочего места
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=GetWorkstation&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 25.03.2020 16:02
     */
    public static function GetWorkstation()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetWorkstation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $workstations = Workstation::find()
                ->asArray()
                ->all();
            if (empty($workstations)) {
                $warnings[] = $method_name . '. Справочник рабочих мест пуст';
            } else {
                $result = $workstations;
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
     * Метод GetModuleAmicum() - Получение справочника модулей АМИКУМ
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                // идентификатор модуля АМИКУМ
     *      "title":"Электронная книга нарядов"     // наименование модуля АМИКУМ
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=GetModuleAmicum&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 25.03.2020 16:02
     */
    public static function GetModuleAmicum()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetModuleAmicum';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $module_amicum = ModulAmicum::find()
                ->asArray()
                ->all();

            if (empty($module_amicum)) {
                $warnings[] = $method_name . '. Справочник модулей амикум пуст';
            } else {
                $result = $module_amicum;
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
     * Метод GetPage() - Получение справочника разделов/страниц АМИКУМ
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                // идентификатор раздела АМИКУМ
     *      "title":"Табличная форма выдачи наряда" // наименование раздела АМИКУМ
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=GetPage&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 25.03.2020 16:02
     */
    public static function GetPage()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetPage';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $pages = Page::find()
                ->asArray()
                ->all();

            if (empty($pages)) {
                $warnings[] = $method_name . '. Справочник разделов/страниц амикум пуст';
            } else {
                $result = $pages;
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
     * Метод GetAccess() - Получение справочника методов АМИКУМ
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                // идентификатор метода АМИКУМ
     *      "title":"actionMo"                      // наименование метода
     *      "description":"Отключение конвейера"    // описание метода
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=GetAccess&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 25.03.2020 16:02
     */
    public static function GetAccess()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetAccess';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $access = Access::find()
                ->asArray()
                ->all();

            if (empty($access)) {
                $warnings[] = $method_name . '. Справочник методов амикум пуст';
            } else {
                $result = $access;
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
     * Метод GetUsers() - Получение справочника пользоателей системы АМИКУМ
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *               id                      -   ключ пользователя
     *               login                   -   логин пользователя в системе АМИКУМ
     *               full_name               -   полное имя пользователя
     *               workstation_id          -   роль пользователя из систем АМИКУМ (устаревшее)
     *               default                 -   роль по умолчанию (на будущее)
     *               email                   -   электронка пользователя в АМИКУМ
     *               user_ad_id              -   логин пользователя в системе AD
     *               props_ad_upd            -   электронка в AD пользователя
     *               date_time_sync          -   время синхронизации учетки с AD
     *               worker_id               -   ключ работника
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=GetUsers&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 25.03.2020 16:02
     */
    public static function GetUsers()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetAccess';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $users = User::find()
                ->with('worker.employee')
                ->asArray()
                ->all();

            foreach ($users as $user) {
                if ($user['worker']) {
                    $full_name = $user['worker']['employee']['last_name'] . " " . $user['worker']['employee']['first_name'] . " " . $user['worker']['employee']['patronymic'];
                } else {
                    $full_name = "";
                }
                $users_result[] = array(
                    'id' => $user['id'],
                    'login' => $user['login'],
                    'full_name' => $full_name,
                    'workstation_id' => $user['workstation_id'],
                    'default' => $user['default'],
                    'email' => $user['email'],
                    'user_ad_id' => $user['user_ad_id'],
                    'props_ad_upd' => $user['props_ad_upd'],
                    'date_time_sync' => $user['date_time_sync'],
                    'worker_id' => $user['worker_id']
                );
            }

            if (!isset($users_result)) {
                $warnings[] = $method_name . '. Справочник пользователей системы амикум пуст';
            } else {
                $result = $users_result;
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

    // DeleteSystemAmicumRole - метод удаления роли доступа в систему
    // выходные данные:
    //              workstation_id          -   ключ рабочего места (роль пользователя)
    // разработал: Якимов М.Н.
    // пример: 127.0.0.1/read-manager-amicum?controller=UserAutorization&method=DeleteSystemAmicumRole&subscribe=&data={"workstation_id":12}
    public static function DeleteSystemAmicumRole($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeleteSystemAmicumRole';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'workstation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $workstation_id = $post_dec->workstation_id;

            // удаляем ранее созданные права у роли и записываем новые права
            $result = WorkstationPage::deleteAll(['workstation_id' => $workstation_id]);

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

// запись в БД начала выполнения скрипта
// TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetUserAmicumRole - метод получения ролей доступа пользователя в систему
    // выходные данные:
    //  user_role:
    //      {user_id}
    //              user_id                 -   ключ пользователя
    //              login                   -   логин пользователя в системе АМИКУМ
    //              full_name               -   полное имя пользователя
    //              default                 -   роль по умолчанию (на будущее)
    //              email                   -   электронка пользователя в АМИКУМ
    //              user_ad_id              -   логин пользователя в системе AD
    //              props_ad_upd            -   электронка в AD пользователя
    //              date_time_sync          -   время синхронизации учетки с AD
    //              tabel_number            -   табельный номер пользователя
    //              worker_id               -   ключ работника
    //              workstations            -   роли пользователя
    //                  {user_workstation_id}            ключ роли пользователя
    //                          user_workstation_id         - ключ роли пользователя
    //                          workstation_id              - ключ роли системы
    //                          workstation_title           - название роли системы
    //
    // разработал: Якимов М.Н.
    // пример: 127.0.0.1/read-manager-amicum?controller=UserAutorization&method=GetUserAmicumRole&subscribe=&data={}
    public static function GetUserAmicumRole()
    {
        // Стартовая отладочная информация
        $method_name = 'GetUserAmicumRole';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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

            $user_workstations = UserWorkstation::find()
                ->with('workstation')
                ->with('user')
                ->with('user.worker.employee')
                ->all();

            //      {user_id}
            //              user_id                 -   ключ пользователя
            //              login                   -   логин пользователя в системе АМИКУМ
            //              full_name               -   полное имя пользователя
            //              default                 -   роль по умолчанию (на будущее)
            //              email                   -   электронка пользователя в АМИКУМ
            //              user_ad_id              -   логин пользователя в системе AD
            //              props_ad_upd            -   электронка в AD пользователя
            //              date_time_sync          -   время синхронизации учетки с AD
            //              tabel_number            -   табельный номер пользователя
            //              worker_id               -   ключ работника
            //              workstations            -   роли пользователя
            //                  {user_workstation_id}            ключ роли пользователя
            //                          user_workstation_id         - ключ роли пользователя
            //                          workstation_id              - ключ роли системы
            //                          workstation_title           - название роли системы
            foreach ($user_workstations as $user_workstation) {
                $user_id = $user_workstation['user_id'];
                if (isset($user_workstation['user']) and isset($user_workstation['user']['worker'])) {
                    $full_name = $user_workstation['user']['worker']['employee']['last_name'] . " " . $user_workstation['user']['worker']['employee']['first_name'] . " " . $user_workstation['user']['worker']['employee']['patronymic'];
                    $tabel_number = $user_workstation['user']['worker']['tabel_number'];
                } else {
                    $full_name = "";
                    $tabel_number = "";
                }
                $user_role[$user_id]['user_id'] = $user_id;
                $user_role[$user_id]['login'] = $user_workstation['user']['login'];
                $user_role[$user_id]['full_name'] = $full_name;
                $user_role[$user_id]['default'] = $user_workstation['user']['default'];
                $user_role[$user_id]['user_ad_id'] = $user_workstation['user']['user_ad_id'];
                $user_role[$user_id]['props_ad_upd'] = $user_workstation['user']['props_ad_upd'];
                $user_role[$user_id]['date_time_sync'] = $user_workstation['user']['date_time_sync'];
                $user_role[$user_id]['tabel_number'] = $tabel_number;
                $user_role[$user_id]['worker_id'] = $user_workstation['user']['worker_id'];
                $user_role[$user_id]['workstations'][$user_workstation['id']]['user_workstation_id'] = $user_workstation['id'];
                $user_role[$user_id]['workstations'][$user_workstation['id']]['workstation_id'] = $user_workstation['workstation']['id'];
                $user_role[$user_id]['workstations'][$user_workstation['id']]['workstation_title'] = $user_workstation['workstation']['title'];
            }

            if (isset($user_role)) {
                $result = $user_role;
            } else {
                $result = (object)array();
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

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // SaveUserAmicumRole - метод сохранения прав доступа пользователей в систему
    // выходные данные:
    //  user_role:
    //              user_id                 -   ключ пользователя
    //              login                   -   логин пользователя в системе АМИКУМ
    //              full_name               -   полное имя пользователя
    //              default                 -   роль по умолчанию (на будущее)
    //              email                   -   электронка пользователя в АМИКУМ
    //              user_ad_id              -   логин пользователя в системе AD
    //              props_ad_upd            -   электронка в AD пользователя
    //              date_time_sync          -   время синхронизации учетки с AD
    //              tabel_number            -   табельный номер пользователя
    //              worker_id               -   ключ работника
    //              workstations            -   роли пользователя
    //                  {user_workstation_id}            ключ роли пользователя
    //                          user_workstation_id         - ключ роли пользователя
    //                          workstation_id              - ключ роли системы
    //                          workstation_title           - название роли системы
    // разработал: Якимов М.Н.
    // пример: 127.0.0.1/read-manager-amicum?controller=UserAutorization&method=SaveUserAmicumRole&subscribe=&data={}
    public static function SaveUserAmicumRole($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SaveUserAmicumRole';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'user_role'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $user_role = $post_dec->user_role;

            //              user_id                 -   ключ пользователя
            //              login                   -   логин пользователя в системе АМИКУМ
            //              full_name               -   полное имя пользователя
            //              default                 -   роль по умолчанию (на будущее)
            //              email                   -   электронка пользователя в АМИКУМ
            //              user_ad_id              -   логин пользователя в системе AD
            //              props_ad_upd            -   электронка в AD пользователя
            //              date_time_sync          -   время синхронизации учетки с AD
            //              tabel_number            -   табельный номер пользователя
            //              worker_id               -   ключ работника
            //              workstations            -   роли пользователя
            //                  {user_workstation_id}            ключ роли пользователя
            //                          user_workstation_id         - ключ роли пользователя
            //                          workstation_id              - ключ роли системы
            //                          workstation_title           - название роли системы

            // ищем рабочее место, если такого айди нет, то создаем с нуля
            $user_id = $user_role->user_id;
            $user = User::findOne(['id' => $user_id]);
            if (!$user) {
                throw new Exception($method_name . '. Пользователя системы не существует в БД');
            }

            UserWorkstation::deleteAll(['user_id' => $user_id]);

            foreach ($user_role->workstations as $user_workstation) {
                $user_workstation_add = new UserWorkstation();
                $user_workstation_add->workstation_id = $user_workstation->workstation_id;
                $user_workstation_add->user_id = $user_id;

                if (!$user_workstation_add->save()) {
                    throw new Exception($method_name . '. Ошибка сохранения модели UserWorkstation');
                }

                $user_workstation_id = $user_workstation_add->id;
                $user_workstation->user_workstation_id = $user_workstation_id;
            }
            $result = $user_role;
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

// запись в БД начала выполнения скрипта
// TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // DeleteUserAmicumRole - метод удаления прав доступа пользователей в систему
    // выходные данные:
    //              user_id                 -   ключ пользователя
    // разработал: Якимов М.Н.
    // пример: 127.0.0.1/read-manager-amicum?controller=UserAutorization&method=DeleteUserAmicumRole&subscribe=&data={"user_id":12}
    public static function DeleteUserAmicumRole($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeleteUserAmicumRole';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'user_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $user_id = $post_dec->user_id;

            $result = UserWorkstation::deleteAll(['user_id' => $user_id]);

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

// запись в БД начала выполнения скрипта
// TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // checkPermissionUser - метод проверки наличия прав на получение данных из системы
    // входные данные:
    //              user_id                 -   ключ пользователя
    //              check_method_name       -   метод к которому запрашивается доступ
    // выходные параметры:
    //              true/false
    // разработал: Якимов М.Н.
    // пример:
    public static function checkPermissionUser($user_id, $check_method_name)
    {
        // Стартовая отладочная информация
        $method_name = 'checkPermissionUser';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
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
        $result = false;                                                                                                 // результирующий массив (если требуется)
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

            if (
                $user_id == NULL
                or $user_id == ''
                or $check_method_name == NULL
                or $check_method_name == ''
            ) {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';

            $user_workstation = (new Query())
                ->select('workstation_page.id')
                ->from('user_workstation')
                ->innerJoin('workstation_page', "workstation_page.workstation_id=user_workstation.workstation_id")
                ->innerJoin('access', "access.id=workstation_page.access_id")
                ->where(['user_workstation.user_id' => $user_id])
                ->andWhere(['access.title' => $check_method_name])
                ->andWhere(['workstation_page.permission_amicum' => 1])
                ->all();
            if ($user_workstation) {
                $result = true;
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

// запись в БД начала выполнения скрипта
// TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveModulAmicum() - Сохранение справочника модулей амикум
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "module_amicum":
     *  {
     *      "module_amicum_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "module_amicum_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=SaveModulAmicum&subscribe=&data={"module_amicum":{"module_amicum_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveModulAmicum($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveModulAmicum';
        $handbook_module_amicum_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'module_amicum'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_module_amicum_id = $post_dec->module_amicum->module_amicum_id;
            $title = $post_dec->module_amicum->title;
            $new_handbook_module_amicum_id = ModulAmicum::findOne(['id' => $handbook_module_amicum_id]);
            if (empty($new_handbook_module_amicum_id)) {
                $new_handbook_module_amicum_id = new ModulAmicum();
            }
            $new_handbook_module_amicum_id->title = $title;
            if ($new_handbook_module_amicum_id->save()) {
                $new_handbook_module_amicum_id->refresh();
                $handbook_module_amicum_data['id'] = $new_handbook_module_amicum_id->id;
                $handbook_module_amicum_data['title'] = $new_handbook_module_amicum_id->title;
            } else {
                $errors[] = $new_handbook_module_amicum_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника модулей амикум');
            }
            unset($new_handbook_module_amicum_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_module_amicum_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteModulAmicum() - Удаление справочника модулей амикум
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "module_amicum_id": 98             // идентификатор справочника модулей амикум
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=DeleteModulAmicum&subscribe=&data={"module_amicum_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteModulAmicum($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteModulAmicum';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'module_amicum_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_module_amicum_id = $post_dec->module_amicum_id;
            $del_handbook_module_amicum = ModulAmicum::deleteAll(['id' => $handbook_module_amicum_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveUser() - Сохранение справочника пользователей
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "user":
     *  {
     *      user_id                         // ключ пользователя
     *      login                           // логин пользователя
     *      workstation_id                  // ключ рабочего места
     *      default                         // запись по умолчанию
     *      worker_id                       // ключ работника
     *      email                           // электронная почта пользователя
     *      user_ad_id                      // логин из AD
     *      props_ad_upd                    // свойство об обновлении данной учетки
     *      date_time_sync                  // время обновления учетки
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      user_id                         // ключ пользователя
     *      login                           // логин пользователя
     *      workstation_id                  // ключ рабочего места
     *      default                         // запись по умолчанию
     *      worker_id                       // ключ работника
     *      email                           // электронная почта пользователя
     *      user_ad_id                      // логин из AD
     *      props_ad_upd                    // свойство об обновлении данной учетки
     *      date_time_sync                  // время обновления учетки
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=SaveUser&subscribe=&data={"user":{}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveUser($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveUser';
        $handbook_user_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'user'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_user_id = $post_dec->user->user_id;
            $login = $post_dec->user->login;
            $workstation_id = $post_dec->user->workstation_id;
            $default = $post_dec->user->default;
            $worker_id = $post_dec->user->worker_id;
            $email = $post_dec->user->email;
            $user_ad_id = $post_dec->user->user_ad_id;
            $props_ad_upd = $post_dec->user->props_ad_upd;
            $date_time_sync = $post_dec->user->date_time_sync;
            $new_handbook_user_id = User::findOne(['id' => $handbook_user_id]);
            if (empty($new_handbook_user_id)) {
                $new_handbook_user_id = new User();
            }
            $new_handbook_user_id->login = $login;
            $new_handbook_user_id->workstation_id = $workstation_id;
            $new_handbook_user_id->default = $default;
            $new_handbook_user_id->worker_id = $worker_id;
            $new_handbook_user_id->email = $email;
            $new_handbook_user_id->user_ad_id = $user_ad_id;
            $new_handbook_user_id->props_ad_upd = $props_ad_upd;
            $new_handbook_user_id->date_time_sync = $date_time_sync;
            if ($new_handbook_user_id->save()) {
                $new_handbook_user_id->refresh();
                $handbook_user_data['user_id'] = $new_handbook_user_id->id;
                $handbook_user_data['login'] = $new_handbook_user_id->login;
                $handbook_user_data['workstation_id'] = $new_handbook_user_id->workstation_id;
                $handbook_user_data['default'] = $new_handbook_user_id->default;
                $handbook_user_data['worker_id'] = $new_handbook_user_id->worker_id;
                $handbook_user_data['email'] = $new_handbook_user_id->email;
                $handbook_user_data['user_ad_id'] = $new_handbook_user_id->user_ad_id;
                $handbook_user_data['props_ad_upd'] = $new_handbook_user_id->props_ad_upd;
                $handbook_user_data['date_time_sync'] = $new_handbook_user_id->date_time_sync;
                $handbook_user_data['full_name'] = $post_dec->user->full_name;
            } else {
                $errors[] = $new_handbook_user_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника пользователей');
            }
            unset($new_handbook_user_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_user_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteUser() - Удаление справочника пользователей
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "user_id": 98             // идентификатор справочника пользователей
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=DeleteUser&subscribe=&data={"user_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteUser($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteUser';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'user_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_user_id = $post_dec->user_id;
            $del_handbook_user = User::deleteAll(['id' => $handbook_user_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveWorkstation() - Сохранение справочника ролей
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "workstation":
     *  {
     *      "workstation_id":-1,            // ключ роли
     *      "title":"ACTION",               // название роли
     *      "default":"1",                  // роль по умолчанию
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "workstation_id":-1,            // ключ роли
     *      "title":"ACTION",               // название роли
     *      "default":"1",                  // роль по умолчанию
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=SaveWorkstation&subscribe=&data={"workstation":{"workstation_id":-1,"title":"Администратор","default":"1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveWorkstation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок
        $method_name = 'SaveWorkstation';
        $handbook_workstation_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'workstation'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_workstation_id = $post_dec->workstation->workstation_id;
            $title = $post_dec->workstation->title;
            $default = $post_dec->workstation->default;
            $new_handbook_workstation_id = Workstation::findOne(['id' => $handbook_workstation_id]);
            if (empty($new_handbook_workstation_id)) {
                $new_handbook_workstation_id = new Workstation();
            }
            $new_handbook_workstation_id->title = $title;
            $new_handbook_workstation_id->default = $default;
            if ($new_handbook_workstation_id->save()) {
                $new_handbook_workstation_id->refresh();
                $handbook_workstation_data['workstation_id'] = $new_handbook_workstation_id->id;
                $handbook_workstation_data['title'] = $new_handbook_workstation_id->title;
                $handbook_workstation_data['default'] = $new_handbook_workstation_id->default;
            } else {
                $errors[] = $new_handbook_workstation_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника ролей');
            }
            unset($new_handbook_workstation_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $handbook_workstation_data, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteWorkstation() - Удаление справочника ролей
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "workstation_id": 98             // идентификатор справочника ролей
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=UserAutorization&method=DeleteWorkstation&subscribe=&data={"workstation_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteWorkstation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteWorkstation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'workstation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_workstation_id = $post_dec->workstation_id;
            if (!WorkstationPage::findOne(['workstation_id' => $handbook_workstation_id])) {
                $del_handbook_workstation = Workstation::deleteAll(['id' => $handbook_workstation_id]);
            } else {
                throw new Exception('Удаление невозможно. Есть связанные данные в Таблице выдачи прав пользователям');
            }
        } catch (Throwable $exception) {
//            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод AddWorkstationRollToAll() - Добавление прав пользователя в каждую роль
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "module_amicum_id": "-1"    // ключ модуля
     *      "page_id": "-1"             // ключ страницы
     *      "access_id": "-1"           // ключ справочника методов
     *      "access_title": ""          // название метода
     *      "description": ""           // описание методов
     *      "permission_amicum": 1      // права доступа
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example 127.0.0.1/read-manager-amicum?controller=UserAutorization&method=AddWorkstationRollToAll&subscribe=&data={"module_amicum_id": "-1","page_id": "-1","access_id": "-1","access_title": "","description": "","permission_amicum"}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function AddWorkstationRollToAll($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'AddWorkstationRollToAll';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'access_id') ||
                !property_exists($post_dec, 'access_title') ||
                !property_exists($post_dec, 'description') ||
                !property_exists($post_dec, 'page_id') ||
                !property_exists($post_dec, 'permission_amicum') ||
                !property_exists($post_dec, 'module_amicum_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $access_id = $post_dec->access_id;
            $access_title = $post_dec->access_title;
            $description = $post_dec->description;
            $page_id = $post_dec->page_id;
            $permission_amicum = $post_dec->permission_amicum;
            $modul_amicum_id = $post_dec->module_amicum_id;

            // найти есть ли метод или нет такой
            $access = Access::findOne(['id' => $access_id]);
            if (!$access) {
                $access = Access::findOne(['title' => $access_title]);
                if (!$access) {
                    $access = new Access();
                    $access->title = $access_title;
                    $access->description = $description;
                    $access->page_id = $page_id;
                    if (!$access->save()) {
                        $errors[] = $access->errors;
                        throw new Exception($method_name . '. Ошибка сохранения модели Page');
                    }
                }
                $access_id = $access->id;
            }

            $workstations = Workstation::find()->all();

            foreach ($workstations as $workstation) {
                $all_roll_to_insert[] = array(
                    'workstation_id' => $workstation->id,
                    'modul_amicum_id' => $modul_amicum_id,
                    'page_id' => $page_id,
                    'access_id' => $access_id,
                    'permission_amicum' => $permission_amicum
                );
            }

            if (isset($all_roll_to_insert)) {
                $sql = Yii::$app->db_target->queryBuilder->batchInsert('workstation_page', ['workstation_id', 'modul_amicum_id', 'page_id', 'access_id', 'permission_amicum'], $all_roll_to_insert);
                $role_insert = Yii::$app->db_target->createCommand($sql . " ON DUPLICATE KEY UPDATE `workstation_id` = VALUES (`workstation_id`), `modul_amicum_id` = VALUES (`modul_amicum_id`), `page_id` = VALUES (`page_id`), `access_id` = VALUES (`access_id`)")->execute();
                if (!$role_insert) {
                    $errors[] = $method_name . '.Права пользователя добавлены не были. Скорее всего уже существовали';
                }
            }

            $response = self::GetSystemAmicumRole();
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка получения прав пользователей');
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


}